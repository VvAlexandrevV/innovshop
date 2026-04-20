<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Form\OrderType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final class CheckoutController extends AbstractController
{
// Cette méthode sert à gérer le processus de commande (checkout) : affichage du formulaire, création de la commande et transformation du panier en commande.
// Liée à Order, OrderItem, User, Cart, OrderType (formulaire) et au template checkout/index.html.twig.
#[Route('/checkout', name: 'app_checkout')]
public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $cart = $user->getCart();

        if (!$cart || $cart->getCartItems()->isEmpty()) {
            return $this->redirectToRoute('app_panier');
        }

        $order = new Order();

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setUser($user);
            $order->setStatus('En attente');

            $total = 0;

            $entityManager->persist($order);

            foreach ($cart->getCartItems() as $cartItem) {
                $product = $cartItem->getProduct();

                $orderItem = new OrderItem();
                $orderItem->setOrderEntity($order);
                $orderItem->setProduct($product);
                $orderItem->setProductName($product->getNom());
                $orderItem->setProductPrice((string) $product->getPrix());

                $total += $product->getPrix();

                $entityManager->persist($orderItem);
                $entityManager->remove($cartItem);
            }

            $order->setTotal((string) $total);

            $entityManager->flush();

            return $this->redirectToRoute('app_checkout_success', [
                'id' => $order->getId(),
            ]);
        }

        $total = 0;

        foreach ($cart->getCartItems() as $cartItem) {
            $total += $cartItem->getProduct()->getPrix();
        }

        return $this->render('checkout/index.html.twig', [
            'orderForm' => $form->createView(),
            'cartItems' => $cart->getCartItems(),
            'total' => $total,
        ]);
    }

    // Cette méthode sert à afficher la page de confirmation après une commande réussie.
    // Liée à l'entité Order et au template checkout/success.html.twig.
    #[Route('/checkout/success/{id}', name: 'app_checkout_success')]
    public function success(Order $order): Response
        {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();

            if (!$user || $order->getUser() !== $user) {
                throw $this->createAccessDeniedException();
            }

            return $this->render('checkout/success.html.twig', [
                'order' => $order,
            ]);
        }
}
