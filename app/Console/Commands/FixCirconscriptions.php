<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixCirconscriptions extends Command
{
    protected $signature = 'fix:circonscriptions
                            {--dry-run : Afficher les changements sans les appliquer}
                            {--departement= : Corriger uniquement un département (ex: 39)}';

    protected $description = 'Corrige les circonscriptions législatives des communes à partir du fichier officiel du Ministère de l\'Intérieur';

    public function handle(): int
    {
        $csvPath = public_path('data/circo_legislatives_2017.csv');

        if (! file_exists($csvPath)) {
            $this->error("Fichier introuvable : {$csvPath}");

            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');
        $filterDept = $this->option('departement');

        if ($dryRun) {
            $this->warn('Mode dry-run : aucune modification ne sera appliquée.');
        }

        $this->info('📋 Lecture du fichier de correspondance...');

        $mapping = $this->loadMapping($csvPath, $filterDept);
        $this->info(sprintf('   %d communes chargées depuis le fichier officiel.', count($mapping)));

        $fixedPostal = 0;
        $fixedVilles = 0;
        $mismatchPostal = 0;
        $mismatchVilles = 0;

        // Fix french_postal_codes
        $this->info('');
        $this->info('🔧 Correction de french_postal_codes...');

        $query = DB::table('french_postal_codes')->whereNotNull('insee_code');
        if ($filterDept) {
            $query->where('department_code', $filterDept);
        }

        $postalCodes = $query->get(['id', 'insee_code', 'city_name', 'department_code', 'circonscription']);
        $bar = $this->output->createProgressBar($postalCodes->count());

        foreach ($postalCodes as $pc) {
            $bar->advance();
            $insee = $pc->insee_code;

            if (! isset($mapping[$insee])) {
                continue;
            }

            $correctCirco = $pc->department_code.'-'.str_pad($mapping[$insee], 2, '0', STR_PAD_LEFT);

            if ($pc->circonscription !== $correctCirco) {
                $mismatchPostal++;
                if (! $dryRun) {
                    DB::table('french_postal_codes')
                        ->where('id', $pc->id)
                        ->update(['circonscription' => $correctCirco, 'updated_at' => now()]);
                    $fixedPostal++;
                } elseif ($this->output->isVerbose()) {
                    $this->newLine();
                    $this->line("   {$pc->city_name} ({$insee}): {$pc->circonscription} → {$correctCirco}");
                }
            }
        }

        $bar->finish();
        $this->newLine();

        // Fix villes
        $this->info('');
        $this->info('🔧 Correction de villes...');

        $villesQuery = DB::table('villes')->whereNotNull('code_insee');
        if ($filterDept) {
            $villesQuery->where('departement_code', $filterDept);
        }

        $villes = $villesQuery->get(['id', 'code_insee', 'nom', 'departement_code', 'circonscription']);
        $bar2 = $this->output->createProgressBar($villes->count());

        foreach ($villes as $ville) {
            $bar2->advance();
            $insee = $ville->code_insee;

            if (! isset($mapping[$insee])) {
                continue;
            }

            $correctCirco = $ville->departement_code.'-'.str_pad($mapping[$insee], 2, '0', STR_PAD_LEFT);

            if ($ville->circonscription !== $correctCirco) {
                $mismatchVilles++;
                if (! $dryRun) {
                    DB::table('villes')
                        ->where('id', $ville->id)
                        ->update(['circonscription' => $correctCirco, 'updated_at' => now()]);
                    $fixedVilles++;
                } elseif ($this->output->isVerbose()) {
                    $this->newLine();
                    $this->line("   {$ville->nom} ({$insee}): {$ville->circonscription} → {$correctCirco}");
                }
            }
        }

        $bar2->finish();
        $this->newLine(2);

        // Summary
        $this->info('✅ Résultat :');
        $this->table(
            ['Table', 'Erreurs détectées', 'Corrigées'],
            [
                ['french_postal_codes', $mismatchPostal, $dryRun ? '0 (dry-run)' : $fixedPostal],
                ['villes', $mismatchVilles, $dryRun ? '0 (dry-run)' : $fixedVilles],
            ]
        );

        if ($dryRun && ($mismatchPostal + $mismatchVilles) > 0) {
            $this->warn('Relancez sans --dry-run pour appliquer les corrections.');
        }

        return self::SUCCESS;
    }

    /**
     * Charge le mapping INSEE -> numéro de circo depuis le CSV
     */
    private function loadMapping(string $csvPath, ?string $filterDept): array
    {
        $mapping = [];
        $handle = fopen($csvPath, 'r');
        fgetcsv($handle); // skip header

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 5) {
                continue;
            }

            [$deptCode, $deptName, $comCode, $comName, $circo] = $row;

            if ($filterDept && $deptCode !== $filterDept) {
                continue;
            }

            $insee = $deptCode.$comCode;
            $mapping[$insee] = (int) $circo;
        }

        fclose($handle);

        return $mapping;
    }
}
