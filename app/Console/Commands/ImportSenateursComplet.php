<?php

namespace App\Console\Commands;

use App\Models\Senateur;
use App\Models\SenateurHistoriqueGroupe;
use App\Models\SenateurCommission;
use App\Models\SenateurMandat;
use App\Models\SenateurEtude;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportSenateursComplet extends Command
{
    protected $signature = 'import:senateurs-complet 
                            {--fresh : Vide toutes les tables avant l\'import}
                            {--skip-etudes : Ignore l\'import des études}';

    protected $description = 'Import complet des sénateurs depuis les APIs REST data.senat.fr';

    private const BASE_URL = 'https://data.senat.fr/data/senateurs';

    private array $stats = [
        'senateurs' => ['imported' => 0, 'updated' => 0, 'errors' => 0],
        'groupes' => ['imported' => 0, 'errors' => 0],
        'commissions' => ['imported' => 0, 'errors' => 0],
        'mandats' => ['imported' => 0, 'errors' => 0],
        'etudes' => ['imported' => 0, 'errors' => 0],
    ];

    public function handle(): int
    {
        $this->info('🏛️  Import complet des sénateurs...');
        $this->newLine();

        if ($this->option('fresh')) {
            $this->warn('⚠️  Mode --fresh : suppression des données existantes...');
            $this->cleanTables();
        }

        // 1. Import sénateurs
        $this->info('📦 1/5 - Import des sénateurs...');
        $this->importSenateurs();

        // 2. Import historique groupes
        $this->newLine();
        $this->info('📦 2/5 - Import historique groupes politiques...');
        $this->importHistoriqueGroupes();

        // 3. Import commissions
        $this->newLine();
        $this->info('📦 3/5 - Import commissions...');
        $this->importCommissions();

        // 4. Import mandats (tous types)
        $this->newLine();
        $this->info('📦 4/5 - Import mandats (tous types)...');
        $this->importMandats();

        // 5. Import études (optionnel)
        if (!$this->option('skip-etudes')) {
            $this->newLine();
            $this->info('📦 5/5 - Import études...');
            $this->importEtudes();
        }

        $this->newLine(2);
        $this->displaySummary();

        return self::SUCCESS;
    }

    private function cleanTables(): void
    {
        SenateurEtude::truncate();
        SenateurMandat::truncate();
        SenateurCommission::truncate();
        SenateurHistoriqueGroupe::truncate();
        Senateur::truncate();
        $this->line('✓ Tables vidées');
    }

    private function importSenateurs(): void
    {
        $url = self::BASE_URL . '/ODSEN_GENERAL.json';
        
        try {
            $response = Http::timeout(60)->get($url);
            
            if (!$response->successful()) {
                $this->error("❌ Erreur API : {$response->status()}");
                return;
            }

            $data = $response->json();
            $senateurs = $data['results'] ?? [];

            $bar = $this->output->createProgressBar(count($senateurs));
            $bar->start();

            foreach ($senateurs as $senateurData) {
                try {
                    $senateur = Senateur::updateOrCreate(
                        ['matricule' => $senateurData['Matricule']],
                        [
                            'civilite' => $senateurData['Qualite'] ?? null,
                            'nom_usuel' => $senateurData['Nom_usuel'] ?? null,
                            'prenom_usuel' => $senateurData['Prenom_usuel'] ?? null,
                            'etat' => $senateurData['Etat'] ?? 'ANCIEN',
                            'date_naissance' => $this->parseDate($senateurData['Date_naissance'] ?? null),
                            'date_deces' => $this->parseDate($senateurData['Date_de_deces'] ?? null),
                            'groupe_politique' => $senateurData['Groupe_politique'] ?? null,
                            'type_appartenance_groupe' => $senateurData['Type_d_app_au_grp_politique'] ?? null,
                            'commission_permanente' => $senateurData['Commission_permanente'] ?? null,
                            'circonscription' => $senateurData['Circonscription'] ?? null,
                            'fonction_bureau_senat' => $senateurData['Fonction_au_Bureau_du_Senat'] ?? null,
                            'email' => $senateurData['Courrier_electronique'] ?? null,
                            'pcs_insee' => $senateurData['PCS_INSEE'] ?? null,
                            'categorie_socio_pro' => $senateurData['Categorie_professionnelle'] ?? null,
                            'description_profession' => $senateurData['Description_de_la_profession'] ?? null,
                        ]
                    );

                    if ($senateur->wasRecentlyCreated) {
                        $this->stats['senateurs']['imported']++;
                    } else {
                        $this->stats['senateurs']['updated']++;
                    }
                } catch (\Exception $e) {
                    $this->stats['senateurs']['errors']++;
                }
                $bar->advance();
            }

            $bar->finish();
        } catch (\Exception $e) {
            $this->error("❌ Erreur : {$e->getMessage()}");
        }
    }

    private function importHistoriqueGroupes(): void
    {
        $url = self::BASE_URL . '/ODSEN_HISTOGROUPES.json';
        
        try {
            $response = Http::timeout(60)->get($url);
            $data = $response->json();
            $groupes = $data['results'] ?? [];

            $bar = $this->output->createProgressBar(count($groupes));
            $bar->start();

            foreach ($groupes as $groupeData) {
                try {
                    SenateurHistoriqueGroupe::updateOrCreate(
                        [
                            'matricule' => $groupeData['Matricule'],
                            'groupe_politique' => $groupeData['Groupe_politique'],
                            'date_debut' => $this->parseDate($groupeData['Date_debut']),
                        ],
                        [
                            'type_appartenance' => $groupeData['Type_d_app_au_grp_politique'] ?? null,
                            'date_fin' => $this->parseDate($groupeData['Date_fin'] ?? null),
                        ]
                    );
                    $this->stats['groupes']['imported']++;
                } catch (\Exception $e) {
                    $this->stats['groupes']['errors']++;
                }
                $bar->advance();
            }

            $bar->finish();
        } catch (\Exception $e) {
            $this->error("❌ Erreur : {$e->getMessage()}");
        }
    }

    private function importCommissions(): void
    {
        $url = self::BASE_URL . '/ODSEN_COMS.json';
        
        try {
            $response = Http::timeout(60)->get($url);
            $data = $response->json();
            $commissions = $data['results'] ?? [];

            $bar = $this->output->createProgressBar(count($commissions));
            $bar->start();

            foreach ($commissions as $commData) {
                try {
                    SenateurCommission::updateOrCreate(
                        [
                            'matricule' => $commData['Matricule'],
                            'commission' => $commData['Commission_permanente'],
                            'date_debut' => $this->parseDate($commData['Date_debut']),
                        ],
                        [
                            'date_fin' => $this->parseDate($commData['Date_fin'] ?? null),
                            'fonction' => $commData['Fonction'] ?? 'Membre',
                        ]
                    );
                    $this->stats['commissions']['imported']++;
                } catch (\Exception $e) {
                    $this->stats['commissions']['errors']++;
                }
                $bar->advance();
            }

            $bar->finish();
        } catch (\Exception $e) {
            $this->error("❌ Erreur : {$e->getMessage()}");
        }
    }

    private function importMandats(): void
    {
        $endpoints = [
            'SENATEUR' => 'ODSEN_ELUSEN.json',
            'DEPUTE' => 'ODSEN_ELUDEP.json',
            'EUROPEEN' => 'ODSEN_ELUEUR.json',
            'METROPOLITAIN' => 'ODSEN_ELUMET.json',
            'MUNICIPAL' => 'ODSEN_ELUVIL.json',
        ];

        foreach ($endpoints as $type => $endpoint) {
            $url = self::BASE_URL . '/' . $endpoint;
            
            try {
                $response = Http::timeout(60)->get($url);
                $data = $response->json();
                $mandats = $data['results'] ?? [];

                $this->line("  → {$type} : " . count($mandats) . " mandats");

                foreach ($mandats as $mandatData) {
                    try {
                        SenateurMandat::create([
                            'matricule' => $mandatData['Matricule'],
                            'type_mandat' => $type,
                            'circonscription' => $mandatData['Circonscription'] ?? null,
                            'date_debut' => $this->parseDate($mandatData['Date_debut']),
                            'date_fin' => $this->parseDate($mandatData['Date_fin'] ?? null),
                            'motif_fin' => $mandatData['Motif_fin'] ?? null,
                            'numero_mandat' => $mandatData['Numero_mandat'] ?? null,
                        ]);
                        $this->stats['mandats']['imported']++;
                    } catch (\Exception $e) {
                        $this->stats['mandats']['errors']++;
                    }
                }
            } catch (\Exception $e) {
                $this->warn("⚠️  Erreur {$type} : {$e->getMessage()}");
            }
        }
    }

    private function importEtudes(): void
    {
        $url = self::BASE_URL . '/ODSEN_ETUDES.json';
        
        try {
            $response = Http::timeout(60)->get($url);
            $data = $response->json();
            $etudes = $data['results'] ?? [];

            $bar = $this->output->createProgressBar(count($etudes));
            $bar->start();

            foreach ($etudes as $etudeData) {
                try {
                    SenateurEtude::create([
                        'matricule' => $etudeData['Matricule'],
                        'diplome' => $etudeData['Diplome'] ?? null,
                        'etablissement' => $etudeData['Etablissement'] ?? null,
                        'annee_obtention' => $etudeData['Annee_obtention'] ?? null,
                    ]);
                    $this->stats['etudes']['imported']++;
                } catch (\Exception $e) {
                    $this->stats['etudes']['errors']++;
                }
                $bar->advance();
            }

            $bar->finish();
        } catch (\Exception $e) {
            $this->error("❌ Erreur : {$e->getMessage()}");
        }
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date || $date === 'null') {
            return null;
        }
        
        // Format : "2023/10/01 00:00:00" → "2023-10-01"
        return substr(str_replace('/', '-', $date), 0, 10);
    }

    private function displaySummary(): void
    {
        $this->info('✅ Import terminé !');
        $this->newLine();
        
        $this->table(
            ['Entité', 'Importés', 'Mis à jour', 'Erreurs'],
            [
                ['Sénateurs', $this->stats['senateurs']['imported'], $this->stats['senateurs']['updated'], $this->stats['senateurs']['errors']],
                ['Groupes', $this->stats['groupes']['imported'], '-', $this->stats['groupes']['errors']],
                ['Commissions', $this->stats['commissions']['imported'], '-', $this->stats['commissions']['errors']],
                ['Mandats', $this->stats['mandats']['imported'], '-', $this->stats['mandats']['errors']],
                ['Études', $this->stats['etudes']['imported'], '-', $this->stats['etudes']['errors']],
            ]
        );

        $this->newLine();
        $this->info('📊 Statistiques finales :');
        $this->line('   - Sénateurs actifs : ' . Senateur::actifs()->count());
        $this->line('   - Sénateurs anciens : ' . Senateur::anciens()->count());
        $this->line('   - Mandats sénateur actifs : ' . SenateurMandat::where('type_mandat', 'SENATEUR')->whereNull('date_fin')->count());
    }
}

