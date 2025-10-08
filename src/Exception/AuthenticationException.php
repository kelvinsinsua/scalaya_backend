<?php

namespace App\Exception;

class AuthenticationException extends \Exception
{
    public function __construct(
        string $message = 'Authentication failed',
        private string $errorCode = 'AUTHENTICATION_FAILED',
        int $code = 401,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}