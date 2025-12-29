<?php

namespace App\Console\Commands;

use App\Models\Tag;
use App\Models\ThematiqueLoi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTagsFromSenat extends Command
{
    protected $signature = 'sync:tags-senat 
                            {--with-lois : Associer les tags aux lois existantes}
                            {--fresh : Réinitialiser tous les tags officiels}';

    protected $description = 'Synchronise les thématiques du Sénat comme tags officiels';

    protected int $created = 0;
    protected int $updated = 0;
    protected int $linked = 0;

    public function handle(): int
    {
        $this->info('🏷️  Synchronisation des tags depuis le Sénat...');

        if ($this->option('fresh')) {
            $this->warn('  Suppression des tags officiels existants...');
            Tag::where('source', 'official')->delete();
        }

        // 1. Importer les thématiques comme tags
        $this->importThematiques();

        // 2. Associer aux lois si demandé
        if ($this->option('with-lois')) {
            $this->linkTagsToLois();
        }

        $this->newLine();
        $this->info('✅ Synchronisation terminée !');
        $this->table(
            ['Tags créés', 'Tags mis à jour', 'Associations lois'],
            [[$this->created, $this->updated, $this->linked]]
        );

        return Command::SUCCESS;
    }

    protected function importThematiques(): void
    {
        $this->info('  📚 Import des thématiques Sénat...');

        $thematiques = ThematiqueLoi::all();

        $bar = $this->output->createProgressBar($thematiques->count());
        $bar->start();

        foreach ($thematiques as $thematique) {
            $nom = $thematique->thelib ?? $thematique->thecle;
            
            if (empty($nom)) {
                $bar->advance();
                continue;
            }

            $slug = Str::slug($nom);
            $couleur = Tag::getColorForTheme($nom);
            $icone = Tag::getIconForTheme($nom);

            $tag = Tag::updateOrCreate(
                ['slug' => $slug],
                [
                    'nom' => $nom,
                    'description' => "Thématique officielle du Sénat",
                    'couleur' => $couleur,
                    'icone' => $icone,
                    'type' => 'thematique',
                    'source' => 'official',
                    'validated' => true,
                    'validated_at' => now(),
                ]
            );

            if ($tag->wasRecentlyCreated) {
                $this->created++;
            } else {
                $this->updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function linkTagsToLois(): void
    {
        $this->info('  🔗 Association des tags aux lois...');

        // Récupérer les associations thématique-loi existantes
        $associations = DB::table('senat_dosleg_loithe')
            ->join('senat_dosleg_the', 'senat_dosleg_loithe.thecle', '=', 'senat_dosleg_the.thecle')
            ->select('senat_dosleg_loithe.loicod', 'senat_dosleg_the.thelib', 'senat_dosleg_the.thecle')
            ->get();

        $bar = $this->output->createProgressBar($associations->count());
        $bar->start();

        // Grouper par loi pour optimiser
        $byLoi = $associations->groupBy('loicod');

        foreach ($byLoi as $loicod => $thematiquesList) {
            foreach ($thematiquesList as $thematique) {
                $nom = $thematique->thelib ?? $thematique->thecle;
                $slug = Str::slug($nom);

                $tag = Tag::where('slug', $slug)->first();
                
                if (!$tag) {
                    continue;
                }

                // Vérifier si l'association existe déjà
                $exists = DB::table('loi_tag')
                    ->where('loi_loicod', $loicod)
                    ->where('tag_id', $tag->id)
                    ->exists();

                if (!$exists) {
                    DB::table('loi_tag')->insert([
                        'loi_loicod' => $loicod,
                        'tag_id' => $tag->id,
                        'source' => 'official',
                        'validated' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $tag->increment('usage_count');
                    $this->linked++;
                }

                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
    }
}

