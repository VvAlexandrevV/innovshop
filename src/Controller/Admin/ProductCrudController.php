<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class ProductCrudController extends AbstractCrudController
{
    /**
     * Indique à EasyAdmin quelle entité ce CRUD doit gérer.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Gestion des produits.
     *
     * Cette méthode relie ce contrôleur EasyAdmin à l’entité Product.
     */
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    /**
     * Configure les champs utilisés pour créer, modifier et afficher un produit.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Création / modification des produits.
     *
     * Cette méthode permet à l’administrateur de gérer les informations
     * principales d’un produit : nom, prix, stock, description, spécifications,
     * image, catégorie, mise en avant et visibilité dans le catalogue.
     *
     * Le champ "aLaUne" sert à afficher un produit dans la section
     * "produits à la une" de la page d’accueil.
     *
     * Le champ "isActive" permet de retirer un produit du catalogue
     * sans le supprimer définitivement, ce qui protège l’historique des commandes.
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

        yield BooleanField::new('aLaUne', 'À la une');

        yield BooleanField::new('isActive', 'Produit actif')
            ->setHelp('Désactivez ce champ pour retirer le produit du catalogue sans supprimer son historique.');

        yield AssociationField::new('category', 'Catégorie');

        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();
    }

    /**
     * Configure les actions disponibles sur les produits.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Gestion sécurisée des produits.
     *
     * La suppression est désactivée pour éviter de supprimer un produit
     * déjà lié à des commandes. À la place, le champ "isActive"
     * permet de masquer un produit sans casser l’historique.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::DELETE);
    }
}