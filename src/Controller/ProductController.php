<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    /**
     * Affiche le catalogue avec filtres + pagination.
     *
     * Fonctionnalité InnovShop :
     * Front Office - Catalogue produits.
     *
     * Gère :
     * - recherche texte
     * - filtre catégorie
     * - tri (prix, date…)
     * - filtre prix min/max
     * - pagination (KnpPaginator)
     */
    #[Route('/product', name: 'app_product')]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        PaginatorInterface $paginator
    ): Response {
        $search = $request->query->get('q');
        $categoryId = $request->query->getInt('category');
        $tri = $request->query->get('tri', 'newest');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');

        $queryBuilder = $productRepository->findBySearchAndCategory(
            $search,
            $categoryId,
            $tri,
            $minPrice !== null && $minPrice !== '' ? (float) $minPrice : null,
            $maxPrice !== null && $maxPrice !== '' ? (float) $maxPrice : null
        );

            $products = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10,
            [
                'route' => 'app_product',
                'params' => $request->query->all(),
            ]
        );

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll(),
            'search' => $search,
            'selectedCategory' => $categoryId,
            'tri' => $tri,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
        ]);
    }

    /**
     * Retourne les produits filtrés (AJAX).
     *
     * Fonctionnalité InnovShop :
     * Front Office - Recherche dynamique.
     *
     * Sert à recharger uniquement la grille produits
     * sans recharger toute la page.
     */
    #[Route('/product/search', name: 'app_product_search')]
    public function search(
        Request $request,
        ProductRepository $productRepository,
        PaginatorInterface $paginator
    ): Response {

    if (!$request->isXmlHttpRequest()) {
    return $this->redirectToRoute('app_product', $request->query->all());
}
        $search = $request->query->get('q');
        $categoryId = $request->query->getInt('category');
        $tri = $request->query->get('tri', 'newest');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');

        $queryBuilder = $productRepository->findBySearchAndCategory(
            $search,
            $categoryId,
            $tri,
            $minPrice !== null && $minPrice !== '' ? (float) $minPrice : null,
            $maxPrice !== null && $maxPrice !== '' ? (float) $maxPrice : null
        );

            $products = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10,
            [
                'route' => 'app_product',
                'params' => $request->query->all(),
            ]
        );

        return $this->render('product/_products_grid.html.twig', [
            'products' => $products,
        ]);
    }

    /**
     * Affiche la fiche produit.
     *
     * Fonctionnalité InnovShop :
     * Front Office - Détail produit.
     *
     * Vérifie que le produit est actif,
     * sinon redirection avec message d’erreur.
     */
    #[Route('/product/{id}', name: 'app_product_detail', requirements: ['id' => '\d+'])]
    public function detailProduct(int $id, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);

        if (!$product || !$product->isActive()) {
            $this->addFlash('error', 'Ce produit n’est plus disponible.');

            return $this->redirectToRoute('app_product');
        }

        return $this->render('product/detail.html.twig', [
            'product' => $product,
        ]);
    }
}