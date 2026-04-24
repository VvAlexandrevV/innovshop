<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PaymentController extends AbstractController
{
    #[Route('/checkout/payment', name: 'app_checkout_payment')]
    #[IsGranted('ROLE_USER')]
    public function payment(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $cart = $user->getCart();

        if (!$cart || $cart->getCartItems()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_panier');
        }

        $delivery = $requestStack->getSession()->get('checkout_delivery');

        if (
            !$delivery ||
            empty($delivery['firstName']) ||
            empty($delivery['lastName']) ||
            empty($delivery['address']) ||
            empty($delivery['postalCode']) ||
            empty($delivery['city']) ||
            empty($delivery['country'])
        ) {
            $this->addFlash('error', 'Les informations de livraison sont absentes ou incomplètes.');
            return $this->redirectToRoute('app_checkout');
        }

        foreach ($cart->getCartItems() as $cartItem) {
            $product = $cartItem->getProduct();

            if (!$product->isActive()) {
                $this->addFlash('error', 'Le produit "' . $product->getNom() . '" n’est plus disponible.');
                return $this->redirectToRoute('app_checkout');
            }
        }

        $order = new Order();
        $order->setUser($user);
        $order->setStatus('pending_payment');
        $order->setDeliveryFirstName($delivery['firstName']);
        $order->setDeliveryLastName($delivery['lastName']);
        $order->setDeliveryAddress($delivery['address']);
        $order->setDeliveryPostalCode($delivery['postalCode']);
        $order->setDeliveryCity($delivery['city']);
        $order->setDeliveryCountry($delivery['country']);

        $total = 0;
        $lineItems = [];

        $entityManager->persist($order);

        foreach ($cart->getCartItems() as $cartItem) {
            $product = $cartItem->getProduct();
            $price = $product->getPrix();

            $orderItem = new OrderItem();
            $orderItem->setOrderEntity($order);
            $orderItem->setProduct($product);
            $orderItem->setProductName($product->getNom());
            $orderItem->setProductPrice((string) $price);

            $total += $price;

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $product->getNom(),
                    ],
                    'unit_amount' => (int) round($price * 100),
                ],
                'quantity' => 1,
            ];

            $entityManager->persist($orderItem);
        }

        $order->setTotal((string) $total);

        $entityManager->flush();

        $stripe = new StripeClient($_ENV['STRIPE_SECRET_KEY']);

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $user->getEmail(),
            'line_items' => $lineItems,
            'success_url' => $this->generateUrl(
                'app_checkout_success',
                ['id' => $order->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'cancel_url' => $this->generateUrl(
                'app_checkout_cancel',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ]);

        return $this->redirect($session->url);
    }

    #[Route('/checkout/cancel', name: 'app_checkout_cancel')]
    #[IsGranted('ROLE_USER')]
    public function cancel(): Response
    {
        $this->addFlash('error', 'Paiement annulé.');
        return $this->redirectToRoute('app_panier');
    }
}