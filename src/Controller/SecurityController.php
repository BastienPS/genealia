<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà authentifié, on le renvoie vers la zone qui
        // le concerne : l'admin vers l'espace admin, un client vers le formulaire de demande.
        if ($this->getUser()) {
            return $this->redirectToRoute(
                $this->isGranted('ROLE_ADMIN') ? 'app_admin_requests' : 'app_request_new'
            );
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Interception : cette méthode n'est jamais atteinte, le firewall gère la déconnexion.
        throw new \LogicException('Cette méthode est interceptée par le firewall de sécurité.');
    }
}