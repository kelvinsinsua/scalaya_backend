<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['sku'], message: 'product.sku.unique')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['list', 'detail'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'product.name.not_blank')]
    #[Assert\Length(max: 255, maxMessage: 'product.name.max_length')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    #[Assert\NotBlank(message: 'product.sku.not_blank')]
    #[Assert\Length(max: 100, maxMessage: 'product.sku.max_length')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private string $sku;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'product.supplier_reference.not_blank')]
    #[Assert\Length(max: 100, maxMessage: 'product.supplier_reference.max_length')]
    #[Groups(['detail', 'create', 'update'])]
    private string $supplierReference;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['detail', 'create', 'update'])]
    private ?string $description = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Assert\All([
        new Assert\Url(message: 'product.images.invalid_url', requireTld: true)
    ])]
    #[Assert\Count(max: 10, maxMessage: 'product.images.max_count')]
    #[Groups(['detail', 'create', 'update'])]
    private ?array $images = [];

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'product.cost_price.not_blank')]
    #[Assert\PositiveOrZero(message: 'product.cost_price.positive')]
    #[Groups(['detail', 'create', 'update'])]
    private string $costPrice;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'product.selling_price.not_blank')]
    #[Assert\PositiveOrZero(message: 'product.selling_price.positive')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private string $sellingPrice;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 3, nullable: true)]
    #[Assert\PositiveOrZero(message: 'product.weight.positive')]
    #[Groups(['detail', 'create', 'update'])]
    private ?string $weight = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Assert\Collection(
        fields: [
            'width' => new Assert\Optional([
                new Assert\Type(type: 'numeric', message: 'product.dimensions.width.numeric'),
                new Assert\PositiveOrZero(message: 'product.dimensions.width.positive')
            ]),
            'height' => new Assert\Optional([
                new Assert\Type(type: 'numeric', message: 'product.dimensions.height.numeric'),
                new Assert\PositiveOrZero(message: 'product.dimensions.height.positive')
            ]),
            'depth' => new Assert\Optional([
                new Assert\Type(type: 'numeric', message: 'product.dimensions.depth.numeric'),
                new Assert\PositiveOrZero(message: 'product.dimensions.depth.positive')
            ]),
        ],
        allowExtraFields: false,
        extraFieldsMessage: 'product.dimensions.extra_fields'
    )]
    #[Groups(['detail', 'create', 'update'])]
    private ?array $dimensions = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100, maxMessage: 'product.category.max_length')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private ?string $category = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull(message: 'product.stock_level.not_null')]
    #[Assert\PositiveOrZero(message: 'product.stock_level.positive')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private int $stockLevel = 0;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: 'product.status.not_blank')]
    #[Assert\Choice(choices: ['available', 'out_of_stock', 'discontinued'], message: 'product.status.invalid')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private string $status = 'available';

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['detail'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['detail'])]
    private \DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne(targetEntity: Supplier::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'product.supplier.not_null')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private ?Supplier $supplier = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: OrderItem::class)]
    private Collection $orderItems;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->orderItems = new ArrayCollection();
        $this->images = [];
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): static
    {
        $this->sku = $sku;
        return $this;
    }

    public function getSupplierReference(): string
    {
        return $this->supplierReference;
    }

    public function setSupplierReference(string $supplierReference): static
    {
        $this->supplierReference = $supplierReference;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }

    public function setImages(?array $images): static
    {
        $this->images = $images ?? [];
        return $this;
    }

    public function addImage(string $image): static
    {
        if (!in_array($image, $this->images ?? [])) {
            $this->images[] = $image;
        }
        return $this;
    }

    public function removeImage(string $image): static
    {
        $this->images = array_values(array_filter($this->images ?? [], fn($img) => $img !== $image));
        return $this;
    }

    public function getCostPrice(): string
    {
        return $this->costPrice;
    }

    public function setCostPrice(string $costPrice): static
    {
        $this->costPrice = $costPrice;
        return $this;
    }

    public function getSellingPrice(): string
    {
        return $this->sellingPrice;
    }

    public function setSellingPrice(string $sellingPrice): static
    {
        $this->sellingPrice = $sellingPrice;
        return $this;
    }

    public function getWeight(): ?string
    {
        return $this->weight;
    }

    public function setWeight(?string $weight): static
    {
        $this->weight = $weight;
        return $this;
    }

    public function getDimensions(): ?array
    {
        return $this->dimensions;
    }

    public function setDimensions(?array $dimensions): static
    {
        $this->dimensions = $dimensions;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getStockLevel(): int
    {
        return $this->stockLevel;
    }

    public function setStockLevel(int $stockLevel): static
    {
        $this->stockLevel = $stockLevel;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
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

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): static
    {
        $this->supplier = $supplier;
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
            $orderItem->setProduct($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getProduct() === $this) {
                $orderItem->setProduct(null);
            }
        }

        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->stockLevel > 0;
    }

    public function isInStock(): bool
    {
        return $this->stockLevel > 0;
    }

    public function getMargin(): float
    {
        $cost = (float) $this->costPrice;
        $selling = (float) $this->sellingPrice;
        
        if ($cost <= 0) {
            return 0;
        }
        
        return (($selling - $cost) / $cost) * 100;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}