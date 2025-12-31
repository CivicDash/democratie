<?php

namespace App\Console\Commands;

use App\Models\Topic;
use Illuminate\Console\Command;

class MigrateTopicsToIdeas extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'topics:migrate-to-ideas 
                            {--dry-run : Afficher les changements sans les appliquer}
                            {--type=discussion : Type d\'idée par défaut (discussion, proposal, debate)}';

    /**
     * The console command description.
     */
    protected $description = 'Migre les anciens Topics (type debate/bill) vers le nouveau système d\'Idées avec idea_type';

    /**
     * Mapping des anciens types vers les nouveaux
     */
    protected array $typeMapping = [
        'debate' => 'discussion',  // Débats libres → Discussions
        'bill' => 'proposal',      // Projets de loi → Propositions
        'referendum' => 'petition', // Référendums → Pétitions
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $defaultType = $this->option('type');
        
        $this->info('🔄 Migration des Topics vers le système Idées');
        $this->newLine();
        
        // Trouver les topics sans idea_type ou avec idea_type vide
        $topics = Topic::whereNull('idea_type')
            ->orWhere('idea_type', '')
            ->get();
        
        if ($topics->isEmpty()) {
            $this->info('✅ Aucun topic à migrer. Tous les topics ont déjà un idea_type.');
            return self::SUCCESS;
        }
        
        $this->info("📊 {$topics->count()} topics à migrer");
        $this->newLine();
        
        $migrated = 0;
        $errors = 0;
        
        foreach ($topics as $topic) {
            // Déterminer le nouveau idea_type
            $oldType = $topic->type ?? 'debate';
            $newIdeaType = $this->typeMapping[$oldType] ?? $defaultType;
            
            // Vérifier si le topic a déjà un slug
            if (empty($topic->slug)) {
                $topic->slug = Topic::generateSlug($topic->title);
            }
            
            $this->line("  📝 [{$topic->id}] {$topic->title}");
            $this->line("     Type: {$oldType} → {$newIdeaType}");
            $this->line("     Slug: {$topic->slug}");
            
            if (!$dryRun) {
                try {
                    $topic->update([
                        'idea_type' => $newIdeaType,
                        'slug' => $topic->slug,
                    ]);
                    $migrated++;
                    $this->info("     ✅ Migré");
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("     ❌ Erreur: " . $e->getMessage());
                }
            } else {
                $this->comment("     🔍 (dry-run - pas de modification)");
            }
            
            $this->newLine();
        }
        
        $this->newLine();
        
        if ($dryRun) {
            $this->warn("🔍 Mode dry-run: aucune modification effectuée");
            $this->info("   Relancez sans --dry-run pour appliquer les changements");
        } else {
            $this->info("✅ Migration terminée");
            $this->info("   Migrés: {$migrated}");
            if ($errors > 0) {
                $this->warn("   Erreurs: {$errors}");
            }
        }
        
        return self::SUCCESS;
    }
}
