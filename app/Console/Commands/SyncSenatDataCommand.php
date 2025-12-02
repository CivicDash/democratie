<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Senat\SenatDataDownloader;
use App\Services\Senat\AkomaNtosoParser;

/**
 * Commande de synchronisation des données du Sénat
 * 
 * Usage :
 *   php artisan senat:sync                    # Tout synchroniser
 *   php artisan senat:sync senateurs          # Source spécifique
 *   php artisan senat:sync --textes           # Textes Akoma Ntoso
 *   php artisan senat:sync --status           # Afficher le statut
 */
class SyncSenatDataCommand extends Command
{
    protected $signature = 'senat:sync 
                            {source? : Source à synchroniser (senateurs, dosleg, questions, debats, ameli)}
                            {--all : Synchroniser toutes les sources}
                            {--textes : Synchroniser les textes Akoma Ntoso}
                            {--status : Afficher le statut des données}
                            {--force : Forcer le téléchargement même si le cache est valide}
                            {--analyze : Analyser sans importer}
                            {--no-confirm : Ne pas demander de confirmation}';

    protected $description = 'Synchronise les données Open Data du Sénat (bases SQL et textes XML)';

    private SenatDataDownloader $downloader;
    private AkomaNtosoParser $parser;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->downloader = new SenatDataDownloader();
        $this->parser = new AkomaNtosoParser();

        $this->info('');
        $this->info('🏛️  Synchronisation des données du Sénat');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Mode statut
        if ($this->option('status')) {
            return $this->showStatus();
        }

        // Synchronisation textes Akoma Ntoso
        if ($this->option('textes')) {
            return $this->syncTextes();
        }

        // Synchronisation bases SQL
        $source = $this->argument('source');
        $all = $this->option('all') || !$source;

        if ($all) {
            return $this->syncAllDatabases();
        }

        return $this->syncDatabase($source);
    }

    /**
     * Affiche le statut des données
     */
    private function showStatus(): int
    {
        $this->info('');
        $this->info('📊 Statut des données Sénat');
        $this->newLine();

        // Statistiques du cache
        $cacheStats = $this->downloader->getCacheStats();
        
        $this->table(
            ['Base', 'Téléchargé', 'Taille', 'Dernière MAJ', 'Cache valide'],
            collect($cacheStats)->map(function ($stats, $type) {
                return [
                    $type,
                    $stats['exists'] ? '✅' : '❌',
                    $stats['size_mb'] . ' Mo',
                    $stats['modified'] ?? '-',
                    $stats['cache_valid'] ? '✅' : '❌',
                ];
            })->toArray()
        );

        // Statistiques des tables
        $this->newLine();
        $this->info('📋 Tables importées :');
        
        try {
            $tables = DB::select("
                SELECT tablename, 
                       pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size
                FROM pg_tables 
                WHERE schemaname = 'public' 
                  AND tablename LIKE 'senat_%'
                ORDER BY tablename
            ");

            if (empty($tables)) {
                $this->warn('   Aucune table Sénat trouvée');
            } else {
                foreach ($tables as $table) {
                    $count = DB::table($table->tablename)->count();
                    $this->line("   ✓ {$table->tablename} : {$count} enregistrements ({$table->size})");
                }
            }
        } catch (\Exception $e) {
            $this->error('   Erreur : ' . $e->getMessage());
        }

        // Statistiques des vues
        $this->newLine();
        $this->info('👁️ Vues SQL :');
        
        try {
            $views = DB::select("
                SELECT viewname 
                FROM pg_views 
                WHERE schemaname = 'public' 
                  AND (viewname LIKE 'senat%' OR viewname LIKE 'senateurs%' OR viewname LIKE '%_senat')
                ORDER BY viewname
            ");

            if (empty($views)) {
                $this->warn('   Aucune vue Sénat trouvée');
            } else {
                foreach ($views as $view) {
                    try {
                        $count = DB::table($view->viewname)->count();
                        $this->line("   ✓ {$view->viewname} : {$count} enregistrements");
                    } catch (\Exception $e) {
                        $this->line("   ⚠ {$view->viewname} : erreur");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('   Erreur : ' . $e->getMessage());
        }

        return Command::SUCCESS;
    }

    /**
     * Synchronise toutes les bases de données
     */
    private function syncAllDatabases(): int
    {
        $databases = config('senat.databases', []);
        
        // Trier par priorité
        uasort($databases, fn($a, $b) => ($a['priority'] ?? 99) <=> ($b['priority'] ?? 99));

        $this->info('');
        $this->info('📦 Synchronisation de toutes les bases');
        $this->newLine();

        $this->table(
            ['#', 'Base', 'Description'],
            collect($databases)->map(function ($config, $type) {
                return [
                    $config['priority'] ?? '-',
                    $type,
                    $config['description'],
                ];
            })->toArray()
        );

        if (!$this->option('no-confirm') && !$this->confirm('Voulez-vous continuer ?', false)) {
            $this->info('❌ Annulé');
            return Command::FAILURE;
        }

        $success = 0;
        $failed = 0;

        foreach (array_keys($databases) as $type) {
            $result = $this->syncDatabase($type, false);
            
            if ($result === Command::SUCCESS) {
                $success++;
            } else {
                $failed++;
            }
        }

        $this->newLine();
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("✅ Terminé : {$success} réussi(s), {$failed} échec(s)");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Synchronise une base de données spécifique
     */
    private function syncDatabase(string $type, bool $standalone = true): int
    {
        $databases = config('senat.databases', []);
        
        if (!isset($databases[$type])) {
            $this->error("❌ Base inconnue : {$type}");
            $this->info("   Bases disponibles : " . implode(', ', array_keys($databases)));
            return Command::FAILURE;
        }

        $config = $databases[$type];
        $force = $this->option('force');
        $analyze = $this->option('analyze');

        $this->newLine();
        $this->info("📥 {$config['description']}");
        $this->line("   Source : {$config['url']}");

        // Télécharger
        $this->line("   ⏳ Téléchargement...");
        $zipFile = $this->downloader->downloadDatabase($type, $force);
        
        if (!$zipFile) {
            $this->error("   ❌ Échec du téléchargement");
            return Command::FAILURE;
        }
        $this->line("   ✅ Téléchargé");

        // Extraire
        $this->line("   ⏳ Extraction...");
        $sqlFiles = $this->downloader->extractZip($zipFile, $type);
        
        if (empty($sqlFiles)) {
            $this->error("   ❌ Aucun fichier SQL trouvé");
            return Command::FAILURE;
        }
        $this->line("   ✅ " . count($sqlFiles) . " fichier(s) SQL");

        // Analyser ou importer
        if ($analyze) {
            return $this->analyzeSqlFiles($sqlFiles, $config);
        }

        return $this->importSqlFiles($sqlFiles, $config);
    }

    /**
     * Analyse les fichiers SQL
     */
    private function analyzeSqlFiles(array $sqlFiles, array $config): int
    {
        $this->newLine();
        $this->info("🔍 Analyse de la structure");

        foreach ($sqlFiles as $sqlFile) {
            $this->line("   📄 " . basename($sqlFile));
            
            $content = file_get_contents($sqlFile);
            
            // Compter les tables
            preg_match_all('/CREATE TABLE\s+(\w+)/i', $content, $tables);
            $this->line("      Tables : " . count($tables[1]));
            
            foreach ($tables[1] as $table) {
                $this->line("      - {$table}");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Importe les fichiers SQL
     */
    private function importSqlFiles(array $sqlFiles, array $config): int
    {
        $prefix = $config['table_prefix'] ?? '';
        
        $this->line("   ⏳ Import SQL (préfixe : {$prefix})...");

        $dbConfig = config('database.connections.pgsql');
        
        foreach ($sqlFiles as $sqlFile) {
            // Transformer le SQL avec le préfixe
            $transformedFile = $this->transformSqlWithPrefix($sqlFile, $prefix);
            
            if (!$transformedFile) {
                $this->error("   ❌ Erreur transformation : " . basename($sqlFile));
                continue;
            }

            // Exécuter via psql
            $command = sprintf(
                'PGPASSWORD=%s psql -h %s -p %s -U %s -d %s -f %s 2>&1',
                escapeshellarg($dbConfig['password']),
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['port']),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($transformedFile)
            );

            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            // Nettoyer le fichier temporaire
            if ($transformedFile !== $sqlFile) {
                @unlink($transformedFile);
            }

            if ($returnVar !== 0) {
                $this->error("   ❌ Erreur import : " . basename($sqlFile));
                // Afficher les dernières erreurs
                $errors = array_filter($output, fn($line) => stripos($line, 'error') !== false);
                foreach (array_slice($errors, -3) as $error) {
                    $this->line("      " . $error);
                }
                return Command::FAILURE;
            }
        }

        $this->line("   ✅ Import terminé");
        return Command::SUCCESS;
    }

    /**
     * Transforme un fichier SQL pour ajouter les préfixes de tables
     */
    private function transformSqlWithPrefix(string $sqlFile, string $prefix): ?string
    {
        if (empty($prefix)) {
            return $sqlFile;
        }

        $tempFile = storage_path('app/temp_' . basename($sqlFile));

        try {
            $input = fopen($sqlFile, 'r');
            $output = fopen($tempFile, 'w');

            if (!$input || !$output) {
                return null;
            }

            while (($line = fgets($input)) !== false) {
                // CREATE TABLE
                if (preg_match('/^CREATE TABLE\s+(\w+)/i', $line, $matches)) {
                    $line = str_replace(
                        "CREATE TABLE {$matches[1]}",
                        "CREATE TABLE {$prefix}{$matches[1]}",
                        $line
                    );
                }

                // ALTER TABLE
                if (preg_match('/^ALTER TABLE\s+(\w+)/i', $line, $matches)) {
                    $line = str_replace(
                        "ALTER TABLE {$matches[1]}",
                        "ALTER TABLE {$prefix}{$matches[1]}",
                        $line
                    );
                }

                // COPY
                if (preg_match('/^COPY\s+(\w+)/i', $line, $matches)) {
                    $line = str_replace(
                        "COPY {$matches[1]}",
                        "COPY {$prefix}{$matches[1]}",
                        $line
                    );
                }

                // CREATE INDEX ... ON table
                if (preg_match('/ON\s+(\w+)\s+USING/i', $line, $matches)) {
                    $line = str_replace(
                        "ON {$matches[1]} USING",
                        "ON {$prefix}{$matches[1]} USING",
                        $line
                    );
                }

                // REFERENCES
                if (preg_match('/REFERENCES\s+(\w+)\s*\(/i', $line, $matches)) {
                    $line = str_replace(
                        "REFERENCES {$matches[1]}",
                        "REFERENCES {$prefix}{$matches[1]}",
                        $line
                    );
                }

                fwrite($output, $line);
            }

            fclose($input);
            fclose($output);

            return $tempFile;

        } catch (\Exception $e) {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
            return null;
        }
    }

    /**
     * Synchronise les textes Akoma Ntoso
     */
    private function syncTextes(): int
    {
        $this->info('');
        $this->info('📄 Synchronisation des textes Akoma Ntoso');
        $this->newLine();

        $force = $this->option('force');

        // Télécharger les flux
        $this->info('📥 Téléchargement des flux...');
        
        $depotsFile = $this->downloader->downloadAkomaNtosoFeed('depots', $force);
        $adoptionsFile = $this->downloader->downloadAkomaNtosoFeed('adoptions', $force);

        if (!$depotsFile && !$adoptionsFile) {
            $this->error('❌ Impossible de télécharger les flux');
            return Command::FAILURE;
        }

        // Parser les flux
        $textes = [];
        
        if ($depotsFile) {
            $depots = $this->downloader->parseAkomaNtosoFeed($depotsFile);
            $this->line("   ✅ Textes déposés : " . count($depots));
            $textes = array_merge($textes, array_map(fn($t) => array_merge($t, ['source' => 'depots']), $depots));
        }

        if ($adoptionsFile) {
            $adoptions = $this->downloader->parseAkomaNtosoFeed($adoptionsFile);
            $this->line("   ✅ Textes adoptés : " . count($adoptions));
            $textes = array_merge($textes, array_map(fn($t) => array_merge($t, ['source' => 'adoptions']), $adoptions));
        }

        // Afficher les statistiques
        $this->newLine();
        $this->info('📊 Statistiques :');

        $byType = collect($textes)->groupBy('type')->map->count();
        foreach ($byType as $type => $count) {
            $typeName = config("senat.types_textes.{$type}", $type);
            $this->line("   - {$typeName} : {$count}");
        }

        // Afficher les textes récents
        $this->newLine();
        $this->info('📋 Textes les plus récents :');

        $recent = collect($textes)
            ->sortByDesc('last_modified')
            ->take(10);

        $this->table(
            ['Type', 'Numéro', 'Dernière modification'],
            $recent->map(function ($t) {
                return [
                    config("senat.types_textes.{$t['type']}", $t['type'] ?? '-'),
                    $t['number'] ?? '-',
                    $t['last_modified'] ?? '-',
                ];
            })->toArray()
        );

        // Mode analyse : télécharger et parser un exemple
        if ($this->option('analyze') && $recent->isNotEmpty()) {
            $this->newLine();
            $this->info('🔍 Analyse d\'un texte exemple...');

            $example = $recent->first();
            $textFile = $this->downloader->downloadAkomaNtosoText($example['url']);

            if ($textFile) {
                $parsed = $this->parser->parseFile($textFile);
                
                if ($parsed) {
                    $stats = $this->parser->extractStats($parsed);
                    $this->line("   Type : {$stats['type']}");
                    $this->line("   Articles : {$stats['articles_count']}");
                    $this->line("   Références : {$stats['references_count']}");

                    if (!empty($parsed['preface']['title'])) {
                        $this->line("   Titre : " . substr($parsed['preface']['title'], 0, 80) . '...');
                    }

                    $authors = $this->parser->extractAuthors($parsed);
                    if (!empty($authors)) {
                        $this->line("   Auteurs : " . count($authors));
                        foreach (array_slice($authors, 0, 3) as $author) {
                            $this->line("      - {$author['name']}");
                        }
                    }
                }
            }
        }

        return Command::SUCCESS;
    }
}

