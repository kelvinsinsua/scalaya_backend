<?php

namespace App\Entity;

use App\Repository\AdminUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AdminUserRepository::class)]
#[ORM\Table(name: 'admin_users')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'An admin user with this email already exists.')]
class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['list', 'detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'admin_user.email.not_blank')]
    #[Assert\Email(message: 'admin_user.email.invalid')]
    #[Assert\Length(max: 180, maxMessage: 'admin_user.email.max_length')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private ?string $email = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'admin_user.roles.not_blank')]
    #[Assert\Count(min: 1, minMessage: 'admin_user.roles.min_count')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private array $roles = [];

    #[ORM\Column]
    #[Assert\NotBlank(message: 'admin_user.password.not_blank')]
    #[Assert\Length(min: 8, minMessage: 'admin_user.password.min_length')]
    #[Groups(['create', 'update'])]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'admin_user.first_name.not_blank')]
    #[Assert\Length(max: 100, maxMessage: 'admin_user.first_name.max_length')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'admin_user.last_name.not_blank')]
    #[Assert\Length(max: 100, maxMessage: 'admin_user.last_name.max_length')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'admin_user.status.not_blank')]
    #[Assert\Choice(choices: ['active', 'inactive'], message: 'admin_user.status.invalid')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private string $status = 'active';

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['detail'])]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['detail'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['detail'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        // Validate roles
        $validRoles = ['ROLE_ADMIN', 'ROLE_MANAGER', 'ROLE_OPERATOR'];
        foreach ($roles as $role) {
            if (!in_array($role, $validRoles)) {
                throw new \InvalidArgumentException(sprintf('Invalid role "%s". Valid roles are: %s', $role, implode(', ', $validRoles)));
            }
        }
        
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, ['active', 'inactive'])) {
            throw new \InvalidArgumentException('Status must be either "active" or "inactive".');
        }
        
        $this->status = $status;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function updateLastLogin(): static
    {
        $this->lastLoginAt = new \DateTime();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    public function isManager(): bool
    {
        return $this->hasRole('ROLE_MANAGER');
    }

    public function isOperator(): bool
    {
        return $this->hasRole('ROLE_OPERATOR');
    }
}