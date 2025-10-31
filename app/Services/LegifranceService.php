<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LegifranceService
{
    private const OAUTH_URL = 'https://oauth.piste.gouv.fr/api/oauth/token';
    private const API_BASE_URL = 'https://api.piste.gouv.fr/dila/legifrance/lf-engine-app';
    private const TOKEN_CACHE_KEY = 'legifrance_oauth_token';
    private const TOKEN_CACHE_DURATION = 55; // minutes (token valid 1h, on refresh à 55min)

    private $clientId;
    private $clientSecret;

    public function __construct()
    {
        $this->clientId = config('services.legifrance.client_id');
        $this->clientSecret = config('services.legifrance.client_secret');
    }

    /**
     * Obtenir le token OAuth2 (avec cache)
     */
    private function getAccessToken(): ?string
    {
        // Vérifier le cache
        $cachedToken = Cache::get(self::TOKEN_CACHE_KEY);
        if ($cachedToken) {
            return $cachedToken;
        }

        // Sinon, requêter un nouveau token
        try {
            $response = Http::asForm()->post(self::OAUTH_URL, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => 'openid',
            ]);

            if ($response->successful()) {
                $token = $response->json('access_token');
                
                // Mettre en cache pour 55 minutes
                Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addMinutes(self::TOKEN_CACHE_DURATION));
                
                return $token;
            }

            Log::error('Legifrance OAuth error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Legifrance OAuth exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Faire une requête à l'API Légifrance
     */
    private function makeRequest(string $endpoint, array $body = []): ?array
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->post(self::API_BASE_URL . $endpoint, $body);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Legifrance API error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Legifrance API exception', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Chercher un article par référence
     * 
     * @param string $reference Ex: "L. 123-4"
     * @param string $codeName Ex: "Code civil"
     * @return array|null
     */
    public function searchArticle(string $reference, string $codeName): ?array
    {
        $cacheKey = "legifrance_article_{$codeName}_{$reference}";
        
        // Cache 7 jours
        return Cache::remember($cacheKey, now()->addDays(7), function () use ($reference, $codeName) {
            $codeId = $this->getCodeId($codeName);
            
            if (!$codeId) {
                return null;
            }

            $response = $this->makeRequest('/search', [
                'recherche' => [
                    'champs' => [
                        [
                            'typeChamp' => 'ARTICLE',
                            'criteres' => [
                                [
                                    'typeRecherche' => 'EXACTE',
                                    'valeur' => $reference,
                                ]
                            ]
                        ],
                        [
                            'typeChamp' => 'CODE',
                            'criteres' => [
                                [
                                    'typeRecherche' => 'EXACTE',
                                    'valeur' => $codeId,
                                ]
                            ]
                        ]
                    ],
                    'operateur' => 'ET',
                ],
                'pageNumber' => 1,
                'pageSize' => 1,
            ]);

            return $response['results'][0] ?? null;
        });
    }

    /**
     * Obtenir les détails complets d'un article
     */
    public function getArticleDetails(string $articleId): ?array
    {
        $cacheKey = "legifrance_article_details_{$articleId}";
        
        return Cache::remember($cacheKey, now()->addDays(7), function () use ($articleId) {
            return $this->makeRequest('/consult/article', [
                'id' => $articleId,
            ]);
        });
    }

    /**
     * Chercher de la jurisprudence liée à un article
     */
    public function findJurisprudence(string $reference, string $codeName, int $limit = 5): array
    {
        $cacheKey = "legifrance_juri_{$codeName}_{$reference}";
        
        return Cache::remember($cacheKey, now()->addDays(7), function () use ($reference, $codeName, $limit) {
            $response = $this->makeRequest('/search', [
                'recherche' => [
                    'champs' => [
                        [
                            'typeChamp' => 'TEXT_INTEGRAL',
                            'criteres' => [
                                [
                                    'typeRecherche' => 'CONTAINS',
                                    'valeur' => "{$reference} {$codeName}",
                                ]
                            ]
                        ]
                    ],
                    'operateur' => 'ET',
                ],
                'fond' => 'JURI',
                'pageNumber' => 1,
                'pageSize' => $limit,
            ]);

            return $response['results'] ?? [];
        });
    }

    /**
     * Obtenir les articles connexes d'un code
     */
    public function getRelatedArticles(string $codeId, string $sectionId, int $limit = 5): array
    {
        $cacheKey = "legifrance_related_{$codeId}_{$sectionId}";
        
        return Cache::remember($cacheKey, now()->addDays(7), function () use ($codeId, $sectionId, $limit) {
            $response = $this->makeRequest('/list', [
                'textId' => $codeId,
                'sectionId' => $sectionId,
                'pageNumber' => 1,
                'pageSize' => $limit,
            ]);

            return $response['articles'] ?? [];
        });
    }

    /**
     * Mapper nom de code vers ID Légifrance
     */
    private function getCodeId(string $codeName): ?string
    {
        $codeMap = [
            'code civil' => 'LEGITEXT000006070721',
            'code pénal' => 'LEGITEXT000006070719',
            'code du travail' => 'LEGITEXT000006072050',
            'code de la santé publique' => 'LEGITEXT000006072665',
            'code de commerce' => 'LEGITEXT000005634379',
            'code de la sécurité sociale' => 'LEGITEXT000006073189',
            'code général des impôts' => 'LEGITEXT000006069577',
            'code de l\'environnement' => 'LEGITEXT000006074220',
            'code de l\'éducation' => 'LEGITEXT000006071191',
            'code de la consommation' => 'LEGITEXT000006069565',
            'code de procédure pénale' => 'LEGITEXT000006071154',
            'code de procédure civile' => 'LEGITEXT000006070716',
            'code de la construction et de l\'habitation' => 'LEGITEXT000006074096',
            'code rural et de la pêche maritime' => 'LEGITEXT000006071367',
            'code monétaire et financier' => 'LEGITEXT000006072026',
        ];

        $normalizedName = strtolower(trim($codeName));
        
        return $codeMap[$normalizedName] ?? null;
    }

    /**
     * Obtenir le nom complet d'un code depuis un acronyme
     */
    public function expandCodeAcronym(string $acronym): ?string
    {
        $acronymMap = [
            'csp' => 'code de la santé publique',
            'cct' => 'code du travail',
            'cgi' => 'code général des impôts',
            'css' => 'code de la sécurité sociale',
            'cpp' => 'code de procédure pénale',
            'cpc' => 'code de procédure civile',
            'cch' => 'code de la construction et de l\'habitation',
            'cmf' => 'code monétaire et financier',
        ];

        $normalizedAcronym = strtolower(trim($acronym));
        
        return $acronymMap[$normalizedAcronym] ?? null;
    }

    /**
     * Vérifier la disponibilité de l'API
     */
    public function healthCheck(): bool
    {
        $token = $this->getAccessToken();
        return $token !== null;
    }
}

