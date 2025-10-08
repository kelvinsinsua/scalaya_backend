<?php

namespace App\Dto;

use App\Entity\Customer;
use App\Validator\Constraints\StrongPassword;
use App\Validator\Constraints\UniqueEmail;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEmail(entityClass: Customer::class)]
class CustomerRegistrationRequest
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    #[Assert\Length(max: 180, maxMessage: 'Email must not exceed 180 characters')]
    public string $email;

    #[Assert\NotBlank(message: 'Password is required')]
    #[StrongPassword(min: 8, requireNumber: true, requireUppercase: true, requireLowercase: true)]
    public string $password;

    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(max: 100, maxMessage: 'First name must not exceed 100 characters')]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z\s\-\'\.]+$/',
        message: 'First name can only contain letters, spaces, hyphens, apostrophes, and periods'
    )]
    public string $firstName;

    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(max: 100, maxMessage: 'Last name must not exceed 100 characters')]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z\s\-\'\.]+$/',
        message: 'Last name can only contain letters, spaces, hyphens, apostrophes, and periods'
    )]
    public string $lastName;

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
        $dto->firstName = $data['firstName'] ?? '';
        $dto->lastName = $data['lastName'] ?? '';
        $dto->phone = $data['phone'] ?? null;

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'phone' => $this->phone,
        ];
    }
}