<?php

namespace App\Service;

use App\Entity\Customer;
use App\Exception\BusinessLogicException;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CustomerAuthService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CustomerRepository $customerRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private TokenService $tokenService
    ) {
    }

    /**
     * Register a new customer
     */
    public function registerCustomer(array $data): Customer
    {
        // Check if customer with this email already exists
        $existingCustomer = $this->customerRepository->findOneBy(['email' => $data['email']]);
        if ($existingCustomer) {
            throw new BusinessLogicException(
                'A customer account with this email address already exists',
                'EMAIL_EXISTS',
                409
            );
        }

        $customer = new Customer();
        $customer->setEmail($data['email']);
        $customer->setFirstName($data['firstName']);
        $customer->setLastName($data['lastName']);
        $customer->setPhone($data['phone'] ?? null);
        $customer->setStatus(Customer::STATUS_ACTIVE);

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($customer, $data['password']);
        $customer->setPassword($hashedPassword);

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $customer;
    }

    /**
     * Authenticate a customer with email and password
     */
    public function authenticateCustomer(string $email, string $password): ?Customer
    {
        $customer = $this->customerRepository->findOneBy(['email' => $email]);
        
        if (!$customer || !$customer->isActive()) {
            return null;
        }

        if (!$this->passwordHasher->isPasswordValid($customer, $password)) {
            return null;
        }

        return $customer;
    }

    /**
     * Generate a password reset token for a customer
     */
    public function generatePasswordResetToken(Customer $customer): string
    {
        $token = $this->tokenService->generatePasswordResetToken();
        $hashedToken = $this->tokenService->hashToken($token);
        $expiresAt = $this->tokenService->getTokenExpirationTime();

        $customer->setPasswordResetToken($hashedToken);
        $customer->setPasswordResetTokenExpiresAt($expiresAt);

        $this->entityManager->flush();

        return $token; // Return the plain token for sending via email
    }

    /**
     * Reset password using a token
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $hashedToken = $this->tokenService->hashToken($token);
        
        $customer = $this->customerRepository->findOneBy(['passwordResetToken' => $hashedToken]);
        
        if (!$customer) {
            return false;
        }

        // Check if token has expired
        if ($customer->getPasswordResetTokenExpiresAt() && 
            $this->tokenService->isTokenExpired($customer->getPasswordResetTokenExpiresAt())) {
            return false;
        }

        // Update password and clear reset token
        $hashedPassword = $this->passwordHasher->hashPassword($customer, $newPassword);
        $customer->setPassword($hashedPassword);
        $customer->setPasswordResetToken(null);
        $customer->setPasswordResetTokenExpiresAt(null);

        $this->entityManager->flush();

        return true;
    }

    /**
     * Change password for an authenticated customer
     */
    public function changePassword(Customer $customer, string $currentPassword, string $newPassword): bool
    {
        // Verify current password
        if (!$this->passwordHasher->isPasswordValid($customer, $currentPassword)) {
            return false;
        }

        // Update to new password
        $hashedPassword = $this->passwordHasher->hashPassword($customer, $newPassword);
        $customer->setPassword($hashedPassword);

        $this->entityManager->flush();

        return true;
    }
}