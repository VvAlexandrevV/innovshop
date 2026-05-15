<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $specification = null;

    #[ORM\Column]
    private ?float $prix = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column]
    private ?bool $aLaUne = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $seller = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $stock = 0;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $isBlockedByAdmin = false;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $hasSellerUpdateAfterAdminBlock = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sellerUpdatedAfterAdminBlockAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminBlockReason = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $adminBlockedAt = null;

    /**
     * @var Collection<int, Variant>
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: Variant::class, orphanRemoval: true)]
    private Collection $variants;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isActive = true;
        $this->isBlockedByAdmin = false;
        $this->stock = 0;
        $this->variants = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getSpecification(): ?string
    {
        return $this->specification;
    }

    public function setSpecification(string $specification): static
    {
        $this->specification = $specification;
        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function isALaUne(): ?bool
    {
        return $this->aLaUne;
    }

    public function setALaUne(bool $aLaUne): static
    {
        $this->aLaUne = $aLaUne;
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = max(0, $stock);
        return $this;
    }

    public function decreaseStock(int $quantity = 1): static
    {
        $this->stock = max(0, $this->stock - $quantity);
        return $this;
    }

    public function isBlockedByAdmin(): ?bool
    {
        return $this->isBlockedByAdmin;
    }

    public function setIsBlockedByAdmin(bool $isBlockedByAdmin): static
    {
        $wasBlocked = $this->isBlockedByAdmin;

        $this->isBlockedByAdmin = $isBlockedByAdmin;

        /*
        * Cas 1 :
        * Le produit vient d’être bloqué par l’admin.
        *
        * On initialise le blocage :
        * - date de blocage
        * - retrait automatique de la mise à la une
        * - correction vendeur remise à zéro
        */
        if ($isBlockedByAdmin && !$wasBlocked) {
            $this->adminBlockedAt = new \DateTimeImmutable();
            $this->aLaUne = false;
            $this->hasSellerUpdateAfterAdminBlock = false;
            $this->sellerUpdatedAfterAdminBlockAt = null;
        }

        /*
        * Cas 2 :
        * Le produit vient d’être débloqué par l’admin.
        *
        * On nettoie les infos de modération.
        */
        if (!$isBlockedByAdmin && $wasBlocked) {
            $this->adminBlockedAt = null;
            $this->adminBlockReason = null;
            $this->hasSellerUpdateAfterAdminBlock = false;
            $this->sellerUpdatedAfterAdminBlockAt = null;
        }

        /*
        * Cas 3 :
        * Le produit était déjà bloqué et reste bloqué.
        *
        * On ne touche pas à hasSellerUpdateAfterAdminBlock.
        * Sinon l’admin pourrait effacer sans le vouloir le signal
        * indiquant que le vendeur a corrigé son produit.
        */

        return $this;
    }

    public function getAdminBlockReason(): ?string
    {
        return $this->adminBlockReason;
    }

    public function setAdminBlockReason(?string $adminBlockReason): static
    {
        $this->adminBlockReason = $adminBlockReason;
        return $this;
    }

    public function getAdminBlockedAt(): ?\DateTimeImmutable
    {
        return $this->adminBlockedAt;
    }

    public function setAdminBlockedAt(?\DateTimeImmutable $adminBlockedAt): static
    {
        $this->adminBlockedAt = $adminBlockedAt;
        return $this;
    }

    public function hasAvailableVariant(): bool
    {
        foreach ($this->variants as $variant) {
            if ($variant->isAvailable()) {
                return true;
            }
        }

        return false;
    }

    public function isAvailable(): bool
    {
        return $this->isVisible() && ($this->getStock() > 0 || $this->hasAvailableVariant());
    }

    public function canBeAddedWithoutVariant(): bool
    {
        return $this->isVisible() && $this->getStock() > 0;
    }

    /**
     * @return Collection<int, Variant>
     */
    public function getVariants(): Collection
    {
        return $this->variants;
    }

    public function addVariant(Variant $variant): static
    {
        if (!$this->variants->contains($variant)) {
            $this->variants->add($variant);
            $variant->setProduct($this);
        }

        return $this;
    }

    public function removeVariant(Variant $variant): static
    {
        if ($this->variants->removeElement($variant)) {
            if ($variant->getProduct() === $this) {
                $variant->setProduct(null);
            }
        }

        return $this;
    }

    public function getSeller(): ?User
    {
        return $this->seller;
    }

    public function setSeller(?User $seller): static
    {
        $this->seller = $seller;
        return $this;
    }

    public function getImagePreview(): ?string
    {
        return $this->image;
    }

    public function isMarketplaceProduct(): bool
    {
        return $this->seller !== null;
    }

    public function isAdminProduct(): bool
    {
        return $this->seller === null;
    }

    public function isVisible(): bool
    {
        return $this->isActive() && !$this->isBlockedByAdmin();
    }

    public function hasSellerUpdateAfterAdminBlock(): ?bool
    {
        return $this->hasSellerUpdateAfterAdminBlock;
    }

    public function setHasSellerUpdateAfterAdminBlock(bool $hasSellerUpdateAfterAdminBlock): static
    {
        $this->hasSellerUpdateAfterAdminBlock = $hasSellerUpdateAfterAdminBlock;

        return $this;
    }

    public function getSellerUpdatedAfterAdminBlockAt(): ?\DateTimeImmutable
    {
        return $this->sellerUpdatedAfterAdminBlockAt;
    }

    public function setSellerUpdatedAfterAdminBlockAt(?\DateTimeImmutable $sellerUpdatedAfterAdminBlockAt): static
    {
        $this->sellerUpdatedAfterAdminBlockAt = $sellerUpdatedAfterAdminBlockAt;

        return $this;
    }

    public function markAsUpdatedAfterAdminBlock(): static
    {
        $this->hasSellerUpdateAfterAdminBlock = true;
        $this->sellerUpdatedAfterAdminBlockAt = new \DateTimeImmutable();

        return $this;
    }

    public function getSellerActiveSwitch(): string
    {
        return '';
    }

    public function getAdminVariantsPreview(): string
    {
        return '';
    }
}