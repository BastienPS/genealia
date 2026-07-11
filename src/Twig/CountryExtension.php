<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\Intl\Countries;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Filtre Twig `country_name` : convertit un code pays ISO 2 lettres (FR, IT, …)
 * en son nom en français (« France », « Italie »). Renvoie une chaîne vide pour
 * un code null/vide, et le code brut si l'Intl ne le connaît pas.
 */
final class CountryExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('country_name', [$this, 'countryName']),
        ];
    }

    public function countryName(?string $code): string
    {
        $code = trim((string) $code);

        if ($code === '') {
            return '';
        }

        try {
            return Countries::getName($code, 'fr');
        } catch (\Throwable) {
            return $code;
        }
    }
}