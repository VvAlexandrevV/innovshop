<?php

namespace App\Controller\Admin;

use App\Controller\Admin\AdminProductCrudController;
use App\Controller\Admin\CategoryCrudController;
use App\Controller\Admin\OrderCrudController;
use App\Controller\Admin\ProductCrudController;
use App\Controller\Admin\UserCrudController;
use App\Controller\Admin\VariantCrudController;
use App\Controller\Admin\VendorProductCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    /**
     * Page d’entrée du back-office.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Connexion au tableau de bord administrateur.
     *
     * Cette méthode vérifie d’abord que l’utilisateur est connecté.
     * S’il ne l’est pas, il est redirigé vers la page de connexion.
     *
     * Ensuite, elle vérifie que l’utilisateur possède le rôle ROLE_ADMIN.
     * Si ce n’est pas le cas, il est renvoyé vers l’accueil.
     *
     * Si l’utilisateur est bien administrateur, il est redirigé
     * directement vers la gestion des produits InnovShop.
     */
    public function index(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_home');
        }

        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        $url = $adminUrlGenerator
            ->setController(AdminProductCrudController::class)
            ->generateUrl();

        return $this->redirect($url);
    }

    /**
     * Configure l’apparence générale du dashboard EasyAdmin.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Tableau de bord administrateur.
     */
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('InnovShop');
    }

    /**
     * Configure le menu latéral du back-office.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Navigation administrateur.
     *
     * Le menu sépare maintenant :
     * - la vue globale de tous les produits ;
     * - les produits InnovShop modifiables par l’admin ;
     * - les produits vendeurs modérés par l’admin.
     */
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToRoute('Retour au site', 'fa fa-home', 'app_home');

        yield MenuItem::section('Produits');

        yield MenuItem::linkTo(ProductCrudController::class, 'Tous les produits', 'fa fa-boxes-stacked');
        yield MenuItem::linkTo(AdminProductCrudController::class, 'Produits InnovShop', 'fa fa-box');
        yield MenuItem::linkTo(VendorProductCrudController::class, 'Produits vendeurs', 'fa fa-store');

        yield MenuItem::section('Catalogue');

        yield MenuItem::linkTo(CategoryCrudController::class, 'Catégories', 'fa fa-tags');
        yield MenuItem::linkTo(VariantCrudController::class, 'Variantes InnovShop', 'fa fa-sitemap');

        yield MenuItem::section('Gestion');

        yield MenuItem::linkTo(OrderCrudController::class, 'Commandes', 'fa fa-shopping-bag');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-users');
    }
}