<?php

namespace App\Console\Commands;

use App\Services\Presidentielle\IntegriteChecker;
use App\Services\Presidentielle\PresidentielleExporter;
use Illuminate\Console\Command;

/**
 * Génère l'export JSON statique du contenu présidentielle publié.
 * Lance d'abord le contrôle d'intégrité : en cas de violation bloquante, l'export
 * est refusé (sauf --force). Ne sort que le contenu valide + affiché.
 */
class PresidentielleExport extends Command
{
    protected $signature = 'presidentielle:export
        {--election=2027 : élection à exporter}
        {--path= : répertoire de sortie (défaut: storage/app/presidentielle/dist)}
        {--force : exporter malgré des violations d\'intégrité}';

    protected $description = 'Exporte le contenu présidentielle publié en JSON statique versionné.';

    public function handle(IntegriteChecker $integrite, PresidentielleExporter $exporter): int
    {
        $election = (string) $this->option('election');
        $dir = $this->option('path') ?: storage_path('app/presidentielle/dist');

        $resultat = $integrite->analyser($election);
        if (! empty($resultat['violations']) && ! $this->option('force')) {
            $this->error(count($resultat['violations']).' violation(s) d\'intégrité — export refusé. Corriger, ou --force pour passer outre.');
            foreach ($resultat['violations'] as $v) {
                $this->line("  ✗ {$v['message']}");
            }

            return self::FAILURE;
        }
        foreach ($resultat['alertes'] as $a) {
            $this->warn('⚠  '.$a['message']);
        }

        $data = $exporter->build($election);
        $fichiers = $exporter->write($data, $dir);

        $this->info('Export généré : '.count($fichiers)." fichier(s) dans {$dir}");
        $this->line("  candidats: {$data['meta']['nb_candidats']} · thèmes: {$data['meta']['nb_themes']}");
        $this->line("  content_hash: {$data['meta']['content_hash']}");

        return self::SUCCESS;
    }
}
