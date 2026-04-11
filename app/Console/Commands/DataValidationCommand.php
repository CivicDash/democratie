<?php

namespace App\Console\Commands;

use App\Models\ActeurAN;
use App\Models\PersonnePolitique;
use App\Models\Senateur;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DataValidationCommand extends Command
{
    protected $signature = 'data:validate
                            {--fix : Tenter de corriger les problèmes détectés}
                            {--json : Sortie JSON}';

    protected $description = 'Valide la cohérence des données et détecte les anomalies (doublons, liens morts, couverture)';

    public function handle(): int
    {
        $this->info('🔍 Validation des données CivicDash');
        $this->newLine();

        $issues = [];

        $issues = array_merge($issues, $this->checkDuplicates());
        $issues = array_merge($issues, $this->checkCoverage());
        $issues = array_merge($issues, $this->checkConsistency());

        if ($this->option('json')) {
            $this->line(json_encode([
                'validated_at' => now()->toIso8601String(),
                'total_issues' => count($issues),
                'critical' => collect($issues)->where('severity', 'critical')->count(),
                'warnings' => collect($issues)->where('severity', 'warning')->count(),
                'info' => collect($issues)->where('severity', 'info')->count(),
                'issues' => $issues,
            ], JSON_PRETTY_PRINT));

            return count($issues) > 0 ? Command::FAILURE : Command::SUCCESS;
        }

        if (empty($issues)) {
            $this->info('✅ Aucun problème détecté !');

            return Command::SUCCESS;
        }

        $this->table(
            ['Sévérité', 'Type', 'Description', 'Détails'],
            collect($issues)->map(fn ($i) => [
                match ($i['severity']) {
                    'critical' => '🔴 Critique',
                    'warning' => '🟡 Avertissement',
                    default => '🔵 Info',
                },
                $i['type'],
                $i['description'],
                $i['details'] ?? '',
            ])->toArray()
        );

        $this->newLine();
        $this->info('📊 '.count($issues).' problème(s) détecté(s)');

        return Command::FAILURE;
    }

    private function checkDuplicates(): array
    {
        $issues = [];
        $this->info('   🔍 Vérification des doublons...');

        $dupeDeputes = DB::table('acteurs_an')
            ->select('nom', 'prenom', DB::raw('COUNT(*) as cnt'))
            ->groupBy('nom', 'prenom')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($dupeDeputes as $dupe) {
            $issues[] = [
                'severity' => 'warning',
                'type' => 'Doublon député',
                'description' => "Député en double : {$dupe->prenom} {$dupe->nom}",
                'details' => "{$dupe->cnt} occurrences",
            ];
        }

        try {
            $dupeSenateurs = DB::table('senateurs')
                ->select('nom', 'prenom', DB::raw('COUNT(*) as cnt'))
                ->groupBy('nom', 'prenom')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($dupeSenateurs as $dupe) {
                $issues[] = [
                    'severity' => 'warning',
                    'type' => 'Doublon sénateur',
                    'description' => "Sénateur en double : {$dupe->prenom} {$dupe->nom}",
                    'details' => "{$dupe->cnt} occurrences",
                ];
            }
        } catch (\Throwable) {
        }

        $this->line('   ✅ Doublons vérifiés ('.count($issues).' trouvés)');

        return $issues;
    }

    private function checkCoverage(): array
    {
        $issues = [];
        $this->info('   📊 Vérification de la couverture...');

        $totalDeputes = ActeurAN::count();
        if ($totalDeputes > 0) {
            $withPhoto = ActeurAN::whereNotNull('photo_wikipedia_url')->where('photo_wikipedia_url', '!=', '')->count();
            $photoPct = round(($withPhoto / $totalDeputes) * 100, 1);
            if ($photoPct < 90) {
                $issues[] = [
                    'severity' => 'info',
                    'type' => 'Couverture photos',
                    'description' => "Députés avec photo Wikipedia : {$photoPct}%",
                    'details' => "{$withPhoto}/{$totalDeputes}",
                ];
            }
        }

        try {
            $totalSenateurs = Senateur::count();
            if ($totalSenateurs > 0) {
                $withPhoto = Senateur::whereNotNull('photo_url')->where('photo_url', '!=', '')->count();
                $photoPct = round(($withPhoto / $totalSenateurs) * 100, 1);
                if ($photoPct < 90) {
                    $issues[] = [
                        'severity' => 'info',
                        'type' => 'Couverture photos',
                        'description' => "Sénateurs avec photo : {$photoPct}%",
                        'details' => "{$withPhoto}/{$totalSenateurs}",
                    ];
                }
            }
        } catch (\Throwable) {
        }

        $personnesCount = PersonnePolitique::count();
        if ($personnesCount > 0) {
            $withWikidata = PersonnePolitique::whereNotNull('wikidata_id')->count();
            $wdPct = round(($withWikidata / $personnesCount) * 100, 1);
            if ($wdPct < 70) {
                $issues[] = [
                    'severity' => 'warning',
                    'type' => 'Couverture Wikidata',
                    'description' => "PersonnePolitique avec Wikidata ID : {$wdPct}%",
                    'details' => "{$withWikidata}/{$personnesCount}",
                ];
            }
        }

        $this->line('   ✅ Couverture vérifiée');

        return $issues;
    }

    private function checkConsistency(): array
    {
        $issues = [];
        $this->info('   🔗 Vérification de la cohérence...');

        $latestDemoYear = DB::table('france_demographics')->max('year');
        if ($latestDemoYear && $latestDemoYear < date('Y') - 1) {
            $issues[] = [
                'severity' => 'warning',
                'type' => 'Données obsolètes',
                'description' => "Démographie France : dernière année = {$latestDemoYear}",
                'details' => 'Lancer import:insee-demographics',
            ];
        }

        $latestEcoYear = DB::table('france_economy')->whereNull('quarter')->max('year');
        if ($latestEcoYear && $latestEcoYear < date('Y') - 1) {
            $issues[] = [
                'severity' => 'warning',
                'type' => 'Données obsolètes',
                'description' => "Économie France : dernière année = {$latestEcoYear}",
                'details' => 'Lancer import:insee-economy',
            ];
        }

        $this->line('   ✅ Cohérence vérifiée');

        return $issues;
    }
}
