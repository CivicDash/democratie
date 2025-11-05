<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SetupDemoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:setup 
                            {--fresh : Réinitialiser complètement la base de données}
                            {--force : Forcer l\'exécution sans confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure CivicDash en mode démonstration avec des données synthétiques';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->displayBanner();

        // Vérification de l'environnement
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('❌ Cette commande ne peut pas être exécutée en production sans --force');
            return self::FAILURE;
        }

        // Confirmation
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  Cette opération va modifier la base de données. Continuer ?', false)) {
                $this->info('Opération annulée.');
                return self::SUCCESS;
            }
        }

        $this->newLine();
        $this->info('🚀 Démarrage de la configuration en mode démo...');
        $this->newLine();

        // Étape 1 : Migrations
        $this->step('Exécution des migrations', function () {
            if ($this->option('fresh')) {
                $this->call('migrate:fresh', ['--force' => true]);
            } else {
                $this->call('migrate', ['--force' => true]);
            }
        });

        // Étape 2 : Seeders de base
        $this->step('Chargement des données de référence', function () {
            $this->call('db:seed', [
                '--class' => 'RolesAndPermissionsSeeder',
                '--force' => true,
            ]);
            $this->call('db:seed', [
                '--class' => 'TerritoriesSeeder',
                '--force' => true,
            ]);
            $this->call('db:seed', [
                '--class' => 'SectorsSeeder',
                '--force' => true,
            ]);
            $this->call('db:seed', [
                '--class' => 'ThematiqueLegislationSeeder',
                '--force' => true,
            ]);
            // $this->call('db:seed', [
            //     '--class' => 'PolicyVersionSeeder',
            //     '--force' => true,
            // ]);
            $this->call('db:seed', [
                '--class' => 'GroupesParlementairesSeeder',
                '--force' => true,
            ]);
            $this->call('db:seed', [
                '--class' => 'AchievementSeeder',
                '--force' => true,
            ]);
        });

        // Étape 3 : Comptes de test
        $this->step('Création des comptes de test', function () {
            $this->createTestAccounts();
        });

        // Étape 4 : Données de démonstration
        $this->step('Génération des données de démonstration', function () {
            $this->call('db:seed', [
                '--class' => 'DemoDataSeeder',
                '--force' => true,
            ]);
        });

        // Étape 5 : Index de recherche (optionnel si Scout est configuré)
        $this->step('Indexation des données pour la recherche', function () {
            if (!config('scout.driver')) {
                $this->warn('⚠️  Scout non configuré, indexation ignorée');
                return;
            }

            $searchableModels = [
                'App\\Models\\PropositionLoi',
                'App\\Models\\Topic',
                'App\\Models\\Post',
            ];

            foreach ($searchableModels as $model) {
                if (in_array('Laravel\\Scout\\Searchable', class_uses_recursive($model))) {
                    try {
                        $this->call('scout:import', ['model' => $model]);
                    } catch (\Exception $e) {
                        $this->warn("⚠️  Impossible d'indexer {$model}: {$e->getMessage()}");
                    }
                } else {
                    $this->comment("⏭️  {$model} n'est pas searchable, ignoré");
                }
            }
        });

        // Étape 6 : Cache
        $this->step('Optimisation du cache', function () {
            $this->call('config:cache');
            $this->call('route:cache');
            $this->call('view:cache');
        });

        $this->newLine(2);
        $this->displaySuccess();

        return self::SUCCESS;
    }

    private function displayBanner(): void
    {
        $this->newLine();
        $this->line('╔═══════════════════════════════════════════════════════════╗');
        $this->line('║                                                           ║');
        $this->line('║           🎬 CIVICDASH - MODE DÉMONSTRATION 🎬            ║');
        $this->line('║                                                           ║');
        $this->line('║     Configuration automatique avec données réalistes     ║');
        $this->line('║                                                           ║');
        $this->line('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    private function step(string $description, callable $callback): void
    {
        $this->info("⏳ $description...");
        
        $startTime = microtime(true);
        $callback();
        $duration = round(microtime(true) - $startTime, 2);
        
        $this->info("✅ $description terminé ({$duration}s)");
        $this->newLine();
    }

    private function createTestAccounts(): void
    {
        $users = [
            [
                'name' => 'Admin CivicDash',
                'email' => 'admin@civicdash.fr',
                'role' => 'admin',
            ],
            [
                'name' => 'Modérateur Test',
                'email' => 'moderator@civicdash.fr',
                'role' => 'moderator',
            ],
            [
                'name' => 'Député Test',
                'email' => 'legislator@civicdash.fr',
                'role' => 'legislator',
            ],
            [
                'name' => 'Journaliste Test',
                'email' => 'journalist@civicdash.fr',
                'role' => 'journalist',
            ],
            [
                'name' => 'Citoyen Test',
                'email' => 'citizen@civicdash.fr',
                'role' => 'citizen',
            ],
        ];

        foreach ($users as $userData) {
            $user = \App\Models\User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => \Illuminate\Support\Facades\Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            if (!$user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }

            // Créer le profil pour le citoyen
            if ($userData['role'] === 'citizen' && !$user->profile) {
                \App\Models\Profile::create([
                    'user_id' => $user->id,
                    'display_name' => \App\Models\Profile::generateDisplayName(),
                    'citizen_ref_hash' => \App\Models\Profile::hashCitizenRef('test-citizen-ref-123'),
                    'scope' => 'national',
                    'is_verified' => false,
                ]);
            }
        }
    }

    private function displaySuccess(): void
    {
        $this->line('╔═══════════════════════════════════════════════════════════╗');
        $this->line('║                                                           ║');
        $this->line('║              ✅ CONFIGURATION TERMINÉE ! ✅                ║');
        $this->line('║                                                           ║');
        $this->line('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->info('📊 Données générées :');
        $this->table(
            ['Type', 'Quantité'],
            [
                ['👥 Citoyens', '50+'],
                ['🏛️ Députés', '20'],
                ['📜 Propositions de loi', '30'],
                ['💬 Topics de débat', '25'],
                ['💭 Posts et réponses', '200+'],
                ['🗳️ Votes citoyens', '1500+'],
                ['📅 Événements législatifs', '14'],
                ['⚖️ Références juridiques', '3'],
                ['#️⃣ Hashtags', '8'],
            ]
        );

        $this->newLine();
        $this->info('🔐 Comptes de test disponibles :');
        $this->newLine();
        
        $this->table(
            ['Rôle', 'Email', 'Mot de passe'],
            [
                ['Admin', 'admin@civicdash.fr', 'password'],
                ['Modérateur', 'moderator@civicdash.fr', 'password'],
                ['Législateur', 'legislator@civicdash.fr', 'password'],
                ['Journaliste', 'journalist@civicdash.fr', 'password'],
                ['Citoyen', 'citizen@civicdash.fr', 'password'],
                ['Citoyens démo', 'citoyen1@demo.civicdash.fr à citoyen50@...', 'demo2025'],
                ['Députés démo', 'depute1@demo.assemblee-nationale.fr à depute20@...', 'demo2025'],
            ]
        );

        $this->newLine();
        $this->info('🌐 Vous pouvez maintenant accéder à CivicDash :');
        $this->line('   → php artisan serve');
        $this->line('   → http://localhost:8000');
        $this->newLine();

        $this->warn('⚠️  N\'oubliez pas de configurer PEPPER dans .env pour le hachage sécurisé !');
        $this->info('   → Commande : make pepper (ou générez une clé aléatoire de 32 caractères)');
        $this->newLine();
    }
}

