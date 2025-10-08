<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use App\Validator\Constraints\ValidOrderTotals;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
#[ORM\HasLifecycleCallbacks]
#[ValidOrderTotals]
class Order
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_SHIPPED,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['list', 'detail'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Groups(['list', 'detail'])]
    private string $orderNumber;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'order.subtotal.not_null')]
    #[Assert\PositiveOrZero(message: 'order.subtotal.positive')]
    #[Groups(['list', 'detail'])]
    private string $subtotal = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'order.tax_amount.not_null')]
    #[Assert\PositiveOrZero(message: 'order.tax_amount.positive')]
    #[Groups(['list', 'detail'])]
    private string $taxAmount = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'order.shipping_amount.not_null')]
    #[Assert\PositiveOrZero(message: 'order.shipping_amount.positive')]
    #[Groups(['list', 'detail'])]
    private string $shippingAmount = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'order.total_amount.not_null')]
    #[Assert\PositiveOrZero(message: 'order.total_amount.positive')]
    #[Groups(['list', 'detail'])]
    private string $totalAmount = '0.00';

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: 'order.status.not_blank')]
    #[Assert\Choice(choices: self::STATUSES, message: 'order.status.invalid')]
    #[Groups(['list', 'detail', 'update'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\ManyToOne(targetEntity: Address::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'order.shipping_address.not_null')]
    #[Groups(['detail', 'create'])]
    private ?Address $shippingAddress = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['detail'])]
    private ?\DateTimeImmutable $shippedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['detail'])]
    private ?\DateTimeImmutable $deliveredAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['detail'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['detail'])]
    private \DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'order.customer.not_null')]
    #[Groups(['list', 'detail', 'create'])]
    private ?Customer $customer = null;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Assert\Count(min: 1, minMessage: 'order.order_items.min_count')]
    #[Groups(['detail', 'create'])]
    private Collection $orderItems;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->orderItems = new ArrayCollection();
        $this->generateOrderNumber();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function generateOrderNumber(): void
    {
        $this->orderNumber = 'ORD-' . date('Y') . '-' . strtoupper(uniqid());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;
        return $this;
    }

    public function getSubtotal(): string
    {
        return $this->subtotal;
    }

    public function setSubtotal(string $subtotal): static
    {
        $this->subtotal = $subtotal;
        return $this;
    }

    public function getTaxAmount(): string
    {
        return $this->taxAmount;
    }

    public function setTaxAmount(string $taxAmount): static
    {
        $this->taxAmount = $taxAmount;
        return $this;
    }

    public function getShippingAmount(): string
    {
        return $this->shippingAmount;
    }

    public function setShippingAmount(string $shippingAmount): static
    {
        $this->shippingAmount = $shippingAmount;
        return $this;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        
        // Automatically set timestamps based on status
        if ($status === self::STATUS_SHIPPED && $this->shippedAt === null) {
            $this->shippedAt = new \DateTimeImmutable();
        }
        
        if ($status === self::STATUS_DELIVERED && $this->deliveredAt === null) {
            $this->deliveredAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getShippingAddress(): ?Address
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?Address $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;
        return $this;
    }

    public function getShippedAt(): ?\DateTimeImmutable
    {
        return $this->shippedAt;
    }

    public function setShippedAt(?\DateTimeImmutable $shippedAt): static
    {
        $this->shippedAt = $shippedAt;
        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeImmutable $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;
        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrder($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getOrder() === $this) {
                $orderItem->setOrder(null);
            }
        }

        return $this;
    }

    public function calculateTotals(): static
    {
        $subtotal = 0;
        
        foreach ($this->orderItems as $item) {
            $subtotal += (float) $item->getLineTotal();
        }
        
        $this->subtotal = number_format($subtotal, 2, '.', '');
        
        // Calculate total (subtotal + tax + shipping)
        $total = $subtotal + (float) $this->taxAmount + (float) $this->shippingAmount;
        $this->totalAmount = number_format($total, 2, '.', '');
        
        return $this;
    }

    public function getItemCount(): int
    {
        return $this->orderItems->count();
    }

    public function getTotalQuantity(): int
    {
        $totalQuantity = 0;
        
        foreach ($this->orderItems as $item) {
            $totalQuantity += $item->getQuantity();
        }
        
        return $totalQuantity;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isShipped(): bool
    {
        return $this->status === self::STATUS_SHIPPED;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function __toString(): string
    {
        return $this->orderNumber;
    }
}