<?php

namespace App\Console\Commands;

use App\Models\CitizenLawStats;
use App\Models\CitizenLawVote;
use App\Models\Loi;
use App\Models\User;
use Illuminate\Console\Command;

class GenerateSyntheticVotes extends Command
{
    protected $signature = 'demo:generate-votes 
                            {--count=1000 : Nombre de votes à générer}
                            {--lois=100 : Nombre de lois à cibler}
                            {--clear : Supprimer les votes existants avant}';

    protected $description = 'Génère des votes citoyens synthétiques pour la démo';

    public function handle(): int
    {
        $this->info('🗳️ Génération de votes citoyens synthétiques...');

        if ($this->option('clear')) {
            CitizenLawVote::truncate();
            CitizenLawStats::truncate();
            $this->warn('   Votes existants supprimés');
        }

        $votesCount = (int) $this->option('count');
        $loisCount = (int) $this->option('lois');

        // Récupérer des utilisateurs (ou en créer des fictifs)
        $users = User::limit(50)->pluck('id')->toArray();

        if (empty($users)) {
            $this->error('Aucun utilisateur trouvé. Créez d\'abord des utilisateurs.');

            return Command::FAILURE;
        }

        $this->info('   Utilisateurs disponibles : '.count($users));

        // Récupérer des lois récentes (promulguées ou en cours)
        // La table senat_dosleg_loi utilise: loicod, loitit, etaloicod
        $lois = Loi::whereIn('etaloicod', ['promulgue', 'en_cours', 'P', 'EC'])
            ->orderByDesc('loicod')
            ->limit($loisCount)
            ->get(['loicod', 'loitit']);

        if ($lois->isEmpty()) {
            // Fallback: prendre n'importe quelles lois
            $lois = Loi::orderByDesc('loicod')
                ->limit($loisCount)
                ->get(['loicod', 'loitit']);
        }

        $this->info('   Lois ciblées : '.$lois->count());

        if ($lois->isEmpty()) {
            $this->error('Aucune loi trouvée.');

            return Command::FAILURE;
        }

        $bar = $this->output->createProgressBar($votesCount);
        $bar->start();

        $created = 0;
        $skipped = 0;

        for ($i = 0; $i < $votesCount; $i++) {
            // Sélectionner aléatoirement un utilisateur et une loi
            $userId = $users[array_rand($users)];
            $loi = $lois->random();

            // Générer un vote avec un biais variable selon la loi
            // Pour simuler des lois populaires/impopulaires
            $loiHash = crc32($loi->loicod);
            $biasPour = ($loiHash % 100) / 100; // 0 à 1

            $vote = (mt_rand(0, 100) / 100) < $biasPour ? 1 : -1;

            try {
                CitizenLawVote::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'loi_cod' => $loi->loicod,
                    ],
                    [
                        'vote' => $vote,
                    ]
                );
                $created++;
            } catch (\Exception $e) {
                $skipped++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Recalculer les statistiques pour toutes les lois votées
        $this->info('📊 Recalcul des statistiques...');

        $loiCods = CitizenLawVote::distinct()->pluck('loi_cod');
        $statsBar = $this->output->createProgressBar($loiCods->count());

        foreach ($loiCods as $loiCod) {
            CitizenLawStats::recalculateForLoi($loiCod);
            $statsBar->advance();
        }

        $statsBar->finish();
        $this->newLine();

        // Afficher quelques exemples
        $this->info('');
        $this->info('📋 Exemples de résultats :');

        $examples = CitizenLawStats::orderByDesc('total_votes')
            ->limit(5)
            ->get();

        $this->table(
            ['Loi', 'Pour', 'Contre', 'Total', '% Pour'],
            $examples->map(fn ($s) => [
                substr($s->loi_cod, 0, 30),
                $s->votes_pour,
                $s->votes_contre,
                $s->total_votes,
                $s->pct_pour.'%',
            ])
        );

        $this->info('');
        $this->info("✅ Terminé ! {$created} votes créés, {$skipped} ignorés (doublons)");
        $this->info("   {$loiCods->count()} lois avec des votes citoyens");

        return Command::SUCCESS;
    }
}
