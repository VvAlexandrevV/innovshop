<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\Variant;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class VariantCrudController extends AbstractCrudController
{
    /**
     * Indique à EasyAdmin quelle entité ce CRUD doit gérer.
     *
     * Fonctionnalité InnovShop :
     * Back Office Admin - Gestion des variantes InnovShop.
     *
     * Ce CRUD admin ne doit gérer que les variantes des produits InnovShop,
     * c’est-à-dire les produits sans vendeur associé.
     */
    public static function getEntityFqcn(): string
    {
        return Variant::class;
    }

    /**
     * Configure les titres du CRUD.
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Variantes InnovShop')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer une variante InnovShop')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier une variante InnovShop');
    }

    /**
     * Filtre la liste des variantes côté admin.
     *
     * Règle marketplace :
     * l’admin ne voit ici que les variantes liées aux produits InnovShop.
     *
     * Les variantes des produits vendeurs restent visibles uniquement :
     * - dans l’espace seller du vendeur concerné ;
     * - en lecture dans l’onglet admin "Produits vendeurs".
     */
    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->join('entity.product', 'product')
            ->andWhere('product.seller IS NULL');
    }

    /**
     * Configure les champs utilisés pour gérer les variantes côté admin.
     *
     * Fonctionnalité InnovShop :
     * Back Office Admin - Gestion des options disponibles.
     *
     * L’administrateur peut créer et modifier des variantes uniquement
     * sur les produits InnovShop.
     */
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('type', 'Type de variante')
            ->setHelp('Exemple : Couleur, Taille, Mémoire, Matière...');

        yield TextField::new('value', 'Valeur')
            ->setHelp('Exemple : Noir, XL, 256 Go...');

        yield MoneyField::new('priceModifier', 'Supplément')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setRequired(false)
            ->setHelp('Laissez vide ou mettez 0 si cette variante ne modifie pas le prix.');

        yield IntegerField::new('stock', 'Stock')
            ->setHelp('Stock disponible pour cette variante.');

        yield BooleanField::new('isActive', 'Variante active')
            ->setHelp('Permet de masquer une variante sans la supprimer.');

        yield AssociationField::new('product', 'Produit InnovShop associé')
            ->setRequired(true)
            ->setQueryBuilder(function (QueryBuilder $queryBuilder) {
                return $queryBuilder
                    ->andWhere('entity.seller IS NULL')
                    ->orderBy('entity.nom', 'ASC');
            })
            ->setHelp('L’admin peut créer des variantes uniquement sur les produits InnovShop.');
    }

    /**
     * Sécurise la création d’une variante côté admin.
     *
     * Même si quelqu’un tente de contourner le formulaire,
     * on bloque la création si le produit appartient à un vendeur.
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Variant) {
            return;
        }

        $this->denyVariantOnSellerProduct($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Sécurise la modification d’une variante côté admin.
     *
     * L’admin ne peut pas rattacher une variante à un produit vendeur.
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Variant) {
            return;
        }

        $this->denyVariantOnSellerProduct($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * Sécurise la suppression côté admin.
     *
     * Même si l’action de suppression est disponible plus tard,
     * l’admin ne pourra pas supprimer une variante vendeur depuis ce CRUD.
     */
    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Variant) {
            return;
        }

        $this->denyVariantOnSellerProduct($entityInstance);

        parent::deleteEntity($entityManager, $entityInstance);
    }

    /**
     * Configure les actions disponibles.
     *
     * On désactive la suppression directe pour éviter de casser
     * des paniers, commandes ou historiques.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::DELETE);
    }

    /**
     * Vérifie que la variante est bien rattachée à un produit InnovShop.
     *
     * Un produit InnovShop est un produit sans vendeur :
     * Product::seller === null.
     */
    private function denyVariantOnSellerProduct(Variant $variant): void
    {
        $product = $variant->getProduct();

        if (!$product instanceof Product) {
            throw $this->createAccessDeniedException('Une variante doit être rattachée à un produit.');
        }

        if ($product->getSeller() !== null) {
            throw $this->createAccessDeniedException('L’admin ne peut pas gérer les variantes des produits vendeurs depuis cette section.');
        }
    }
}