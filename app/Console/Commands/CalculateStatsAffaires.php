<?php

namespace App\Console\Commands;

use App\Models\ActeurAN;
use App\Models\AffaireJudiciaire;
use App\Models\Maire;
use App\Models\Senateur;
use App\Models\StatsAffaireJudiciaire;
use Illuminate\Console\Command;

class CalculateStatsAffaires extends Command
{
    protected $signature = 'affaires:calculate-stats';

    protected $description = 'Calcule les statistiques des affaires judiciaires (global, par parti, par type de mandat)';

    public function handle(): int
    {
        $this->info('Calcul des statistiques affaires judiciaires...');

        $this->calculateGlobal();
        $this->calculateParParti();
        $this->calculateParTypeMandat();
        $this->calculateParTypeAffaire();

        $this->info('Statistiques calculées avec succès.');

        return self::SUCCESS;
    }

    private function calculateGlobal(): void
    {
        $this->info('  → Statistiques globales...');

        $validees = AffaireJudiciaire::publiques();
        $total = (clone $validees)->count();
        $personnes = (clone $validees)->select('nom', 'prenom')->distinct()->count();

        $parStatut = [];
        foreach (AffaireJudiciaire::STATUTS_JUDICIAIRES() as $statut) {
            $parStatut[$statut] = (clone $validees)->where('statut_judiciaire', $statut)->count();
        }

        $parCategorie = [];
        foreach (AffaireJudiciaire::CATEGORIES() as $cat) {
            $parCategorie[$cat] = (clone $validees)->where('categorie', $cat)->count();
        }

        $parType = [];
        foreach (AffaireJudiciaire::TYPES_AFFAIRE() as $type) {
            $count = (clone $validees)->where('type_affaire', $type)->count();
            if ($count > 0) {
                $parType[$type] = $count;
            }
        }

        StatsAffaireJudiciaire::updateOrCreate(
            ['scope' => 'global', 'scope_value' => null],
            [
                'data' => [
                    'totaux' => [
                        'validees' => $total,
                        'personnes' => $personnes,
                        'condamnations_definitives' => $parStatut['condamne_definitif'] ?? 0,
                    ],
                    'par_statut' => $parStatut,
                    'par_categorie' => $parCategorie,
                    'par_type' => $parType,
                ],
                'calculated_at' => now(),
            ]
        );
    }

    private function calculateParParti(): void
    {
        $this->info('  → Statistiques par parti...');

        $partis = AffaireJudiciaire::publiques()
            ->whereNotNull('parti_politique')
            ->select('parti_politique')
            ->distinct()
            ->pluck('parti_politique');

        $totalDeputesActifs = ActeurAN::deputes()->count();
        $totalSenateursActifs = Senateur::actifs()->count();
        $totalElus = $totalDeputesActifs + $totalSenateursActifs;

        foreach ($partis as $parti) {
            $affaires = AffaireJudiciaire::publiques()->byParti($parti);
            $totalAffaires = (clone $affaires)->count();
            $condamnations = (clone $affaires)->definitives()->count();

            $totalElusParti = $this->countElusParti($parti);

            $parType = [];
            foreach (AffaireJudiciaire::TYPES_AFFAIRE() as $type) {
                $count = (clone $affaires)->where('type_affaire', $type)->count();
                if ($count > 0) {
                    $parType[$type] = $count;
                }
            }

            $parCategorie = [];
            foreach (AffaireJudiciaire::CATEGORIES() as $cat) {
                $count = (clone $affaires)->where('categorie', $cat)->count();
                if ($count > 0) {
                    $parCategorie[$cat] = $count;
                }
            }

            $gravites = (clone $affaires)->get()->map->gravite_score;

            StatsAffaireJudiciaire::updateOrCreate(
                ['scope' => 'parti', 'scope_value' => $parti],
                [
                    'data' => [
                        'total_affaires' => $totalAffaires,
                        'total_elus_parti' => $totalElusParti,
                        'ratio_affaires_pour_100' => $totalElusParti > 0
                            ? round(($totalAffaires / $totalElusParti) * 100, 2)
                            : null,
                        'condamnations_definitives' => $condamnations,
                        'ratio_condamnations_pour_100' => $totalElusParti > 0
                            ? round(($condamnations / $totalElusParti) * 100, 2)
                            : null,
                        'par_type' => $parType,
                        'par_categorie' => $parCategorie,
                        'gravite_moyenne' => $gravites->isNotEmpty()
                            ? round($gravites->avg(), 1)
                            : null,
                    ],
                    'calculated_at' => now(),
                ]
            );
        }
    }

    private function calculateParTypeMandat(): void
    {
        $this->info('  → Statistiques par type de mandat...');

        $types = [
            'depute' => [
                'total_elus' => ActeurAN::deputes()->count(),
                'query' => fn () => AffaireJudiciaire::publiques()->whereNotNull('acteur_an_uid'),
            ],
            'senateur' => [
                'total_elus' => Senateur::actifs()->count(),
                'query' => fn () => AffaireJudiciaire::publiques()->whereNotNull('senateur_matricule'),
            ],
            'ministre' => [
                'total_elus' => \App\Models\PersonnePolitique::actifs()->count(),
                'query' => fn () => AffaireJudiciaire::publiques()->whereNotNull('personne_politique_id'),
            ],
            'maire' => [
                'total_elus' => Maire::enExercice()->count(),
                'query' => fn () => AffaireJudiciaire::publiques()->whereNotNull('maire_id'),
            ],
        ];

        foreach ($types as $typeMandat => $config) {
            $affaires = $config['query']();
            $totalAffaires = (clone $affaires)->count();
            $totalElus = $config['total_elus'];

            $parParti = [];
            $partis = (clone $affaires)->whereNotNull('parti_politique')
                ->select('parti_politique')
                ->distinct()
                ->pluck('parti_politique');

            foreach ($partis as $parti) {
                $count = (clone $affaires)->where('parti_politique', $parti)->count();
                if ($count > 0) {
                    $parParti[$parti] = $count;
                }
            }

            $parType = [];
            foreach (AffaireJudiciaire::TYPES_AFFAIRE() as $type) {
                $count = (clone $affaires)->where('type_affaire', $type)->count();
                if ($count > 0) {
                    $parType[$type] = $count;
                }
            }

            StatsAffaireJudiciaire::updateOrCreate(
                ['scope' => 'type_mandat', 'scope_value' => $typeMandat],
                [
                    'data' => [
                        'total_affaires' => $totalAffaires,
                        'total_elus' => $totalElus,
                        'ratio_pour_100' => $totalElus > 0
                            ? round(($totalAffaires / $totalElus) * 100, 2)
                            : null,
                        'par_parti' => $parParti,
                        'par_type' => $parType,
                    ],
                    'calculated_at' => now(),
                ]
            );
        }
    }

    private function calculateParTypeAffaire(): void
    {
        $this->info('  → Statistiques par type d\'affaire...');

        foreach (AffaireJudiciaire::TYPES_AFFAIRE() as $type) {
            $affaires = AffaireJudiciaire::publiques()->where('type_affaire', $type);
            $total = (clone $affaires)->count();

            if ($total === 0) {
                continue;
            }

            $parParti = [];
            $partis = (clone $affaires)->whereNotNull('parti_politique')
                ->select('parti_politique')
                ->distinct()
                ->pluck('parti_politique');

            foreach ($partis as $parti) {
                $parParti[$parti] = (clone $affaires)->where('parti_politique', $parti)->count();
            }

            StatsAffaireJudiciaire::updateOrCreate(
                ['scope' => 'type_affaire', 'scope_value' => $type],
                [
                    'data' => [
                        'total' => $total,
                        'par_parti' => $parParti,
                        'condamnations_definitives' => (clone $affaires)->definitives()->count(),
                    ],
                    'calculated_at' => now(),
                ]
            );
        }
    }

    private function countElusParti(string $parti): int
    {
        $deputes = ActeurAN::deputes()
            ->whereHas('mandats', function ($q) use ($parti) {
                $q->where('type_organe', 'GP')
                  ->whereNull('date_fin')
                  ->whereHas('organe', fn ($o) => $o->where('libelle_abrege', $parti));
            })
            ->count();

        $senateurs = Senateur::actifs()
            ->where('groupe_politique', 'LIKE', "%{$parti}%")
            ->count();

        return $deputes + $senateurs;
    }
}
