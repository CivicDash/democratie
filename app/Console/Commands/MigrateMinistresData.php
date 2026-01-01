<?php

namespace App\Console\Commands;

use App\Models\Gouvernement;
use App\Models\Ministre;
use App\Models\Ministere;
use App\Models\PersonnePolitique;
use App\Models\PosteMinisteriel;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MigrateMinistresData extends Command
{
    protected $signature = 'gouvernement:migrate-data 
                            {--dry-run : Mode simulation}
                            {--import-historique : Importer aussi l\'historique des gouvernements}';

    protected $description = 'Migrer les données ministres vers la nouvelle structure (personnes + postes)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('🔄 Migration des données gouvernementales');
        $this->newLine();

        // 1. Importer l'historique des gouvernements si demandé
        if ($this->option('import-historique')) {
            $this->importHistoriqueGouvernements($dryRun);
        }

        // 2. Mettre à jour le numéro du gouvernement actuel
        $this->updateCurrentGouvernement($dryRun);

        // 3. Migrer les ministres existants
        $this->migrateMinistres($dryRun);

        $this->newLine();
        $this->info('✅ Migration terminée !');

        return Command::SUCCESS;
    }

    private function importHistoriqueGouvernements(bool $dryRun): void
    {
        $this->info('📜 Import de l\'historique des gouvernements...');
        
        $file = database_path('data/gouvernements_historique.json');
        if (!file_exists($file)) {
            $this->warn('   Fichier historique non trouvé : ' . $file);
            return;
        }

        $data = json_decode(file_get_contents($file), true);
        $gouvernements = $data['gouvernements'] ?? [];

        foreach ($gouvernements as $gouv) {
            $this->line("   → {$gouv['numero']}ème : {$gouv['nom']} {$gouv['suffixe']}");
            
            if (!$dryRun) {
                Gouvernement::updateOrCreate(
                    ['numero' => $gouv['numero']],
                    [
                        'nom' => $gouv['nom'],
                        'slug' => Str::slug($gouv['nom'] . '-' . ($gouv['suffixe'] ?: '')),
                        'suffixe' => $gouv['suffixe'] ?: null,
                        'premier_ministre' => $gouv['premier_ministre'],
                        'president' => $gouv['president'],
                        'date_debut' => $gouv['date_debut'],
                        'date_fin' => $gouv['date_fin'],
                        'actif' => $gouv['actif'],
                        'legislature' => $gouv['legislature'] ?? null,
                        'contexte' => $gouv['contexte'] ?? null,
                    ]
                );
            }
        }

        $this->info('   ✓ ' . count($gouvernements) . ' gouvernements importés');
    }

    private function updateCurrentGouvernement(bool $dryRun): void
    {
        $this->info('📊 Mise à jour du gouvernement actuel...');
        
        $current = Gouvernement::where('actif', true)->first();
        
        if ($current && !$current->numero) {
            $this->line("   → Gouvernement actuel : {$current->nom}");
            $this->line("   → Attribution du numéro 48 (Lecornu II)");
            
            if (!$dryRun) {
                $current->update([
                    'numero' => 48,
                    'suffixe' => 'II',
                ]);
            }
        } else {
            $this->line("   → Gouvernement actuel déjà numéroté : " . ($current?->numero ?? 'aucun'));
        }
    }

    private function migrateMinistres(bool $dryRun): void
    {
        $this->info('👥 Migration des ministres vers personnes_politiques + postes_ministeriels...');
        
        $ministres = Ministre::with(['gouvernement', 'ministere'])->get();
        
        if ($ministres->isEmpty()) {
            $this->warn('   Aucun ministre à migrer');
            return;
        }

        $this->info('   → ' . $ministres->count() . ' ministres à migrer');
        
        $stats = ['personnes' => 0, 'postes' => 0, 'existants' => 0];

        $bar = $this->output->createProgressBar($ministres->count());
        $bar->start();

        foreach ($ministres as $ministre) {
            // 1. Créer ou trouver la personne politique
            $slug = Str::slug($ministre->prenom . '-' . $ministre->nom);
            
            if (!$dryRun) {
                $personne = PersonnePolitique::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'prenom' => $ministre->prenom,
                        'nom' => $ministre->nom,
                        'civilite' => $ministre->civilite,
                        'date_naissance' => $ministre->date_naissance,
                        'lieu_naissance' => $ministre->lieu_naissance,
                        'profession' => $ministre->profession,
                        'parti_politique' => $ministre->parti_politique,
                        'photo_url' => $ministre->photo_url,
                        'twitter_url' => $ministre->twitter,
                        'wikipedia_url' => $ministre->wikipedia_url,
                        'uid_an' => $ministre->uid_an,
                        'uid_senat' => $ministre->uid_senat,
                    ]
                );

                if ($personne->wasRecentlyCreated) {
                    $stats['personnes']++;
                } else {
                    $stats['existants']++;
                }

                // 2. Créer le poste ministériel
                PosteMinisteriel::firstOrCreate(
                    [
                        'personne_id' => $personne->id,
                        'gouvernement_id' => $ministre->gouvernement_id,
                        'fonction' => $ministre->fonction,
                    ],
                    [
                        'ministere_id' => $ministre->ministere_id,
                        'type_fonction' => $ministre->type_fonction ?? 'ministre',
                        'date_debut' => $ministre->date_debut ?? $ministre->gouvernement?->date_debut,
                        'date_fin' => $ministre->date_fin,
                        'actif' => $ministre->actif,
                        'decret_nomination' => $ministre->decret_nomination,
                        'date_decret' => $ministre->date_decret,
                    ]
                );
                $stats['postes']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("   ✓ Personnes créées : {$stats['personnes']}");
        $this->info("   ✓ Personnes existantes : {$stats['existants']}");
        $this->info("   ✓ Postes créés : {$stats['postes']}");
    }
}
