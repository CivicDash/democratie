<?php

namespace App\Console\Commands;

use App\Models\FrenchPostalCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Enrichit french_postal_codes avec les populations depuis geo.api.gouv.fr
 */
class EnrichPostalCodesPopulation extends Command
{
    protected $signature = 'enrich:postal-codes-population 
                            {--department= : Code département spécifique (ex: 75)}
                            {--force : Réimporter même si population existe}
                            {--batch-size=100 : Taille des lots}';

    protected $description = 'Enrichit les codes postaux avec les populations depuis geo.api.gouv.fr';

    private int $updated = 0;
    private int $errors = 0;

    public function handle(): int
    {
        $this->info('🏘️ Enrichissement des populations des communes');
        $this->newLine();

        $department = $this->option('department');
        $force = $this->option('force');
        $batchSize = (int) $this->option('batch-size');

        // Récupérer les codes INSEE uniques à enrichir
        $query = DB::table('french_postal_codes')
            ->select('insee_code')
            ->distinct();

        if ($department) {
            $query->where('department_code', $department);
        }

        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('population')
                  ->orWhere('population', 0);
            });
        }

        $inseeCodes = $query->pluck('insee_code')->unique()->values();

        $this->info("📊 {$inseeCodes->count()} communes à enrichir");
        $this->newLine();

        if ($inseeCodes->isEmpty()) {
            $this->info('✅ Toutes les communes ont déjà une population.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($inseeCodes->count());
        $bar->start();

        // Traiter par lots pour éviter de surcharger l'API
        foreach ($inseeCodes->chunk($batchSize) as $batch) {
            $this->processBatch($batch->toArray());
            $bar->advance($batch->count());
            
            // Pause pour ne pas surcharger l'API
            usleep(100000); // 100ms
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Terminé : {$this->updated} mises à jour, {$this->errors} erreurs");

        return self::SUCCESS;
    }

    private function processBatch(array $inseeCodes): void
    {
        foreach ($inseeCodes as $inseeCode) {
            try {
                $this->enrichCommune($inseeCode);
            } catch (\Exception $e) {
                $this->errors++;
                Log::warning("Erreur enrichissement {$inseeCode}: " . $e->getMessage());
            }
        }
    }

    private function enrichCommune(string $inseeCode): void
    {
        // Appel API geo.api.gouv.fr
        $response = Http::timeout(10)
            ->get("https://geo.api.gouv.fr/communes/{$inseeCode}", [
                'fields' => 'population,surface,codeEpci,nom',
            ]);

        if (!$response->successful()) {
            // Peut-être un code arrondissement, essayer sans
            if (preg_match('/^(75|69|13)(\d{3})$/', $inseeCode, $matches)) {
                // Paris/Lyon/Marseille : utiliser le code commune principal
                $codeCommune = match ($matches[1]) {
                    '75' => '75056', // Paris
                    '69' => preg_match('/^6938[1-9]$/', $inseeCode) ? '69123' : null, // Lyon
                    '13' => preg_match('/^132\d{2}$/', $inseeCode) ? '13055' : null, // Marseille
                    default => null,
                };

                if ($codeCommune) {
                    $response = Http::timeout(10)
                        ->get("https://geo.api.gouv.fr/communes/{$codeCommune}", [
                            'fields' => 'population,surface,codeEpci,nom',
                        ]);
                }
            }
        }

        if (!$response->successful()) {
            $this->errors++;
            return;
        }

        $data = $response->json();

        // Mettre à jour tous les enregistrements avec ce code INSEE
        $updateData = [];

        if (isset($data['population'])) {
            $updateData['population'] = $data['population'];
        }
        if (isset($data['surface'])) {
            // surface est en hectares, convertir en km²
            $updateData['superficie'] = round($data['surface'] / 100, 2);
        }
        if (isset($data['codeEpci'])) {
            $updateData['epci_code'] = $data['codeEpci'];
        }

        if (!empty($updateData)) {
            FrenchPostalCode::where('insee_code', $inseeCode)->update($updateData);
            $this->updated++;
        }
    }
}
