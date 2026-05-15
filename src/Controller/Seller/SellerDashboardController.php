<?php

namespace App\Controller\Seller;

use App\Repository\OrderItemRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use App\Controller\Seller\SellerVariantCrudController;

#[AdminDashboard(routePath: '/seller', routeName: 'seller')]
class SellerDashboardController extends AbstractDashboardController
{

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('build/app.css');
    }

    public function index(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isGranted('ROLE_SELLER')) {
            return $this->redirectToRoute('app_home');
        }

        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        $url = $adminUrlGenerator
            ->setDashboard(self::class)
            ->setController(SellerProductCrudController::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    #[AdminRoute('/orders', name: 'seller_orders')]
    public function orders(
        Request $request,
        OrderItemRepository $orderItemRepository
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isGranted('ROLE_SELLER')) {
            return $this->redirectToRoute('app_home');
        }

        $seller = $this->getUser();

        $period = $request->query->get('period');
        $startDateInput = $request->query->get('startDate');
        $endDateInput = $request->query->get('endDate');

        $startDate = null;
        $endDate = null;

        if ($startDateInput) {
            $startDate = new \DateTimeImmutable($startDateInput . ' 00:00:00');
        }

        if ($endDateInput) {
            $endDate = new \DateTimeImmutable($endDateInput . ' 23:59:59');
        }

        if (!$startDate && !$endDate && $period) {
            $now = new \DateTimeImmutable();

            match ($period) {
                'day' => [
                    $startDate = $now->setTime(0, 0, 0),
                    $endDate = $now->setTime(23, 59, 59),
                ],
                'yesterday' => [
                    $startDate = $now->modify('yesterday')->setTime(0, 0, 0),
                    $endDate = $now->modify('yesterday')->setTime(23, 59, 59),
                ],
                'week' => [
                    $startDate = $now->modify('monday this week')->setTime(0, 0, 0),
                    $endDate = $now->modify('sunday this week')->setTime(23, 59, 59),
                ],
                'month' => [
                    $startDate = $now->modify('first day of this month')->setTime(0, 0, 0),
                    $endDate = $now->modify('last day of this month')->setTime(23, 59, 59),
                ],
                'year' => [
                    $startDate = $now->modify('first day of january this year')->setTime(0, 0, 0),
                    $endDate = $now->modify('last day of december this year')->setTime(23, 59, 59),
                ],
                default => null,
            };
        }

            $queryBuilder = $orderItemRepository->createQueryBuilder('oi')
                ->join('oi.orderEntity', 'o')
                ->join('oi.product', 'p')
                ->leftJoin('o.user', 'u')
                ->addSelect('o')
                ->addSelect('p')
                ->addSelect('u')
                ->where('oi.seller = :seller')
                ->setParameter('seller', $seller)
                ->orderBy('o.createdAt', 'DESC');

        if ($startDate) {
            $queryBuilder
                ->andWhere('o.createdAt >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $queryBuilder
                ->andWhere('o.createdAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        $items = $queryBuilder
            ->getQuery()
            ->getResult();

        $totalGross = 0;
        $totalCommission = 0;
        $totalNet = 0;
        $totalProductsSold = count($items);

        foreach ($items as $item) {
            $totalGross += (float) $item->getProductPrice();
            $totalCommission += (float) $item->getCommissionAmount();
            $totalNet += (float) $item->getSellerAmount();
        }

        return $this->render('seller/orders/index.html.twig', [
            'items' => $items,
            'totalGross' => $totalGross,
            'totalCommission' => $totalCommission,
            'totalNet' => $totalNet,
            'totalProductsSold' => $totalProductsSold,
            'period' => $period,
            'startDate' => $startDateInput,
            'endDate' => $endDateInput,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Espace vendeur InnovShop');
    }

    public function configureMenuItems(): iterable
    {

        $user = $this->getUser();

        if (
            $user instanceof \App\Entity\User
            && $user->isSeller()
            && $user->getSellerProfile()
            && !$user->getSellerProfile()->getStripeAccountId()
        ) {
            yield MenuItem::linkToRoute(
                '⚠ Connecter Stripe',
                'fa fa-credit-card',
                'seller_stripe_connect'
            );
        }
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        $productsUrl = $adminUrlGenerator
            ->setDashboard(self::class)
            ->setController(SellerProductCrudController::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();
        yield MenuItem::linkToRoute('Mon profil vendeur', 'fa fa-building', 'seller_profile_edit');    
        yield MenuItem::linkToRoute('Retour au site', 'fa fa-home', 'app_home');    

       yield MenuItem::linkToUrl('Mes produits', 'fa fa-box', $productsUrl);

        $variantsUrl = $adminUrlGenerator
            ->setDashboard(self::class)
            ->setController(SellerVariantCrudController::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();

        yield MenuItem::linkToUrl('Mes variantes', 'fa fa-tags', $variantsUrl);

        yield MenuItem::linkToRoute('Mes commandes', 'fa fa-shopping-bag', 'seller_orders');
    }
    
}