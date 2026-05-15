<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;

/**
 * Fichier : StripeMarketplaceService.php
 *
 * Gère la partie marketplace liée à Stripe Connect.
 *
 * Ce service s'occupe des opérations financières entre InnovShop
 * et les vendeurs :
 * - regrouper les montants dus par vendeur
 * - vérifier que chaque vendeur possède un compte Stripe Connect
 * - créer les transferts Stripe vers les vendeurs
 * - enregistrer les identifiants de transfert dans les OrderItem
 *
 * Important :
 * Le client paie d'abord InnovShop via Stripe Checkout.
 * Ensuite, InnovShop reverse les montants nets aux vendeurs.
 */
class StripeMarketplaceService
{
    private StripeClient $stripe;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        $this->stripe = new StripeClient($_ENV['STRIPE_SECRET_KEY']);
    }

    /**
     * Transfère aux vendeurs les montants nets d'une commande payée.
     *
     * Marketplace - Reversement vendeur après paiement.
     *
     * Cette méthode :
     * - parcourt les lignes de commande
     * - ignore les produits InnovShop/admin
     * - regroupe les montants par vendeur
     * - crée un transfert Stripe pour chaque vendeur
     * - marque les OrderItem comme transférés
     *
     * Exemple :
     * Commande totale : 300 €
     * Vendeur A : 100 € de produits, 10 € commission, 90 € transférés
     * Vendeur B : 200 € de produits, 20 € commission, 180 € transférés
     */
    public function transferSellerAmounts(Order $order): void
    {
        $sellerTransfers = [];

        foreach ($order->getOrderItems() as $orderItem) {
            $seller = $orderItem->getSeller();

            if (!$seller) {
                continue;
            }

            if ($orderItem->getTransferStatus() === 'transferred') {
                continue;
            }

            $sellerProfile = $seller->getSellerProfile();

            if (!$sellerProfile || !$sellerProfile->getStripeAccountId()) {
                $orderItem->setTransferStatus('failed');

                continue;
            }

            $sellerId = $seller->getId();

            if (!isset($sellerTransfers[$sellerId])) {
                $sellerTransfers[$sellerId] = [
                    'seller' => $seller,
                    'stripeAccountId' => $sellerProfile->getStripeAccountId(),
                    'amount' => 0,
                    'items' => [],
                ];
            }

            $sellerTransfers[$sellerId]['amount'] += (float) $orderItem->getSellerAmount();
            $sellerTransfers[$sellerId]['items'][] = $orderItem;
        }

        foreach ($sellerTransfers as $sellerTransfer) {
            $amountInCents = (int) round($sellerTransfer['amount'] * 100);

            if ($amountInCents <= 0) {
                continue;
            }

            try {
                $transfer = $this->stripe->transfers->create([
                    'amount' => $amountInCents,
                    'currency' => 'eur',
                    'destination' => $sellerTransfer['stripeAccountId'],
                    'description' => 'Reversement vendeur InnovShop - Commande #' . $order->getId(),
                    'metadata' => [
                        'order_id' => (string) $order->getId(),
                        'seller_id' => (string) $sellerTransfer['seller']->getId(),
                    ],
                ]);

                foreach ($sellerTransfer['items'] as $orderItem) {
                    /** @var OrderItem $orderItem */
                    $orderItem->setStripeTransferId($transfer->id);
                    $orderItem->setTransferStatus('transferred');
                }
           } catch (\Throwable $exception) {
            foreach ($sellerTransfer['items'] as $orderItem) {
                /** @var OrderItem $orderItem */
                $orderItem->setTransferStatus('failed');
            }
        }
        }

        $this->entityManager->flush();
    }
}