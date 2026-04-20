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

class ProductCrudController extends AbstractCrudController
{   
    // Cette méthode sert à indiquer à EasyAdmin quelle entité est gérée par ce CRUD.
    // Liée à l'entité Product.
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    // Cette méthode sert à définir les champs affichés et éditables dans le back-office (formulaire + liste).
    // Liée à l'entité Product et utilisée par EasyAdmin pour générer les pages (create, edit, index).
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom', 'Nom');

        yield MoneyField::new('prix', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents(false);

        yield TextareaField::new('description', 'Description');
        yield TextareaField::new('specification', 'Spécification');

        yield ImageField::new('image', 'Image')
            ->setBasePath('images/products')
            ->setUploadDir('public/images/products')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(false);

        yield BooleanField::new('aLaUne', 'À la une');
        yield AssociationField::new('category', 'Catégorie');
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }

    // Cette méthode sert à configurer les actions disponibles dans le back-office (comme supprimer, éditer, etc.).
    // Liée à EasyAdmin et à l'interface admin (Dashboard / CRUD).
    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::DELETE);
    }
}