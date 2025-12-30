<?php

namespace App\Console\Commands;

use App\Models\ActeurAN;
use App\Models\DeputeCirconscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportCirconscriptionsAN extends Command
{
    protected $signature = 'import:circonscriptions-an 
                            {--legislature=17 : Législature à importer}
                            {--fresh : Vide la table avant import}
                            {--limit= : Limiter à N acteurs (tests)}';

    protected $description = 'Importe les liaisons député-circonscription depuis les fichiers acteurs JSON';

    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private int $errors = 0;

    public function handle(): int
    {
        $legislature = (int) $this->option('legislature');
        
        $this->info("🗺️  Import des circonscriptions députés (L{$legislature})...");
        
        if ($this->option('fresh')) {
            $this->warn('⚠️  Mode --fresh : suppression des données existantes...');
            DeputeCirconscription::where('legislature', $legislature)->delete();
        }

        // Récupérer les acteurs depuis la BDD
        $acteurs = ActeurAN::all();
        
        if ($limit = $this->option('limit')) {
            $acteurs = $acteurs->take((int) $limit);
            $this->warn("⚠️  Mode TEST : {$limit} acteurs maximum");
        }

        if ($acteurs->isEmpty()) {
            $this->error('❌ Aucun acteur en BDD. Lancer d\'abord : import:acteurs-an');
            return self::FAILURE;
        }

        $this->info("📊 {$acteurs->count()} acteurs à traiter");
        $bar = $this->output->createProgressBar($acteurs->count());
        $bar->start();

        foreach ($acteurs as $acteur) {
            try {
                $this->processActeur($acteur->uid, $legislature);
            } catch (\Exception $e) {
                $this->errors++;
                if ($this->output->isVerbose()) {
                    $this->error("Erreur {$acteur->uid}: {$e->getMessage()}");
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->displaySummary($legislature);

        return self::SUCCESS;
    }

    private function processActeur(string $uid, int $legislature): void
    {
        $filePath = public_path("data/acteur/{$uid}.json");
        
        if (!file_exists($filePath)) {
            $this->skipped++;
            return;
        }

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if (!isset($data['acteur']['mandats']['mandat'])) {
            $this->skipped++;
            return;
        }

        $mandats = $data['acteur']['mandats']['mandat'];
        
        // Normaliser en tableau si un seul mandat
        if (isset($mandats['uid'])) {
            $mandats = [$mandats];
        }

        // Chercher le mandat ASSEMBLEE de la législature cible
        foreach ($mandats as $mandat) {
            if (($mandat['typeOrgane'] ?? '') !== 'ASSEMBLEE') {
                continue;
            }
            
            $mandatLegislature = (int) ($mandat['legislature'] ?? 0);
            if ($mandatLegislature !== $legislature) {
                continue;
            }

            $this->importCirconscription($uid, $mandat);
        }
    }

    private function importCirconscription(string $acteurUid, array $mandat): void
    {
        $mandatUid = $mandat['uid'] ?? null;
        $election = $mandat['election'] ?? null;
        $mandature = $mandat['mandature'] ?? [];
        
        if (!$mandatUid || !$election) {
            $this->skipped++;
            return;
        }

        $lieu = $election['lieu'] ?? [];
        
        // Extraire les données
        $departement = $lieu['departement'] ?? null;
        $numDepartement = $lieu['numDepartement'] ?? null;
        $numCirco = isset($lieu['numCirco']) ? (int) $lieu['numCirco'] : null;
        
        if (!$departement || !$numDepartement || !$numCirco) {
            $this->skipped++;
            return;
        }

        // Suppléant
        $suppleantRef = null;
        if (isset($mandat['suppleants']['suppleant']['suppleantRef'])) {
            $suppleantRef = $mandat['suppleants']['suppleant']['suppleantRef'];
        } elseif (isset($mandat['suppleants']['suppleant'][0]['suppleantRef'])) {
            $suppleantRef = $mandat['suppleants']['suppleant'][0]['suppleantRef'];
        }

        // Insert ou update
        $model = DeputeCirconscription::updateOrCreate(
            ['mandat_uid' => $mandatUid],
            [
                'acteur_uid' => $acteurUid,
                'legislature' => (int) $mandat['legislature'],
                'circonscription_ref' => $election['refCirconscription'] ?? null,
                'departement' => $departement,
                'num_departement' => $numDepartement,
                'num_circo' => $numCirco,
                'region' => $lieu['region'] ?? null,
                'region_type' => $lieu['regionType'] ?? null,
                'cause_mandat' => $election['causeMandat'] ?? null,
                'date_debut' => $mandat['dateDebut'] ?? null,
                'date_fin' => $mandat['dateFin'] ?? null,
                'date_prise_fonction' => $mandature['datePriseFonction'] ?? null,
                'cause_fin' => $mandature['causeFin'] ?? null,
                'premiere_election' => (bool) ($mandature['premiereElection'] ?? false),
                'place_hemicycle' => isset($mandature['placeHemicycle']) 
                    ? (int) $mandature['placeHemicycle'] 
                    : null,
                'suppleant_ref' => $suppleantRef,
            ]
        );

        if ($model->wasRecentlyCreated) {
            $this->created++;
        } else {
            $this->updated++;
        }
    }

    private function displaySummary(int $legislature): void
    {
        $this->info('✅ Import terminé !');
        $this->newLine();
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✓ Créés', $this->created],
                ['↻ Mis à jour', $this->updated],
                ['⊘ Ignorés', $this->skipped],
                ['⚠ Erreurs', $this->errors],
            ]
        );

        // Statistiques
        $total = DeputeCirconscription::legislature($legislature)->count();
        $actifs = DeputeCirconscription::legislature($legislature)->actif()->count();
        
        $this->newLine();
        $this->info("📊 Total législature {$legislature} : {$total} liaisons");
        $this->info("📊 Mandats actifs : {$actifs}");

        // Top régions
        $topRegions = DeputeCirconscription::legislature($legislature)
            ->selectRaw('region, COUNT(*) as total')
            ->groupBy('region')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        if ($topRegions->isNotEmpty()) {
            $this->newLine();
            $this->info('📊 Top 5 régions :');
            foreach ($topRegions as $r) {
                $this->line("   - {$r->region} : {$r->total} députés");
            }
        }

        // Top départements
        $topDepts = DeputeCirconscription::legislature($legislature)
            ->selectRaw('departement, COUNT(*) as total')
            ->groupBy('departement')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        if ($topDepts->isNotEmpty()) {
            $this->newLine();
            $this->info('📊 Top 5 départements :');
            foreach ($topDepts as $d) {
                $this->line("   - {$d->departement} : {$d->total} députés");
            }
        }
    }
}
