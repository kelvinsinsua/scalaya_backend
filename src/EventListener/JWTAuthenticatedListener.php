<?php

namespace App\EventListener;

use App\Entity\Customer;
use App\Entity\Supplier;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Psr\Log\LoggerInterface;

class JWTAuthenticatedListener
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    /**
     * Handles post-authentication logic after JWT token validation.
     *
     * @param JWTAuthenticatedEvent $event
     */
    public function onJWTAuthenticated(JWTAuthenticatedEvent $event): void
    {
        $token = $event->getToken();
        $user = $token->getUser();
        $payload = $event->getPayload();

        // Log successful authentication
        if ($user instanceof Customer) {
            $this->logger->info('Customer authenticated via JWT', [
                'customer_id' => $user->getId(),
                'email' => $user->getEmail(),
                'user_type' => $payload['user_type'] ?? 'customer'
            ]);
        } elseif ($user instanceof Supplier) {
            $this->logger->info('Supplier authenticated via JWT', [
                'supplier_id' => $user->getId(),
                'email' => $user->getContactEmail(),
                'user_type' => $payload['user_type'] ?? 'supplier'
            ]);
        }

        // Additional post-authentication logic can be added here
        // For example: updating last login time, checking account status, etc.
    }
}