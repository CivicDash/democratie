<?php

namespace App\Console\Commands;

use App\Models\ResultatListeMunicipale;
use App\Models\ResultatMunicipal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnrichTetesListe extends Command
{
    protected $signature = 'municipales:enrich-tetes-liste
                            {tour=1 : Tour de l\'élection (1 ou 2)}
                            {--url= : URL du CSV candidatures (par défaut: data.gouv.fr)}
                            {--dry-run : Simuler sans écrire}';

    protected $description = 'Enrichit les résultats municipaux avec les noms/sexe des têtes de liste depuis le CSV candidatures';

    private const CANDIDATURES_T1 = 'https://static.data.gouv.fr/resources/elections-municipales-2026-listes-candidates-au-premier-tour/20260313-152615/municipales-2026-candidatures-france-entiere-tour-1-2026-03-13.csv';
    private const CANDIDATURES_T2 = 'https://static.data.gouv.fr/resources/elections-municipales-2026-listes-candidates-au-second-tour/20260320-141955/municipales-2026-candidatures-france-entiere-tour-2-2026-03-20.csv';

    private int $matched = 0;
    private int $notFound = 0;
    private int $skipped = 0;

    public function handle(): int
    {
        $tour = (int) $this->argument('tour');
        $url = $this->option('url') ?: ($tour === 2 ? self::CANDIDATURES_T2 : self::CANDIDATURES_T1);
        $dryRun = $this->option('dry-run');

        $this->info("Enrichissement des têtes de liste T{$tour} depuis le CSV candidatures");
        $this->info("URL: {$url}");
        if ($dryRun) {
            $this->warn('Mode DRY RUN');
        }
        $this->newLine();

        $context = stream_context_create(['http' => ['timeout' => 120]]);
        $handle = fopen($url, 'r', false, $context);

        if (!$handle) {
            $this->error('Impossible de télécharger le fichier');
            return self::FAILURE;
        }

        $header = fgetcsv($handle, 0, ';', '"', '\\');
        $this->info('Colonnes: ' . count($header));

        $tetesListe = [];

        $this->info('Lecture du CSV et extraction des têtes de liste...');
        $lineCount = 0;

        while (($row = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
            $lineCount++;
            if (count($row) < 14) {
                continue;
            }

            $estTete = trim($row[9] ?? '');
            if ($estTete !== 'OUI') {
                continue;
            }

            $codeCommune = trim($row[2] ?? '');
            $panneau = (int) trim($row[4] ?? '0');
            $sexe = trim($row[11] ?? '');
            $nom = trim($row[12] ?? '');
            $prenom = trim($row[13] ?? '');

            if (!$codeCommune || !$panneau || !$nom) {
                continue;
            }

            $key = "{$codeCommune}_{$panneau}";
            $tetesListe[$key] = [
                'code_commune' => $codeCommune,
                'panneau' => $panneau,
                'nom' => $nom,
                'prenom' => $prenom,
                'sexe' => $sexe,
            ];
        }

        fclose($handle);

        $this->info("Lignes CSV lues: {$lineCount}");
        $this->info('Têtes de liste extraites: ' . count($tetesListe));
        $this->newLine();

        $resultatsIndex = [];
        ResultatMunicipal::where('tour', $tour)
            ->select('id', 'code_commune')
            ->chunk(5000, function ($chunk) use (&$resultatsIndex) {
                foreach ($chunk as $r) {
                    $resultatsIndex[$r->code_commune] = $r->id;
                }
            });

        $this->info('Résultats communaux indexés: ' . count($resultatsIndex));

        $bar = $this->output->createProgressBar(count($tetesListe));
        $bar->setFormat('verbose');
        $bar->start();

        $batch = [];

        foreach ($tetesListe as $key => $tete) {
            $resultatId = $resultatsIndex[$tete['code_commune']] ?? null;

            if (!$resultatId) {
                $this->notFound++;
                $bar->advance();
                continue;
            }

            if (!$dryRun) {
                $updated = ResultatListeMunicipale::where('resultat_commune_id', $resultatId)
                    ->where('numero_panneau', $tete['panneau'])
                    ->update([
                        'tete_de_liste_nom' => $tete['nom'],
                        'tete_de_liste_prenom' => $tete['prenom'],
                        'tete_de_liste_sexe' => $tete['sexe'] ?: null,
                    ]);

                if ($updated > 0) {
                    $this->matched++;
                } else {
                    $this->notFound++;
                }
            } else {
                $this->matched++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Métrique', 'Valeur'], [
            ['Têtes de liste dans le CSV', count($tetesListe)],
            ['Résultats enrichis', $this->matched],
            ['Non trouvés en base', $this->notFound],
        ]);

        $enriched = ResultatListeMunicipale::whereNotNull('tete_de_liste_nom')
            ->where('tete_de_liste_nom', '!=', '')
            ->count();
        $this->info("Total listes avec tête de liste en base: {$enriched}");

        return self::SUCCESS;
    }
}
