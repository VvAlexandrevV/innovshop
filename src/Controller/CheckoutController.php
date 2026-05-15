<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use App\Service\StripeMarketplaceService;

final class CheckoutController extends AbstractController
{
    /**
     * Affiche et traite la première étape du checkout.
     *
     * Fonctionnalité InnovShop :
     * Processus de commande - Vérification du panier et saisie des informations de livraison.
     *
     * Cette méthode vérifie que l’utilisateur est connecté,
     * que son panier n’est pas vide et que les produits sont encore disponibles.
     *
     * Si le formulaire de livraison est valide, les informations sont stockées en session
     * puis l’utilisateur est redirigé vers l’étape de paiement.
     */
    #[Route('/checkout', name: 'app_checkout')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $cart = $user->getCart();

        if (!$cart || $cart->getCartItems()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_panier');
        }

        $stockErrorMessage = $this->getStockErrorMessage($cart);

        if ($stockErrorMessage) {
            $this->addFlash('warning', $stockErrorMessage);
            return $this->redirectToRoute('app_panier');
        }

        $order = new Order();
        $order->setUser($user);
        $order->setStatus('pending_payment');
        $order->setTotal('0');

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Le formulaire de livraison est invalide.');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $request->getSession()->set('checkout_delivery', [
                'firstName' => $order->getDeliveryFirstName(),
                'lastName' => $order->getDeliveryLastName(),
                'address' => $order->getDeliveryAddress(),
                'postalCode' => $order->getDeliveryPostalCode(),
                'city' => $order->getDeliveryCity(),
                'country' => $order->getDeliveryCountry(),
            ]);

            return $this->redirectToRoute('app_checkout_payment');
        }

        $total = 0;
        $checkoutItems = [];

        foreach ($cart->getCartItems() as $cartItem) {
            $product = $cartItem->getProduct();
            $price = $product->getPrix();

            foreach ($cartItem->getVariants() as $variant) {
                if ($variant->getPriceModifier()) {
                    $price += (float) $variant->getPriceModifier();
                }
            }

            $checkoutItems[] = [
                'cartItem' => $cartItem,
                'product' => $product,
                'variants' => $cartItem->getVariants(),
                'price' => $price,
            ];

            $total += $price;
        }

        return $this->render('checkout/index.html.twig', [
            'orderForm' => $form->createView(),
            'cartItems' => $checkoutItems,
            'total' => $total,
        ]);
    }

    /**
     * Valide la commande après paiement réussi.
     *
     * Fonctionnalité InnovShop :
     * Processus de commande - Confirmation de commande.
     *
     * Cette méthode vérifie que la commande appartient bien à l’utilisateur connecté.
     * Si la commande est encore en attente de paiement, elle passe au statut "paid".
     *
     * Elle envoie ensuite un email de confirmation, diminue le stock des produits
     * ou des variantes, vide le panier et supprime les informations de livraison en session.
     */
    #[Route('/checkout/success/{id}', name: 'app_checkout_success')]
    public function success(
        Order $order,
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        MailerInterface $mailer,
        StripeMarketplaceService $stripeMarketplaceService
    ): Response {
        
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($order->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($order->getStatus() === 'pending_payment') {
            $cart = $user->getCart();

            if (!$cart || $cart->getCartItems()->isEmpty()) {
                $this->addFlash('error', 'Votre panier est vide.');
                return $this->redirectToRoute('app_panier');
            }

            $stockErrorMessage = $this->getStockErrorMessage($cart);

            if ($stockErrorMessage) {
                $this->addFlash('warning', $stockErrorMessage);
                return $this->redirectToRoute('app_panier');
            }

            $order->setStatus('paid');

            $stripeMarketplaceService->transferSellerAmounts($order);
            try {
                $email = (new TemplatedEmail())
                    ->from('noreply@innovshop.fr')
                    ->to($user->getEmail())
                    ->subject('Confirmation de votre commande InnovShop #' . $order->getId())
                    ->htmlTemplate('emails/order_confirmation.html.twig')
                    ->context([
                        'order' => $order,
                    ]);

                $mailer->send($email);
            } catch (\Throwable $exception) {
                $this->addFlash('warning', 'La commande est validée, mais l’email de confirmation n’a pas pu être envoyé.');
            }

            foreach ($cart->getCartItems() as $cartItem) {
                if ($cartItem->getVariants()->isEmpty()) {
                    $cartItem->getProduct()->decreaseStock();
                } else {
                    foreach ($cartItem->getVariants() as $variant) {
                        $variant->decreaseStock();
                    }
                }

                $entityManager->remove($cartItem);
            }

            $requestStack->getSession()->remove('checkout_delivery');

            $entityManager->flush();
        }

        return $this->render('checkout/success.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * Vérifie la disponibilité réelle des produits et variantes du panier.
     *
     * Fonctionnalité InnovShop :
     * Panier / Commande - Contrôle du stock avant validation.
     *
     * Cette méthode empêche l’utilisateur de commander un produit désactivé,
     * une variante indisponible ou une quantité supérieure au stock disponible.
     *
     * Elle retourne un message d’erreur si un problème est détecté,
     * ou null si tout est valide.
     */
    private function getStockErrorMessage($cart): ?string
    {
        $productNeeds = [];
        $variantNeeds = [];

        foreach ($cart->getCartItems() as $cartItem) {
            $product = $cartItem->getProduct();
            $variants = $cartItem->getVariants();

            if (!$product || !$product->isActive()) {
                return 'Un produit de votre panier n’est plus disponible.';
            }

            if ($variants->isEmpty()) {
                if (!$product->canBeAddedWithoutVariant()) {
                    return 'Le produit "' . $product->getNom() . '" n’est plus disponible sans option.';
                }

                $productId = $product->getId();
                $productNeeds[$productId] = ($productNeeds[$productId] ?? 0) + 1;

                if ($productNeeds[$productId] > $product->getStock()) {
                    return 'Stock insuffisant pour "' . $product->getNom() . '". Il reste ' . $product->getStock() . ' article(s) disponible(s).';
                }

                continue;
            }

            foreach ($variants as $variant) {
                if (
                    !$variant->isAvailable() ||
                    $variant->getProduct() !== $product
                ) {
                    return 'Une option du produit "' . $product->getNom() . '" n’est plus disponible.';
                }

                $variantId = $variant->getId();
                $variantNeeds[$variantId] = ($variantNeeds[$variantId] ?? 0) + 1;

                if ($variantNeeds[$variantId] > $variant->getStock()) {
                    return 'Stock insuffisant pour "' . $product->getNom() . '" avec l’option "' . $variant->getType() . ' - ' . $variant->getValue() . '". Il reste ' . $variant->getStock() . ' article(s) disponible(s).';
                }
            }
        }

        return null;
    }
}