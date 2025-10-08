<?php

namespace App\Controller\Api;

use App\Dto\CustomerRegistrationRequest;
use App\Dto\LoginRequest;
use App\Dto\PasswordChangeRequest;
use App\Dto\PasswordRecoveryRequest;
use App\Dto\PasswordResetRequest;
use App\Entity\Customer;
use App\Exception\AuthenticationException;
use App\Exception\BusinessLogicException;
use App\Service\CustomerAuthService;
use App\Service\ErrorResponseService;
use App\Service\RequestValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/customer', name: 'api_customer_')]
class CustomerAuthController extends AbstractController
{
    public function __construct(
        private CustomerAuthService $customerAuthService,
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
        $validation = $this->validationService->validateJsonRequest($request, CustomerRegistrationRequest::class);
        
        if (!$validation['success']) {
            return $validation['response'];
        }

        /** @var CustomerRegistrationRequest $dto */
        $dto = $validation['dto'];

        // Register the customer
        $customer = $this->customerAuthService->registerCustomer($dto->toArray());

        return $this->errorResponseService->createSuccessResponse(
            'Customer registered successfully',
            [
                'customer' => [
                    'id' => $customer->getId(),
                    'email' => $customer->getEmail(),
                    'firstName' => $customer->getFirstName(),
                    'lastName' => $customer->getLastName(),
                    'phone' => $customer->getPhone(),
                    'status' => $customer->getStatus()
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

        // Authenticate the customer
        $customer = $this->customerAuthService->authenticateCustomer($dto->email, $dto->password);

        if (!$customer) {
            throw new AuthenticationException('Invalid email or password', 'INVALID_CREDENTIALS');
        }

        // Check if customer account is active
        if (!$customer->isActive()) {
            throw new AuthenticationException('Account is not active', 'ACCOUNT_INACTIVE', 403);
        }

        // Generate JWT token
        $token = $this->jwtManager->create($customer);

        return $this->errorResponseService->createSuccessResponse(
            'Login successful',
            [
                'token' => $token,
                'user' => [
                    'id' => $customer->getId(),
                    'email' => $customer->getEmail(),
                    'firstName' => $customer->getFirstName(),
                    'lastName' => $customer->getLastName(),
                    'type' => 'customer'
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

        // Find customer by email
        $customer = $this->entityManager->getRepository(Customer::class)->findOneBy(['email' => $dto->email]);

        if (!$customer) {
            throw new BusinessLogicException(
                'No account found with this email address',
                'EMAIL_NOT_FOUND',
                404
            );
        }

        // Generate password reset token
        $token = $this->customerAuthService->generatePasswordResetToken($customer);

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
        $success = $this->customerAuthService->resetPassword($dto->token, $dto->password);

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
        // Get the authenticated customer
        $customer = $this->getUser();
        
        if (!$customer instanceof Customer) {
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
        $success = $this->customerAuthService->changePassword(
            $customer, 
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