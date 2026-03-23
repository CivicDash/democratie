<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixAmendementsEncoding extends Command
{
    protected $signature = 'fix:amendements-encoding 
                            {--limit= : Limite le nombre d\'amendements à corriger}
                            {--dry-run : Affiche les corrections sans les appliquer}';

    protected $description = 'Corrige l\'encodage HTML des amendements (&#x00E9; → é)';

    public function handle(): int
    {
        $this->info('🔧 Correction de l\'encodage HTML des amendements AN...');
        
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');
        
        if ($dryRun) {
            $this->warn('⚠️  Mode dry-run : aucune modification ne sera appliquée');
        }

        // Compter les amendements à corriger
        $query = DB::table('amendements_an')
            ->where(function($q) {
                $q->where('dispositif', 'like', '%&#x%')
                  ->orWhere('expose', 'like', '%&#x%')
                  ->orWhere('auteur_libelle', 'like', '%&#x%')
                  ->orWhere('cartouche_informatif', 'like', '%&#x%');
            });
        
        $total = $query->count();
        $this->info("📊 {$total} amendements avec encodage HTML à corriger");
        
        if ($total === 0) {
            $this->info('✅ Aucun amendement à corriger');
            return self::SUCCESS;
        }

        // Limiter si demandé
        if ($limit) {
            $query->limit((int)$limit);
            $this->warn("⚠️  Limité à {$limit} amendements");
        }

        $amendements = $query->get(['uid', 'dispositif', 'expose', 'auteur_libelle', 'cartouche_informatif']);
        
        $bar = $this->output->createProgressBar(count($amendements));
        $bar->start();
        
        $fixed = 0;
        $errors = 0;

        foreach ($amendements as $amd) {
            try {
                $updates = [];
                
                if ($amd->dispositif && str_contains($amd->dispositif, '&#x')) {
                    $updates['dispositif'] = html_entity_decode($amd->dispositif, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
                
                if ($amd->expose && str_contains($amd->expose, '&#x')) {
                    $updates['expose'] = html_entity_decode($amd->expose, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
                
                if ($amd->auteur_libelle && str_contains($amd->auteur_libelle, '&#')) {
                    $updates['auteur_libelle'] = html_entity_decode($amd->auteur_libelle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
                
                if ($amd->cartouche_informatif && str_contains($amd->cartouche_informatif, '&#x')) {
                    $updates['cartouche_informatif'] = html_entity_decode($amd->cartouche_informatif, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }

                if (!empty($updates) && !$dryRun) {
                    DB::table('amendements_an')
                        ->where('uid', $amd->uid)
                        ->update($updates);
                    $fixed++;
                } elseif (!empty($updates)) {
                    $fixed++;
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->warn("⚠️  Erreur {$amd->uid}: {$e->getMessage()}");
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['✓ Amendements corrigés', $fixed],
                ['⚠ Erreurs', $errors],
                ['Mode', $dryRun ? 'Dry-run (pas de modif)' : 'Appliqué'],
            ]
        );

        if ($dryRun && $fixed > 0) {
            $this->info("💡 Exécutez sans --dry-run pour appliquer les corrections");
        }

        return self::SUCCESS;
    }
}
