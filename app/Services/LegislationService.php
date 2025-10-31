<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service d'intégration avec les API de l'Assemblée nationale et du Sénat
 * 
 * Sources:
 * - Assemblée nationale: https://data.assemblee-nationale.fr/
 * - Sénat: https://data.senat.fr/
 * - Format Akoma Ntoso (XML législatif): https://www.akomantoso.com/
 */
class LegislationService
{
    // URLs de base des API
    private const ASSEMBLEE_BASE_URL = 'https://data.assemblee-nationale.fr/';
    private const SENAT_BASE_URL = 'https://data.senat.fr/';
    
    // Durée de cache (24 heures pour données législatives qui changent moins souvent)
    private const CACHE_TTL = 86400;
    
    // Numéro de la législature actuelle (17ème législature depuis 2024)
    private const CURRENT_LEGISLATURE = 17;

    public function __construct(
        private DataGouvService $dataGouvService
    ) {}

    /**
     * Récupère les propositions de loi récentes
     * 
     * @param string $source 'assemblee', 'senat', ou 'both'
     * @param int $limit Nombre de résultats
     * @param array $filters Filtres (statut, theme, date)
     * @return array
     */
    public function getPropositionsLoi(string $source = 'both', int $limit = 20, array $filters = []): array
    {
        $cacheKey = "legislation:propositions:{$source}:" . md5(json_encode($filters)) . ":{$limit}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($source, $limit, $filters) {
            $propositions = [];

            if ($source === 'assemblee' || $source === 'both') {
                $propositions = array_merge($propositions, $this->fetchAssembleePropositions($limit, $filters));
            }

            if ($source === 'senat' || $source === 'both') {
                $propositions = array_merge($propositions, $this->fetchSenatPropositions($limit, $filters));
            }

            // Trier par date décroissante
            usort($propositions, fn($a, $b) => strtotime($b['date_depot']) <=> strtotime($a['date_depot']));

            return array_slice($propositions, 0, $limit);
        });
    }

    /**
     * Récupère le détail d'une proposition de loi
     * 
     * @param string $source 'assemblee' ou 'senat'
     * @param string $numero Numéro de la proposition
     * @param int $legislature Numéro de législature
     * @return array|null
     */
    public function getPropositionDetail(string $source, string $numero, int $legislature = null): ?array
    {
        $legislature = $legislature ?? self::CURRENT_LEGISLATURE;
        $cacheKey = "legislation:proposition:{$source}:{$legislature}:{$numero}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($source, $numero, $legislature) {
            if ($source === 'assemblee') {
                return $this->fetchAssembleePropositionDetail($numero, $legislature);
            } elseif ($source === 'senat') {
                return $this->fetchSenatPropositionDetail($numero);
            }
            
            return null;
        });
    }

    /**
     * Récupère les amendements d'une proposition
     * 
     * @param string $source 'assemblee' ou 'senat'
     * @param string $numero Numéro de la proposition
     * @param int $legislature Numéro de législature
     * @return array
     */
    public function getAmendements(string $source, string $numero, int $legislature = null): array
    {
        $legislature = $legislature ?? self::CURRENT_LEGISLATURE;
        $cacheKey = "legislation:amendements:{$source}:{$legislature}:{$numero}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($source, $numero, $legislature) {
            if ($source === 'assemblee') {
                return $this->fetchAssembleeAmendements($numero, $legislature);
            } elseif ($source === 'senat') {
                return $this->fetchSenatAmendements($numero);
            }
            
            return [];
        });
    }

    /**
     * Récupère l'agenda législatif
     * 
     * @param string $source 'assemblee', 'senat', ou 'both'
     * @param \DateTime|null $dateDebut Date de début
     * @param \DateTime|null $dateFin Date de fin
     * @return array
     */
    public function getAgendaLegislatif(string $source = 'both', ?\DateTime $dateDebut = null, ?\DateTime $dateFin = null): array
    {
        $dateDebut = $dateDebut ?? new \DateTime();
        $dateFin = $dateFin ?? (clone $dateDebut)->modify('+30 days');
        
        $cacheKey = "legislation:agenda:{$source}:" . $dateDebut->format('Y-m-d') . ':' . $dateFin->format('Y-m-d');
        
        return Cache::remember($cacheKey, 3600, function () use ($source, $dateDebut, $dateFin) {
            $agenda = [];

            if ($source === 'assemblee' || $source === 'both') {
                $agenda['assemblee'] = $this->fetchAssembleeAgenda($dateDebut, $dateFin);
            }

            if ($source === 'senat' || $source === 'both') {
                $agenda['senat'] = $this->fetchSenatAgenda($dateDebut, $dateFin);
            }

            return $agenda;
        });
    }

    /**
     * Récupère les votes sur une proposition
     * 
     * @param string $source 'assemblee' ou 'senat'
     * @param string $numero Numéro de la proposition
     * @param int $legislature Numéro de législature
     * @return array
     */
    public function getVotes(string $source, string $numero, int $legislature = null): array
    {
        $legislature = $legislature ?? self::CURRENT_LEGISLATURE;
        $cacheKey = "legislation:votes:{$source}:{$legislature}:{$numero}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($source, $numero, $legislature) {
            if ($source === 'assemblee') {
                return $this->fetchAssembleeVotes($numero, $legislature);
            } elseif ($source === 'senat') {
                return $this->fetchSenatVotes($numero);
            }
            
            return [];
        });
    }

    /**
     * Compare une proposition citoyenne avec les propositions législatives
     * 
     * @param string $titre Titre de la proposition citoyenne
     * @param string $description Description
     * @param array $tags Tags/mots-clés
     * @return array Propositions similaires
     */
    public function findSimilarPropositions(string $titre, string $description, array $tags = []): array
    {
        $cacheKey = "legislation:similar:" . md5($titre . $description . implode(',', $tags));
        
        return Cache::remember($cacheKey, 3600, function () use ($titre, $description, $tags) {
            // Rechercher dans les propositions récentes
            $allPropositions = $this->getPropositionsLoi('both', 100);
            
            $similar = [];
            
            foreach ($allPropositions as $prop) {
                $score = $this->calculateSimilarityScore($titre, $description, $tags, $prop);
                
                if ($score > 0.3) { // Seuil de similarité à 30%
                    $similar[] = [
                        'proposition' => $prop,
                        'score' => $score,
                        'raisons' => $this->getSimilarityReasons($titre, $description, $tags, $prop),
                    ];
                }
            }

            // Trier par score décroissant
            usort($similar, fn($a, $b) => $b['score'] <=> $a['score']);

            return array_slice($similar, 0, 5);
        });
    }

    /**
     * Récupère les statistiques d'activité législative
     * 
     * @param int $legislature Numéro de législature
     * @return array
     */
    public function getStatistiques(int $legislature = null): array
    {
        $legislature = $legislature ?? self::CURRENT_LEGISLATURE;
        $cacheKey = "legislation:stats:{$legislature}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($legislature) {
            return [
                'assemblee' => $this->fetchAssembleeStatistiques($legislature),
                'senat' => $this->fetchSenatStatistiques(),
                'legislature' => $legislature,
            ];
        });
    }

    // ========================================================================
    // MÉTHODES PRIVÉES - ASSEMBLÉE NATIONALE
    // ========================================================================

    private function fetchAssembleePropositions(int $limit, array $filters): array
    {
        try {
            // Via data.gouv.fr qui agrège les données de l'Assemblée
            $dataset = $this->dataGouvService->getDataset('propositions-de-loi-assemblee-nationale');
            
            if (!$dataset || !isset($dataset['resources'])) {
                Log::warning('Dataset propositions Assemblée non trouvé');
                return [];
            }

            // Trouver la ressource JSON la plus récente
            $resource = collect($dataset['resources'])
                ->where('format', 'json')
                ->sortByDesc('last_modified')
                ->first();

            if (!$resource) {
                return [];
            }

            $data = $this->dataGouvService->downloadJson($resource['url']);
            
            return $this->formatAssembleePropositions($data, $filters);
        } catch (\Exception $e) {
            Log::error('Erreur fetchAssembleePropositions', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function fetchAssembleePropositionDetail(string $numero, int $legislature): ?array
    {
        try {
            // URL du dossier législatif
            $url = self::ASSEMBLEE_BASE_URL . "api/documents/dossiers/{$legislature}/DLR5L{$legislature}N{$numero}";
            
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            return $this->formatAssembleePropositionDetail($data);
        } catch (\Exception $e) {
            Log::error('Erreur fetchAssembleePropositionDetail', [
                'numero' => $numero,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function fetchAssembleeAmendements(string $numero, int $legislature): array
    {
        try {
            // Dataset des amendements sur data.gouv.fr
            $dataset = $this->dataGouvService->getDataset('amendements-assemblee-nationale');
            
            if (!$dataset) {
                return [];
            }

            $resource = $this->dataGouvService->findResource($dataset, [
                'format' => 'json',
                'year' => date('Y'),
            ]);

            if (!$resource) {
                return [];
            }

            $data = $this->dataGouvService->downloadJson($resource['url']);
            
            // Filtrer par numéro de proposition
            return collect($data)
                ->filter(fn($amendement) => str_contains($amendement['texte_reference'] ?? '', $numero))
                ->map(fn($amendement) => $this->formatAmendement($amendement, 'assemblee'))
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Erreur fetchAssembleeAmendements', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function fetchAssembleeAgenda(\DateTime $dateDebut, \DateTime $dateFin): array
    {
        try {
            $url = self::ASSEMBLEE_BASE_URL . 'api/seance-publique/agenda';
            
            $response = Http::timeout(30)->get($url, [
                'date_debut' => $dateDebut->format('Y-m-d'),
                'date_fin' => $dateFin->format('Y-m-d'),
            ]);
            
            if (!$response->successful()) {
                return [];
            }

            return $this->formatAssembleeAgenda($response->json());
        } catch (\Exception $e) {
            Log::error('Erreur fetchAssembleeAgenda', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function fetchAssembleeVotes(string $numero, int $legislature): array
    {
        // À implémenter avec l'API scrutins de l'Assemblée
        return [];
    }

    private function fetchAssembleeStatistiques(int $legislature): array
    {
        return [
            'legislature' => $legislature,
            'total_propositions' => 0, // À récupérer depuis l'API
            'propositions_adoptees' => 0,
            'en_discussion' => 0,
        ];
    }

    // ========================================================================
    // MÉTHODES PRIVÉES - SÉNAT
    // ========================================================================

    private function fetchSenatPropositions(int $limit, array $filters): array
    {
        try {
            // Via data.gouv.fr
            $dataset = $this->dataGouvService->getDataset('propositions-de-loi-senat');
            
            if (!$dataset) {
                return [];
            }

            $resource = $this->dataGouvService->findResource($dataset, ['format' => 'json']);

            if (!$resource) {
                return [];
            }

            $data = $this->dataGouvService->downloadJson($resource['url']);
            
            return $this->formatSenatPropositions($data, $filters);
        } catch (\Exception $e) {
            Log::error('Erreur fetchSenatPropositions', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function fetchSenatPropositionDetail(string $numero): ?array
    {
        // À implémenter avec l'API du Sénat
        return null;
    }

    private function fetchSenatAmendements(string $numero): array
    {
        // À implémenter
        return [];
    }

    private function fetchSenatAgenda(\DateTime $dateDebut, \DateTime $dateFin): array
    {
        // À implémenter
        return [];
    }

    private function fetchSenatVotes(string $numero): array
    {
        // À implémenter
        return [];
    }

    private function fetchSenatStatistiques(): array
    {
        return [
            'total_propositions' => 0,
            'propositions_adoptees' => 0,
            'en_discussion' => 0,
        ];
    }

    // ========================================================================
    // MÉTHODES DE FORMATAGE
    // ========================================================================

    private function formatAssembleePropositions(array $data, array $filters): array
    {
        return collect($data)
            ->map(fn($prop) => [
                'source' => 'assemblee',
                'numero' => $prop['numero'] ?? '',
                'titre' => $prop['titre'] ?? '',
                'auteurs' => $prop['auteurs'] ?? [],
                'date_depot' => $prop['date_depot'] ?? '',
                'statut' => $prop['statut'] ?? 'en_cours',
                'theme' => $prop['theme'] ?? '',
                'url' => $prop['url'] ?? '',
            ])
            ->filter(function ($prop) use ($filters) {
                if (isset($filters['statut']) && $prop['statut'] !== $filters['statut']) {
                    return false;
                }
                if (isset($filters['theme']) && !str_contains(strtolower($prop['theme']), strtolower($filters['theme']))) {
                    return false;
                }
                return true;
            })
            ->values()
            ->toArray();
    }

    private function formatAssembleePropositionDetail(array $data): array
    {
        return [
            'source' => 'assemblee',
            'numero' => $data['numero'] ?? '',
            'titre' => $data['titre'] ?? '',
            'auteurs' => $data['auteurs'] ?? [],
            'date_depot' => $data['date_depot'] ?? '',
            'statut' => $data['statut'] ?? '',
            'expose_motifs' => $data['expose_motifs'] ?? '',
            'texte_integral' => $data['texte'] ?? '',
            'etapes' => $data['etapes'] ?? [],
            'url' => $data['url'] ?? '',
        ];
    }

    private function formatSenatPropositions(array $data, array $filters): array
    {
        // Format similaire à l'Assemblée
        return collect($data)
            ->map(fn($prop) => [
                'source' => 'senat',
                'numero' => $prop['numero'] ?? '',
                'titre' => $prop['titre'] ?? '',
                'auteurs' => $prop['auteurs'] ?? [],
                'date_depot' => $prop['date_depot'] ?? '',
                'statut' => $prop['statut'] ?? 'en_cours',
                'theme' => $prop['theme'] ?? '',
                'url' => $prop['url'] ?? '',
            ])
            ->values()
            ->toArray();
    }

    private function formatAssembleeAgenda(array $data): array
    {
        return collect($data)
            ->map(fn($seance) => [
                'date' => $seance['date'] ?? '',
                'heure_debut' => $seance['heure_debut'] ?? '',
                'heure_fin' => $seance['heure_fin'] ?? '',
                'type' => $seance['type'] ?? 'seance_publique',
                'sujets' => $seance['ordre_du_jour'] ?? [],
            ])
            ->toArray();
    }

    private function formatAmendement(array $data, string $source): array
    {
        return [
            'source' => $source,
            'numero' => $data['numero'] ?? '',
            'auteurs' => $data['auteurs'] ?? [],
            'dispositif' => $data['dispositif'] ?? '',
            'expose_motifs' => $data['expose'] ?? '',
            'sort' => $data['sort'] ?? '', // adopté, rejeté, retiré
            'date_depot' => $data['date_depot'] ?? '',
        ];
    }

    // ========================================================================
    // MÉTHODES DE COMPARAISON
    // ========================================================================

    private function calculateSimilarityScore(string $titre, string $description, array $tags, array $proposition): float
    {
        $score = 0.0;

        // Similarité du titre (40% du score)
        $score += $this->calculateTextSimilarity($titre, $proposition['titre']) * 0.4;

        // Similarité des mots-clés (30% du score)
        if (!empty($tags)) {
            $tagScore = 0;
            foreach ($tags as $tag) {
                if (str_contains(strtolower($proposition['titre'] . ' ' . ($proposition['theme'] ?? '')), strtolower($tag))) {
                    $tagScore += 1;
                }
            }
            $score += min($tagScore / count($tags), 1.0) * 0.3;
        }

        // Similarité du thème (30% du score)
        if (!empty($proposition['theme'])) {
            $score += $this->calculateTextSimilarity($description, $proposition['theme']) * 0.3;
        }

        return min($score, 1.0);
    }

    private function calculateTextSimilarity(string $text1, string $text2): float
    {
        $text1 = strtolower($text1);
        $text2 = strtolower($text2);

        // Algorithme de Levenshtein normalisé
        $maxLen = max(strlen($text1), strlen($text2));
        if ($maxLen === 0) {
            return 0.0;
        }

        $distance = levenshtein(substr($text1, 0, 255), substr($text2, 0, 255));
        return 1 - ($distance / $maxLen);
    }

    private function getSimilarityReasons(string $titre, string $description, array $tags, array $proposition): array
    {
        $reasons = [];

        // Mots communs dans le titre
        $commonWords = array_intersect(
            str_word_count(strtolower($titre), 1, 'àâäéèêëïîôùûüÿç'),
            str_word_count(strtolower($proposition['titre']), 1, 'àâäéèêëïîôùûüÿç')
        );

        if (count($commonWords) >= 2) {
            $reasons[] = "Mots communs dans le titre: " . implode(', ', array_slice($commonWords, 0, 3));
        }

        // Tags correspondants
        foreach ($tags as $tag) {
            if (str_contains(strtolower($proposition['titre'] . ' ' . ($proposition['theme'] ?? '')), strtolower($tag))) {
                $reasons[] = "Thème correspondant: {$tag}";
            }
        }

        return $reasons;
    }

    // ========================================================================
    // MÉTHODES POUR GROUPES PARLEMENTAIRES
    // ========================================================================

    /**
     * Récupère les groupes parlementaires
     * 
     * @param string $source 'assemblee' ou 'senat'
     * @param int|null $legislature
     * @return array
     */
    public function getGroupesParlementaires(string $source = 'assemblee', ?int $legislature = null): array
    {
        $legislature = $legislature ?? self::CURRENT_LEGISLATURE;
        $cacheKey = "legislation:groupes:{$source}:{$legislature}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($source, $legislature) {
            if ($source === 'assemblee') {
                return $this->fetchAssembleeGroupes($legislature);
            } elseif ($source === 'senat') {
                return $this->fetchSenatGroupes();
            }
            
            return [];
        });
    }

    /**
     * Récupère les détails de vote par groupe pour un scrutin
     * 
     * @param string $source
     * @param string $numeroScrutin
     * @param int|null $legislature
     * @return array
     */
    public function getVoteDetailsByGroupe(string $source, string $numeroScrutin, ?int $legislature = null): array
    {
        $legislature = $legislature ?? self::CURRENT_LEGISLATURE;
        $cacheKey = "legislation:vote_groupes:{$source}:{$legislature}:{$numeroScrutin}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($source, $numeroScrutin, $legislature) {
            if ($source === 'assemblee') {
                return $this->fetchAssembleeVoteGroupes($numeroScrutin, $legislature);
            }
            
            return [];
        });
    }

    /**
     * Récupère les députés/sénateurs d'un groupe
     * 
     * @param string $source
     * @param string $sigleGroupe
     * @param int|null $legislature
     * @return array
     */
    public function getDeputesByGroupe(string $source, string $sigleGroupe, ?int $legislature = null): array
    {
        $legislature = $legislature ?? self::CURRENT_LEGISLATURE;
        $cacheKey = "legislation:deputes_groupe:{$source}:{$sigleGroupe}:{$legislature}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($source, $sigleGroupe, $legislature) {
            if ($source === 'assemblee') {
                return $this->fetchAssembleeDeputesByGroupe($sigleGroupe, $legislature);
            }
            
            return [];
        });
    }

    // ========================================================================
    // MÉTHODES PRIVÉES - GROUPES ASSEMBLÉE
    // ========================================================================

    /**
     * Fetch groupes parlementaires Assemblée nationale
     */
    private function fetchAssembleeGroupes(int $legislature): array
    {
        try {
            // L'API Assemblée fournit la liste des organes (dont les groupes)
            $response = Http::timeout(30)
                ->get(self::ASSEMBLEE_BASE_URL . "acteurs/groupe/legislature/{$legislature}");

            if (!$response->successful()) {
                Log::warning("Erreur API Assemblée groupes", ['status' => $response->status()]);
                return [];
            }

            $data = $response->json();
            
            if (!isset($data['organes'])) {
                return [];
            }

            return collect($data['organes'])
                ->map(fn($groupe) => $this->formatAssembleeGroupe($groupe))
                ->toArray();

        } catch (\Exception $e) {
            Log::error("Erreur fetch groupes Assemblée", [
                'error' => $e->getMessage(),
                'legislature' => $legislature,
            ]);
            return [];
        }
    }

    /**
     * Fetch détails vote par groupe Assemblée
     */
    private function fetchAssembleeVoteGroupes(string $numeroScrutin, int $legislature): array
    {
        try {
            $response = Http::timeout(30)
                ->get(self::ASSEMBLEE_BASE_URL . "scrutins/legislature/{$legislature}/numero/{$numeroScrutin}");

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            
            // Extraire les votes par groupe depuis les données de scrutin
            if (!isset($data['scrutin']['syntheseVote']['decompte']['groupes'])) {
                return [];
            }

            return collect($data['scrutin']['syntheseVote']['decompte']['groupes'])
                ->map(fn($groupe) => [
                    'sigle' => $groupe['organeRef'] ?? '',
                    'position' => $this->determinerPosition($groupe),
                    'pour' => $groupe['pour'] ?? 0,
                    'contre' => $groupe['contre'] ?? 0,
                    'abstention' => $groupe['abstention'] ?? 0,
                    'non_votants' => $groupe['nonVotants'] ?? 0,
                ])
                ->toArray();

        } catch (\Exception $e) {
            Log::error("Erreur fetch vote groupes Assemblée", [
                'error' => $e->getMessage(),
                'scrutin' => $numeroScrutin,
            ]);
            return [];
        }
    }

    /**
     * Fetch députés par groupe Assemblée
     */
    private function fetchAssembleeDeputesByGroupe(string $sigleGroupe, int $legislature): array
    {
        try {
            $response = Http::timeout(30)
                ->get(self::ASSEMBLEE_BASE_URL . "acteurs/deputes/legislature/{$legislature}");

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            
            if (!isset($data['acteurs'])) {
                return [];
            }

            // Filtrer les députés du groupe
            return collect($data['acteurs'])
                ->filter(function($depute) use ($sigleGroupe) {
                    return isset($depute['mandats'][0]['organes'])
                        && collect($depute['mandats'][0]['organes'])
                            ->contains(fn($organe) => $organe['codeGroupe'] === $sigleGroupe);
                })
                ->map(fn($depute) => [
                    'uid' => $depute['uid'] ?? '',
                    'nom' => $depute['nom']['nomFamille'] ?? '',
                    'prenom' => $depute['prenom'] ?? '',
                    'circonscription' => $depute['mandats'][0]['election']['lieu']['departement'] ?? '',
                ])
                ->toArray();

        } catch (\Exception $e) {
            Log::error("Erreur fetch députés par groupe", [
                'error' => $e->getMessage(),
                'sigle' => $sigleGroupe,
            ]);
            return [];
        }
    }

    /**
     * Fetch groupes Sénat
     */
    private function fetchSenatGroupes(): array
    {
        try {
            // L'API Sénat fournit les groupes
            $response = Http::timeout(30)
                ->get(self::SENAT_BASE_URL . 'les-groupes-politiques/');

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            
            return collect($data['groupes'] ?? [])
                ->map(fn($groupe) => $this->formatSenatGroupe($groupe))
                ->toArray();

        } catch (\Exception $e) {
            Log::error("Erreur fetch groupes Sénat", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Formate un groupe Assemblée
     */
    private function formatAssembleeGroupe(array $data): array
    {
        return [
            'uid' => $data['uid'] ?? '',
            'nom' => $data['libelle'] ?? '',
            'sigle' => $data['libelleAbrev'] ?? '',
            'nombre_membres' => $data['nombreMembres'] ?? 0,
            'position_politique' => $this->devinerPosition($data['libelle'] ?? ''),
            'couleur_hex' => $this->getCouleurGroupe($data['libelleAbrev'] ?? ''),
        ];
    }

    /**
     * Formate un groupe Sénat
     */
    private function formatSenatGroupe(array $data): array
    {
        return [
            'uid' => $data['id'] ?? '',
            'nom' => $data['nom'] ?? '',
            'sigle' => $data['sigle'] ?? '',
            'nombre_membres' => $data['effectif'] ?? 0,
            'position_politique' => $this->devinerPosition($data['nom'] ?? ''),
            'couleur_hex' => $this->getCouleurGroupe($data['sigle'] ?? ''),
        ];
    }

    /**
     * Détermine la position d'un groupe à partir d'un vote
     */
    private function determinerPosition(array $groupe): string
    {
        $pour = $groupe['pour'] ?? 0;
        $contre = $groupe['contre'] ?? 0;
        $abstention = $groupe['abstention'] ?? 0;

        $max = max($pour, $contre, $abstention);

        if ($pour === $max) return 'pour';
        if ($contre === $max) return 'contre';
        if ($abstention === $max) return 'abstention';

        return 'mixte';
    }

    /**
     * Devine la position politique d'un groupe à partir de son nom
     */
    private function devinerPosition(string $nom): string
    {
        $nom = mb_strtolower($nom);

        if (str_contains($nom, 'insoumis') || str_contains($nom, 'communiste')) {
            return 'extreme_gauche';
        }
        if (str_contains($nom, 'socialiste') || str_contains($nom, 'écologiste')) {
            return 'gauche';
        }
        if (str_contains($nom, 'renaissance') || str_contains($nom, 'modem') || str_contains($nom, 'horizons')) {
            return 'centre';
        }
        if (str_contains($nom, 'républicain')) {
            return 'droite';
        }
        if (str_contains($nom, 'national')) {
            return 'extreme_droite';
        }

        return 'centre';
    }

    /**
     * Retourne une couleur par défaut pour un groupe
     */
    private function getCouleurGroupe(string $sigle): string
    {
        $couleurs = [
            'RE' => '#FFB400', // Renaissance (jaune)
            'LR' => '#0066CC', // Les Républicains (bleu)
            'RN' => '#00008B', // Rassemblement National (bleu marine)
            'LFI' => '#CC0000', // La France Insoumise (rouge)
            'SOC' => '#FF8080', // Socialistes (rose)
            'LIOT' => '#00CED1', // LIOT (turquoise)
            'GDR' => '#DD0000', // Gauche Démocrate et Républicaine (rouge)
            'ECO' => '#00C000', // Écologiste (vert)
            'HOR' => '#FF6600', // Horizons (orange)
            'DEM' => '#FF9900', // Démocrate (orange)
        ];

        return $couleurs[$sigle] ?? '#6B7280'; // Gris par défaut
    }
}


