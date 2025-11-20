<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WikipediaService
{
    private const API_BASE_URL = 'https://fr.wikipedia.org/api/rest_v1/page/summary/';
    private const WIKIPEDIA_BASE_URL = 'https://fr.wikipedia.org';
    private const USER_AGENT = 'CivicDash/1.0 (https://demo.objectif2027.fr)';
    
    /**
     * Parser le tableau Wikipedia de la législature 17
     * 
     * @return array Liste des députés avec leur lien Wikipedia
     */
    public function parseDeputesL17(): array
    {
        try {
            $url = 'https://fr.wikipedia.org/wiki/Liste_des_députés_de_la_XVIIe_législature_de_la_Cinquième_République';
            
            $response = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
            ])->timeout(30)->get($url);
            
            if (!$response->successful()) {
                throw new Exception("Erreur HTTP {$response->status()} lors de la récupération de la page Wikipedia");
            }
            
            $html = $response->body();
            
            // Parser le HTML pour extraire les liens des députés
            $deputes = [];
            
            // Pattern pour trouver les lignes du tableau contenant les députés
            // Format: <td><a href="/wiki/Nom_Prenom" title="...">Nom Prénom</a>
            preg_match_all(
                '/<tr[^>]*>.*?<td[^>]*>.*?<a href="(\/wiki\/[^"]+)"[^>]*title="([^"]*)"[^>]*>([^<]+)<\/a>.*?<\/tr>/si',
                $html,
                $matches,
                PREG_SET_ORDER
            );
            
            foreach ($matches as $match) {
                $wikiPath = $match[1];
                $title = $match[2];
                $nom = $match[3];
                
                // Ignorer les liens vers des pages non-biographiques
                if (
                    strpos($wikiPath, 'Circonscription') !== false ||
                    strpos($wikiPath, 'Liste_') !== false ||
                    strpos($wikiPath, 'Élection') !== false ||
                    strpos($wikiPath, 'Groupe_') !== false
                ) {
                    continue;
                }
                
                $deputes[] = [
                    'nom_complet' => trim($nom),
                    'wikipedia_path' => $wikiPath,
                    'wikipedia_url' => self::WIKIPEDIA_BASE_URL . $wikiPath,
                    'title' => $title,
                ];
            }
            
            Log::info("Wikipedia L17: {count} députés trouvés", ['count' => count($deputes)]);
            
            return $deputes;
            
        } catch (Exception $e) {
            Log::error("Erreur lors du parsing Wikipedia L17: {$e->getMessage()}");
            throw $e;
        }
    }
    
    /**
     * Récupérer les données Wikipedia d'une page via l'API MediaWiki
     * 
     * @param string $pageTitle Titre de la page (ex: "Marine_Le_Pen")
     * @return array|null Données Wikipedia ou null si erreur
     */
    public function getPageSummary(string $pageTitle): ?array
    {
        try {
            // Extraire le titre de la page depuis l'URL si nécessaire
            if (strpos($pageTitle, '/wiki/') !== false) {
                $pageTitle = str_replace('/wiki/', '', $pageTitle);
            }
            
            $url = self::API_BASE_URL . urlencode($pageTitle);
            
            $response = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
            ])->timeout(10)->get($url);
            
            if (!$response->successful()) {
                Log::warning("Wikipedia API erreur {$response->status()} pour: {$pageTitle}");
                return null;
            }
            
            $data = $response->json();
            
            return [
                'title' => $data['title'] ?? null,
                'extract' => $data['extract'] ?? null,
                'thumbnail' => $data['thumbnail']['source'] ?? null,
                'wikipedia_url' => $data['content_urls']['desktop']['page'] ?? null,
            ];
            
        } catch (Exception $e) {
            Log::error("Erreur API Wikipedia pour {$pageTitle}: {$e->getMessage()}");
            return null;
        }
    }
    
    /**
     * Normaliser un nom pour le matching
     * 
     * @param string $nom
     * @return string
     */
    public function normalizeName(string $nom): string
    {
        // Supprimer les accents
        $nom = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nom);
        
        // Tout en minuscules
        $nom = mb_strtolower($nom);
        
        // Supprimer les tirets, espaces multiples, caractères spéciaux
        $nom = preg_replace('/[^a-z0-9\s]/', '', $nom);
        $nom = preg_replace('/\s+/', ' ', $nom);
        
        return trim($nom);
    }
    
    /**
     * Calculer la similarité entre deux noms (Levenshtein normalisé)
     * 
     * @param string $nom1
     * @param string $nom2
     * @return float Score de 0 à 1 (1 = identique)
     */
    public function nameSimilarity(string $nom1, string $nom2): float
    {
        $nom1 = $this->normalizeName($nom1);
        $nom2 = $this->normalizeName($nom2);
        
        if ($nom1 === $nom2) {
            return 1.0;
        }
        
        $maxLen = max(strlen($nom1), strlen($nom2));
        if ($maxLen === 0) {
            return 0.0;
        }
        
        $distance = levenshtein($nom1, $nom2);
        
        return 1 - ($distance / $maxLen);
    }
    
    /**
     * Matcher un acteur AN avec les données Wikipedia
     * 
     * @param array $acteur ['nom' => '...', 'prenom' => '...']
     * @param array $deputesWikipedia Liste des députés Wikipedia
     * @return array|null Meilleur match ou null
     */
    public function matchActeur(array $acteur, array $deputesWikipedia): ?array
    {
        $nomCompletActeur = trim(($acteur['prenom'] ?? '') . ' ' . ($acteur['nom'] ?? ''));
        
        if (empty($nomCompletActeur)) {
            return null;
        }
        
        $bestMatch = null;
        $bestScore = 0.0;
        
        foreach ($deputesWikipedia as $depute) {
            $nomWiki = $depute['nom_complet'];
            
            // Calculer la similarité
            $score = $this->nameSimilarity($nomCompletActeur, $nomWiki);
            
            // Si score > 0.8, on considère que c'est un match
            if ($score > $bestScore && $score >= 0.8) {
                $bestScore = $score;
                $bestMatch = $depute;
                $bestMatch['similarity_score'] = $score;
            }
        }
        
        return $bestMatch;
    }
    
    /**
     * Récupérer les données complètes pour un acteur
     * 
     * @param array $acteur
     * @param array $deputesWikipedia
     * @return array|null
     */
    public function enrichActeur(array $acteur, array $deputesWikipedia): ?array
    {
        // 1. Matcher l'acteur avec Wikipedia
        $match = $this->matchActeur($acteur, $deputesWikipedia);
        
        if (!$match) {
            return null;
        }
        
        // 2. Récupérer les données via l'API MediaWiki
        $summary = $this->getPageSummary($match['wikipedia_path']);
        
        if (!$summary) {
            return [
                'wikipedia_url' => $match['wikipedia_url'],
                'photo_wikipedia_url' => null,
                'wikipedia_extract' => null,
                'similarity_score' => $match['similarity_score'],
            ];
        }
        
        return [
            'wikipedia_url' => $summary['wikipedia_url'] ?? $match['wikipedia_url'],
            'photo_wikipedia_url' => $summary['thumbnail'],
            'wikipedia_extract' => $summary['extract'],
            'similarity_score' => $match['similarity_score'],
        ];
    }
    
    /**
     * Rechercher une page Wikipedia par nom (fallback)
     * 
     * @param string $nom
     * @param string $prenom
     * @return array|null
     */
    public function searchByName(string $nom, string $prenom): ?array
    {
        try {
            $searchTerm = "{$prenom} {$nom} député";
            
            $url = 'https://fr.wikipedia.org/w/api.php';
            
            $response = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
            ])->get($url, [
                'action' => 'opensearch',
                'search' => $searchTerm,
                'limit' => 1,
                'namespace' => 0,
                'format' => 'json',
            ]);
            
            if (!$response->successful()) {
                return null;
            }
            
            $data = $response->json();
            
            // Format OpenSearch: [query, [titles], [descriptions], [urls]]
            if (empty($data[3][0])) {
                return null;
            }
            
            $wikiUrl = $data[3][0];
            $pageTitle = basename(parse_url($wikiUrl, PHP_URL_PATH));
            
            return $this->getPageSummary($pageTitle);
            
        } catch (Exception $e) {
            Log::error("Erreur recherche Wikipedia pour {$prenom} {$nom}: {$e->getMessage()}");
            return null;
        }
    }
}

