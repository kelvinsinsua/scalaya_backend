<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\SupplierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: SupplierRepository::class)]
#[ORM\Table(name: 'suppliers')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['contactEmail'], message: 'supplier.contact_email.unique')]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_MANAGER') or is_granted('ROLE_ADMIN')"
        ),
        new Get(
            security: "is_granted('ROLE_MANAGER') or is_granted('ROLE_ADMIN') or object == user"
        ),
        new Post(
            security: "is_granted('ROLE_MANAGER') or is_granted('ROLE_ADMIN')"
        ),
        new Put(
            security: "is_granted('ROLE_MANAGER') or is_granted('ROLE_ADMIN') or object == user"
        ),
        new Patch(
            security: "is_granted('ROLE_MANAGER') or is_granted('ROLE_ADMIN') or object == user"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        )
    ],
    normalizationContext: ['groups' => ['supplier:read']],
    denormalizationContext: ['groups' => ['supplier:write']],
    paginationItemsPerPage: 20
)]
class Supplier implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['supplier:read', 'list', 'detail'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'supplier.company_name.not_blank')]
    #[Assert\Length(max: 255, maxMessage: 'supplier.company_name.max_length')]
    #[Groups(['supplier:read', 'supplier:write', 'list', 'detail', 'create', 'update'])]
    private string $companyName;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'supplier.contact_email.not_blank')]
    #[Assert\Email(message: 'supplier.contact_email.invalid')]
    #[Assert\Length(max: 255, maxMessage: 'supplier.contact_email.max_length')]
    #[Groups(['supplier:read', 'supplier:write', 'list', 'detail', 'create', 'update'])]
    private string $contactEmail;

    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    #[Assert\Length(max: 150, maxMessage: 'supplier.contact_person.max_length')]
    #[Groups(['supplier:read', 'supplier:write', 'list', 'detail', 'create', 'update'])]
    private ?string $contactPerson = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Length(max: 20, maxMessage: 'supplier.phone.max_length')]
    #[Assert\Regex(
        pattern: '/^[\+]?[1-9][\d]{0,15}$/',
        message: 'supplier.phone.invalid_format'
    )]
    #[Groups(['supplier:read', 'supplier:write', 'list', 'detail', 'create', 'update'])]
    private ?string $phone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['supplier:read', 'supplier:write', 'detail', 'create', 'update'])]
    private ?string $address = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['supplier:read', 'supplier:write', 'detail', 'create', 'update'])]
    private ?string $notes = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: 'supplier.status.not_blank')]
    #[Assert\Choice(choices: ['active', 'inactive'], message: 'supplier.status.invalid')]
    #[Groups(['supplier:read', 'supplier:write', 'list', 'detail', 'create', 'update'])]
    private string $status = 'active';

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['supplier:read', 'detail'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['supplier:read', 'detail'])]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: Product::class)]
    #[Groups(['supplier:read', 'detail'])]
    private Collection $products;

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
        $this->products = new ArrayCollection();
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

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = $companyName;
        return $this;
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;
        return $this;
    }

    public function getContactPerson(): ?string
    {
        return $this->contactPerson;
    }

    public function setContactPerson(?string $contactPerson): static
    {
        $this->contactPerson = $contactPerson;
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

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
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
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setSupplier($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getSupplier() === $this) {
                $product->setSupplier(null);
            }
        }

        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
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
        return $this->contactEmail;
    }

    public function getRoles(): array
    {
        return ['ROLE_SUPPLIER'];
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function __toString(): string
    {
        return $this->companyName;
    }
}