<?php

namespace App\Console\Commands;

use App\Models\DossierLegislatifAN;
use App\Models\DossierLegislatifSenat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportDossiersSenat extends Command
{
    protected $signature = 'import:dossiers-senat 
                            {--fresh : Supprimer les dossiers existants avant l\'import}
                            {--limit= : Limiter le nombre de dossiers (pour tests)}
                            {--match : Tenter de matcher avec les dossiers AN}';

    protected $description = 'Importe les dossiers législatifs du Sénat depuis data.senat.fr (CSV)';

    private const CSV_URL = 'https://data.senat.fr/data/dosleg/dossiers-legislatifs.csv';

    private const CSV_PATH = 'temp/dossiers-senat.csv';

    public function handle(): int
    {
        $this->info('📥 Import des dossiers législatifs Sénat...');

        if ($this->option('fresh')) {
            $this->warn('⚠️  Mode --fresh : suppression des dossiers existants...');
            DossierLegislatifSenat::truncate();
        }

        // 1. Télécharger le CSV
        $this->info('📥 Téléchargement du fichier CSV...');

        try {
            $response = Http::timeout(60)->get(self::CSV_URL);

            if (! $response->successful()) {
                $this->error("❌ Erreur HTTP {$response->status()}");

                return Command::FAILURE;
            }

            Storage::put(self::CSV_PATH, $response->body());
            $this->info('✅ Fichier téléchargé');

        } catch (\Exception $e) {
            $this->error('❌ Erreur de téléchargement : '.$e->getMessage());

            return Command::FAILURE;
        }

        // 2. Parser le CSV
        $csvPath = Storage::path(self::CSV_PATH);

        if (! file_exists($csvPath)) {
            $this->error('❌ Fichier CSV introuvable');

            return Command::FAILURE;
        }

        $handle = fopen($csvPath, 'r');

        if (! $handle) {
            $this->error('❌ Impossible d\'ouvrir le fichier CSV');

            return Command::FAILURE;
        }

        // Lire l'en-tête
        $header = fgetcsv($handle, 0, ';');

        if (! $header) {
            $this->error('❌ En-tête CSV invalide');
            fclose($handle);

            return Command::FAILURE;
        }

        $this->info('📊 Colonnes CSV : '.implode(', ', $header));

        $stats = [
            'total' => 0,
            'nouveaux' => 0,
            'mis_a_jour' => 0,
            'matches_an' => 0,
            'erreurs' => 0,
        ];

        $limit = $this->option('limit');
        $doMatch = $this->option('match');

        if ($limit) {
            $this->warn("⚠️  Mode TEST : {$limit} dossiers maximum");
        }

        $progressBar = $this->output->createProgressBar();
        $progressBar->start();

        // 3. Importer les lignes
        $lineNumber = 1; // Commence à 1 pour l'en-tête
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $lineNumber++;

            if ($limit && $stats['total'] >= $limit) {
                break;
            }

            // Ignorer les lignes vides ou mal formées
            if (empty($row) || count($row) < 2) {
                continue;
            }

            try {
                $data = array_combine($header, $row);

                // Si array_combine échoue (nombre de colonnes différent)
                if ($data === false) {
                    $stats['erreurs']++;

                    continue;
                }

                $this->importDossier($data, $doMatch, $stats);
            } catch (\Exception $e) {
                $stats['erreurs']++;
                // Seulement afficher les 5 premières erreurs
                if ($stats['erreurs'] <= 5) {
                    $this->newLine();
                    $this->error("❌ Erreur ligne {$lineNumber}: ".$e->getMessage());
                }
            }

            $stats['total']++;
            $progressBar->advance();
        }

        fclose($handle);
        $progressBar->finish();
        $this->newLine(2);

        // 4. Afficher les statistiques
        $this->info('✅ Import terminé !');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✓ Total traités', $stats['total']],
                ['✓ Nouveaux dossiers', $stats['nouveaux']],
                ['↻ Dossiers mis à jour', $stats['mis_a_jour']],
                ['🔗 Matchés avec AN', $stats['matches_an']],
                ['⚠ Erreurs', $stats['erreurs']],
            ]
        );

        $totalDB = DossierLegislatifSenat::count();
        $this->info("📊 Total en base de données : {$totalDB} dossiers Sénat");

        // Nettoyer le fichier temporaire
        Storage::delete(self::CSV_PATH);

        return Command::SUCCESS;
    }

    private function importDossier(array $data, bool $doMatch, array &$stats): void
    {
        // Mapping des colonnes CSV (à adapter selon la structure réelle)
        // Note : Les noms de colonnes peuvent varier, vérifier avec le CSV réel

        $numeroSenat = $data['Numéro'] ?? $data['numero'] ?? null;

        if (! $numeroSenat) {
            throw new \Exception('Numéro de dossier manquant');
        }

        $dossierData = [
            'numero_senat' => $numeroSenat,
            'numero_an' => $data['Numéro AN'] ?? $data['numero_an'] ?? null,
            'legislature' => $this->extractLegislature($numeroSenat),
            'type_dossier' => $data['Type'] ?? $data['type'] ?? null,
            'titre' => $data['Titre'] ?? $data['titre'] ?? 'Sans titre',
            'titre_court' => $data['Titre court'] ?? $data['titre_court'] ?? null,
            'date_depot' => $this->parseDate($data['Date de dépôt'] ?? $data['date_depot'] ?? null),
            'date_adoption_senat' => $this->parseDate($data['Date d\'adoption'] ?? $data['date_adoption'] ?? null),
            'date_promulgation' => $this->parseDate($data['Date de promulgation'] ?? $data['date_promulgation'] ?? null),
            'statut' => $this->detectStatut($data),
            'url_senat' => $data['URL Sénat'] ?? $data['url'] ?? null,
            'url_legifrance' => $data['URL Légifrance'] ?? $data['url_legifrance'] ?? null,
            'numero_loi' => $data['Numéro de loi'] ?? $data['numero_loi'] ?? null,
            'donnees_source' => $data,
        ];

        // Matching avec AN si demandé
        if ($doMatch && $dossierData['numero_an']) {
            $dossierAN = DossierLegislatifAN::where('uid', 'LIKE', "%{$dossierData['numero_an']}%")
                ->orWhere('titre', 'LIKE', '%'.Str::limit($dossierData['titre'], 50).'%')
                ->first();

            if ($dossierAN) {
                $dossierData['dossier_an_uid'] = $dossierAN->uid;
                $stats['matches_an']++;
            }
        }

        $dossier = DossierLegislatifSenat::updateOrCreate(
            ['numero_senat' => $numeroSenat],
            $dossierData
        );

        if ($dossier->wasRecentlyCreated) {
            $stats['nouveaux']++;
        } else {
            $stats['mis_a_jour']++;
        }
    }

    private function extractLegislature(string $numero): string
    {
        // Ex: "23-264" -> "2023"
        // Ex: "2023-2024-123" -> "2023"
        if (preg_match('/(\d{2,4})/', $numero, $matches)) {
            $year = $matches[1];

            return strlen($year) === 2 ? '20'.$year : $year;
        }

        return 'Inconnue';
    }

    private function detectStatut(array $data): string
    {
        if (! empty($data['Date de promulgation']) || ! empty($data['date_promulgation'])) {
            return 'Promulgué';
        }

        if (! empty($data['Date d\'adoption']) || ! empty($data['date_adoption'])) {
            return 'Adopté';
        }

        $statut = $data['Statut'] ?? $data['statut'] ?? 'En cours';

        return match (strtolower($statut)) {
            'promulgué', 'promulguée' => 'Promulgué',
            'adopté', 'adoptée' => 'Adopté',
            'rejeté', 'rejetée' => 'Rejeté',
            'en cours', 'en_cours' => 'En cours',
            default => 'En cours',
        };
    }

    private function parseDate(?string $date): ?string
    {
        if (! $date || $date === '0000-00-00') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
