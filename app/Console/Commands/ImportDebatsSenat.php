<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Import des débats en séance publique du Sénat
 * 
 * Source: https://data.senat.fr/data/debats/debats.zip
 * 
 * Tables:
 * - debats -> senat_debats
 * - secdis -> senat_sections_discussion
 * - secdivers -> senat_sections_diverses
 * - intpjl -> senat_interventions_legislatives
 * - intdivers -> senat_interventions_diverses
 * - typsec -> senat_types_section
 * - lecassdeb -> senat_lectures_debats
 */
class ImportDebatsSenat extends Command
{
    protected $signature = 'senat:import-debats 
                            {--fresh : Vider les tables avant import}
                            {--download : Télécharger le fichier avant import}
                            {--since= : Importer seulement depuis cette date (YYYY-MM-DD)}
                            {--year= : Importer seulement une année spécifique}';

    protected $description = 'Importe les comptes-rendus des débats du Sénat';

    private $stats = [
        'debats' => 0,
        'sections_discussion' => 0,
        'sections_diverses' => 0,
        'interventions_legislatives' => 0,
        'interventions_diverses' => 0,
        'types_section' => 0,
        'lectures_debats' => 0,
    ];

    private $batchSize = 1000;
    private $sqlFile;
    private $yearFilter = null;
    private $sinceFilter = null;

    public function handle(): int
    {
        $this->info('🏛️  Import des débats du Sénat');
        $this->newLine();

        // Filtres
        if ($this->option('year')) {
            $this->yearFilter = (int) $this->option('year');
            $this->info("📅 Filtrage par année: {$this->yearFilter}");
        }
        if ($this->option('since')) {
            $this->sinceFilter = $this->option('since');
            $this->info("📅 Filtrage depuis: {$this->sinceFilter}");
        }

        // Téléchargement si demandé
        if ($this->option('download')) {
            $this->downloadData();
        }

        // Vérifier que le fichier existe
        $this->sqlFile = storage_path('app/data/senat/debats.sql');
        if (!file_exists($this->sqlFile)) {
            $this->error('❌ Fichier debats.sql non trouvé. Utilisez --download pour le télécharger.');
            return Command::FAILURE;
        }

        // Vider les tables si demandé
        if ($this->option('fresh')) {
            $this->warn('🗑️  Suppression des données existantes...');
            $this->truncateTables();
        }

        // Import des données
        $this->importData();

        // Afficher les statistiques
        $this->displayStats();

        return Command::SUCCESS;
    }

    private function downloadData(): void
    {
        $this->info('📥 Téléchargement des données...');
        
        $url = 'https://data.senat.fr/data/debats/debats.zip';
        $zipPath = storage_path('app/data/senat/debats.zip');
        $extractPath = storage_path('app/data/senat');

        // Créer le dossier si nécessaire
        if (!is_dir($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        // Télécharger
        $this->output->write('   Téléchargement... ');
        $ch = curl_init($url);
        $fp = fopen($zipPath, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        $this->info('✓');

        // Extraire
        $this->output->write('   Extraction... ');
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($extractPath);
            $zip->close();
            $this->info('✓');
        } else {
            $this->error('Échec extraction');
        }
    }

    private function truncateTables(): void
    {
        DB::statement('TRUNCATE TABLE senat_interventions_diverses CASCADE');
        DB::statement('TRUNCATE TABLE senat_interventions_legislatives CASCADE');
        DB::statement('TRUNCATE TABLE senat_sections_diverses CASCADE');
        DB::statement('TRUNCATE TABLE senat_sections_discussion CASCADE');
        DB::statement('TRUNCATE TABLE senat_lectures_debats CASCADE');
        DB::statement('TRUNCATE TABLE senat_debats CASCADE');
        DB::statement('TRUNCATE TABLE senat_types_section CASCADE');
    }

    private function importData(): void
    {
        $this->info('📖 Lecture du fichier SQL...');
        
        $handle = fopen($this->sqlFile, 'r');
        if (!$handle) {
            $this->error('Impossible d\'ouvrir le fichier');
            return;
        }

        $currentTable = null;
        $columns = [];
        $buffer = [];
        $lineNum = 0;

        while (($line = fgets($handle)) !== false) {
            $lineNum++;
            
            // Afficher progression tous les 100k lignes
            if ($lineNum % 100000 === 0) {
                $this->output->write("\r   Ligne {$lineNum}...");
            }

            // Détecter le début d'un COPY
            if (preg_match('/^COPY (public\.)?(\w+) \((.+)\) FROM stdin;/', $line, $matches)) {
                $currentTable = $matches[2];
                $columns = array_map('trim', explode(',', $matches[3]));
                $buffer = [];
                continue;
            }

            // Fin d'un COPY
            if ($line === "\\.\n" && $currentTable) {
                $this->processBuffer($currentTable, $columns, $buffer);
                $currentTable = null;
                $columns = [];
                $buffer = [];
                continue;
            }

            // Ligne de données
            if ($currentTable) {
                $values = explode("\t", rtrim($line, "\n"));
                
                // Appliquer les filtres de date si nécessaire
                if ($this->shouldInclude($currentTable, $columns, $values)) {
                    $buffer[] = $values;
                    
                    // Flush le buffer si trop grand
                    if (count($buffer) >= $this->batchSize) {
                        $this->processBuffer($currentTable, $columns, $buffer);
                        $buffer = [];
                    }
                }
            }
        }

        fclose($handle);
        $this->output->write("\r");
        $this->info("   ✓ Lecture terminée ({$lineNum} lignes)");
    }

    private function shouldInclude(string $table, array $columns, array $values): bool
    {
        // Pas de filtre
        if (!$this->yearFilter && !$this->sinceFilter) {
            return true;
        }

        // Trouver la colonne de date selon la table
        $dateColumn = match($table) {
            'debats', 'secdis', 'secdivers' => 'datsea',
            'lecassdeb' => 'datsea',
            default => null,
        };

        if (!$dateColumn) {
            return true; // Tables sans date
        }

        $dateIndex = array_search($dateColumn, $columns);
        if ($dateIndex === false) {
            return true;
        }

        $dateValue = $values[$dateIndex] ?? null;
        if (!$dateValue || $dateValue === '\\N') {
            return true;
        }

        // Extraire l'année
        $year = (int) substr($dateValue, 0, 4);
        $date = substr($dateValue, 0, 10);

        // Appliquer les filtres
        if ($this->yearFilter && $year !== $this->yearFilter) {
            return false;
        }

        if ($this->sinceFilter && $date < $this->sinceFilter) {
            return false;
        }

        return true;
    }

    private function processBuffer(string $table, array $columns, array $buffer): void
    {
        if (empty($buffer)) {
            return;
        }

        match($table) {
            'typsec' => $this->importTypesSection($columns, $buffer),
            'debats' => $this->importDebats($columns, $buffer),
            'lecassdeb' => $this->importLecturesDebats($columns, $buffer),
            'secdis' => $this->importSectionsDiscussion($columns, $buffer),
            'secdivers' => $this->importSectionsDiverses($columns, $buffer),
            'intpjl' => $this->importInterventionsLegislatives($columns, $buffer),
            'intdivers' => $this->importInterventionsDiverses($columns, $buffer),
            default => null,
        };
    }

    private function importTypesSection(array $columns, array $buffer): void
    {
        $data = [];
        foreach ($buffer as $row) {
            $record = $this->mapRow($columns, $row);
            $data[] = [
                'code' => trim($record['typseccod'] ?? ''),
                'libelle' => $this->cleanValue($record['typseclib'] ?? null),
            ];
        }

        DB::table('senat_types_section')->upsert($data, ['code'], ['libelle']);
        $this->stats['types_section'] += count($data);
    }

    private function importDebats(array $columns, array $buffer): void
    {
        $data = [];
        foreach ($buffer as $row) {
            $record = $this->mapRow($columns, $row);
            $dateSeance = $this->cleanValue($record['datsea'] ?? null);
            
            if (!$dateSeance) continue;

            $data[] = [
                'date_seance' => $dateSeance,
                'numero' => $this->cleanValue($record['numero'] ?? null),
                'url' => $this->cleanValue($record['deburl'] ?? null),
                'libelle_special' => $this->cleanValue($record['libspec'] ?? null),
                'est_congres' => ($record['estcongres'] ?? 'N') === 'O',
                'etat_video' => $this->cleanValue($record['etavidcod'] ?? null),
                'cpterr' => (int) ($record['cpterr'] ?? 0),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($data)) {
            DB::table('senat_debats')->upsert($data, ['date_seance'], [
                'numero', 'url', 'libelle_special', 'est_congres', 'etat_video', 'cpterr', 'updated_at'
            ]);
            $this->stats['debats'] += count($data);
        }
    }

    private function importLecturesDebats(array $columns, array $buffer): void
    {
        $data = [];
        foreach ($buffer as $row) {
            $record = $this->mapRow($columns, $row);
            $lectureId = trim($record['lecassidt'] ?? '');
            $dateSeance = $this->cleanValue($record['datsea'] ?? null);
            
            if (!$lectureId || !$dateSeance) continue;

            $data[] = [
                'lecture_id' => $lectureId,
                'date_seance' => $dateSeance,
            ];
        }

        if (!empty($data)) {
            DB::table('senat_lectures_debats')->upsert($data, ['lecture_id', 'date_seance'], []);
            $this->stats['lectures_debats'] += count($data);
        }
    }

    private function importSectionsDiscussion(array $columns, array $buffer): void
    {
        $data = [];
        foreach ($buffer as $row) {
            $record = $this->mapRow($columns, $row);
            $id = (int) ($record['secdiscle'] ?? 0);
            
            if (!$id) continue;

            $data[] = [
                'id' => $id,
                'lecture_id' => trim($record['lecassidt'] ?? ''),
                'type_section' => trim($record['typseccod'] ?? ''),
                'date_seance' => $this->cleanValue($record['datsea'] ?? null),
                'numero' => $this->cleanValue($record['secdisnum'] ?? null),
                'objet' => $this->cleanValue($record['secdisobj'] ?? null),
                'url' => $this->cleanValue($record['secdisurl'] ?? null),
                'ordre' => $this->cleanValue($record['secdisordid'] ?? null),
                'parent_id' => $this->cleanValue($record['secdispere'] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($data)) {
            DB::table('senat_sections_discussion')->upsert($data, ['id'], [
                'lecture_id', 'type_section', 'date_seance', 'numero', 'objet', 'url', 'ordre', 'parent_id', 'updated_at'
            ]);
            $this->stats['sections_discussion'] += count($data);
        }
    }

    private function importSectionsDiverses(array $columns, array $buffer): void
    {
        $data = [];
        foreach ($buffer as $row) {
            $record = $this->mapRow($columns, $row);
            $id = (int) ($record['secdiverscle'] ?? 0);
            
            if (!$id) continue;

            $data[] = [
                'id' => $id,
                'type_section' => trim($record['typseccod'] ?? ''),
                'date_seance' => $this->cleanValue($record['datsea'] ?? null),
                'objet' => $this->cleanValue($record['secdiversobj'] ?? null),
                'url' => $this->cleanValue($record['secdiversurl'] ?? null),
                'ordre' => $this->cleanValue($record['secdiversordid'] ?? null),
                'parent_id' => $this->cleanValue($record['secdiverspere'] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($data)) {
            DB::table('senat_sections_diverses')->upsert($data, ['id'], [
                'type_section', 'date_seance', 'objet', 'url', 'ordre', 'parent_id', 'updated_at'
            ]);
            $this->stats['sections_diverses'] += count($data);
        }
    }

    private function importInterventionsLegislatives(array $columns, array $buffer): void
    {
        $data = [];
        foreach ($buffer as $row) {
            $record = $this->mapRow($columns, $row);
            $id = (int) ($record['intpjlcle'] ?? 0);
            
            if (!$id) continue;

            $data[] = [
                'id' => $id,
                'auteur_code' => trim($record['autcod'] ?? ''),
                'section_id' => (int) ($record['secdiscle'] ?? 0),
                'analyse' => $this->cleanValue($record['intana'] ?? null),
                'fonction' => $this->cleanValue($record['intfon'] ?? null),
                'url' => $this->cleanValue($record['inturl'] ?? null),
                'ordre' => $this->cleanValue($record['intordid'] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($data)) {
            DB::table('senat_interventions_legislatives')->upsert($data, ['id'], [
                'auteur_code', 'section_id', 'analyse', 'fonction', 'url', 'ordre', 'updated_at'
            ]);
            $this->stats['interventions_legislatives'] += count($data);
        }
    }

    private function importInterventionsDiverses(array $columns, array $buffer): void
    {
        $data = [];
        foreach ($buffer as $row) {
            $record = $this->mapRow($columns, $row);
            $id = (int) ($record['intdiverscle'] ?? 0);
            
            if (!$id) continue;

            $data[] = [
                'id' => $id,
                'auteur_code' => trim($record['autcod'] ?? ''),
                'section_id' => (int) ($record['secdiverscle'] ?? 0),
                'analyse' => $this->cleanValue($record['intana'] ?? null),
                'fonction' => $this->cleanValue($record['intfon'] ?? null),
                'url' => $this->cleanValue($record['inturl'] ?? null),
                'ordre' => $this->cleanValue($record['intdiversordid'] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($data)) {
            DB::table('senat_interventions_diverses')->upsert($data, ['id'], [
                'auteur_code', 'section_id', 'analyse', 'fonction', 'url', 'ordre', 'updated_at'
            ]);
            $this->stats['interventions_diverses'] += count($data);
        }
    }

    private function mapRow(array $columns, array $values): array
    {
        $result = [];
        foreach ($columns as $i => $column) {
            $result[$column] = $values[$i] ?? null;
        }
        return $result;
    }

    private function cleanValue(?string $value): ?string
    {
        if ($value === null || $value === '\\N' || $value === '') {
            return null;
        }
        return trim($value);
    }

    private function displayStats(): void
    {
        $this->newLine();
        $this->info('📊 Statistiques d\'import:');
        $this->table(
            ['Table', 'Enregistrements'],
            [
                ['senat_debats', number_format($this->stats['debats'])],
                ['senat_sections_discussion', number_format($this->stats['sections_discussion'])],
                ['senat_sections_diverses', number_format($this->stats['sections_diverses'])],
                ['senat_interventions_legislatives', number_format($this->stats['interventions_legislatives'])],
                ['senat_interventions_diverses', number_format($this->stats['interventions_diverses'])],
                ['senat_types_section', number_format($this->stats['types_section'])],
                ['senat_lectures_debats', number_format($this->stats['lectures_debats'])],
            ]
        );

        $total = array_sum($this->stats);
        $this->info("   Total: " . number_format($total) . " enregistrements");
        $this->newLine();
        $this->info('✅ Import terminé!');
    }
}
