<?php

namespace App\Controller\Seller;

use App\Entity\User;
use App\Form\SellerProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SellerProfileController extends AbstractController
{
    /**
     * Affiche et traite la modification du profil vendeur.
     *
     * Fonctionnalité InnovShop :
     * Espace vendeur - Profil entreprise.
     *
     * Route :
     * /seller/profile
     *
     * Sécurité :
     * seul un utilisateur avec ROLE_SELLER peut accéder à cette page.
     *
     * Cette page permet au vendeur de modifier uniquement
     * les informations entreprise autorisées :
     * - email entreprise
     * - téléphone entreprise
     * - adresse entreprise
     * - code postal
     * - ville
     * - pays
     *
     * Le vendeur ne peut pas modifier :
     * - le nom officiel de l'entreprise
     * - le SIRET
     * - le statut
     * - le compte Stripe
     */
    #[Route('/seller/profile', name: 'seller_profile_edit')]
    #[IsGranted('ROLE_SELLER')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /**
         * Récupération de l'utilisateur connecté.
         *
         * Comme la route est protégée par ROLE_SELLER,
         * on s'attend à récupérer un User vendeur.
         */
        $user = $this->getUser();

        /**
         * Petite sécurité supplémentaire.
         *
         * getUser() peut techniquement retourner UserInterface|null.
         * Ici, on vérifie qu'on a bien une instance de notre entité User.
         */
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur invalide.');
        }

        /**
         * Récupération du profil vendeur lié à l'utilisateur.
         */
        $sellerProfile = $user->getSellerProfile();

        /**
         * Si un utilisateur a ROLE_SELLER mais aucun SellerProfile,
         * c'est une anomalie de données.
         *
         * On bloque proprement au lieu de provoquer une erreur obscure.
         */
        if ($sellerProfile === null) {
            $this->addFlash('error', 'Aucun profil vendeur n’est associé à votre compte.');

            return $this->redirectToRoute('app_home');
        }

        /**
         * Création du formulaire de modification.
         *
         * Le formulaire est directement lié à SellerProfile.
         * Il ne contient que les champs autorisés.
         */
        $form = $this->createForm(SellerProfileType::class, $sellerProfile);
        $form->handleRequest($request);

        /**
         * Si le formulaire est envoyé et valide,
         * on met à jour la date de modification puis on sauvegarde.
         */
        if ($form->isSubmitted() && $form->isValid()) {
            $sellerProfile->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'Votre profil vendeur a bien été mis à jour.');

            return $this->redirectToRoute('seller_profile_edit');
        }

        return $this->render('seller/profile/edit.html.twig', [
            'sellerProfile' => $sellerProfile,
            'sellerProfileForm' => $form,
        ]);
    }
}