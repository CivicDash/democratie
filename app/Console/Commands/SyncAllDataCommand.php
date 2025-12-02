<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SyncAllDataCommand extends Command
{
    protected $signature = 'sync:all
                            {--quick : Mode rapide (données récentes uniquement)}
                            {--senat : Synchroniser uniquement les données Sénat}
                            {--an : Synchroniser uniquement les données AN}
                            {--hatvp : Synchroniser uniquement les données HATVP}
                            {--photos : Synchroniser uniquement les photos Wikipedia}
                            {--fresh : Réimporter toutes les données (attention: long!)}
                            {--dry-run : Afficher les commandes sans les exécuter}';

    protected $description = 'Synchronise toutes les données parlementaires (Sénat, AN, HATVP, Wikipedia)';

    private bool $dryRun = false;
    private array $results = [];

    public function handle(): int
    {
        $this->dryRun = $this->option('dry-run');
        $quick = $this->option('quick');
        $fresh = $this->option('fresh');
        
        // Déterminer quoi synchroniser
        $syncSenat = $this->option('senat') || (!$this->option('an') && !$this->option('hatvp') && !$this->option('photos'));
        $syncAN = $this->option('an') || (!$this->option('senat') && !$this->option('hatvp') && !$this->option('photos'));
        $syncHatvp = $this->option('hatvp') || (!$this->option('senat') && !$this->option('an') && !$this->option('photos'));
        $syncPhotos = $this->option('photos') || (!$this->option('senat') && !$this->option('an') && !$this->option('hatvp'));

        $this->info("🔄 Synchronisation des données parlementaires");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        if ($this->dryRun) {
            $this->warn("⚠️  Mode dry-run : les commandes ne seront pas exécutées");
        }
        
        if ($quick) {
            $this->info("⚡ Mode rapide activé");
        }
        
        $startTime = now();

        // 1. Données Sénat
        if ($syncSenat) {
            $this->syncSenat($quick, $fresh);
        }

        // 2. Données AN
        if ($syncAN) {
            $this->syncAN($quick, $fresh);
        }

        // 3. Données HATVP
        if ($syncHatvp) {
            $this->syncHatvp($quick, $fresh);
        }

        // 4. Photos Wikipedia
        if ($syncPhotos) {
            $this->syncPhotos($quick);
        }

        // Résumé
        $duration = now()->diffInMinutes($startTime);
        $this->newLine();
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("✅ Synchronisation terminée en {$duration} minutes");
        $this->displayResults();

        return Command::SUCCESS;
    }

    private function syncSenat(bool $quick, bool $fresh): void
    {
        $this->newLine();
        $this->info("🏛️  1/4 - Données Sénat");
        
        if (!$quick) {
            // Import complet des bases SQL
            $this->runCommand('import:senat-sql', ['type' => 'senateurs'], 'Sénateurs');
            
            if (!$fresh) {
                // En mode normal, on ne réimporte pas AMELI (très long)
                $this->info("   ⏭️  AMELI ignoré (utilisez --fresh pour réimporter)");
            } else {
                $this->runCommand('import:senat-sql', ['type' => 'ameli', '--fresh' => true], 'Amendements AMELI');
            }
            
            $this->runCommand('import:senat-sql', ['type' => 'questions'], 'Questions');
        } else {
            $this->info("   ⏭️  Mode rapide : bases SQL ignorées");
        }
        
        // Textes Akoma Ntoso (toujours, car incrémental)
        $since = $quick ? 3 : 30;
        $this->runCommand('import:akoma-ntoso', ['--since' => $since], "Textes Akoma Ntoso ({$since}j)");
    }

    private function syncAN(bool $quick, bool $fresh): void
    {
        $this->newLine();
        $this->info("🏛️  2/4 - Données Assemblée Nationale");
        
        $limit = $quick ? 100 : null;
        $options = $limit ? ['--limit' => $limit] : [];
        
        $this->runCommand('import:questions-an', $options, 'Questions au Gouvernement');
    }

    private function syncHatvp(bool $quick, bool $fresh): void
    {
        $this->newLine();
        $this->info("📋 3/4 - Données HATVP (Transparence)");
        
        $options = ['--import-details' => true];
        
        if ($quick) {
            $options['--limit'] = 200;
            $options['--parlementaires'] = true;
        }
        
        $this->runCommand('hatvp:sync', $options, 'Déclarations HATVP');
    }

    private function syncPhotos(bool $quick): void
    {
        $this->newLine();
        $this->info("📸 4/4 - Photos Wikipedia");
        
        $limit = $quick ? 50 : null;
        
        // Sénateurs
        $options = $limit ? ['--limit' => $limit] : [];
        $this->runCommand('enrich:senateurs-wikipedia', $options, 'Photos sénateurs');
        
        // Députés
        $this->runCommand('enrich:deputes-wikipedia', $options, 'Photos députés');
    }

    private function runCommand(string $command, array $options = [], string $label = ''): void
    {
        $label = $label ?: $command;
        $optionsStr = collect($options)->map(fn($v, $k) => is_bool($v) ? $k : "{$k}={$v}")->implode(' ');
        
        $this->info("   → {$label}...");
        
        if ($this->dryRun) {
            $this->line("     <fg=gray>php artisan {$command} {$optionsStr}</>");
            $this->results[$label] = 'dry-run';
            return;
        }
        
        try {
            $exitCode = Artisan::call($command, $options);
            
            if ($exitCode === 0) {
                $this->info("     ✅ Succès");
                $this->results[$label] = 'success';
            } else {
                $this->warn("     ⚠️  Code retour: {$exitCode}");
                $this->results[$label] = 'warning';
            }
        } catch (\Exception $e) {
            $this->error("     ❌ Erreur: " . $e->getMessage());
            $this->results[$label] = 'error';
        }
    }

    private function displayResults(): void
    {
        $this->newLine();
        $this->table(
            ['Tâche', 'Statut'],
            collect($this->results)->map(fn($status, $task) => [
                $task,
                match($status) {
                    'success' => '✅ Succès',
                    'warning' => '⚠️ Attention',
                    'error' => '❌ Erreur',
                    'dry-run' => '🔍 Dry-run',
                    default => $status,
                }
            ])->toArray()
        );
    }
}

