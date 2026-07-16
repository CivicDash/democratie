<?php

namespace App\Console\Commands;

use App\Models\HatvpActiviteConsultant;
use App\Models\HatvpActiviteProfessionnelle;
use App\Models\HatvpCollaborateur;
use App\Models\HatvpDeclaration;
use App\Models\HatvpFonctionBenevole;
use App\Models\HatvpImmeuble;
use App\Models\HatvpMandatElectif;
use App\Models\HatvpParticipationDirigeante;
use App\Models\HatvpParticipationFinanciere;
use App\Models\HatvpRemunerationActivitePro;
use App\Models\HatvpRemunerationConsultant;
use App\Models\HatvpRemunerationDirigeant;
use App\Models\HatvpRevenu;
use App\Models\HatvpVehicule;
use App\Services\Hatvp\HatvpDataDownloader;
use App\Services\Hatvp\HatvpXmlParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Commande de synchronisation des données HATVP
 *
 * Usage :
 *   php artisan hatvp:sync                    # Synchroniser toutes les déclarations
 *   php artisan hatvp:sync --parlementaires   # Uniquement députés et sénateurs
 *   php artisan hatvp:sync --status           # Afficher le statut
 *   php artisan hatvp:sync --import-details   # Importer les détails des déclarations
 */
class SyncHatvpDataCommand extends Command
{
    protected $signature = 'hatvp:sync 
                            {--parlementaires : Synchroniser uniquement les parlementaires (députés/sénateurs)}
                            {--type= : Type de mandat (senateur, depute)}
                            {--status : Afficher le statut des données}
                            {--force : Forcer le téléchargement même si le cache est valide}
                            {--analyze : Analyser sans importer}
                            {--import : Importer les déclarations en base (métadonnées)}
                            {--import-details : Importer les détails complets des déclarations}
                            {--presidentielle : Cibler UNIQUEMENT les déclarations rattachées à un candidat présidentielle (personne_politique_id) ; implique intérêts seulement (pas de patrimoine)}
                            {--dia-only : N\'importer que les intérêts (DIA) — saute les sections de patrimoine (DSP)}
                            {--limit= : Limiter le nombre de déclarations à traiter}
                            {--verbose-details : Afficher les détails de chaque déclaration}';

    protected $description = 'Synchronise les données Open Data de la HATVP (déclarations d\'intérêts et de patrimoine)';

    private HatvpDataDownloader $downloader;

    private HatvpXmlParser $parser;

    private int $imported = 0;

    private int $updated = 0;

    private int $errors = 0;

    private int $detailsImported = 0;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->downloader = new HatvpDataDownloader;
        $this->parser = new HatvpXmlParser;

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

        // Mode import détails complets
        if ($this->option('import-details')) {
            return $this->importDeclarationsDetails();
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
                ['Taille', $cacheStats['declarations']['size_mb'].' Mo'],
                ['Dernière modification', $cacheStats['declarations']['modified'] ?? '-'],
                ['Âge', ($cacheStats['declarations']['age_hours'] ?? '-').' heures'],
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

        if (! $xmlFile) {
            $this->error('❌ Impossible de télécharger les déclarations');

            return Command::FAILURE;
        }

        $this->info('✅ Fichier téléchargé');

        // Parser l'index
        $this->info('📊 Analyse de l\'index...');
        $declarations = $this->downloader->parseDeclarationsIndex($xmlFile);

        $this->info('   Total : '.count($declarations).' déclarations');

        // Filtrer les parlementaires si demandé
        if ($this->option('parlementaires')) {
            $declarations = $this->downloader->filterParlementaires($declarations);
            $this->info('   Parlementaires : '.count($declarations));
        }

        // Filtrer par type si demandé
        $type = $this->option('type');
        if ($type) {
            $declarations = array_filter($declarations, function ($d) use ($type) {
                return strtolower($d['code_type_mandat'] ?? '') === strtolower($type);
            });
            $this->info("   Type '{$type}' : ".count($declarations));
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
        if (! $this->checkTablesExist()) {
            $this->error('❌ Les tables HATVP n\'existent pas.');
            $this->warn('   Exécutez : php artisan migrate');

            return Command::FAILURE;
        }

        // Télécharger le fichier global
        $this->info('📥 Téléchargement du fichier global...');
        $xmlFile = $this->downloader->downloadAllDeclarations($force);

        if (! $xmlFile) {
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
            $this->info('   Parlementaires : '.count($declarations));
        }

        // Filtrer par type si demandé
        $type = $this->option('type');
        if ($type) {
            $declarations = array_filter($declarations, function ($d) use ($type) {
                return strtolower($d['code_type_mandat'] ?? '') === strtolower($type);
            });
            $this->info("   Type '{$type}' : ".count($declarations));
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
        $this->info('✅ Import terminé');
        $this->line("   - Nouvelles : {$this->imported}");
        $this->line("   - Mises à jour : {$this->updated}");
        if ($this->errors > 0) {
            $this->warn("   - Erreurs : {$this->errors}");
        }

        return Command::SUCCESS;
    }

    /**
     * Importe une déclaration (métadonnées uniquement)
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
     * Importe les détails complets des déclarations
     * Les données sont extraites directement du fichier declarations.xml global
     */
    private function importDeclarationsDetails(): int
    {
        $force = $this->option('force');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $verboseDetails = $this->option('verbose-details');

        $this->newLine();
        $this->info('📥 Import des DÉTAILS des déclarations HATVP');
        $this->info('   (mandats, rémunérations, collaborateurs, patrimoine...)');
        $this->newLine();

        // Télécharger/utiliser le fichier global
        $this->info('📥 Chargement du fichier global declarations.xml...');
        $xmlFile = $this->downloader->downloadAllDeclarations($force);

        if (! $xmlFile || ! file_exists($xmlFile)) {
            $this->error('❌ Impossible de charger le fichier declarations.xml');

            return Command::FAILURE;
        }

        $size = round(filesize($xmlFile) / 1024 / 1024, 2);
        $this->info("✅ Fichier chargé ({$size} Mo)");
        $this->newLine();

        // Parser le fichier XML avec XMLReader pour les gros fichiers
        $this->info('🔄 Parsing et import des détails...');

        $reader = new \XMLReader;
        $reader->open($xmlFile);

        $count = 0;
        $imported = 0;
        $skipped = 0;

        $parlementairesOnly = $this->option('parlementaires');
        $typeFilter = $this->option('type');
        $presidentielleOnly = (bool) $this->option('presidentielle');
        $diaOnly = (bool) $this->option('dia-only') || $presidentielleOnly;

        // Ciblage présidentielle : ne traiter que les déclarations RATTACHÉES (BO) à un candidat 2027.
        $uuidsCibles = null;
        if ($presidentielleOnly) {
            $personneIds = \App\Models\CandidatPresidentielle::where('election', '2027')
                ->whereNotNull('personne_politique_id')->pluck('personne_politique_id')->unique();
            $uuidsCibles = HatvpDeclaration::whereIn('personne_politique_id', $personneIds)
                ->pluck('uuid')->flip()->all();
            $this->info('   🎯 Ciblage présidentielle : '.count($uuidsCibles).' déclaration(s) rattachée(s) — intérêts (DIA) seulement.');
            if (empty($uuidsCibles)) {
                $this->warn('   Aucune déclaration rattachée à un candidat 2027 — rattachez-les au back-office d\'abord.');
                $reader->close();

                return Command::SUCCESS;
            }
        }

        while ($reader->read()) {
            if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'declaration') {
                $node = $reader->readOuterXml();

                // Parser la déclaration complète
                $data = $this->parser->parseContent($node);

                if (! $data) {
                    $skipped++;

                    continue;
                }

                // Filtrer les parlementaires si demandé
                $typeMandat = strtolower($data['general']['code_type_mandat_fichier'] ?? '');

                if ($parlementairesOnly && ! in_array($typeMandat, ['senateur', 'depute'])) {
                    $skipped++;

                    continue;
                }

                if ($typeFilter && $typeMandat !== strtolower($typeFilter)) {
                    $skipped++;

                    continue;
                }

                // Trouver ou créer la déclaration en base
                $uuid = $data['uuid'] ?? null;
                if (! $uuid) {
                    $skipped++;

                    continue;
                }

                // Ciblage présidentielle : ignorer toute déclaration non rattachée à un candidat 2027.
                if ($uuidsCibles !== null && ! isset($uuidsCibles[$uuid])) {
                    $skipped++;

                    continue;
                }

                $declaration = HatvpDeclaration::where('uuid', $uuid)->first();

                if (! $declaration) {
                    // Créer la déclaration si elle n'existe pas
                    $declarant = $data['general']['declarant'] ?? [];
                    $declaration = HatvpDeclaration::create([
                        'uuid' => $uuid,
                        'date_depot' => $data['date_depot'] ?? null,
                        'type_declaration' => $data['type_declaration'] ?? null,
                        'parlementaire_type' => $typeMandat ?: null,
                        'nom' => strtoupper($declarant['nom'] ?? ''),
                        'prenom' => $declarant['prenom'] ?? '',
                        'date_naissance' => $declarant['date_naissance'] ?? null,
                        'type_mandat' => $data['general']['type_mandat'] ?? null,
                        'code_departement' => $data['general']['code_organe'] ?? null,
                        'date_debut_mandat' => $data['general']['date_debut_mandat'] ?? null,
                    ]);
                }

                // Importer les détails
                try {
                    $stats = $this->importDeclarationDetailsFromData($declaration, $data, $diaOnly);
                    $imported++;
                    $this->detailsImported++;

                    if ($verboseDetails) {
                        $this->line("   ✓ {$declaration->nom} {$declaration->prenom} - {$stats['mandats']} mandats, {$stats['collaborateurs']} collab., {$stats['revenus_total']}€");
                    }
                } catch (\Exception $e) {
                    $this->errors++;
                    if ($verboseDetails) {
                        $this->error("   ✗ {$declaration->nom} : ".$e->getMessage());
                    }
                }

                $count++;

                if ($count % 100 === 0) {
                    $this->line("   Traité : {$count} déclarations...");
                }

                if ($limit && $imported >= $limit) {
                    $this->info("   Limite de {$limit} atteinte.");
                    break;
                }
            }
        }

        $reader->close();

        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('✅ Import des détails terminé');
        $this->line("   - Traitées : {$count}");
        $this->line("   - Enrichies : {$this->detailsImported}");
        $this->line("   - Ignorées : {$skipped}");
        if ($this->errors > 0) {
            $this->warn("   - Erreurs : {$this->errors}");
        }

        return Command::SUCCESS;
    }

    /**
     * Importe les détails d'une déclaration à partir des données parsées
     */
    private function importDeclarationDetailsFromData(HatvpDeclaration $declaration, array $data, bool $diaOnly = false): array
    {
        $stats = [
            'mandats' => 0,
            'collaborateurs' => 0,
            'activites_pro' => 0,
            'dirigeant' => 0,
            'revenus_total' => 0,
        ];

        // Transaction pour l'intégrité
        DB::transaction(function () use ($declaration, $data, &$stats) {
            // 1. Importer les mandats électifs avec rémunérations
            if (! empty($data['mandats_electifs']['items'])) {
                foreach ($data['mandats_electifs']['items'] as $mandat) {
                    $mandatModel = $this->importMandatElectif($declaration, $mandat);
                    if ($mandatModel) {
                        $stats['mandats']++;

                        // Importer les rémunérations du mandat
                        if (! empty($mandat['remunerations']['montants'])) {
                            foreach ($mandat['remunerations']['montants'] as $rem) {
                                $this->importRemunerationMandat($mandatModel, $rem, $mandat['remunerations']['brut_net'] ?? null);
                                $stats['revenus_total'] += $rem['montant'] ?? 0;
                            }
                        }
                    }
                }
            }

            // 2. Importer les collaborateurs
            if (! empty($data['collaborateurs']['items'])) {
                foreach ($data['collaborateurs']['items'] as $collab) {
                    if ($this->importCollaborateur($declaration, $collab)) {
                        $stats['collaborateurs']++;
                    }
                }
            }

            // 3. Importer les activités professionnelles avec rémunérations
            if (! empty($data['activites_professionnelles']['items'])) {
                foreach ($data['activites_professionnelles']['items'] as $activite) {
                    $activiteModel = $this->importActiviteProfessionnelle($declaration, $activite);
                    if ($activiteModel) {
                        $stats['activites_pro']++;

                        // Importer les rémunérations
                        if (! empty($activite['remunerations']['montants'])) {
                            foreach ($activite['remunerations']['montants'] as $rem) {
                                HatvpRemunerationActivitePro::updateOrCreate(
                                    [
                                        'activite_id' => $activiteModel->id,
                                        'annee' => $rem['annee'],
                                    ],
                                    [
                                        'montant' => $rem['montant'],
                                        'brut_net' => $activite['remunerations']['brut_net'] ?? null,
                                    ]
                                );
                                $stats['revenus_total'] += $rem['montant'] ?? 0;
                            }
                        }
                    }
                }
            }

            // 4. Importer les activités de consultant avec rémunérations
            if (! empty($data['activites_consultant']['items'])) {
                foreach ($data['activites_consultant']['items'] as $consultant) {
                    $consultantModel = $this->importActiviteConsultant($declaration, $consultant);
                    if ($consultantModel) {
                        // Importer les rémunérations
                        if (! empty($consultant['remunerations']['montants'])) {
                            foreach ($consultant['remunerations']['montants'] as $rem) {
                                HatvpRemunerationConsultant::updateOrCreate(
                                    [
                                        'activite_id' => $consultantModel->id,
                                        'annee' => $rem['annee'],
                                    ],
                                    [
                                        'montant' => $rem['montant'],
                                        'brut_net' => $consultant['remunerations']['brut_net'] ?? null,
                                    ]
                                );
                                $stats['revenus_total'] += $rem['montant'] ?? 0;
                            }
                        }
                    }
                }
            }

            // 5. Importer les participations dirigeantes avec rémunérations
            if (! empty($data['participations_dirigeantes']['items'])) {
                foreach ($data['participations_dirigeantes']['items'] as $dirigeant) {
                    $dirigeantModel = $this->importParticipationDirigeante($declaration, $dirigeant);
                    if ($dirigeantModel) {
                        $stats['dirigeant']++;

                        // Importer les rémunérations
                        if (! empty($dirigeant['remunerations']['montants'])) {
                            foreach ($dirigeant['remunerations']['montants'] as $rem) {
                                HatvpRemunerationDirigeant::updateOrCreate(
                                    [
                                        'participation_id' => $dirigeantModel->id,
                                        'annee' => $rem['annee'],
                                    ],
                                    [
                                        'montant' => $rem['montant'],
                                        'brut_net' => $dirigeant['remunerations']['brut_net'] ?? null,
                                    ]
                                );
                                $stats['revenus_total'] += $rem['montant'] ?? 0;
                            }
                        }
                    }
                }
            }

            // 6. Importer les fonctions bénévoles
            if (! empty($data['fonctions_benevoles']['items'])) {
                foreach ($data['fonctions_benevoles']['items'] as $benevole) {
                    $this->importFonctionBenevole($declaration, $benevole);
                }
            }

            // 7-9. Patrimoine (DSP) : participations financières, immeubles, véhicules.
            // SAUTÉ en mode DIA-only (garde-fou légal : le patrimoine n'est ni repris ni publié).
            if (! $diaOnly) {
                if (! empty($data['participations_financieres']['items'])) {
                    foreach ($data['participations_financieres']['items'] as $financiere) {
                        $this->importParticipationFinanciere($declaration, $financiere);
                    }
                }
                if (! empty($data['immeubles']['items'])) {
                    foreach ($data['immeubles']['items'] as $immeuble) {
                        $this->importImmeuble($declaration, $immeuble);
                    }
                }
                if (! empty($data['vehicules']['items'])) {
                    foreach ($data['vehicules']['items'] as $vehicule) {
                        $this->importVehicule($declaration, $vehicule);
                    }
                }
            }

            // 10. Importer les revenus annuels
            if (! empty($data['revenus']['items'])) {
                foreach ($data['revenus']['items'] as $revenu) {
                    $this->importRevenu($declaration, $revenu);
                }
            }

            // Marquer la déclaration comme enrichie
            $declaration->update([
                'details_imported_at' => now(),
            ]);
        });

        return $stats;
    }

    /**
     * Importe un mandat électif
     */
    private function importMandatElectif(HatvpDeclaration $declaration, array $data): ?HatvpMandatElectif
    {
        return HatvpMandatElectif::updateOrCreate(
            [
                'declaration_id' => $declaration->id,
                'description' => $data['description'] ?? null,
                'date_debut' => $data['date_debut'] ?? null,
            ],
            [
                'date_fin' => $data['date_fin'] ?? null,
                'conservee' => $data['conservee'] ?? false,
                'commentaire' => $data['commentaire'] ?? null,
            ]
        );
    }

    /**
     * Importe une rémunération de mandat
     */
    private function importRemunerationMandat(HatvpMandatElectif $mandat, array $rem, ?string $brutNet): void
    {
        DB::table('hatvp_remunerations')->updateOrInsert(
            [
                'remuneratable_type' => HatvpMandatElectif::class,
                'remuneratable_id' => $mandat->id,
                'annee' => $rem['annee'],
            ],
            [
                'montant' => $rem['montant'],
                'brut_net' => $brutNet,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Importe un collaborateur
     */
    private function importCollaborateur(HatvpDeclaration $declaration, array $data): ?HatvpCollaborateur
    {
        if (empty($data['nom'])) {
            return null;
        }

        return HatvpCollaborateur::updateOrCreate(
            [
                'declaration_id' => $declaration->id,
                'nom' => $data['nom'],
            ],
            [
                'employeur' => $data['employeur'] ?? null,
                'description' => $data['description_activite'] ?? null,
            ]
        );
    }

    /**
     * Importe une activité professionnelle
     */
    private function importActiviteProfessionnelle(HatvpDeclaration $declaration, array $data): ?HatvpActiviteProfessionnelle
    {
        return HatvpActiviteProfessionnelle::updateOrCreate(
            [
                'declaration_id' => $declaration->id,
                'employeur' => $data['employeur'] ?? $data['nom_societe'] ?? 'Non précisé',
                'date_debut' => $data['date_debut'] ?? null,
            ],
            [
                'description' => $data['description'] ?? $data['activite'] ?? null,
                'date_fin' => $data['date_fin'] ?? null,
                'conservee' => $data['conservee'] ?? false,
            ]
        );
    }

    /**
     * Importe une activité de consultant
     */
    private function importActiviteConsultant(HatvpDeclaration $declaration, array $data): ?HatvpActiviteConsultant
    {
        return HatvpActiviteConsultant::updateOrCreate(
            [
                'declaration_id' => $declaration->id,
                'nom_employeur' => $data['employeur'] ?? $data['nom_societe'] ?? 'Non précisé',
                'date_debut' => $data['date_debut'] ?? null,
            ],
            [
                'description' => $data['description'] ?? $data['activite'] ?? null,
                'date_fin' => $data['date_fin'] ?? null,
                'conservee' => $data['conservee'] ?? false,
            ]
        );
    }

    /**
     * Importe une participation dirigeante
     */
    private function importParticipationDirigeante(HatvpDeclaration $declaration, array $data): ?HatvpParticipationDirigeante
    {
        return HatvpParticipationDirigeante::updateOrCreate(
            [
                'declaration_id' => $declaration->id,
                'nom_societe' => $data['nom_societe'] ?? 'Non précisé',
                'date_debut' => $data['date_debut'] ?? null,
            ],
            [
                'description' => $data['description'] ?? $data['activite'] ?? null,
                'date_fin' => $data['date_fin'] ?? null,
                'conservee' => $data['conservee'] ?? false,
            ]
        );
    }

    /**
     * Importe une fonction bénévole
     */
    private function importFonctionBenevole(HatvpDeclaration $declaration, array $data): void
    {
        HatvpFonctionBenevole::updateOrCreate(
            [
                'declaration_id' => $declaration->id,
                'nom_structure' => $data['nom_structure'] ?? 'Non précisé',
            ],
            [
                'description' => $data['description_activite'] ?? null,
            ]
        );
    }

    /**
     * Importe une participation financière
     */
    private function importParticipationFinanciere(HatvpDeclaration $declaration, array $data): void
    {
        HatvpParticipationFinanciere::updateOrCreate(
            [
                'declaration_id' => $declaration->id,
                'nom_societe' => $data['nom_societe'] ?? 'Non précisé',
            ],
            [
                'evaluation' => $data['evaluation'] ?? null,
                'capital_detenu' => $data['capital_detenu'] ?? null,
                'nombre_parts' => $data['nombre_parts'] ?? null,
                'commentaire' => $data['commentaire'] ?? null,
            ]
        );
    }

    /**
     * Importe un immeuble
     */
    private function importImmeuble(HatvpDeclaration $declaration, array $data): void
    {
        HatvpImmeuble::updateOrCreate(
            [
                'declaration_id' => $declaration->id,
                'nature' => $data['nature'] ?? 'Non précisé',
                'code_postal' => $data['code_postal'] ?? null,
            ],
            [
                'adresse' => $data['adresse'] ?? null,
                'localite' => $data['localite'] ?? null,
                'superficie_bati' => $data['superficie_bati'] ?? null,
                'superficie_non_bati' => $data['superficie_non_bati'] ?? null,
                'date_acquisition' => $data['date_acquisition'] ?? null,
                'prix_acquisition' => $data['prix_acquisition'] ?? null,
                'valeur_venale' => $data['valeur_venale'] ?? null,
                'droit_reel' => $data['droit_reel'] ?? null,
            ]
        );
    }

    /**
     * Importe un véhicule
     */
    private function importVehicule(HatvpDeclaration $declaration, array $data): void
    {
        HatvpVehicule::updateOrCreate(
            [
                'declaration_id' => $declaration->id,
                'nature' => $data['nature'] ?? 'Non précisé',
                'marque' => $data['marque'] ?? null,
            ],
            [
                'annee_achat' => $data['annee_achat'] ?? null,
                'valeur_achat' => $data['valeur_achat'] ?? null,
                'valeur' => $data['valeur'] ?? null,
            ]
        );
    }

    /**
     * Importe un revenu annuel
     */
    private function importRevenu(HatvpDeclaration $declaration, array $data): void
    {
        HatvpRevenu::updateOrCreate(
            [
                'declaration_id' => $declaration->id,
                'annee' => $data['annee'] ?? null,
            ],
            [
                'total_elu' => $data['total_elu'] ?? null,
                'total_conjoint' => $data['total_conjoint'] ?? null,
                'commentaire' => $data['commentaire'] ?? null,
                'details' => json_encode($data['revenus'] ?? []),
            ]
        );
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
        } elseif (in_array($type, ['maire', 'conseiller municipal', '']) || str_contains($type, 'maire') || str_contains($type, 'municipal')) {
            $typeMandat = strtolower($declaration->type_mandat ?? '');
            if (str_contains($typeMandat, 'maire') || str_contains($typeMandat, 'municipal')) {
                $maire = \App\Models\Maire::where('nom', 'ILIKE', $declaration->nom)
                    ->where('prenom', 'ILIKE', $declaration->prenom)
                    ->enExercice()
                    ->first();

                if ($maire) {
                    $declaration->update([
                        'parlementaire_type' => 'maire',
                        'parlementaire_id' => $maire->uid,
                    ]);
                }
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

        if (! $xmlFile) {
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
            $this->info('   Parlementaires : '.count($declarations));
        }

        // Filtrer par type si demandé
        $type = $this->option('type');
        if ($type) {
            $declarations = array_filter($declarations, function ($d) use ($type) {
                return strtolower($d['code_type_mandat'] ?? '') === strtolower($type);
            });
            $this->info("   Type '{$type}' : ".count($declarations));
        }

        // Afficher un résumé
        $this->newLine();
        $this->info('📊 Résumé :');

        $byType = [];
        foreach ($declarations as $d) {
            $key = ($d['code_type_mandat'] ?? 'unknown').'_'.($d['type_declaration'] ?? 'unknown');
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
        if (! $value) {
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
