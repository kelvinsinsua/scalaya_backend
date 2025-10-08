<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'addresses')]
#[ORM\HasLifecycleCallbacks]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['list', 'detail'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'address.first_name.not_blank')]
    #[Assert\Length(max: 100, maxMessage: 'address.first_name.max_length')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'address.last_name.not_blank')]
    #[Assert\Length(max: 100, maxMessage: 'address.last_name.max_length')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    #[Assert\Length(max: 150, maxMessage: 'address.company.max_length')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private ?string $company = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'address.address_line1.not_blank')]
    #[Assert\Length(max: 255, maxMessage: 'address.address_line1.max_length')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private string $addressLine1;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'address.address_line2.max_length')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private ?string $addressLine2 = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'address.city.not_blank')]
    #[Assert\Length(max: 100, maxMessage: 'address.city.max_length')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private string $city;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'address.state.not_blank')]
    #[Assert\Length(max: 100, maxMessage: 'address.state.max_length')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private string $state;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: 'address.postal_code.not_blank')]
    #[Assert\Length(max: 20, maxMessage: 'address.postal_code.max_length')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private string $postalCode;

    #[ORM\Column(type: 'string', length: 2)]
    #[Assert\NotBlank(message: 'address.country.not_blank')]
    #[Assert\Length(exactly: 2, exactMessage: 'address.country.exact_length')]
    #[Assert\Country(message: 'address.country.invalid')]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private string $country;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Length(max: 20, maxMessage: 'address.phone.max_length')]
    #[Assert\Regex(
        pattern: '/^[\+]?[1-9][\d]{0,15}$/',
        message: 'address.phone.invalid_format'
    )]
    #[Groups(['list', 'detail', 'create', 'update'])]
    private ?string $phone = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['detail'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['detail'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getAddressLine1(): string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(string $addressLine1): static
    {
        $this->addressLine1 = $addressLine1;
        return $this;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(?string $addressLine2): static
    {
        $this->addressLine2 = $addressLine2;
        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;
        return $this;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getFormattedAddress(): string
    {
        $address = $this->addressLine1;
        
        if ($this->addressLine2) {
            $address .= ', ' . $this->addressLine2;
        }
        
        $address .= ', ' . $this->city . ', ' . $this->state . ' ' . $this->postalCode;
        $address .= ', ' . $this->country;
        
        return $address;
    }

    public function __toString(): string
    {
        return $this->getFormattedAddress();
    }
}