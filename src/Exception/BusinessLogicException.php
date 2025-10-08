<?php

namespace App\Exception;

class BusinessLogicException extends \Exception
{
    public function __construct(
        string $message,
        private string $errorCode,
        int $code = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}