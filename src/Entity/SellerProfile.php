<?php

namespace App\Entity;

use App\Repository\SellerProfileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SellerProfileRepository::class)]
class SellerProfile
{
    /**
     * Identifiant unique du profil vendeur.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Utilisateur lié au profil entreprise.
     *
     * Relation OneToOne :
     * un profil vendeur appartient à un seul User.
     *
     * inversedBy pointe vers la propriété sellerProfile dans User.
     */
    #[ORM\OneToOne(inversedBy: 'sellerProfile', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * Nom de l'entreprise affiché sur le front.
     *
     * Exemple :
     * "TechNova", "PixelMarket", "InnovSeller".
     */
    #[ORM\Column(length: 255)]
    private ?string $companyName = null;

    /**
     * Numéro SIRET de l'entreprise.
     *
     * En France, un SIRET contient 14 chiffres.
     */
    #[ORM\Column(length: 14)]
    private ?string $siret = null;

    /**
     * Email professionnel de l'entreprise.
     */
    #[ORM\Column(length: 255)]
    private ?string $companyEmail = null;

    /**
     * Téléphone professionnel.
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $companyPhone = null;

    /**
     * Adresse professionnelle.
     */
    #[ORM\Column(length: 255)]
    private ?string $companyAddress = null;

    /**
     * Code postal de l'entreprise.
     */
    #[ORM\Column(length: 20)]
    private ?string $companyPostalCode = null;

    /**
     * Ville de l'entreprise.
     */
    #[ORM\Column(length: 100)]
    private ?string $companyCity = null;

    /**
     * Pays de l'entreprise.
     */
    #[ORM\Column(length: 100)]
    private ?string $companyCountry = null;

    /**
     * Statut de validation du vendeur.
     *
     * Valeurs prévues :
     * - pending : en attente de validation
     * - approved : validé
     * - rejected : refusé
     * - suspended : suspendu
     */
    #[ORM\Column(length: 50)]
    private ?string $status = null;

    /**
     * Date de création du profil vendeur.
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Date de dernière modification du profil vendeur.
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Identifiant du compte Stripe Connect.
     *
     * Pour plus tard, quand tu mettras en place
     * le paiement marketplace avec répartition vendeur / plateforme.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeAccountId = null;

    public function __construct()
    {
        $this->status = 'pending';
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne l'utilisateur lié au profil vendeur.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Lie ce profil vendeur à un utilisateur.
     *
     * On synchronise aussi l'autre côté :
     * User -> SellerProfile.
     */
    public function setUser(User $user): static
    {
        $this->user = $user;

        if ($user->getSellerProfile() !== $this) {
            $user->setSellerProfile($this);
        }

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(string $siret): static
    {
        $this->siret = $siret;

        return $this;
    }

    public function getCompanyEmail(): ?string
    {
        return $this->companyEmail;
    }

    public function setCompanyEmail(string $companyEmail): static
    {
        $this->companyEmail = $companyEmail;

        return $this;
    }

    public function getCompanyPhone(): ?string
    {
        return $this->companyPhone;
    }

    public function setCompanyPhone(?string $companyPhone): static
    {
        $this->companyPhone = $companyPhone;

        return $this;
    }

    public function getCompanyAddress(): ?string
    {
        return $this->companyAddress;
    }

    public function setCompanyAddress(string $companyAddress): static
    {
        $this->companyAddress = $companyAddress;

        return $this;
    }

    public function getCompanyPostalCode(): ?string
    {
        return $this->companyPostalCode;
    }

    public function setCompanyPostalCode(string $companyPostalCode): static
    {
        $this->companyPostalCode = $companyPostalCode;

        return $this;
    }

    public function getCompanyCity(): ?string
    {
        return $this->companyCity;
    }

    public function setCompanyCity(string $companyCity): static
    {
        $this->companyCity = $companyCity;

        return $this;
    }

    public function getCompanyCountry(): ?string
    {
        return $this->companyCountry;
    }

    public function setCompanyCountry(string $companyCountry): static
    {
        $this->companyCountry = $companyCountry;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Définit le statut de validation du vendeur.
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getStripeAccountId(): ?string
    {
        return $this->stripeAccountId;
    }

    public function setStripeAccountId(?string $stripeAccountId): static
    {
        $this->stripeAccountId = $stripeAccountId;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * Affichage propre dans EasyAdmin.
     */
    public function __toString(): string
    {
        return $this->companyName ?? 'Profil vendeur';
    }
}