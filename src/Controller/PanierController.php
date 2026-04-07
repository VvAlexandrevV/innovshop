<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ProductRepository;

final class PanierController extends AbstractController
{
    //fonction pour ajouter au panier
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

    //fonction pour lire le panier
    #[Route('/panier', name: 'app_panier')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $panier = $request->getSession()->get('panier', []);
        $lignesPanier = [];
        $total = 0;

        foreach ($panier as $index => $ligne) {
            $product = $productRepository->find($ligne['productId']);

            if ($product) {
                $lignesPanier[] = [
                    'index' => $index,
                    'product' => $product,
                ];

                $total += $product->getPrix();
            }
        }

        return $this->render('panier/index.html.twig', [
            'lignesPanier' => $lignesPanier,
            'total' => $total,
        ]);
}

    //fonction pour vider le panier
    #[Route('/panier/clear', name: 'app_panier_clear')]
    public function clear(Request $request): Response
{
        $session = $request->getSession();
        $session->remove('panier');

        return $this->redirectToRoute('app_panier');
}

     //fonction pour supprimer un article
    #[Route('/panier/remove/{index}', name: 'app_panier_remove')]
    public function remove(int $index, Request $request): Response
    {
        $session = $request->getSession();

        $panier = $session->get('panier', []);//recup le panier

        if (isset($panier[$index])) { //verif que index existe
            unset($panier[$index]);   //on supprime u e ligne

            // reorganise le tableau
            $panier = array_values($panier);

            $session->set('panier', $panier);//on remet une session
        }

        return $this->redirectToRoute('app_panier');
    }
}
