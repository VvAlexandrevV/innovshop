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

        $hasRemovedUnavailableProduct = false;

        foreach ($cart->getCartItems() as $cartItem) {
            $product = $cartItem->getProduct();

            if (!$product || !$product->isAvailable()) {
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
        $stockErrorMessage = $this->getStockErrorMessage($cart);

        if ($stockErrorMessage) {
            $this->addFlash('warning', $stockErrorMessage);
            return $this->redirectToRoute('app_panier');
        }
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
            $variants = $cartItem->getVariants();

            $price = $product->getPrix();
            $variantLabels = [];

            foreach ($variants as $variant) {
                $label = $variant->getType() . ' - ' . $variant->getValue();

                if ($variant->getPriceModifier()) {
                    $price += (float) $variant->getPriceModifier();
                    $label .= ' (+' . $variant->getPriceModifier() . ' €)';
                }

                $variantLabels[] = $label;
            }

            $variantLabel = !empty($variantLabels)
                ? implode(', ', $variantLabels)
                : null;

            $orderItem = new OrderItem();
            $orderItem->setOrderEntity($order);
            $orderItem->setProduct($product);
            $orderItem->setProductName($product->getNom());
            $orderItem->setProductPrice((string) $price);
            $orderItem->setVariantLabel($variantLabel);

            $total += $price;

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $variantLabel
                            ? $product->getNom() . ' - ' . $variantLabel
                            : $product->getNom(),
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