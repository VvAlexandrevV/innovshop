<?php

namespace App\Controller\Seller;

use App\Entity\Product;
use App\Entity\Variant;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SellerVariantCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Variant::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Mes variantes')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter une variante')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier une variante');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('type', 'Type')
            ->setHelp('Exemple : Couleur, Taille, Mémoire, Matière...');

        yield TextField::new('value', 'Valeur')
            ->setHelp('Exemple : Noir, XL, 256 Go...');

        yield MoneyField::new('priceModifier', 'Supplément de prix')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setRequired(false)
            ->setHelp('Laissez vide ou mettez 0 si la variante ne change pas le prix.');

        yield IntegerField::new('stock', 'Stock')
            ->setHelp('Stock disponible pour cette variante.');

        yield BooleanField::new('isActive', 'Variante active')
            ->setHelp('Désactivez la variante pour la masquer du catalogue sans la supprimer.');

        yield AssociationField::new('product', 'Produit lié')
            ->setQueryBuilder(function (QueryBuilder $queryBuilder) {
                return $queryBuilder
                    ->andWhere('entity.seller = :seller')
                    ->setParameter('seller', $this->getUser())
                    ->andWhere('entity.isActive = :active')
                    ->setParameter('active', true)
                    ->andWhere('entity.isBlockedByAdmin = :blocked')
                    ->setParameter('blocked', false)
                    ->orderBy('entity.nom', 'ASC');
            })
            ->setHelp('Le vendeur ne peut choisir que ses propres produits actifs et non bloqués.');
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->join('entity.product', 'product')
            ->andWhere('product.seller = :seller')
            ->setParameter('seller', $this->getUser());
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Variant) {
            return;
        }

        $product = $entityInstance->getProduct();

        $this->denyAccessIfProductIsInvalidForSeller($product);

        $entityInstance->setIsActive(true);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Variant) {
            return;
        }

        $product = $entityInstance->getProduct();

        $this->denyAccessIfProductIsInvalidForSeller($product);

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Variant) {
            return;
        }

        $product = $entityInstance->getProduct();

        $this->denyAccessIfProductIsInvalidForSeller($product);

        parent::deleteEntity($entityManager, $entityInstance);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::DELETE);
    }

    private function denyAccessIfProductIsInvalidForSeller(?Product $product): void
    {
        if (!$product instanceof Product || $product->getSeller() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez gérer des variantes que pour vos propres produits.');
        }

        if ($product->isBlockedByAdmin()) {
            $reason = $product->getAdminBlockReason();

            $message = 'Ce produit est actuellement en cours d’enquête par l’administration InnovShop. Vous ne pouvez pas modifier ses variantes tant que le blocage n’a pas été levé.';

            if ($reason) {
                $message .= ' Motif : ' . $reason;
            }

            throw $this->createAccessDeniedException($message);
        }
    }
}