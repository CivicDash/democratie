<?php

namespace App\Support;

/**
 * Ce qu'est une URL de source publiable, en un seul endroit.
 *
 * La règle existait en trois copies — `PresidentielleImportArguments::cleanUrl()`,
 * `PresidentielleExporter::url()`, `IntegriteChecker::estUrlValide()` — et une quatrième
 * l'ignorait : `Argument::aSourceFiable()` ne regardait que la fiabilité. Un argument dont
 * la seule source fiable portait `A_COMPLETER` était donc publiable dans le back-office,
 * puis faisait refuser l'export, ce qui fige objectif2027.fr.
 */
final class UrlSource
{
    /** Absolue, en http(s), et sans placeholder. */
    public static function estValide(mixed $url): bool
    {
        return is_string($url)
            && trim($url) !== ''
            && ! str_contains($url, 'A_COMPLETER')
            && (bool) preg_match('#^https?://#i', trim($url));
    }

    /** L'URL si elle est publiable, sinon null. */
    public static function nettoyer(mixed $url): ?string
    {
        return self::estValide($url) ? trim($url) : null;
    }
}
