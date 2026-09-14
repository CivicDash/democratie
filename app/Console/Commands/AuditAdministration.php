<?php

namespace App\Console\Commands;

use App\Support\AuditEcritures;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Le même contrôle que les tests, mais lisible — et exécutable en production.
 */
class AuditAdministration extends Command
{
    protected $signature = 'civicdash:audit-admin
                            {--json : sortie machine}
                            {--strict : code de sortie 1 si une divergence subsiste}';

    protected $description = "Confronte les écritures de l'administration aux colonnes réelles";

    public function handle(): int
    {
        $ecritures = AuditEcritures::analyser();
        $composants = $this->composantsAbsents();

        if ($this->option('json')) {
            $this->line(json_encode([
                'ecritures' => $ecritures,
                'composants_absents' => $composants,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return $this->sortie($ecritures, $composants);
        }

        $parStatut = collect($ecritures)->groupBy('statut');

        $this->info('Écritures des routes admin');
        $this->line(sprintf(
            '  conformes %d · divergentes %d · indéterminées %d',
            $parStatut->get('conforme')?->count() ?? 0,
            $parStatut->get('divergent')?->count() ?? 0,
            $parStatut->get('indetermine')?->count() ?? 0,
        ));

        if ($divergentes = $parStatut->get('divergent')) {
            $this->newLine();
            $this->error('Ces écritures ne peuvent pas aboutir :');
            $this->table(
                ['Route', 'Modèle', 'Colonnes inexistantes', 'Hors $fillable'],
                $divergentes->map(fn (array $l) => [
                    $l['route'],
                    class_basename($l['modele']),
                    implode(', ', $l['inconnues']) ?: '—',
                    implode(', ', $l['non_fillable']) ?: '—',
                ])->all(),
            );
        }

        $this->newLine();
        $this->info('Composants Vue rendus mais absents');
        if ($composants === []) {
            $this->line('  aucun');
        } else {
            foreach ($composants as $page => $source) {
                $this->line("  {$page}  (rendu par {$source})");
            }
        }

        return $this->sortie($ecritures, $composants);
    }

    /** @return array<string, string> */
    private function composantsAbsents(): array
    {
        $manquants = [];

        foreach ([base_path('app'), base_path('routes')] as $racine) {
            foreach (File::allFiles($racine) as $fichier) {
                if ($fichier->getExtension() !== 'php') {
                    continue;
                }
                preg_match_all("/Inertia::render\(\s*'([^']+)'/", $fichier->getContents(), $trouves);
                foreach ($trouves[1] as $composant) {
                    if (! File::exists(resource_path("js/Pages/{$composant}.vue"))) {
                        $manquants[$composant] = $fichier->getRelativePathname();
                    }
                }
            }
        }

        ksort($manquants);

        return $manquants;
    }

    private function sortie(array $ecritures, array $composants): int
    {
        if (! $this->option('strict')) {
            return self::SUCCESS;
        }

        $divergentes = collect($ecritures)->where('statut', 'divergent')->count();

        return ($divergentes > 0 || $composants !== []) ? self::FAILURE : self::SUCCESS;
    }
}
