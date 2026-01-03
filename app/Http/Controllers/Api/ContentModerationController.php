<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ContentModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentModerationController extends Controller
{
    protected ContentModerationService $moderationService;

    public function __construct(ContentModerationService $moderationService)
    {
        $this->moderationService = $moderationService;
    }

    /**
     * Retourne la liste des domaines whitelistés
     * GET /api/content-moderation/whitelisted-domains
     */
    public function whitelistedDomains(): JsonResponse
    {
        $domains = $this->moderationService->getWhitelistedDomains();
        
        // Grouper par catégorie pour une meilleure lisibilité
        $grouped = [
            'gouvernement' => array_filter($domains, fn($d) => str_contains($d, 'gouv.fr') || in_array($d, ['assemblee-nationale.fr', 'senat.fr', 'elysee.fr'])),
            'statistiques' => array_filter($domains, fn($d) => str_contains($d, 'insee.fr')),
            'europe' => array_filter($domains, fn($d) => str_contains($d, 'europa.eu')),
            'institutions' => array_filter($domains, fn($d) => in_array($d, ['conseil-constitutionnel.fr', 'conseil-etat.fr', 'ccomptes.fr', 'hatvp.fr'])),
            'interne' => array_filter($domains, fn($d) => str_contains($d, 'civicdash.fr') || $d === 'localhost'),
        ];

        return response()->json([
            'success' => true,
            'domains' => $domains,
            'grouped' => $grouped,
            'count' => count($domains),
            'message' => 'Seuls les liens vers ces domaines sont autorisés dans les contributions.',
        ]);
    }

    /**
     * Retourne les formats de références internes supportés
     * GET /api/content-moderation/reference-formats
     */
    public function referenceFormats(): JsonResponse
    {
        $patterns = config('moderation.reference_patterns', []);
        
        $formats = [];
        foreach ($patterns as $type => $config) {
            $formats[$type] = [
                'description' => $config['description'],
                'example' => $config['example'],
                'syntax' => "@{$type}:<identifiant>",
            ];
        }

        return response()->json([
            'success' => true,
            'formats' => $formats,
            'usage' => [
                'description' => 'Utilisez ces formats pour créer des liens automatiques vers les ressources CivicDash.',
                'example' => 'La @loi:2024-123 a été adoptée grâce au travail de @depute:PA123456.',
            ],
        ]);
    }

    /**
     * Valide un contenu sans le modifier
     * POST /api/content-moderation/validate
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'max:50000'],
        ]);

        $content = $request->input('content');
        $validation = $this->moderationService->validate($content);

        return response()->json([
            'success' => true,
            'valid' => $validation['valid'],
            'issues' => $validation['issues'],
            'summary' => $validation['summary'],
            'recommendations' => $this->getRecommendations($validation),
        ]);
    }

    /**
     * Preview du contenu après modération
     * POST /api/content-moderation/preview
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'max:50000'],
        ]);

        $content = $request->input('content');
        
        $result = $this->moderationService->fullModerate(
            $content,
            auth()->id(),
            null,
            [
                'moderate_words' => true,
                'sanitize_links' => true,
                'parse_references' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'original' => $result['original'],
            'moderated' => $result['content'],
            'html' => $result['content_html'] ?? $result['content'],
            'blocked' => $result['blocked'],
            'modifications' => $result['modifications'],
            'details' => [
                'word_replacements' => $result['word_replacements'] ?? 0,
                'removed_links' => $result['removed_links'] ?? [],
                'kept_links' => $result['kept_links'] ?? [],
                'references' => $result['references'] ?? [],
            ],
        ]);
    }

    /**
     * Résout les références internes d'un contenu
     * POST /api/content-moderation/resolve-references
     */
    public function resolveReferences(Request $request): JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'max:50000'],
        ]);

        $content = $request->input('content');
        $references = $this->moderationService->extractReferences($content);

        return response()->json([
            'success' => true,
            'references' => $references,
            'valid_count' => count(array_filter($references, fn($r) => $r['exists'])),
            'invalid_count' => count(array_filter($references, fn($r) => !$r['exists'])),
        ]);
    }

    /**
     * Génère des recommandations basées sur les problèmes détectés
     */
    protected function getRecommendations(array $validation): array
    {
        $recommendations = [];

        if (!empty($validation['issues']['banned_words'])) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Votre texte contient des mots qui seront automatiquement remplacés. Considérez reformuler pour plus de clarté.',
            ];
        }

        if (!empty($validation['issues']['external_links'])) {
            $count = count($validation['issues']['external_links']);
            $recommendations[] = [
                'type' => 'info',
                'message' => "{$count} lien(s) externe(s) détecté(s). Seuls les liens vers les sites officiels (.gouv.fr, insee.fr, etc.) sont autorisés.",
            ];
        }

        if (!empty($validation['issues']['invalid_references'])) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Certaines références internes (@loi:, @depute:, etc.) ne correspondent à aucun élément existant.',
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'Votre contenu est conforme aux règles de la plateforme.',
            ];
        }

        return $recommendations;
    }
}
