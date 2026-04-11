<?php

namespace App\Console\Commands;

use App\Models\ActeurAN;
use App\Models\AmendementAN;
use App\Models\CommuneBudget;
use App\Models\DossierLegislatifAN;
use App\Models\DossierLegislatifSenat;
use App\Models\FranceDemographics;
use App\Models\FranceEconomy;
use App\Models\HatvpDeclaration;
use App\Models\ImportLog;
use App\Models\Maire;
use App\Models\QuestionAN;
use App\Models\ScrutinAN;
use App\Models\Senateur;
use Illuminate\Console\Command;

class DataHealthCheckCommand extends Command
{
    protected $signature = 'data:health-check
                            {--json : Sortie JSON}';

    protected $description = 'Vérifie la fraîcheur et la complétude de toutes les sources de données';

    private const SOURCES = [
        [
            'name' => 'Députés (AN)',
            'model' => ActeurAN::class,
            'command' => 'import:acteurs-an',
            'source' => 'an',
            'expected_min' => 500,
        ],
        [
            'name' => 'Sénateurs',
            'model' => Senateur::class,
            'command' => 'senat:sync',
            'source' => 'senat',
            'expected_min' => 300,
        ],
        [
            'name' => 'Scrutins AN',
            'model' => ScrutinAN::class,
            'command' => 'import:scrutins-an',
            'source' => 'an',
            'expected_min' => 100,
        ],
        [
            'name' => 'Amendements AN',
            'model' => AmendementAN::class,
            'command' => 'import:amendements-an',
            'source' => 'an',
            'expected_min' => 1000,
        ],
        [
            'name' => 'Dossiers AN',
            'model' => DossierLegislatifAN::class,
            'command' => 'import:dossiers-textes-an',
            'source' => 'an',
            'expected_min' => 50,
        ],
        [
            'name' => 'Dossiers Sénat',
            'model' => DossierLegislatifSenat::class,
            'command' => 'import:dossiers-senat',
            'source' => 'senat',
            'expected_min' => 50,
        ],
        [
            'name' => 'Questions AN',
            'model' => QuestionAN::class,
            'command' => 'import:questions-an',
            'source' => 'an',
            'expected_min' => 500,
        ],
        [
            'name' => 'HATVP',
            'model' => HatvpDeclaration::class,
            'command' => 'hatvp:sync',
            'source' => 'hatvp',
            'expected_min' => 100,
        ],
        [
            'name' => 'Maires',
            'model' => Maire::class,
            'command' => 'import:maires-datagouv',
            'source' => 'system',
            'expected_min' => 30000,
        ],
        [
            'name' => 'Budgets communes',
            'model' => CommuneBudget::class,
            'command' => 'import:commune-budgets',
            'source' => 'system',
            'expected_min' => 10000,
        ],
        [
            'name' => 'Démographie France',
            'model' => FranceDemographics::class,
            'command' => 'import:insee-demographics',
            'source' => 'insee',
            'expected_min' => 3,
        ],
        [
            'name' => 'Économie France',
            'model' => FranceEconomy::class,
            'command' => 'import:insee-economy',
            'source' => 'insee',
            'expected_min' => 3,
        ],
    ];

    public function handle(): int
    {
        $this->info('🏥 Health Check des données CivicDash');
        $this->newLine();

        $results = [];
        $warnings = 0;
        $errors = 0;

        foreach (self::SOURCES as $source) {
            $result = $this->checkSource($source);
            $results[] = $result;

            if ($result['status'] === 'error') {
                $errors++;
            } elseif ($result['status'] === 'warning') {
                $warnings++;
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'checked_at' => now()->toIso8601String(),
                'total' => count($results),
                'healthy' => count($results) - $warnings - $errors,
                'warnings' => $warnings,
                'errors' => $errors,
                'sources' => $results,
            ], JSON_PRETTY_PRINT));

            return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
        }

        $this->table(
            ['Source', 'Enregistrements', 'Attendu min', 'Dernier import', 'Fraîcheur', 'Statut'],
            collect($results)->map(fn ($r) => [
                $r['name'],
                number_format($r['count']),
                number_format($r['expected_min']),
                $r['last_import'] ?? '—',
                $r['freshness_label'],
                $r['status_icon'],
            ])->toArray()
        );

        $this->newLine();
        $total = count($results);
        $healthy = $total - $warnings - $errors;
        $this->info("📊 Résumé : {$healthy}/{$total} sources saines, {$warnings} avertissements, {$errors} erreurs");

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function checkSource(array $source): array
    {
        $modelClass = $source['model'];
        $count = $modelClass::count();

        $lastLog = ImportLog::where('command', $source['command'])
            ->where('status', '!=', ImportLog::STATUS_RUNNING)
            ->orderBy('finished_at', 'desc')
            ->first();

        $lastImport = $lastLog?->finished_at;
        $daysSinceImport = $lastImport ? (int) now()->diffInDays($lastImport) : null;

        if ($daysSinceImport === null) {
            $freshnessLabel = '❓ Jamais importé';
            $freshness = 'unknown';
        } elseif ($daysSinceImport <= 1) {
            $freshnessLabel = '🟢 < 24h';
            $freshness = 'fresh';
        } elseif ($daysSinceImport <= 7) {
            $freshnessLabel = "🟡 {$daysSinceImport}j";
            $freshness = 'stale';
        } else {
            $freshnessLabel = "🔴 {$daysSinceImport}j";
            $freshness = 'old';
        }

        $status = 'ok';
        $statusIcon = '✅';

        if ($count < $source['expected_min']) {
            $status = 'warning';
            $statusIcon = '⚠️  Données incomplètes';
        }
        if ($count === 0) {
            $status = 'error';
            $statusIcon = '❌ Vide';
        }
        if ($freshness === 'old') {
            $status = 'warning';
            $statusIcon = '⚠️  Périmé';
        }
        if ($lastLog?->status === ImportLog::STATUS_FAILED) {
            $status = 'error';
            $statusIcon = '❌ Dernier import échoué';
        }

        return [
            'name' => $source['name'],
            'command' => $source['command'],
            'count' => $count,
            'expected_min' => $source['expected_min'],
            'last_import' => $lastImport?->format('d/m/Y H:i'),
            'last_import_status' => $lastLog?->status,
            'days_since_import' => $daysSinceImport,
            'freshness' => $freshness,
            'freshness_label' => $freshnessLabel,
            'status' => $status,
            'status_icon' => $statusIcon,
        ];
    }
}
