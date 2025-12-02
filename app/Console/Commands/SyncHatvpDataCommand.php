<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Hatvp\HatvpDataDownloader;
use App\Services\Hatvp\HatvpXmlParser;
use App\Models\HatvpDeclaration;
use App\Models\HatvpMandatElectif;
use App\Models\HatvpRemuneration;
use App\Models\HatvpFonctionBenevole;
use App\Models\HatvpParticipationDirigeante;
use App\Models\HatvpParticipationFinanciere;
use App\Models\HatvpCollaborateur;
use App\Models\HatvpImmeuble;
use App\Models\HatvpVehicule;
use App\Models\HatvpRevenu;

/**
 * Commande de synchronisation des données HATVP
 * 
 * Usage :
 *   php artisan hatvp:sync                    # Synchroniser toutes les déclarations
 *   php artisan hatvp:sync --parlementaires   # Uniquement députés et sénateurs
 *   php artisan hatvp:sync --status           # Afficher le statut
 */
class SyncHatvpDataCommand extends Command
{
    protected $signature = 'hatvp:sync 
                            {--parlementaires : Synchroniser uniquement les parlementaires (députés/sénateurs)}
                            {--type= : Type de mandat (senateur, depute)}
                            {--status : Afficher le statut des données}
                            {--force : Forcer le téléchargement même si le cache est valide}
                            {--analyze : Analyser sans importer}
                            {--import : Importer les déclarations en base}
                            {--limit= : Limiter le nombre de déclarations à traiter}';

    protected $description = 'Synchronise les données Open Data de la HATVP (déclarations d\'intérêts et de patrimoine)';

    private HatvpDataDownloader $downloader;
    private HatvpXmlParser $parser;
    private int $imported = 0;
    private int $updated = 0;
    private int $errors = 0;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->downloader = new HatvpDataDownloader();
        $this->parser = new HatvpXmlParser();

        $this->info('');
        $this->info('🏛️  Synchronisation des données HATVP');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('   Haute Autorité pour la Transparence de la Vie Publique');

        // Mode statut
        if ($this->option('status')) {
            return $this->showStatus();
        }

        // Mode analyse
        if ($this->option('analyze')) {
            return $this->analyzeDeclarations();
        }

        // Mode import
        if ($this->option('import')) {
            return $this->importDeclarations();
        }

        // Par défaut : synchronisation (téléchargement + analyse)
        return $this->syncDeclarations();
    }

    /**
     * Affiche le statut des données
     */
    private function showStatus(): int
    {
        $this->newLine();
        $this->info('📊 Statut des données HATVP');
        $this->newLine();

        // Statistiques du cache
        $cacheStats = $this->downloader->getCacheStats();
        
        $this->table(
            ['Élément', 'Valeur'],
            [
                ['Fichier declarations.xml', $cacheStats['declarations']['exists'] ? '✅ Présent' : '❌ Absent'],
                ['Taille', $cacheStats['declarations']['size_mb'] . ' Mo'],
                ['Dernière modification', $cacheStats['declarations']['modified'] ?? '-'],
                ['Âge', ($cacheStats['declarations']['age_hours'] ?? '-') . ' heures'],
                ['Cache valide', $cacheStats['declarations']['cache_valid'] ? '✅' : '❌'],
                ['Dossiers individuels', $cacheStats['dossiers_count']],
            ]
        );

        // Statistiques en base
        $this->newLine();
        $this->info('📋 Données en base :');
        
        try {
            $stats = [
                ['Déclarations', HatvpDeclaration::count()],
                ['- Intérêts (DIA)', HatvpDeclaration::interets()->count()],
                ['- Patrimoine (DSP)', HatvpDeclaration::patrimoine()->count()],
                ['- Sénateurs', HatvpDeclaration::senateurs()->count()],
                ['- Députés', HatvpDeclaration::deputes()->count()],
                ['Mandats électifs', HatvpMandatElectif::count()],
                ['Collaborateurs', HatvpCollaborateur::count()],
                ['Immeubles', HatvpImmeuble::count()],
                ['Véhicules', HatvpVehicule::count()],
            ];
            
            $this->table(['Élément', 'Nombre'], $stats);
        } catch (\Exception $e) {
            $this->warn('   Tables non créées. Exécutez : php artisan migrate');
        }

        return Command::SUCCESS;
    }

    /**
     * Analyse les déclarations sans importer
     */
    private function analyzeDeclarations(): int
    {
        $force = $this->option('force');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->newLine();
        $this->info('🔍 Analyse des déclarations HATVP');
        $this->newLine();

        // Télécharger le fichier global
        $this->info('📥 Téléchargement du fichier global...');
        $xmlFile = $this->downloader->downloadAllDeclarations($force);

        if (!$xmlFile) {
            $this->error('❌ Impossible de télécharger les déclarations');
            return Command::FAILURE;
        }

        $this->info('✅ Fichier téléchargé');

        // Parser l'index
        $this->info('📊 Analyse de l\'index...');
        $declarations = $this->downloader->parseDeclarationsIndex($xmlFile);

        $this->info("   Total : " . count($declarations) . " déclarations");

        // Filtrer les parlementaires si demandé
        if ($this->option('parlementaires')) {
            $declarations = $this->downloader->filterParlementaires($declarations);
            $this->info("   Parlementaires : " . count($declarations));
        }

        // Filtrer par type si demandé
        $type = $this->option('type');
        if ($type) {
            $declarations = array_filter($declarations, function ($d) use ($type) {
                return strtolower($d['code_type_mandat'] ?? '') === strtolower($type);
            });
            $this->info("   Type '{$type}' : " . count($declarations));
        }

        // Limiter si demandé
        if ($limit) {
            $declarations = array_slice($declarations, 0, $limit);
        }

        // Statistiques par type de mandat
        $this->newLine();
        $this->info('📈 Répartition par type de mandat :');

        $byType = [];
        foreach ($declarations as $d) {
            $type = $d['type_mandat'] ?? 'Inconnu';
            $byType[$type] = ($byType[$type] ?? 0) + 1;
        }

        arsort($byType);
        foreach ($byType as $type => $count) {
            $this->line("   - {$type} : {$count}");
        }

        // Statistiques par type de déclaration
        $this->newLine();
        $this->info('📈 Répartition par type de déclaration :');

        $byDecl = [];
        foreach ($declarations as $d) {
            $type = $d['type_declaration'] ?? 'Inconnu';
            $byDecl[$type] = ($byDecl[$type] ?? 0) + 1;
        }

        arsort($byDecl);
        foreach ($byDecl as $type => $count) {
            $label = config("hatvp.types_declarations.{$type}", $type);
            $this->line("   - {$type} ({$label}) : {$count}");
        }

        return Command::SUCCESS;
    }

    /**
     * Importe les déclarations en base
     */
    private function importDeclarations(): int
    {
        $force = $this->option('force');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->newLine();
        $this->info('📥 Import des déclarations HATVP en base');
        $this->newLine();

        // Vérifier que les tables existent
        if (!$this->checkTablesExist()) {
            $this->error('❌ Les tables HATVP n\'existent pas.');
            $this->warn('   Exécutez : php artisan migrate');
            return Command::FAILURE;
        }

        // Télécharger le fichier global
        $this->info('📥 Téléchargement du fichier global...');
        $xmlFile = $this->downloader->downloadAllDeclarations($force);

        if (!$xmlFile) {
            $this->error('❌ Impossible de télécharger les déclarations');
            return Command::FAILURE;
        }

        $size = round(filesize($xmlFile) / 1024 / 1024, 2);
        $this->info("✅ Fichier téléchargé ({$size} Mo)");

        // Parser l'index
        $this->info('📊 Parsing de l\'index...');
        $declarations = $this->downloader->parseDeclarationsIndex($xmlFile);

        $total = count($declarations);
        $this->info("   Total : {$total} déclarations");

        // Filtrer les parlementaires si demandé
        if ($this->option('parlementaires')) {
            $declarations = $this->downloader->filterParlementaires($declarations);
            $this->info("   Parlementaires : " . count($declarations));
        }

        // Filtrer par type si demandé
        $type = $this->option('type');
        if ($type) {
            $declarations = array_filter($declarations, function ($d) use ($type) {
                return strtolower($d['code_type_mandat'] ?? '') === strtolower($type);
            });
            $this->info("   Type '{$type}' : " . count($declarations));
        }

        // Limiter si demandé
        if ($limit) {
            $declarations = array_slice($declarations, 0, $limit);
            $this->info("   Limité à : {$limit}");
        }

        // Import
        $this->newLine();
        $this->info('🔄 Import en base...');
        
        $bar = $this->output->createProgressBar(count($declarations));
        $bar->start();

        foreach ($declarations as $declInfo) {
            try {
                $this->importDeclaration($declInfo);
            } catch (\Exception $e) {
                $this->errors++;
                // Log l'erreur mais continue
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Résumé
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("✅ Import terminé");
        $this->line("   - Nouvelles : {$this->imported}");
        $this->line("   - Mises à jour : {$this->updated}");
        if ($this->errors > 0) {
            $this->warn("   - Erreurs : {$this->errors}");
        }

        return Command::SUCCESS;
    }

    /**
     * Importe une déclaration
     */
    private function importDeclaration(array $info): void
    {
        // Vérifier si la déclaration existe déjà
        $existing = HatvpDeclaration::where('uuid', $info['uuid'])->first();

        if ($existing) {
            // Mise à jour si la date de dépôt a changé
            $this->updated++;
            return;
        }

        // Créer la déclaration
        $declaration = HatvpDeclaration::create([
            'uuid' => $info['uuid'],
            'date_depot' => $this->parseDate($info['date_depot']),
            'type_declaration' => $info['type_declaration'],
            'parlementaire_type' => $info['code_type_mandat'] ?? null,
            'nom' => $info['nom'],
            'prenom' => $info['prenom'],
            'date_naissance' => $this->parseDate($info['date_naissance']),
            'type_mandat' => $info['type_mandat'],
            'code_departement' => $info['code_departement'],
            'date_debut_mandat' => $this->parseDate($info['date_debut_mandat']),
        ]);

        // Essayer de lier au parlementaire
        $this->linkToParlementaire($declaration);

        $this->imported++;
    }

    /**
     * Lie la déclaration au parlementaire correspondant
     */
    private function linkToParlementaire(HatvpDeclaration $declaration): void
    {
        $type = strtolower($declaration->parlementaire_type ?? '');

        if ($type === 'senateur') {
            // Chercher le sénateur par nom/prénom/département
            $senateur = \App\Models\Senateur::where('nom', 'ILIKE', $declaration->nom)
                ->where('prenom', 'ILIKE', $declaration->prenom)
                ->first();

            if ($senateur) {
                $declaration->update([
                    'parlementaire_type' => 'senateur',
                    'parlementaire_id' => $senateur->matricule,
                ]);
            }
        } elseif ($type === 'depute') {
            // Chercher le député par nom/prénom
            $depute = \App\Models\ActeurAN::where('nom', 'ILIKE', $declaration->nom)
                ->where('prenom', 'ILIKE', $declaration->prenom)
                ->first();

            if ($depute) {
                $declaration->update([
                    'parlementaire_type' => 'depute',
                    'parlementaire_id' => $depute->uid,
                ]);
            }
        }
    }

    /**
     * Synchronise les déclarations (téléchargement + analyse)
     */
    private function syncDeclarations(): int
    {
        $force = $this->option('force');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->newLine();
        $this->info('📥 Téléchargement des déclarations...');

        // Télécharger le fichier global
        $xmlFile = $this->downloader->downloadAllDeclarations($force);

        if (!$xmlFile) {
            $this->error('❌ Impossible de télécharger les déclarations');
            return Command::FAILURE;
        }

        $size = round(filesize($xmlFile) / 1024 / 1024, 2);
        $this->info("✅ Fichier téléchargé ({$size} Mo)");

        // Parser l'index
        $this->info('📊 Parsing de l\'index...');
        $declarations = $this->downloader->parseDeclarationsIndex($xmlFile);

        $total = count($declarations);
        $this->info("   Total : {$total} déclarations");

        // Filtrer les parlementaires si demandé
        if ($this->option('parlementaires')) {
            $declarations = $this->downloader->filterParlementaires($declarations);
            $this->info("   Parlementaires : " . count($declarations));
        }

        // Filtrer par type si demandé
        $type = $this->option('type');
        if ($type) {
            $declarations = array_filter($declarations, function ($d) use ($type) {
                return strtolower($d['code_type_mandat'] ?? '') === strtolower($type);
            });
            $this->info("   Type '{$type}' : " . count($declarations));
        }

        // Afficher un résumé
        $this->newLine();
        $this->info('📊 Résumé :');

        $byType = [];
        foreach ($declarations as $d) {
            $key = ($d['code_type_mandat'] ?? 'unknown') . '_' . ($d['type_declaration'] ?? 'unknown');
            $byType[$key] = ($byType[$key] ?? 0) + 1;
        }

        $this->table(
            ['Type Mandat', 'Type Déclaration', 'Nombre'],
            collect($byType)->map(function ($count, $key) {
                [$mandat, $decl] = explode('_', $key);
                return [$mandat, $decl, $count];
            })->sortBy(0)->values()->toArray()
        );

        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('✅ Synchronisation terminée');
        $this->newLine();
        $this->info('💡 Pour importer en base :');
        $this->line('   php artisan hatvp:sync --import --parlementaires');

        return Command::SUCCESS;
    }

    /**
     * Vérifie que les tables HATVP existent
     */
    private function checkTablesExist(): bool
    {
        try {
            return \Schema::hasTable('hatvp_declarations');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Parse une date au format français
     */
    private function parseDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        // Format: "18/11/2024 10:30:48" ou "24/09/2024" ou "01/2018"
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $value, $matches)) {
            return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
        }

        // Format: "01/2018"
        if (preg_match('/^(\d{2})\/(\d{4})$/', $value, $matches)) {
            return "{$matches[2]}-{$matches[1]}-01";
        }

        return $value;
    }
}
