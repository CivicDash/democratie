<?php

namespace App\Console\Commands;

use App\Models\Senateur;
use App\Models\SenateurQuestion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class ImportQuestionsSenat extends Command
{
    protected $signature = 'import:questions-senat
                            {--fresh : Vider la table avant import}
                            {--limit= : Limite du nombre de questions}
                            {--sync-only : Synchroniser depuis les tables SQL existantes}
                            {--year= : Année spécifique (défaut: 5 dernières années)}';

    protected $description = 'Importe les questions au Gouvernement des sénateurs depuis data.senat.fr';

    private int $imported = 0;

    private int $updated = 0;

    private int $skipped = 0;

    private int $errors = 0;

    private string $zipUrl = 'https://data.senat.fr/data/questions/questions.zip';

    private string $schemaName = 'questions';

    public function handle(): int
    {
        $fresh = $this->option('fresh');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $syncOnly = $this->option('sync-only');
        $year = $this->option('year') ? (int) $this->option('year') : null;

        $this->info('🏛️  Import des Questions au Gouvernement - Sénat');
        $this->newLine();

        if ($fresh) {
            $this->warn('⚠️  Mode --fresh : suppression des questions existantes...');
            SenateurQuestion::truncate();
        }

        // Vérifier si les tables SQL existent
        $tablesExist = $this->checkTablesExist();

        if (! $tablesExist && ! $syncOnly) {
            // Télécharger et importer les tables SQL
            $this->info('📥 Téléchargement et import des données brutes...');
            if (! $this->downloadAndImportSQL()) {
                return Command::FAILURE;
            }
        }

        if ($syncOnly && ! $tablesExist) {
            $this->error("❌ Les tables SQL n'existent pas. Relancez sans --sync-only.");

            return Command::FAILURE;
        }

        // Synchroniser vers notre table senateurs_questions
        $this->info('🔄 Synchronisation des questions vers senateurs_questions...');
        $this->syncQuestions($limit, $year);

        // Stats finales
        $this->newLine();
        $this->info("📊 Résumé de l'import :");
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Questions importées', $this->imported],
                ['Questions mises à jour', $this->updated],
                ['Questions ignorées', $this->skipped],
                ['Erreurs', $this->errors],
                ['Total en base', SenateurQuestion::count()],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Vérifie si les tables SQL questions existent
     */
    private function checkTablesExist(): bool
    {
        try {
            // Vérifier si le schema existe
            $schemas = DB::select('SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?', [$this->schemaName]);

            if (empty($schemas)) {
                $this->line("   ℹ️  Schema '{$this->schemaName}' n'existe pas encore");

                return false;
            }

            // Vérifier si la table principale existe
            $tables = DB::select("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = ? AND table_name = 'tam_questions'
            ", [$this->schemaName]);

            if (empty($tables)) {
                $this->line("   ℹ️  Table 'tam_questions' n'existe pas encore");

                return false;
            }

            $count = DB::table("{$this->schemaName}.tam_questions")->count();
            $this->line("   ✅ Tables SQL présentes ({$count} questions brutes)");

            return true;
        } catch (\Exception $e) {
            $this->line('   ⚠️  Erreur vérification tables: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Télécharge et importe le fichier SQL via psql
     */
    private function downloadAndImportSQL(): bool
    {
        $this->info('   📥 Téléchargement du fichier ZIP...');

        $zipPath = storage_path('app/temp/questions.zip');
        $extractPath = storage_path('app/temp/questions');

        // Créer les dossiers
        if (! file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }
        if (! file_exists($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        // Télécharger le ZIP
        try {
            $response = Http::timeout(600)->get($this->zipUrl);

            if (! $response->successful()) {
                $this->error("   ❌ Erreur HTTP {$response->status()}");

                return false;
            }

            file_put_contents($zipPath, $response->body());
            $sizeMB = round(filesize($zipPath) / 1024 / 1024, 2);
            $this->line("   ✅ Téléchargé ({$sizeMB} MB)");
        } catch (\Exception $e) {
            $this->error('   ❌ Erreur téléchargement: '.$e->getMessage());

            return false;
        }

        // Extraire le ZIP
        $this->info('   📦 Extraction...');
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            $this->error("   ❌ Impossible d'ouvrir le ZIP");

            return false;
        }
        $zip->extractTo($extractPath);
        $zip->close();

        // Trouver le fichier SQL
        $sqlFile = null;
        $files = glob($extractPath.'/*.sql');
        if (! empty($files)) {
            $sqlFile = $files[0];
        }

        if (! $sqlFile) {
            $this->error('   ❌ Aucun fichier SQL trouvé');

            return false;
        }

        $this->line('   ✅ Fichier SQL: '.basename($sqlFile));

        // Importer via psql
        $this->info('   🔧 Import via psql (cela peut prendre plusieurs minutes)...');

        $dbConfig = config('database.connections.pgsql');
        $command = sprintf(
            'PGPASSWORD=%s psql -h %s -p %s -U %s -d %s -f %s 2>&1',
            escapeshellarg($dbConfig['password']),
            escapeshellarg($dbConfig['host']),
            escapeshellarg($dbConfig['port']),
            escapeshellarg($dbConfig['username']),
            escapeshellarg($dbConfig['database']),
            escapeshellarg($sqlFile)
        );

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->error("   ❌ Erreur psql (code {$returnVar})");
            // Afficher les dernières erreurs
            $errorLines = array_filter($output, fn ($l) => stripos($l, 'error') !== false);
            foreach (array_slice($errorLines, -5) as $line) {
                $this->line('      '.$line);
            }

            return false;
        }

        $this->line('   ✅ Import SQL terminé');

        // Nettoyage
        @unlink($zipPath);
        // Garder le dossier pour analyse si besoin

        return true;
    }

    /**
     * Synchronise les données des tables SQL vers senateurs_questions
     */
    private function syncQuestions(?int $limit, ?int $year): void
    {
        $this->info('   📋 Récupération des questions depuis les tables SQL...');

        try {
            // Construire la requête
            $query = DB::table("{$this->schemaName}.tam_questions as q")
                ->leftJoin("{$this->schemaName}.tam_reponses as r", 'q.id', '=', 'r.idque')
                ->select([
                    'q.matricule',
                    'q.numero',
                    'q.natquecod as type',
                    'q.txtque as texte_question',
                    'q.mindepotlib as ministre_destinataire',
                    'q.datejodepot as date_question',
                    'r.txtrep as texte_reponse',
                    'r.datejorep as date_reponse',
                    'q.rubrique as theme',
                    'q.themes as sous_theme',
                    'q.titre',
                    'q.nom',
                    'q.prenom',
                ])
                ->orderByDesc('q.datejodepot');

            // Filtrer par année
            if ($year) {
                $query->whereYear('q.datejodepot', $year);
                $this->line("   📅 Filtrage année: {$year}");
            } else {
                // Par défaut: 5 dernières années
                $minYear = now()->year - 5;
                $query->whereYear('q.datejodepot', '>=', $minYear);
                $this->line("   📅 Période: {$minYear} - ".now()->year);
            }

            if ($limit) {
                $query->limit($limit);
                $this->line("   ⚠️  Limite: {$limit} questions");
            }

            $questions = $query->get();
            $total = $questions->count();
            $this->line("   📊 {$total} questions à traiter");

            // Récupérer les sénateurs existants
            $senateurs = Senateur::pluck('id', 'matricule')->toArray();
            $this->line('   👥 '.count($senateurs).' sénateurs en base');

            $bar = $this->output->createProgressBar($total);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
            $bar->start();

            foreach ($questions as $q) {
                $bar->setMessage("Q{$q->numero}");

                try {
                    // Vérifier si le sénateur existe
                    if (! isset($senateurs[trim($q->matricule)])) {
                        $this->skipped++;
                        $bar->advance();

                        continue;
                    }

                    // Déterminer le type de question
                    $type = $this->parseQuestionType($q->type);

                    // Créer ou mettre à jour
                    $data = [
                        'senateur_matricule' => trim($q->matricule),
                        'numero' => $q->numero,
                        'type' => $type,
                        'texte_question' => $this->cleanText($q->texte_question),
                        'ministre_destinataire' => $q->ministre_destinataire,
                        'date_question' => $q->date_question ? \Carbon\Carbon::parse($q->date_question)->format('Y-m-d') : null,
                        'texte_reponse' => $this->cleanText($q->texte_reponse),
                        'date_reponse' => $q->date_reponse ? \Carbon\Carbon::parse($q->date_reponse)->format('Y-m-d') : null,
                        'a_reponse' => ! empty($q->texte_reponse),
                        'theme' => $q->theme ?? $q->rubrique,
                        'sous_theme' => $q->sous_theme,
                    ];

                    $existing = SenateurQuestion::where('numero', $q->numero)->first();

                    if ($existing) {
                        $existing->update($data);
                        $this->updated++;
                    } else {
                        SenateurQuestion::create($data);
                        $this->imported++;
                    }

                } catch (\Exception $e) {
                    $this->errors++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

        } catch (\Exception $e) {
            $this->error('   ❌ Erreur synchronisation: '.$e->getMessage());
        }
    }

    /**
     * Convertit le code type en libellé
     */
    private function parseQuestionType(?string $code): string
    {
        $types = config('senat.types_questions', []);

        return $types[$code] ?? $code ?? 'ecrite';
    }

    /**
     * Nettoie le texte HTML
     */
    private function cleanText(?string $text): ?string
    {
        if (empty($text)) {
            return null;
        }
        // Supprimer les balises HTML
        $text = strip_tags($text);
        // Décoder les entités
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        // Nettoyer les espaces multiples
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
