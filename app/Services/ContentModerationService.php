<?php

namespace App\Services;

use App\Models\BannedWord;
use App\Models\NiceWord;
use App\Models\ModerationLog;
use App\Models\Loi;
use App\Models\ActeurAN;
use App\Models\Senateur;
use App\Models\Maire;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ContentModerationService
{
    /**
     * Durée du cache en secondes (1 heure)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Domaines whitelistés (liens autorisés)
     * Chargés depuis config/moderation.php
     */
    protected array $whitelistedDomains;

    /**
     * Patterns de références internes
     * Chargés depuis config/moderation.php
     */
    protected array $internalReferencePatterns;

    public function __construct()
    {
        // Charger la configuration
        $this->whitelistedDomains = config('moderation.whitelisted_domains', [
            '*.gouv.fr', 'insee.fr', 'assemblee-nationale.fr', 'senat.fr',
        ]);
        
        // Charger les patterns de références
        $patterns = config('moderation.reference_patterns', []);
        $this->internalReferencePatterns = [];
        foreach ($patterns as $type => $config) {
            $this->internalReferencePatterns[$type] = $config['pattern'];
        }
    }

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

    // =========================================================================
    // GESTION DES LIENS
    // =========================================================================

    /**
     * Sanitize les liens dans le contenu
     * - Supprime les liens vers des domaines non autorisés
     * - Conserve les liens vers les domaines whitelistés
     * - Conserve les références internes (@loi:, @depute:, etc.)
     * 
     * @param string $content Le contenu à nettoyer
     * @return array ['content' => string, 'removed_links' => array, 'kept_links' => array]
     */
    public function sanitizeLinks(string $content): array
    {
        $removedLinks = [];
        $keptLinks = [];
        
        // Pattern pour détecter les URLs
        $urlPattern = '/https?:\/\/[^\s\<\>\"\'\)\]]+/i';
        
        $sanitizedContent = preg_replace_callback($urlPattern, function ($match) use (&$removedLinks, &$keptLinks) {
            $url = $match[0];
            
            // Extraire le domaine
            $parsedUrl = parse_url($url);
            $host = $parsedUrl['host'] ?? '';
            
            if ($this->isDomainWhitelisted($host)) {
                $keptLinks[] = $url;
                return $url; // Conserver le lien
            }
            
            // Lien non autorisé : le remplacer par un message
            $removedLinks[] = $url;
            return '[lien externe supprimé]';
        }, $content);
        
        return [
            'content' => $sanitizedContent,
            'removed_links' => $removedLinks,
            'kept_links' => $keptLinks,
            'links_removed_count' => count($removedLinks),
        ];
    }

    /**
     * Vérifie si un domaine est whitelisté
     */
    public function isDomainWhitelisted(string $host): bool
    {
        $host = strtolower($host);
        
        foreach ($this->whitelistedDomains as $pattern) {
            // Wildcard pattern (*.gouv.fr)
            if (str_starts_with($pattern, '*.')) {
                $suffix = substr($pattern, 1); // .gouv.fr
                if (str_ends_with($host, $suffix) || $host === substr($pattern, 2)) {
                    return true;
                }
            } else {
                // Exact match
                if ($host === strtolower($pattern)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Obtenir la liste des domaines whitelistés
     */
    public function getWhitelistedDomains(): array
    {
        return $this->whitelistedDomains;
    }

    // =========================================================================
    // RÉFÉRENCES INTERNES
    // =========================================================================

    /**
     * Parse et enrichit les références internes dans le contenu
     * Transforme @loi:2024-123 en lien cliquable avec preview
     * 
     * @param string $content Le contenu à parser
     * @return array ['content' => string, 'references' => array]
     */
    public function parseInternalReferences(string $content): array
    {
        $references = [];
        $parsedContent = $content;
        
        foreach ($this->internalReferencePatterns as $type => $pattern) {
            $parsedContent = preg_replace_callback($pattern, function ($match) use ($type, &$references) {
                $fullMatch = $match[0];
                $identifier = $match[1];
                
                // Résoudre la référence
                $resolved = $this->resolveReference($type, $identifier);
                
                if ($resolved) {
                    $references[] = [
                        'type' => $type,
                        'identifier' => $identifier,
                        'label' => $resolved['label'],
                        'url' => $resolved['url'],
                        'exists' => true,
                    ];
                    
                    // Retourner le format HTML enrichi
                    return sprintf(
                        '<a href="%s" class="internal-ref internal-ref-%s" data-type="%s" data-id="%s" title="%s">%s</a>',
                        $resolved['url'],
                        $type,
                        $type,
                        $identifier,
                        htmlspecialchars($resolved['label']),
                        $fullMatch
                    );
                }
                
                // Référence non trouvée : marquer comme invalide
                $references[] = [
                    'type' => $type,
                    'identifier' => $identifier,
                    'exists' => false,
                ];
                
                return sprintf(
                    '<span class="internal-ref internal-ref-invalid" title="Référence non trouvée">%s</span>',
                    $fullMatch
                );
            }, $parsedContent);
        }
        
        return [
            'content' => $parsedContent,
            'references' => $references,
            'valid_count' => count(array_filter($references, fn($r) => $r['exists'])),
            'invalid_count' => count(array_filter($references, fn($r) => !$r['exists'])),
        ];
    }

    /**
     * Extraire les références internes sans les transformer
     * Utile pour la validation ou les notifications
     */
    public function extractReferences(string $content): array
    {
        $references = [];
        
        foreach ($this->internalReferencePatterns as $type => $pattern) {
            preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $identifier = $match[1];
                $resolved = $this->resolveReference($type, $identifier);
                
                $references[] = [
                    'type' => $type,
                    'identifier' => $identifier,
                    'raw' => $match[0],
                    'label' => $resolved['label'] ?? null,
                    'url' => $resolved['url'] ?? null,
                    'exists' => $resolved !== null,
                ];
            }
        }
        
        return $references;
    }

    /**
     * Résoudre une référence interne vers ses données
     */
    protected function resolveReference(string $type, string $identifier): ?array
    {
        return match ($type) {
            'loi' => $this->resolveLoi($identifier),
            'depute' => $this->resolveDepute($identifier),
            'senateur' => $this->resolveSenateur($identifier),
            'maire' => $this->resolveMaire($identifier),
            'scrutin' => $this->resolveScrutin($identifier),
            'amendement' => $this->resolveAmendement($identifier),
            default => null,
        };
    }

    protected function resolveLoi(string $code): ?array
    {
        try {
            $loi = Cache::remember("ref_loi_{$code}", 3600, function () use ($code) {
                return Loi::where('loicod', $code)->first(['loicod', 'loititre']);
            });
            
            if ($loi) {
                return [
                    'label' => $loi->loititre,
                    'url' => route('lois.show', $code),
                ];
            }
        } catch (\Exception $e) {
            Log::debug("Reference loi not found: {$code}");
        }
        
        return null;
    }

    protected function resolveDepute(string $uid): ?array
    {
        try {
            $depute = Cache::remember("ref_depute_{$uid}", 3600, function () use ($uid) {
                return ActeurAN::where('uid', $uid)->first(['uid', 'prenom', 'nom', 'slug']);
            });
            
            if ($depute) {
                return [
                    'label' => $depute->prenom . ' ' . $depute->nom,
                    'url' => route('representants.deputes.show', $depute->slug ?? $uid),
                ];
            }
        } catch (\Exception $e) {
            Log::debug("Reference depute not found: {$uid}");
        }
        
        return null;
    }

    protected function resolveSenateur(string $matricule): ?array
    {
        try {
            $senateur = Cache::remember("ref_senateur_{$matricule}", 3600, function () use ($matricule) {
                return Senateur::where('matricule', $matricule)->first(['matricule', 'prenom', 'nom']);
            });
            
            if ($senateur) {
                return [
                    'label' => $senateur->prenom . ' ' . $senateur->nom,
                    'url' => route('representants.senateurs.show', $matricule),
                ];
            }
        } catch (\Exception $e) {
            Log::debug("Reference senateur not found: {$matricule}");
        }
        
        return null;
    }

    protected function resolveMaire(string $id): ?array
    {
        try {
            $maire = Cache::remember("ref_maire_{$id}", 3600, function () use ($id) {
                return Maire::find($id, ['id', 'prenom', 'nom', 'commune']);
            });
            
            if ($maire) {
                return [
                    'label' => $maire->prenom . ' ' . $maire->nom . ' (' . $maire->commune . ')',
                    'url' => route('representants.maires.show', $id),
                ];
            }
        } catch (\Exception $e) {
            Log::debug("Reference maire not found: {$id}");
        }
        
        return null;
    }

    protected function resolveScrutin(string $numero): ?array
    {
        // Pour les scrutins, on retourne juste l'URL sans vérification
        // Car la table peut avoir différentes structures
        return [
            'label' => "Scrutin #{$numero}",
            'url' => "/scrutins/{$numero}",
        ];
    }

    protected function resolveAmendement(string $numero): ?array
    {
        // Pour les amendements, on retourne juste l'URL
        return [
            'label' => "Amendement #{$numero}",
            'url' => "/amendements/{$numero}",
        ];
    }

    // =========================================================================
    // MODÉRATION COMPLÈTE
    // =========================================================================

    /**
     * Modération complète du contenu :
     * 1. Remplace les mots bannis par des mots gentils
     * 2. Supprime les liens non autorisés
     * 3. Parse les références internes
     * 
     * @param string $content Le contenu à modérer
     * @param int|null $userId L'ID de l'utilisateur
     * @param object|null $model Le modèle associé
     * @param array $options Options de modération
     * @return array Résultat complet de la modération
     */
    public function fullModerate(
        string $content,
        ?int $userId = null,
        ?object $model = null,
        array $options = []
    ): array {
        $result = [
            'original' => $content,
            'content' => $content,
            'blocked' => false,
            'modifications' => [],
        ];
        
        // 1. Modération des mots bannis
        if ($options['moderate_words'] ?? true) {
            $wordResult = $this->moderate($content, $userId, $model);
            $result['content'] = $wordResult['content'];
            $result['blocked'] = $wordResult['blocked'];
            $result['word_replacements'] = $wordResult['replacements'];
            $result['word_details'] = $wordResult['details'];
            
            if ($wordResult['modified']) {
                $result['modifications'][] = 'words';
            }
        }
        
        // Si bloqué, on arrête là
        if ($result['blocked']) {
            return $result;
        }
        
        // 2. Sanitization des liens
        if ($options['sanitize_links'] ?? true) {
            $linkResult = $this->sanitizeLinks($result['content']);
            $result['content'] = $linkResult['content'];
            $result['removed_links'] = $linkResult['removed_links'];
            $result['kept_links'] = $linkResult['kept_links'];
            
            if (!empty($linkResult['removed_links'])) {
                $result['modifications'][] = 'links';
            }
        }
        
        // 3. Parsing des références internes (optionnel, pour l'affichage)
        if ($options['parse_references'] ?? false) {
            $refResult = $this->parseInternalReferences($result['content']);
            $result['content_html'] = $refResult['content'];
            $result['references'] = $refResult['references'];
            
            if (!empty($refResult['references'])) {
                $result['modifications'][] = 'references';
            }
        }
        
        $result['modified'] = !empty($result['modifications']);
        
        return $result;
    }

    /**
     * Valider le contenu sans le modifier
     * Retourne les problèmes détectés
     */
    public function validate(string $content): array
    {
        $issues = [];
        
        // Vérifier les mots bannis
        $wordCheck = $this->check($content);
        if (!$wordCheck['clean']) {
            $issues['banned_words'] = $wordCheck['found'];
        }
        
        // Vérifier les liens non autorisés
        $linkResult = $this->sanitizeLinks($content);
        if (!empty($linkResult['removed_links'])) {
            $issues['external_links'] = $linkResult['removed_links'];
        }
        
        // Extraire les références pour vérification
        $references = $this->extractReferences($content);
        $invalidRefs = array_filter($references, fn($r) => !$r['exists']);
        if (!empty($invalidRefs)) {
            $issues['invalid_references'] = $invalidRefs;
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'summary' => [
                'banned_words' => count($issues['banned_words'] ?? []),
                'external_links' => count($issues['external_links'] ?? []),
                'invalid_references' => count($issues['invalid_references'] ?? []),
            ],
        ];
    }
}
