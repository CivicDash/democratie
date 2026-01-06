<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Import des budgets communaux depuis data.gouv.fr
 * Source : Comptes individuels des communes (DGFIP)
 * 
 * @see https://www.data.gouv.fr/fr/datasets/comptes-individuels-des-communes/
 */
class ImportCommuneBudgets extends Command
{
    protected $signature = 'import:commune-budgets 
                            {--annee=2022 : Année à importer}
                            {--limit=100 : Nombre max de communes (0 = toutes)}
                            {--departement= : Filtrer par département (ex: 75)}
                            {--demo : Utiliser les données de démonstration}
                            {--force : Écraser les données existantes}';
    
    protected $description = 'Importe les budgets des communes depuis data.gouv.fr (DGFIP/OFGL)';

    // URLs des fichiers CSV par année (comptes des communes)
    private array $sources = [
        2022 => 'https://data.economie.gouv.fr/api/explore/v2.1/catalog/datasets/comptes-individuels-des-communes-fichier-global-a-compter-de-2000/exports/csv?select=exer,ident,ndept,nomen,lbudg,pib,recfonc,depfonc,reciinv,depinv,encdet,anudet,caf,popn&where=exer%20%3D%202022&limit=-1&timezone=Europe%2FParis&use_labels=true&delimiter=%3B',
        2021 => 'https://data.economie.gouv.fr/api/explore/v2.1/catalog/datasets/comptes-individuels-des-communes-fichier-global-a-compter-de-2000/exports/csv?select=exer,ident,ndept,nomen,lbudg,pib,recfonc,depfonc,reciinv,depinv,encdet,anudet,caf,popn&where=exer%20%3D%202021&limit=-1&timezone=Europe%2FParis&use_labels=true&delimiter=%3B',
        2020 => 'https://data.economie.gouv.fr/api/explore/v2.1/catalog/datasets/comptes-individuels-des-communes-fichier-global-a-compter-de-2000/exports/csv?select=exer,ident,ndept,nomen,lbudg,pib,recfonc,depfonc,reciinv,depinv,encdet,anudet,caf,popn&where=exer%20%3D%202020&limit=-1&timezone=Europe%2FParis&use_labels=true&delimiter=%3B',
    ];

    private int $imported = 0;
    private int $updated = 0;
    private int $skipped = 0;

    public function handle(): int
    {
        $annee = (int) $this->option('annee');
        $limit = (int) $this->option('limit');
        $departement = $this->option('departement');
        $force = $this->option('force');

        $this->info("📊 Import des budgets communaux - Année {$annee}");

        // Utiliser des données de démonstration pour le PoC
        if ($this->option('demo')) {
            return $this->importDemoData($annee, $force);
        }

        // Vérifier si l'année est disponible
        $url = $this->buildUrl($annee, $departement);

        $this->info("📥 Téléchargement des données depuis l'API DGFIP...");
        $this->line("   URL: " . substr($url, 0, 80) . "...");

        try {
            $response = Http::timeout(120)
                ->withHeaders(['Accept' => 'text/csv'])
                ->get($url);

            if (!$response->successful()) {
                $this->warn("⚠️ API DGFIP non disponible (HTTP {$response->status()})");
                $this->info("💡 Utilisation des données de démonstration...");
                return $this->importDemoData($annee, $force);
            }

            $csvContent = $response->body();
            $this->info("   ✓ Fichier téléchargé (" . number_format(strlen($csvContent) / 1024, 1) . " Ko)");

        } catch (\Exception $e) {
            $this->warn("⚠️ Erreur de téléchargement: " . $e->getMessage());
            $this->info("💡 Utilisation des données de démonstration...");
            return $this->importDemoData($annee, $force);
        }

        // Parser le CSV
        $this->info("📄 Parsing du fichier CSV...");
        $lines = explode("\n", $csvContent);
        $headers = null;
        $data = [];

        foreach ($lines as $i => $line) {
            if (empty(trim($line))) continue;
            
            $row = str_getcsv($line, ';');
            
            if ($headers === null) {
                $headers = array_map('strtolower', array_map('trim', $row));
                $this->line("   Colonnes: " . implode(', ', array_slice($headers, 0, 8)) . "...");
                continue;
            }

            if (count($row) !== count($headers)) continue;

            $record = array_combine($headers, $row);
            $data[] = $record;

            if ($limit > 0 && count($data) >= $limit) break;
        }

        $this->info("   ✓ " . count($data) . " enregistrements trouvés");

        if (empty($data)) {
            $this->warn("⚠️ Aucune donnée à importer");
            return Command::SUCCESS;
        }

        // Supprimer les anciennes données si --force
        if ($force) {
            $deleted = DB::table('commune_budgets')->where('annee', $annee)->delete();
            $this->warn("   🗑️ {$deleted} anciennes lignes supprimées pour {$annee}");
        }

        // Importer les données
        $this->info("💾 Import en base de données...");
        $bar = $this->output->createProgressBar(count($data));
        $bar->start();

        foreach ($data as $record) {
            $this->importRecord($record, $annee, $force);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Résumé
        $this->info("✅ Import terminé !");
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Importés', $this->imported],
                ['Mis à jour', $this->updated],
                ['Ignorés', $this->skipped],
                ['Total traité', count($data)],
            ]
        );

        return Command::SUCCESS;
    }

    private function buildUrl(int $annee, ?string $departement = null): string
    {
        $where = "exer = {$annee}";
        if ($departement) {
            $where .= " AND ndept = '{$departement}'";
        }

        return 'https://data.economie.gouv.fr/api/explore/v2.1/catalog/datasets/comptes-individuels-des-communes-fichier-global-a-compter-de-2000/exports/csv?' . http_build_query([
            'select' => 'exer,ident,ndept,nomen,lbudg,pib,recfonc,depfonc,reciinv,depinv,encdet,anudet,caf,popn',
            'where' => $where,
            'limit' => -1,
            'timezone' => 'Europe/Paris',
            'use_labels' => 'true',
            'delimiter' => ';',
        ]);
    }

    private function importRecord(array $record, int $annee, bool $force): void
    {
        // Mapper les colonnes DGFIP vers notre schéma
        // DGFIP: exer, ident, ndept, nomen, lbudg, recfonc, depfonc, reciinv, depinv, encdet, anudet, caf, popn
        $inseeCode = $this->extractInseeCode($record);
        
        if (!$inseeCode) {
            $this->skipped++;
            return;
        }

        // Vérifier si la commune existe dans notre base
        $villeExists = DB::table('villes')->where('code_insee', $inseeCode)->exists();
        
        if (!$villeExists) {
            // Essayer avec le format court (sans le 0 initial pour certains départements)
            $this->skipped++;
            return;
        }

        $data = [
            'insee_code' => $inseeCode,
            'annee' => $annee,
            'recettes_fonctionnement' => $this->parseAmount($record['recfonc'] ?? $record['recettes de fonctionnement'] ?? null),
            'depenses_fonctionnement' => $this->parseAmount($record['depfonc'] ?? $record['dépenses de fonctionnement'] ?? null),
            'recettes_investissement' => $this->parseAmount($record['reciinv'] ?? $record["recettes d'investissement"] ?? null),
            'depenses_investissement' => $this->parseAmount($record['depinv'] ?? $record["dépenses d'investissement"] ?? null),
            'encours_dette' => $this->parseAmount($record['encdet'] ?? $record['encours de la dette au 31/12'] ?? null),
            'annuite_dette' => $this->parseAmount($record['anudet'] ?? $record['annuité de la dette'] ?? null),
            'capacite_autofinancement' => $this->parseAmount($record['caf'] ?? $record["capacité d'autofinancement"] ?? null),
            'population' => $this->parsePopulation($record['popn'] ?? $record['population'] ?? null),
            'source' => 'DGFIP/OFGL',
            'updated_at' => now(),
        ];

        // Calculer euros par habitant
        if ($data['population'] > 0 && $data['encours_dette']) {
            $data['euros_par_habitant'] = round($data['encours_dette'] / $data['population'], 2);
        }

        // Calculer épargne brute (recettes fonct - dépenses fonct)
        if ($data['recettes_fonctionnement'] && $data['depenses_fonctionnement']) {
            $data['epargne_brute'] = $data['recettes_fonctionnement'] - $data['depenses_fonctionnement'];
        }

        // Insérer ou mettre à jour
        $existing = DB::table('commune_budgets')
            ->where('insee_code', $inseeCode)
            ->where('annee', $annee)
            ->first();

        if ($existing) {
            if ($force) {
                DB::table('commune_budgets')
                    ->where('id', $existing->id)
                    ->update($data);
                $this->updated++;
            } else {
                $this->skipped++;
            }
        } else {
            $data['created_at'] = now();
            DB::table('commune_budgets')->insert($data);
            $this->imported++;
        }
    }

    private function extractInseeCode(array $record): ?string
    {
        // Le code INSEE est dans "ident" au format SIREN ou code commune
        $ident = $record['ident'] ?? $record['siren/code commune'] ?? null;
        
        if (!$ident) return null;

        // Si c'est un SIREN (9 chiffres), les 5 premiers = code département + code commune
        // Sinon c'est directement le code INSEE
        $ident = trim($ident);
        
        if (strlen($ident) >= 5) {
            // Prendre les 5 premiers caractères comme code INSEE
            return substr($ident, 0, 5);
        }

        return null;
    }

    private function parseAmount(?string $value): ?float
    {
        if ($value === null || $value === '' || $value === 'NC') return null;
        
        // Nettoyer le montant (retirer espaces, remplacer virgule par point)
        $value = str_replace([' ', ',', '€'], ['', '.', ''], trim($value));
        
        if (!is_numeric($value)) return null;
        
        // Les montants DGFIP sont en milliers d'euros
        return (float) $value * 1000;
    }

    private function parsePopulation(?string $value): ?int
    {
        if ($value === null || $value === '' || $value === 'NC') return null;
        
        $value = str_replace([' ', ','], ['', ''], trim($value));
        
        if (!is_numeric($value)) return null;
        
        return (int) $value;
    }
}
