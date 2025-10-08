<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class StrongPassword extends Constraint
{
    public string $message = 'Password must be at least {{ min }} characters long and contain at least one lowercase letter, one uppercase letter, one number, and one special character.';
    public int $min = 8;
    public bool $requireSpecialChar = false;
    public bool $requireNumber = true;
    public bool $requireUppercase = true;
    public bool $requireLowercase = true;

    public function __construct(
        int $min = null,
        bool $requireSpecialChar = null,
        bool $requireNumber = null,
        bool $requireUppercase = null,
        bool $requireLowercase = null,
        string $message = null,
        array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct([], $groups, $payload);

        $this->min = $min ?? $this->min;
        $this->requireSpecialChar = $requireSpecialChar ?? $this->requireSpecialChar;
        $this->requireNumber = $requireNumber ?? $this->requireNumber;
        $this->requireUppercase = $requireUppercase ?? $this->requireUppercase;
        $this->requireLowercase = $requireLowercase ?? $this->requireLowercase;
        $this->message = $message ?? $this->message;
    }
}