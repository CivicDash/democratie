<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ImportSenatSQL extends Command
{
    protected $signature = 'import:senat-sql 
                            {type : Type de base (questions, debats, ameli)}
                            {--fresh : Vider les tables avant import}
                            {--analyze : Analyser la structure sans importer}';

    protected $description = 'Importe les bases SQL PostgreSQL du Sénat (Questions, Débats, Amendements)';

    private const DATABASES = [
        'senateurs' => [
            'url' => 'https://data.senat.fr/data/senateurs/export_sens.zip',
            'description' => 'Sénateurs (Profils complets)',
            'table_prefix' => 'senat_senateurs_',
        ],
        'dosleg' => [
            'url' => 'https://data.senat.fr/data/dosleg/dosleg.zip',
            'description' => 'Dossiers Législatifs',
            'table_prefix' => 'senat_dosleg_',
        ],
        'questions' => [
            'url' => 'https://data.senat.fr/data/questions/questions.zip',
            'description' => 'Questions au Gouvernement',
            'table_prefix' => 'senat_questions_',
        ],
        'debats' => [
            'url' => 'https://data.senat.fr/data/debats/debats.zip',
            'description' => 'Comptes rendus des débats',
            'table_prefix' => 'senat_debats_',
        ],
        'ameli' => [
            'url' => 'https://data.senat.fr/data/ameli/ameli.zip',
            'description' => 'Amendements (Base AMELI)',
            'table_prefix' => 'senat_ameli_',
        ],
    ];

    public function handle(): int
    {
        $type = $this->argument('type');
        $analyzeOnly = $this->option('analyze');
        $fresh = $this->option('fresh');

        if (!isset(self::DATABASES[$type])) {
            $this->error("❌ Type invalide. Types disponibles : " . implode(', ', array_keys(self::DATABASES)));
            return Command::FAILURE;
        }

        $config = self::DATABASES[$type];
        $this->info("🏛️  Import base SQL Sénat : {$config['description']}");
        $this->info("📊 Source : {$config['url']}");

        // 1. Télécharger le ZIP
        $zipPath = $this->downloadZip($config['url'], $type);
        if (!$zipPath) {
            return Command::FAILURE;
        }

        // 2. Extraire le ZIP
        $sqlFiles = $this->extractZip($zipPath, $type);
        if (empty($sqlFiles)) {
            return Command::FAILURE;
        }

        // 3. Analyser ou Importer
        if ($analyzeOnly) {
            return $this->analyzeSQL($sqlFiles, $config);
        } else {
            return $this->importSQL($sqlFiles, $config, $fresh);
        }
    }

    private function downloadZip(string $url, string $type): ?string
    {
        $this->info("📥 Téléchargement du fichier ZIP...");
        
        $zipPath = storage_path("app/temp/{$type}.zip");
        
        // Créer le dossier temp si nécessaire
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        try {
            $response = Http::timeout(300)->get($url);

            if (!$response->successful()) {
                $this->error("❌ Erreur HTTP {$response->status()}");
                return null;
            }

            file_put_contents($zipPath, $response->body());
            $size = filesize($zipPath);
            $sizeMB = round($size / 1024 / 1024, 2);
            
            $this->info("✅ Fichier téléchargé ({$sizeMB} MB)");
            return $zipPath;

        } catch (\Exception $e) {
            $this->error("❌ Erreur de téléchargement : " . $e->getMessage());
            return null;
        }
    }

    private function extractZip(string $zipPath, string $type): array
    {
        $this->info("📦 Extraction du fichier ZIP...");
        
        $extractPath = storage_path("app/temp/{$type}");
        
        if (!file_exists($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        $zip = new ZipArchive();
        
        if ($zip->open($zipPath) !== true) {
            $this->error("❌ Impossible d'ouvrir le fichier ZIP");
            return [];
        }

        $zip->extractTo($extractPath);
        $zip->close();

        // Lister les fichiers SQL
        $sqlFiles = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractPath)
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'sql') {
                $sqlFiles[] = $file->getPathname();
            }
        }

        $this->info("✅ " . count($sqlFiles) . " fichier(s) SQL trouvé(s)");
        
        foreach ($sqlFiles as $file) {
            $this->line("   - " . basename($file));
        }

        return $sqlFiles;
    }

    private function analyzeSQL(array $sqlFiles, array $config): int
    {
        $this->info("🔍 ANALYSE DE LA STRUCTURE SQL");
        $this->newLine();

        foreach ($sqlFiles as $sqlFile) {
            $this->info("📄 Fichier : " . basename($sqlFile));
            
            $content = file_get_contents($sqlFile);
            $lines = explode("\n", $content);
            
            // Extraire les CREATE TABLE
            $tables = [];
            $currentTable = null;
            $currentColumns = [];
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                if (preg_match('/CREATE TABLE\s+(\w+)/i', $line, $matches)) {
                    if ($currentTable) {
                        $tables[$currentTable] = $currentColumns;
                    }
                    $currentTable = $matches[1];
                    $currentColumns = [];
                }
                
                if ($currentTable && preg_match('/^\s*(\w+)\s+(VARCHAR|INTEGER|TEXT|DATE|TIMESTAMP|BOOLEAN)/i', $line, $matches)) {
                    $currentColumns[] = [
                        'name' => $matches[1],
                        'type' => $matches[2],
                    ];
                }
                
                if ($currentTable && preg_match('/\);$/', $line)) {
                    $tables[$currentTable] = $currentColumns;
                    $currentTable = null;
                    $currentColumns = [];
                }
            }
            
            // Afficher les tables
            foreach ($tables as $tableName => $columns) {
                $this->newLine();
                $this->line("📊 Table : <fg=cyan>{$tableName}</>");
                $this->line("   Colonnes : " . count($columns));
                
                foreach ($columns as $col) {
                    $this->line("   - {$col['name']} ({$col['type']})");
                }
            }
        }

        $this->newLine();
        $this->info("✅ Analyse terminée");
        $this->newLine();
        $this->warn("💡 Pour importer les données, relancez sans --analyze");
        
        return Command::SUCCESS;
    }

    private function importSQL(array $sqlFiles, array $config, bool $fresh): int
    {
        $this->warn("⚠️  IMPORT SQL DIRECT");
        $this->warn("   Cette opération va créer/modifier des tables PostgreSQL directement.");
        
        if (!$this->confirm("Voulez-vous continuer ?", false)) {
            $this->info("❌ Annulé");
            return Command::FAILURE;
        }

        foreach ($sqlFiles as $sqlFile) {
            $this->info("📥 Import de " . basename($sqlFile) . "...");
            
            // Utiliser psql directement pour éviter les problèmes de mémoire PHP
            $dbConfig = config('database.connections.pgsql');
            
            $host = $dbConfig['host'];
            $port = $dbConfig['port'];
            $database = $dbConfig['database'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];
            
            // Construire la commande psql
            $command = sprintf(
                'PGPASSWORD=%s psql -h %s -p %s -U %s -d %s -f %s 2>&1',
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($database),
                escapeshellarg($sqlFile)
            );
            
            $this->line("🔧 Exécution via psql (cela peut prendre plusieurs minutes)...");
            
            // Exécuter la commande
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);
            
            if ($returnVar === 0) {
                $this->info("✅ Import réussi : " . basename($sqlFile));
            } else {
                $this->error("❌ Erreur lors de l'import de " . basename($sqlFile));
                $this->error("Code retour : {$returnVar}");
                
                // Afficher les dernières lignes d'erreur
                $errorLines = array_slice($output, -10);
                foreach ($errorLines as $line) {
                    $this->line("  " . $line);
                }
            }
            $this->newLine();
        }

        // Afficher les statistiques
        $this->newLine();
        $this->info("📊 Vérification des tables importées...");
        
        try {
            $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename LIMIT 20");
            
            $this->line("   Échantillon des tables créées :");
            foreach (array_slice($tables, 0, 10) as $table) {
                $this->line("   ✓ {$table->tablename}");
            }
            
            if (count($tables) > 10) {
                $this->line("   ... et " . (count($tables) - 10) . " autres tables");
            }
        } catch (\Exception $e) {
            $this->warn("   Impossible d'afficher les tables : " . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}

