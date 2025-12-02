<?php

namespace App\Console\Commands;

use App\Services\AssembleeNationale\XmlDownloader;
use App\Services\AssembleeNationale\XmlParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncAnDataCommand extends Command
{
    protected $signature = 'an:sync 
                            {source? : Source à synchroniser (scrutins, deputes_actifs, etc.)}
                            {--legislature=17 : Numéro de la législature}
                            {--fresh : Vide les tables avant l\'import}
                            {--skip-download : Utilise les fichiers déjà téléchargés}
                            {--limit= : Limite le nombre d\'éléments à importer}
                            {--dry-run : Simule l\'import sans modifier la base}
                            {--sample= : Affiche un échantillon d\'éléments}';

    protected $description = 'Synchronise les données de l\'Assemblée Nationale depuis les sources XML';

    protected int $imported = 0;
    protected int $updated = 0;
    protected int $skipped = 0;
    protected int $errors = 0;

    public function handle(): int
    {
        $legislature = (int) $this->option('legislature');
        $source = $this->argument('source');

        $this->info("🏛️  Synchronisation AN - Législature {$legislature}");
        $this->newLine();

        // Mode échantillon
        if ($this->option('sample')) {
            return $this->showSample($source, $legislature);
        }

        try {
            if ($source) {
                return $this->syncSource($source, $legislature);
            } else {
                return $this->syncAll($legislature);
            }
        } catch (\Exception $e) {
            $this->error("❌ Erreur : {$e->getMessage()}");
            Log::channel('an-sync')->error("Sync error: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString()
            ]);
            return self::FAILURE;
        }
    }

    /**
     * Synchronise une source spécifique
     */
    protected function syncSource(string $source, int $legislature): int
    {
        $sources = config('assemblee-nationale.sources');
        
        if (!isset($sources[$source])) {
            $this->error("❌ Source inconnue : {$source}");
            return self::FAILURE;
        }

        $sourceConfig = $sources[$source];
        $this->info("📥 Synchronisation de : {$source}");
        $this->info("   {$sourceConfig['description']}");
        $this->newLine();

        // Étape 1 : Téléchargement
        if (!$this->option('skip-download')) {
            $this->info("1️⃣  Téléchargement...");
            $downloader = new XmlDownloader($legislature);
            $downloadResult = $downloader->download($source);
            
            if ($downloadResult['status'] === 'error') {
                $this->error("   ❌ Échec du téléchargement");
                return self::FAILURE;
            }
            
            $this->info("   ✅ " . ($downloadResult['status'] === 'cached' ? 'Cache utilisé' : 'Téléchargé'));
        }

        // Étape 2 : Parsing et import
        $this->info("2️⃣  Import des données...");
        
        $downloader = $downloader ?? new XmlDownloader($legislature);
        $xmlPath = $downloader->getXmlPath($source);
        
        $parser = new XmlParser($source);
        
        // Compter les éléments
        $this->info("   📊 Comptage des éléments...");
        $totalCount = $parser->count($xmlPath);
        $this->info("   📊 {$totalCount} éléments trouvés");

        if ($this->option('dry-run')) {
            $this->warn("   ⚠️  Mode dry-run : aucune modification");
            return self::SUCCESS;
        }

        // Fresh mode
        if ($this->option('fresh')) {
            $this->warn("   ⚠️  Mode fresh : suppression des données existantes...");
            $modelClass = $sourceConfig['model'];
            if (class_exists($modelClass)) {
                $modelClass::truncate();
            }
        }

        // Import
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $bar = $this->output->createProgressBar($limit ?? $totalCount);
        $bar->start();

        $count = 0;
        foreach ($parser->parse($xmlPath) as $data) {
            try {
                $this->importElement($source, $data, $sourceConfig);
            } catch (\Exception $e) {
                $this->errors++;
                Log::channel('an-sync')->warning("Import error: {$e->getMessage()}", [
                    'source' => $source,
                    'data' => array_slice($data, 0, 5), // Log partiel pour debug
                ]);
            }
            
            $bar->advance();
            $count++;
            
            if ($limit && $count >= $limit) {
                break;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->displaySummary();

        return $this->errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Synchronise toutes les sources
     */
    protected function syncAll(int $legislature): int
    {
        $sources = config('assemblee-nationale.sources');
        
        // Trier par priorité
        uasort($sources, fn($a, $b) => ($a['priority'] ?? 99) <=> ($b['priority'] ?? 99));

        $this->info("📥 Synchronisation de " . count($sources) . " sources...");
        $this->newLine();

        $results = [];
        
        foreach (array_keys($sources) as $sourceKey) {
            $this->newLine();
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            
            // Reset counters
            $this->imported = 0;
            $this->updated = 0;
            $this->skipped = 0;
            $this->errors = 0;
            
            $result = $this->syncSource($sourceKey, $legislature);
            
            $results[$sourceKey] = [
                'status' => $result === self::SUCCESS ? 'success' : 'error',
                'imported' => $this->imported,
                'updated' => $this->updated,
                'errors' => $this->errors,
            ];
        }

        // Résumé global
        $this->newLine();
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📊 RÉSUMÉ GLOBAL");
        $this->newLine();

        $this->table(
            ['Source', 'Statut', 'Importés', 'Mis à jour', 'Erreurs'],
            collect($results)->map(function ($result, $key) {
                return [
                    $key,
                    $result['status'] === 'success' ? '✅' : '❌',
                    $result['imported'],
                    $result['updated'],
                    $result['errors'],
                ];
            })->toArray()
        );

        $hasErrors = collect($results)->contains(fn($r) => $r['status'] === 'error');
        
        return $hasErrors ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Importe un élément selon la source
     */
    protected function importElement(string $source, array $data, array $config): void
    {
        $modelClass = $config['model'];
        $parser = $config['parser'];

        // Appeler le parser spécifique
        $method = "parse" . ucfirst($parser);
        
        if (method_exists($this, $method)) {
            $parsed = $this->$method($data);
        } else {
            // Parser générique
            $parsed = $data;
        }

        if (empty($parsed)) {
            $this->skipped++;
            return;
        }

        // Déterminer la clé unique
        $uid = $parsed['uid'] ?? $parsed['id'] ?? null;
        
        if (!$uid) {
            $this->skipped++;
            return;
        }

        // Upsert
        $model = $modelClass::updateOrCreate(
            ['uid' => $uid],
            $parsed
        );

        if ($model->wasRecentlyCreated) {
            $this->imported++;
        } else {
            $this->updated++;
        }
    }

    /**
     * Parser pour les scrutins
     */
    protected function parseScrutins(array $data): array
    {
        $uid = $data['uid'] ?? null;
        if (!$uid) return [];

        $syntheseVote = $data['syntheseVote'] ?? [];

        return [
            'uid' => $uid,
            'numero' => (int) ($data['numero'] ?? 0),
            'legislature' => (int) ($data['legislature'] ?? 17),
            'session_ref' => $data['sessionRef'] ?? null,
            'date_scrutin' => $data['dateScrutin'] ?? null,
            'titre' => $data['titre'] ?? null,
            'sort' => $data['sort'] ?? null,
            'nombre_votants' => (int) ($syntheseVote['nombreVotants'] ?? 0),
            'suffrages_exprimes' => (int) ($syntheseVote['suffragesExprimes'] ?? 0),
            'pour' => (int) ($syntheseVote['pour'] ?? 0),
            'contre' => (int) ($syntheseVote['contre'] ?? 0),
            'abstentions' => (int) ($syntheseVote['nlesAbstentions'] ?? 0),
            'donnees_source' => json_encode($data),
        ];
    }

    /**
     * Parser pour les députés
     */
    protected function parseDeputes(array $data): array
    {
        $uid = $data['uid']['#text'] ?? $data['uid'] ?? null;
        if (!$uid) return [];

        $etatCivil = $data['etatCivil'] ?? [];
        $ident = $etatCivil['ident'] ?? [];
        $infoNaissance = $etatCivil['infoNaissance'] ?? [];
        $profession = $data['profession'] ?? [];

        return [
            'uid' => $uid,
            'civilite' => $ident['civ'] ?? null,
            'prenom' => $ident['prenom'] ?? null,
            'nom' => $ident['nom'] ?? null,
            'date_naissance' => $infoNaissance['dateNais'] ?? null,
            'ville_naissance' => $infoNaissance['villeNais'] ?? null,
            'departement_naissance' => $infoNaissance['depNais'] ?? null,
            'profession_libelle' => $profession['libelleCourant'] ?? null,
            'donnees_source' => json_encode($data),
        ];
    }

    /**
     * Parser pour les acteurs (générique)
     */
    protected function parseActeurs(array $data): array
    {
        return $this->parseDeputes($data);
    }

    /**
     * Parser pour les organes
     */
    protected function parseOrganes(array $data): array
    {
        $uid = $data['uid'] ?? null;
        if (!$uid) return [];

        return [
            'uid' => $uid,
            'code_type' => $data['codeType'] ?? null,
            'libelle' => $data['libelle'] ?? null,
            'libelle_abrege' => $data['libelleAbrege'] ?? null,
            'date_debut' => $data['viMoDe']['dateDebut'] ?? null,
            'date_fin' => $data['viMoDe']['dateFin'] ?? null,
            'couleur' => $data['couleurAssociee'] ?? null,
            'donnees_source' => json_encode($data),
        ];
    }

    /**
     * Parser pour les amendements
     */
    protected function parseAmendements(array $data): array
    {
        $uid = $data['uid'] ?? null;
        if (!$uid) return [];

        $identifiant = $data['identifiant'] ?? [];
        $corps = $data['corps'] ?? [];
        $sort = $data['sort'] ?? [];
        $signataires = $data['signataires'] ?? [];
        
        // Premier auteur
        $auteur = $signataires['auteur'] ?? null;
        if (is_array($auteur) && isset($auteur[0])) {
            $auteur = $auteur[0];
        }

        return [
            'uid' => $uid,
            'numero' => $identifiant['numero'] ?? null,
            'legislature' => (int) ($identifiant['legislature'] ?? 17),
            'texte_ref' => $data['texteLegislatifRef'] ?? null,
            'auteur_ref' => is_array($auteur) ? ($auteur['acteurRef'] ?? null) : null,
            'dispositif' => $corps['dispositif'] ?? null,
            'expose_sommaire' => $corps['exposeSommaire'] ?? null,
            'date_depot' => $sort['dateSort'] ?? null,
            'sort' => $sort['sortEnSeance'] ?? null,
            'donnees_source' => json_encode($data),
        ];
    }

    /**
     * Affiche un échantillon d'éléments
     */
    protected function showSample(?string $source, int $legislature): int
    {
        if (!$source) {
            $this->error("❌ Veuillez spécifier une source avec --sample");
            return self::FAILURE;
        }

        $limit = (int) $this->option('sample');
        
        $downloader = new XmlDownloader($legislature);
        $xmlPath = $downloader->getXmlPath($source);
        
        if (!is_dir($xmlPath)) {
            $this->warn("⚠️  Fichiers non téléchargés, téléchargement...");
            $downloader->download($source);
        }

        $parser = new XmlParser($source);
        $samples = $parser->sample($xmlPath, $limit);

        $this->info("📋 Échantillon de {$limit} éléments pour : {$source}");
        $this->newLine();

        foreach ($samples as $i => $sample) {
            $this->line("━━━ Élément " . ($i + 1) . " ━━━");
            $this->line(json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->newLine();
        }

        return self::SUCCESS;
    }

    /**
     * Affiche le résumé
     */
    protected function displaySummary(): void
    {
        $this->info("📊 Résumé :");
        $this->info("   ✅ Importés : {$this->imported}");
        $this->info("   🔄 Mis à jour : {$this->updated}");
        $this->info("   ⏭️  Ignorés : {$this->skipped}");
        
        if ($this->errors > 0) {
            $this->warn("   ❌ Erreurs : {$this->errors}");
        }
    }
}

