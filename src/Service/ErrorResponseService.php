<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ErrorResponseService
{
    public const ERROR_CODES = [
        // Validation errors (400)
        'VALIDATION_ERROR' => 'Validation failed',
        'INVALID_JSON' => 'Invalid JSON data provided',
        'MISSING_FIELD' => 'Required field is missing',
        'INVALID_FORMAT' => 'Invalid data format',
        
        // Authentication errors (401)
        'INVALID_CREDENTIALS' => 'Invalid email or password',
        'AUTHENTICATION_REQUIRED' => 'Authentication required',
        'INVALID_TOKEN' => 'Invalid or expired token',
        'TOKEN_EXPIRED' => 'Token has expired',
        'TOKEN_MISSING' => 'Authentication token is missing',
        
        // Authorization errors (403)
        'ACCOUNT_INACTIVE' => 'Account is not active',
        'ACCESS_DENIED' => 'Access denied',
        'INSUFFICIENT_PERMISSIONS' => 'Insufficient permissions',
        
        // Not found errors (404)
        'EMAIL_NOT_FOUND' => 'No account found with this email address',
        'USER_NOT_FOUND' => 'User not found',
        'RESOURCE_NOT_FOUND' => 'Resource not found',
        
        // Conflict errors (409)
        'EMAIL_EXISTS' => 'An account with this email address already exists',
        'RESOURCE_CONFLICT' => 'Resource conflict',
        
        // Server errors (500)
        'INTERNAL_ERROR' => 'Internal server error',
        'REGISTRATION_FAILED' => 'Registration failed due to server error',
        'LOGIN_FAILED' => 'Login failed due to server error',
        'PASSWORD_RECOVERY_FAILED' => 'Password recovery failed due to server error',
        'PASSWORD_RESET_FAILED' => 'Password reset failed due to server error',
        'PASSWORD_CHANGE_FAILED' => 'Password change failed due to server error',
    ];

    /**
     * Create a standardized error response
     */
    public function createErrorResponse(
        string $errorCode,
        string $customMessage = null,
        array $details = [],
        int $statusCode = null
    ): JsonResponse {
        $message = $customMessage ?? self::ERROR_CODES[$errorCode] ?? 'Unknown error';
        $statusCode = $statusCode ?? $this->getStatusCodeForErrorCode($errorCode);

        return new JsonResponse([
            'error' => [
                'code' => $errorCode,
                'message' => $message,
                'details' => $details
            ]
        ], $statusCode);
    }

    /**
     * Create validation error response from constraint violations
     */
    public function createValidationErrorResponse(
        ConstraintViolationListInterface $violations,
        string $message = 'Validation failed'
    ): JsonResponse {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
                'invalidValue' => $violation->getInvalidValue()
            ];
        }

        return $this->createErrorResponse(
            'VALIDATION_ERROR',
            $message,
            $errors,
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Create success response
     */
    public function createSuccessResponse(
        string $message,
        array $data = [],
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        $response = ['message' => $message];
        
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }

        return new JsonResponse($response, $statusCode);
    }

    /**
     * Get appropriate HTTP status code for error code
     */
    private function getStatusCodeForErrorCode(string $errorCode): int
    {
        return match ($errorCode) {
            'VALIDATION_ERROR', 'INVALID_JSON', 'MISSING_FIELD', 'INVALID_FORMAT', 'INVALID_TOKEN' => Response::HTTP_BAD_REQUEST,
            'INVALID_CREDENTIALS', 'AUTHENTICATION_REQUIRED', 'TOKEN_EXPIRED', 'TOKEN_MISSING' => Response::HTTP_UNAUTHORIZED,
            'ACCOUNT_INACTIVE', 'ACCESS_DENIED', 'INSUFFICIENT_PERMISSIONS' => Response::HTTP_FORBIDDEN,
            'EMAIL_NOT_FOUND', 'USER_NOT_FOUND', 'RESOURCE_NOT_FOUND' => Response::HTTP_NOT_FOUND,
            'EMAIL_EXISTS', 'RESOURCE_CONFLICT' => Response::HTTP_CONFLICT,
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };
    }
}