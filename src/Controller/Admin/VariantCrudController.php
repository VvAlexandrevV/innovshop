<?php

namespace App\Controller\Admin;

use App\Entity\Variant;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class VariantCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Variant::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('type', 'Type de variante');
        yield TextField::new('value', 'Valeur');

        yield MoneyField::new('priceModifier', 'Supplément')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setRequired(false);

        yield AssociationField::new('products', 'Produits associés')
            ->setFormTypeOptions([
                'by_reference' => false,
            ])
            ->autocomplete();
    }
}