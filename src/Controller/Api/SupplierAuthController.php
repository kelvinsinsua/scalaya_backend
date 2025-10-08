<?php

namespace App\Controller\Api;

use App\Dto\LoginRequest;
use App\Dto\PasswordChangeRequest;
use App\Dto\PasswordRecoveryRequest;
use App\Dto\PasswordResetRequest;
use App\Dto\SupplierRegistrationRequest;
use App\Entity\Supplier;
use App\Exception\AuthenticationException;
use App\Exception\BusinessLogicException;
use App\Service\ErrorResponseService;
use App\Service\RequestValidationService;
use App\Service\SupplierAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/supplier', name: 'api_supplier_')]
class SupplierAuthController extends AbstractController
{
    public function __construct(
        private SupplierAuthService $supplierAuthService,
        private JWTTokenManagerInterface $jwtManager,
        private RequestValidationService $validationService,
        private ErrorResponseService $errorResponseService,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        // Validate JSON request and create DTO
        $validation = $this->validationService->validateJsonRequest($request, SupplierRegistrationRequest::class);
        
        if (!$validation['success']) {
            return $validation['response'];
        }

        /** @var SupplierRegistrationRequest $dto */
        $dto = $validation['dto'];

        // Register the supplier
        $supplier = $this->supplierAuthService->registerSupplier($dto->toArray());

        return $this->errorResponseService->createSuccessResponse(
            'Supplier registered successfully',
            [
                'supplier' => [
                    'id' => $supplier->getId(),
                    'email' => $supplier->getContactEmail(),
                    'companyName' => $supplier->getCompanyName(),
                    'contactPerson' => $supplier->getContactPerson(),
                    'phone' => $supplier->getPhone(),
                    'status' => $supplier->getStatus()
                ]
            ],
            Response::HTTP_CREATED
        );
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        // Validate JSON request and create DTO
        $validation = $this->validationService->validateJsonRequest($request, LoginRequest::class);
        
        if (!$validation['success']) {
            return $validation['response'];
        }

        /** @var LoginRequest $dto */
        $dto = $validation['dto'];

        // Authenticate the supplier
        $supplier = $this->supplierAuthService->authenticateSupplier($dto->email, $dto->password);

        if (!$supplier) {
            throw new AuthenticationException('Invalid email or password', 'INVALID_CREDENTIALS');
        }

        // Check if supplier account is active
        if (!$supplier->isActive()) {
            throw new AuthenticationException('Account is not active', 'ACCOUNT_INACTIVE', 403);
        }

        // Generate JWT token
        $token = $this->jwtManager->create($supplier);

        return $this->errorResponseService->createSuccessResponse(
            'Login successful',
            [
                'token' => $token,
                'user' => [
                    'id' => $supplier->getId(),
                    'email' => $supplier->getContactEmail(),
                    'companyName' => $supplier->getCompanyName(),
                    'contactPerson' => $supplier->getContactPerson(),
                    'type' => 'supplier'
                ]
            ]
        );
    }

    #[Route('/password-recovery', name: 'password_recovery', methods: ['POST'])]
    public function passwordRecovery(Request $request): JsonResponse
    {
        // Validate JSON request and create DTO
        $validation = $this->validationService->validateJsonRequest($request, PasswordRecoveryRequest::class);
        
        if (!$validation['success']) {
            return $validation['response'];
        }

        /** @var PasswordRecoveryRequest $dto */
        $dto = $validation['dto'];

        // Find supplier by email
        $supplier = $this->entityManager->getRepository(Supplier::class)->findOneBy(['contactEmail' => $dto->email]);

        if (!$supplier) {
            throw new BusinessLogicException(
                'No account found with this email address',
                'EMAIL_NOT_FOUND',
                404
            );
        }

        // Generate password reset token
        $token = $this->supplierAuthService->generatePasswordResetToken($supplier);

        // In a real application, you would send this token via email
        // For now, we'll return a success message
        return $this->errorResponseService->createSuccessResponse(
            'Password recovery token has been sent to your email address',
            [
                // Note: In production, never return the actual token in the response
                // This is only for testing purposes
                'token' => $token
            ]
        );
    }

    #[Route('/password-reset', name: 'password_reset', methods: ['POST'])]
    public function passwordReset(Request $request): JsonResponse
    {
        // Validate JSON request and create DTO
        $validation = $this->validationService->validateJsonRequest($request, PasswordResetRequest::class);
        
        if (!$validation['success']) {
            return $validation['response'];
        }

        /** @var PasswordResetRequest $dto */
        $dto = $validation['dto'];

        // Reset password using token
        $success = $this->supplierAuthService->resetPassword($dto->token, $dto->password);

        if (!$success) {
            throw new BusinessLogicException(
                'Invalid or expired reset token',
                'INVALID_TOKEN'
            );
        }

        return $this->errorResponseService->createSuccessResponse(
            'Password has been reset successfully'
        );
    }

    #[Route('/password-change', name: 'password_change', methods: ['PUT'])]
    public function passwordChange(Request $request): JsonResponse
    {
        // Get the authenticated supplier
        $supplier = $this->getUser();
        
        if (!$supplier instanceof Supplier) {
            throw new AuthenticationException('Authentication required', 'AUTHENTICATION_REQUIRED');
        }

        // Validate JSON request and create DTO
        $validation = $this->validationService->validateJsonRequest($request, PasswordChangeRequest::class);
        
        if (!$validation['success']) {
            return $validation['response'];
        }

        /** @var PasswordChangeRequest $dto */
        $dto = $validation['dto'];

        // Change password
        $success = $this->supplierAuthService->changePassword(
            $supplier, 
            $dto->currentPassword, 
            $dto->newPassword
        );

        if (!$success) {
            throw new BusinessLogicException(
                'Current password is incorrect',
                'INVALID_CURRENT_PASSWORD'
            );
        }

        return $this->errorResponseService->createSuccessResponse(
            'Password has been changed successfully'
        );
    }
}