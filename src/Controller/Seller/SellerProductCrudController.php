<?php

namespace App\Controller\Seller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

class SellerProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Mes produits')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter un produit')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier mon produit')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom', 'Nom');

        yield MoneyField::new('prix', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents(false);

        yield IntegerField::new('stock', 'Stock')
            ->setHelp('Stock disponible pour ce produit.');

        yield TextareaField::new('description', 'Description');

        yield TextareaField::new('specification', 'Spécification');

        yield ImageField::new('image', 'Image')
            ->setUploadDir('public/images/products')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(false)
            ->onlyOnForms();

        yield TextField::new('imagePreview', 'Image')
            ->formatValue(function ($value, Product $entity) {
                $image = $entity->getImage()
                    ? '/images/products/' . $entity->getImage()
                    : '/images/default-product.png';

                return sprintf('<img src="%s" style="max-height: 80px;">', $image);
            })
            ->renderAsHtml()
            ->onlyOnIndex();

        /*
         * Message admin visible côté vendeur.
         *
         * Le champ est désactivé :
         * le vendeur peut lire le motif, mais ne peut pas le modifier.
         *
         * Il apparaît sur le formulaire d’édition.
         */
        if ($pageName === Crud::PAGE_EDIT) {
            yield TextareaField::new('adminBlockReason', 'Message de l’administration')
                ->setDisabled()
                ->setRequired(false)
                ->setHelp('Si votre produit est en cours d’examen, ce message explique ce qui doit être corrigé.')
                ->onlyOnForms();
        }

        yield AssociationField::new('category', 'Catégorie');

        /*
         * Faux switch visuel.
         *
         * On n’utilise pas directement BooleanField sur isActive,
         * car le vendeur pourrait réactiver un produit bloqué par l’admin.
         *
         * Ce faux switch appelle nos actions sécurisées :
         * - activateProduct()
         * - deactivateProduct()
         */
        yield TextField::new('sellerActiveSwitch', 'Produit actif')
            ->formatValue(function ($value, Product $product) {
                $isActive = $product->isActive();
                $isBlocked = $product->isBlockedByAdmin();

                if ($isBlocked) {
                    return '
                        <span title="Produit bloqué par l’administration" style="
                            display:inline-flex;
                            align-items:center;
                            width:44px;
                            height:22px;
                            border-radius:999px;
                            background:#6b7280;
                            opacity:0.55;
                            position:relative;
                            cursor:not-allowed;
                        ">
                            <span style="
                                width:18px;
                                height:18px;
                                border-radius:50%;
                                background:white;
                                position:absolute;
                                left:2px;
                                top:2px;
                            "></span>
                        </span>
                        <small style="margin-left:8px;color:#f59e0b;">Enquête</small>
                    ';
                }

                $actionName = $isActive ? 'deactivateProduct' : 'activateProduct';

                $url = $this->container->get(AdminUrlGenerator::class)
                    ->setController(self::class)
                    ->setAction($actionName)
                    ->setEntityId($product->getId())
                    ->generateUrl();

                $background = $isActive ? '#2563eb' : '#4b5563';
                $circlePosition = $isActive ? '22px' : '2px';

                return sprintf(
                    '<a href="%s" title="%s" style="
                        display:inline-flex;
                        align-items:center;
                        width:44px;
                        height:22px;
                        border-radius:999px;
                        background:%s;
                        position:relative;
                        text-decoration:none;
                    ">
                        <span style="
                            width:18px;
                            height:18px;
                            border-radius:50%%;
                            background:white;
                            position:absolute;
                            left:%s;
                            top:2px;
                            transition:all .2s ease;
                        "></span>
                    </a>',
                    $url,
                    $isActive ? 'Désactiver le produit' : 'Activer le produit',
                    $background,
                    $circlePosition
                );
            })
            ->renderAsHtml()
            ->onlyOnIndex();

        yield BooleanField::new('isBlockedByAdmin', 'Enquête admin')
            ->renderAsSwitch(false)
            ->onlyOnIndex()
            ->setHelp('Si ce champ est actif, le produit est bloqué par l’administration.');

        yield BooleanField::new('hasSellerUpdateAfterAdminBlock', 'Correction envoyée')
            ->renderAsSwitch(false)
            ->onlyOnIndex()
            ->setHelp('Indique si ce produit bloqué a été modifié après le blocage admin.');

        yield TextareaField::new('adminBlockReason', 'Message admin')
            ->onlyOnDetail();
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->andWhere('entity.seller = :seller')
            ->setParameter('seller', $this->getUser());
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product) {
            return;
        }

        $entityInstance->setSeller($this->getUser());
        $entityInstance->setIsActive(true);
        $entityInstance->setIsBlockedByAdmin(false);
        $entityInstance->setALaUne(false);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product) {
            return;
        }

        $this->denyAccessIfProductDoesNotBelongToSeller($entityInstance);

        /*
         * Le vendeur peut corriger un produit bloqué.
         *
         * Mais le produit reste invisible tant que l’admin ne lève pas
         * le blocage dans le BO admin.
         */
        if ($entityInstance->isBlockedByAdmin()) {
            $alreadyMarkedAsUpdated = $entityInstance->hasSellerUpdateAfterAdminBlock();

            $entityInstance->markAsUpdatedAfterAdminBlock();

            if (!$alreadyMarkedAsUpdated) {
                $this->addFlash(
                    'info',
                    'Votre produit a été mis à jour. Il restera en cours d’examen tant que l’administration InnovShop n’aura pas levé le blocage.'
                );
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    #[AdminRoute(path: '/activate-product', name: 'activate_product')]
    public function activateProduct(
        AdminContext $context,
        EntityManagerInterface $entityManager
    ): Response {
        $product = $context->getEntity()->getInstance();

        if (!$product instanceof Product) {
            return $this->redirectToSellerProductIndex();
        }

        $this->denyAccessIfProductDoesNotBelongToSeller($product);

        if ($product->isBlockedByAdmin()) {
            $this->addFlash(
                'warning',
                'Ce produit est en cours d’enquête par l’administration InnovShop. Vous ne pouvez pas le réactiver tant que le blocage n’a pas été levé.'
            );

            return $this->redirectToSellerProductIndex();
        }

        $product->setIsActive(true);

        $entityManager->flush();

        $this->addFlash('success', 'Le produit a été réactivé.');

        return $this->redirectToSellerProductIndex();
    }

    #[AdminRoute(path: '/deactivate-product', name: 'deactivate_product')]
    public function deactivateProduct(
        AdminContext $context,
        EntityManagerInterface $entityManager
    ): Response {
        $product = $context->getEntity()->getInstance();

        if (!$product instanceof Product) {
            return $this->redirectToSellerProductIndex();
        }

        $this->denyAccessIfProductDoesNotBelongToSeller($product);

        if ($product->isBlockedByAdmin()) {
            $this->addFlash(
                'warning',
                'Ce produit est en cours d’enquête par l’administration InnovShop. Vous ne pouvez pas modifier son activation tant que le blocage n’a pas été levé.'
            );

            return $this->redirectToSellerProductIndex();
        }

        $product->setIsActive(false);

        $entityManager->flush();

        $this->addFlash('success', 'Le produit a été désactivé.');

        return $this->redirectToSellerProductIndex();
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product) {
            return;
        }

        $this->denyAccessIfProductDoesNotBelongToSeller($entityInstance);

        if ($entityInstance->isBlockedByAdmin()) {
            $this->addFlash(
                'warning',
                'Ce produit est en cours d’enquête. Vous ne pouvez pas modifier son activation tant que le blocage admin est actif.'
            );

            return;
        }

        $entityInstance->setIsActive(false);

        $entityManager->flush();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::DELETE);
    }

    private function denyAccessIfProductDoesNotBelongToSeller(Product $product): void
    {
        if ($product->getSeller() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres produits.');
        }
    }

    private function redirectToSellerProductIndex(): Response
    {
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }
}