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
    #[Route('/panier/add/{id}', name: 'app_panier_add')]
    public function add(
        int $id,
        Request $request,
        ProductRepository $productRepository,
        VariantRepository $variantRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $product = $productRepository->find($id);

        if (!$product || !$product->isActive()) {
            $this->addFlash('error', 'Ce produit n’est plus disponible.');
            return $this->redirectToRoute('app_product');
        }

        $variantIds = $request->request->all('variantIds');
        $selectedVariants = [];

        if ($product->getVariants()->count() > 0 && empty($variantIds)) {
            $this->addFlash('error', 'Veuillez sélectionner au moins une variante avant d’ajouter ce produit au panier.');

            return $this->redirectToRoute('app_product_detail', ['id' => $product->getId()]);
        }

        foreach ($variantIds as $variantId) {
            $variant = $variantRepository->find($variantId);

            if (!$variant || !$product->getVariants()->contains($variant)) {
                $this->addFlash('error', 'Une variante sélectionnée est invalide.');

                return $this->redirectToRoute('app_product_detail', ['id' => $product->getId()]);
            }

            $selectedVariants[] = $variant;
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

                    if (!$product || !$product->isActive()) {
                        $entityManager->remove($cartItem);
                        $hasRemovedUnavailableProduct = true;
                        continue;
                    }

                    $variants = $cartItem->getVariants();
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

                if (!$product || !$product->isActive()) {
                    $hasRemovedUnavailableProduct = true;
                    continue;
                }

                $variants = [];

                foreach (($ligne['variantIds'] ?? []) as $variantId) {
                    $variant = $variantRepository->find($variantId);

                    if ($variant && $product->getVariants()->contains($variant)) {
                        $variants[] = $variant;
                    }
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
            $this->addFlash('warning', 'Un produit de votre panier n’est plus disponible et a été retiré.');
        }

        return $this->render('panier/index.html.twig', [
            'lignesPanier' => $lignesPanier,
            'total' => $total,
        ]);
    }

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

    #[Route('/panier/validation-commande', name: 'app_panier_validation_commande')]
    public function validationCommande(): Response
    {
        return $this->redirectToRoute('app_checkout');
    }

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