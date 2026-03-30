<?php

namespace App\Console\Commands;

use App\Models\ScrutinAN;
use App\Models\VoteIndividuelAN;
use Illuminate\Console\Command;

class RecalculateScrutinTotals extends Command
{
    protected $signature = 'scrutins:recalculate-totals 
                            {--legislature=17 : Législature à recalculer}
                            {--all : Recalculer toutes les législatures}';

    protected $description = 'Recalcule les totaux (pour/contre/abstentions) des scrutins depuis les votes individuels';

    public function handle(): int
    {
        $this->info('🔄 Recalcul des totaux des scrutins...');

        $query = ScrutinAN::query();

        if (! $this->option('all')) {
            $legislature = $this->option('legislature');
            $query->where('legislature', $legislature);
            $this->info("📊 Législature: {$legislature}");
        } else {
            $this->info('📊 Toutes les législatures');
        }

        $scrutins = $query->get();
        $total = $scrutins->count();
        $this->info("📋 {$total} scrutins à traiter");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        $skipped = 0;

        foreach ($scrutins as $scrutin) {
            // Compter les votes individuels
            $votes = VoteIndividuelAN::where('scrutin_ref', $scrutin->uid)->get();

            if ($votes->isEmpty()) {
                $skipped++;
                $bar->advance();

                continue;
            }

            $pour = $votes->where('position', 'pour')->count();
            $contre = $votes->where('position', 'contre')->count();
            $abstentions = $votes->where('position', 'abstention')->count();
            $nonVotants = $votes->where('position', 'non_votant')->count();
            $votants = $votes->count();

            // Mettre à jour le scrutin
            $scrutin->update([
                'pour' => $pour,
                'contre' => $contre,
                'abstentions' => $abstentions,
                'non_votants' => $nonVotants,
                'nombre_votants' => $votants,
                'suffrages_exprimes' => $pour + $contre,
            ]);

            // Déterminer le résultat si null
            if (! $scrutin->resultat_code) {
                if ($pour > $contre) {
                    $scrutin->update([
                        'resultat_code' => 'adopté',
                        'resultat_libelle' => 'Adopté',
                    ]);
                } elseif ($contre > $pour) {
                    $scrutin->update([
                        'resultat_code' => 'rejeté',
                        'resultat_libelle' => 'Rejeté',
                    ]);
                }
            }

            $updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('✅ Recalcul terminé !');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Scrutins traités', $total],
                ['Scrutins mis à jour', $updated],
                ['Scrutins sans votes', $skipped],
            ]
        );

        return Command::SUCCESS;
    }
}
