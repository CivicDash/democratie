<?php

namespace App\Console\Commands;

use App\Services\EluActivityNotificationService;
use Illuminate\Console\Command;

class ProcessEluActivityNotifications extends Command
{
    protected $signature = 'elu:process-activities 
                            {--since= : Date depuis laquelle chercher (format Y-m-d H:i:s)}
                            {--digest= : Envoyer les digests (daily|weekly)}
                            {--dry-run : Simuler sans envoyer}';

    protected $description = 'Détecte les nouvelles activités des élus suivis et envoie les notifications';

    public function handle(EluActivityNotificationService $service): int
    {
        $this->info('🔍 Recherche des nouvelles activités des élus suivis...');

        // Déterminer la date de début
        $since = $this->option('since') 
            ? new \DateTime($this->option('since'))
            : now()->subDay();

        $this->line("   📅 Depuis: {$since->format('d/m/Y H:i')}");

        // Mode digest uniquement
        if ($digest = $this->option('digest')) {
            return $this->processDigests($service, $digest);
        }

        // Traiter les nouvelles activités
        if ($this->option('dry-run')) {
            return $this->dryRun($service, $since);
        }

        $stats = $service->processNewActivities($since);

        $this->newLine();
        $this->info('📊 Résultats:');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Activités détectées', $stats['activities_found']],
                ['Notifications envoyées', $stats['notifications_sent']],
            ]
        );

        if ($stats['notifications_sent'] > 0) {
            $this->info("✅ {$stats['notifications_sent']} notification(s) envoyée(s)");
        } else {
            $this->line('ℹ️  Aucune nouvelle notification à envoyer');
        }

        return Command::SUCCESS;
    }

    /**
     * Mode simulation
     */
    private function dryRun(EluActivityNotificationService $service, \DateTime $since): int
    {
        $this->warn('🔸 Mode simulation (dry-run)');

        $activities = $service->detectNewActivities($since);

        if ($activities->isEmpty()) {
            $this->info('ℹ️  Aucune nouvelle activité détectée');
            return Command::SUCCESS;
        }

        $this->info("📋 {$activities->count()} activité(s) détectée(s):");
        $this->newLine();

        foreach ($activities->groupBy('elu_nom') as $eluNom => $eluActivities) {
            $this->line("  👤 {$eluNom}");
            foreach ($eluActivities as $activity) {
                $icon = $activity['activity_icon'] ?? '📌';
                $type = ucfirst($activity['activity_type']);
                $title = \Str::limit($activity['activity_title'] ?? '', 50);
                $this->line("     {$icon} [{$type}] {$title}");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Envoyer les digests
     */
    private function processDigests(EluActivityNotificationService $service, string $frequency): int
    {
        if (!in_array($frequency, ['daily', 'weekly'])) {
            $this->error("❌ Fréquence invalide: {$frequency}. Utilisez 'daily' ou 'weekly'");
            return Command::FAILURE;
        }

        $label = $frequency === 'daily' ? 'quotidiens' : 'hebdomadaires';
        $this->info("📧 Envoi des digests {$label}...");

        if ($this->option('dry-run')) {
            $this->warn('🔸 Mode simulation - aucun email envoyé');
            return Command::SUCCESS;
        }

        $sentCount = $service->sendDigests($frequency);

        if ($sentCount > 0) {
            $this->info("✅ {$sentCount} digest(s) envoyé(s)");
        } else {
            $this->line('ℹ️  Aucun digest à envoyer');
        }

        return Command::SUCCESS;
    }
}
