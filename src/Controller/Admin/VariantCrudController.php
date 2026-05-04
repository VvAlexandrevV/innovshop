<?php

namespace App\Controller\Admin;

use App\Entity\Variant;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class VariantCrudController extends AbstractCrudController
{
    /**
     * Indique à EasyAdmin quelle entité ce CRUD doit gérer.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Gestion des variantes produit.
     *
     * Cette méthode relie ce contrôleur EasyAdmin à l’entité Variant.
     */
    public static function getEntityFqcn(): string
    {
        return Variant::class;
    }

    /**
     * Configure les champs utilisés pour gérer les variantes.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Gestion des options disponibles.
     *
     * Cette méthode permet à l’administrateur de créer des variantes
     * liées à un produit : type de variante, valeur, supplément de prix,
     * stock spécifique et produit associé.
     *
     * Exemple :
     * type = couleur, valeur = noir
     * type = taille, valeur = XL
     */
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('type', 'Type de variante');

        yield TextField::new('value', 'Valeur');

        yield MoneyField::new('priceModifier', 'Supplément')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setRequired(false);

        yield IntegerField::new('stock', 'Stock')
            ->setHelp('Stock disponible pour cette variante.');

        yield AssociationField::new('product', 'Produit associé')
            ->autocomplete();
    }
}