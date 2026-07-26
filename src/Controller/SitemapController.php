<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Génère /sitemap.xml pour les moteurs de recherche.
 *
 * Ne liste que les pages publiques indexables. Les routes derrière auth
 * (/login, /request/new, /espace, /admin) sont exclues et bloquées par
 * robots.txt + meta robots noindex.
 */
class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'app_sitemap', defaults: ['_format' => 'xml'])]
    public function index(): Response
    {
        $urls = [
            ['route' => 'app_home',               'priority' => '1.0', 'changefreq' => 'monthly'],
            ['route' => 'app_legal_faq',         'priority' => '0.8', 'changefreq' => 'monthly'],
            ['route' => 'app_legal_apropos',     'priority' => '0.6', 'changefreq' => 'yearly'],
            ['route' => 'app_legal_mentions',    'priority' => '0.3', 'changefreq' => 'yearly'],
            ['route' => 'app_legal_confidentialite', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        $today = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d');

        $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $loc = $this->generateUrl($url['route'], referenceType: UrlGeneratorInterface::ABSOLUTE_URL);
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>';
            $lines[] = '    <lastmod>' . $today . '</lastmod>';
            $lines[] = '    <changefreq>' . $url['changefreq'] . '</changefreq>';
            $lines[] = '    <priority>' . $url['priority'] . '</priority>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return new Response(implode("\n", $lines), Response::HTTP_OK, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}