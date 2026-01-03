<?php

namespace App\Services;

use App\Models\BannedWord;
use App\Models\NiceWord;
use App\Models\ModerationLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ContentModerationService
{
    /**
     * Durée du cache en secondes (1 heure)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Mots gentils par défaut si la table est vide
     */
    protected array $defaultNiceWords = [
        // Compliments
        'magnifique', 'merveilleux', 'formidable', 'extraordinaire', 'fantastique',
        'incroyable', 'superbe', 'génial', 'brillant', 'admirable',
        
        // Emojis
        '🌸', '🌈', '✨', '💖', '🦋', '🌻', '🍀', '🎀', '🌟', '💫',
        '🐱', '🐶', '🐰', '🦊', '🐼', '🦄', '🌺', '🍭', '🧁', '🎈',
        
        // Nature
        'petit nuage', 'arc-en-ciel', 'papillon', 'fleur de lotus', 'rayon de soleil',
        'brise légère', 'étoile filante', 'pétale de rose',
        
        // Animaux mignons
        'petit chaton', 'bébé panda', 'petit lapin', 'licorne', 'petit koala',
        'bébé phoque', 'petit renard', 'hamster câlin',
        
        // Nourriture
        'cupcake', 'macaron', 'bonbon', 'guimauve', 'barbe à papa',
        
        // Drôle
        'patate douce', 'chou-fleur de l\'amour', 'cornichon câlin', 'nouille joyeuse',
        'petite crêpe', 'champion du monde des bisous', 'professeur de câlins',
    ];

    /**
     * Mots bannis par défaut (insultes françaises courantes)
     */
    protected array $defaultBannedWords = [
        // Insultes générales
        ['word' => 'merde', 'category' => 'insulte', 'severity' => 'low'],
        ['word' => 'putain', 'category' => 'insulte', 'severity' => 'low'],
        ['word' => 'bordel', 'category' => 'insulte', 'severity' => 'low'],
        ['word' => 'con', 'category' => 'insulte', 'severity' => 'medium'],
        ['word' => 'connard', 'category' => 'insulte', 'severity' => 'medium'],
        ['word' => 'connasse', 'category' => 'insulte', 'severity' => 'medium'],
        ['word' => 'salaud', 'category' => 'insulte', 'severity' => 'medium'],
        ['word' => 'salope', 'category' => 'insulte', 'severity' => 'high'],
        ['word' => 'enculé', 'category' => 'insulte', 'severity' => 'high'],
        ['word' => 'nique', 'category' => 'insulte', 'severity' => 'high'],
        ['word' => 'ntm', 'category' => 'insulte', 'severity' => 'high'],
        ['word' => 'fdp', 'category' => 'insulte', 'severity' => 'high'],
        ['word' => 'batard', 'category' => 'insulte', 'severity' => 'medium'],
        ['word' => 'crétin', 'category' => 'insulte', 'severity' => 'low'],
        ['word' => 'débile', 'category' => 'insulte', 'severity' => 'low'],
        ['word' => 'idiot', 'category' => 'insulte', 'severity' => 'low'],
        ['word' => 'imbécile', 'category' => 'insulte', 'severity' => 'low'],
        ['word' => 'abruti', 'category' => 'insulte', 'severity' => 'low'],
        ['word' => 'bouffon', 'category' => 'insulte', 'severity' => 'low'],
        ['word' => 'pétasse', 'category' => 'insulte', 'severity' => 'medium'],
        ['word' => 'pouffiasse', 'category' => 'insulte', 'severity' => 'medium'],
        ['word' => 'fils de pute', 'category' => 'insulte', 'severity' => 'high'],
        ['word' => 'ta gueule', 'category' => 'insulte', 'severity' => 'medium'],
        ['word' => 'ferme ta gueule', 'category' => 'insulte', 'severity' => 'medium'],
        
        // Racisme (à bloquer, pas remplacer)
        ['word' => 'nègre', 'category' => 'racisme', 'severity' => 'high'],
        ['word' => 'négro', 'category' => 'racisme', 'severity' => 'high'],
        ['word' => 'bougnoule', 'category' => 'racisme', 'severity' => 'high'],
        ['word' => 'youpin', 'category' => 'racisme', 'severity' => 'high'],
        ['word' => 'feuj', 'category' => 'racisme', 'severity' => 'high'],
        ['word' => 'bicot', 'category' => 'racisme', 'severity' => 'high'],
        ['word' => 'chinetoque', 'category' => 'racisme', 'severity' => 'high'],
        
        // Violence
        ['word' => 'je vais te tuer', 'category' => 'violence', 'severity' => 'high'],
        ['word' => 'je te tue', 'category' => 'violence', 'severity' => 'high'],
        ['word' => 'crève', 'category' => 'violence', 'severity' => 'medium'],
        ['word' => 'va mourir', 'category' => 'violence', 'severity' => 'high'],
    ];

    /**
     * Modérer un contenu (texte)
     * 
     * @param string $content Le contenu à modérer
     * @param int|null $userId L'ID de l'utilisateur
     * @param object|null $model Le modèle associé (Topic, Post, etc.)
     * @return array ['content' => string, 'replacements' => int, 'blocked' => bool, 'details' => array]
     */
    public function moderate(string $content, ?int $userId = null, ?object $model = null): array
    {
        $bannedWords = $this->getBannedWords();
        $replacementCount = 0;
        $details = [];
        $blocked = false;
        $moderatedContent = $content;

        foreach ($bannedWords as $bannedWord) {
            $pattern = $bannedWord->pattern;
            
            // Compter les occurrences
            preg_match_all($pattern, $moderatedContent, $matches);
            $occurrences = count($matches[0]);
            
            if ($occurrences > 0) {
                // Si c'est du racisme ou violence grave, on bloque
                if (in_array($bannedWord->category, ['racisme']) && $bannedWord->severity === 'high') {
                    $blocked = true;
                    $details[] = [
                        'word' => $bannedWord->word,
                        'category' => $bannedWord->category,
                        'action' => 'blocked',
                    ];
                    continue;
                }
                
                // Sinon, on remplace par un mot gentil
                $moderatedContent = preg_replace_callback(
                    $pattern,
                    function ($match) use ($bannedWord, &$replacementCount, &$details, $userId, $model) {
                        $niceWord = $this->getRandomNiceWord();
                        $replacementCount++;
                        
                        $details[] = [
                            'original' => $match[0],
                            'replacement' => $niceWord,
                            'category' => $bannedWord->category,
                        ];
                        
                        // Logger le remplacement
                        $this->logReplacement($match[0], $niceWord, $bannedWord, $userId, $model);
                        
                        return $niceWord;
                    },
                    $moderatedContent
                );
            }
        }

        return [
            'content' => $moderatedContent,
            'original' => $content,
            'replacements' => $replacementCount,
            'blocked' => $blocked,
            'details' => $details,
            'modified' => $content !== $moderatedContent,
        ];
    }

    /**
     * Vérifier si un contenu contient des mots bannis (sans modifier)
     */
    public function check(string $content): array
    {
        $bannedWords = $this->getBannedWords();
        $found = [];

        foreach ($bannedWords as $bannedWord) {
            if (preg_match($bannedWord->pattern, $content)) {
                $found[] = [
                    'word' => $bannedWord->word,
                    'category' => $bannedWord->category,
                    'severity' => $bannedWord->severity,
                ];
            }
        }

        return [
            'clean' => empty($found),
            'found' => $found,
            'count' => count($found),
        ];
    }

    /**
     * Récupérer les mots bannis (avec cache)
     */
    protected function getBannedWords()
    {
        return Cache::remember('banned_words', self::CACHE_TTL, function () {
            return BannedWord::active()->get();
        });
    }

    /**
     * Récupérer un mot gentil aléatoire
     */
    protected function getRandomNiceWord(): string
    {
        // Essayer d'abord la BDD
        $niceWords = Cache::remember('nice_words', self::CACHE_TTL, function () {
            return NiceWord::active()->pluck('word')->toArray();
        });

        if (!empty($niceWords)) {
            return $niceWords[array_rand($niceWords)];
        }

        // Fallback sur les mots par défaut
        return $this->defaultNiceWords[array_rand($this->defaultNiceWords)];
    }

    /**
     * Logger un remplacement
     */
    protected function logReplacement(
        string $original,
        string $replacement,
        BannedWord $bannedWord,
        ?int $userId,
        ?object $model
    ): void {
        try {
            ModerationLog::create([
                'moderatable_type' => $model ? get_class($model) : null,
                'moderatable_id' => $model?->id,
                'user_id' => $userId,
                'action' => 'word_replaced',
                'original_word' => $original,
                'replacement_word' => $replacement,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log moderation action', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Vider le cache des mots
     */
    public function clearCache(): void
    {
        Cache::forget('banned_words');
        Cache::forget('nice_words');
    }

    /**
     * Seeder les mots par défaut
     */
    public function seedDefaultWords(): array
    {
        $bannedCount = 0;
        $niceCount = 0;

        // Ajouter les mots bannis
        foreach ($this->defaultBannedWords as $word) {
            BannedWord::firstOrCreate(
                ['word' => $word['word']],
                [
                    'category' => $word['category'],
                    'severity' => $word['severity'],
                    'is_active' => true,
                ]
            );
            $bannedCount++;
        }

        // Ajouter les mots gentils
        foreach ($this->defaultNiceWords as $word) {
            $category = $this->guessNiceWordCategory($word);
            NiceWord::firstOrCreate(
                ['word' => $word],
                [
                    'category' => $category,
                    'is_active' => true,
                ]
            );
            $niceCount++;
        }

        $this->clearCache();

        return [
            'banned' => $bannedCount,
            'nice' => $niceCount,
        ];
    }

    /**
     * Deviner la catégorie d'un mot gentil
     */
    protected function guessNiceWordCategory(string $word): string
    {
        // Emojis
        if (preg_match('/[\x{1F300}-\x{1F9FF}]/u', $word)) {
            return 'emoji';
        }
        
        // Animaux
        if (preg_match('/chaton|panda|lapin|licorne|koala|phoque|renard|hamster/i', $word)) {
            return 'animal';
        }
        
        // Nature
        if (preg_match('/nuage|arc-en-ciel|papillon|fleur|soleil|brise|étoile|rose/i', $word)) {
            return 'nature';
        }
        
        // Nourriture
        if (preg_match('/cupcake|macaron|bonbon|guimauve|barbe à papa|crêpe/i', $word)) {
            return 'nourriture';
        }
        
        // Drôle
        if (preg_match('/patate|chou-fleur|cornichon|nouille|champion|professeur/i', $word)) {
            return 'drole';
        }
        
        return 'compliment';
    }

    /**
     * Obtenir les statistiques de modération
     */
    public function getStats(): array
    {
        return [
            'total_banned_words' => BannedWord::active()->count(),
            'total_nice_words' => NiceWord::active()->count(),
            'replacements_today' => ModerationLog::replacements()->today()->count(),
            'replacements_week' => ModerationLog::replacements()->thisWeek()->count(),
            'top_replaced' => ModerationLog::replacements()
                ->selectRaw('original_word, COUNT(*) as count')
                ->groupBy('original_word')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            'by_category' => BannedWord::active()
                ->selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->get()
                ->pluck('count', 'category'),
        ];
    }
}
