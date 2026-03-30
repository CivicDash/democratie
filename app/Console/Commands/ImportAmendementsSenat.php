<?php

namespace App\Console\Commands;

use App\Models\AmendementSenat;
use Illuminate\Console\Command;

class ImportAmendementsSenat extends Command
{
    protected $signature = 'import:amendements-senat 
                            {--legislature=2024 : Législature à importer (ex: 2024)} 
                            {--fresh : Vider la table avant import}
                            {--limit= : Limite du nombre d\'amendements (pour tests)}';

    protected $description = 'Importe les amendements du Sénat depuis data.senat.fr';

    private int $imported = 0;

    private int $updated = 0;

    private int $skipped = 0;

    private int $errors = 0;

    /**
     * API data.senat.fr - Amendements
     * Source : API JSON REST endpoint
     */
    public function handle(): int
    {
        $legislature = (int) $this->option('legislature');
        $fresh = $this->option('fresh');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->info('🏛️  Import des amendements Sénat...');
        $this->info("📊 Législature cible : {$legislature}");

        if ($fresh) {
            $this->warn('⚠️  Mode --fresh : suppression des amendements existants...');
            AmendementSenat::where('legislature', $legislature)->delete();
        }

        if ($limit) {
            $this->warn("⚠️  Mode TEST : {$limit} amendements maximum");
        }

        // Note: Les amendements du Sénat sont disponibles via la base SQL AMELI
        $this->error('❌ Import manuel non disponible. Utilisez la base SQL AMELI :');
        $this->newLine();
        $this->info('📦 Commande recommandée :');
        $this->info('   php artisan import:senat-sql ameli --fresh');
        $this->newLine();
        $this->info('   OU');
        $this->newLine();
        $this->info('   ./scripts/import_senat_sql.sh');
        $this->info('   → Choisir option 2 (Import essentiel)');
        $this->newLine();
        $this->info('ℹ️  La base AMELI contient ~50 000 amendements Sénat avec :');
        $this->info('   ✅ Texte législatif associé');
        $this->info('   ✅ Auteur (sénateur)');
        $this->info('   ✅ Co-signataires');
        $this->info('   ✅ Sort (adopté, rejeté, retiré, etc.)');
        $this->info('   ✅ Dispositif et exposé des motifs');
        $this->info('   ✅ Dates de dépôt et sort');

        return Command::FAILURE;
    }
}
