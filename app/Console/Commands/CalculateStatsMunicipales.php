<?php

namespace App\Console\Commands;

use App\Models\Maire;
use App\Models\ResultatListeMunicipale;
use App\Models\ResultatMunicipal;
use App\Models\StatsElectionMunicipale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateStatsMunicipales extends Command
{
    protected $signature = 'municipales:calculate-stats
                            {--annee=2026 : Année de l\'élection}';

    protected $description = 'Calcule les statistiques agrégées des élections municipales';

    public function handle(): int
    {
        $annee = (int) $this->option('annee');
        $this->info("Calcul des statistiques municipales {$annee}...");

        $this->calculateScope('national', null, $annee);

        $departements = ResultatMunicipal::select('code_departement')
            ->distinct()
            ->pluck('code_departement');

        $bar = $this->output->createProgressBar($departements->count() + 1);
        $bar->start();
        $bar->advance();

        foreach ($departements as $codeDept) {
            $this->calculateScope('departement', $codeDept, $annee);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $stats = StatsElectionMunicipale::national($annee)->first();
        if ($stats) {
            $data = $stats->data;
            $this->info('Résumé national :');
            $this->table(['Métrique', 'Valeur'], [
                ['Communes', $data['communes']['total'] ?? '-'],
                ['Élues T1', $data['communes']['elues_t1'] ?? '-'],
                ['Second tour', $data['communes']['second_tour'] ?? '-'],
                ['Participation T1', ($data['participation']['t1']['taux'] ?? '-') . ' %'],
                ['Taux femmes maires', ($data['parite_maires']['taux_femmes'] ?? '-') . ' %'],
                ['Taux réélection', ($data['renouvellement']['taux_reelection'] ?? '-') . ' %'],
            ]);
        }

        return self::SUCCESS;
    }

    private function calculateScope(string $scope, ?string $scopeCode, int $annee): void
    {
        $query = ResultatMunicipal::query();
        if ($scopeCode) {
            $query->where('code_departement', $scopeCode);
        }

        $participation = $this->calculateParticipation($query->clone());
        $communes = $this->calculateCommunes($query->clone());
        $nuances = $this->calculateNuances($query->clone());
        $parite = $this->calculateParite($scopeCode);
        $renouvellement = $this->calculateRenouvellement($scopeCode);

        $data = [
            'participation' => $participation,
            'communes' => $communes,
            'nuances' => $nuances,
            'parite_maires' => $parite,
            'renouvellement' => $renouvellement,
        ];

        StatsElectionMunicipale::updateOrCreate(
            [
                'annee' => $annee,
                'scope' => $scope,
                'scope_code' => $scopeCode,
            ],
            [
                'data' => $data,
                'calculated_at' => now(),
            ]
        );
    }

    private function calculateParticipation($query): array
    {
        $result = [];

        foreach ([1, 2] as $tour) {
            $tourData = (clone $query)->where('tour', $tour);

            $agg = $tourData->selectRaw('
                SUM(inscrits) as inscrits,
                SUM(votants) as votants,
                SUM(abstentions) as abstentions,
                SUM(blancs) as blancs,
                SUM(nuls) as nuls,
                SUM(exprimes) as exprimes
            ')->first();

            if ($agg && $agg->inscrits > 0) {
                $result["t{$tour}"] = [
                    'inscrits' => (int) $agg->inscrits,
                    'votants' => (int) $agg->votants,
                    'abstentions' => (int) $agg->abstentions,
                    'taux' => round(($agg->votants / $agg->inscrits) * 100, 2),
                    'abstention' => round(($agg->abstentions / $agg->inscrits) * 100, 2),
                ];
            }
        }

        return $result;
    }

    private function calculateCommunes($query): array
    {
        return [
            'total' => (clone $query)->where('tour', 1)->count(),
            'elues_t1' => (clone $query)->where('statut_commune', 'elu_t1')->count(),
            'second_tour' => (clone $query)->where('statut_commune', 'second_tour')->count(),
            'elues_t2' => (clone $query)->where('statut_commune', 'elu_t2')->count(),
            'sans_candidat' => (clone $query)->where('statut_commune', 'sans_candidat')->count(),
        ];
    }

    private function calculateNuances($query): array
    {
        $resultatIds = (clone $query)->pluck('id');

        return ResultatListeMunicipale::whereIn('resultat_commune_id', $resultatIds)
            ->whereNotNull('nuance_politique')
            ->where('nuance_politique', '!=', '')
            ->select('nuance_politique')
            ->selectRaw('COUNT(*) as listes_total')
            ->selectRaw('SUM(CASE WHEN elu = true THEN 1 ELSE 0 END) as communes_gagnees')
            ->selectRaw('SUM(voix) as voix_total')
            ->groupBy('nuance_politique')
            ->orderByDesc('communes_gagnees')
            ->get()
            ->keyBy('nuance_politique')
            ->map(fn($row) => [
                'listes_total' => (int) $row->listes_total,
                'communes_gagnees' => (int) $row->communes_gagnees,
                'voix_total' => (int) $row->voix_total,
            ])
            ->toArray();
    }

    private function calculateParite(?string $codeDept): array
    {
        $query = Maire::where('mandature', '2026-2032')->where('en_exercice', true);
        if ($codeDept) {
            $query->where('code_departement', $codeDept);
        }

        $total = (clone $query)->count();
        $femmes = (clone $query)->where('civilite', 'Mme')->count();
        $hommes = $total - $femmes;
        $tauxFemmes = $total > 0 ? round(($femmes / $total) * 100, 1) : 0;

        return [
            'hommes' => $hommes,
            'femmes' => $femmes,
            'taux_femmes' => $tauxFemmes,
        ];
    }

    private function calculateRenouvellement(?string $codeDept): array
    {
        $query = Maire::where('mandature', '2026-2032')->where('en_exercice', true);
        if ($codeDept) {
            $query->where('code_departement', $codeDept);
        }

        $total = (clone $query)->count();
        $reelus = (clone $query)->where('reelu', true)->count();
        $nouveaux = (clone $query)->where('reelu', false)->count();

        return [
            'sortants_reelus' => $reelus,
            'nouveaux' => $nouveaux,
            'taux_reelection' => $total > 0 ? round(($reelus / $total) * 100, 1) : 0,
        ];
    }
}
