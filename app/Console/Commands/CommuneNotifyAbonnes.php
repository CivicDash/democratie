<?php

namespace App\Console\Commands;

use App\Models\CommuneAbonnement;
use App\Models\CommuneArticle;
use App\Models\CommuneEvenement;
use App\Notifications\CommuneNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CommuneNotifyAbonnes extends Command
{
    protected $signature = 'communes:notify-abonnes
                            {--type=all : Type de notification (actus, evenements, all)}
                            {--since= : Depuis quand (defaut: 24h)}
                            {--dry-run : Simule sans envoyer}';

    protected $description = 'Envoie les notifications aux abonnes des communes (nouvelles actus, evenements)';

    public function handle(): int
    {
        $type = $this->option('type');
        $since = $this->option('since')
            ? \Carbon\Carbon::parse($this->option('since'))
            : now()->subDay();
        $dryRun = $this->option('dry-run');

        $this->info("Notifications communes depuis {$since->format('d/m/Y H:i')}");

        $totalNotifs = 0;

        if (in_array($type, ['actus', 'all'])) {
            $totalNotifs += $this->notifierActus($since, $dryRun);
        }

        if (in_array($type, ['evenements', 'all'])) {
            $totalNotifs += $this->notifierEvenements($since, $dryRun);
        }

        $this->newLine();
        $this->info('Total notifications '.($dryRun ? '(simulation)' : 'envoyees').": {$totalNotifs}");

        return self::SUCCESS;
    }

    private function notifierActus(\Carbon\Carbon $since, bool $dryRun): int
    {
        $articles = CommuneArticle::with('communePage.ville')
            ->publies()
            ->where('publie_at', '>=', $since)
            ->get();

        if ($articles->isEmpty()) {
            $this->line('  Aucun nouvel article');

            return 0;
        }

        $this->info("  {$articles->count()} nouveaux articles");
        $count = 0;

        foreach ($articles as $article) {
            $abonnes = CommuneAbonnement::parCommune($article->communePage->code_insee)
                ->veutActus()
                ->with('user')
                ->get();

            foreach ($abonnes as $abonnement) {
                if ($dryRun) {
                    $this->line("    -> [{$abonnement->user->name}] {$article->titre}");
                } else {
                    try {
                        $abonnement->user->notify(new CommuneNotification(
                            'nouvel_article',
                            $article->communePage,
                            $article
                        ));
                    } catch (\Exception $e) {
                        Log::warning('CommuneNotifyAbonnes: erreur notification', [
                            'user_id' => $abonnement->user_id,
                            'article_id' => $article->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                $count++;
            }
        }

        return $count;
    }

    private function notifierEvenements(\Carbon\Carbon $since, bool $dryRun): int
    {
        $evenements = CommuneEvenement::with('communePage.ville')
            ->publies()
            ->aVenir()
            ->where('created_at', '>=', $since)
            ->get();

        if ($evenements->isEmpty()) {
            $this->line('  Aucun nouvel evenement');

            return 0;
        }

        $this->info("  {$evenements->count()} nouveaux evenements");
        $count = 0;

        foreach ($evenements as $evenement) {
            $abonnes = CommuneAbonnement::parCommune($evenement->communePage->code_insee)
                ->veutEvenements()
                ->with('user')
                ->get();

            foreach ($abonnes as $abonnement) {
                if ($dryRun) {
                    $this->line("    -> [{$abonnement->user->name}] {$evenement->titre}");
                } else {
                    try {
                        $abonnement->user->notify(new CommuneNotification(
                            'nouvel_evenement',
                            $evenement->communePage,
                            $evenement
                        ));
                    } catch (\Exception $e) {
                        Log::warning('CommuneNotifyAbonnes: erreur notification', [
                            'user_id' => $abonnement->user_id,
                            'evenement_id' => $evenement->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                $count++;
            }
        }

        return $count;
    }
}
