<?php

namespace App\Security;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class CustomerUserProvider implements UserProviderInterface
{
    public function __construct(
        private CustomerRepository $customerRepository
    ) {
    }

    /**
     * Loads the user for the given user identifier (email).
     *
     * @param string $identifier The user identifier (email)
     * @return UserInterface
     * @throws UserNotFoundException if the user is not found
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $customer = $this->customerRepository->findByEmail($identifier);

        if (!$customer) {
            throw new UserNotFoundException(sprintf('Customer with email "%s" not found.', $identifier));
        }

        // Only allow active customers to authenticate
        if (!$customer->isActive()) {
            throw new UserNotFoundException(sprintf('Customer with email "%s" is not active.', $identifier));
        }

        return $customer;
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
        if (!$user instanceof Customer) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        // Reload the customer from the database
        $refreshedCustomer = $this->customerRepository->find($user->getId());

        if (!$refreshedCustomer) {
            throw new UserNotFoundException(sprintf('Customer with ID "%s" not found.', $user->getId()));
        }

        // Check if customer is still active
        if (!$refreshedCustomer->isActive()) {
            throw new UserNotFoundException(sprintf('Customer with ID "%s" is no longer active.', $user->getId()));
        }

        return $refreshedCustomer;
    }

    /**
     * Whether this provider supports the given user class.
     *
     * @param string $class
     * @return bool
     */
    public function supportsClass(string $class): bool
    {
        return Customer::class === $class || is_subclass_of($class, Customer::class);
    }
}