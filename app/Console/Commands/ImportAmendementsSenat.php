<?php

namespace App\Console\Commands;

use App\Models\AmendementSenat;
use App\Models\Senateur;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportAmendementsSenat extends Command
{
    protected $signature = 'import:amendements-senat 
                            {--legislature=2024 : Législature à importer (ex: 2024)} 
                            {--fresh : Vider la table avant import}
                            {--limit= : Limite du nombre d\'amendements (pour tests)}';

    protected $description = 'Importe les amendements du Sénat depuis data.senat.fr';

    private int $imported = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private int $errors = 0;

    /**
     * API data.senat.fr - Amendements
     * Source : API JSON REST endpoint
     */
    public function handle(): int
    {
        $legislature = (int) $this->option('legislature');
        $fresh = $this->option('fresh');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->info("🏛️  Import des amendements Sénat...");
        $this->info("📊 Législature cible : {$legislature}");

        if ($fresh) {
            $this->warn("⚠️  Mode --fresh : suppression des amendements existants...");
            AmendementSenat::where('legislature', $legislature)->delete();
        }

        if ($limit) {
            $this->warn("⚠️  Mode TEST : {$limit} amendements maximum");
        }

        // Note: Les amendements du Sénat ne sont pas directement disponibles en masse via OpenData
        // Il faut les récupérer via l'API REST individuellement ou via scraping
        
        $this->error("❌ Les amendements du Sénat ne sont pas disponibles en masse via data.senat.fr");
        $this->error("   L'API ne fournit pas de liste complète des amendements.");
        $this->newLine();
        $this->warn("💡 Alternatives :");
        $this->warn("   1. Utiliser NosSenateurs.fr (mais service deprecated)");
        $this->warn("   2. Scraper depuis senat.fr (pages HTML)");
        $this->warn("   3. Demander l'accès à une API privée");
        $this->newLine();
        $this->info("📊 Pour l'instant, seules les données suivantes sont disponibles pour le Sénat :");
        $this->info("   ✅ Profils sénateurs");
        $this->info("   ✅ Mandats et groupes");
        $this->info("   ✅ Commissions");
        $this->info("   ✅ Mandats locaux");
        $this->info("   ✅ Formations/Études");
        $this->info("   ✅ Dossiers législatifs");
        $this->info("   ❌ Scrutins (non publics)");
        $this->info("   ❌ Votes individuels (non publics)");
        $this->info("   ❌ Amendements (non accessibles en masse)");
        $this->info("   ⚠️  Questions au Gouvernement (voir import:questions-senat)");

        return Command::FAILURE;
    }
            $lines = explode("\n", $csvContent);
            $headers = null;
            $amendements = [];

            foreach ($lines as $index => $line) {
                if ($index === 0) {
                    // Header
                    $headers = str_getcsv($line, ';');
                    continue;
                }

                if (empty(trim($line))) {
                    continue;
                }

                $data = str_getcsv($line, ';');
                
                if (count($data) < count($headers)) {
                    continue;
                }

                $row = array_combine($headers, $data);
                
                // Filtrer par législature (année)
                if (isset($row['Annee']) && (int) $row['Annee'] === $legislature) {
                    $amendements[] = $row;
                }

                if ($limit && count($amendements) >= $limit) {
                    break;
                }
            }

            $this->info("📊 " . count($amendements) . " amendements trouvés pour {$legislature}");

            // Import avec barre de progression
            $progressBar = $this->output->createProgressBar(count($amendements));
            $progressBar->start();

            foreach ($amendements as $amendementData) {
                try {
                    $this->importAmendement($amendementData, $legislature);
                } catch (\Exception $e) {
                    $this->errors++;
                    if ($this->errors <= 5) {
                        $this->newLine();
                        $this->error("❌ Erreur : " . $e->getMessage());
                    }
                }
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors du téléchargement : " . $e->getMessage());
            return Command::FAILURE;
        }

        $this->displaySummary($legislature);

        return Command::SUCCESS;
    }

    private function importAmendement(array $data, int $legislature): void
    {
        // Mapping des colonnes CSV vers la BDD
        $uid = $data['Cle'] ?? null;
        
        if (!$uid) {
            $this->skipped++;
            return;
        }

        // Récupérer le sénateur auteur (par matricule)
        $senateurMatricule = $data['Auteur_matricule'] ?? null;

        // Mapper le sort
        $sortCode = $this->mapSortCode($data['Sort'] ?? null);

        $amendementModel = AmendementSenat::updateOrCreate(
            ['uid' => $uid],
            [
                'texte_ref' => $data['Texte_numero'] ?? null,
                'auteur_senateur_matricule' => $senateurMatricule,
                'legislature' => $legislature,
                'numero' => $data['Numero'] ?? null,
                'numero_long' => $data['Numero_long'] ?? null,
                'subdiv_type' => $data['Subdivision_type'] ?? null,
                'subdiv_titre' => $data['Subdivision_titre'] ?? null,
                'auteur_type' => $data['Auteur_type'] ?? null,
                'auteur_nom' => $data['Auteur_nom'] ?? null,
                'auteur_groupe_sigle' => $data['Auteur_groupe'] ?? null,
                'cosignataires' => isset($data['Cosignataires']) ? json_decode($data['Cosignataires'], true) : null,
                'nombre_cosignataires' => isset($data['Nombre_cosignataires']) ? (int) $data['Nombre_cosignataires'] : 0,
                'dispositif' => $data['Dispositif'] ?? null,
                'expose' => $data['Expose'] ?? null,
                'sort_code' => $sortCode,
                'sort_libelle' => $data['Sort'] ?? null,
                'date_depot' => $this->parseDate($data['Date_depot'] ?? null),
                'date_sort' => $this->parseDate($data['Date_sort'] ?? null),
                'url_senat' => isset($data['Cle']) ? "https://data.senat.fr/data/ameli/{$data['Cle']}.json" : null,
            ]
        );

        if ($amendementModel->wasRecentlyCreated) {
            $this->imported++;
        } else {
            $this->updated++;
        }
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date || empty(trim($date))) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function mapSortCode(?string $sort): ?string
    {
        if (!$sort) {
            return null;
        }

        $mapping = [
            'Adopté' => 'ADOPTE',
            'Rejeté' => 'REJETE',
            'Retiré' => 'RETIRE',
            'Tombé' => 'TOMBE',
            'Irrecevable' => 'IRRECEVABLE',
            'Non soutenu' => 'NON_SOUTENU',
        ];

        return $mapping[$sort] ?? strtoupper(str_replace(' ', '_', $sort));
    }

    private function displaySummary(int $legislature): void
    {
        $this->info('✅ Import terminé !');
        $this->newLine();
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✓ Nouveaux amendements', $this->imported],
                ['↻ Amendements mis à jour', $this->updated],
                ['⊘ Amendements skippés', $this->skipped],
                ['⚠ Erreurs', $this->errors],
            ]
        );

        $total = AmendementSenat::where('legislature', $legislature)->count();
        $adoptes = AmendementSenat::where('legislature', $legislature)->adoptes()->count();
        $rejetes = AmendementSenat::where('legislature', $legislature)->rejetes()->count();

        $this->info("📊 Total en base de données : {$total} amendements");
        $this->info("   - Adoptés : {$adoptes}");
        $this->info("   - Rejetés : {$rejetes}");
        $this->newLine();
        $this->info("📊 Législature {$legislature} : {$total} amendements");
        $this->info("   - Adoptés : {$adoptes}");
    }
}

