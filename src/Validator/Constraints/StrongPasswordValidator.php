<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class StrongPasswordValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof StrongPassword) {
            throw new UnexpectedTypeException($constraint, StrongPassword::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $errors = [];

        // Check minimum length
        if (strlen($value) < $constraint->min) {
            $errors[] = sprintf('at least %d characters', $constraint->min);
        }

        // Check for lowercase letter
        if ($constraint->requireLowercase && !preg_match('/[a-z]/', $value)) {
            $errors[] = 'one lowercase letter';
        }

        // Check for uppercase letter
        if ($constraint->requireUppercase && !preg_match('/[A-Z]/', $value)) {
            $errors[] = 'one uppercase letter';
        }

        // Check for number
        if ($constraint->requireNumber && !preg_match('/\d/', $value)) {
            $errors[] = 'one number';
        }

        // Check for special character
        if ($constraint->requireSpecialChar && !preg_match('/[^a-zA-Z\d]/', $value)) {
            $errors[] = 'one special character';
        }

        if (!empty($errors)) {
            $message = 'Password must contain ' . implode(', ', $errors) . '.';
            $this->context->buildViolation($message)
                ->setParameter('{{ min }}', (string) $constraint->min)
                ->addViolation();
        }
    }
}