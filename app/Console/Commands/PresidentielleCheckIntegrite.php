<?php

namespace App\Console\Commands;

use App\Services\Presidentielle\IntegriteChecker;
use Illuminate\Console\Command;

/**
 * Vérifie l'intégrité éditoriale du contenu présidentielle publié.
 * Sort en échec (code 1) si des violations bloquantes existent — utilisé comme
 * garde-fou par presidentielle:export.
 */
class PresidentielleCheckIntegrite extends Command
{
    protected $signature = 'presidentielle:check-integrite {--election=2027}';

    protected $description = 'Contrôle les asymétries et violations éditoriales du contenu publié (bloque l\'export si violation).';

    public function handle(IntegriteChecker $checker): int
    {
        $election = (string) $this->option('election');
        $resultat = $checker->analyser($election);

        foreach ($resultat['alertes'] as $a) {
            $this->warn('⚠  '.$a['message']);
        }

        if (empty($resultat['violations'])) {
            $this->info("Intégrité OK — aucune violation bloquante (élection {$election}).");
            if ($resultat['alertes']) {
                $this->line(count($resultat['alertes']).' alerte(s) de symétrie à surveiller.');
            }

            return self::SUCCESS;
        }

        $this->error(count($resultat['violations']).' violation(s) bloquante(s) :');
        foreach ($resultat['violations'] as $v) {
            $this->line("  ✗ [{$v['type']}] {$v['message']}");
        }

        return self::FAILURE;
    }
}
