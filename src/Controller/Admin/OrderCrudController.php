<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OrderCrudController extends AbstractCrudController
{
    /**
     * Indique à EasyAdmin quelle entité ce CRUD doit gérer.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Gestion des commandes.
     *
     * Cette méthode relie ce contrôleur EasyAdmin à l’entité Order.
     */
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    /**
     * Configure les champs visibles dans l’administration des commandes.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Visualisation et suivi des commandes.
     *
     * L’administrateur peut consulter les informations principales
     * d’une commande : client, date, total, adresse de livraison et produits.
     *
     * Le seul champ réellement modifiable ici est le statut de commande.
     * Cela correspond à la fonctionnalité permettant de marquer une commande
     * comme payée, expédiée, livrée ou annulée.
     */
   public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield AssociationField::new('user', 'Client')
            ->setDisabled();

        yield DateTimeField::new('createdAt', 'Date de commande')
            ->setDisabled();

        yield MoneyField::new('total', 'Total')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setDisabled();

        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'En attente de paiement' => 'pending_payment',
                'Payée' => 'paid',
                'Expédiée' => 'shipped',
                'Livrée' => 'delivered',
                'Annulée' => 'cancelled',
            ]);

        yield TextField::new('deliveryFirstName', 'Prénom livraison')
            ->setDisabled();

        yield TextField::new('deliveryLastName', 'Nom livraison')
            ->setDisabled();

        yield TextField::new('deliveryAddress', 'Adresse')
            ->setDisabled();

        yield TextField::new('deliveryPostalCode', 'Code postal')
            ->setDisabled();

        yield TextField::new('deliveryCity', 'Ville')
            ->setDisabled();

        yield TextField::new('deliveryCountry', 'Pays')
            ->setDisabled();

        yield AssociationField::new('orderItems', 'Produits')
            ->onlyOnDetail();
    }

    /**
     * Configure les actions disponibles sur les commandes.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Gestion sécurisée des commandes.
     *
     * La création manuelle et la suppression des commandes sont désactivées.
     * C’est logique : une commande doit être créée depuis le parcours client,
     * et son historique ne doit pas disparaître comme un ninja dans la brume.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE);
    }

    /**
     * Configure l’affichage général du CRUD des commandes.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Tableau de suivi des commandes.
     *
     * Cette méthode définit les libellés affichés dans EasyAdmin
     * et trie les commandes de la plus récente à la plus ancienne.
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }
}