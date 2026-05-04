<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;

class UserCrudController extends AbstractCrudController
{
    /**
     * Indique à EasyAdmin quelle entité ce CRUD doit gérer.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Gestion des clients.
     *
     * Cette méthode relie ce contrôleur EasyAdmin à l’entité User.
     */
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    /**
     * Configure les champs affichés dans l’administration des utilisateurs.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Visualisation des comptes clients.
     *
     * L’administrateur peut consulter l’email, les rôles,
     * le nombre de commandes, le total dépensé et l’historique des commandes.
     *
     * Ce CRUD sert principalement à consulter les clients,
     * pas à modifier leurs données.
     */
    public function configureFields(string $pageName): iterable
    {
        yield EmailField::new('email', 'Email');

        yield ArrayField::new('roles', 'Rôles')
            ->hideOnForm();

        yield IntegerField::new('ordersCount', 'Nb commandes')
            ->onlyOnIndex();

        yield MoneyField::new('totalSpent', 'Total dépensé')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->hideOnForm();

        yield AssociationField::new('orders', 'Historique des commandes')
            ->onlyOnDetail();
    }

    /**
     * Configure les actions disponibles sur les utilisateurs.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Consultation des comptes clients.
     *
     * La création, la suppression et la modification sont désactivées.
     * L’admin peut donc consulter les comptes sans risquer de modifier
     * accidentellement les données utilisateur.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::DELETE, Action::EDIT);
    }

    /**
     * Configure l’affichage général du CRUD utilisateurs.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Liste des clients.
     *
     * Cette méthode définit les libellés affichés dans EasyAdmin
     * et trie les utilisateurs par email dans l’ordre alphabétique.
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setDefaultSort(['email' => 'ASC']);
    }
}