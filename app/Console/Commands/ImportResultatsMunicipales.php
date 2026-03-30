<?php

namespace App\Console\Commands;

use App\Models\CandidatMunicipal;
use App\Models\ListeElectorale;
use App\Models\ResultatListeMunicipale;
use App\Models\ResultatMunicipal;
use App\Models\Ville;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportResultatsMunicipales extends Command
{
    protected $signature = 'municipales:import-resultats
                            {tour=1 : Tour de l\'élection (1 ou 2)}
                            {--file= : Chemin vers un fichier CSV local}
                            {--url= : URL directe du CSV}
                            {--dry-run : Simuler sans écrire en base}
                            {--limit= : Limiter le nombre de communes (test)}';

    protected $description = 'Importe les résultats des élections municipales depuis data.gouv.fr';

    private const DATAGOUV_T1 = 'https://static.data.gouv.fr/resources/elections-municipales-2026-resultats-du-premier-tour/20260320-164339/municipales-2026-resultats-communes-2026-03-20.csv';

    private const DATAGOUV_T2 = 'https://static.data.gouv.fr/resources/elections-municipales-2026-resultats-du-scond-tour/20260323-180124/municipales-2026-resultats-communes-2026-03-23-16h14.csv';

    private const FIXED_COLS = 18;

    private const LIST_BLOCK_SIZE = 13;

    private int $communesProcessed = 0;

    private int $listesProcessed = 0;

    private int $elusT1 = 0;

    private int $secondTour = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $tour = (int) $this->argument('tour');
        $dryRun = $this->option('dry-run');

        $this->info("Import des résultats municipales — Tour {$tour}");

        $csvContent = $this->loadCsv($tour);
        if ($csvContent === null) {
            return self::FAILURE;
        }

        $lines = $this->parseCsvLines($csvContent);
        $this->info(count($lines).' communes trouvées');

        $limit = $this->option('limit');
        if ($limit) {
            $lines = array_slice($lines, 0, (int) $limit);
            $this->warn("Mode test : limité à {$limit} communes");
        }

        $bar = $this->output->createProgressBar(count($lines));
        $bar->setFormat('verbose');
        $bar->start();

        foreach ($lines as $line) {
            try {
                $data = str_getcsv($line, ';', '"');
                if (count($data) < self::FIXED_COLS + self::LIST_BLOCK_SIZE) {
                    $bar->advance();

                    continue;
                }

                if (trim($data[0], '"') === 'Code département') {
                    $bar->advance();

                    continue;
                }

                if (! $dryRun) {
                    DB::transaction(fn () => $this->processCommune($data, $tour));
                }
                $this->communesProcessed++;
            } catch (\Exception $e) {
                $this->errors++;
                if ($this->output->isVerbose()) {
                    $this->newLine();
                    $codeCommune = trim($data[2] ?? '??');
                    $this->error("Erreur ({$codeCommune}): {$e->getMessage()}");
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->displaySummary($tour, $dryRun);

        return self::SUCCESS;
    }

    /**
     * Format horizontal data.gouv.fr :
     * Colonnes fixes 0-17 (participation), puis blocs de 13 colonnes par liste.
     *
     * Bloc liste (offset +0 à +12) :
     * +0 N°Panneau, +1 Nom, +2 Prénom, +3 Sexe, +4 Nuance,
     * +5 Lib.abrégé, +6 Lib.complet, +7 Voix, +8 %Voix/ins,
     * +9 %Voix/exp, +10 Élu, +11 Sièges CM, +12 Sièges CC
     */
    private function processCommune(array $data, int $tour): void
    {
        $codeDept = trim($data[0] ?? '');
        $codeCommune = str_pad(trim($data[2] ?? ''), 5, '0', STR_PAD_LEFT);
        $nomCommune = trim($data[3] ?? '');

        $inscrits = $this->parseInt($data[4] ?? '0');
        $votants = $this->parseInt($data[5] ?? '0');
        $tauxVotants = $this->parsePercent($data[6] ?? '0');
        $abstentions = $this->parseInt($data[7] ?? '0');
        $tauxAbstention = $this->parsePercent($data[8] ?? '0');
        $exprimes = $this->parseInt($data[9] ?? '0');
        $blancs = $this->parseInt($data[12] ?? '0');
        $nuls = $this->parseInt($data[15] ?? '0');

        $ville = Ville::where('code_insee', $codeCommune)->first();

        $resultat = ResultatMunicipal::updateOrCreate(
            ['code_commune' => $codeCommune, 'tour' => $tour],
            [
                'nom_commune' => $nomCommune,
                'code_departement' => $codeDept,
                'inscrits' => $inscrits,
                'abstentions' => $abstentions,
                'taux_abstention' => $tauxAbstention,
                'votants' => $votants,
                'taux_participation' => $tauxVotants,
                'blancs' => $blancs,
                'nuls' => $nuls,
                'exprimes' => $exprimes,
                'ville_id' => $ville?->id,
            ]
        );

        $listeGagnante = null;
        $meilleurScore = 0;
        $nbListes = 0;
        $totalSieges = 0;

        $offset = self::FIXED_COLS;
        while ($offset + self::LIST_BLOCK_SIZE <= count($data)) {
            $panneau = trim($data[$offset] ?? '');
            if ($panneau === '') {
                break;
            }

            $nbListes++;

            $nomCandidat = trim($data[$offset + 1] ?? '');
            $prenomCandidat = trim($data[$offset + 2] ?? '');
            $nuance = trim($data[$offset + 4] ?? '');
            $libelleAbrege = trim($data[$offset + 5] ?? '');
            $libelleComplet = trim($data[$offset + 6] ?? '');
            $voix = $this->parseInt($data[$offset + 7] ?? '0');
            $pctInscrits = $this->parsePercent($data[$offset + 8] ?? '0');
            $pctExprimes = $this->parsePercent($data[$offset + 9] ?? '0');
            $eluRaw = trim($data[$offset + 10] ?? '');
            $siegesCM = $this->parseIntNullable($data[$offset + 11] ?? '');
            $siegesCC = $this->parseIntNullable($data[$offset + 12] ?? '');

            $eluFlag = ! empty($eluRaw);

            $listeOfficielle = ListeElectorale::where('commune_code_insee', $codeCommune)
                ->where('numero_panneau', (int) $panneau)
                ->where('tour', $tour)
                ->where('source', 'datagouv')
                ->first();

            $nomListe = $libelleComplet ?: $libelleAbrege ?: null;

            $resultatListe = ResultatListeMunicipale::updateOrCreate(
                [
                    'resultat_commune_id' => $resultat->id,
                    'numero_panneau' => (int) $panneau,
                ],
                [
                    'liste_id' => $listeOfficielle?->id,
                    'nom_liste' => $nomListe,
                    'nuance_politique' => $nuance ?: null,
                    'tete_de_liste_nom' => $nomCandidat ?: null,
                    'tete_de_liste_prenom' => $prenomCandidat ?: null,
                    'voix' => $voix,
                    'pourcentage_exprimes' => $pctExprimes,
                    'pourcentage_inscrits' => $pctInscrits,
                    'elu' => $eluFlag,
                    'sieges_obtenus' => $siegesCM,
                    'sieges_conseil_communautaire' => $siegesCC,
                ]
            );

            $this->listesProcessed++;
            $totalSieges += $siegesCM ?? 0;

            if ($voix > $meilleurScore) {
                $meilleurScore = $voix;
                $listeGagnante = $resultatListe;
            }

            if ($eluFlag && $listeOfficielle) {
                CandidatMunicipal::where('liste_id', $listeOfficielle->id)
                    ->where('est_tete_de_liste', true)
                    ->update(['elu' => true]);
            }

            $offset += self::LIST_BLOCK_SIZE;
        }

        $statut = $this->determineStatutCommune($tour, $inscrits, $exprimes, $listeGagnante, $nbListes, $totalSieges);
        $resultat->update([
            'statut_commune' => $statut,
            'nb_listes' => $nbListes,
            'nb_sieges_pourvus' => $totalSieges > 0 ? $totalSieges : null,
        ]);

        if (in_array($statut, ['elu_t1', 'elu_t2']) && $listeGagnante && ! $listeGagnante->elu) {
            $listeGagnante->update(['elu' => true]);
        }

        if ($statut === 'elu_t1') {
            $this->elusT1++;
        } elseif ($statut === 'second_tour') {
            $this->secondTour++;
        }
    }

    private function determineStatutCommune(
        int $tour,
        int $inscrits,
        int $exprimes,
        ?ResultatListeMunicipale $gagnante,
        int $nbListes,
        int $totalSieges,
    ): string {
        if (! $gagnante) {
            return 'sans_candidat';
        }

        if ($gagnante->elu) {
            return $tour === 1 ? 'elu_t1' : 'elu_t2';
        }

        if ($nbListes === 1 && $totalSieges > 0) {
            return $tour === 1 ? 'elu_t1' : 'elu_t2';
        }

        if ($tour === 1 && $exprimes > 0 && $inscrits > 0) {
            $pctExprimes = ($gagnante->voix / $exprimes) * 100;
            $pctInscrits = ($gagnante->voix / $inscrits) * 100;

            if ($pctExprimes > 50 && $pctInscrits >= 25) {
                return 'elu_t1';
            }

            return 'second_tour';
        }

        return $tour === 2 ? 'elu_t2' : 'second_tour';
    }

    private function parseInt(string $val): int
    {
        $val = trim($val, " \t\n\r\0\x0B\"");
        $val = str_replace([' ', '%', "\xc2\xa0"], '', $val);

        return (int) $val;
    }

    private function parseIntNullable(string $val): ?int
    {
        $val = trim($val, " \t\n\r\0\x0B\"");
        if ($val === '') {
            return null;
        }

        return (int) $val;
    }

    private function parsePercent(string $val): float
    {
        $val = trim($val, " \t\n\r\0\x0B\"");
        $val = str_replace(['%', ' ', "\xc2\xa0"], '', $val);
        $val = str_replace(',', '.', $val);

        return (float) $val;
    }

    private function loadCsv(int $tour): ?string
    {
        if ($file = $this->option('file')) {
            if (! file_exists($file)) {
                $this->error("Fichier introuvable : {$file}");

                return null;
            }
            $content = file_get_contents($file);
        } else {
            $url = $this->option('url') ?? ($tour === 1 ? self::DATAGOUV_T1 : self::DATAGOUV_T2);
            if (empty($url)) {
                $this->error("Aucune URL configurée pour le tour {$tour}. Utilisez --url= ou --file= pour fournir les données.");

                return null;
            }
            $this->info("Téléchargement depuis : {$url}");

            try {
                $response = Http::timeout(300)->get($url);
                if (! $response->successful()) {
                    $this->error("Erreur HTTP : {$response->status()}");

                    return null;
                }
                $content = $response->body();
            } catch (\Exception $e) {
                $this->error("Erreur de téléchargement : {$e->getMessage()}");

                return null;
            }
        }

        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'ISO-8859-15', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        return $content;
    }

    private function parseCsvLines(string $content): array
    {
        $lines = explode("\n", $content);
        array_shift($lines);

        return array_filter($lines, fn ($l) => trim($l) !== '');
    }

    private function displaySummary(int $tour, bool $dryRun): void
    {
        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Import des résultats T{$tour} terminé !");
        $this->newLine();
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Communes traitées', $this->communesProcessed],
                ['Résultats de listes', $this->listesProcessed],
                ['Élues au T1', $this->elusT1],
                ['Vers second tour', $this->secondTour],
                ['Erreurs', $this->errors],
            ]
        );
    }
}
