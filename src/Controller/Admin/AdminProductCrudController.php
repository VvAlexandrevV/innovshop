<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AdminProductCrudController extends ProductCrudController
{
    /**
     * Ce CRUD gère uniquement les produits InnovShop.
     *
     * Fonctionnalité InnovShop :
     * Back Office Admin - Gestion des produits de la boutique.
     *
     * Un produit InnovShop est un produit sans vendeur associé :
     * Product::seller === null.
     */
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    /**
     * Configure les titres du CRUD des produits InnovShop.
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Produits InnovShop')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un produit InnovShop')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier un produit InnovShop')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    /**
     * Configure les champs des produits InnovShop.
     *
     * Ici, contrairement à la vue "Tous les produits", l’admin peut agir
     * directement depuis la liste sur :
     * - À la une ;
     * - Produit actif.
     */
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom', 'Nom');

        yield MoneyField::new('prix', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents(false);

        yield IntegerField::new('stock', 'Stock')
            ->setHelp('Nombre d’unités disponibles à la vente.');

        yield TextareaField::new('description', 'Description');
        yield TextareaField::new('specification', 'Spécification');

        yield ImageField::new('image', 'Image')
            ->setUploadDir('public/images/products')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(false)
            ->onlyOnForms();

        yield TextField::new('imagePreview', 'Image')
            ->formatValue(function ($value, $entity) {
                $image = $entity->getImage()
                    ? '/images/products/' . $entity->getImage()
                    : '/images/default-product.png';

                return sprintf('<img src="%s" style="max-height: 80px;">', $image);
            })
            ->renderAsHtml()
            ->onlyOnIndex();

        yield BooleanField::new('aLaUne', 'À la une')
            ->renderAsSwitch(true);

        yield BooleanField::new('isActive', 'Produit actif')
            ->renderAsSwitch(true)
            ->setHelp('Désactivez ce champ pour retirer le produit du catalogue sans supprimer son historique.');

        yield AssociationField::new('category', 'Catégorie');

        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();
    }

    /**
     * Filtre la liste pour afficher uniquement les produits admin.
     *
     * Les produits vendeurs sont exclus de cette section.
     */
    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->andWhere('entity.seller IS NULL');
    }

    /**
     * Lorsqu’un admin crée un produit ici, on force seller à null.
     *
     * Cela garantit que le produit appartient bien à InnovShop.
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product) {
            return;
        }

        $entityInstance->setSeller(null);

        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Sécurise la modification :
     * ce CRUD ne doit modifier que les produits InnovShop.
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product) {
            return;
        }

        if ($entityInstance->getSeller() !== null) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier un produit vendeur depuis cette section.');
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * Configure les actions disponibles sur les produits InnovShop.
     *
     * L’admin peut créer et modifier ses produits.
     * La suppression reste désactivée pour préserver l’historique.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::DELETE);
    }
}