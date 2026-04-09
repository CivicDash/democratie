<?php

namespace App\Console\Commands;

use App\Models\DossierLegislatifAN;
use App\Models\TexteLegislatifAN;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportDossiersTextesAN extends Command
{
    protected $signature = 'import:dossiers-textes-an 
                            {--legislature=17 : Législature à importer (par défaut: 17)}
                            {--all : Importer tous les dossiers (toutes législatures)}
                            {--fresh : Vide les tables avant l\'import}';

    protected $description = 'Importe les dossiers législatifs et textes depuis la structure amendements/';

    private int $dossiersImported = 0;

    private int $textesImported = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $legislature = $this->option('legislature');
        $importAll = $this->option('all');

        $this->info('🏛️  Import des dossiers législatifs et textes AN...');

        if ($importAll) {
            $this->warn('⚠️  Mode --all : import de TOUS les dossiers');
        } else {
            $this->info("📊 Législature cible : {$legislature}");
        }

        $basePath = public_path('data/amendements');

        if (! is_dir($basePath)) {
            $this->error("❌ Répertoire introuvable : {$basePath}");

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->warn('⚠️  Mode --fresh : suppression des données existantes...');
            TexteLegislatifAN::truncate();
            DossierLegislatifAN::truncate();
        }

        // Parcourir les dossiers DLR*
        $dossierDirs = File::directories($basePath);
        $dossierDirs = array_filter($dossierDirs, function ($dir) {
            return str_starts_with(basename($dir), 'DLR');
        });

        $this->info('📊 '.count($dossierDirs).' dossiers trouvés');
        $bar = $this->output->createProgressBar(count($dossierDirs));
        $bar->start();

        foreach ($dossierDirs as $dossierDir) {
            try {
                $this->importDossier($dossierDir, $legislature, $importAll);
            } catch (\Exception $e) {
                $this->errors++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->displaySummary($legislature, $importAll);

        return self::SUCCESS;
    }

    private function importDossier(string $dossierPath, int $legislature, bool $importAll): void
    {
        $dossierUid = basename($dossierPath);

        // Extraction législature depuis l'UID (ex: DLR5L17N51035 -> 17)
        preg_match('/L(\d+)N/', $dossierUid, $matches);
        $dossierLegislature = isset($matches[1]) ? (int) $matches[1] : null;

        // Filtrage par législature
        if (! $importAll && $dossierLegislature && $dossierLegislature !== (int) $legislature) {
            return;
        }

        // Extraction numéro depuis l'UID (ex: DLR5L17N51035 -> 51035)
        preg_match('/N(\d+)/', $dossierUid, $matchesNum);
        $numero = isset($matchesNum[1]) ? (int) $matchesNum[1] : null;

        // Créer le dossier
        $dossier = DossierLegislatifAN::updateOrCreate(
            ['uid' => $dossierUid],
            [
                'legislature' => $dossierLegislature,
                'numero' => $numero,
                'titre' => null, // Pas de titre dans la structure actuelle
                'date_creation' => null,
            ]
        );

        if ($dossier->wasRecentlyCreated) {
            $this->dossiersImported++;
        }

        // Parcourir les textes (PION*, PRJL*, etc.)
        $texteDirs = File::directories($dossierPath);

        foreach ($texteDirs as $texteDir) {
            $texteUid = basename($texteDir);

            // Filtrer les textes par préfixe (PION, PRJL, etc.)
            if (! preg_match('/^(PION|PRJL|RAPP|AVIS)/', $texteUid)) {
                continue;
            }

            $this->importTexte($texteUid, $dossierUid, $dossierLegislature);
        }
    }

    private function importTexte(string $texteUid, string $dossierUid, ?int $legislature): void
    {
        // Extraction type texte (ex: PIONANR5L17B0689 -> PION)
        preg_match('/^([A-Z]+)/', $texteUid, $matches);
        $typeTexte = $matches[1] ?? null;

        // Extraction numéro (ex: PIONANR5L17B0689 -> 689)
        preg_match('/B(\d+)/', $texteUid, $matchesNum);
        $numero = isset($matchesNum[1]) ? (int) $matchesNum[1] : null;

        // Créer le texte
        $texte = TexteLegislatifAN::updateOrCreate(
            ['uid' => $texteUid],
            [
                'dossier_ref' => $dossierUid,
                'legislature' => $legislature,
                'type_texte' => $typeTexte,
                'numero' => $numero,
                'titre' => null,
                'date_depot' => null,
            ]
        );

        if ($texte->wasRecentlyCreated) {
            $this->textesImported++;
        }
    }

    private function displaySummary(int $legislature, bool $importAll): void
    {
        $this->info('✅ Import terminé !');
        $this->newLine();
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✓ Dossiers importés', $this->dossiersImported],
                ['✓ Textes importés', $this->textesImported],
                ['⚠ Erreurs', $this->errors],
            ]
        );

        // Stats finales
        $totalDossiers = DossierLegislatifAN::count();
        $totalTextes = TexteLegislatifAN::count();

        if (! $importAll) {
            $dossiersLeg = DossierLegislatifAN::legislature($legislature)->count();
            $textesLeg = TexteLegislatifAN::legislature($legislature)->count();
        }

        $this->newLine();
        $this->info('📊 Total en base de données :');
        $this->info("   - Dossiers : {$totalDossiers}");
        $this->info("   - Textes : {$totalTextes}");

        if (! $importAll) {
            $this->newLine();
            $this->info("📊 Législature {$legislature} :");
            $this->info("   - Dossiers : {$dossiersLeg}");
            $this->info("   - Textes : {$textesLeg}");
        }
    }
}
