<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Order;
use App\Form\UserProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final class AccountController extends AbstractController
{
    
    #[Route('/account', name: 'app_account')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('account/index.html.twig', [
            'user' => $user,
            'ordersCount' => $user->getOrdersCount(),
            'totalSpent' => $user->getTotalSpent(),
        ]);
    }

    #[Route('/account/orders', name: 'app_account_orders')]
    #[IsGranted('ROLE_USER')]
    public function orders(OrderRepository $orderRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $orders = $orderRepository->findByUserOrderedByNewest($user);

        return $this->render('account/orders.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/account/profile', name: 'app_account_profile')]
    #[Route('/account/profile', name: 'app_account_profile')]
    #[IsGranted('ROLE_USER')]
    public function profile(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Vos informations ont bien été mises à jour.');

            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/profile.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }

    #[Route('/account/orders/{id}', name: 'app_account_order_show')]
    #[IsGranted('ROLE_USER')]
    public function showOrder(Order $order): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($order->getUser() !== $user) {
            throw $this->createAccessDeniedException('Accès interdit à cette commande.');
        }

        return $this->render('account/show_order.html.twig', [
            'order' => $order,
            'orderItems' => $order->getOrderItems(),
        ]);
    }    
}