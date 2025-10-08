<?php

namespace App\EventListener;

use App\Entity\Customer;
use App\Entity\Supplier;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

class JWTCreatedListener
{
    /**
     * Adds custom claims to JWT tokens.
     *
     * @param JWTCreatedEvent $event
     */
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();
        $payload = $event->getData();

        // Add user type and user ID to the token payload
        if ($user instanceof Customer) {
            $payload['user_type'] = 'customer';
            $payload['user_id'] = $user->getId();
            $payload['email'] = $user->getEmail();
        } elseif ($user instanceof Supplier) {
            $payload['user_type'] = 'supplier';
            $payload['user_id'] = $user->getId();
            $payload['email'] = $user->getContactEmail();
        }

        // Set the updated payload
        $event->setData($payload);
    }
}