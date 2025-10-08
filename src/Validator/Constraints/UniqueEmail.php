<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueEmail extends Constraint
{
    public string $message = 'An account with this email address already exists.';
    public string $entityClass;
    public string $emailField = 'email';

    public function __construct(
        string $entityClass,
        string $emailField = null,
        string $message = null,
        array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct([], $groups, $payload);

        $this->entityClass = $entityClass;
        $this->emailField = $emailField ?? $this->emailField;
        $this->message = $message ?? $this->message;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}