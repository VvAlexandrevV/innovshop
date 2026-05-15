<?php

namespace App\Controller\Seller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Fichier : SellerStripeConnectController.php
 *
 * Rôle dans InnovShop :
 * Gère la connexion Stripe Connect des vendeurs.
 *
 * Ce contrôleur permet à un vendeur de connecter son compte Stripe
 * afin de pouvoir recevoir automatiquement ses reversements après une vente.
 *
 * Il ne demande jamais directement les coordonnées bancaires au vendeur.
 * Ces informations sont saisies sur l'interface sécurisée de Stripe.
 *
 * InnovShop stocke seulement l'identifiant du compte Stripe Connect :
 * exemple : acct_123456789
 */
#[Route('/seller/stripe')]
#[IsGranted('ROLE_SELLER')]
final class SellerStripeConnectController extends AbstractController
{
    /**
     * Lance ou relance l'onboarding Stripe Connect du vendeur.
     *
     * Fonctionnalité InnovShop :
     * Marketplace - Connexion du compte Stripe vendeur.
     *
     * Cette méthode :
     * - récupère le vendeur connecté
     * - vérifie qu'il possède un profil vendeur
     * - crée un compte Stripe Connect si nécessaire
     * - enregistre le stripeAccountId dans SellerProfile
     * - génère un lien d'onboarding Stripe
     * - redirige le vendeur vers Stripe
     */
    #[Route('/connect', name: 'seller_stripe_connect')]
    public function connect(
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $seller */
        $seller = $this->getUser();

        $sellerProfile = $seller->getSellerProfile();

        if (!$sellerProfile) {
            $this->addFlash('error', 'Vous devez avoir un profil vendeur avant de connecter Stripe.');
            return $this->redirectToRoute('seller');
        }

        $stripe = new StripeClient($_ENV['STRIPE_SECRET_KEY']);

        if ($sellerProfile->getStripeAccountId()) {
            $stripe->accounts->update(
                $sellerProfile->getStripeAccountId(),
                [
                    'capabilities' => [
                        'transfers' => [
                            'requested' => true,
                        ],
                    ],
                ]
            );
        }

        if (!$sellerProfile->getStripeAccountId()) {
            $account = $stripe->accounts->create([
                'type' => 'express',
                'country' => 'FR',
                'email' => $sellerProfile->getCompanyEmail(),
                'business_type' => 'company',
                'capabilities' => [
                    'transfers' => [
                        'requested' => true,
                    ],
                ],
                'business_profile' => [
                    'name' => $sellerProfile->getCompanyName(),
                ],
                'metadata' => [
                    'seller_id' => (string) $seller->getId(),
                    'seller_profile_id' => (string) $sellerProfile->getId(),
                    'platform' => 'InnovShop',
                ],
            ]);

            $sellerProfile->setStripeAccountId($account->id);

            $entityManager->flush();
        }

        $accountLink = $stripe->accountLinks->create([
            'account' => $sellerProfile->getStripeAccountId(),
            'refresh_url' => $this->generateUrl(
                'seller_stripe_connect',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'return_url' => $this->generateUrl(
                'seller_stripe_return',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'type' => 'account_onboarding',
        ]);

        return $this->redirect($accountLink->url);
    }

    /**
     * Gère le retour du vendeur après l'onboarding Stripe.
     *
     * Fonctionnalité InnovShop :
     * Marketplace - Retour après connexion Stripe.
     *
     * Stripe redirige ici le vendeur après son passage
     * dans le formulaire d'onboarding.
     *
     * Important :
     * Être redirigé ici ne garantit pas forcément que le vendeur
     * a terminé toutes les étapes Stripe.
     * Plus tard, on pourra vérifier charges_enabled et payouts_enabled.
     */
    #[Route('/return', name: 'seller_stripe_return')]
    public function return(): Response
    {
        $this->addFlash('success', 'Votre compte Stripe a été connecté ou mis à jour.');

        return $this->redirectToRoute('seller');
    }
}