<?php

namespace App\Console\Commands;

use App\Models\ListeElectorale;
use App\Notifications\CandidatureNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Envoie des rappels aux candidats avant la date limite de dépôt
 */
class SendCandidatureReminders extends Command
{
    protected $signature = 'candidatures:send-reminders {--dry-run : Simuler sans envoyer}';

    protected $description = 'Envoie des rappels aux candidats à J-7, J-3 et J-1 avant la date limite';

    private const DATE_LIMITE_DEPOT = '2026-02-27';

    private const JOURS_RAPPEL = [7, 3, 1];

    public function handle(): int
    {
        $dateLimite = Carbon::parse(self::DATE_LIMITE_DEPOT);
        $joursRestants = now()->startOfDay()->diffInDays($dateLimite, false);

        $this->info("📅 Date limite de dépôt : {$dateLimite->format('d/m/Y')}");
        $this->info("📊 Jours restants : {$joursRestants}");

        // Vérifier si c'est un jour de rappel
        if (! in_array($joursRestants, self::JOURS_RAPPEL)) {
            $this->info("ℹ️  Pas de rappel prévu aujourd'hui (rappels à J-7, J-3, J-1)");

            return self::SUCCESS;
        }

        $this->info("🔔 Envoi des rappels J-{$joursRestants}...");

        // Récupérer les listes non validées avec un créateur
        $listes = ListeElectorale::whereNotIn('statut', ['valide', 'rejete'])
            ->whereNotNull('created_by')
            ->with('createur')
            ->get();

        $this->info("📋 {$listes->count()} listes non validées trouvées");

        $dryRun = $this->option('dry-run');
        $sent = 0;
        $errors = 0;

        foreach ($listes as $liste) {
            if (! $liste->createur) {
                continue;
            }

            try {
                if ($dryRun) {
                    $this->line("  [DRY-RUN] Rappel pour {$liste->createur->email} - {$liste->nom_liste}");
                } else {
                    $liste->createur->notify(new CandidatureNotification(
                        $liste,
                        'rappel_depot'
                    ));
                    $this->line("  ✓ Rappel envoyé à {$liste->createur->email}");
                }
                $sent++;
            } catch (\Exception $e) {
                $this->error("  ✗ Erreur pour {$liste->createur->email}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Rappels envoyés', $sent],
                ['Erreurs', $errors],
                ['Mode', $dryRun ? 'Simulation' : 'Réel'],
            ]
        );

        return self::SUCCESS;
    }
}
