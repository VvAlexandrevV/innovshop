<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cette adresse email.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * Identifiant unique de l'utilisateur.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Email utilisé pour la connexion.
     */
    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * Liste des rôles Symfony de l'utilisateur.
     *
     * Exemple :
     * - ROLE_USER
     * - ROLE_SELLER
     * - ROLE_ADMIN
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * Mot de passe hashé.
     *
     * On ne stocke jamais le mot de passe en clair.
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * Panier lié à l'utilisateur connecté.
     *
     * Relation OneToOne :
     * un utilisateur possède un panier.
     */
    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Cart $cart = null;

    /**
     * Commandes passées par l'utilisateur.
     *
     * Relation OneToMany :
     * un utilisateur peut avoir plusieurs commandes.
     *
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Order::class)]
    private Collection $orders;

    /**
     * Prénom du client.
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstname = null;

    /**
     * Nom du client.
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastname = null;

    /**
     * Téléphone personnel du client.
     */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    /**
     * Adresse personnelle du client.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    /**
     * Code postal personnel du client.
     */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $postalCode = null;

    /**
     * Ville personnelle du client.
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    /**
     * Pays personnel du client.
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $country = null;

    /**
     * Produits créés par ce vendeur.
     *
     * Si l'utilisateur est vendeur, il peut avoir plusieurs produits.
     * Si l'utilisateur est un client classique, cette collection reste vide.
     *
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(mappedBy: 'seller', targetEntity: Product::class)]
    private Collection $sellerProducts;

    /**
     * Profil entreprise du vendeur.
     *
     * Relation OneToOne :
     * un User vendeur possède un seul SellerProfile.
     *
     * mappedBy signifie que SellerProfile possède la clé étrangère user_id.
     */
    #[ORM\OneToOne(mappedBy: 'user', targetEntity: SellerProfile::class, cascade: ['persist', 'remove'])]
    private ?SellerProfile $sellerProfile = null;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->sellerProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne l'email de l'utilisateur.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Définit l'email de l'utilisateur.
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Identifiant utilisé par Symfony Security.
     *
     * Ici, l'utilisateur se connecte avec son email.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * Retourne les rôles de l'utilisateur.
     *
     * On ajoute toujours ROLE_USER automatiquement.
     * Comme ça, même si la colonne roles est vide,
     * l'utilisateur reste un client classique.
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * Définit les rôles de l'utilisateur.
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Retourne le mot de passe hashé.
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Définit le mot de passe hashé.
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Méthode demandée par UserInterface.
     *
     * Utile si tu stockes des données sensibles temporaires.
     * Ici, on ne fait rien de spécial.
     */
    public function eraseCredentials(): void
    {
    }

    /**
     * Évite que le mot de passe complet soit conservé tel quel
     * lors de la sérialisation de l'utilisateur en session.
     */
    public function __serialize(): array
    {
        $data = (array) $this;

        if ($this->password !== null) {
            $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);
        }

        return $data;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    /**
     * Lie un panier à l'utilisateur.
     *
     * On synchronise aussi l'autre côté de la relation :
     * Cart -> User.
     */
    public function setCart(Cart $cart): static
    {
        if ($cart->getUser() !== $this) {
            $cart->setUser($this);
        }

        $this->cart = $cart;

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    /**
     * Ajoute une commande à l'utilisateur.
     */
    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setUser($this);
        }

        return $this;
    }

    /**
     * Retire une commande de la collection.
     */
    public function removeOrder(Order $order): static
    {
        $this->orders->removeElement($order);

        return $this;
    }

    /**
     * Affichage simple de l'utilisateur.
     *
     * Très utile dans EasyAdmin pour éviter :
     * "Object of class User could not be converted to string".
     */
    public function __toString(): string
    {
        return $this->getEmail() ?? '';
    }

    /**
     * Nombre total de commandes passées par l'utilisateur.
     *
     * Utilisé dans l'espace client ou dans l'administration.
     */
    public function getOrdersCount(): int
    {
        return $this->getOrders()->count();
    }

    /**
     * Total dépensé par l'utilisateur.
     *
     * Additionne le total de toutes ses commandes.
     */
    public function getTotalSpent(): float
    {
        $total = 0;

        foreach ($this->getOrders() as $order) {
            $total += $order->getTotal() ?? 0;
        }

        return $total;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getSellerProducts(): Collection
    {
        return $this->sellerProducts;
    }

    /**
     * Ajoute un produit à la liste des produits du vendeur.
     */
    public function addSellerProduct(Product $product): static
    {
        if (!$this->sellerProducts->contains($product)) {
            $this->sellerProducts->add($product);
            $product->setSeller($this);
        }

        return $this;
    }

    /**
     * Retire un produit de la liste des produits du vendeur.
     */
    public function removeSellerProduct(Product $product): static
    {
        if ($this->sellerProducts->removeElement($product)) {
            if ($product->getSeller() === $this) {
                $product->setSeller(null);
            }
        }

        return $this;
    }

    /**
     * Retourne le profil vendeur lié à l'utilisateur.
     */
    public function getSellerProfile(): ?SellerProfile
    {
        return $this->sellerProfile;
    }

    /**
     * Définit le profil vendeur de l'utilisateur.
     *
     * Important :
     * on synchronise aussi SellerProfile -> User.
     */
    public function setSellerProfile(?SellerProfile $sellerProfile): static
    {
        if ($sellerProfile !== null && $sellerProfile->getUser() !== $this) {
            $sellerProfile->setUser($this);
        }

        $this->sellerProfile = $sellerProfile;

        return $this;
    }

    /**
     * Vérifie si l'utilisateur est vendeur.
     */
    public function isSeller(): bool
    {
        return in_array('ROLE_SELLER', $this->getRoles(), true);
    }

    /**
     * Vérifie si l'utilisateur est admin.
     */
    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->getRoles(), true);
    }
}