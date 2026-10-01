<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('pages/home.html.twig');
    }

    #[Route('/spectacles', name: 'spectacles')]
    public function spectacles(): Response
    {
        return $this->render('pages/spectacles.html.twig');
    }

    #[Route('/nova', name: 'nova')]
    public function nova(): Response
    {
        return $this->render('pages/nova.html.twig');
    }

    #[Route('/nova-2025-2026', name: 'nova_2025_2026')]
    public function nova20252026(): Response
    {
        return $this->render('pages/nova-2025-2026.html.twig');
    }

    #[Route('/nova-2024-2025', name: 'nova_2024_2025')]
    public function nova20242025(): Response
    {
        return $this->render('pages/nova-2024-2025.html.twig');
    }

    #[Route('/contacts', name: 'contacts')]
    public function contacts(): Response
    {
        return $this->render('pages/contacts.html.twig');
    }

    #[Route('/mentions-legales', name: 'mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('pages/mentions-legales.html.twig');
    }

    #[Route('/politique-de-confidentialite', name: 'politique_confidentialite')]
    public function politiqueConfidentialite(): Response
    {
        return $this->render('pages/politique-de-confidentialite.html.twig');
    }

    #[Route('/politique-des-cookies', name: 'politique_cookies')]
    public function politiqueCookies(): Response
    {
        return $this->render('pages/politique-des-cookies.html.twig');
    }

    #[Route('/cgu', name: 'cgu')]
    public function cgu(): Response
    {
        return $this->render('pages/cgu.html.twig');
    }

    #[Route('/newsletter', name: 'newsletter')]
    public function newsletter(): Response
    {
        return $this->render('pages/newsletter.html.twig');
    }

    #[Route('/newsletter-pending', name: 'newsletter_pending')]
    public function newsletterPending(): Response
    {
        return $this->render('pages/newsletter-pending.html.twig');
    }

    #[Route('/newsletter-confirmation', name: 'newsletter_confirmation')]
    public function newsletterConfirmation(): Response
    {
        return $this->render('pages/newsletter-confirmation.html.twig');
    }
}
