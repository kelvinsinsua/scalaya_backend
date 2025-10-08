<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use App\Validator\Constraints\SufficientStock;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ORM\Table(name: 'order_items')]
#[ORM\HasLifecycleCallbacks]
#[SufficientStock]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['list', 'detail'])]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull(message: 'order_item.quantity.not_null')]
    #[Assert\Positive(message: 'order_item.quantity.positive')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private int $quantity;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'order_item.unit_price.not_null')]
    #[Assert\PositiveOrZero(message: 'order_item.unit_price.positive')]
    #[Groups(['list', 'detail', 'create'])]
    private string $unitPrice;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'order_item.line_total.not_null')]
    #[Assert\PositiveOrZero(message: 'order_item.line_total.positive')]
    #[Groups(['list', 'detail'])]
    private string $lineTotal;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'order_item.order.not_null')]
    private ?Order $order = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'order_item.product.not_null')]
    #[Groups(['list', 'detail', 'create'])]
    private ?Product $product = null;

    public function __construct()
    {
        $this->quantity = 1;
        $this->unitPrice = '0.00';
        $this->lineTotal = '0.00';
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateLineTotal(): void
    {
        if ($this->quantity > 0) {
            // Use product's selling price if unit price is not set and product exists
            if ($this->unitPrice === '0.00' && $this->product) {
                $this->unitPrice = $this->product->getSellingPrice();
            }
            
            $total = (float) $this->unitPrice * $this->quantity;
            $this->lineTotal = number_format($total, 2, '.', '');
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        $this->calculateLineTotal();
        return $this;
    }

    public function getUnitPrice(): string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;
        $this->calculateLineTotal();
        return $this;
    }

    public function getLineTotal(): string
    {
        return $this->lineTotal;
    }

    public function setLineTotal(string $lineTotal): static
    {
        $this->lineTotal = $lineTotal;
        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;
        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        
        // Automatically set unit price from product if not already set
        if ($product && $this->unitPrice === '0.00') {
            $this->unitPrice = $product->getSellingPrice();
        }
        
        $this->calculateLineTotal();
        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->product?->getName();
    }

    public function getProductSku(): ?string
    {
        return $this->product?->getSku();
    }

    public function getTotalValue(): float
    {
        return (float) $this->lineTotal;
    }

    public function __toString(): string
    {
        $productName = $this->product?->getName() ?? 'Unknown Product';
        return sprintf('%s (x%d)', $productName, $this->quantity);
    }
}