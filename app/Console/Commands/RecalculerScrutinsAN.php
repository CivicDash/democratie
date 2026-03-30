<?php

namespace App\Console\Commands;

use App\Models\ScrutinAN;
use App\Models\VoteIndividuelAN;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculerScrutinsAN extends Command
{
    protected $signature = 'scrutins:recalculer {--legislature=17 : Législature à traiter}';

    protected $description = 'Recalcule les totaux pour/contre/abstentions des scrutins AN depuis les votes individuels';

    public function handle(): int
    {
        $legislature = $this->option('legislature');

        $this->info("Recalcul des totaux des scrutins pour la législature {$legislature}...");

        $scrutins = ScrutinAN::where('legislature', $legislature)->get();
        $total = $scrutins->count();
        $updated = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($scrutins as $scrutin) {
            // Compter les votes par position
            $votes = VoteIndividuelAN::where('scrutin_ref', $scrutin->uid)
                ->select('position', DB::raw('COUNT(*) as count'))
                ->groupBy('position')
                ->pluck('count', 'position')
                ->toArray();

            $pour = $votes['pour'] ?? 0;
            $contre = $votes['contre'] ?? 0;
            $abstentions = $votes['abstention'] ?? 0;
            $nonVotants = $votes['non_votant'] ?? 0;

            $nombreVotants = $pour + $contre + $abstentions;
            $suffragesExprimes = $pour + $contre;

            // Mettre à jour si différent
            if ($scrutin->pour != $pour ||
                $scrutin->contre != $contre ||
                $scrutin->abstentions != $abstentions) {

                $scrutin->update([
                    'pour' => $pour,
                    'contre' => $contre,
                    'abstentions' => $abstentions,
                    'non_votants' => $nonVotants,
                    'nombre_votants' => $nombreVotants,
                    'suffrages_exprimes' => $suffragesExprimes,
                ]);

                $updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("✅ {$updated} scrutins mis à jour sur {$total} traités.");

        return Command::SUCCESS;
    }
}
