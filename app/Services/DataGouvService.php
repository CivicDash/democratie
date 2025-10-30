<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service d'intégration avec l'API data.gouv.fr
 * 
 * @see https://www.data.gouv.fr/fr/apidoc/
 */
class DataGouvService
{
    private const BASE_URL = 'https://www.data.gouv.fr/api/1/';
    private const CACHE_TTL = 604800; // 7 jours en secondes
    private const REQUEST_TIMEOUT = 30; // 30 secondes
    private const RETRY_TIMES = 3; // Nombre de tentatives
    private const RETRY_DELAY = 1000; // Délai entre tentatives (ms)

    /**
     * Clé API optionnelle (pour augmenter les limites de taux)
     */
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.datagouv.api_key');
    }

    /**
     * Récupère les informations complètes d'un dataset
     * 
     * @param string $datasetId ID ou slug du dataset
     * @return array|null Données du dataset ou null si erreur
     */
    public function getDataset(string $datasetId): ?array
    {
        $cacheKey = $this->getCacheKey('dataset', $datasetId);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($datasetId) {
            try {
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->retry(self::RETRY_TIMES, self::RETRY_DELAY)
                    ->get(self::BASE_URL . "datasets/{$datasetId}");

                if ($response->successful()) {
                    Log::info("data.gouv.fr dataset récupéré", [
                        'dataset_id' => $datasetId,
                        'resources_count' => count($response->json('resources', [])),
                    ]);
                    
                    return $response->json();
                }

                Log::warning("data.gouv.fr dataset introuvable", [
                    'dataset_id' => $datasetId,
                    'status' => $response->status(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error("Erreur data.gouv.fr getDataset", [
                    'dataset_id' => $datasetId,
                    'error' => $e->getMessage(),
                ]);
                
                return null;
            }
        });
    }

    /**
     * Recherche de datasets par mots-clés
     * 
     * @param string $query Terme de recherche
     * @param array $filters Filtres supplémentaires (organization, tag, featured, etc.)
     * @param int $pageSize Nombre de résultats par page (défaut: 20, max: 100)
     * @param int $page Numéro de page (commence à 1)
     * @return array Résultats de recherche
     */
    public function searchDatasets(
        string $query, 
        array $filters = [], 
        int $pageSize = 20,
        int $page = 1
    ): array {
        $cacheKey = $this->getCacheKey('search', md5($query . json_encode($filters) . $pageSize . $page));
        
        return Cache::remember($cacheKey, 3600, function () use ($query, $filters, $pageSize, $page) {
            try {
                $params = array_merge([
                    'q' => $query,
                    'page_size' => min($pageSize, 100),
                    'page' => $page,
                ], $filters);

                $response = Http::withHeaders($this->getHeaders())
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->get(self::BASE_URL . 'datasets/', $params);

                if ($response->successful()) {
                    return $response->json();
                }

                return ['data' => [], 'total' => 0];
            } catch (\Exception $e) {
                Log::error("Erreur data.gouv.fr searchDatasets", [
                    'query' => $query,
                    'error' => $e->getMessage(),
                ]);
                
                return ['data' => [], 'total' => 0];
            }
        });
    }

    /**
     * Récupère les métadonnées d'une ressource (fichier) spécifique
     * 
     * @param string $resourceId ID de la ressource
     * @return array|null Métadonnées de la ressource
     */
    public function getResourceMetadata(string $resourceId): ?array
    {
        $cacheKey = $this->getCacheKey('resource', $resourceId);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($resourceId) {
            try {
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->get(self::BASE_URL . "datasets/resources/{$resourceId}");

                if ($response->successful()) {
                    return $response->json();
                }

                return null;
            } catch (\Exception $e) {
                Log::error("Erreur data.gouv.fr getResourceMetadata", [
                    'resource_id' => $resourceId,
                    'error' => $e->getMessage(),
                ]);
                
                return null;
            }
        });
    }

    /**
     * Télécharge et parse un fichier CSV depuis une URL
     * 
     * @param string $url URL du fichier CSV
     * @param bool $withHeaders Si true, utilise la première ligne comme clés
     * @param string $delimiter Délimiteur CSV (défaut: ',')
     * @param int $maxRows Nombre maximum de lignes à parser (0 = toutes)
     * @return array Données CSV parsées
     */
    public function downloadCsv(
        string $url, 
        bool $withHeaders = true,
        string $delimiter = ',',
        int $maxRows = 0
    ): array {
        $cacheKey = $this->getCacheKey('csv', md5($url));
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($url, $withHeaders, $delimiter, $maxRows) {
            try {
                $response = Http::timeout(60) // CSV peut être gros
                    ->retry(2, 2000)
                    ->get($url);
                
                if (!$response->successful()) {
                    Log::warning("Échec téléchargement CSV", [
                        'url' => $url,
                        'status' => $response->status(),
                    ]);
                    return [];
                }

                $content = $response->body();
                
                // Détection de l'encodage et conversion UTF-8
                $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
                if ($encoding !== 'UTF-8') {
                    $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                }

                $lines = explode("\n", trim($content));
                $csv = array_map(function($line) use ($delimiter) {
                    return str_getcsv($line, $delimiter);
                }, $lines);

                // Enlever les lignes vides
                $csv = array_filter($csv, fn($row) => !empty(array_filter($row)));
                
                if (empty($csv)) {
                    return [];
                }

                $headers = null;
                if ($withHeaders) {
                    $headers = array_shift($csv);
                }

                // Limiter le nombre de lignes si demandé
                if ($maxRows > 0) {
                    $csv = array_slice($csv, 0, $maxRows);
                }

                // Associer les headers si présents
                if ($headers) {
                    $csv = array_map(function ($row) use ($headers) {
                        // S'assurer que le nombre de colonnes correspond
                        $row = array_pad($row, count($headers), null);
                        return array_combine($headers, array_slice($row, 0, count($headers)));
                    }, $csv);
                }

                Log::info("CSV téléchargé et parsé", [
                    'url' => $url,
                    'rows' => count($csv),
                    'encoding' => $encoding,
                ]);

                return array_values($csv); // Réindexer
            } catch (\Exception $e) {
                Log::error("Erreur data.gouv.fr downloadCsv", [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
                
                return [];
            }
        });
    }

    /**
     * Télécharge et parse un fichier JSON depuis une URL
     * 
     * @param string $url URL du fichier JSON
     * @return array Données JSON parsées
     */
    public function downloadJson(string $url): array
    {
        $cacheKey = $this->getCacheKey('json', md5($url));
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($url) {
            try {
                $response = Http::timeout(60)
                    ->retry(2, 2000)
                    ->get($url);
                
                if (!$response->successful()) {
                    return [];
                }

                $data = $response->json();
                
                Log::info("JSON téléchargé et parsé", [
                    'url' => $url,
                    'items' => is_array($data) ? count($data) : 1,
                ]);

                return $data ?? [];
            } catch (\Exception $e) {
                Log::error("Erreur data.gouv.fr downloadJson", [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
                
                return [];
            }
        });
    }

    /**
     * Trouve une ressource dans un dataset selon des critères
     * 
     * @param array $dataset Dataset complet
     * @param array $criteria Critères de recherche (title, format, year, etc.)
     * @return array|null Ressource trouvée ou null
     */
    public function findResource(array $dataset, array $criteria): ?array
    {
        $resources = $dataset['resources'] ?? [];
        
        foreach ($resources as $resource) {
            $match = true;
            
            foreach ($criteria as $key => $value) {
                if ($key === 'title' && !str_contains(strtolower($resource['title'] ?? ''), strtolower($value))) {
                    $match = false;
                    break;
                }
                
                if ($key === 'format' && strcasecmp($resource['format'] ?? '', $value) !== 0) {
                    $match = false;
                    break;
                }
                
                if ($key === 'year') {
                    $title = $resource['title'] ?? '';
                    if (!str_contains($title, (string) $value)) {
                        $match = false;
                        break;
                    }
                }
                
                if ($key === 'description' && !str_contains(strtolower($resource['description'] ?? ''), strtolower($value))) {
                    $match = false;
                    break;
                }
            }
            
            if ($match) {
                return $resource;
            }
        }
        
        return null;
    }

    /**
     * Invalide le cache pour un dataset ou une ressource spécifique
     * 
     * @param string $type Type de cache ('dataset', 'resource', 'csv', 'json', 'all')
     * @param string|null $identifier Identifiant spécifique ou null pour tout le type
     * @return bool Succès de l'invalidation
     */
    public function invalidateCache(string $type = 'all', ?string $identifier = null): bool
    {
        try {
            if ($type === 'all') {
                Cache::tags(['datagouv'])->flush();
                Log::info("Cache data.gouv.fr entièrement vidé");
                return true;
            }
            
            if ($identifier) {
                $cacheKey = $this->getCacheKey($type, $identifier);
                Cache::forget($cacheKey);
                Log::info("Cache data.gouv.fr invalidé", [
                    'type' => $type,
                    'identifier' => $identifier,
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur invalidation cache data.gouv.fr", [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Récupère les statistiques du service
     * 
     * @return array Statistiques d'utilisation
     */
    public function getStats(): array
    {
        return [
            'cache_ttl' => self::CACHE_TTL,
            'timeout' => self::REQUEST_TIMEOUT,
            'retry_times' => self::RETRY_TIMES,
            'api_key_configured' => !empty($this->apiKey),
            'base_url' => self::BASE_URL,
        ];
    }

    /**
     * Génère une clé de cache unique
     * 
     * @param string $type Type de données
     * @param string $identifier Identifiant unique
     * @return string Clé de cache
     */
    private function getCacheKey(string $type, string $identifier): string
    {
        return "datagouv:{$type}:{$identifier}";
    }

    /**
     * Génère les headers HTTP pour les requêtes
     * 
     * @return array Headers
     */
    private function getHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => 'CivicDash/1.0 (Plateforme Démocratie Participative)',
        ];

        if ($this->apiKey) {
            $headers['X-API-KEY'] = $this->apiKey;
        }

        return $headers;
    }
}

