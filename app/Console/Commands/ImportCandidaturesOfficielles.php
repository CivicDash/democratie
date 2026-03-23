<?php

namespace App\Console\Commands;

use App\Models\CandidatMunicipal;
use App\Models\ListeElectorale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportCandidaturesOfficielles extends Command
{
    protected $signature = 'municipales:import-candidatures
                            {tour=1 : Tour de l\'élection (1 ou 2)}
                            {--file= : Chemin vers un fichier CSV local}
                            {--url= : URL directe du CSV}
                            {--dry-run : Simuler sans écrire en base}
                            {--limit= : Limiter le nombre de communes (test)}';

    protected $description = 'Importe les candidatures officielles municipales depuis data.gouv.fr';

    private const DATAGOUV_T1 = 'https://static.data.gouv.fr/resources/elections-municipales-2026-resultats-du-premier-tour/20260320-164339/municipales-2026-resultats-communes-2026-03-20.csv';
    private const DATAGOUV_T2 = 'https://static.data.gouv.fr/resources/elections-municipales-2026-resultats-du-premier-tour/20260320-164339/municipales-2026-resultats-communes-2026-03-20.csv';

    private const FIXED_COLS = 18;
    private const LIST_BLOCK_SIZE = 13;

    private int $listesCreated = 0;
    private int $listesUpdated = 0;
    private int $candidatsCreated = 0;
    private int $candidatsUpdated = 0;
    private int $matched = 0;
    private int $errors = 0;

    public function handle(): int
    {
        $tour = (int) $this->argument('tour');
        $dryRun = $this->option('dry-run');

        $this->info("Import des candidatures officielles — Tour {$tour}");

        $csvContent = $this->loadCsv($tour);
        if ($csvContent === null) {
            return self::FAILURE;
        }

        $lines = $this->parseCsvLines($csvContent);
        $this->info(count($lines) . ' communes trouvées dans le fichier');

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

                if (!$dryRun) {
                    DB::transaction(fn() => $this->processRow($data, $tour));
                }
            } catch (\Exception $e) {
                $this->errors++;
                if ($this->output->isVerbose()) {
                    $this->newLine();
                    $this->error("Erreur: {$e->getMessage()}");
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->displaySummary($dryRun);

        return self::SUCCESS;
    }

    /**
     * Traite une ligne CSV = une commune.
     * Extrait chaque bloc de liste (13 colonnes) à partir de la colonne 18.
     */
    private function processRow(array $data, int $tour): void
    {
        $codeDept = trim($data[0] ?? '');
        $codeCommune = str_pad(trim($data[2] ?? ''), 5, '0', STR_PAD_LEFT);
        $nomCommune = trim($data[3] ?? '');

        $offset = self::FIXED_COLS;

        while ($offset + self::LIST_BLOCK_SIZE <= count($data)) {
            $panneau = trim($data[$offset] ?? '');
            if ($panneau === '') {
                break;
            }

            $nomCandidat = trim($data[$offset + 1] ?? '');
            $prenomCandidat = trim($data[$offset + 2] ?? '');
            $sexeCandidat = trim($data[$offset + 3] ?? '');
            $nuance = trim($data[$offset + 4] ?? '');
            $libelleAbrege = trim($data[$offset + 5] ?? '');
            $libelleComplet = trim($data[$offset + 6] ?? '');

            $liste = ListeElectorale::updateOrCreate(
                [
                    'commune_code_insee' => $codeCommune,
                    'numero_panneau' => (int) $panneau,
                    'tour' => $tour,
                    'source' => 'datagouv',
                ],
                [
                    'uuid' => Str::uuid(),
                    'commune_nom' => $nomCommune,
                    'departement_code' => $codeDept,
                    'nom_liste' => $libelleComplet ?: $libelleAbrege ?: "Liste {$panneau}",
                    'nuance_politique' => $nuance ?: null,
                    'libelle_abrege' => $libelleAbrege ?: null,
                    'libelle_etendu' => $libelleComplet ?: null,
                    'statut' => 'officiel',
                ]
            );

            if ($liste->wasRecentlyCreated) {
                $this->listesCreated++;
            } else {
                $this->listesUpdated++;
            }

            if ($tour === 2) {
                $listeT1 = ListeElectorale::where('commune_code_insee', $codeCommune)
                    ->where('numero_panneau', (int) $panneau)
                    ->where('tour', 1)
                    ->where('source', 'datagouv')
                    ->first();
                if ($listeT1) {
                    $liste->update(['liste_t1_id' => $listeT1->id]);
                }
            }

            if (!empty($nomCandidat) && !empty($prenomCandidat)) {
                $this->processCandidat($liste, $nomCandidat, $prenomCandidat, $sexeCandidat);
            }

            $this->matchCivicdashListe($liste);

            $offset += self::LIST_BLOCK_SIZE;
        }
    }

    private function processCandidat(ListeElectorale $liste, string $nom, string $prenom, string $sexe): void
    {
        $nom = mb_convert_case($nom, MB_CASE_TITLE, 'UTF-8');
        $prenom = mb_convert_case($prenom, MB_CASE_TITLE, 'UTF-8');

        $candidat = CandidatMunicipal::updateOrCreate(
            [
                'liste_id' => $liste->id,
                'nom' => $nom,
                'prenom' => $prenom,
                'source' => 'datagouv',
            ],
            [
                'uuid' => Str::uuid(),
                'civilite' => $sexe === 'F' ? 'Mme' : 'M.',
                'sexe' => $sexe ?: null,
                'position' => 1,
                'est_tete_de_liste' => true,
                'statut' => 'actif',
            ]
        );

        if ($candidat->wasRecentlyCreated) {
            $this->candidatsCreated++;
        } else {
            $this->candidatsUpdated++;
        }
    }

    private function matchCivicdashListe(ListeElectorale $officielle): void
    {
        $listesCD = ListeElectorale::where('commune_code_insee', $officielle->commune_code_insee)
            ->where('source', 'civicdash')
            ->where('statut', 'valide')
            ->get();

        foreach ($listesCD as $listeCD) {
            $similarity = 0;
            similar_text(
                mb_strtolower($officielle->nom_liste),
                mb_strtolower($listeCD->nom_liste),
                $similarity
            );

            if ($similarity >= 80) {
                $officielle->update(['liste_civicdash_id' => $listeCD->id]);
                $this->matched++;
                return;
            }
        }
    }

    private function loadCsv(int $tour): ?string
    {
        if ($file = $this->option('file')) {
            if (!file_exists($file)) {
                $this->error("Fichier introuvable : {$file}");
                return null;
            }
            $this->info("Lecture du fichier local : {$file}");
            $content = file_get_contents($file);
        } else {
            $url = $this->option('url') ?? ($tour === 1 ? self::DATAGOUV_T1 : self::DATAGOUV_T2);
            $this->info("Téléchargement depuis : {$url}");

            try {
                $response = Http::timeout(300)->get($url);
                if (!$response->successful()) {
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
            $this->info("Encoding converti de {$encoding} vers UTF-8");
        }

        return $content;
    }

    private function parseCsvLines(string $content): array
    {
        $lines = explode("\n", $content);
        array_shift($lines);
        return array_filter($lines, fn($l) => trim($l) !== '');
    }

    private function displaySummary(bool $dryRun): void
    {
        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Import des candidatures terminé !");
        $this->newLine();
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Listes créées', $this->listesCreated],
                ['Listes mises à jour', $this->listesUpdated],
                ['Candidats créés (têtes de liste)', $this->candidatsCreated],
                ['Candidats mis à jour', $this->candidatsUpdated],
                ['Matchs CivicDash', $this->matched],
                ['Erreurs', $this->errors],
            ]
        );
    }
}
