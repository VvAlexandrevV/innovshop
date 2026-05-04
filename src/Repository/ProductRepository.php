<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findBySearchAndCategory(?string $search, ?int $categoryId, string $tri = 'newest', ?float $minPrice = null, ?float $maxPrice = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.variants', 'v')
            ->addSelect('c')
            ->addSelect('v')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true);

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

    public function findActiveForCatalog(): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.variants', 'v')
            ->addSelect('c')
            ->addSelect('v')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.createdAt', 'DESC');
    }
}