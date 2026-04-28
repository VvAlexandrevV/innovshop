<?php

namespace App\Entity;

use App\Repository\VariantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VariantRepository::class)]
class Variant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $value = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $priceModifier = null;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $stock = 0;

    #[ORM\ManyToOne(inversedBy: 'variants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    public function __toString(): string
    {
        return ($this->type ?? '') . ' - ' . ($this->value ?? '');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function getPriceModifier(): ?string
    {
        return $this->priceModifier;
    }

    public function setPriceModifier(?string $priceModifier): static
    {
        $this->priceModifier = $priceModifier;
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

    public function isAvailable(): bool
    {
        return $this->getStock() > 0;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }
}