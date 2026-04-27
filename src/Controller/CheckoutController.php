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

        $hasRemovedUnavailableProduct = false;

        foreach ($cart->getCartItems() as $cartItem) {
            $product = $cartItem->getProduct();

            if (!$product || !$product->isActive()) {
                $entityManager->remove($cartItem);
                $hasRemovedUnavailableProduct = true;
            }
        }

        if ($hasRemovedUnavailableProduct) {
            $entityManager->flush();

            $this->addFlash('warning', 'Un produit de votre panier n’est plus disponible et a été retiré.');

            return $this->redirectToRoute('app_panier');
        }

        if ($cart->getCartItems()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
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
        RequestStack $requestStack
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($order->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($order->getStatus() === 'pending_payment') {
            $order->setStatus('paid');

            $cart = $user->getCart();

            if ($cart) {
                foreach ($cart->getCartItems() as $cartItem) {
                    $entityManager->remove($cartItem);
                }
            }

            $requestStack->getSession()->remove('checkout_delivery');

            $entityManager->flush();
        }

        return $this->render('checkout/success.html.twig', [
            'order' => $order,
        ]);
    }
}