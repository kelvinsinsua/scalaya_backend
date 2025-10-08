<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PasswordRecoveryRequest
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    public string $email;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->email = $data['email'] ?? '';

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
        ];
    }
}