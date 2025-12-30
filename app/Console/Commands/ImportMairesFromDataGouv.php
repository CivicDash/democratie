<?php

namespace App\Console\Commands;

use App\Models\Maire;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportMairesFromDataGouv extends Command
{
    protected $signature = 'import:maires-datagouv 
                            {--fresh : Vider la table des maires avant l\'import}
                            {--update-only : Mettre à jour uniquement les maires existants}
                            {--limit= : Limiter le nombre d\'imports (pour test)}';

    protected $description = 'Importe/enrichit les maires depuis l\'API data.gouv.fr (données actualisées avec nuance, contact, etc.)';

    private const API_URL = 'https://www.data.gouv.fr/api/1/datasets/r/24a4233d-75a4-44f9-9c15-da09731bb509';

    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private int $errors = 0;

    public function handle(): int
    {
        $this->info('🏛️ Import des maires depuis data.gouv.fr...');
        $this->info('📡 URL: ' . self::API_URL);
        $this->newLine();

        if ($this->option('fresh')) {
            $this->warn('⚠️  Mode --fresh : suppression des données existantes...');
            Maire::truncate();
        }

        $updateOnly = $this->option('update-only');
        if ($updateOnly) {
            $this->info('📊 Mode --update-only : enrichissement des maires existants uniquement');
        }

        // Télécharger les données
        $this->info('📥 Téléchargement des données...');
        
        try {
            $response = Http::timeout(120)->get(self::API_URL);
            
            if (!$response->successful()) {
                $this->error("❌ Erreur HTTP: {$response->status()}");
                return self::FAILURE;
            }

            $data = $response->json();
            
            if (!is_array($data)) {
                $this->error('❌ Format de données invalide (attendu: tableau JSON)');
                return self::FAILURE;
            }

            $this->info("✅ " . count($data) . " maires reçus");

        } catch (\Exception $e) {
            $this->error("❌ Erreur de téléchargement: {$e->getMessage()}");
            return self::FAILURE;
        }

        $limit = $this->option('limit');
        $total = $limit ? min((int) $limit, count($data)) : count($data);
        
        if ($limit) {
            $this->warn("⚠️  Mode TEST : limité à {$limit} maires");
            $data = array_slice($data, 0, (int) $limit);
        }

        $this->newLine();
        $this->info("📊 Traitement de {$total} maires...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($data as $item) {
            try {
                $this->processItem($item, $updateOnly);
            } catch (\Exception $e) {
                $this->errors++;
                if ($this->output->isVerbose()) {
                    $this->error("Erreur: {$e->getMessage()}");
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->displaySummary();

        return self::SUCCESS;
    }

    private function processItem(array $item, bool $updateOnly): void
    {
        // Format des données JSON:
        // code_insee, nom_comm, comm_min, civilite, prenom_elu, nom_elu, 
        // nuance, adresse, code_postal, telephone, site_web, mandature, ept, statut
        // geo_point_2d (lon, lat), geo_shape

        $codeInsee = $item['code_insee'] ?? null;
        $nom = $item['nom_elu'] ?? null;
        $prenom = $item['prenom_elu'] ?? null;

        if (!$codeInsee || !$nom || !$prenom) {
            $this->skipped++;
            return;
        }

        // Département = 2 premiers caractères du code INSEE (sauf Corse et DOM)
        $deptCode = substr($codeInsee, 0, 2);
        if ($deptCode === '97' || $deptCode === '98') {
            $deptCode = substr($codeInsee, 0, 3); // DOM-TOM
        }

        // Chercher un maire existant
        $existingMaire = Maire::where('code_commune', $codeInsee)->first();

        if ($updateOnly && !$existingMaire) {
            $this->skipped++;
            return;
        }

        // Données à insérer/mettre à jour
        $maireData = [
            'uid' => 'MAIRE-' . $codeInsee,
            'nom' => mb_convert_case($nom, MB_CASE_TITLE, 'UTF-8'),
            'prenom' => mb_convert_case($prenom, MB_CASE_TITLE, 'UTF-8'),
            'civilite' => $this->normalizeCivilite($item['civilite'] ?? ''),
            'code_commune' => $codeInsee,
            'nom_commune' => $item['comm_min'] ?? $item['nom_comm'] ?? '',
            'code_departement' => $deptCode,
            'en_exercice' => true,
            // Données enrichies depuis data.gouv.fr
            'telephone' => $this->cleanPhone($item['telephone'] ?? null),
            'site_web' => $this->cleanUrl($item['site_web'] ?? null),
            'adresse_mairie' => $this->buildAdresse($item),
            'nuance_politique' => $item['nuance'] ?? null,
            'mandature' => $item['mandature'] ?? '2020-2026',
        ];

        // Ajouter coordonnées si disponibles
        if (isset($item['geo_point_2d']['lon']) && isset($item['geo_point_2d']['lat'])) {
            $maireData['longitude'] = $item['geo_point_2d']['lon'];
            $maireData['latitude'] = $item['geo_point_2d']['lat'];
        }

        if ($existingMaire) {
            // Mise à jour (enrichissement)
            $existingMaire->update($maireData);
            $this->updated++;
        } else {
            // Création
            Maire::create($maireData);
            $this->created++;
        }
    }

    private function normalizeCivilite(?string $civilite): ?string
    {
        if (!$civilite) return null;
        
        $civilite = trim($civilite);
        
        if (in_array(strtolower($civilite), ['m.', 'm', 'mr', 'monsieur'])) {
            return 'M.';
        }
        if (in_array(strtolower($civilite), ['mme', 'mme.', 'madame'])) {
            return 'Mme';
        }
        
        return $civilite;
    }

    private function cleanPhone(?string $phone): ?string
    {
        if (!$phone) return null;
        
        // Nettoyer et formater le téléphone
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (strlen($phone) === 10 && $phone[0] === '0') {
            // Format français: 01 23 45 67 89
            return preg_replace('/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1 $2 $3 $4 $5', $phone);
        }
        
        return $phone;
    }

    private function cleanUrl(?string $url): ?string
    {
        if (!$url) return null;
        
        $url = trim($url);
        
        // Ajouter http:// si nécessaire
        if ($url && !preg_match('/^https?:\/\//', $url)) {
            $url = 'http://' . $url;
        }
        
        return $url;
    }

    private function buildAdresse(array $item): ?string
    {
        $parts = [];
        
        if (!empty($item['adresse'])) {
            $parts[] = $item['adresse'];
        }
        
        if (!empty($item['code_postal'])) {
            $commune = $item['comm_min'] ?? $item['nom_comm'] ?? '';
            $parts[] = $item['code_postal'] . ' ' . $commune;
        }
        
        return !empty($parts) ? implode(', ', $parts) : null;
    }

    private function displaySummary(): void
    {
        $this->info('✅ Import terminé !');
        $this->newLine();
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✓ Créés', $this->created],
                ['↻ Mis à jour', $this->updated],
                ['⊘ Ignorés', $this->skipped],
                ['⚠ Erreurs', $this->errors],
            ]
        );

        // Stats finales
        $total = Maire::count();
        $enExercice = Maire::enExercice()->count();
        
        $this->newLine();
        $this->info("📊 Total maires en base : {$total}");
        $this->info("📊 Maires en exercice : {$enExercice}");

        // Top nuances politiques
        $topNuances = Maire::whereNotNull('nuance_politique')
            ->selectRaw('nuance_politique, COUNT(*) as total')
            ->groupBy('nuance_politique')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        if ($topNuances->isNotEmpty()) {
            $this->newLine();
            $this->info('📊 Top 10 nuances politiques :');
            foreach ($topNuances as $n) {
                $libelle = $this->getNuanceLibelle($n->nuance_politique);
                $this->line("   - {$n->nuance_politique} ({$libelle}) : {$n->total}");
            }
        }
    }

    private function getNuanceLibelle(string $code): string
    {
        return match($code) {
            'LDVG' => 'Divers gauche',
            'LDVD' => 'Divers droite',
            'LDVC' => 'Divers centre',
            'LSOC' => 'Socialiste',
            'LLR' => 'Les Républicains',
            'LREM' => 'Renaissance',
            'LREC' => 'Rassemblement National',
            'LECO' => 'Écologiste',
            'LCOM' => 'Communiste',
            'LUDI' => 'UDI',
            'LDIV' => 'Divers',
            'LEXG' => 'Extrême gauche',
            'LEXT' => 'Extrême droite',
            'LMDM' => 'Modem',
            default => $code,
        };
    }
}
