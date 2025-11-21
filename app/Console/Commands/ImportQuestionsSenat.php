<?php

namespace App\Console\Commands;

use App\Models\SenateurQuestion;
use App\Models\Senateur;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportQuestionsSenat extends Command
{
    protected $signature = 'import:questions-senat
                            {--fresh : Vider la table avant import}
                            {--limit= : Limite du nombre de questions (pour tests)}';

    protected $description = 'Importe les questions au Gouvernement des sénateurs depuis data.senat.fr';

    private int $imported = 0;
    private int $updated = 0;
    private int $skipped = 0;
    private int $errors = 0;

    /**
     * API data.senat.fr - Questions au Gouvernement
     * Source : API JSON REST endpoint ou base SQL
     */
    public function handle(): int
    {
        $fresh = $this->option('fresh');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->info("🏛️  Import des questions au Gouvernement Sénat...");

        if ($fresh) {
            $this->warn("⚠️  Mode --fresh : suppression des questions existantes...");
            SenateurQuestion::truncate();
        }

        if ($limit) {
            $this->warn("⚠️  Mode TEST : {$limit} questions maximum");
        }

        // Note: Les questions sont disponibles via la base SQL questions.zip
        $this->error("❌ Import manuel non disponible. Utilisez la base SQL Questions :");
        $this->newLine();
        $this->info("📦 Commande recommandée :");
        $this->info("   php artisan import:senat-sql questions --fresh");
        $this->newLine();
        $this->info("   OU");
        $this->newLine();
        $this->info("   ./scripts/import_senat_sql.sh");
        $this->info("   → Choisir option 3 (Import complet) ou 4 (Import intégral)");
        $this->newLine();
        $this->info("ℹ️  La base Questions contient ~30 000 questions avec :");
        $this->info("   ✅ Type de question (écrite, orale, QAG...)");
        $this->info("   ✅ Auteur (sénateur)");
        $this->info("   ✅ Ministre destinataire");
        $this->info("   ✅ Objet et texte de la question");
        $this->info("   ✅ Réponse ministérielle");
        $this->info("   ✅ Dates de dépôt et réponse");

        return Command::FAILURE;
    }
}
