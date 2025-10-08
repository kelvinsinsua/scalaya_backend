<?php

namespace App\Security;

use App\Entity\Supplier;
use App\Repository\SupplierRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SupplierUserProvider implements UserProviderInterface
{
    public function __construct(
        private SupplierRepository $supplierRepository
    ) {
    }

    /**
     * Loads the user for the given user identifier (contact email).
     *
     * @param string $identifier The user identifier (contact email)
     * @return UserInterface
     * @throws UserNotFoundException if the user is not found
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $supplier = $this->supplierRepository->findByContactEmail($identifier);

        if (!$supplier) {
            throw new UserNotFoundException(sprintf('Supplier with contact email "%s" not found.', $identifier));
        }

        // Only allow active suppliers to authenticate
        if (!$supplier->isActive()) {
            throw new UserNotFoundException(sprintf('Supplier with contact email "%s" is not active.', $identifier));
        }

        return $supplier;
    }

    /**
     * Refreshes the user after being reloaded from the session.
     *
     * @param UserInterface $user
     * @return UserInterface
     * @throws UnsupportedUserException if the user is not supported
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof Supplier) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        // Reload the supplier from the database
        $refreshedSupplier = $this->supplierRepository->find($user->getId());

        if (!$refreshedSupplier) {
            throw new UserNotFoundException(sprintf('Supplier with ID "%s" not found.', $user->getId()));
        }

        // Check if supplier is still active
        if (!$refreshedSupplier->isActive()) {
            throw new UserNotFoundException(sprintf('Supplier with ID "%s" is no longer active.', $user->getId()));
        }

        return $refreshedSupplier;
    }

    /**
     * Whether this provider supports the given user class.
     *
     * @param string $class
     * @return bool
     */
    public function supportsClass(string $class): bool
    {
        return Supplier::class === $class || is_subclass_of($class, Supplier::class);
    }
}