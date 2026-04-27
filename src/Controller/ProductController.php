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
    #[Route('/product', name: 'app_product')]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        PaginatorInterface $paginator
    ): Response {
        $search = $request->query->get('q');
        $categoryId = $request->query->getInt('category');

        $queryBuilder = $productRepository->findBySearchAndCategory($search, $categoryId);

        $products = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll(),
            'search' => $search,
            'selectedCategory' => $categoryId,
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_detail')]
    public function detailProduct(int $id, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);

        if (!$product || !$product->isActive()) {
            throw $this->createNotFoundException('Produit introuvable');
        }

        return $this->render('product/detail.html.twig', [
            'product' => $product,
        ]);
    }
}