<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

use App\Controller\Admin\ProductCrudController;
use App\Controller\Admin\CategoryCrudController;
use App\Controller\Admin\OrderCrudController;
use App\Controller\Admin\UserCrudController;
use App\Controller\Admin\VariantCrudController;

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
     * directement vers la gestion des produits.
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
            ->setController(ProductCrudController::class)
            ->generateUrl();

        return $this->redirect($url);
    }

    /**
     * Configure l’apparence générale du dashboard EasyAdmin.
     *
     * Fonctionnalité InnovShop :
     * Back Office - Tableau de bord administrateur.
     *
     * Ici, on définit simplement le titre affiché dans l’interface admin.
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
     * Cette méthode donne accès aux différentes zones de gestion :
     * produits, catégories, variantes, commandes et utilisateurs.
     */
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::linkTo(ProductCrudController::class, 'Produits', 'fa fa-box');
        yield MenuItem::linkTo(CategoryCrudController::class, 'Catégories', 'fa fa-tags');
        yield MenuItem::linkTo(VariantCrudController::class, 'Variantes', 'fa fa-tags');
        yield MenuItem::linkTo(OrderCrudController::class, 'Commandes', 'fa fa-shopping-bag');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-users');
    }
}