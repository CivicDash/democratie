<?php

namespace App\Console\Commands;

use App\Models\AffaireJudiciaire;
use App\Models\User;
use App\Notifications\AffairesEnAttenteNotification;
use Illuminate\Console\Command;

class AffairesNotifyModerators extends Command
{
    protected $signature = 'affaires:notify-moderators';

    protected $description = 'Notifie les modérateurs quand des affaires sont en attente de validation';

    public function handle(): int
    {
        $enAttente = AffaireJudiciaire::enAttente()->count();
        $contestees = AffaireJudiciaire::where('statut_validation', 'conteste')
            ->where('updated_at', '<', now()->subHours(72))
            ->count();

        if ($enAttente === 0 && $contestees === 0) {
            $this->info('Aucune affaire en attente.');

            return self::SUCCESS;
        }

        $moderators = User::role('admin')->get();

        if ($moderators->isEmpty()) {
            $this->warn('Aucun modérateur trouvé.');

            return self::SUCCESS;
        }

        $message = [];
        if ($enAttente > 0) {
            $message[] = "{$enAttente} affaire(s) en attente de validation";
        }
        if ($contestees > 0) {
            $message[] = "{$contestees} contestation(s) non traitée(s) (>72h)";
        }

        $this->info('Notification envoyée à '.$moderators->count().' modérateur(s) : '.implode(', ', $message));

        foreach ($moderators as $moderator) {
            try {
                $moderator->notify(new AffairesEnAttenteNotification($enAttente, $contestees));
            } catch (\Exception $e) {
                $this->warn("Impossible de notifier {$moderator->email}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
