<?php

namespace App\Console\Commands;

use App\Models\CommunePage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportEmailsMairies extends Command
{
    protected $signature = 'communes:import-emails-service-public
                            {--limit= : Limite le nombre de communes}
                            {--departement= : Filtre par code departement}
                            {--dry-run : Affiche sans modifier}';

    protected $description = 'Importe les emails et coordonnees des mairies via l\'API Annuaire service-public.fr';

    private const API_BASE = 'https://api-lannuaire.service-public.fr/api/explore/v2.1/catalog/datasets/api-lannuaire-administration/records';

    private const BATCH_SIZE = 100;

    public function handle(): int
    {
        $this->info('Import des coordonnees mairies depuis l\'API service-public.fr...');

        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');
        $departement = $this->option('departement');

        $offset = 0;
        $total = 0;
        $updated = 0;
        $created = 0;
        $errors = 0;

        do {
            $params = [
                'where' => "type_service_local = 'mairie'",
                'limit' => self::BATCH_SIZE,
                'offset' => $offset,
                'order_by' => 'code_insee_commune',
            ];

            if ($departement) {
                $params['where'] .= " AND code_insee_commune LIKE '{$departement}%'";
            }

            try {
                $response = Http::timeout(30)
                    ->retry(3, 1000)
                    ->get(self::API_BASE, $params);

                if (! $response->successful()) {
                    $this->error("Erreur API (HTTP {$response->status()}) a l'offset {$offset}");
                    $errors++;
                    $offset += self::BATCH_SIZE;

                    continue;
                }

                $data = $response->json();
                $records = $data['results'] ?? [];
                $totalCount = $data['total_count'] ?? 0;

                if (empty($records)) {
                    break;
                }

                foreach ($records as $record) {
                    $codeInsee = $record['code_insee_commune'] ?? null;
                    if (! $codeInsee) {
                        continue;
                    }

                    $total++;

                    $updates = $this->extractMairieData($record);

                    if (empty($updates)) {
                        continue;
                    }

                    if ($dryRun) {
                        $this->line("  [{$codeInsee}] ".($updates['email_mairie'] ?? 'pas d\'email'));
                        $updated++;

                        continue;
                    }

                    $page = CommunePage::where('code_insee', $codeInsee)->first();

                    if ($page) {
                        $page->update($updates);
                        $updated++;
                    } else {
                        $created++;
                    }
                }

                $offset += self::BATCH_SIZE;
                $this->output->write("\r  Traites: {$total} / ~{$totalCount}");

                if ($limit && $total >= (int) $limit) {
                    break;
                }

                usleep(200000);

            } catch (\Exception $e) {
                $this->error("Exception a l'offset {$offset}: {$e->getMessage()}");
                Log::error('ImportEmailsMairies error', ['offset' => $offset, 'error' => $e->getMessage()]);
                $errors++;
                $offset += self::BATCH_SIZE;
            }
        } while (! empty($records));

        $this->newLine(2);

        $this->table(
            ['Metrique', 'Valeur'],
            [
                ['Communes traitees', $total],
                ['Pages mises a jour', $updated],
                ['Nouvelles pages', $created],
                ['Erreurs', $errors],
                ['Mode', $dryRun ? 'Dry-run' : 'Applique'],
            ]
        );

        return self::SUCCESS;
    }

    private function extractMairieData(array $record): array
    {
        $data = [];

        $email = $record['adresse_courriel'] ?? null;
        if ($email) {
            $emails = is_array($email) ? $email : explode(';', $email);
            $data['email_mairie'] = trim($emails[0]);
        }

        $telephone = $record['telephone'] ?? null;
        if ($telephone) {
            $phones = is_array($telephone) ? $telephone : explode(';', $telephone);
            $data['telephone'] = trim($phones[0]);
        }

        $adresse = $this->buildAdresse($record);
        if ($adresse) {
            $data['adresse_mairie'] = $adresse;
        }

        $siteWeb = $record['url_service_public'] ?? $record['site_internet'] ?? null;
        if ($siteWeb) {
            $sites = is_array($siteWeb) ? $siteWeb : [$siteWeb];
            $data['site_officiel'] = trim($sites[0]);
        }

        $horaires = $record['plage_ouverture'] ?? null;
        if ($horaires) {
            $data['horaires_ouverture'] = $this->parseHoraires($horaires);
        }

        return $data;
    }

    private function buildAdresse(array $record): ?string
    {
        $parts = array_filter([
            $record['adresse_numero_voie'] ?? null,
            $record['adresse_complement1'] ?? null,
            $record['adresse_code_postal'] ?? null,
            $record['adresse_commune'] ?? null,
        ]);

        return ! empty($parts) ? implode(' ', $parts) : null;
    }

    private function parseHoraires($horaires): ?array
    {
        if (is_string($horaires)) {
            return ['brut' => $horaires];
        }

        if (is_array($horaires)) {
            return $horaires;
        }

        return null;
    }
}
