<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ProductRepository;

final class PanierController extends AbstractController
{
    #[Route('/panier/add/{id}', name: 'app_panier_add')]
    public function add(int $id, Request $request, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable');
        }

        $session = $request->getSession();
        $panier = $session->get('panier', []);

        $panier[] = [
            'productId' => $id,
        ];

        $session->set('panier', $panier);

        $referer = $request->headers->get('referer');

        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_product');
    }

    #[Route('/panier', name: 'app_panier')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $panier = $request->getSession()->get('panier', []);
        $produits = [];

        foreach ($panier as $ligne) {
            $product = $productRepository->find($ligne['productId']);

            if ($product) {
                $produits[] = $product;
            }
        }

        return $this->render('panier/index.html.twig', [
            'produits' => $produits,
        ]);
    }
}