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
    
    /**
     * Affiche le tableau de bord du compte utilisateur.
     *
     * Fonctionnalité InnovShop :
     * Espace client - Tableau de bord utilisateur.
     *
     * Cette méthode permet à un utilisateur connecté de voir ses informations principales,
     * le nombre total de commandes passées et le montant total dépensé.
     */
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

    /**
     * Affiche l’historique des commandes de l’utilisateur connecté.
     *
     * Fonctionnalité InnovShop :
     * Espace client - Historique des commandes.
     *
     * Cette méthode récupère uniquement les commandes du client connecté,
     * triées de la plus récente à la plus ancienne.
     */
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

    /**
     * Affiche et traite le formulaire de modification du profil utilisateur.
     *
     * Fonctionnalité InnovShop :
     * Espace client - Modification des informations de profil et de livraison.
     *
     * Cette méthode permet à l’utilisateur connecté de modifier ses informations.
     * Si le formulaire est valide, les changements sont enregistrés en base de données.
     */
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

    /**
     * Affiche le détail d’une commande appartenant à l’utilisateur connecté.
     *
     * Fonctionnalité InnovShop :
     * Espace client - Consultation du détail d’une commande.
     *
     * Cette méthode protège l’accès aux commandes :
     * un utilisateur ne peut voir que ses propres commandes.
     */
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