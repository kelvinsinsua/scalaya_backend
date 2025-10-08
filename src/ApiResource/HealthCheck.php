<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Api\HealthCheckController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/health',
            controller: HealthCheckController::class,
            read: false
        )
    ],
    security: "true"
)]
class HealthCheck
{
    public string $status = 'ok';
    public string $message = 'API Platform is working correctly';
    public string $timestamp;
    
    public function __construct()
    {
        $this->timestamp = date('c');
    }
}