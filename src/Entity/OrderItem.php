<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Fichier : OrderItem.php
 * Représente une ligne de commande.
 *
 * Une commande peut contenir plusieurs OrderItem.
 * Chaque OrderItem garde une copie des informations importantes du produit
 * au moment de l'achat :
 * - nom du produit
 * - prix payé
 * - options choisies
 * - vendeur concerné
 * - commission plateforme
 * - montant net vendeur
 * - montant conservé par InnovShop
 *
 * Dans la logique marketplace, cette entité sert aussi à suivre
 * le transfert Stripe Connect envoyé au vendeur après paiement.
 */
#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    /**
     * Identifiant unique de la ligne de commande.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Commande à laquelle appartient cette ligne.
     */
    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $orderEntity = null;

    /**
     * Produit acheté.
     *
     * Le produit reste lié pour permettre un suivi interne,
     * même si les informations importantes sont aussi copiées
     * dans productName et productPrice.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    /**
     * Nom du produit au moment de l'achat.
     *
     * On stocke le nom en dur pour garder un historique fiable,
     * même si le produit est renommé plus tard.
     */
    #[ORM\Column(length: 255)]
    private ?string $productName = null;

    /**
     * Prix réellement payé pour cette ligne.
     *
     * Ce montant inclut le prix de base du produit
     * et les éventuels modificateurs de prix des variantes.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $productPrice = null;

    /**
     * Résumé des variantes choisies.
     *
     * Exemple :
     * "Couleur - Rouge, Taille - XL (+5 €)".
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $variantLabel = null;

    /**
     * Vendeur propriétaire du produit.
     *
     * Si ce champ est null, cela signifie que le produit appartient
     * directement à InnovShop / admin.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $seller = null;

    /**
     * Commission conservée par InnovShop sur cette ligne.
     *
     * Exemple :
     * produit vendeur à 100 € avec 10 % de commission = 10 €.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $commissionAmount = '0.00';

    /**
     * Montant net dû au vendeur.
     *
     * Exemple :
     * produit vendeur à 100 € avec 10 % de commission = 90 €.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $sellerAmount = '0.00';

    /**
     * Montant conservé par la plateforme InnovShop.
     *
     * Pour un produit vendeur, cela correspond à la commission.
     * Pour un produit admin, cela correspond au prix complet du produit.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $platformAmount = '0.00';

    /**
     * Identifiant du transfert Stripe Connect.
     *
     * Quand InnovShop reverse l'argent au vendeur,
     * Stripe renvoie un identifiant du type "tr_...".
     * On le stocke ici pour garder une trace du paiement vendeur.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeTransferId = null;

    /**
     * Statut du transfert Stripe vers le vendeur.
     *
     * Valeurs possibles dans notre logique :
     * - null : aucun transfert nécessaire ou pas encore lancé
     * - pending : transfert à faire
     * - transferred : transfert effectué
     * - failed : erreur pendant le transfert
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $transferStatus = null;

    /**
     * Retourne l'identifiant unique de la ligne de commande.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne la commande liée à cette ligne.
     */
    public function getOrderEntity(): ?Order
    {
        return $this->orderEntity;
    }

    /**
     * Associe cette ligne à une commande.
     */
    public function setOrderEntity(?Order $orderEntity): static
    {
        $this->orderEntity = $orderEntity;

        return $this;
    }

    /**
     * Retourne le produit acheté.
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * Associe un produit à cette ligne de commande.
     */
    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Retourne le nom du produit au moment de l'achat.
     */
    public function getProductName(): ?string
    {
        return $this->productName;
    }

    /**
     * Définit le nom du produit au moment de l'achat.
     */
    public function setProductName(string $productName): static
    {
        $this->productName = $productName;

        return $this;
    }

    /**
     * Retourne le prix payé pour cette ligne.
     */
    public function getProductPrice(): ?string
    {
        return $this->productPrice;
    }

    /**
     * Définit le prix payé pour cette ligne.
     */
    public function setProductPrice(string $productPrice): static
    {
        $this->productPrice = $productPrice;

        return $this;
    }

    /**
     * Retourne le résumé des variantes choisies.
     */
    public function getVariantLabel(): ?string
    {
        return $this->variantLabel;
    }

    /**
     * Définit le résumé des variantes choisies.
     */
    public function setVariantLabel(?string $variantLabel): static
    {
        $this->variantLabel = $variantLabel;

        return $this;
    }

    /**
     * Retourne le vendeur concerné par cette ligne.
     */
    public function getSeller(): ?User
    {
        return $this->seller;
    }

    /**
     * Associe un vendeur à cette ligne.
     *
     * Si le produit appartient à InnovShop directement,
     * ce champ peut rester null.
     */
    public function setSeller(?User $seller): static
    {
        $this->seller = $seller;

        return $this;
    }

    /**
     * Retourne le montant de commission InnovShop.
     */
    public function getCommissionAmount(): ?string
    {
        return $this->commissionAmount;
    }

    /**
     * Définit le montant de commission InnovShop.
     */
    public function setCommissionAmount(string $commissionAmount): static
    {
        $this->commissionAmount = $commissionAmount;

        return $this;
    }

    /**
     * Retourne le montant net dû au vendeur.
     */
    public function getSellerAmount(): ?string
    {
        return $this->sellerAmount;
    }

    /**
     * Définit le montant net dû au vendeur.
     */
    public function setSellerAmount(string $sellerAmount): static
    {
        $this->sellerAmount = $sellerAmount;

        return $this;
    }

    /**
     * Retourne le montant conservé par InnovShop.
     */
    public function getPlatformAmount(): ?string
    {
        return $this->platformAmount;
    }

    /**
     * Définit le montant conservé par InnovShop.
     */
    public function setPlatformAmount(string $platformAmount): static
    {
        $this->platformAmount = $platformAmount;

        return $this;
    }

    /**
     * Retourne l'identifiant du transfert Stripe Connect.
     */
    public function getStripeTransferId(): ?string
    {
        return $this->stripeTransferId;
    }

    /**
     * Stocke l'identifiant du transfert Stripe Connect.
     */
    public function setStripeTransferId(?string $stripeTransferId): static
    {
        $this->stripeTransferId = $stripeTransferId;

        return $this;
    }

    /**
     * Retourne le statut du transfert Stripe vendeur.
     */
    public function getTransferStatus(): ?string
    {
        return $this->transferStatus;
    }

    /**
     * Définit le statut du transfert Stripe vendeur.
     */
    public function setTransferStatus(?string $transferStatus): static
    {
        $this->transferStatus = $transferStatus;

        return $this;
    }
}