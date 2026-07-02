<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class ConnectController extends AbstractController
{
    public function __construct(private readonly ClientRegistry $clientRegistry)
    {
    }

    /**
     * Kick off the Google OAuth dance: redirect the user to Google's consent screen.
     */
    #[Route('/connect/google', name: 'connect_google')]
    public function connectGoogle(): RedirectResponse
    {
        return $this->clientRegistry->getClient('google')->redirect(['email', 'profile'], []);
    }

    /**
     * Callback endpoint. Intercepted entirely by GoogleAuthenticator — this stub
     * only exists so the bundle can resolve the redirect_route to a URL.
     */
    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(): void
    {
    }

    #[Route('/connect/facebook', name: 'connect_facebook')]
    public function connectFacebook(): RedirectResponse
    {
        return $this->clientRegistry->getClient('facebook')->redirect(['email', 'public_profile'], []);
    }

    #[Route('/connect/facebook/check', name: 'connect_facebook_check')]
    public function connectFacebookCheck(): void
    {
    }
}