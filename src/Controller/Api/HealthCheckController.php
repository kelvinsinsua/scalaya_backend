<?php

namespace App\Controller\Api;

use App\ApiResource\HealthCheck;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class HealthCheckController extends AbstractController
{
    public function __invoke(): HealthCheck
    {
        return new HealthCheck();
    }
}