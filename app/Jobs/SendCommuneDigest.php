<?php

namespace App\Jobs;

use App\Models\CommuneAbonnement;
use App\Models\CommunePage;
use App\Models\User;
use App\Notifications\CommuneDigestNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCommuneDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 900;

    public function handle(): void
    {
        $abonnements = CommuneAbonnement::where('notif_email', true)
            ->get()
            ->groupBy('user_id');

        foreach ($abonnements as $userId => $userAbos) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }

            $digestData = [];

            foreach ($userAbos as $abo) {
                $page = CommunePage::with('ville')->where('code_insee', $abo->commune_code_insee)->first();
                if (! $page) {
                    continue;
                }

                $articles = [];
                if ($abo->notif_actus && $page->actus_actives) {
                    $articles = $page->articles()
                        ->publies()
                        ->where('publie_at', '>=', now()->subWeek())
                        ->recents()
                        ->limit(5)
                        ->get()
                        ->map(fn ($a) => [
                            'titre' => $a->titre,
                            'slug' => $a->slug,
                            'extrait' => $a->extrait_auto,
                            'url' => url("/commune-hub/{$page->code_insee}/actualites/{$a->slug}"),
                        ])
                        ->toArray();
                }

                $evenements = [];
                if ($abo->notif_evenements && $page->evenements_actifs) {
                    $evenements = $page->evenements()
                        ->publies()
                        ->where('date_debut', '>=', now())
                        ->where('date_debut', '<=', now()->addWeeks(2))
                        ->prochains()
                        ->limit(5)
                        ->get()
                        ->map(fn ($e) => [
                            'titre' => $e->titre,
                            'slug' => $e->slug,
                            'date' => $e->date_debut->format('d/m/Y H:i'),
                            'lieu' => $e->lieu_nom,
                            'url' => url("/commune-hub/{$page->code_insee}/evenements/{$e->slug}"),
                        ])
                        ->toArray();
                }

                $forumCount = 0;
                if ($abo->notif_forum && $page->forum_actif) {
                    $forumCount = $page->topics()
                        ->where('status', 'published')
                        ->where('created_at', '>=', now()->subWeek())
                        ->count();
                }

                if (! empty($articles) || ! empty($evenements) || $forumCount > 0) {
                    $digestData[] = [
                        'commune_nom' => $page->ville?->nom ?? $page->code_insee,
                        'code_insee' => $page->code_insee,
                        'articles' => $articles,
                        'evenements' => $evenements,
                        'forum_nouveaux' => $forumCount,
                    ];
                }
            }

            if (! empty($digestData)) {
                $user->notify(new CommuneDigestNotification($digestData));
            }
        }
    }
}
