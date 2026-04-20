<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    // Cette méthode sert à gérer l'accès au back-office et rediriger vers le CRUD des produits par défaut.
    // Liée à la sécurité (ROLE_ADMIN) et à ProductCrudController.
    public function index(): Response
    {
    
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        $url = $adminUrlGenerator
            ->setController(ProductCrudController::class)
            ->generateUrl();

        return $this->redirect($url);
    }

    // Cette méthode sert à configurer le dashboard (titre, configuration globale du BO).
    // Liée à EasyAdmin (affichage global du back-office).
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('InnovShop');
    }

    // Cette méthode sert à définir les éléments du menu du back-office (liens de navigation).
    // Liée à EasyAdmin et au ProductCrudController pour accéder à la gestion des produits.
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(ProductCrudController::class, 'Produits', 'fa fa-box');
    }
}