<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductCrudController extends AbstractCrudController
{
    /**
     * Indique à EasyAdmin quelle entité ce CRUD doit gérer.
     *
     * Fonctionnalité InnovShop :
     * Back Office Admin - Vue globale des produits.
     *
     * Ce CRUD sert à afficher tous les produits de la marketplace :
     * - produits InnovShop ;
     * - produits vendeurs.
     *
     * Il sert de vue d’ensemble et ne doit pas être utilisé pour modifier
     * directement les produits afin d’éviter de casser la séparation admin/seller.
     */
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    /**
     * Configure le titre de la vue globale des produits.
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Tous les produits')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    /**
     * Configure les champs affichés dans la liste globale.
     *
     * Cette vue permet à l’admin de voir rapidement si un produit appartient :
     * - à InnovShop si le seller est vide ;
     * - à un vendeur si le seller est renseigné.
     */
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('imagePreview', 'Image')
            ->formatValue(function ($value, $entity) {
                $image = $entity->getImage()
                    ? '/images/products/' . $entity->getImage()
                    : '/images/default-product.png';

                return sprintf('<img src="%s" style="max-height: 80px;">', $image);
            })
            ->renderAsHtml()
            ->onlyOnIndex();

        yield TextField::new('nom', 'Nom');

        yield MoneyField::new('prix', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents(false);

        yield IntegerField::new('stock', 'Stock');

        yield AssociationField::new('seller', 'Vendeur')
            ->formatValue(function ($value, $entity) {
                return $entity->getSeller() ? (string) $entity->getSeller() : 'InnovShop';
            });

        yield AssociationField::new('category', 'Catégorie');

        yield BooleanField::new('aLaUne', 'À la une')
            ->renderAsSwitch(false);

        yield BooleanField::new('isActive', 'Produit actif')
            ->renderAsSwitch(false);

        yield DateTimeField::new('createdAt', 'Créé le');
    }

    /**
     * Désactive les actions de modification dans la vue globale.
     *
     * Les modifications doivent se faire depuis :
     * - Produits InnovShop pour les produits admin ;
     * - Produits vendeurs pour les produits marketplace.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }
}