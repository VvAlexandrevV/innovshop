<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CategoryCrudController extends AbstractCrudController
{
    /**
     * Indique à EasyAdmin quelle entité ce CRUD doit gérer.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Gestion des catégories.
     *
     * Cette méthode permet à l’administrateur de gérer les catégories
     * utilisées pour classer les produits du catalogue.
     */
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    /**
     * Configure les champs affichés dans le formulaire et la liste EasyAdmin.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Création / modification des catégories.
     *
     * Ici, l’administrateur peut renseigner ou modifier le nom d’une catégorie.
     */
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom', 'Nom de la catégorie');
    }

    /**
     * Configure les actions disponibles dans le CRUD des catégories.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Gestion des catégories.
     *
     * Actuellement, toutes les actions EasyAdmin restent disponibles.
     * La suppression pourrait être désactivée en décommentant disable(Action::DELETE).
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions;//->disable(Action::DELETE);
    }
}