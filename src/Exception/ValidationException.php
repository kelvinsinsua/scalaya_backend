<?php

namespace App\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends \Exception
{
    public function __construct(
        private ConstraintViolationListInterface $violations,
        string $message = 'Validation failed',
        private string $errorCode = 'VALIDATION_ERROR',
        int $code = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}