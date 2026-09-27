<?php

namespace App\Console\Commands;

use App\Models\ImportLog;
use App\Services\Presidentielle\Eurostat\ExtractionEurostat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Extraction mensuelle des comparaisons européennes des repères chiffrés.
 *
 * Ne change jamais ce que le site montre : les séries qui diffèrent de la version publiée
 * attendent une validation humaine dans l'écran Eurostat du back-office.
 */
class PresidentielleEurostatExtraire extends Command
{
    protected $signature = 'presidentielle:eurostat-extraire
        {--dry-run : interroger Eurostat sans rien enregistrer}
        {--sortie= : écrire aussi le résultat brut dans ce fichier (comparaison avec fetch_eurostat.py)}';

    protected $description = 'Extrait les séries Eurostat du catalogue et dépose les révisions en attente de validation.';

    public function handle(ExtractionEurostat $extraction): int
    {
        $log = $this->option('dry-run') ? null : ImportLog::start('presidentielle:eurostat-extraire', 'eurostat');

        try {
            $resultat = $extraction->extraire();
        } catch (\Throwable $e) {
            $log?->fail($e->getMessage(), null, self::FAILURE);
            $this->error('Extraction interrompue : '.$e->getMessage());

            return self::FAILURE;
        }

        if ($chemin = $this->option('sortie')) {
            File::put($chemin, json_encode($resultat, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->line("Résultat écrit dans {$chemin}");
        }

        $n = count($resultat['indicateurs']);
        if ($this->option('dry-run')) {
            $this->info("DRY-RUN : {$n} indicateurs lus, rien n'est enregistré.");

            return self::SUCCESS;
        }

        $bilan = $extraction->enregistrer($resultat);
        $log?->finish($bilan['nouveaux'], $bilan['revisions'], $bilan['inchanges'], 0, self::SUCCESS);
        $this->info("{$n} indicateurs : {$bilan['nouveaux']} nouveau(x), {$bilan['revisions']} révision(s) à relire, {$bilan['inchanges']} inchangé(s). Archive : {$bilan['archive']}");

        return self::SUCCESS;
    }
}
