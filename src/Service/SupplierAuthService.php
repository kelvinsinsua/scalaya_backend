<?php

namespace App\Service;

use App\Entity\Supplier;
use App\Exception\BusinessLogicException;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SupplierAuthService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SupplierRepository $supplierRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private TokenService $tokenService
    ) {
    }

    /**
     * Register a new supplier
     */
    public function registerSupplier(array $data): Supplier
    {
        // Check if supplier with this email already exists
        $existingSupplier = $this->supplierRepository->findOneBy(['contactEmail' => $data['email']]);
        if ($existingSupplier) {
            throw new BusinessLogicException(
                'A supplier account with this email address already exists',
                'EMAIL_EXISTS',
                409
            );
        }

        $supplier = new Supplier();
        $supplier->setContactEmail($data['email']);
        $supplier->setCompanyName($data['companyName']);
        $supplier->setContactPerson($data['contactPerson']);
        $supplier->setPhone($data['phone'] ?? null);
        $supplier->setIsActive(true);

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($supplier, $data['password']);
        $supplier->setPassword($hashedPassword);

        $this->entityManager->persist($supplier);
        $this->entityManager->flush();

        return $supplier;
    }

    /**
     * Authenticate a supplier with email and password
     */
    public function authenticateSupplier(string $email, string $password): ?Supplier
    {
        $supplier = $this->supplierRepository->findOneBy(['contactEmail' => $email]);
        
        if (!$supplier || !$supplier->isActive()) {
            return null;
        }

        if (!$this->passwordHasher->isPasswordValid($supplier, $password)) {
            return null;
        }

        return $supplier;
    }

    /**
     * Generate a password reset token for a supplier
     */
    public function generatePasswordResetToken(Supplier $supplier): string
    {
        $token = $this->tokenService->generatePasswordResetToken();
        $hashedToken = $this->tokenService->hashToken($token);
        $expiresAt = $this->tokenService->getTokenExpirationTime();

        $supplier->setPasswordResetToken($hashedToken);
        $supplier->setPasswordResetTokenExpiresAt($expiresAt);

        $this->entityManager->flush();

        return $token; // Return the plain token for sending via email
    }

    /**
     * Reset password using a token
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $hashedToken = $this->tokenService->hashToken($token);
        
        $supplier = $this->supplierRepository->findOneBy(['passwordResetToken' => $hashedToken]);
        
        if (!$supplier) {
            return false;
        }

        // Check if token has expired
        if ($supplier->getPasswordResetTokenExpiresAt() && 
            $this->tokenService->isTokenExpired($supplier->getPasswordResetTokenExpiresAt())) {
            return false;
        }

        // Update password and clear reset token
        $hashedPassword = $this->passwordHasher->hashPassword($supplier, $newPassword);
        $supplier->setPassword($hashedPassword);
        $supplier->setPasswordResetToken(null);
        $supplier->setPasswordResetTokenExpiresAt(null);

        $this->entityManager->flush();

        return true;
    }

    /**
     * Change password for an authenticated supplier
     */
    public function changePassword(Supplier $supplier, string $currentPassword, string $newPassword): bool
    {
        // Verify current password
        if (!$this->passwordHasher->isPasswordValid($supplier, $currentPassword)) {
            return false;
        }

        // Update to new password
        $hashedPassword = $this->passwordHasher->hashPassword($supplier, $newPassword);
        $supplier->setPassword($hashedPassword);

        $this->entityManager->flush();

        return true;
    }
}