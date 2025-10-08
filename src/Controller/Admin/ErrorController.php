<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
class ErrorController extends AbstractController
{
    #[Route('/access-denied', name: 'admin_access_denied')]
    #[IsGranted('ROLE_USER')]
    public function accessDenied(): Response
    {
        $user = $this->getUser();
        $userRoles = $user ? $user->getRoles() : [];
        
        return $this->render('admin/error/access_denied.html.twig', [
            'user_roles' => $userRoles,
        ], new Response('', Response::HTTP_FORBIDDEN));
    }
}