<?php

namespace App\EventListener;

use App\Exception\AuthenticationException;
use App\Exception\BusinessLogicException;
use App\Exception\ValidationException;
use App\Service\ErrorResponseService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException as SecurityAuthenticationException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ExceptionListener
{
    public function __construct(
        private ErrorResponseService $errorResponseService,
        private LoggerInterface $logger,
        private string $environment
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Only handle API requests (JSON responses)
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $response = $this->createErrorResponse($exception);
        
        if ($response) {
            $event->setResponse($response);
        }
    }

    private function createErrorResponse(\Throwable $exception): ?JsonResponse
    {
        // Log the exception
        $this->logException($exception);

        return match (true) {
            $exception instanceof ValidationException => $this->handleValidationException($exception),
            $exception instanceof AuthenticationException => $this->handleAuthenticationException($exception),
            $exception instanceof BusinessLogicException => $this->handleBusinessLogicException($exception),
            $exception instanceof SecurityAuthenticationException => $this->handleSecurityAuthenticationException($exception),
            $exception instanceof AccessDeniedException, $exception instanceof AccessDeniedHttpException => $this->handleAccessDeniedException($exception),
            $exception instanceof NotFoundHttpException => $this->handleNotFoundException($exception),
            $exception instanceof HttpException => $this->handleHttpException($exception),
            default => $this->handleGenericException($exception),
        };
    }

    private function handleValidationException(ValidationException $exception): JsonResponse
    {
        return $this->errorResponseService->createValidationErrorResponse(
            $exception->getViolations(),
            $exception->getMessage()
        );
    }

    private function handleAuthenticationException(AuthenticationException $exception): JsonResponse
    {
        return $this->errorResponseService->createErrorResponse(
            $exception->getErrorCode(),
            $exception->getMessage(),
            [],
            $exception->getCode()
        );
    }

    private function handleBusinessLogicException(BusinessLogicException $exception): JsonResponse
    {
        return $this->errorResponseService->createErrorResponse(
            $exception->getErrorCode(),
            $exception->getMessage(),
            [],
            $exception->getCode()
        );
    }

    private function handleSecurityAuthenticationException(SecurityAuthenticationException $exception): JsonResponse
    {
        return $this->errorResponseService->createErrorResponse(
            'AUTHENTICATION_REQUIRED',
            'Authentication required',
            [],
            Response::HTTP_UNAUTHORIZED
        );
    }

    private function handleAccessDeniedException(\Throwable $exception): JsonResponse
    {
        return $this->errorResponseService->createErrorResponse(
            'ACCESS_DENIED',
            'Access denied',
            [],
            Response::HTTP_FORBIDDEN
        );
    }

    private function handleNotFoundException(NotFoundHttpException $exception): JsonResponse
    {
        return $this->errorResponseService->createErrorResponse(
            'RESOURCE_NOT_FOUND',
            'Resource not found',
            [],
            Response::HTTP_NOT_FOUND
        );
    }

    private function handleHttpException(HttpException $exception): JsonResponse
    {
        $errorCode = match ($exception->getStatusCode()) {
            400 => 'INVALID_REQUEST',
            401 => 'AUTHENTICATION_REQUIRED',
            403 => 'ACCESS_DENIED',
            404 => 'RESOURCE_NOT_FOUND',
            409 => 'RESOURCE_CONFLICT',
            422 => 'VALIDATION_ERROR',
            429 => 'RATE_LIMIT_EXCEEDED',
            default => 'HTTP_ERROR',
        };

        return $this->errorResponseService->createErrorResponse(
            $errorCode,
            $exception->getMessage() ?: 'HTTP error occurred',
            [],
            $exception->getStatusCode()
        );
    }

    private function handleGenericException(\Throwable $exception): JsonResponse
    {
        $message = $this->environment === 'prod' 
            ? 'An unexpected error occurred' 
            : $exception->getMessage();

        $details = $this->environment === 'prod' 
            ? [] 
            : [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];

        return $this->errorResponseService->createErrorResponse(
            'INTERNAL_ERROR',
            $message,
            $details,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    private function logException(\Throwable $exception): void
    {
        $context = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        if ($exception instanceof ValidationException) {
            $this->logger->info('Validation error occurred', $context);
        } elseif ($exception instanceof AuthenticationException || $exception instanceof SecurityAuthenticationException) {
            $this->logger->warning('Authentication error occurred', $context);
        } elseif ($exception instanceof BusinessLogicException) {
            $this->logger->notice('Business logic error occurred', $context);
        } else {
            $this->logger->error('Unexpected error occurred', $context);
        }
    }
}