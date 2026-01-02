<?php

namespace App\Console\Commands;

use App\Models\BudgetMinistere;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportPlfMinisteres extends Command
{
    protected $signature = 'import:plf-ministeres 
                            {--year= : Année spécifique à importer}
                            {--all : Importer toutes les années disponibles}
                            {--force : Écraser les données existantes}';

    protected $description = 'Importe les données PLF (Projet de Loi de Finances) par ministère depuis les fichiers CSV';

    private array $fichiers = [
        2022 => 'Budget_Loi_Finance_2022.csv',
        2023 => 'Budget_Loi_Finance_2023.csv',
        2024 => 'Budget_Loi_Finance_2024.csv',
        2025 => 'Budget_Loi_Finance_2025.csv',
        2026 => 'Projet_Loi_Finance_2026.csv',
    ];

    public function handle(): int
    {
        $this->info('📊 Import des données PLF par ministère');
        $this->newLine();

        $year = $this->option('year');
        $all = $this->option('all');
        $force = $this->option('force');

        if ($year) {
            $annees = [(int) $year];
        } elseif ($all) {
            $annees = array_keys($this->fichiers);
        } else {
            // Par défaut, importer toutes les années
            $annees = array_keys($this->fichiers);
        }

        $totalImported = 0;

        foreach ($annees as $annee) {
            if (!isset($this->fichiers[$annee])) {
                $this->warn("⚠️  Fichier non disponible pour l'année {$annee}");
                continue;
            }

            $fichier = database_path("data/plf/{$this->fichiers[$annee]}");
            
            if (!file_exists($fichier)) {
                $this->error("❌ Fichier introuvable : {$fichier}");
                continue;
            }

            $this->info("📅 Année {$annee} : {$this->fichiers[$annee]}");
            $imported = $this->importerFichier($fichier, $annee, $force);
            $totalImported += $imported;
            $this->info("   → {$imported} ministères importés");
        }

        $this->newLine();
        $this->info("✅ Import terminé : {$totalImported} enregistrements");

        return Command::SUCCESS;
    }

    private function importerFichier(string $fichier, int $annee, bool $force): int
    {
        $handle = fopen($fichier, 'r');
        if (!$handle) {
            return 0;
        }

        // Lire l'en-tête
        $header = fgetcsv($handle, 0, ';');
        if (!$header) {
            fclose($handle);
            return 0;
        }

        $count = 0;
        
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            // Ignorer les lignes vides
            if (empty($row[0]) || trim($row[0]) === '') {
                continue;
            }

            $nom = trim($row[0]);
            $budgetGeneral = $this->parseNumber($row[1] ?? '');
            $budgetsAnnexes = $this->parseNumber($row[2] ?? '');
            $comptesAffectation = $this->parseNumber($row[3] ?? '');
            $comptesConcours = $this->parseNumber($row[4] ?? '');

            // Calculer le total
            $total = ($budgetGeneral ?? 0) + ($budgetsAnnexes ?? 0) + 
                     ($comptesAffectation ?? 0) + ($comptesConcours ?? 0);

            // Générer le code à partir du nom
            $code = Str::slug($nom);

            // Déterminer le type de loi
            $typeLoi = str_contains($this->fichiers[$annee], 'Projet') ? 'plf' : 'lfi';

            $data = [
                'nom' => $nom,
                'sigle' => $this->extraireSigle($nom),
                'annee' => $annee,
                'type_loi' => $typeLoi,
                'budget_general' => $budgetGeneral,
                'budgets_annexes' => $budgetsAnnexes,
                'comptes_affectation_speciale' => $comptesAffectation,
                'comptes_concours_financiers' => $comptesConcours,
                'budget_total' => $total > 0 ? $total : null,
                'budget_cp' => $budgetGeneral, // Pour compatibilité
                'couleur' => BudgetMinistere::getCouleur($nom),
                'source' => 'PLF ' . $annee,
            ];

            if ($force) {
                BudgetMinistere::updateOrCreate(
                    ['code' => $code, 'annee' => $annee, 'type_loi' => $typeLoi],
                    $data
                );
            } else {
                BudgetMinistere::firstOrCreate(
                    ['code' => $code, 'annee' => $annee, 'type_loi' => $typeLoi],
                    $data
                );
            }

            $count++;
        }

        fclose($handle);
        return $count;
    }

    private function parseNumber(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        // Remplacer la virgule par un point et supprimer les espaces
        $value = str_replace([',', ' '], ['.', ''], trim($value));
        
        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function extraireSigle(string $nom): ?string
    {
        $sigles = [
            'Économie' => 'MEFSIN',
            'Armées' => 'MINARM',
            'Justice' => 'MJ',
            'Intérieur' => 'MI',
            'Éducation' => 'MENJ',
            'Culture' => 'MC',
            'Travail' => 'MTPEI',
            'Agriculture' => 'MAA',
            'Transition écologique' => 'MTE',
            'Europe et affaires étrangères' => 'MEAE',
            'Solidarités' => 'MSS',
            'Enseignement supérieur' => 'MESR',
            'Services du Premier ministre' => 'SPM',
            'Outre-mer' => 'MOM',
            'Sports' => 'MS',
            'Santé' => 'MSS',
        ];

        foreach ($sigles as $key => $sigle) {
            if (str_contains($nom, $key)) {
                return $sigle;
            }
        }

        return null;
    }
}
