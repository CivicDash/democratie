<?php

namespace App\Console\Commands;

use App\Models\Maire;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportMairesFromCsv extends Command
{
    protected $signature = 'import:maires {--fresh : Vider la table des maires avant l\'import} {--limit= : Limiter le nombre d\'imports (pour test)}';

    protected $description = 'Importe les maires depuis le fichier CSV local (public/data/elus-maires-mai.csv)';

    public function handle()
    {
        $this->info('🏛️ Import des maires depuis fichier CSV local...');
        $this->newLine();

        if ($this->option('fresh')) {
            $this->warn('⚠️  Suppression des données des maires existants...');
            Maire::truncate();
        }

        // Chemin du fichier CSV local
        $csvPath = public_path('data/elus-maires-mai.csv');

        if (! file_exists($csvPath)) {
            $this->error('❌ Fichier CSV introuvable: '.$csvPath);

            return Command::FAILURE;
        }

        $limit = $this->option('limit');
        if ($limit) {
            $this->warn("⚠️  Mode TEST : Import limité à {$limit} lignes");
        }

        $this->info('📂 Lecture du fichier: '.basename($csvPath));
        $this->newLine();

        try {
            $handle = fopen($csvPath, 'r');

            if (! $handle) {
                $this->error('❌ Impossible d\'ouvrir le fichier CSV');

                return Command::FAILURE;
            }

            // Lire l'en-tête
            $header = fgetcsv($handle, 2000, ';');

            // Compter les lignes pour la barre de progression
            $totalLines = count(file($csvPath)) - 1;
            $lineCount = $limit ? min($limit, $totalLines) : $totalLines;

            $this->info("📊 {$lineCount} lignes à traiter (sur {$totalLines} au total)");
            $bar = $this->output->createProgressBar($lineCount);
            $bar->setFormat('verbose');

            $imported = 0;
            $updated = 0;
            $errors = 0;
            $batch = [];
            $batchSize = 500;

            $lineNumber = 0;
            while (($data = fgetcsv($handle, 2000, ';')) !== false) {
                if ($limit && $lineNumber >= $limit) {
                    break;
                }
                $lineNumber++;

                // Format CSV:
                // Code du département;Libellé du département;Code de la collectivité à statut particulier;
                // Libellé de la collectivité à statut particulier;Code de la commune;Libellé de la commune;
                // Nom de l'élu;Prénom de l'élu;Code sexe;Date de naissance;
                // Code de la catégorie socio-professionnelle;Libellé de la catégorie socio-professionnelle;
                // Date de début du mandat;Date de début de la fonction

                if (count($data) < 14) {
                    $errors++;
                    $bar->advance();

                    continue;
                }

                $deptCode = trim($data[0] ?? '');
                $deptName = trim($data[1] ?? '');
                $codeCommune = trim($data[4] ?? '');
                $nomCommune = trim($data[5] ?? '');
                $nom = trim($data[6] ?? '');
                $prenom = trim($data[7] ?? '');
                $sexeCode = trim($data[8] ?? '');
                $dateNaissance = trim($data[9] ?? '');
                $professionCode = trim($data[10] ?? '');
                $profession = trim($data[11] ?? '');
                $dateDebutMandat = trim($data[12] ?? '');
                $dateDebutFonction = trim($data[13] ?? '');

                if (empty($nom) || empty($prenom) || empty($codeCommune)) {
                    $errors++;
                    $bar->advance();

                    continue;
                }

                // Générer un UID unique basé sur code commune + nom + prénom
                $uid = 'MAIRE-'.strtoupper($codeCommune).'-'.$this->slugify($nom.'-'.$prenom);

                // Convertir les dates
                $dateNaissanceParsed = $this->parseDate($dateNaissance);
                $dateDebutMandatParsed = $this->parseDate($dateDebutMandat);
                $dateDebutFonctionParsed = $this->parseDate($dateDebutFonction);

                // Déterminer la civilité
                $civilite = $sexeCode === 'F' ? 'Mme' : 'M.';

                $batch[] = [
                    'uid' => $uid,
                    'nom' => strtoupper($nom),
                    'prenom' => ucwords(strtolower($prenom)),
                    'nom_complet' => $civilite.' '.ucwords(strtolower($prenom)).' '.strtoupper($nom),
                    'civilite' => $civilite,
                    'date_naissance' => $dateNaissanceParsed,
                    'code_commune' => $codeCommune,
                    'nom_commune' => ucwords(strtolower($nomCommune)),
                    'code_departement' => $deptCode,
                    'nom_departement' => $deptName,
                    'code_region' => null,
                    'nom_region' => null,
                    'profession' => $profession,
                    'categorie_socio_pro' => $professionCode,
                    'debut_mandat' => $dateDebutMandatParsed,
                    'debut_fonction' => $dateDebutFonctionParsed,
                    'fin_mandat' => null,
                    'en_exercice' => true,
                    'photo_url' => null,
                    'email' => null,
                    'telephone' => null,
                    'site_web' => null,
                    'adresse_mairie' => null,
                    'population_commune' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batch) >= $batchSize) {
                    $result = $this->insertBatch($batch);
                    $imported += $result['imported'];
                    $updated += $result['updated'];
                    $errors += $result['errors'];
                    $batch = [];
                    $bar->advance($batchSize);
                }
            }

            // Insérer le dernier batch
            if (! empty($batch)) {
                $result = $this->insertBatch($batch);
                $imported += $result['imported'];
                $updated += $result['updated'];
                $errors += $result['errors'];
                $bar->advance(count($batch));
            }

            fclose($handle);
            $bar->finish();
            $this->newLine(2);

            $this->info('✅ Import terminé !');
            $this->info("   ✓ {$imported} maires importés");
            $this->info("   ↻ {$updated} maires mis à jour");
            if ($errors > 0) {
                $this->warn("   ⚠️  {$errors} lignes ignorées");
            }

            // Vérification finale
            $total = Maire::count();
            $totalEnExercice = Maire::where('en_exercice', true)->count();
            $this->newLine();
            $this->info("📊 Total en base: {$total} maires ({$totalEnExercice} en exercice)");

            // Stats par département (top 5)
            $this->newLine();
            $this->info('📊 Top 5 départements:');
            $topDepts = Maire::select('code_departement', 'nom_departement', DB::raw('COUNT(*) as total'))
                ->groupBy('code_departement', 'nom_departement')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            foreach ($topDepts as $dept) {
                $this->line("   {$dept->code_departement} - {$dept->nom_departement}: {$dept->total} maires");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de l\'import: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    /**
     * Insérer un batch de données
     */
    private function insertBatch(array $batch): array
    {
        $imported = 0;
        $updated = 0;
        $errors = 0;

        foreach ($batch as $record) {
            try {
                $maire = Maire::updateOrCreate(
                    [
                        'uid' => $record['uid'],
                    ],
                    $record
                );

                if ($maire->wasRecentlyCreated) {
                    $imported++;
                } else {
                    $updated++;
                }

            } catch (\Exception $e) {
                $errors++;
                // Log seulement les premières erreurs
                if ($errors < 3) {
                    $this->warn('⚠️  Erreur: '.$e->getMessage());
                }
            }
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    /**
     * Parse une date au format DD/MM/YYYY
     */
    private function parseDate(?string $date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Format: DD/MM/YYYY
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
                return Carbon::createFromFormat('d/m/Y', $date);
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Slugify une chaîne pour l'UID
     */
    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        return $text;
    }
}
