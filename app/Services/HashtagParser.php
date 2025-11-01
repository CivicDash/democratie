<?php

namespace App\Services;

/**
 * Service d'extraction et parsing de hashtags
 * 
 * Extrait les #hashtags depuis du texte (Markdown, plain text)
 * Style Twitter : #Climat, #Réforme2025, #SantéPublique
 */
class HashtagParser
{
    /**
     * Regex pour matcher les hashtags
     * 
     * Règles :
     * - Commence par #
     * - Lettres, chiffres, tirets, underscores, accents
     * - Minimum 2 caractères
     * - Maximum 50 caractères
     * - Pas de hashtags successifs (##)
     */
    protected const HASHTAG_PATTERN = '/#([a-zA-Z0-9À-ÿ_\-]{2,50})(?!\w)/u';

    /**
     * Limite max de hashtags par contenu (éviter spam)
     */
    protected const MAX_HASHTAGS = 10;

    /**
     * Extrait les hashtags depuis un texte
     * 
     * @param string $content Contenu avec potentiels #hashtags
     * @return array Tableau de hashtags uniques (sans #)
     */
    public static function extract(string $content): array
    {
        if (empty($content)) {
            return [];
        }

        // Matcher tous les hashtags
        preg_match_all(self::HASHTAG_PATTERN, $content, $matches);

        if (empty($matches[1])) {
            return [];
        }

        // Récupérer uniquement les hashtags (groupe 1 = sans #)
        $hashtags = $matches[1];

        // Dédupliquer (case-insensitive)
        $unique = [];
        $seen = [];

        foreach ($hashtags as $hashtag) {
            $lower = mb_strtolower($hashtag);
            
            if (!in_array($lower, $seen)) {
                $unique[] = $hashtag; // Conserver case original
                $seen[] = $lower;
            }
        }

        // Limiter nombre
        return array_slice($unique, 0, self::MAX_HASHTAGS);
    }

    /**
     * Remplace les hashtags par des liens cliquables (HTML)
     * 
     * @param string $content Contenu avec #hashtags
     * @param string $routeName Route Laravel pour les liens (ex: 'hashtag.show')
     * @return string HTML avec liens
     */
    public static function linkify(string $content, string $routeName = 'hashtag.show'): string
    {
        return preg_replace_callback(
            self::HASHTAG_PATTERN,
            function ($matches) use ($routeName) {
                $hashtag = $matches[1];
                $slug = \App\Models\Hashtag::normalize($hashtag);
                $url = route($routeName, ['slug' => $slug]);
                
                return '<a href="' . $url . '" class="hashtag-link">#' . htmlspecialchars($hashtag) . '</a>';
            },
            $content
        );
    }

    /**
     * Compte les hashtags dans un texte
     */
    public static function count(string $content): int
    {
        return count(self::extract($content));
    }

    /**
     * Vérifie si un texte contient des hashtags
     */
    public static function contains(string $content): bool
    {
        return self::count($content) > 0;
    }

    /**
     * Valide un hashtag individuel
     * 
     * @param string $hashtag Hashtag avec ou sans #
     * @return bool
     */
    public static function isValid(string $hashtag): bool
    {
        // Retirer # si présent
        $hashtag = ltrim($hashtag, '#');

        // Vérifier longueur
        if (strlen($hashtag) < 2 || strlen($hashtag) > 50) {
            return false;
        }

        // Vérifier caractères autorisés
        return preg_match('/^[a-zA-Z0-9À-ÿ_\-]+$/u', $hashtag) === 1;
    }

    /**
     * Filtre les hashtags interdits ou modérés
     * 
     * @param array $hashtags
     * @return array Hashtags filtrés
     */
    public static function filter(array $hashtags): array
    {
        // Liste noire (à adapter selon modération)
        $blacklist = [
            'spam',
            'adult',
            'nsfw',
            // Ajouter mots sensibles selon contexte
        ];

        return array_filter($hashtags, function ($hashtag) use ($blacklist) {
            $slug = \App\Models\Hashtag::normalize($hashtag);
            return !in_array($slug, $blacklist);
        });
    }

    /**
     * Suggère des hashtags depuis un texte (NLP simple)
     * 
     * Extrait les mots importants qui pourraient être des hashtags
     * (sans # dans le texte original)
     * 
     * @param string $content
     * @return array Suggestions de hashtags
     */
    public static function suggest(string $content): array
    {
        // Extraire mots de 4+ lettres (heuristique simple)
        preg_match_all('/\b([A-ZÀ-Ÿ][a-zà-ÿ]{3,})\b/u', $content, $matches);

        $suggestions = [];
        
        foreach ($matches[1] ?? [] as $word) {
            if (self::isValid($word)) {
                $suggestions[] = $word;
            }
        }

        return array_slice(array_unique($suggestions), 0, 5);
    }
}

