<?php

namespace App\Controller;

use App\Entity\SellerProfile;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    /**
     * Gère l'inscription d'un utilisateur.
     *
     * Fonctionnalité InnovShop :
     * Front Office - Création de compte.
     *
     * Deux modes sont possibles :
     *
     * /register?type=individual
     * → création d'un compte particulier
     *
     * /register?type=company
     * → création d'un compte entreprise / vendeur
     *
     * Le gros avantage :
     * le formulaire affiché correspond vraiment au type choisi.
     */
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $entityManager
    ): Response {
        /**
         * Récupère le type demandé dans l'URL.
         *
         * Exemple :
         * /register?type=company
         */
        $accountType = $request->query->get('type', 'individual');

        /**
         * Sécurité :
         * si quelqu'un met un type inconnu dans l'URL,
         * on revient au formulaire particulier.
         */
        if (!in_array($accountType, ['individual', 'company'], true)) {
            $accountType = 'individual';
        }

        $user = new User();

        /**
         * Création du formulaire avec l'option account_type.
         *
         * Cette option permet au FormType de savoir s'il doit ajouter
         * les champs entreprise ou non.
         */
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'account_type' => $accountType,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * On récupère le type de compte depuis le champ caché.
             *
             * Important :
             * au moment du POST, on ne dépend pas seulement de l'URL.
             */
            $submittedAccountType = $form->get('accountType')->getData();

            if (!in_array($submittedAccountType, ['individual', 'company'], true)) {
                $submittedAccountType = 'individual';
            }

            /**
             * Récupération du mot de passe en clair.
             *
             * Il ne sera jamais stocké tel quel.
             */
            $plainPassword = $form->get('plainPassword')->getData();

            /**
             * Hash du mot de passe.
             */
            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $plainPassword)
            );

            /**
             * Cas entreprise :
             * - ajoute ROLE_SELLER
             * - crée un SellerProfile
             * - lie le profil au User
             */
            if ($submittedAccountType === 'company') {
                $user->setRoles(['ROLE_SELLER']);

                $sellerProfile = new SellerProfile();

                $sellerProfile
                    ->setCompanyName($this->getTrimmedFormValue($form, 'companyName'))
                    ->setSiret($this->getTrimmedFormValue($form, 'siret'))
                    ->setCompanyEmail($this->getTrimmedFormValue($form, 'companyEmail'))
                    ->setCompanyPhone($this->getTrimmedNullableFormValue($form, 'companyPhone'))
                    ->setCompanyAddress($this->getTrimmedFormValue($form, 'companyAddress'))
                    ->setCompanyPostalCode($this->getTrimmedFormValue($form, 'companyPostalCode'))
                    ->setCompanyCity($this->getTrimmedFormValue($form, 'companyCity'))
                    ->setCompanyCountry($this->getTrimmedFormValue($form, 'companyCountry'))
                    ->setStatus('pending')
                    ->setCreatedAt(new \DateTimeImmutable());

                /**
                 * Synchronise la relation User ↔ SellerProfile.
                 */
                $user->setSellerProfile($sellerProfile);

                $entityManager->persist($sellerProfile);
            }

            /**
             * Enregistrement du User.
             */
            $entityManager->persist($user);
            $entityManager->flush();

            /**
             * Connexion automatique après inscription.
             */
            return $security->login($user, LoginFormAuthenticator::class, 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'accountType' => $accountType,
        ]);
    }

    /**
     * Récupère une valeur texte depuis le formulaire.
     *
     * trim() retire les espaces inutiles au début et à la fin.
     */
    private function getTrimmedFormValue(FormInterface $form, string $fieldName): string
    {
        return trim((string) $form->get($fieldName)->getData());
    }

    /**
     * Récupère une valeur texte facultative depuis le formulaire.
     *
     * Si la valeur est vide, on retourne null.
     * Utile pour companyPhone.
     */
    private function getTrimmedNullableFormValue(FormInterface $form, string $fieldName): ?string
    {
        $value = $this->getTrimmedFormValue($form, $fieldName);

        return $value !== '' ? $value : null;
    }
}