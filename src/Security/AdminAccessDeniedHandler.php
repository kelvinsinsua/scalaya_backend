<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AdminAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        // Only handle admin routes
        if (!str_starts_with($request->getPathInfo(), '/admin')) {
            return null;
        }

        // Don't redirect if already on access denied page
        if ($request->getPathInfo() === '/admin/access-denied') {
            return null;
        }

        // Redirect to custom access denied page
        $url = $this->urlGenerator->generate('admin_access_denied');
        return new RedirectResponse($url);
    }
}