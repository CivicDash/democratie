<?php

namespace App\Services;

use App\Mail\EluActivityDigestMail;
use App\Models\ActeurAN;
use App\Models\EluActivityNotification;
use App\Models\EluFollower;
use App\Models\Notification;
use App\Models\ScrutinAN;
use App\Models\VoteIndividuelAN;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EluActivityNotificationService
{
    /**
     * Types d'activités et leurs sources de données
     */
    private const ACTIVITY_SOURCES = [
        'votes' => [
            'model' => VoteIndividuelAN::class,
            'elu_field' => 'acteur_uid',
            'date_field' => 'created_at',
            'title_callback' => 'getVoteTitle',
        ],
        // À étendre avec d'autres types (interventions, amendements, etc.)
    ];

    /**
     * Détecter les nouvelles activités pour tous les élus suivis
     */
    public function detectNewActivities(\DateTime $since = null): Collection
    {
        $since = $since ?? now()->subDay();
        $activities = collect();

        // Récupérer tous les députés suivis
        $followedDeputes = EluFollower::where('elu_type', 'depute')
            ->select('elu_id')
            ->distinct()
            ->pluck('elu_id');

        if ($followedDeputes->isEmpty()) {
            return $activities;
        }

        // Détecter les nouveaux votes
        $newVotes = $this->detectNewVotes($followedDeputes, $since);
        $activities = $activities->merge($newVotes);

        // TODO: Ajouter d'autres types d'activités
        // $activities = $activities->merge($this->detectNewInterventions($followedDeputes, $since));
        // $activities = $activities->merge($this->detectNewAmendements($followedDeputes, $since));

        return $activities;
    }

    /**
     * Détecter les nouveaux votes des députés suivis
     */
    private function detectNewVotes(Collection $deputeUids, \DateTime $since): Collection
    {
        $votes = DB::table('votes_individuels_an as v')
            ->join('scrutins_an as s', 'v.scrutin_id', '=', 's.id')
            ->join('acteurs_an as a', 'v.acteur_uid', '=', 'a.uid')
            ->whereIn('v.acteur_uid', $deputeUids)
            ->where('s.date_scrutin', '>=', $since->format('Y-m-d'))
            ->select([
                'v.acteur_uid as elu_id',
                DB::raw("'depute' as elu_type"),
                DB::raw("'votes' as activity_type"),
                's.id as activity_id',
                's.date_scrutin as activity_date',
                's.titre as activity_title',
                'v.position as activity_detail',
                DB::raw("CONCAT(a.prenom, ' ', a.nom) as elu_nom"),
            ])
            ->orderByDesc('s.date_scrutin')
            ->get();

        return $votes->map(function ($vote) {
            return [
                'elu_type' => $vote->elu_type,
                'elu_id' => $vote->elu_id,
                'elu_nom' => $vote->elu_nom,
                'activity_type' => $vote->activity_type,
                'activity_id' => $vote->activity_id,
                'activity_date' => $vote->activity_date,
                'activity_title' => $vote->activity_title,
                'activity_detail' => $this->formatVotePosition($vote->activity_detail),
                'activity_icon' => '🗳️',
                'activity_url' => route('representants.deputes.scrutins.show', $vote->activity_id),
            ];
        });
    }

    /**
     * Formater la position de vote
     */
    private function formatVotePosition(?string $position): string
    {
        return match ($position) {
            'pour' => '✅ A voté Pour',
            'contre' => '❌ A voté Contre',
            'abstention' => '⚪ S\'est abstenu',
            'non_votant' => '➖ Non votant',
            default => 'Position inconnue',
        };
    }

    /**
     * Notifier les followers d'une activité
     */
    public function notifyFollowers(array $activity): int
    {
        $notifiedCount = 0;

        // Trouver les followers intéressés par ce type d'activité
        $followers = EluFollower::where('elu_type', $activity['elu_type'])
            ->where('elu_id', $activity['elu_id'])
            ->where("notify_{$activity['activity_type']}", true)
            ->with('user')
            ->get();

        foreach ($followers as $follower) {
            // Vérifier si déjà notifié pour cette activité
            $alreadyNotified = EluActivityNotification::where('elu_follower_id', $follower->id)
                ->where('activity_type', $activity['activity_type'])
                ->where('activity_id', $activity['activity_id'])
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            // Créer la notification in-app si activée
            $notification = null;
            if ($follower->notify_site) {
                $notification = $this->createSiteNotification($follower, $activity);
            }

            // Enregistrer la notification d'activité
            $activityNotif = EluActivityNotification::create([
                'elu_follower_id' => $follower->id,
                'activity_type' => $activity['activity_type'],
                'activity_id' => $activity['activity_id'],
                'notification_id' => $notification?->id,
                'email_sent' => false,
                'notified_at' => now(),
            ]);

            // Envoyer email si fréquence "instant"
            if ($follower->notify_email && $follower->email_frequency === 'instant') {
                $this->sendInstantEmail($follower, $activity);
                $activityNotif->update(['email_sent' => true]);
            }

            // Mettre à jour le compteur
            $follower->increment('notifications_received');
            $follower->update(['last_activity_notified_at' => now()]);

            $notifiedCount++;
        }

        return $notifiedCount;
    }

    /**
     * Créer une notification sur le site
     */
    private function createSiteNotification(EluFollower $follower, array $activity): Notification
    {
        return Notification::create([
            'user_id' => $follower->user_id,
            'type' => 'elu_activity',
            'title' => "Nouvelle activité de {$activity['elu_nom']}",
            'message' => "{$activity['activity_icon']} {$activity['activity_title']}",
            'data' => [
                'elu_type' => $activity['elu_type'],
                'elu_id' => $activity['elu_id'],
                'activity_type' => $activity['activity_type'],
                'activity_id' => $activity['activity_id'],
                'activity_detail' => $activity['activity_detail'],
            ],
            'url' => $activity['activity_url'] ?? null,
            'read' => false,
        ]);
    }

    /**
     * Envoyer un email instantané
     */
    private function sendInstantEmail(EluFollower $follower, array $activity): void
    {
        try {
            Mail::to($follower->user->email)->send(
                new EluActivityDigestMail(
                    $follower->user,
                    collect([$activity]),
                    'instant'
                )
            );
        } catch (\Exception $e) {
            Log::error("Erreur envoi email activité élu", [
                'user_id' => $follower->user_id,
                'elu_id' => $follower->elu_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envoyer les digests (quotidien ou hebdomadaire)
     */
    public function sendDigests(string $frequency): int
    {
        $sentCount = 0;

        // Trouver les followers avec cette fréquence et des notifications en attente
        $followers = EluFollower::where('notify_email', true)
            ->where('email_frequency', $frequency)
            ->whereHas('activityNotifications', function ($q) {
                $q->where('email_sent', false);
            })
            ->with(['user', 'activityNotifications' => function ($q) {
                $q->where('email_sent', false)->orderByDesc('notified_at');
            }])
            ->get()
            ->groupBy('user_id');

        foreach ($followers as $userId => $userFollowers) {
            $user = $userFollowers->first()->user;
            $allActivities = collect();

            foreach ($userFollowers as $follower) {
                foreach ($follower->activityNotifications as $notif) {
                    $allActivities->push($this->rebuildActivityFromNotification($notif, $follower));
                }
            }

            if ($allActivities->isEmpty()) {
                continue;
            }

            try {
                Mail::to($user->email)->send(
                    new EluActivityDigestMail($user, $allActivities, $frequency)
                );

                // Marquer comme envoyé
                foreach ($userFollowers as $follower) {
                    $follower->activityNotifications()
                        ->where('email_sent', false)
                        ->update(['email_sent' => true]);
                }

                $sentCount++;
            } catch (\Exception $e) {
                Log::error("Erreur envoi digest {$frequency}", [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sentCount;
    }

    /**
     * Reconstruire les infos d'activité depuis une notification
     */
    private function rebuildActivityFromNotification(EluActivityNotification $notif, EluFollower $follower): array
    {
        // Récupérer les détails selon le type
        $activity = [
            'elu_type' => $follower->elu_type,
            'elu_id' => $follower->elu_id,
            'elu_nom' => $follower->elu_nom,
            'activity_type' => $notif->activity_type,
            'activity_id' => $notif->activity_id,
            'activity_date' => $notif->notified_at,
            'activity_icon' => EluFollower::ACTIVITY_TYPES[$notif->activity_type]['icon'] ?? '📌',
        ];

        // Enrichir selon le type
        if ($notif->activity_type === 'votes') {
            $scrutin = ScrutinAN::find($notif->activity_id);
            if ($scrutin) {
                $activity['activity_title'] = $scrutin->titre;
                $activity['activity_url'] = route('representants.deputes.scrutins.show', $scrutin->id);
            }
        }

        return $activity;
    }

    /**
     * Traiter toutes les nouvelles activités et notifier
     */
    public function processNewActivities(\DateTime $since = null): array
    {
        $activities = $this->detectNewActivities($since);
        $stats = [
            'activities_found' => $activities->count(),
            'notifications_sent' => 0,
        ];

        foreach ($activities as $activity) {
            $stats['notifications_sent'] += $this->notifyFollowers($activity);
        }

        return $stats;
    }
}
