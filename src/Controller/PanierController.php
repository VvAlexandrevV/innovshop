<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Repository\ProductRepository;
use App\Repository\VariantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PanierController extends AbstractController
{
    /**
     * Ajoute un produit (avec ou sans variantes) au panier.
     *
     * Fonctionnalité InnovShop :
     * Front Office - Ajout au panier.
     *
     * Cette méthode gère :
     * - la vérification de disponibilité du produit
     * - la validation des variantes sélectionnées
     * - l'ajout en base si utilisateur connecté
     * - l'ajout en session si utilisateur non connecté
     *
     * Chaque ajout crée une nouvelle ligne (pas de quantité).
     */
    #[Route('/panier/add/{id}', name: 'app_panier_add')]
    public function add(
        int $id,
        Request $request,
        ProductRepository $productRepository,
        VariantRepository $variantRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $product = $productRepository->find($id);

        if (!$product || !$product->isAvailable()) {
            $this->addFlash('error', 'Ce produit n’est plus disponible.');
            return $this->redirectToRoute('app_product');
        }

        $variantIds = $request->request->all('variantIds');
        $selectedVariants = [];

        foreach ($variantIds as $variantId) {
            $variant = $variantRepository->find($variantId);

            if (
                !$variant ||
                $variant->getProduct() !== $product ||
                !$variant->isAvailable()
            ) {
                $this->addFlash('error', 'Une option sélectionnée n’est plus disponible.');

                return $this->redirectToRoute('app_product_detail', ['id' => $product->getId()]);
            }

            $selectedVariants[] = $variant;
        }

        if ($product->getStock() <= 0 && empty($selectedVariants)) {
            $this->addFlash('error', 'Le produit de base n’est plus disponible. Sélectionnez une option disponible.');

            return $this->redirectToRoute('app_product_detail', ['id' => $product->getId()]);
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

            foreach ($selectedVariants as $variant) {
                $cartItem->addVariant($variant);
            }

            $entityManager->persist($cartItem);
            $entityManager->flush();
        } else {
            $session = $request->getSession();
            $panier = $session->get('panier', []);

            $panier[] = [
                'productId' => $id,
                'variantIds' => array_map('intval', $variantIds),
            ];

            $session->set('panier', $panier);
        }

        $this->addFlash('success', 'Produit ajouté au panier.');

        return $this->redirectToRoute('app_panier');
    }

    /**
     * Affiche le contenu du panier.
     *
     * Fonctionnalité InnovShop :
     * Front Office - Consultation du panier.
     *
     * Cette méthode :
     * - reconstruit les lignes du panier (BDD ou session)
     * - supprime automatiquement les produits invalides
     * - calcule le prix total
     * - gère les variantes
     *
     * Elle nettoie aussi les incohérences (produits supprimés, stock 0, etc).
     */
    #[Route('/panier', name: 'app_panier')]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        VariantRepository $variantRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $lignesPanier = [];
        $total = 0;
        $hasRemovedUnavailableProduct = false;

        if ($this->getUser()) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $cart = $user->getCart();

            if ($cart) {
                foreach ($cart->getCartItems() as $cartItem) {
                    $product = $cartItem->getProduct();
                    $variants = $cartItem->getVariants();

                    if (!$product || !$product->isAvailable()) {
                        $entityManager->remove($cartItem);
                        $hasRemovedUnavailableProduct = true;
                        continue;
                    }

                    if ($product->getStock() <= 0 && $variants->isEmpty()) {
                        $entityManager->remove($cartItem);
                        $hasRemovedUnavailableProduct = true;
                        continue;
                    }

                    $hasInvalidVariant = false;

                    foreach ($variants as $variant) {
                        if (
                            !$variant->isAvailable() ||
                            $variant->getProduct() !== $product
                        ) {
                            $hasInvalidVariant = true;
                            break;
                        }
                    }

                    if ($hasInvalidVariant) {
                        $entityManager->remove($cartItem);
                        $hasRemovedUnavailableProduct = true;
                        continue;
                    }

                    $price = $product->getPrix();

                    foreach ($variants as $variant) {
                        if ($variant->getPriceModifier()) {
                            $price += (float) $variant->getPriceModifier();
                        }
                    }

                    $lignesPanier[] = [
                        'id' => $cartItem->getId(),
                        'product' => $product,
                        'variants' => $variants,
                        'price' => $price,
                    ];

                    $total += $price;
                }

                if ($hasRemovedUnavailableProduct) {
                    $entityManager->flush();
                }
            }
        } else {
            $session = $request->getSession();
            $panier = $session->get('panier', []);
            $cleanPanier = [];

            foreach ($panier as $ligne) {
                $product = $productRepository->find($ligne['productId']);

                if (!$product || !$product->isAvailable()) {
                    $hasRemovedUnavailableProduct = true;
                    continue;
                }

                $variants = [];
                $hasInvalidVariant = false;

                foreach (($ligne['variantIds'] ?? []) as $variantId) {
                    $variant = $variantRepository->find($variantId);

                    if (
                        !$variant ||
                        $variant->getProduct() !== $product ||
                        !$variant->isAvailable()
                    ) {
                        $hasInvalidVariant = true;
                        break;
                    }

                    $variants[] = $variant;
                }

                if ($hasInvalidVariant) {
                    $hasRemovedUnavailableProduct = true;
                    continue;
                }

                if ($product->getStock() <= 0 && empty($variants)) {
                    $hasRemovedUnavailableProduct = true;
                    continue;
                }

                $price = $product->getPrix();

                foreach ($variants as $variant) {
                    if ($variant->getPriceModifier()) {
                        $price += (float) $variant->getPriceModifier();
                    }
                }

                $cleanPanier[] = [
                    'productId' => $product->getId(),
                    'variantIds' => array_map(fn ($variant) => $variant->getId(), $variants),
                ];

                $lignesPanier[] = [
                    'index' => count($cleanPanier) - 1,
                    'product' => $product,
                    'variants' => $variants,
                    'price' => $price,
                ];

                $total += $price;
            }

            $session->set('panier', $cleanPanier);
        }

        if ($hasRemovedUnavailableProduct) {
            $this->addFlash('warning', 'Un produit ou une option de votre panier n’est plus disponible et a été retiré.');
        }

        return $this->render('panier/index.html.twig', [
            'lignesPanier' => $lignesPanier,
            'total' => $total,
        ]);
    }

    /**
     * Vide complètement le panier.
     *
     * Fonctionnalité InnovShop :
     * Front Office - Vider le panier.
     *
     * Supprime toutes les lignes :
     * - en base de données si connecté
     * - en session sinon
     */
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
            $request->getSession()->remove('panier');
        }

        return $this->redirectToRoute('app_panier');
    }

    /**
     * Supprime une ligne du panier (version session).
     *
     * Fonctionnalité InnovShop :
     * Front Office - Suppression d’un produit (non connecté).
     *
     * Supprime un élément du tableau session via son index.
     */
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

    /**
     * Redirige vers le checkout.
     *
     * Fonctionnalité InnovShop :
     * Front Office - Validation du panier.
     *
     * Point d’entrée vers le processus de commande.
     */
    #[Route('/panier/validation-commande', name: 'app_panier_validation_commande')]
    public function validationCommande(): Response
    {
        return $this->redirectToRoute('app_checkout');
    }

    /**
     * Supprime une ligne du panier (version utilisateur connecté).
     *
     * Fonctionnalité InnovShop :
     * Front Office - Suppression d’un produit (connecté).
     *
     * Supprime un CartItem en base de données.
     */
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

        foreach ($cart->getCartItems() as $cartItem) {
            if ($cartItem->getId() === $id) {
                $entityManager->remove($cartItem);
                $entityManager->flush();
                break;
            }
        }

        return $this->redirectToRoute('app_panier');
    }
}