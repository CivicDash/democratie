<?php

namespace App\Console\Commands;

use App\Models\Senateur;
use App\Models\SenateurMandatLocal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportSenateursMandatsLocaux extends Command
{
    protected $signature = 'import:senateurs-mandats-locaux 
                            {--fresh : Supprimer les mandats existants avant l\'import}
                            {--limit= : Limiter le nombre de sénateurs à traiter (pour tests)}';

    protected $description = 'Importe les mandats locaux des sénateurs depuis les APIs du Sénat';

    // URLs des APIs Sénat
    private const API_URLS = [
        'MUNICIPAL' => 'https://data.senat.fr/data/senateurs/ODSEN_ELUVIL.json',
        'DEPARTEMENTAL' => 'https://data.senat.fr/data/senateurs/ODSEN_ELUMET.json',
        'DEPUTE' => 'https://data.senat.fr/data/senateurs/ODSEN_ELUDEP.json',
        'EUROPEEN' => 'https://data.senat.fr/data/senateurs/ODSEN_ELUEUR.json',
    ];

    public function handle(): int
    {
        $this->info('🏛️  Import des mandats locaux des sénateurs...');
        
        if ($this->option('fresh')) {
            $this->warn('⚠️  Mode --fresh : suppression des mandats existants...');
            SenateurMandatLocal::truncate();
        }

        $stats = [
            'total' => 0,
            'nouveaux' => 0,
            'mis_a_jour' => 0,
            'erreurs' => 0,
        ];

        foreach (self::API_URLS as $type => $url) {
            $this->info("\n📥 Import des mandats de type : {$type}");
            
            try {
                $response = Http::timeout(30)->get($url);
                
                if (!$response->successful()) {
                    $this->error("❌ Erreur HTTP {$response->status()} pour {$type}");
                    continue;
                }

                $data = $response->json();
                
                if (empty($data['Senateurs'])) {
                    $this->warn("⚠️  Aucune donnée pour {$type}");
                    continue;
                }

                $mandatsData = $data['Senateurs'];
                $limit = $this->option('limit');
                
                if ($limit) {
                    $mandatsData = array_slice($mandatsData, 0, (int) $limit);
                    $this->warn("⚠️  Mode TEST : {$limit} mandats maximum");
                }

                $progressBar = $this->output->createProgressBar(count($mandatsData));
                $progressBar->start();

                foreach ($mandatsData as $mandatData) {
                    try {
                        $this->importMandat($mandatData, $type, $stats);
                    } catch (\Exception $e) {
                        $stats['erreurs']++;
                        $this->newLine();
                        $this->error("❌ Erreur : " . $e->getMessage());
                    }
                    
                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->newLine();
                
            } catch (\Exception $e) {
                $this->error("❌ Erreur générale pour {$type} : " . $e->getMessage());
                continue;
            }
        }

        // Affichage des statistiques
        $this->newLine();
        $this->info('✅ Import terminé !');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✓ Total traités', $stats['total']],
                ['✓ Nouveaux mandats', $stats['nouveaux']],
                ['↻ Mandats mis à jour', $stats['mis_a_jour']],
                ['⚠ Erreurs', $stats['erreurs']],
            ]
        );

        $totalDB = SenateurMandatLocal::count();
        $this->info("📊 Total en base de données : {$totalDB} mandats locaux");

        return Command::SUCCESS;
    }

    private function importMandat(array $data, string $type, array &$stats): void
    {
        $matricule = $data['Matricule'] ?? null;
        
        if (!$matricule) {
            throw new \Exception('Matricule manquant');
        }

        // Vérifier que le sénateur existe
        if (!Senateur::where('matricule', $matricule)->exists()) {
            return; // Skipper si le sénateur n'est pas importé
        }

        $stats['total']++;

        // Mapping des données selon le type
        $mandatData = match ($type) {
            'MUNICIPAL' => [
                'fonction' => $data['Fonction'] ?? null,
                'collectivite' => $data['Commune'] ?? null,
                'code_collectivite' => $data['Code_commune'] ?? null,
                'date_debut' => $this->parseDate($data['Date_de_debut'] ?? null),
                'date_fin' => $this->parseDate($data['Date_de_fin'] ?? null),
            ],
            'DEPARTEMENTAL' => [
                'fonction' => $data['Fonction'] ?? null,
                'collectivite' => $data['Departement'] ?? null,
                'code_collectivite' => $data['Code_departement'] ?? null,
                'date_debut' => $this->parseDate($data['Date_de_debut'] ?? null),
                'date_fin' => $this->parseDate($data['Date_de_fin'] ?? null),
            ],
            'DEPUTE' => [
                'fonction' => 'Député',
                'collectivite' => $data['Circonscription'] ?? null,
                'code_collectivite' => null,
                'date_debut' => $this->parseDate($data['Date_de_debut'] ?? null),
                'date_fin' => $this->parseDate($data['Date_de_fin'] ?? null),
            ],
            'EUROPEEN' => [
                'fonction' => 'Député européen',
                'collectivite' => 'Parlement européen',
                'code_collectivite' => null,
                'date_debut' => $this->parseDate($data['Date_de_debut'] ?? null),
                'date_fin' => $this->parseDate($data['Date_de_fin'] ?? null),
            ],
            default => [],
        };

        $enCours = empty($mandatData['date_fin']);

        $mandat = SenateurMandatLocal::updateOrCreate(
            [
                'senateur_matricule' => $matricule,
                'type_mandat' => $type,
                'fonction' => $mandatData['fonction'],
                'collectivite' => $mandatData['collectivite'],
                'date_debut' => $mandatData['date_debut'],
            ],
            [
                'code_collectivite' => $mandatData['code_collectivite'],
                'date_fin' => $mandatData['date_fin'],
                'en_cours' => $enCours,
                'details' => $data,
            ]
        );

        if ($mandat->wasRecentlyCreated) {
            $stats['nouveaux']++;
        } else {
            $stats['mis_a_jour']++;
        }
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}

