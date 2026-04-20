<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

final class ProductController extends AbstractController
{   
    // Cette méthode sert à afficher la liste des produits avec pagination.
    // Liée à ProductRepository, KnpPaginator et au template product/index.html.twig.
    #[Route('/product', name: 'app_product')]
    public function index(ProductRepository $productRepository, PaginatorInterface $paginator, Request $request): Response
    {

        $products_query = $productRepository->findAll();

        $products = $paginator->paginate(
        $products_query, /* query NOT result */
        $request->query->getInt('page', 1), /* page number */
        10 /* limit per page */
    );


        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    // Cette méthode sert à afficher le détail d’un produit précis.
    // Liée à ProductRepository et au template product/detail.html.twig.
   #[Route('/product/{id}', name: 'app_product_detail')]
    public function detailProduct(int $id, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable');
        }

        return $this->render('product/detail.html.twig', [
            'product' => $product,
        ]);
    }

}
