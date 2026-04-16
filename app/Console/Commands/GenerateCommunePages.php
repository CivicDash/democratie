<?php

namespace App\Console\Commands;

use App\Models\CommunePage;
use App\Models\Ville;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateCommunePages extends Command
{
    protected $signature = 'communes:generate-pages
                            {--departement= : Genere uniquement pour un departement}
                            {--force : Regenere meme les pages existantes}
                            {--limit= : Limite le nombre de pages}
                            {--dev : Echantillon representatif pour dev (grandes villes + moyennes + petites)}';

    protected $description = 'Genere les pages communes auto-generees pour toutes les villes en base';

    private const DEV_SAMPLE = [
        '75056',  // Paris
        '13055',  // Marseille
        '69123',  // Lyon
        '31555',  // Toulouse
        '44109',  // Nantes
        '67482',  // Strasbourg (prefecture, chef-lieu region)
        '39448',  // Rochefort-sur-Nenon (village Jura ~700 hab)
        '34172',  // Montpellier
        '29019',  // Brest (sous-prefecture)
        '97105',  // Cayenne (DROM)
        '20004',  // Ajaccio (Corse)
        '01001',  // L'Abergement-Clemenciat (petit village Ain)
    ];

    public function handle(): int
    {
        if ($this->option('dev')) {
            return $this->generateDev();
        }

        $this->info('Generation des pages communes...');

        $query = Ville::query()
            ->whereNotNull('code_insee')
            ->where('code_insee', '!=', '');

        if ($departement = $this->option('departement')) {
            $query->where('departement_code', $departement);
        }

        if (! $this->option('force')) {
            $query->whereDoesntHave('communePage');
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $total = $query->count();
        $this->info("  {$total} villes a traiter");

        if ($total === 0) {
            $this->info('Aucune ville a traiter');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $created = 0;
        $errors = 0;

        $query->select('id', 'code_insee', 'slug', 'nom', 'departement_code')
            ->chunk(500, function ($villes) use ($bar, &$created, &$errors) {
                $inserts = [];

                foreach ($villes as $ville) {
                    try {
                        $inserts[] = [
                            'code_insee' => $ville->code_insee,
                            'ville_id' => $ville->id,
                            'statut' => 'auto_generee',
                            'forum_actif' => true,
                            'couleur_primaire' => '#1e40af',
                            'couleur_secondaire' => '#3b82f6',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $created++;
                    } catch (\Exception $e) {
                        $errors++;
                        Log::warning('GenerateCommunePages: erreur', [
                            'code_insee' => $ville->code_insee,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $bar->advance();
                }

                if (! empty($inserts)) {
                    DB::table('commune_pages')->upsert(
                        $inserts,
                        ['code_insee'],
                        ['ville_id', 'updated_at']
                    );
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metrique', 'Valeur'],
            [
                ['Pages creees', $created],
                ['Erreurs', $errors],
                ['Total en base', CommunePage::count()],
            ]
        );

        return self::SUCCESS;
    }

    private function generateDev(): int
    {
        $this->info('Mode DEV : generation d\'un echantillon representatif...');
        $this->newLine();

        $villes = Ville::whereIn('code_insee', self::DEV_SAMPLE)
            ->select('id', 'code_insee', 'slug', 'nom', 'departement_nom', 'population')
            ->get();

        if ($villes->isEmpty()) {
            $this->warn('Aucune des communes de l\'echantillon n\'est en base.');
            $this->line('Codes recherches : '.implode(', ', self::DEV_SAMPLE));
            $this->newLine();

            // Fallback : prendre les 12 plus grandes villes disponibles
            $this->info('Fallback : selection des 12 plus grandes villes en base...');
            $villes = Ville::whereNotNull('code_insee')
                ->where('code_insee', '!=', '')
                ->where('arrondissement_municipal', false)
                ->orderByDesc('population')
                ->limit(12)
                ->select('id', 'code_insee', 'slug', 'nom', 'departement_nom', 'population')
                ->get();

            if ($villes->isEmpty()) {
                $this->error('Aucune ville en base.');

                return self::FAILURE;
            }
        }

        $inserts = [];
        $rows = [];

        foreach ($villes as $ville) {
            $inserts[] = [
                'code_insee' => $ville->code_insee,
                'ville_id' => $ville->id,
                'statut' => 'auto_generee',
                'forum_actif' => true,
                'couleur_primaire' => '#1e40af',
                'couleur_secondaire' => '#3b82f6',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $rows[] = [
                $ville->code_insee,
                $ville->nom,
                $ville->departement_nom,
                number_format($ville->population ?? 0, 0, ',', ' '),
                $ville->slug,
            ];
        }

        DB::table('commune_pages')->upsert(
            $inserts,
            ['code_insee'],
            ['ville_id', 'updated_at']
        );

        $this->table(
            ['INSEE', 'Commune', 'Departement', 'Population', 'Slug'],
            $rows
        );

        $this->newLine();
        $this->info("  {$villes->count()} pages communes generees pour dev");
        $this->line('  Testez via : /commune-hub/{code_insee}');
        $this->newLine();

        return self::SUCCESS;
    }
}
