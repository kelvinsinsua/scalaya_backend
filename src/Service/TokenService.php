<?php

namespace App\Service;

class TokenService
{
    private const TOKEN_LENGTH = 32;
    private const TOKEN_EXPIRY_HOURS = 24;

    /**
     * Generate a secure password reset token
     */
    public function generatePasswordResetToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }

    /**
     * Check if a token has expired
     */
    public function isTokenExpired(\DateTimeImmutable $expiresAt): bool
    {
        return $expiresAt < new \DateTimeImmutable();
    }

    /**
     * Hash a token for secure storage
     */
    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Get token expiration time (24 hours from now)
     */
    public function getTokenExpirationTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('+' . self::TOKEN_EXPIRY_HOURS . ' hours');
    }
}