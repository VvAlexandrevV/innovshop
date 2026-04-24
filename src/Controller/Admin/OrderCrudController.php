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
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

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

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }
}