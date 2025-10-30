<?php

namespace App\Services;

use App\Models\CommuneBudget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion des budgets territoriaux
 * Utilise les données de data.gouv.fr pour récupérer et analyser
 * les budgets des collectivités territoriales françaises
 */
class BudgetTerritorialService
{
    // ID du dataset des balances comptables des communes sur data.gouv.fr
    private const DATASET_BALANCES = 'balances-comptables-des-communes';
    
    // Durée de cache pour les budgets (30 jours)
    private const CACHE_TTL = 2592000;

    public function __construct(
        private DataGouvService $dataGouvService
    ) {}

    /**
     * Récupère le budget d'une commune pour une année donnée
     * 
     * @param string $codeInsee Code INSEE de la commune (5 caractères)
     * @param int $annee Année du budget (défaut: année en cours)
     * @return array|null Budget de la commune ou null si non trouvé
     */
    public function getCommuneBudget(string $codeInsee, int $annee = null): ?array
    {
        $annee = $annee ?? date('Y');
        $cacheKey = "budget:commune:{$codeInsee}:{$annee}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($codeInsee, $annee) {
            // Vérifier d'abord en base de données
            $cachedBudget = CommuneBudget::where('code_insee', $codeInsee)
                ->where('annee', $annee)
                ->first();

            if ($cachedBudget) {
                Log::info("Budget commune trouvé en BDD", [
                    'code_insee' => $codeInsee,
                    'annee' => $annee,
                ]);
                return $this->formatBudgetFromModel($cachedBudget);
            }

            // Sinon, récupérer depuis data.gouv.fr
            $budget = $this->fetchBudgetFromDataGouv($codeInsee, $annee);
            
            if ($budget) {
                // Sauvegarder en base pour futures requêtes
                $this->saveBudgetToDatabase($budget);
            }

            return $budget;
        });
    }

    /**
     * Récupère les budgets de plusieurs communes
     * 
     * @param array $codesInsee Liste des codes INSEE
     * @param int $annee Année
     * @return array Budgets indexés par code INSEE
     */
    public function getMultipleBudgets(array $codesInsee, int $annee = null): array
    {
        $annee = $annee ?? date('Y');
        $budgets = [];
        
        foreach ($codesInsee as $code) {
            $budget = $this->getCommuneBudget($code, $annee);
            if ($budget) {
                $budgets[$code] = $budget;
            }
        }

        return $budgets;
    }

    /**
     * Compare les budgets de plusieurs communes
     * 
     * @param array $codesInsee Liste des codes INSEE à comparer
     * @param int $annee Année de référence
     * @return array Données de comparaison
     */
    public function compareBudgets(array $codesInsee, int $annee = null): array
    {
        $budgets = $this->getMultipleBudgets($codesInsee, $annee);
        
        if (empty($budgets)) {
            return [];
        }

        $comparison = [
            'annee' => $annee ?? date('Y'),
            'communes' => [],
            'moyennes' => [],
            'totaux' => [],
        ];

        // Données par commune
        foreach ($budgets as $code => $budget) {
            $comparison['communes'][$code] = [
                'nom' => $budget['nom_commune'],
                'population' => $budget['population'],
                'budget_total' => $budget['budget_total'],
                'depenses_par_habitant' => $budget['depenses_par_habitant'],
                'taux_endettement' => $budget['taux_endettement'] ?? 0,
            ];
        }

        // Calcul des moyennes
        $totalPopulation = array_sum(array_column($budgets, 'population'));
        $totalBudget = array_sum(array_column($budgets, 'budget_total'));
        $avgDepensesHab = array_sum(array_column($budgets, 'depenses_par_habitant')) / count($budgets);

        $comparison['moyennes'] = [
            'depenses_par_habitant' => round($avgDepensesHab, 2),
        ];

        $comparison['totaux'] = [
            'population' => $totalPopulation,
            'budget_total' => $totalBudget,
        ];

        return $comparison;
    }

    /**
     * Génère le contexte budgétaire pour un projet citoyen
     * 
     * @param string $codeInsee Code INSEE de la commune
     * @param float $montantProjet Montant du projet en euros
     * @param string|null $categorie Catégorie du projet (optionnel)
     * @return array Contexte budgétaire enrichi
     */
    public function getProjectContext(string $codeInsee, float $montantProjet, ?string $categorie = null): array
    {
        $budget = $this->getCommuneBudget($codeInsee);
        
        if (!$budget) {
            return [
                'error' => 'Budget communal non disponible',
                'code_insee' => $codeInsee,
            ];
        }

        // Calculs de base
        $pourcentageBudgetTotal = ($montantProjet / max(1, $budget['budget_total'])) * 100;
        $pourcentageInvestissement = ($montantProjet / max(1, $budget['depenses_investissement'])) * 100;
        $coutParHabitant = $montantProjet / max(1, $budget['population']);
        $joursDebudget = ($montantProjet / max(1, $budget['budget_total'])) * 365;

        $context = [
            'commune' => [
                'code_insee' => $codeInsee,
                'nom' => $budget['nom_commune'],
                'population' => $budget['population'],
            ],
            'projet' => [
                'montant' => $montantProjet,
                'categorie' => $categorie,
            ],
            'impact' => [
                'pourcentage_budget_total' => round($pourcentageBudgetTotal, 4),
                'pourcentage_investissement' => round($pourcentageInvestissement, 2),
                'cout_par_habitant' => round($coutParHabitant, 2),
                'equivalent_jours_budget' => round($joursDebudget, 1),
                'equivalent_heures_budget' => round($joursDebudget * 24, 1),
            ],
            'comparaisons' => $this->generateComparisons($montantProjet, $budget, $categorie),
            'contexte_lisible' => $this->generateReadableContext($montantProjet, $budget, $categorie),
        ];

        return $context;
    }

    /**
     * Récupère le budget depuis data.gouv.fr
     */
    private function fetchBudgetFromDataGouv(string $codeInsee, int $annee): ?array
    {
        try {
            // Récupérer le dataset des balances comptables
            $dataset = $this->dataGouvService->getDataset(self::DATASET_BALANCES);
            
            if (!$dataset) {
                Log::warning("Dataset balances comptables introuvable");
                return null;
            }

            // Trouver la ressource CSV pour l'année demandée
            $resource = $this->dataGouvService->findResource($dataset, [
                'year' => $annee,
                'format' => 'csv',
            ]);

            if (!$resource) {
                Log::warning("Ressource budget introuvable", [
                    'annee' => $annee,
                ]);
                return null;
            }

            // Télécharger et parser le CSV
            $csvData = $this->dataGouvService->downloadCsv($resource['url']);
            
            if (empty($csvData)) {
                return null;
            }

            // Rechercher la commune dans les données CSV
            $communeData = collect($csvData)->first(function ($row) use ($codeInsee) {
                // Les colonnes peuvent varier, essayer plusieurs noms
                return ($row['ident'] ?? $row['siren'] ?? $row['code_insee'] ?? null) === $codeInsee;
            });

            if (!$communeData) {
                Log::info("Commune non trouvée dans le dataset", [
                    'code_insee' => $codeInsee,
                    'annee' => $annee,
                ]);
                return null;
            }

            return $this->formatBudgetData($communeData, $annee);
        } catch (\Exception $e) {
            Log::error("Erreur fetchBudgetFromDataGouv", [
                'code_insee' => $codeInsee,
                'annee' => $annee,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Formate les données brutes du CSV en structure standardisée
     */
    private function formatBudgetData(array $rawData, int $annee): array
    {
        // Mapping des colonnes (peut varier selon les années)
        $sd = (float) ($rawData['sd'] ?? $rawData['budget_total'] ?? 0);
        $population = (int) ($rawData['population'] ?? $rawData['pop'] ?? 0);
        
        return [
            'code_insee' => $rawData['ident'] ?? $rawData['siren'] ?? $rawData['code_insee'] ?? '',
            'nom_commune' => $rawData['lbudg'] ?? $rawData['nom'] ?? $rawData['commune'] ?? '',
            'annee' => $annee,
            'population' => $population,
            'budget_total' => $sd,
            'recettes_fonctionnement' => (float) ($rawData['rf'] ?? 0),
            'depenses_fonctionnement' => (float) ($rawData['df'] ?? 0),
            'recettes_investissement' => (float) ($rawData['ri'] ?? 0),
            'depenses_investissement' => (float) ($rawData['di'] ?? 0),
            'dette' => (float) ($rawData['dette'] ?? 0),
            'depenses_par_habitant' => $population > 0 ? round($sd / $population, 2) : 0,
            'taux_endettement' => $sd > 0 ? round((($rawData['dette'] ?? 0) / $sd) * 100, 2) : 0,
        ];
    }

    /**
     * Formate un budget depuis le modèle Eloquent
     */
    private function formatBudgetFromModel(CommuneBudget $budget): array
    {
        return [
            'code_insee' => $budget->code_insee,
            'nom_commune' => $budget->nom_commune,
            'annee' => $budget->annee,
            'population' => $budget->population,
            'budget_total' => $budget->budget_total,
            'recettes_fonctionnement' => $budget->recettes_fonctionnement,
            'depenses_fonctionnement' => $budget->depenses_fonctionnement,
            'recettes_investissement' => $budget->recettes_investissement,
            'depenses_investissement' => $budget->depenses_investissement,
            'dette' => $budget->dette,
            'depenses_par_habitant' => $budget->depenses_par_habitant,
            'taux_endettement' => $budget->budget_total > 0 
                ? round(($budget->dette / $budget->budget_total) * 100, 2) 
                : 0,
            'sections' => $budget->sections ?? [],
        ];
    }

    /**
     * Sauvegarde le budget en base de données
     */
    private function saveBudgetToDatabase(array $budget): void
    {
        try {
            CommuneBudget::updateOrCreate(
                [
                    'code_insee' => $budget['code_insee'],
                    'annee' => $budget['annee'],
                ],
                [
                    'nom_commune' => $budget['nom_commune'],
                    'population' => $budget['population'],
                    'budget_total' => $budget['budget_total'],
                    'recettes_fonctionnement' => $budget['recettes_fonctionnement'],
                    'depenses_fonctionnement' => $budget['depenses_fonctionnement'],
                    'recettes_investissement' => $budget['recettes_investissement'],
                    'depenses_investissement' => $budget['depenses_investissement'],
                    'dette' => $budget['dette'],
                    'depenses_par_habitant' => $budget['depenses_par_habitant'],
                ]
            );

            Log::info("Budget sauvegardé en BDD", [
                'code_insee' => $budget['code_insee'],
                'annee' => $budget['annee'],
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur sauvegarde budget", [
                'code_insee' => $budget['code_insee'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Génère des comparaisons parlantes pour le projet
     */
    private function generateComparisons(float $montant, array $budget, ?string $categorie): array
    {
        $comparisons = [];

        // Comparaison avec des postes de dépenses typiques
        $postesTypes = [
            ['nom' => 'Culture et sports', 'pourcentage' => 0.08],
            ['nom' => 'Éducation', 'pourcentage' => 0.15],
            ['nom' => 'Voirie et transports', 'pourcentage' => 0.12],
            ['nom' => 'Environnement', 'pourcentage' => 0.10],
            ['nom' => 'Aménagement urbain', 'pourcentage' => 0.18],
        ];

        foreach ($postesTypes as $poste) {
            $montantPoste = $budget['budget_total'] * $poste['pourcentage'];
            $ratio = $montantPoste > 0 ? ($montant / $montantPoste) * 100 : 0;
            
            $comparisons[] = [
                'poste' => $poste['nom'],
                'montant_annuel_estime' => $montantPoste,
                'pourcentage_du_poste' => round($ratio, 2),
                'pertinent' => $categorie ? str_contains(strtolower($poste['nom']), strtolower($categorie)) : false,
            ];
        }

        return $comparisons;
    }

    /**
     * Génère un contexte lisible en français
     */
    private function generateReadableContext(float $montant, array $budget, ?string $categorie): array
    {
        $messages = [];

        // Message principal
        $pourcentage = ($montant / $budget['budget_total']) * 100;
        $messages['principal'] = sprintf(
            "Ce projet de %s représente %s du budget annuel de %s.",
            $this->formatMontant($montant),
            $this->formatPourcentage($pourcentage),
            $budget['nom_commune']
        );

        // Message par habitant
        $coutHab = $montant / $budget['population'];
        $messages['par_habitant'] = sprintf(
            "Cela correspond à un coût de %s par habitant.",
            $this->formatMontant($coutHab)
        );

        // Message temporel
        $jours = ($montant / $budget['budget_total']) * 365;
        if ($jours < 1) {
            $heures = $jours * 24;
            $messages['temporel'] = sprintf(
                "Cela équivaut à %s de budget communal.",
                $heures < 1 ? "moins d'une heure" : round($heures, 1) . " heures"
            );
        } else {
            $messages['temporel'] = sprintf(
                "Cela équivaut à %s de budget communal.",
                round($jours, 1) . " jours"
            );
        }

        return $messages;
    }

    /**
     * Formate un montant en euros
     */
    private function formatMontant(float $montant): string
    {
        if ($montant >= 1000000) {
            return number_format($montant / 1000000, 2, ',', ' ') . ' M€';
        } elseif ($montant >= 1000) {
            return number_format($montant / 1000, 0, ',', ' ') . ' k€';
        } else {
            return number_format($montant, 2, ',', ' ') . ' €';
        }
    }

    /**
     * Formate un pourcentage
     */
    private function formatPourcentage(float $pourcentage): string
    {
        if ($pourcentage < 0.01) {
            return 'moins de 0.01%';
        } elseif ($pourcentage < 1) {
            return number_format($pourcentage, 3, ',', '') . '%';
        } else {
            return number_format($pourcentage, 2, ',', '') . '%';
        }
    }
}

