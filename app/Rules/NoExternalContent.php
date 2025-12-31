<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Règle de validation pour empêcher les liens externes et images
 * dans les contenus restreints (discussions)
 */
class NoExternalContent implements ValidationRule
{
    /**
     * Patterns à bloquer
     */
    protected array $blockedPatterns = [
        // URLs externes (http/https)
        '/https?:\/\/(?!demo\.objectif2027\.fr|objectif2027\.fr|localhost)[^\s<>\[\]]+/i',
        // Images markdown
        '/!\[.*?\]\(.*?\)/i',
        // Images HTML
        '/<img[^>]*>/i',
        // Liens HTML avec href externe
        '/<a[^>]*href\s*=\s*["\']https?:\/\/(?!demo\.objectif2027\.fr|objectif2027\.fr|localhost)[^"\']*["\'][^>]*>/i',
        // iframes
        '/<iframe[^>]*>/i',
        // Embeds
        '/<embed[^>]*>/i',
        '/<object[^>]*>/i',
        // Base64 images
        '/data:image\/[^;]+;base64,/i',
    ];

    /**
     * Domaines internes autorisés
     */
    protected array $allowedDomains = [
        'demo.objectif2027.fr',
        'objectif2027.fr',
        'localhost',
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        // Vérifier les patterns bloqués
        foreach ($this->blockedPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $fail($this->getMessage($pattern));
                return;
            }
        }

        // Vérifier les URLs génériques (attraper tout ce qui ressemble à une URL externe)
        if (preg_match_all('/https?:\/\/([^\s<>\[\]\/]+)/i', $value, $matches)) {
            foreach ($matches[1] as $domain) {
                $domain = strtolower(preg_replace('/:\d+$/', '', $domain)); // Enlever le port
                if (!$this->isDomainAllowed($domain)) {
                    $fail('Les liens externes ne sont pas autorisés dans les discussions. Utilisez uniquement des liens vers le site.');
                    return;
                }
            }
        }
    }

    /**
     * Vérifie si un domaine est autorisé
     */
    protected function isDomainAllowed(string $domain): bool
    {
        foreach ($this->allowedDomains as $allowed) {
            if ($domain === $allowed || str_ends_with($domain, '.' . $allowed)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Message d'erreur selon le pattern
     */
    protected function getMessage(string $pattern): string
    {
        if (str_contains($pattern, 'img') || str_contains($pattern, 'image') || str_contains($pattern, '!\[')) {
            return 'Les images ne sont pas autorisées dans les discussions.';
        }
        if (str_contains($pattern, 'iframe') || str_contains($pattern, 'embed') || str_contains($pattern, 'object')) {
            return 'Les contenus embarqués (vidéos, iframes) ne sont pas autorisés dans les discussions.';
        }
        return 'Les liens externes ne sont pas autorisés dans les discussions. Utilisez uniquement des liens vers le site.';
    }
}
