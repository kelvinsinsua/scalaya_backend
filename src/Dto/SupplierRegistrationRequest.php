<?php

namespace App\Dto;

use App\Entity\Supplier;
use App\Validator\Constraints\StrongPassword;
use App\Validator\Constraints\UniqueEmail;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEmail(entityClass: Supplier::class, emailField: 'contactEmail')]
class SupplierRegistrationRequest
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    #[Assert\Length(max: 255, maxMessage: 'Email must not exceed 255 characters')]
    public string $email;

    #[Assert\NotBlank(message: 'Password is required')]
    #[StrongPassword(min: 8, requireNumber: true, requireUppercase: true, requireLowercase: true)]
    public string $password;

    #[Assert\NotBlank(message: 'Company name is required')]
    #[Assert\Length(max: 255, maxMessage: 'Company name must not exceed 255 characters')]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9\s\-\&\.\,\'\"]+$/',
        message: 'Company name contains invalid characters'
    )]
    public string $companyName;

    #[Assert\NotBlank(message: 'Contact person is required')]
    #[Assert\Length(max: 150, maxMessage: 'Contact person must not exceed 150 characters')]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z\s\-\'\.]+$/',
        message: 'Contact person can only contain letters, spaces, hyphens, apostrophes, and periods'
    )]
    public string $contactPerson;

    #[Assert\Length(max: 20, maxMessage: 'Phone must not exceed 20 characters')]
    #[Assert\Regex(
        pattern: '/^[\+]?[1-9][\d]{0,15}$/',
        message: 'Invalid phone format. Use international format (e.g., +1234567890)'
    )]
    public ?string $phone = null;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->email = $data['email'] ?? '';
        $dto->password = $data['password'] ?? '';
        $dto->companyName = $data['companyName'] ?? '';
        $dto->contactPerson = $data['contactPerson'] ?? '';
        $dto->phone = $data['phone'] ?? null;

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'companyName' => $this->companyName,
            'contactPerson' => $this->contactPerson,
            'phone' => $this->phone,
        ];
    }
}