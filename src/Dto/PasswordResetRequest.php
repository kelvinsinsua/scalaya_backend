<?php

namespace App\Dto;

use App\Validator\Constraints\StrongPassword;
use Symfony\Component\Validator\Constraints as Assert;

class PasswordResetRequest
{
    #[Assert\NotBlank(message: 'Reset token is required')]
    public string $token;

    #[Assert\NotBlank(message: 'Password is required')]
    #[StrongPassword(min: 8, requireNumber: true, requireUppercase: true, requireLowercase: true)]
    public string $password;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->token = $data['token'] ?? '';
        $dto->password = $data['password'] ?? '';

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'password' => $this->password,
        ];
    }
}