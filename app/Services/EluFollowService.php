<?php

namespace App\Services;

use App\Models\ActeurAN;
use App\Models\EluFollower;
use App\Models\Maire;
use App\Models\Notification;
use App\Models\PoliticalPerson;
use App\Models\Senateur;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EluFollowService
{
    /**
     * Suivre un élu
     */
    public function follow(User $user, string $eluType, string $eluId, array $preferences = []): EluFollower
    {
        // Récupérer les infos de l'élu
        $eluInfo = $this->getEluInfo($eluType, $eluId);

        if (!$eluInfo) {
            throw new \InvalidArgumentException("Élu non trouvé: {$eluType}/{$eluId}");
        }

        // Préférences par défaut
        $defaultPrefs = [
            'notify_votes' => true,
            'notify_interventions' => true,
            'notify_amendements' => false,
            'notify_propositions' => true,
            'notify_rapports' => false,
            'notify_commissions' => false,
            'notify_actualites' => true,
            'notify_site' => true,
            'notify_email' => false,
            'email_frequency' => 'instant',
        ];

        $prefs = array_merge($defaultPrefs, $preferences);

        return EluFollower::updateOrCreate(
            [
                'user_id' => $user->id,
                'elu_type' => $eluType,
                'elu_id' => $eluId,
            ],
            array_merge($prefs, [
                'elu_nom' => $eluInfo['nom'],
                'elu_photo_url' => $eluInfo['photo_url'],
                'elu_groupe' => $eluInfo['groupe'],
                'elu_circonscription' => $eluInfo['circonscription'],
                'followed_at' => now(),
            ])
        );
    }

    /**
     * Ne plus suivre un élu
     */
    public function unfollow(User $user, string $eluType, string $eluId): bool
    {
        return EluFollower::where('user_id', $user->id)
            ->where('elu_type', $eluType)
            ->where('elu_id', $eluId)
            ->delete() > 0;
    }

    /**
     * Mettre à jour les préférences de suivi
     */
    public function updatePreferences(User $user, string $eluType, string $eluId, array $preferences): ?EluFollower
    {
        $follower = EluFollower::where('user_id', $user->id)
            ->where('elu_type', $eluType)
            ->where('elu_id', $eluId)
            ->first();

        if (!$follower) {
            return null;
        }

        $follower->update($preferences);
        return $follower->fresh();
    }

    /**
     * Vérifier si un utilisateur suit un élu
     */
    public function isFollowing(User $user, string $eluType, string $eluId): bool
    {
        return EluFollower::where('user_id', $user->id)
            ->where('elu_type', $eluType)
            ->where('elu_id', $eluId)
            ->exists();
    }

    /**
     * Obtenir le suivi d'un élu par un utilisateur
     */
    public function getFollowing(User $user, string $eluType, string $eluId): ?EluFollower
    {
        return EluFollower::where('user_id', $user->id)
            ->where('elu_type', $eluType)
            ->where('elu_id', $eluId)
            ->first();
    }

    /**
     * Obtenir tous les élus suivis par un utilisateur
     */
    public function getFollowedElus(User $user): Collection
    {
        return EluFollower::where('user_id', $user->id)
            ->orderByDesc('followed_at')
            ->get();
    }

    /**
     * Obtenir les followers d'un élu
     */
    public function getFollowers(string $eluType, string $eluId): Collection
    {
        return EluFollower::with('user')
            ->where('elu_type', $eluType)
            ->where('elu_id', $eluId)
            ->get();
    }

    /**
     * Obtenir les followers intéressés par un type d'activité
     */
    public function getInterestedFollowers(string $eluType, string $eluId, string $activityType): Collection
    {
        return EluFollower::with('user')
            ->forElu($eluType, $eluId)
            ->interestedIn($activityType)
            ->get();
    }

    /**
     * Notifier les followers d'une activité
     */
    public function notifyActivity(
        string $eluType,
        string $eluId,
        string $activityType,
        string $activityId,
        string $title,
        string $message,
        ?string $url = null
    ): int {
        $followers = $this->getInterestedFollowers($eluType, $eluId, $activityType);
        $notifiedCount = 0;

        foreach ($followers as $follower) {
            // Éviter les doublons
            if ($follower->wasNotified($activityType, $activityId)) {
                continue;
            }

            $notificationId = null;

            // Notification in-app
            if ($follower->notify_site) {
                $notification = Notification::create([
                    'user_id' => $follower->user_id,
                    'category' => 'elu_activity',
                    'title' => $title,
                    'message' => $message,
                    'icon' => EluFollower::ACTIVITY_TYPES[$activityType]['icon'] ?? '📢',
                    'url' => $url,
                    'data' => [
                        'elu_type' => $eluType,
                        'elu_id' => $eluId,
                        'elu_nom' => $follower->elu_nom,
                        'activity_type' => $activityType,
                        'activity_id' => $activityId,
                    ],
                ]);
                $notificationId = $notification->id;
            }

            // Marquer comme notifié
            $emailSent = false; // TODO: implémenter l'envoi email selon email_frequency

            $follower->markAsNotified($activityType, $activityId, $notificationId, $emailSent);
            $notifiedCount++;
        }

        return $notifiedCount;
    }

    /**
     * Obtenir les informations d'un élu
     */
    public function getEluInfo(string $eluType, string $eluId): ?array
    {
        return match($eluType) {
            'depute' => $this->getDeputeInfo($eluId),
            'senateur' => $this->getSenateurInfo($eluId),
            'maire' => $this->getMaireInfo($eluId),
            'ministre' => $this->getMinistreInfo($eluId),
            default => null,
        };
    }

    private function getDeputeInfo(string $uid): ?array
    {
        $depute = ActeurAN::find($uid);
        if (!$depute) return null;

        // Récupérer le groupe politique via mandats_an
        $groupeLibelle = null;
        try {
            $groupe = DB::table('mandats_an')
                ->join('organes_an', 'mandats_an.organe_ref', '=', 'organes_an.uid')
                ->where('mandats_an.acteur_ref', $uid)
                ->where('organes_an.code_type', 'GP')
                ->whereNull('mandats_an.date_fin')
                ->select('organes_an.libelle_abrege')
                ->first();
            $groupeLibelle = $groupe?->libelle_abrege;
        } catch (\Exception $e) {
            // Ignorer les erreurs SQL, on continue sans le groupe
        }

        // Récupérer la circonscription
        $circonscription = null;
        try {
            $mandat = DB::table('mandats_an')
                ->join('organes_an', 'mandats_an.organe_ref', '=', 'organes_an.uid')
                ->where('mandats_an.acteur_ref', $uid)
                ->where('organes_an.code_type', 'CIRCO')
                ->whereNull('mandats_an.date_fin')
                ->select('organes_an.libelle')
                ->first();
            $circonscription = $mandat?->libelle;
        } catch (\Exception $e) {
            // Ignorer les erreurs SQL
        }

        return [
            'nom' => trim($depute->prenom . ' ' . $depute->nom),
            'photo_url' => $depute->photo_wikipedia_url ?? "https://www.assemblee-nationale.fr/dyn/deputes/{$uid}/image",
            'groupe' => $groupeLibelle,
            'circonscription' => $circonscription,
        ];
    }

    private function getSenateurInfo(string $matricule): ?array
    {
        $senateur = Senateur::find($matricule);
        if (!$senateur) return null;

        return [
            'nom' => trim($senateur->prenom . ' ' . $senateur->nom),
            'photo_url' => $senateur->photo_wikipedia_url ?? "https://www.senat.fr/senimg/senateur_{$matricule}.jpg",
            'groupe' => $senateur->groupe_sigle,
            'circonscription' => $senateur->circonscription,
        ];
    }

    private function getMaireInfo(int $id): ?array
    {
        $maire = Maire::with('ville')->find($id);
        if (!$maire) return null;

        return [
            'nom' => trim($maire->prenom . ' ' . $maire->nom),
            'photo_url' => $maire->photo_url,
            'groupe' => $maire->nuance_politique,
            'circonscription' => $maire->ville?->nom,
        ];
    }

    private function getMinistreInfo(int $id): ?array
    {
        $ministre = PoliticalPerson::find($id);
        if (!$ministre) return null;

        return [
            'nom' => $ministre->full_name,
            'photo_url' => $ministre->photo_url,
            'groupe' => null,
            'circonscription' => null, // TODO: récupérer la fonction actuelle
        ];
    }

    /**
     * Statistiques de suivi pour un élu
     */
    public function getEluStats(string $eluType, string $eluId): array
    {
        $followersCount = EluFollower::forElu($eluType, $eluId)->count();
        
        $preferences = EluFollower::forElu($eluType, $eluId)
            ->select(
                DB::raw('SUM(CASE WHEN notify_votes THEN 1 ELSE 0 END) as votes'),
                DB::raw('SUM(CASE WHEN notify_interventions THEN 1 ELSE 0 END) as interventions'),
                DB::raw('SUM(CASE WHEN notify_propositions THEN 1 ELSE 0 END) as propositions'),
                DB::raw('SUM(CASE WHEN notify_email THEN 1 ELSE 0 END) as email_subscribers')
            )
            ->first();

        return [
            'followers_count' => $followersCount,
            'preferences' => [
                'votes' => (int) $preferences?->votes ?? 0,
                'interventions' => (int) $preferences?->interventions ?? 0,
                'propositions' => (int) $preferences?->propositions ?? 0,
                'email_subscribers' => (int) $preferences?->email_subscribers ?? 0,
            ],
        ];
    }
}
