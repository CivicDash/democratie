<?php

namespace App\Console\Commands;

use App\Models\Senateur;
use App\Models\SenateurEtude;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportSenateursEtudes extends Command
{
    protected $signature = 'import:senateurs-etudes 
                            {--fresh : Supprimer les études existantes avant l\'import}
                            {--limit= : Limiter le nombre d\'entrées à traiter (pour tests)}';

    protected $description = 'Importe les formations/études des sénateurs depuis l\'API du Sénat';

    private const API_URL = 'https://data.senat.fr/data/senateurs/ODSEN_ETUDES.json';

    public function handle(): int
    {
        $this->info('🎓 Import des formations des sénateurs...');
        
        if ($this->option('fresh')) {
            $this->warn('⚠️  Mode --fresh : suppression des études existantes...');
            SenateurEtude::truncate();
        }

        try {
            $response = Http::timeout(30)->get(self::API_URL);
            
            if (!$response->successful()) {
                $this->error("❌ Erreur HTTP {$response->status()}");
                return Command::FAILURE;
            }

            $data = $response->json();
            
            if (empty($data['Senateurs'])) {
                $this->error("❌ Aucune donnée d'études disponible");
                return Command::FAILURE;
            }

            $etudesData = $data['Senateurs'];
            $limit = $this->option('limit');
            
            if ($limit) {
                $etudesData = array_slice($etudesData, 0, (int) $limit);
                $this->warn("⚠️  Mode TEST : {$limit} entrées maximum");
            }

            $stats = [
                'total' => 0,
                'nouveaux' => 0,
                'mis_a_jour' => 0,
                'skip' => 0,
                'erreurs' => 0,
            ];

            $progressBar = $this->output->createProgressBar(count($etudesData));
            $progressBar->start();

            foreach ($etudesData as $etudeData) {
                try {
                    $this->importEtude($etudeData, $stats);
                } catch (\Exception $e) {
                    $stats['erreurs']++;
                    $this->newLine();
                    $this->error("❌ Erreur : " . $e->getMessage());
                }
                
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Affichage des statistiques
            $this->info('✅ Import terminé !');
            $this->table(
                ['Métrique', 'Valeur'],
                [
                    ['✓ Total traités', $stats['total']],
                    ['✓ Nouvelles études', $stats['nouveaux']],
                    ['↻ Études mises à jour', $stats['mis_a_jour']],
                    ['⊘ Entrées skippées', $stats['skip']],
                    ['⚠ Erreurs', $stats['erreurs']],
                ]
            );

            $totalDB = SenateurEtude::count();
            $this->info("📊 Total en base de données : {$totalDB} formations");

            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur générale : " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function importEtude(array $data, array &$stats): void
    {
        $matricule = $data['Matricule'] ?? null;
        
        if (!$matricule) {
            $stats['skip']++;
            return;
        }

        // Vérifier que le sénateur existe
        if (!Senateur::where('matricule', $matricule)->exists()) {
            $stats['skip']++;
            return;
        }

        $stats['total']++;

        $etude = SenateurEtude::updateOrCreate(
            [
                'senateur_matricule' => $matricule,
                'etablissement' => $data['Etablissement'] ?? null,
                'diplome' => $data['Diplome'] ?? null,
            ],
            [
                'niveau' => $this->detectNiveau($data['Diplome'] ?? ''),
                'domaine' => $data['Domaine'] ?? null,
                'annee' => $this->parseAnnee($data['Annee'] ?? null),
                'details' => $data,
            ]
        );

        if ($etude->wasRecentlyCreated) {
            $stats['nouveaux']++;
        } else {
            $stats['mis_a_jour']++;
        }
    }

    private function detectNiveau(string $diplome): ?string
    {
        $diplome = strtolower($diplome);
        
        if (str_contains($diplome, 'doctorat') || str_contains($diplome, 'phd')) {
            return 'DOCTORAT';
        }
        if (str_contains($diplome, 'master') || str_contains($diplome, 'dess') || str_contains($diplome, 'dea')) {
            return 'BAC+5';
        }
        if (str_contains($diplome, 'licence') || str_contains($diplome, 'bachelor')) {
            return 'BAC+3';
        }
        if (str_contains($diplome, 'bts') || str_contains($diplome, 'dut') || str_contains($diplome, 'deug')) {
            return 'BAC+2';
        }
        if (str_contains($diplome, 'baccalauréat') || str_contains($diplome, 'bac ')) {
            return 'BAC';
        }

        return null;
    }

    private function parseAnnee(?string $annee): ?int
    {
        if (!$annee) {
            return null;
        }

        // Extraire les 4 chiffres d'une année (ex: "2005", "2005-2006")
        if (preg_match('/(\d{4})/', $annee, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}

