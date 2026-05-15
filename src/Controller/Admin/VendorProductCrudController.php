<?php

namespace App\Controller\Admin;

use App\Entity\Product;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class VendorProductCrudController extends AbstractCrudController
{
    /**
     * Ce CRUD admin gère les produits appartenant aux vendeurs.
     *
     * Fonctionnalité InnovShop :
     * Back Office Admin - Modération des produits vendeurs.
     */
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Produits vendeurs')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modération du produit vendeur')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->andWhere('entity.seller IS NOT NULL');
    }

    public function configureFields(string $pageName): iterable
    {
        if ($pageName === Crud::PAGE_EDIT) {
            yield BooleanField::new('aLaUne', 'À la une')
                ->setHelp('Permet de mettre ce produit vendeur en avant sur le site.');

            yield BooleanField::new('isBlockedByAdmin', 'Produit bloqué par l’admin')
                ->setHelp('Si ce champ est activé, le produit reste invisible sur le front. Le vendeur peut le corriger, mais il ne peut pas lever le blocage.');

            yield TextareaField::new('adminBlockReason', 'Motif du blocage')
                ->setRequired(false)
                ->setHelp('Ce message sera visible par le vendeur lorsqu’il modifiera son produit.');

            yield BooleanField::new('hasSellerUpdateAfterAdminBlock', 'Produit corrigé par le vendeur')
                ->renderAsSwitch(false)
                ->setDisabled()
                ->setHelp('Indique si le vendeur a modifié ce produit depuis le blocage admin.');

            yield DateTimeField::new('sellerUpdatedAfterAdminBlockAt', 'Dernière correction vendeur')
                ->setDisabled();

            return;
        }

        yield TextField::new('imagePreview', 'Image')
            ->formatValue(function ($value, Product $entity) {
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

        yield TextField::new('adminVariantsPreview', 'Variantes')
            ->formatValue(function ($value, Product $product) {
                if ($product->getVariants()->isEmpty()) {
                    return '<span style="color:#6b7280;">Aucune variante</span>';
                }

                $html = '<ul style="margin:0; padding-left:16px;">';

                foreach ($product->getVariants() as $variant) {
                    $status = $variant->isActive() ? 'active' : 'inactive';
                    $statusColor = $variant->isActive() ? '#16a34a' : '#dc2626';

                    $priceModifier = $variant->getPriceModifier()
                        ? number_format((float) $variant->getPriceModifier(), 2, ',', ' ') . ' €'
                        : '0,00 €';

                    $html .= sprintf(
                        '<li>
                            <strong>%s</strong> : %s 
                            <small style="color:#6b7280;">(+%s, stock: %d)</small>
                            <span style="color:%s;">● %s</span>
                        </li>',
                        htmlspecialchars($variant->getType() ?? '', ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($variant->getValue() ?? '', ENT_QUOTES, 'UTF-8'),
                        $priceModifier,
                        $variant->getStock() ?? 0,
                        $statusColor,
                        $status
                    );
                }

                $html .= '</ul>';

                return $html;
            })
            ->renderAsHtml()
            ->onlyOnIndex();

        yield AssociationField::new('seller', 'Vendeur');

        yield AssociationField::new('category', 'Catégorie');

        yield BooleanField::new('aLaUne', 'À la une');

        yield BooleanField::new('isActive', 'Actif vendeur')
            ->renderAsSwitch(false)
            ->setHelp('Indique si le vendeur a activé son produit.');

        yield BooleanField::new('isBlockedByAdmin', 'Bloqué admin');

        yield BooleanField::new('hasSellerUpdateAfterAdminBlock', 'Mis à jour par vendeur')
            ->renderAsSwitch(false)
            ->setHelp('Oui si le vendeur a corrigé ce produit depuis le blocage admin.');

        yield DateTimeField::new('sellerUpdatedAfterAdminBlockAt', 'Mis à jour le');

        yield DateTimeField::new('adminBlockedAt', 'Bloqué le');

        yield DateTimeField::new('createdAt', 'Créé le');

        yield TextareaField::new('adminBlockReason', 'Motif du blocage')
            ->onlyOnDetail();

        yield TextField::new('adminVariantsPreview', 'Détail des variantes')
            ->formatValue(function ($value, Product $product) {
                if ($product->getVariants()->isEmpty()) {
                    return '<span style="color:#6b7280;">Aucune variante pour ce produit.</span>';
                }

                $html = '
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">Type</th>
                                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">Valeur</th>
                                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">Supplément</th>
                                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">Stock</th>
                                <th style="text-align:left; border-bottom:1px solid #ddd; padding:6px;">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                ';

                foreach ($product->getVariants() as $variant) {
                    $status = $variant->isActive() ? 'Active' : 'Inactive';
                    $statusColor = $variant->isActive() ? '#16a34a' : '#dc2626';

                    $priceModifier = $variant->getPriceModifier()
                        ? number_format((float) $variant->getPriceModifier(), 2, ',', ' ') . ' €'
                        : '0,00 €';

                    $html .= sprintf(
                        '<tr>
                            <td style="padding:6px;">%s</td>
                            <td style="padding:6px;">%s</td>
                            <td style="padding:6px;">%s</td>
                            <td style="padding:6px;">%d</td>
                            <td style="padding:6px; color:%s;">%s</td>
                        </tr>',
                        htmlspecialchars($variant->getType() ?? '', ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($variant->getValue() ?? '', ENT_QUOTES, 'UTF-8'),
                        $priceModifier,
                        $variant->getStock() ?? 0,
                        $statusColor,
                        $status
                    );
                }

                $html .= '</tbody></table>';

                return $html;
            })
            ->renderAsHtml()
            ->onlyOnDetail();

        yield TextareaField::new('description', 'Description')
            ->onlyOnDetail();

        yield TextareaField::new('specification', 'Spécification')
            ->onlyOnDetail();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product) {
            return;
        }

        if ($entityInstance->getSeller() === null) {
            throw $this->createAccessDeniedException('Cette section est réservée aux produits vendeurs.');
        }

        /*
         * Si le produit est bloqué, il ne peut pas être à la une.
         */
        if ($entityInstance->isBlockedByAdmin()) {
            $entityInstance->setALaUne(false);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }
}