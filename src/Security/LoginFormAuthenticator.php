<?php

namespace App\Security;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    // Nom de la route utilisée pour afficher le formulaire de connexion.
    public const LOGIN_ROUTE = 'app_login';

    /**
     * Constructeur de l'authenticator.
     *
     * Symfony injecte automatiquement les services nécessaires :
     * - UrlGeneratorInterface : permet de générer des URL à partir des noms de routes
     * - EntityManagerInterface : permet d'enregistrer/modifier/supprimer des données en base
     * - ProductRepository : permet de récupérer les produits en base de données
     */
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository
    ) {
    }

    /**
     * Prépare les informations nécessaires à l'authentification.
     *
     * Cette méthode est appelée quand l'utilisateur soumet le formulaire de connexion.
     *
     * Elle récupère :
     * - l'adresse email saisie
     * - le mot de passe saisi
     * - le token CSRF du formulaire
     *
     * Important :
     * Ce n'est pas cette méthode qui vérifie directement si le mot de passe est bon.
     * Elle crée un "Passport" que Symfony va ensuite utiliser pour :
     * - retrouver l'utilisateur avec son email
     * - vérifier son mot de passe
     * - vérifier que le token CSRF est valide
     */
    public function authenticate(Request $request): Passport
    {
        // Récupère l'email envoyé depuis le formulaire de connexion.
        $email = $request->getPayload()->getString('email');

        // Stocke le dernier email saisi en session.
        // Cela permet de le réafficher dans le formulaire si la connexion échoue.
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        // Crée le Passport de connexion.
        // Symfony va utiliser ces informations pour authentifier l'utilisateur.
        return new Passport(
            // UserBadge : indique à Symfony quel utilisateur rechercher.
            // Ici, Symfony cherche l'utilisateur grâce à son email.
            new UserBadge($email),

            // PasswordCredentials : contient le mot de passe saisi.
            // Symfony le comparera avec le mot de passe hashé en base de données.
            new PasswordCredentials($request->getPayload()->getString('password')),

            [
                // CsrfTokenBadge : vérifie que le formulaire vient bien du site.
                // Cela protège contre les attaques CSRF.
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
            ]
        );
    }

    /**
     * Méthode appelée uniquement si la connexion réussit.
     *
     * Ici, on gère une logique spécifique à InnovShop :
     * si un visiteur avait ajouté des produits au panier avant de se connecter,
     * son panier était stocké en session.
     *
     * Après connexion, cette méthode :
     * - récupère le panier en session
     * - récupère l'utilisateur connecté
     * - récupère ou crée son panier en base de données
     * - transforme chaque ligne du panier session en CartItem en base
     * - vide ensuite le panier de session
     * - redirige l'utilisateur vers la page prévue ou vers l'accueil
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Récupère la session de l'utilisateur.
        $session = $request->getSession();

        // Récupère le panier stocké en session.
        // Si aucun panier n'existe en session, on récupère un tableau vide.
        $panier = $session->get('panier', []);

        /**
         * Récupère l'utilisateur connecté.
         *
         * À ce stade, l'authentification a réussi,
         * donc Symfony sait quel utilisateur est connecté.
         */
        /** @var User $user */
        $user = $token->getUser();

        // Si le panier session n'est pas vide, on le transfère en base de données.
        if (!empty($panier)) {
            // Récupère le panier déjà lié à l'utilisateur connecté.
            $cart = $user->getCart();

            // Si l'utilisateur n'a pas encore de panier en base, on en crée un.
            if (!$cart) {
                $cart = new Cart();

                // Lie le panier à l'utilisateur connecté.
                $cart->setUser($user);

                // Définit la date de création du panier.
                $cart->setCreatedAt(new \DateTimeImmutable());

                // Prépare l'enregistrement du panier en base de données.
                $this->entityManager->persist($cart);
            }

            // Parcourt chaque ligne du panier stocké en session.
            foreach ($panier as $ligne) {
                // Récupère le produit correspondant à l'identifiant stocké en session.
                $product = $this->productRepository->find($ligne['productId']);

                // Si le produit existe toujours en base, on crée une ligne de panier.
                if ($product) {
                    $cartItem = new CartItem();

                    // Lie la ligne de panier au panier de l'utilisateur.
                    $cartItem->setCart($cart);

                    // Lie la ligne de panier au produit récupéré.
                    $cartItem->setProduct($product);

                    // Prépare l'enregistrement de la ligne de panier en base.
                    $this->entityManager->persist($cartItem);
                }
            }

            // Exécute réellement toutes les insertions préparées en base de données.
            $this->entityManager->flush();

            // Une fois le panier transféré en base, on supprime le panier de session.
            // Cela évite d'avoir un doublon entre session et base de données.
            $session->remove('panier');
        }

        /**
         * Si Symfony avait enregistré une page cible avant la connexion,
         * on redirige l'utilisateur vers cette page.
         *
         * Exemple :
         * L'utilisateur voulait accéder au checkout.
         * Il n'était pas connecté.
         * Symfony l'a envoyé vers la page de connexion.
         * Après connexion, il est renvoyé vers le checkout.
         */
        if ($targetPath = $this->getTargetPath($session, $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Si aucune page cible n'était prévue, on redirige vers la page d'accueil.
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    /**
     * Retourne l'URL de la page de connexion.
     *
     * Symfony utilise cette méthode quand il doit rediriger un utilisateur
     * vers le formulaire de connexion.
     *
     * Exemple :
     * si un utilisateur non connecté essaie d'accéder à une page protégée,
     * Symfony l'envoie vers cette route.
     */
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}