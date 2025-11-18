<?php

namespace App\Console\Commands;

use App\Models\OrganeAN;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportOrganesAN extends Command
{
    protected $signature = 'import:organes-an 
                            {--legislature=17 : Législature à importer (par défaut: 17)}
                            {--all : Importer tous les organes (toutes législatures)}
                            {--limit= : Limite le nombre d\'organes à importer (pour tests)}
                            {--fresh : Vide la table avant l\'import}';

    protected $description = 'Importe les organes (groupes politiques, commissions, délégations) depuis les fichiers JSON AN';

    private int $imported = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private int $errors = 0;

    public function handle(): int
    {
        $legislature = $this->option('legislature');
        $importAll = $this->option('all');
        
        $this->info('🏛️  Import des organes AN...');
        
        if ($importAll) {
            $this->warn('⚠️  Mode --all : import de TOUS les organes (toutes législatures)');
        } else {
            $this->info("📊 Législature cible : {$legislature}");
        }

        $basePath = public_path('data/organe');
        
        if (!is_dir($basePath)) {
            $this->error("❌ Répertoire introuvable : {$basePath}");
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->warn('⚠️  Mode --fresh : suppression des organes existants...');
            OrganeAN::truncate();
        }

        $files = File::glob($basePath . '/*.json');
        $total = count($files);
        
        $limit = $this->option('limit');
        if ($limit) {
            $files = array_slice($files, 0, (int)$limit);
            $this->warn("⚠️  Mode TEST : {$limit} organes maximum");
        }

        $this->info("📊 {$total} fichiers trouvés");
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $file) {
            try {
                $this->importOrgane($file, $legislature, $importAll);
            } catch (\Exception $e) {
                $this->errors++;
                $this->newLine();
                $this->warn("⚠️  Erreur : {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->displaySummary($legislature, $importAll);

        return self::SUCCESS;
    }

    private function importOrgane(string $filePath, int $legislature, bool $importAll): void
    {
        $content = File::get($filePath);
        $data = json_decode($content, true);

        if (!isset($data['organe'])) {
            throw new \Exception("Structure JSON invalide dans {$filePath}");
        }

        $organe = $data['organe'];
        $uid = $organe['uid']['#text'] ?? $organe['uid'] ?? null;

        if (!$uid) {
            throw new \Exception("UID manquant dans {$filePath}");
        }

        // Filtrage par législature
        $orgLegislature = $organe['legislature'] ?? null;
        
        if (!$importAll) {
            // Si législature spécifiée ET que l'organe a une législature différente, on skip
            if ($orgLegislature && (int)$orgLegislature !== (int)$legislature) {
                $this->skipped++;
                return;
            }
            
            // Si organe sans législature, on regarde s'il est actif (date_fin null)
            if (!$orgLegislature) {
                $dateFin = $organe['viMoDe']['dateFin'] ?? null;
                if ($dateFin) {
                    $this->skipped++;
                    return;
                }
            }
        }

        // Extraction des données
        $viMoDe = $organe['viMoDe'] ?? [];
        
        // Insert ou update
        $organeModel = OrganeAN::updateOrCreate(
            ['uid' => $uid],
            [
                'code_type' => $organe['codeType'] ?? null,
                'libelle' => $organe['libelle'] ?? null,
                'libelle_abrege' => $organe['libelleAbrev'] ?? $organe['libelleAbrege'] ?? null,
                'legislature' => $orgLegislature,
                'date_debut' => $viMoDe['dateDebut'] ?? null,
                'date_fin' => $viMoDe['dateFin'] ?? null,
                'regime' => $organe['regime'] ?? null,
                'site_internet' => $organe['siteInternet'] ?? null,
            ]
        );

        if ($organeModel->wasRecentlyCreated) {
            $this->imported++;
        } else {
            $this->updated++;
        }
    }

    private function displaySummary(int $legislature, bool $importAll): void
    {
        $this->info('✅ Import terminé !');
        $this->newLine();
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✓ Nouveaux organes', $this->imported],
                ['↻ Organes mis à jour', $this->updated],
                ['⊘ Organes skippés (législature)', $this->skipped],
                ['⚠ Erreurs', $this->errors],
            ]
        );

        // Stats finales
        $total = OrganeAN::count();
        $groupes = OrganeAN::groupesPolitiques()->count();
        $commissions = OrganeAN::commissionsPermanentes()->count();
        $delegations = OrganeAN::delegations()->count();
        
        if (!$importAll) {
            $totalLeg = OrganeAN::legislature($legislature)->count();
            $groupesLeg = OrganeAN::groupesPolitiques()->legislature($legislature)->count();
            $commissionsLeg = OrganeAN::commissionsPermanentes()->legislature($legislature)->count();
        }
        
        $this->newLine();
        $this->info("📊 Total en base de données : {$total} organes");
        $this->info("   - Groupes politiques : {$groupes}");
        $this->info("   - Commissions permanentes : {$commissions}");
        $this->info("   - Délégations : {$delegations}");
        
        if (!$importAll) {
            $this->newLine();
            $this->info("📊 Législature {$legislature} : {$totalLeg} organes");
            $this->info("   - Groupes : {$groupesLeg}");
            $this->info("   - Commissions : {$commissionsLeg}");
        }
    }
}

