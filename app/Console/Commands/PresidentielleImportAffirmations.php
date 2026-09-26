<?php

namespace App\Console\Commands;

use App\Services\Presidentielle\ImportAffirmations;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Import des fiches « Ce qu'on entend » (contrat presidentielle.affirmations.v1).
 * Tout entre en `detecte`, non publié. Voir ImportAffirmations pour les règles.
 */
class PresidentielleImportAffirmations extends Command
{
    protected $signature = 'presidentielle:import-affirmations
        {fichier : chemin du fichier JSON}
        {--remplacer : écraser une fiche non publiée déjà en base}
        {--dry-run : contrôler et simuler sans écrire en base}';

    protected $description = 'Importe des fiches « Ce qu\'on entend » en file de modération (statut detecte).';

    public function handle(ImportAffirmations $import): int
    {
        $fichier = $this->argument('fichier');
        if (! is_file($fichier)) {
            $this->error("Fichier introuvable : {$fichier}");

            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($fichier), true);
        if (! is_array($data)) {
            $this->error('JSON invalide : '.json_last_error_msg());

            return self::FAILURE;
        }

        $erreurs = $import->valider($data, (bool) $this->option('remplacer'));
        if ($erreurs) {
            $this->error(count($erreurs).' erreur(s), rien n\'est importé :');
            foreach ($erreurs as $e) {
                $this->line("  - {$e}");
            }

            return self::FAILURE;
        }

        DB::beginTransaction();
        try {
            $stats = $import->ecrire($data);
            if ($this->option('dry-run')) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Échec import : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(($this->option('dry-run') ? 'DRY-RUN, rien n\'est écrit. ' : '')
            ."Fiches : {$stats['fiches']} (detecte) · constats : {$stats['constats']}, dont {$stats['a_verifier']} à vérifier · sources : {$stats['sources']} · graphiques : {$stats['graphiques']}.");

        return self::SUCCESS;
    }
}
