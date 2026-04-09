<?php

namespace App\Console\Commands;

use App\Models\CommuneBudget;
use App\Models\MaireMandat;
use App\Models\Ville;
use App\Models\VillePopulation;
use App\Models\VilleStats;
use Illuminate\Console\Command;

/**
 * Calcule et met à jour les statistiques pré-calculées des villes
 */
class CalculateVilleStats extends Command
{
    protected $signature = 'stats:villes 
                            {--departement= : Calculer pour un département spécifique}
                            {--ville= : Calculer pour une ville spécifique (code_insee)}
                            {--min-pop=0 : Population minimum}
                            {--force : Recalculer même si déjà fait récemment}';

    protected $description = 'Calcule les statistiques pré-calculées des villes (endettement, évolution population, etc.)';

    private int $calculated = 0;

    private int $skipped = 0;

    public function handle(): int
    {
        $this->info('📊 Calcul des statistiques des villes');
        $this->newLine();

        $departement = $this->option('departement');
        $villeCode = $this->option('ville');
        $minPop = (int) $this->option('min-pop');
        $force = $this->option('force');

        $query = Ville::query()
            ->where('arrondissement_municipal', false);

        if ($departement) {
            $query->where('departement_code', $departement);
        }

        if ($villeCode) {
            $query->where('code_insee', $villeCode);
        }

        if ($minPop > 0) {
            $query->where('population', '>=', $minPop);
        }

        $villes = $query->get();
        $this->info("   {$villes->count()} villes à traiter");

        $bar = $this->output->createProgressBar($villes->count());
        $bar->start();

        foreach ($villes as $ville) {
            $this->calculateForVille($ville, $force);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('✅ Terminé !');
        $this->info("   Statistiques calculées : {$this->calculated}");
        $this->info("   Ignorées (à jour) : {$this->skipped}");

        return Command::SUCCESS;
    }

    private function calculateForVille(Ville $ville, bool $force): void
    {
        // Vérifier si on doit recalculer
        $existingStats = VilleStats::where('ville_id', $ville->id)
            ->whereNull('annee')
            ->first();

        if ($existingStats && ! $force) {
            // Vérifie si calculé il y a moins de 24h
            if ($existingStats->calculated_at && $existingStats->calculated_at->diffInHours(now()) < 24) {
                $this->skipped++;

                return;
            }
        }

        // Calculer les stats
        $stats = [
            'ville_id' => $ville->id,
            'annee' => null, // Stats actuelles
            'population' => $ville->population,
            'densite' => $ville->densite,
        ];

        // Évolution population sur 5 ans
        $stats['evolution_population_5ans_pct'] = $this->calculatePopulationEvolution($ville);

        // Stats financières depuis les budgets
        $budgetStats = $this->calculateBudgetStats($ville);
        $stats = array_merge($stats, $budgetStats);

        // Stats des maires
        $maireStats = $this->calculateMaireStats($ville);
        $stats = array_merge($stats, $maireStats);

        // Score de santé financière
        $stats['score_sante_financiere'] = $this->calculateScoreSanteFinanciere($stats);

        $stats['calculated_at'] = now();
        $stats['source'] = 'civicdash';

        // Sauvegarder
        VilleStats::updateOrCreate(
            ['ville_id' => $ville->id, 'annee' => null],
            $stats
        );

        $this->calculated++;
    }

    private function calculatePopulationEvolution(Ville $ville): ?float
    {
        // Récupérer les populations historiques
        $populations = VillePopulation::where('ville_id', $ville->id)
            ->orderByDesc('annee')
            ->limit(2)
            ->get();

        if ($populations->count() < 2) {
            return null;
        }

        $recent = $populations->first()->population;
        $ancien = $populations->last()->population;

        if ($ancien <= 0) {
            return null;
        }

        return round((($recent - $ancien) / $ancien) * 100, 2);
    }

    private function calculateBudgetStats(Ville $ville): array
    {
        $stats = [
            'budget_fonctionnement' => null,
            'budget_investissement' => null,
            'dette_totale' => null,
            'dette_par_habitant' => null,
            'taux_endettement_pct' => null,
            'capacite_autofinancement' => null,
        ];

        // Dernier budget disponible
        $budget = CommuneBudget::where('insee_code', $ville->code_insee)
            ->orderByDesc('annee')
            ->first();

        if (! $budget) {
            return $stats;
        }

        $stats['budget_fonctionnement'] = $budget->recettes_fonctionnement;
        $stats['budget_investissement'] = $budget->recettes_investissement;
        $stats['dette_totale'] = $budget->encours_dette;
        $stats['capacite_autofinancement'] = $budget->capacite_autofinancement;

        // Dette par habitant
        if ($budget->encours_dette && $ville->population > 0) {
            $stats['dette_par_habitant'] = round($budget->encours_dette / $ville->population, 2);
        }

        // Taux d'endettement (dette / recettes de fonctionnement)
        if ($budget->encours_dette && $budget->recettes_fonctionnement > 0) {
            $stats['taux_endettement_pct'] = round(($budget->encours_dette / $budget->recettes_fonctionnement) * 100, 2);
        }

        return $stats;
    }

    private function calculateMaireStats(Ville $ville): array
    {
        $stats = [
            'nb_maires_historique' => 0,
            'duree_moyenne_mandat_mois' => null,
        ];

        $mandats = MaireMandat::where('ville_id', $ville->id)->get();

        if ($mandats->isEmpty()) {
            return $stats;
        }

        $stats['nb_maires_historique'] = $mandats->count();

        // Durée moyenne des mandats terminés
        $mandatsTermines = $mandats->filter(fn ($m) => $m->date_fin !== null && $m->date_debut !== null);

        if ($mandatsTermines->isNotEmpty()) {
            $totalMois = $mandatsTermines->sum(fn ($m) => $m->date_debut->diffInMonths($m->date_fin));
            $stats['duree_moyenne_mandat_mois'] = round($totalMois / $mandatsTermines->count());
        }

        return $stats;
    }

    private function calculateScoreSanteFinanciere(array $stats): ?int
    {
        // Score de 0 à 100 basé sur plusieurs critères
        if (! isset($stats['taux_endettement_pct']) && ! isset($stats['dette_par_habitant'])) {
            return null;
        }

        $score = 50; // Base

        // Taux d'endettement (idéal < 80%)
        if (isset($stats['taux_endettement_pct'])) {
            $taux = $stats['taux_endettement_pct'];
            if ($taux < 50) {
                $score += 25;
            } elseif ($taux < 80) {
                $score += 15;
            } elseif ($taux < 100) {
                $score += 5;
            } elseif ($taux < 150) {
                $score -= 10;
            } else {
                $score -= 25;
            }
        }

        // Dette par habitant (référence: moyenne ~1000€)
        if (isset($stats['dette_par_habitant'])) {
            $dette = $stats['dette_par_habitant'];
            if ($dette < 500) {
                $score += 20;
            } elseif ($dette < 1000) {
                $score += 10;
            } elseif ($dette < 1500) {
                $score += 0;
            } elseif ($dette < 2000) {
                $score -= 10;
            } else {
                $score -= 20;
            }
        }

        // Évolution population positive = bonus
        if (isset($stats['evolution_population_5ans_pct']) && $stats['evolution_population_5ans_pct'] > 0) {
            $score += min(10, $stats['evolution_population_5ans_pct'] * 2);
        }

        return max(0, min(100, $score));
    }
}
