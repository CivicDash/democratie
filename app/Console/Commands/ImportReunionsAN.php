<?php

namespace App\Console\Commands;

use App\Models\ReunionAN;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class ImportReunionsAN extends Command
{
    protected $signature = 'import:reunions-an 
                            {--legislature=17 : Législature à importer}
                            {--fresh : Vide la table avant l\'import}
                            {--limit= : Limite le nombre de réunions}
                            {--download : Force le re-téléchargement}';

    protected $description = 'Importe les réunions AN depuis data.assemblee-nationale.fr';

    private string $baseUrl = 'https://data.assemblee-nationale.fr/static/openData/repository';

    private int $imported = 0;

    private int $updated = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $legislature = $this->option('legislature');

        $this->info("📅 Import des réunions AN - Législature {$legislature}");

        // Chemin du fichier ZIP
        $zipUrl = "{$this->baseUrl}/{$legislature}/vp/reunions/Agenda.json.zip";
        $storagePath = storage_path("app/an-data/reunions-{$legislature}");
        $zipPath = "{$storagePath}/Agenda.json.zip";

        // Créer le dossier si nécessaire
        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // Télécharger si nécessaire
        if ($this->option('download') || ! file_exists($zipPath)) {
            $this->info("⬇️  Téléchargement depuis {$zipUrl}...");

            try {
                $response = Http::timeout(300)->get($zipUrl);

                if ($response->successful()) {
                    file_put_contents($zipPath, $response->body());
                    $this->info('✅ Fichier téléchargé ('.round(filesize($zipPath) / 1024 / 1024, 2).' Mo)');
                } else {
                    $this->error('❌ Erreur HTTP: '.$response->status());

                    return self::FAILURE;
                }
            } catch (\Exception $e) {
                $this->error('❌ Erreur de téléchargement: '.$e->getMessage());

                return self::FAILURE;
            }
        } else {
            $this->info("📁 Utilisation du fichier existant: {$zipPath}");
        }

        // Extraction du ZIP
        $this->info('📦 Extraction...');
        $extractPath = "{$storagePath}/extracted";

        if (is_dir($extractPath)) {
            // Nettoyer l'ancien dossier
            $this->deleteDirectory($extractPath);
        }
        mkdir($extractPath, 0755, true);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($extractPath);
            $zip->close();
            $this->info('✅ Extraction terminée');
        } else {
            $this->error("❌ Impossible d'ouvrir le ZIP");

            return self::FAILURE;
        }

        // Trouver les fichiers JSON
        $jsonFiles = $this->findJsonFiles($extractPath);
        $this->info('📊 '.count($jsonFiles).' fichiers de réunions trouvés');

        if (count($jsonFiles) === 0) {
            $this->warn('⚠️  Aucune réunion trouvée');

            return self::SUCCESS;
        }

        // Appliquer la limite si demandée
        $limit = $this->option('limit');
        if ($limit) {
            $jsonFiles = array_slice($jsonFiles, 0, (int) $limit);
            $this->warn("⚠️  Mode limité: {$limit} réunions");
        }

        // Vider la table si --fresh
        if ($this->option('fresh')) {
            $this->warn('⚠️  Suppression des réunions existantes...');
            ReunionAN::where('legislature', $legislature)->delete();
        }

        // Import
        $bar = $this->output->createProgressBar(count($jsonFiles));
        $bar->start();

        foreach ($jsonFiles as $file) {
            try {
                $this->importReunion($file, $legislature);
            } catch (\Exception $e) {
                $this->errors++;
                if ($this->errors <= 5) {
                    $bar->clear();
                    $this->newLine();
                    $this->error('❌ '.basename($file).': '.$e->getMessage());
                    $bar->display();
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Résumé
        $this->info('✅ Import terminé !');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✓ Nouvelles réunions', $this->imported],
                ['↻ Réunions mises à jour', $this->updated],
                ['⚠ Erreurs', $this->errors],
            ]
        );

        // Stats
        $total = ReunionAN::where('legislature', $legislature)->count();
        $aVenir = ReunionAN::where('legislature', $legislature)->aVenir()->count();
        $this->info("📊 Total: {$total} réunions | À venir: {$aVenir}");

        return self::SUCCESS;
    }

    private function findJsonFiles(string $path): array
    {
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'json') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function importReunion(string $filePath, int $legislature): void
    {
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if (! isset($data['reunion'])) {
            throw new \Exception('Structure JSON invalide');
        }

        $reunion = $data['reunion'];
        $uid = $reunion['uid'] ?? null;

        if (! $uid) {
            throw new \Exception('UID manquant');
        }

        // Extraire la session depuis l'UID (ex: RUANR5L16S2024IDC452600 -> S2024)
        preg_match('/S(\d{4})/', $uid, $sessionMatch);
        $session = isset($sessionMatch[0]) ? $sessionMatch[0] : null;

        // Déterminer le type de réunion depuis le type XML
        $typeReunion = null;
        if (isset($reunion['@xsi:type'])) {
            $typeReunion = match ($reunion['@xsi:type']) {
                'reunionCommission_type' => 'Commission',
                'reunionSeance_type' => 'Séance publique',
                'reunionDelegation_type' => 'Délégation',
                'reunionMission_type' => 'Mission',
                'reunionGroupe_type' => 'Groupe',
                default => $reunion['@xsi:type'],
            };
        }

        // Parsing des dates
        $dateDebut = $this->parseDateTime($reunion['timeStampDebut'] ?? null);
        $dateFin = $this->parseDateTime($reunion['timeStampFin'] ?? null);

        // Cycle de vie
        $cycleDeVie = $reunion['cycleDeVie'] ?? [];
        $etat = $cycleDeVie['etat'] ?? null;
        $dateCreation = $this->parseDate($cycleDeVie['chrono']['creation'] ?? null);
        $dateCloture = $this->parseDate($cycleDeVie['chrono']['cloture'] ?? null);

        // Lieu
        $lieu = $reunion['lieu'] ?? [];
        $lieuRef = $this->extractValue($lieu['lieuRef'] ?? null);
        $lieuLibelle = $lieu['libelleLong'] ?? $lieu['libelleCourt'] ?? null;

        // ODJ
        $odj = $reunion['ODJ'] ?? [];
        $odjConvocation = $this->extractOdjItems($odj['convocationODJ'] ?? null);
        $odjResume = $this->extractOdjItems($odj['resumeODJ'] ?? null);
        $pointsOdj = $this->extractPointsOdj($odj['pointsODJ'] ?? null);

        // Participants
        $participants = $reunion['participants'] ?? [];
        $participantsInternes = $this->extractParticipants($participants['participantsInternes'] ?? null);
        $personnesAuditionnees = $this->extractAuditionnes($participants['personnesAuditionnees'] ?? null);

        // Métadonnées
        $formatReunion = $reunion['formatReunion'] ?? null;
        $visioConference = $this->parseBoolean($reunion['visioConference'] ?? false);
        $ouverturePresse = $this->parseBoolean($reunion['ouverturePresse'] ?? false);
        $captationVideo = $this->parseBoolean($reunion['captationVideo'] ?? false);

        // Réunion internationale
        $infosInternationale = $reunion['infosReunionsInternationale'] ?? [];
        $reunionInternationale = $this->parseBoolean($infosInternationale['estReunionInternationale'] ?? false);
        $paysInternationale = $this->extractListePays($infosInternationale['listePays'] ?? null);

        // Insert ou update
        $model = ReunionAN::updateOrCreate(
            ['uid' => $uid],
            [
                'legislature' => $legislature,
                'session' => $session,
                'type_reunion' => $typeReunion,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'lieu_ref' => $lieuRef,
                'lieu_libelle' => $lieuLibelle,
                'etat' => $etat,
                'date_creation' => $dateCreation,
                'date_cloture' => $dateCloture,
                'organe_ref' => $this->extractValue($reunion['organeReuniRef'] ?? null),
                'compte_rendu_ref' => $this->extractValue($reunion['compteRenduRef'] ?? null),
                'session_ref' => $this->extractValue($reunion['sessionRef'] ?? null),
                'demandeur' => $this->extractValue($reunion['demandeur'] ?? null),
                'odj_convocation' => $odjConvocation,
                'odj_resume' => $odjResume,
                'points_odj' => $pointsOdj,
                'participants_internes' => $participantsInternes,
                'personnes_auditionnees' => $personnesAuditionnees,
                'format_reunion' => $formatReunion,
                'visio_conference' => $visioConference,
                'ouverture_presse' => $ouverturePresse,
                'captation_video' => $captationVideo,
                'reunion_internationale' => $reunionInternationale,
                'pays_reunion_internationale' => $paysInternationale,
            ]
        );

        if ($model->wasRecentlyCreated) {
            $this->imported++;
        } else {
            $this->updated++;
        }
    }

    private function parseDateTime(?string $value): ?\DateTime
    {
        if (! $value) {
            return null;
        }
        try {
            return new \DateTime($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseDate(?string $value): ?\DateTime
    {
        if (! $value) {
            return null;
        }
        try {
            return new \DateTime($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return strtolower($value) === 'true';
        }

        return (bool) $value;
    }

    private function extractValue($value)
    {
        // Gérer les valeurs @xsi:nil
        if (is_array($value) && isset($value['@xsi:nil'])) {
            return null;
        }
        if (is_string($value)) {
            return $value;
        }

        return null;
    }

    private function extractOdjItems($odj): ?array
    {
        if (! $odj || ! is_array($odj)) {
            return null;
        }
        if (isset($odj['@xsi:nil'])) {
            return null;
        }

        $items = $odj['item'] ?? null;
        if (! $items) {
            return null;
        }

        // Si c'est un seul item (string), le mettre dans un array
        if (is_string($items)) {
            return [$items];
        }

        return is_array($items) ? array_values($items) : null;
    }

    private function extractPointsOdj($points): ?array
    {
        if (! $points || ! is_array($points)) {
            return null;
        }
        if (isset($points['@xsi:nil'])) {
            return null;
        }

        // Structure plus complexe avec pointODJ
        $pointsList = $points['pointODJ'] ?? $points;
        if (! is_array($pointsList)) {
            return null;
        }

        // Normaliser en array
        if (isset($pointsList['uid'])) {
            $pointsList = [$pointsList];
        }

        return array_map(function ($p) {
            return [
                'uid' => $p['uid'] ?? null,
                'titre' => $p['titre'] ?? null,
                'type' => $p['type'] ?? null,
            ];
        }, $pointsList);
    }

    private function extractParticipants($participants): ?array
    {
        if (! $participants || ! is_array($participants)) {
            return null;
        }
        if (isset($participants['@xsi:nil'])) {
            return null;
        }

        $list = $participants['participant'] ?? $participants;
        if (! is_array($list)) {
            return null;
        }

        // Normaliser
        if (isset($list['acteurRef'])) {
            $list = [$list];
        }

        return array_map(function ($p) {
            return [
                'acteur_ref' => $p['acteurRef'] ?? null,
                'fonction' => $p['fonction'] ?? null,
            ];
        }, $list);
    }

    private function extractAuditionnes($auditionnes): ?array
    {
        if (! $auditionnes || ! is_array($auditionnes)) {
            return null;
        }
        if (isset($auditionnes['@xsi:nil'])) {
            return null;
        }

        $list = $auditionnes['personneAuditionnee'] ?? $auditionnes;
        if (! is_array($list)) {
            return null;
        }

        // Normaliser
        if (isset($list['identite']) || isset($list['qualite'])) {
            $list = [$list];
        }

        return array_map(function ($p) {
            return [
                'identite' => $p['identite'] ?? null,
                'qualite' => $p['qualite'] ?? null,
                'organisme' => $p['organisme'] ?? null,
            ];
        }, $list);
    }

    private function extractListePays($pays): ?array
    {
        if (! $pays || ! is_array($pays)) {
            return null;
        }
        if (isset($pays['@xsi:nil'])) {
            return null;
        }

        $list = $pays['pays'] ?? $pays;
        if (is_string($list)) {
            return [$list];
        }

        return is_array($list) ? array_values($list) : null;
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
