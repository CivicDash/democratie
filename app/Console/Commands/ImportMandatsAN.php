<?php

namespace App\Console\Commands;

use App\Models\MandatAN;
use App\Models\ActeurAN;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportMandatsAN extends Command
{
    protected $signature = 'import:mandats-an 
                            {--legislature=17 : Législature à importer (par défaut: 17)}
                            {--all : Importer tous les mandats (toutes législatures)}
                            {--limit= : Limite le nombre d\'acteurs traités (pour tests)}
                            {--fresh : Vide la table avant l\'import}';

    protected $description = 'Importe les mandats depuis les fichiers acteurs JSON (mandats imbriqués)';

    private int $imported = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private int $errors = 0;

    public function handle(): int
    {
        $legislature = $this->option('legislature');
        $importAll = $this->option('all');
        
        $this->info('🏛️  Import des mandats AN...');
        
        if ($importAll) {
            $this->warn('⚠️  Mode --all : import de TOUS les mandats (toutes législatures)');
        } else {
            $this->info("📊 Législature cible : {$legislature}");
        }

        if ($this->option('fresh')) {
            $this->warn('⚠️  Mode --fresh : suppression des mandats existants...');
            MandatAN::truncate();
        }

        // On récupère les acteurs depuis la BDD (déjà importés)
        $acteurs = ActeurAN::all();
        
        $limit = $this->option('limit');
        if ($limit) {
            $acteurs = $acteurs->take((int)$limit);
            $this->warn("⚠️  Mode TEST : {$limit} acteurs maximum");
        }

        if ($acteurs->isEmpty()) {
            $this->error('❌ Aucun acteur trouvé en BDD. Lancer d\'abord : import:acteurs-an');
            return self::FAILURE;
        }

        $this->info("📊 {$acteurs->count()} acteurs à traiter");
        $bar = $this->output->createProgressBar($acteurs->count());
        $bar->start();

        foreach ($acteurs as $acteur) {
            try {
                $this->importMandatsActeur($acteur->uid, $legislature, $importAll);
            } catch (\Exception $e) {
                $this->errors++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->displaySummary($legislature, $importAll);

        return self::SUCCESS;
    }

    private function importMandatsActeur(string $acteurUid, int $legislature, bool $importAll): void
    {
        $filePath = public_path("data/acteur/{$acteurUid}.json");
        
        if (!file_exists($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if (!isset($data['acteur']['mandats']['mandat'])) {
            return;
        }

        $mandats = $data['acteur']['mandats']['mandat'];
        
        // Si un seul mandat, le transformer en tableau
        if (isset($mandats['uid'])) {
            $mandats = [$mandats];
        }

        foreach ($mandats as $mandatData) {
            $this->importMandat($mandatData, $acteurUid, $legislature, $importAll);
        }
    }

    private function importMandat(array $mandatData, string $acteurUid, int $legislature, bool $importAll): void
    {
        $uid = $mandatData['uid'] ?? null;
        
        if (!$uid) {
            return;
        }

        // Filtrage par législature
        $mandatLegislature = $mandatData['legislature'] ?? null;
        
        if (!$importAll && $mandatLegislature) {
            if ((int)$mandatLegislature !== (int)$legislature) {
                $this->skipped++;
                return;
            }
        }

        // Extraction organe
        $organeRef = null;
        if (isset($mandatData['organes']['organeRef'])) {
            $organeRef = $mandatData['organes']['organeRef'];
            // Si tableau d'organes, prendre le premier
            if (is_array($organeRef)) {
                $organeRef = $organeRef[0] ?? null;
            }
        }

        $infosQualite = $mandatData['infosQualite'] ?? [];

        // Insert ou update
        $mandatModel = MandatAN::updateOrCreate(
            ['uid' => $uid],
            [
                'acteur_ref' => $acteurUid,
                'organe_ref' => $organeRef,
                'legislature' => $mandatLegislature,
                'type_organe' => $mandatData['typeOrgane'] ?? null,
                'date_debut' => $mandatData['dateDebut'] ?? null,
                'date_fin' => $mandatData['dateFin'] ?? null,
                'code_qualite' => $infosQualite['codeQualite'] ?? null,
                'libelle_qualite' => $infosQualite['libQualite'] ?? null,
                'preseance' => $mandatData['preseance'] ?? null,
                'nomination_principale' => (bool)($mandatData['nominPrincipale'] ?? false),
            ]
        );

        if ($mandatModel->wasRecentlyCreated) {
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
                ['✓ Nouveaux mandats', $this->imported],
                ['↻ Mandats mis à jour', $this->updated],
                ['⊘ Mandats skippés (législature)', $this->skipped],
                ['⚠ Erreurs', $this->errors],
            ]
        );

        // Stats finales
        $total = MandatAN::count();
        
        $statsTypes = DB::table('mandats_an')
            ->select('type_organe', DB::raw('COUNT(*) as total'))
            ->groupBy('type_organe')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        
        $this->newLine();
        $this->info("📊 Total en base de données : {$total} mandats");
        
        if (!$importAll) {
            $totalLeg = MandatAN::legislature($legislature)->count();
            $this->info("📊 Législature {$legislature} : {$totalLeg} mandats");
        }
        
        $this->newLine();
        $this->info('📊 Top 5 types de mandats :');
        foreach ($statsTypes as $stat) {
            $this->line("   - {$stat->type_organe} : {$stat->total}");
        }
    }
}

