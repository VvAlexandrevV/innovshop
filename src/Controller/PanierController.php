<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

final class PanierController extends AbstractController
{

// Cette méthode sert à ajouter un produit au panier, soit en session si l'utilisateur n'est pas connecté, soit en base de données s'il est connecté.
// Liée à ProductRepository, Cart, CartItem, User et aux pages produit / panier.
//fonction pour ajouter au panier
#[Route('/panier/add/{id}', name: 'app_panier_add')]
public function add(int $id,Request $request,ProductRepository $productRepository,EntityManagerInterface $entityManager): Response {
    $product = $productRepository->find($id);

    if (!$product) {
        throw $this->createNotFoundException('Produit introuvable');
    }

    if ($this->getUser()) {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $cart = $user->getCart();

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $cart->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($cart);
        }

        $cartItem = new CartItem();
        $cartItem->setCart($cart);
        $cartItem->setProduct($product);

        $entityManager->persist($cartItem);
        $entityManager->flush();
    } else {
        $session = $request->getSession();
        $panier = $session->get('panier', []);

        $panier[] = [
            'productId' => $id,
        ];

        $session->set('panier', $panier);
    }

    $referer = $request->headers->get('referer');

    if ($referer) {
        return $this->redirect($referer);
    }

    return $this->redirectToRoute('app_product');
}
    
    // Cette méthode sert à afficher le contenu du panier avec les produits et le total.
    // Liée à ProductRepository, Cart, CartItem, User et au template panier/index.html.twig.
    #[Route('/panier', name: 'app_panier')]
    public function index(Request $request, ProductRepository $productRepository): Response
        {
            $lignesPanier = [];
            $total = 0;

            if ($this->getUser()) {
                /** @var \App\Entity\User $user */
                $user = $this->getUser();
                $cart = $user->getCart();

                if ($cart) {
                    foreach ($cart->getCartItems() as $cartItem) {
                        $product = $cartItem->getProduct();

                        $lignesPanier[] = [
                            'id' => $cartItem->getId(),
                            'product' => $product,
                        ];

                        $total += $product->getPrix();
                    }
                }
            } else {
                $panier = $request->getSession()->get('panier', []);

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
            }

            return $this->render('panier/index.html.twig', [
                'lignesPanier' => $lignesPanier,
                'total' => $total,
            ]);
        }

    // Cette méthode sert à vider entièrement le panier, en session ou en base de données selon que l'utilisateur est connecté ou non.
    // Liée à Cart, CartItem, User et à la page panier.
    //fonction pour vider le panier
   #[Route('/panier/clear', name: 'app_panier_clear')]
    public function clear(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser()) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();

            $cart = $user->getCart();

            if ($cart) {
                foreach ($cart->getCartItems() as $cartItem) {
                    $entityManager->remove($cartItem);
                }

                $entityManager->flush();
            }
        } else {
            $session = $request->getSession();
            $session->remove('panier');
        }

        return $this->redirectToRoute('app_panier');
    }

    // Cette méthode sert à supprimer un seul article du panier stocké en session.
    // Liée à la session et au template panier/index.html.twig.
    //fonction pour supprimer un article
    #[Route('/panier/remove/session/{index}', name: 'app_panier_remove_session')]
    public function removeSession(int $index, Request $request): Response
        {
            $session = $request->getSession();
            $panier = $session->get('panier', []);

            if (isset($panier[$index])) {
                unset($panier[$index]);
                $panier = array_values($panier);
                $session->set('panier', $panier);
            }

            return $this->redirectToRoute('app_panier');
        }

    // Cette méthode sert à rediriger l'utilisateur vers l'étape de validation de commande.
    // Liée à CheckoutController et à la route app_checkout.
    //validation panier
    #[Route('/panier/validation-commande', name: 'app_panier_validation_commande')]
    public function validationCommande(): Response
        {
            return $this->redirectToRoute('app_checkout');
        }
        
    // Cette méthode sert à supprimer un seul article du panier enregistré en base de données pour un utilisateur connecté.
    // Liée à Cart, CartItem, User et à la page panier.
    #[Route('/panier/remove/cart-item/{id}', name: 'app_panier_remove_cart_item')]
    public function removeCartItem(int $id, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $cart = $user->getCart();

        if (!$cart) {
            return $this->redirectToRoute('app_panier');
        }

        $cartItemToRemove = null;

        foreach ($cart->getCartItems() as $cartItem) {
            if ($cartItem->getId() === $id) {
                $cartItemToRemove = $cartItem;
                break;
            }
        }

        if ($cartItemToRemove) {
            $entityManager->remove($cartItemToRemove);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_panier');
    }
}


