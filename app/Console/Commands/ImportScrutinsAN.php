<?php

namespace App\Console\Commands;

use App\Models\ScrutinAN;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportScrutinsAN extends Command
{
    protected $signature = 'import:scrutins-an 
                            {--legislature=17 : Législature à importer (par défaut: 17)}
                            {--all : Importer tous les scrutins (toutes législatures)}
                            {--limit= : Limite le nombre de scrutins à importer (pour tests)}
                            {--fresh : Vide la table avant l\'import}';

    protected $description = 'Importe les scrutins (votes publics) depuis les fichiers JSON AN';

    private int $imported = 0;

    private int $updated = 0;

    private int $skipped = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $legislature = $this->option('legislature');
        $importAll = $this->option('all');

        $this->info('🏛️  Import des scrutins AN...');

        if ($importAll) {
            $this->warn('⚠️  Mode --all : import de TOUS les scrutins (toutes législatures)');
        } else {
            $this->info("📊 Législature cible : {$legislature}");
        }

        $basePath = public_path('data/scrutins');

        if (! is_dir($basePath)) {
            $this->error("❌ Répertoire introuvable : {$basePath}");

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->warn('⚠️  Mode --fresh : suppression des scrutins existants...');
            ScrutinAN::truncate();
        }

        $files = File::glob($basePath.'/*.json');
        $total = count($files);

        // Filtrage par législature si nécessaire
        if (! $importAll) {
            $files = array_filter($files, function ($file) use ($legislature) {
                return str_contains(basename($file), "L{$legislature}V");
            });
            $files = array_values($files);
        }

        $limit = $this->option('limit');
        if ($limit) {
            $files = array_slice($files, 0, (int) $limit);
            $this->warn("⚠️  Mode TEST : {$limit} scrutins maximum");
        }

        $this->info("📊 {$total} fichiers trouvés, ".count($files).' à importer');
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $file) {
            try {
                $this->importScrutin($file);
            } catch (\Exception $e) {
                $this->errors++;
                $this->newLine();
                $this->warn('⚠️  Erreur dans '.basename($file).": {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->displaySummary($legislature, $importAll);

        return self::SUCCESS;
    }

    private function importScrutin(string $filePath): void
    {
        $content = File::get($filePath);
        $data = json_decode($content, true);

        if (! isset($data['scrutin'])) {
            throw new \Exception('Structure JSON invalide');
        }

        $scrutin = $data['scrutin'];
        $uid = $scrutin['uid'] ?? null;

        if (! $uid) {
            throw new \Exception('UID manquant');
        }

        // Extraction des données
        $syntheseVote = $scrutin['syntheseVote'] ?? [];
        $decompte = $syntheseVote['decompte'] ?? [];
        $ventilation = $scrutin['ventilationVotes'] ?? [];

        // Extraction legislature depuis l'UID (ex: VTANR5L17V1000 -> 17)
        preg_match('/L(\d+)V/', $uid, $matches);
        $legislature = isset($matches[1]) ? (int) $matches[1] : null;

        // Insert ou update
        $scrutinModel = ScrutinAN::updateOrCreate(
            ['uid' => $uid],
            [
                'numero' => $scrutin['numero'] ?? null,
                'organe_ref' => $scrutin['organeRef'] ?? null,
                'seance_ref' => $scrutin['seanceRef'] ?? null,
                'legislature' => $legislature,
                'date_scrutin' => $scrutin['dateScrutin'] ?? null,
                'type_vote_code' => $scrutin['typeVote']['codeTypeVote'] ?? null,
                'type_vote_libelle' => $scrutin['typeVote']['libelleTypeVote'] ?? null,
                'resultat_code' => $syntheseVote['resultat'] ?? null,
                'resultat_libelle' => $syntheseVote['resultatDetail'] ?? null,
                'titre' => $scrutin['titre'] ?? null,
                'nombre_votants' => $decompte['nombreVotants'] ?? 0,
                'suffrages_exprimes' => $decompte['suffragesExprimes'] ?? 0,
                'suffrage_requis' => $decompte['suffrageRequis'] ?? 0,
                'pour' => $decompte['pour']['nombreVotes'] ?? 0,
                'contre' => $decompte['contre']['nombreVotes'] ?? 0,
                'abstentions' => $decompte['abstentions']['nombreVotes'] ?? 0,
                'non_votants' => $decompte['nonVotants']['nombreVotes'] ?? null,
                'ventilation_votes' => $ventilation, // JSON complet
            ]
        );

        if ($scrutinModel->wasRecentlyCreated) {
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
                ['✓ Nouveaux scrutins', $this->imported],
                ['↻ Scrutins mis à jour', $this->updated],
                ['⊘ Scrutins skippés', $this->skipped],
                ['⚠ Erreurs', $this->errors],
            ]
        );

        // Stats finales
        $total = ScrutinAN::count();
        $adoptes = ScrutinAN::adopte()->count();
        $rejetes = ScrutinAN::rejete()->count();

        if (! $importAll) {
            $totalLeg = ScrutinAN::legislature($legislature)->count();
            $adoptesLeg = ScrutinAN::legislature($legislature)->adopte()->count();
            $rejetesLeg = ScrutinAN::legislature($legislature)->rejete()->count();
        }

        $this->newLine();
        $this->info("📊 Total en base de données : {$total} scrutins");
        $this->info("   - Adoptés : {$adoptes}");
        $this->info("   - Rejetés : {$rejetes}");

        if (! $importAll) {
            $this->newLine();
            $this->info("📊 Législature {$legislature} : {$totalLeg} scrutins");
            $this->info("   - Adoptés : {$adoptesLeg}");
            $this->info("   - Rejetés : {$rejetesLeg}");
        }
    }
}
