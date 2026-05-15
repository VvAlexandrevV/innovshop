<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Fichier : HomeController.php
 *
 * Rôle dans InnovShop :
 * Gère la page d'accueil publique du site.
 *
 * La page d'accueil affiche :
 * - les produits à la une
 * - les derniers produits ajoutés
 *
 * Les produits vendeurs sans compte Stripe Connect ne sont pas envoyés
 * à cette page, car ils ne doivent pas être visibles sur le front.
 */
final class HomeController extends AbstractController
{
    /**
     * Affiche la page d'accueil InnovShop.
     *
     * Fonctionnalité InnovShop :
     * Front Office - Page d'accueil.
     *
     * Cette méthode récupère :
     * - les 3 produits actifs à la une
     * - les 3 derniers produits actifs ajoutés
     *
     * Les règles de visibilité marketplace sont appliquées
     * directement dans ProductRepository.
     */
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository): Response
    {
        $produitsALaUne = $productRepository->findFeaturedForHome(3);
        $derniersProduits = $productRepository->findLatestForHome(3);

        return $this->render('home/index.html.twig', [
            'produitsALaUne' => $produitsALaUne,
            'derniersProduits' => $derniersProduits,
        ]);
    }
}