<?php

namespace App\Console\Commands;

use App\Models\DeputeSenateur;
use App\Models\GroupeParlementaire;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnrichSenateursFromApi extends Command
{
    protected $signature = 'enrich:senateurs {--limit= : Limiter le nombre de sénateurs à enrichir (pour test)} {--force : Forcer la mise à jour même si déjà enrichi}';
    protected $description = 'Enrichit les données des sénateurs via l\'API NosSénateurs.fr (groupes, photos, stats)';

    private const API_BASE_URL = 'https://www.nossenateurs.fr';
    private const API_SYNTHESE = 'https://www.nossenateurs.fr/synthese/json';
    private int $successCount = 0;
    private int $errorCount = 0;
    private int $skippedCount = 0;

    public function handle()
    {
        $this->info('🏛️  Enrichissement des sénateurs via NosSénateurs.fr...');
        $this->newLine();

        $limit = $this->option('limit');
        $force = $this->option('force');

        if ($limit) {
            $this->warn("⚠️  Mode TEST : Enrichissement limité à {$limit} sénateurs");
        }

        try {
            // 1. Récupérer tous les sénateurs depuis l'API
            $this->info('📥 Récupération de la liste des sénateurs depuis l\'API...');
            $apiSenateurs = $this->fetchSenateursFromApi();
            
            if (empty($apiSenateurs)) {
                $this->error('❌ Impossible de récupérer les données de l\'API');
                return Command::FAILURE;
            }

            $this->info("✅ {$apiSenateurs->count()} sénateurs récupérés de l'API");
            $this->newLine();

            // 2. Récupérer nos sénateurs en base
            $query = DeputeSenateur::where('source', 'senat');
            
            if (!$force) {
                $query->where(function($q) {
                    $q->whereNull('groupe_politique')
                      ->orWhereNull('photo_url')
                      ->orWhere('nb_propositions', 0);
                });
            }

            if ($limit) {
                $query->limit($limit);
            }

            $senateurs = $query->get();
            
            if ($senateurs->isEmpty()) {
                $this->warn('⚠️  Aucun sénateur à enrichir');
                return Command::SUCCESS;
            }

            $this->info("📊 {$senateurs->count()} sénateurs à enrichir");
            $bar = $this->output->createProgressBar($senateurs->count());
            $bar->setFormat('verbose');

            // 3. Enrichir chaque sénateur
            foreach ($senateurs as $senateur) {
                $this->enrichSenateur($senateur, $apiSenateurs);
                $bar->advance();
                
                // Pause pour ne pas surcharger l'API
                usleep(100000); // 0.1 seconde
            }

            $bar->finish();
            $this->newLine(2);

            // 4. Résumé
            $this->displaySummary();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de l\'enrichissement: ' . $e->getMessage());
            Log::error('EnrichSenateurs error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return Command::FAILURE;
        }
    }

    /**
     * Récupérer tous les sénateurs depuis l'API NosSénateurs.fr
     */
    private function fetchSenateursFromApi()
    {
        try {
            $response = Http::timeout(30)->get(self::API_SYNTHESE);
            
            if (!$response->successful()) {
                return collect([]);
            }

            $data = $response->json();
            
            // L'API retourne un objet avec les sénateurs comme clés
            return collect($data['senateurs'] ?? []);

        } catch (\Exception $e) {
            $this->warn("⚠️  Erreur API: " . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Enrichir un sénateur avec les données de l'API
     */
    private function enrichSenateur(DeputeSenateur $senateur, $apiSenateurs)
    {
        try {
            // Chercher le sénateur dans les données API par nom/prénom
            $apiSenateur = $this->findSenateurInApi($senateur, $apiSenateurs);

            if (!$apiSenateur) {
                $this->skippedCount++;
                return;
            }

            // Mettre à jour les données
            $updated = false;

            // Groupe politique
            if (!empty($apiSenateur['groupe_sigle'])) {
                $senateur->groupe_sigle = $apiSenateur['groupe_sigle'];
                $senateur->groupe_politique = $apiSenateur['groupe'] ?? null;
                $updated = true;

                // Créer/mettre à jour le groupe parlementaire si nécessaire
                $this->upsertGroupe($apiSenateur);
            }

            // Photo
            if (!empty($apiSenateur['url_institution'])) {
                // Construire l'URL de la photo depuis l'ID
                if (preg_match('/\/(\d+)$/', $apiSenateur['url_institution'], $matches)) {
                    $senateurId = $matches[1];
                    $senateur->photo_url = "https://www.nossenateurs.fr/senateur/photo/{$senateurId}/200";
                    $updated = true;
                }
            }

            // URL profil
            if (!empty($apiSenateur['slug'])) {
                $senateur->url_profil = self::API_BASE_URL . '/' . $apiSenateur['slug'];
                $updated = true;
            }

            // Statistiques
            if (isset($apiSenateur['nb_propositions'])) {
                $senateur->nb_propositions = (int) $apiSenateur['nb_propositions'];
                $updated = true;
            }

            if (isset($apiSenateur['nb_amendements'])) {
                $senateur->nb_amendements = (int) $apiSenateur['nb_amendements'];
                $updated = true;
            }

            if (isset($apiSenateur['presences_commission'])) {
                // Calculer un taux de présence approximatif
                $presences = (float) str_replace('%', '', $apiSenateur['presences_commission']);
                $senateur->taux_presence = $presences;
                $updated = true;
            }

            // Fonctions (si disponibles)
            if (!empty($apiSenateur['responsabilites'])) {
                $fonctions = [];
                foreach ($apiSenateur['responsabilites'] as $resp) {
                    $fonctions[] = [
                        'libelle' => $resp['organisme'] ?? '',
                        'fonction' => $resp['fonction'] ?? '',
                    ];
                }
                $senateur->fonctions = $fonctions;
                $updated = true;
            }

            if ($updated) {
                $senateur->save();
                $this->successCount++;
            } else {
                $this->skippedCount++;
            }

        } catch (\Exception $e) {
            $this->errorCount++;
            Log::warning("Erreur enrichissement sénateur {$senateur->nom}", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Trouver un sénateur dans les données API par correspondance nom/prénom
     */
    private function findSenateurInApi(DeputeSenateur $senateur, $apiSenateurs)
    {
        $nom = $this->normalizeString($senateur->nom);
        $prenom = $this->normalizeString($senateur->prenom);

        foreach ($apiSenateurs as $slug => $apiSenateur) {
            $apiNom = $this->normalizeString($apiSenateur['nom'] ?? '');
            $apiPrenom = $this->normalizeString($apiSenateur['prenom'] ?? '');

            // Correspondance exacte ou partielle
            if ($this->namesMatch($nom, $prenom, $apiNom, $apiPrenom)) {
                return $apiSenateur;
            }
        }

        return null;
    }

    /**
     * Vérifier si deux noms correspondent
     */
    private function namesMatch(string $nom1, string $prenom1, string $nom2, string $prenom2): bool
    {
        // Correspondance exacte
        if ($nom1 === $nom2 && $prenom1 === $prenom2) {
            return true;
        }

        // Correspondance partielle (premier prénom uniquement)
        $prenom1Parts = explode(' ', $prenom1);
        $prenom2Parts = explode(' ', $prenom2);
        
        if ($nom1 === $nom2 && !empty($prenom1Parts[0]) && !empty($prenom2Parts[0])) {
            if ($prenom1Parts[0] === $prenom2Parts[0]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normaliser une chaîne pour la comparaison
     */
    private function normalizeString(string $str): string
    {
        $str = strtolower($str);
        $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
        $str = trim($str);
        return $str;
    }

    /**
     * Créer ou mettre à jour un groupe parlementaire
     */
    private function upsertGroupe(array $apiSenateur)
    {
        if (empty($apiSenateur['groupe_sigle'])) {
            return;
        }

        try {
            GroupeParlementaire::updateOrCreate(
                [
                    'source' => 'senat',
                    'sigle' => $apiSenateur['groupe_sigle'],
                ],
                [
                    'nom' => $apiSenateur['groupe'] ?? $apiSenateur['groupe_sigle'],
                    'slug' => \Illuminate\Support\Str::slug($apiSenateur['groupe'] ?? $apiSenateur['groupe_sigle']),
                    'legislature' => null, // Pas de législature pour le Sénat
                ]
            );
        } catch (\Exception $e) {
            // Ignorer les erreurs de groupe
        }
    }

    /**
     * Afficher le résumé
     */
    private function displaySummary()
    {
        $this->info("✅ Enrichissement terminé !");
        $this->newLine();
        
        $this->info("📊 Résumé :");
        $this->line("   ✓ {$this->successCount} sénateurs enrichis");
        $this->line("   ↻ {$this->skippedCount} sénateurs ignorés");
        
        if ($this->errorCount > 0) {
            $this->warn("   ⚠️  {$this->errorCount} erreurs");
        }

        $this->newLine();
        
        // Statistiques finales
        $stats = DeputeSenateur::where('source', 'senat')
            ->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN groupe_politique IS NOT NULL THEN 1 END) as avec_groupe,
                COUNT(CASE WHEN photo_url IS NOT NULL THEN 1 END) as avec_photo,
                AVG(nb_propositions) as avg_propositions,
                AVG(nb_amendements) as avg_amendements
            ')
            ->first();

        $this->info("📈 Statistiques globales :");
        $this->line("   Total sénateurs : {$stats->total}");
        $this->line("   Avec groupe : {$stats->avec_groupe}");
        $this->line("   Avec photo : {$stats->avec_photo}");
        $this->line("   Moy. propositions : " . round($stats->avg_propositions, 1));
        $this->line("   Moy. amendements : " . round($stats->avg_amendements, 1));
    }
}

