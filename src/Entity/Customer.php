<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\Table(name: 'customers')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'customer.email.unique')]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_MANAGER')",
            normalizationContext: ['groups' => ['customer:read', 'customer:list']]
        ),
        new Get(
            security: "is_granted('ROLE_MANAGER') or object == user",
            normalizationContext: ['groups' => ['customer:read', 'customer:detail']]
        ),
        new Post(
            security: "is_granted('ROLE_MANAGER')",
            denormalizationContext: ['groups' => ['customer:write']]
        ),
        new Put(
            security: "is_granted('ROLE_MANAGER') or object == user",
            denormalizationContext: ['groups' => ['customer:write']]
        ),
        new Patch(
            security: "is_granted('ROLE_MANAGER') or object == user",
            denormalizationContext: ['groups' => ['customer:write']]
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        )
    ],
    paginationItemsPerPage: 20
)]
class Customer implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_BLOCKED = 'blocked';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_BLOCKED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['customer:read', 'customer:list', 'customer:detail'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank(message: 'customer.email.not_blank')]
    #[Assert\Email(message: 'customer.email.invalid')]
    #[Assert\Length(max: 180, maxMessage: 'customer.email.max_length')]
    #[Groups(['customer:read', 'customer:write', 'customer:list', 'customer:detail'])]
    private string $email;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'customer.first_name.not_blank')]
    #[Assert\Length(max: 100, maxMessage: 'customer.first_name.max_length')]
    #[Groups(['customer:read', 'customer:write', 'customer:list', 'customer:detail'])]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'customer.last_name.not_blank')]
    #[Assert\Length(max: 100, maxMessage: 'customer.last_name.max_length')]
    #[Groups(['customer:read', 'customer:write', 'customer:list', 'customer:detail'])]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Length(max: 20, maxMessage: 'customer.phone.max_length')]
    #[Assert\Regex(
        pattern: '/^[\+]?[1-9][\d]{0,15}$/',
        message: 'customer.phone.invalid_format'
    )]
    #[Groups(['customer:read', 'customer:write', 'customer:list', 'customer:detail'])]
    private ?string $phone = null;

    #[ORM\OneToOne(targetEntity: Address::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'billing_address_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(['customer:detail', 'customer:write'])]
    private ?Address $billingAddress = null;

    #[ORM\OneToOne(targetEntity: Address::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'shipping_address_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(['customer:detail', 'customer:write'])]
    private ?Address $shippingAddress = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: 'customer.status.not_blank')]
    #[Assert\Choice(choices: self::STATUSES, message: 'customer.status.invalid')]
    #[Groups(['customer:read', 'customer:write', 'customer:list', 'customer:detail'])]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['customer:detail'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['customer:detail'])]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Order::class)]
    #[Groups(['customer:detail'])]
    private Collection $orders;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $passwordResetToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $passwordResetTokenExpiresAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->orders = new ArrayCollection();
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
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

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?Address $billingAddress): static
    {
        $this->billingAddress = $billingAddress;
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

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setCustomer($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getCustomer() === $this) {
                $order->setCustomer(null);
            }
        }

        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function setPasswordResetToken(?string $passwordResetToken): static
    {
        $this->passwordResetToken = $passwordResetToken;
        return $this;
    }

    public function getPasswordResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passwordResetTokenExpiresAt;
    }

    public function setPasswordResetTokenExpiresAt(?\DateTimeImmutable $passwordResetTokenExpiresAt): static
    {
        $this->passwordResetTokenExpiresAt = $passwordResetTokenExpiresAt;
        return $this;
    }

    // UserInterface implementation
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_CUSTOMER'];
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function __toString(): string
    {
        return $this->getFullName() . ' (' . $this->email . ')';
    }
}