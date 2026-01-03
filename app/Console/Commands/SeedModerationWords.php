<?php

namespace App\Console\Commands;

use App\Services\ContentModerationService;
use Illuminate\Console\Command;

class SeedModerationWords extends Command
{
    protected $signature = 'moderation:seed {--force : Forcer même si des mots existent déjà}';
    protected $description = 'Initialise les mots bannis et les mots gentils de remplacement';

    public function handle(ContentModerationService $moderationService): int
    {
        $this->info('🛡️ Initialisation des mots de modération...');
        $this->newLine();

        $result = $moderationService->seedDefaultWords();

        $this->info("✅ {$result['banned']} mots bannis ajoutés");
        $this->info("💖 {$result['nice']} mots gentils ajoutés");

        $this->newLine();
        $this->info('🎉 Modération initialisée !');
        $this->line('');
        $this->line('Exemple de remplacement :');
        $this->line('  "Tu es un crétin" → "Tu es un 🌈"');
        $this->line('  "C\'est de la merde" → "C\'est du petit chaton"');

        return Command::SUCCESS;
    }
}
