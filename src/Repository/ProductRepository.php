<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Fichier : ProductRepository.php
 *
 * Rôle dans InnovShop :
 * Centralise les requêtes liées aux produits.
 *
 * Ce repository sert notamment à récupérer :
 * - les produits visibles dans le catalogue
 * - les produits visibles sur la page d'accueil
 * - les produits d'un vendeur
 * - les produits administrateur / InnovShop
 *
 * Règle marketplace importante :
 * Un produit vendeur ne doit apparaître sur le front que si son vendeur
 * possède un compte Stripe Connect configuré.
 *
 * Un produit InnovShop, donc sans vendeur, reste visible normalement.
 *
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    /**
     * Initialise le repository Doctrine pour l'entité Product.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Recherche les produits visibles dans le catalogue public.
     *
     * Fonctionnalité InnovShop :
     * Front Office - Catalogue produits.
     *
     * Cette méthode permet de filtrer les produits par :
     * - recherche texte
     * - catégorie
     * - tri
     * - prix minimum
     * - prix maximum
     *
     * Elle applique aussi la règle marketplace :
     * les produits vendeurs sans compte Stripe Connect ne sont pas visibles.
     */
    public function findBySearchAndCategory(
        ?string $search,
        ?int $categoryId,
        string $tri = 'newest',
        ?float $minPrice = null,
        ?float $maxPrice = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.variants', 'v')
            ->addSelect('c')
            ->addSelect('v')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.isBlockedByAdmin = :blocked')
            ->setParameter('active', true)
            ->setParameter('blocked', false);

        $this->applyPublicMarketplaceVisibility($qb);

        if ($search) {
            $qb->andWhere(
                'p.nom LIKE :search
                OR p.description LIKE :search
                OR v.type LIKE :search
                OR v.value LIKE :search'
            )
            ->setParameter('search', '%' . $search . '%');
        }

        if ($categoryId) {
            $qb->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        if ($minPrice !== null) {
            $qb->andWhere('p.prix >= :minPrice')
                ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('p.prix <= :maxPrice')
                ->setParameter('maxPrice', $maxPrice);
        }

        match ($tri) {
            'price_asc' => $qb->orderBy('p.prix', 'ASC'),
            'price_desc' => $qb->orderBy('p.prix', 'DESC'),
            default => $qb->orderBy('p.createdAt', 'DESC'),
        };

        return $qb;
    }

    /**
     * Récupère tous les produits appartenant à un vendeur.
     *
     * Fonctionnalité InnovShop :
     * Espace vendeur - Gestion des produits.
     *
     * Cette méthode ne filtre pas selon Stripe Connect.
     * Le vendeur doit pouvoir voir ses produits dans son dashboard,
     * même si son compte Stripe n'est pas encore connecté.
     */
    public function findBySeller(User $seller): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.variants', 'v')
            ->addSelect('c')
            ->addSelect('v')
            ->andWhere('p.seller = :seller')
            ->setParameter('seller', $seller)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les produits créés directement par InnovShop / admin.
     *
     * Fonctionnalité InnovShop :
     * Back Office admin - Gestion des produits InnovShop.
     *
     * Un produit admin est un produit dont le champ seller est null.
     */
    public function findAdminProducts(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.variants', 'v')
            ->addSelect('c')
            ->addSelect('v')
            ->andWhere('p.seller IS NULL')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Prépare une requête des produits actifs visibles dans le catalogue public.
     *
     * Fonctionnalité InnovShop :
     * Front Office - Catalogue produits.
     *
     * Cette méthode est utile pour récupérer les produits actifs
     * sans recherche spécifique.
     *
     * Elle applique aussi la règle marketplace :
     * les produits vendeurs sans compte Stripe Connect ne sont pas visibles.
     */
    public function findActiveForCatalog(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.variants', 'v')
            ->addSelect('c')
            ->addSelect('v')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.isBlockedByAdmin = :blocked')
            ->setParameter('active', true)
            ->setParameter('blocked', false)
            ->orderBy('p.createdAt', 'DESC');

        $this->applyPublicMarketplaceVisibility($qb);

        return $qb;
    }

    /**
     * Récupère les produits à la une visibles sur la page d'accueil.
     *
     * Fonctionnalité InnovShop :
     * Front Office - Accueil.
     *
     * Cette méthode récupère uniquement :
     * - les produits actifs
     * - les produits non bloqués par l'admin
     * - les produits marqués à la une
     * - les produits InnovShop
     * - les produits vendeurs dont le vendeur possède Stripe Connect
     *
     * Important :
     * On ne joint pas les variantes ici.
     * Avec setMaxResults(), une jointure sur les variantes peut faire compter
     * plusieurs lignes SQL pour un seul produit et réduire le nombre réel
     * de produits affichés.
     */
    public function findFeaturedForHome(int $limit = 3): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->andWhere('p.aLaUne = :featured')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.isBlockedByAdmin = :blocked')
            ->setParameter('featured', true)
            ->setParameter('active', true)
            ->setParameter('blocked', false)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit);

        $this->applyPublicMarketplaceVisibility($qb);

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les derniers produits visibles sur la page d'accueil.
     *
     * Fonctionnalité InnovShop :
     * Front Office - Accueil.
     *
     * Cette méthode récupère uniquement :
     * - les produits actifs
     * - les produits non bloqués par l'admin
     * - les produits InnovShop
     * - les produits vendeurs dont le vendeur possède Stripe Connect
     *
     * Important :
     * On ne joint pas les variantes ici pour éviter que setMaxResults()
     * limite des lignes SQL de variantes au lieu de limiter de vrais produits.
     */
    public function findLatestForHome(int $limit = 3): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.isBlockedByAdmin = :blocked')
            ->setParameter('active', true)
            ->setParameter('blocked', false)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit);

        $this->applyPublicMarketplaceVisibility($qb);

        return $qb->getQuery()->getResult();
    }

    /**
     * Applique la règle de visibilité marketplace sur une requête front.
     *
     * Fonctionnalité InnovShop :
     * Marketplace - Sécurité affichage front.
     *
     * Règle :
     * - produit InnovShop/admin : visible si seller est null
     * - produit vendeur : visible seulement si le vendeur possède un SellerProfile
     *   avec un stripeAccountId renseigné
     *
     * Cette méthode ne doit pas être utilisée dans l'espace vendeur,
     * car le vendeur doit pouvoir voir ses propres produits même sans Stripe.
     */
    private function applyPublicMarketplaceVisibility(QueryBuilder $qb): void
    {
        $qb
            ->leftJoin('p.seller', 'seller')
            ->leftJoin('seller.sellerProfile', 'sellerProfile')
            ->addSelect('seller')
            ->addSelect('sellerProfile')
            ->andWhere('p.seller IS NULL OR sellerProfile.stripeAccountId IS NOT NULL');
    }
}