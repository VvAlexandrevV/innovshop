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

final class CheckoutController extends AbstractController
{
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

    #[Route('/checkout/success/{id}', name: 'app_checkout_success')]
    public function success(
        Order $order,
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        MailerInterface $mailer
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