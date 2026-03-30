<?php

namespace App\Console\Commands;

use App\Models\Maire;
use App\Models\MaireMandat;
use App\Models\Ville;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportHistoriqueMandatsMaires extends Command
{
    protected $signature = 'maires:import-historique
                            {--url= : URL d\'un fichier CSV RNE maires (format RNE standard, séparateur ;)}
                            {--file= : Chemin vers un fichier CSV local}
                            {--mandature= : Mandature à attribuer (ex: 2014-2020)}
                            {--date-fin= : Date de fin du mandat (ex: 2020-05-18)}
                            {--dry-run : Simuler sans écrire en base}
                            {--limit= : Limiter le nombre de lignes}';

    protected $description = 'Importe l\'historique des mandats de maires depuis un snapshot CSV du RNE';

    private int $created = 0;

    private int $linked = 0;

    private int $skipped = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $url = $this->option('url');
        $file = $this->option('file');
        $mandature = $this->option('mandature');
        $dryRun = $this->option('dry-run');
        $dateFin = $this->option('date-fin');

        if (! $url && ! $file) {
            $this->error('Vous devez fournir --url ou --file');
            $this->line('');
            $this->line('Exemples d\'utilisation :');
            $this->line('  # Importer les maires sortants 2026 (mandature 2020-2026)');
            $this->line('  php artisan maires:import-historique --url="https://static.data.gouv.fr/resources/..." --mandature=2020-2026');
            $this->line('');
            $this->line('  # Importer un ancien snapshot RNE (mandature 2014-2020)');
            $this->line('  php artisan maires:import-historique --file=public/data/rne-maires-2020.csv --mandature=2014-2020 --date-fin=2020-05-18');

            return self::FAILURE;
        }

        if (! $mandature) {
            $this->error('Vous devez préciser la --mandature (ex: 2014-2020)');

            return self::FAILURE;
        }

        $this->info("Import historique mandats maires - mandature {$mandature}");
        if ($dryRun) {
            $this->warn('Mode DRY RUN');
        }

        $lines = $this->loadCsvLines($url, $file);
        if ($lines === null) {
            return self::FAILURE;
        }

        $header = str_getcsv(array_shift($lines), ';');
        $lines = array_filter($lines, fn ($l) => trim($l) !== '');

        $limit = $this->option('limit');
        if ($limit) {
            $lines = array_slice($lines, 0, (int) $limit);
            $this->warn("Limité à {$limit} lignes");
        }

        $this->info(count($lines).' lignes à traiter');

        $bar = $this->output->createProgressBar(count($lines));
        $bar->setFormat('verbose');
        $bar->start();

        $dateFinParsed = $dateFin ? Carbon::parse($dateFin) : null;

        foreach ($lines as $line) {
            try {
                $data = str_getcsv($line, ';');
                if (count($data) < 13) {
                    $this->skipped++;
                    $bar->advance();

                    continue;
                }
                if (! $dryRun) {
                    $this->processLine($data, $mandature, $dateFinParsed);
                } else {
                    $this->simulateLine($data, $mandature);
                }
            } catch (\Exception $e) {
                $this->errors++;
                if ($this->output->isVerbose()) {
                    $this->newLine();
                    $this->error($e->getMessage());
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->displaySummary();

        return self::SUCCESS;
    }

    private function processLine(array $data, string $mandature, ?Carbon $dateFin): void
    {
        $codeCommune = trim($data[4] ?? '');
        $nom = trim($data[6] ?? '');
        $prenom = trim($data[7] ?? '');
        $sexe = trim($data[8] ?? '');
        $dateDebutMandat = $this->parseDate(trim($data[12] ?? ''));

        if (! $codeCommune || ! $nom || ! $prenom) {
            $this->skipped++;

            return;
        }

        $nomNorm = mb_convert_case($nom, MB_CASE_TITLE, 'UTF-8');
        $prenomNorm = mb_convert_case($prenom, MB_CASE_TITLE, 'UTF-8');

        $existing = MaireMandat::whereHas('ville', fn ($q) => $q->where('code_insee', $codeCommune))
            ->where('mandature', $mandature)
            ->where(DB::raw('LOWER(nom)'), mb_strtolower($nom))
            ->where(DB::raw('LOWER(prenom)'), mb_strtolower($prenom))
            ->first();

        if ($existing) {
            $this->skipped++;

            return;
        }

        $ville = Ville::where('code_insee', $codeCommune)->first();

        $maire = Maire::where('code_commune', $codeCommune)
            ->where(function ($q) use ($nom, $prenom) {
                $q->where('nom', 'ILIKE', $nom)
                    ->where('prenom', 'ILIKE', $prenom);
            })
            ->first();

        $maireId = $maire?->id;

        if ($maire) {
            $this->linked++;
        }

        $anneeElection = null;
        if ($dateDebutMandat) {
            $anneeElection = (int) Carbon::parse($dateDebutMandat)->format('Y');
        }

        MaireMandat::create([
            'ville_id' => $ville?->id,
            'maire_id' => $maireId,
            'nom' => $nomNorm,
            'prenom' => $prenomNorm,
            'sexe' => $sexe === 'F' ? 'F' : 'M',
            'date_debut' => $dateDebutMandat,
            'date_fin' => $dateFin,
            'annee_election' => $anneeElection,
            'mandature' => $mandature,
            'est_actuel' => false,
        ]);

        $this->created++;
    }

    private function simulateLine(array $data, string $mandature): void
    {
        $codeCommune = trim($data[4] ?? '');
        $nom = trim($data[6] ?? '');

        if (! $codeCommune || ! $nom) {
            $this->skipped++;

            return;
        }

        $this->created++;
    }

    private function loadCsvLines(?string $url, ?string $file): ?array
    {
        if ($file) {
            $path = base_path($file);
            if (! file_exists($path)) {
                $this->error("Fichier introuvable : {$path}");

                return null;
            }
            $this->info("Lecture de {$path}");

            return explode("\n", file_get_contents($path));
        }

        $this->info("Téléchargement de {$url}");
        try {
            $response = Http::timeout(300)->get($url);
            if (! $response->successful()) {
                $this->error("Erreur HTTP : {$response->status()}");

                return null;
            }

            return explode("\n", $response->body());
        } catch (\Exception $e) {
            $this->error("Erreur : {$e->getMessage()}");

            return null;
        }
    }

    private function parseDate(?string $str): ?string
    {
        if (! $str) {
            return null;
        }
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{2})$/', $str, $m)) {
            $year = (int) $m[3] < 50 ? '20'.$m[3] : '19'.$m[3];

            return "{$year}-{$m[2]}-{$m[1]}";
        }
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $str, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    private function displaySummary(): void
    {
        $this->info('Import terminé !');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Mandats créés', $this->created],
                ['Liés à un maire existant', $this->linked],
                ['Ignorés (déjà existants)', $this->skipped],
                ['Erreurs', $this->errors],
            ]
        );
    }
}
