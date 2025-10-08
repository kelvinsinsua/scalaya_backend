<?php

namespace App\Dto;

use App\Validator\Constraints\StrongPassword;
use Symfony\Component\Validator\Constraints as Assert;

class PasswordChangeRequest
{
    #[Assert\NotBlank(message: 'Current password is required')]
    public string $currentPassword;

    #[Assert\NotBlank(message: 'New password is required')]
    #[StrongPassword(min: 8, requireNumber: true, requireUppercase: true, requireLowercase: true)]
    public string $newPassword;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->currentPassword = $data['currentPassword'] ?? '';
        $dto->newPassword = $data['newPassword'] ?? '';

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'currentPassword' => $this->currentPassword,
            'newPassword' => $this->newPassword,
        ];
    }
}