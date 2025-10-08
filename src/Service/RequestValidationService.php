<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestValidationService
{
    public function __construct(
        private ValidatorInterface $validator,
        private ErrorResponseService $errorResponseService
    ) {
    }

    /**
     * Validate JSON request and create DTO
     */
    public function validateJsonRequest(Request $request, string $dtoClass): array
    {
        // Parse JSON
        $data = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'response' => $this->errorResponseService->createErrorResponse(
                    'INVALID_JSON',
                    'Invalid JSON data: ' . json_last_error_msg()
                )
            ];
        }

        if (!is_array($data)) {
            return [
                'success' => false,
                'response' => $this->errorResponseService->createErrorResponse('INVALID_JSON')
            ];
        }

        // Create DTO
        try {
            $dto = $dtoClass::fromArray($data);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'response' => $this->errorResponseService->createErrorResponse(
                    'INVALID_FORMAT',
                    'Failed to parse request data: ' . $e->getMessage()
                )
            ];
        }

        // Validate DTO
        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            return [
                'success' => false,
                'response' => $this->errorResponseService->createValidationErrorResponse($violations)
            ];
        }

        return [
            'success' => true,
            'dto' => $dto,
            'data' => $data
        ];
    }

    /**
     * Validate array data against constraints
     */
    public function validateArrayData(array $data, array $constraints): ?JsonResponse
    {
        $violations = $this->validator->validate($data, $constraints);

        if (count($violations) > 0) {
            return $this->errorResponseService->createValidationErrorResponse($violations);
        }

        return null;
    }

    /**
     * Check if request content type is JSON
     */
    public function isJsonRequest(Request $request): bool
    {
        return str_contains($request->headers->get('Content-Type', ''), 'application/json');
    }

    /**
     * Validate that request is JSON
     */
    public function requireJsonRequest(Request $request): ?JsonResponse
    {
        if (!$this->isJsonRequest($request)) {
            return $this->errorResponseService->createErrorResponse(
                'INVALID_FORMAT',
                'Content-Type must be application/json',
                [],
                Response::HTTP_UNSUPPORTED_MEDIA_TYPE
            );
        }

        return null;
    }
}