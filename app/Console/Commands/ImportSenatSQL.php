<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use ZipArchive;

/**
 * Commande d'import des bases SQL PostgreSQL du Sénat
 *
 * Cette commande télécharge et importe les dumps SQL PostgreSQL
 * fournis par le Sénat sur data.senat.fr
 *
 * @see config/senat.php pour la configuration des sources
 * @see docs/SOURCES_DONNEES_SENAT.md pour la documentation
 */
class ImportSenatSQL extends Command
{
    protected $signature = 'import:senat-sql 
                            {type : Type de base (senateurs, dosleg, questions, debats, ameli)}
                            {--fresh : Vider les tables avant import}
                            {--analyze : Analyser la structure sans importer}
                            {--list : Lister les bases disponibles}';

    protected $description = 'Importe les bases SQL PostgreSQL du Sénat (Sénateurs, Dossiers, Questions, Débats, Amendements)';

    public function handle(): int
    {
        // Option --list : afficher les bases disponibles
        if ($this->option('list')) {
            return $this->listDatabases();
        }

        $type = $this->argument('type');
        $analyzeOnly = $this->option('analyze');
        $fresh = $this->option('fresh');

        // Récupérer la configuration depuis config/senat.php
        $databases = config('senat.databases', []);

        if (! isset($databases[$type])) {
            $this->error("❌ Type invalide : {$type}");
            $this->info('   Types disponibles : '.implode(', ', array_keys($databases)));
            $this->info('   Utilisez --list pour voir les détails');

            return Command::FAILURE;
        }

        $config = $databases[$type];
        $this->info("🏛️  Import base SQL Sénat : {$config['description']}");
        $this->info("📊 Source : {$config['url']}");

        // 1. Télécharger le ZIP
        $zipPath = $this->downloadZip($config['url'], $type);
        if (! $zipPath) {
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
        $this->info('📥 Téléchargement du fichier ZIP...');

        $zipPath = storage_path("app/temp/{$type}.zip");

        // Créer le dossier temp si nécessaire
        if (! file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        try {
            $response = Http::timeout(300)->get($url);

            if (! $response->successful()) {
                $this->error("❌ Erreur HTTP {$response->status()}");

                return null;
            }

            file_put_contents($zipPath, $response->body());
            $size = filesize($zipPath);
            $sizeMB = round($size / 1024 / 1024, 2);

            $this->info("✅ Fichier téléchargé ({$sizeMB} MB)");

            return $zipPath;

        } catch (\Exception $e) {
            $this->error('❌ Erreur de téléchargement : '.$e->getMessage());

            return null;
        }
    }

    private function extractZip(string $zipPath, string $type): array
    {
        $this->info('📦 Extraction du fichier ZIP...');

        $extractPath = storage_path("app/temp/{$type}");

        if (! file_exists($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        $zip = new ZipArchive;

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

        $this->info('✅ '.count($sqlFiles).' fichier(s) SQL trouvé(s)');

        foreach ($sqlFiles as $file) {
            $this->line('   - '.basename($file));
        }

        return $sqlFiles;
    }

    private function analyzeSQL(array $sqlFiles, array $config): int
    {
        $this->info('🔍 ANALYSE DE LA STRUCTURE SQL');
        $this->newLine();

        foreach ($sqlFiles as $sqlFile) {
            $this->info('📄 Fichier : '.basename($sqlFile));

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
                $this->line('   Colonnes : '.count($columns));

                foreach ($columns as $col) {
                    $this->line("   - {$col['name']} ({$col['type']})");
                }
            }
        }

        $this->newLine();
        $this->info('✅ Analyse terminée');
        $this->newLine();
        $this->warn('💡 Pour importer les données, relancez sans --analyze');

        return Command::SUCCESS;
    }

    private function importSQL(array $sqlFiles, array $config, bool $fresh): int
    {
        $this->warn('⚠️  IMPORT SQL DIRECT');
        $this->warn('   Cette opération va créer/modifier des tables PostgreSQL directement.');

        if (! $this->confirm('Voulez-vous continuer ?', false)) {
            $this->info('❌ Annulé');

            return Command::FAILURE;
        }

        $prefix = $config['table_prefix'] ?? '';

        foreach ($sqlFiles as $sqlFile) {
            $this->info('📥 Import de '.basename($sqlFile).'...');

            // Transformer le SQL pour ajouter les préfixes
            $this->line("🔄 Transformation du SQL (ajout préfixe : {$prefix})...");
            $transformedSqlFile = $this->transformSQLWithPrefix($sqlFile, $prefix);

            if (! $transformedSqlFile) {
                $this->error('❌ Erreur lors de la transformation du SQL');

                continue;
            }

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
                escapeshellarg($transformedSqlFile)
            );

            $this->line('🔧 Exécution via psql (cela peut prendre plusieurs minutes)...');

            // Exécuter la commande
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            // Nettoyer le fichier temporaire
            @unlink($transformedSqlFile);

            if ($returnVar === 0) {
                $this->info('✅ Import réussi : '.basename($sqlFile));
            } else {
                $this->error("❌ Erreur lors de l'import de ".basename($sqlFile));
                $this->error("Code retour : {$returnVar}");

                // Afficher les dernières lignes d'erreur
                $errorLines = array_slice($output, -10);
                foreach ($errorLines as $line) {
                    $this->line('  '.$line);
                }
            }
            $this->newLine();
        }

        // Afficher les statistiques
        $this->newLine();
        $this->info('📊 Vérification des tables importées...');

        try {
            $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename LIMIT 20");

            $this->line('   Échantillon des tables créées :');
            foreach (array_slice($tables, 0, 10) as $table) {
                $this->line("   ✓ {$table->tablename}");
            }

            if (count($tables) > 10) {
                $this->line('   ... et '.(count($tables) - 10).' autres tables');
            }
        } catch (\Exception $e) {
            $this->warn("   Impossible d'afficher les tables : ".$e->getMessage());
        }

        return Command::SUCCESS;
    }

    /**
     * Transforme un fichier SQL pour ajouter un préfixe aux noms de tables
     * Traite le fichier en streaming pour éviter les problèmes de mémoire
     */
    /**
     * Transforme un fichier SQL pour ajouter un préfixe aux noms de tables.
     * Utilise des tables staging temporaires + INSERT ON CONFLICT DO NOTHING
     * pour un import incrémental (diff) : seules les nouvelles lignes sont ajoutées.
     */
    private function transformSQLWithPrefix(string $sqlFile, string $prefix): ?string
    {
        if (empty($prefix)) {
            return $sqlFile;
        }

        $tempFile = storage_path('app/temp_'.basename($sqlFile));

        try {
            $input = fopen($sqlFile, 'r');
            $output = fopen($tempFile, 'w');

            if (! $input || ! $output) {
                throw new \Exception("Impossible d'ouvrir les fichiers");
            }

            $lineCount = 0;
            $transformedCount = 0;
            $inCopy = false;
            $currentStagingTable = null;
            $currentRealTable = null;

            while (($line = fgets($input)) !== false) {
                $lineCount++;

                // CREATE TABLE -> CREATE TABLE IF NOT EXISTS (table conservée entre imports)
                if (preg_match('/^CREATE TABLE\s+(\w+)/i', $line, $matches)) {
                    $tableName = $matches[1];
                    $realTable = "{$prefix}{$tableName}";
                    $line = str_replace(
                        "CREATE TABLE {$tableName}",
                        "CREATE TABLE IF NOT EXISTS {$realTable}",
                        $line
                    );
                    $transformedCount++;
                }

                // ALTER TABLE avec préfixe
                if (preg_match('/^ALTER TABLE\s+(\w+)/i', $line, $matches)) {
                    $tableName = $matches[1];
                    $line = str_replace(
                        "ALTER TABLE {$tableName}",
                        "ALTER TABLE {$prefix}{$tableName}",
                        $line
                    );
                    $transformedCount++;
                }

                // COPY -> redirigé vers une table staging temporaire
                if (preg_match('/^COPY\s+(\w+)/i', $line, $matches)) {
                    $tableName = $matches[1];
                    $currentRealTable = "{$prefix}{$tableName}";
                    $currentStagingTable = "_stg_{$prefix}{$tableName}";

                    fwrite($output, "DROP TABLE IF EXISTS {$currentStagingTable};\n");
                    fwrite($output, "CREATE TEMP TABLE {$currentStagingTable} (LIKE {$currentRealTable} INCLUDING DEFAULTS);\n");

                    $line = str_replace(
                        "COPY {$tableName}",
                        "COPY {$currentStagingTable}",
                        $line
                    );
                    $inCopy = true;
                    $transformedCount++;
                }

                // Fin du bloc COPY (marqueur \.) -> upsert staging vers table réelle
                if ($inCopy && trim($line) === '\\.') {
                    fwrite($output, $line);
                    fwrite($output, "DO \$\$\n");
                    fwrite($output, "BEGIN\n");
                    fwrite($output, "  IF EXISTS (SELECT 1 FROM pg_index WHERE indrelid = '{$currentRealTable}'::regclass AND indisprimary) THEN\n");
                    fwrite($output, "    INSERT INTO {$currentRealTable} SELECT * FROM {$currentStagingTable} ON CONFLICT DO NOTHING;\n");
                    fwrite($output, "  ELSIF NOT EXISTS (SELECT 1 FROM {$currentRealTable} LIMIT 1) THEN\n");
                    fwrite($output, "    INSERT INTO {$currentRealTable} SELECT * FROM {$currentStagingTable};\n");
                    fwrite($output, "  END IF;\n");
                    fwrite($output, "END \$\$;\n");
                    fwrite($output, "DROP TABLE IF EXISTS {$currentStagingTable};\n");
                    $inCopy = false;
                    $currentStagingTable = null;
                    $currentRealTable = null;
                    $transformedCount++;

                    if ($lineCount % 10000 === 0) {
                        $this->line("   ... {$lineCount} lignes traitées, {$transformedCount} transformations");
                    }

                    continue;
                }

                // CREATE INDEX ... ON table
                if (preg_match('/ON\s+(\w+)\s+USING/i', $line, $matches)) {
                    $tableName = $matches[1];
                    $line = str_replace(
                        "ON {$tableName} USING",
                        "ON {$prefix}{$tableName} USING",
                        $line
                    );
                }

                // FOREIGN KEY REFERENCES
                if (preg_match('/REFERENCES\s+(\w+)\s*\(/i', $line, $matches)) {
                    $tableName = $matches[1];
                    $line = str_replace(
                        "REFERENCES {$tableName}",
                        "REFERENCES {$prefix}{$tableName}",
                        $line
                    );
                }

                fwrite($output, $line);

                if ($lineCount % 10000 === 0) {
                    $this->line("   ... {$lineCount} lignes traitées, {$transformedCount} transformations");
                }
            }

            fclose($input);
            fclose($output);

            $this->line("   ✓ {$lineCount} lignes traitées, {$transformedCount} transformations (mode incrémental)");

            return $tempFile;

        } catch (\Exception $e) {
            $this->error('   Erreur transformation : '.$e->getMessage());
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }

            return null;
        }
    }

    /**
     * Liste les bases de données disponibles
     */
    private function listDatabases(): int
    {
        $databases = config('senat.databases', []);

        $this->info('');
        $this->info('🏛️  Bases SQL PostgreSQL du Sénat');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $this->table(
            ['Type', 'Description', 'Préfixe', 'Tables principales'],
            collect($databases)->map(function ($config, $type) {
                $tables = array_keys($config['tables'] ?? []);

                return [
                    $type,
                    $config['description'],
                    $config['table_prefix'],
                    implode(', ', array_slice($tables, 0, 4)).(count($tables) > 4 ? '...' : ''),
                ];
            })->toArray()
        );

        $this->newLine();
        $this->info('📖 Documentation : docs/SOURCES_DONNEES_SENAT.md');
        $this->newLine();
        $this->info('💡 Usage :');
        $this->line('   php artisan import:senat-sql senateurs           # Importer les sénateurs');
        $this->line('   php artisan import:senat-sql ameli --analyze     # Analyser sans importer');
        $this->line('   php artisan senat:sync                           # Synchronisation complète');

        return Command::SUCCESS;
    }
}
