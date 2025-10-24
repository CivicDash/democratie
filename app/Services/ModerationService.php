<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Report;
use App\Models\Sanction;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Service pour gérer la modération (signalements et sanctions)
 */
class ModerationService
{
    /**
     * Crée un signalement pour un contenu.
     * 
     * @param User $reporter L'utilisateur qui signale
     * @param Model $reportable Le contenu signalé (Post, Topic, etc.)
     * @param string $reason La raison du signalement
     */
    public function createReport(User $reporter, Model $reportable, string $reason): Report
    {
        // Vérifier que le user peut créer un rapport
        if (!$reporter->can('create', Report::class)) {
            throw new RuntimeException('User cannot create reports.');
        }

        // Vérifier que le user ne signale pas son propre contenu
        if ($this->isOwnContent($reporter, $reportable)) {
            throw new RuntimeException('Cannot report own content.');
        }

        return Report::create([
            'reportable_type' => get_class($reportable),
            'reportable_id' => $reportable->id,
            'reporter_id' => $reporter->id,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }

    /**
     * Assigne un signalement à un modérateur.
     */
    public function assignReport(Report $report, User $moderator): Report
    {
        if (!$moderator->can('review', $report)) {
            throw new RuntimeException('Moderator cannot review this report.');
        }

        $report->update([
            'status' => 'reviewing',
            'moderator_id' => $moderator->id,
        ]);

        return $report->fresh();
    }

    /**
     * Résout un signalement (valide).
     */
    public function resolveReport(Report $report, User $moderator, string $notes = null, bool $applyAction = false): Report
    {
        if (!$moderator->can('resolve', $report)) {
            throw new RuntimeException('Moderator cannot resolve this report.');
        }

        return DB::transaction(function () use ($report, $moderator, $notes, $applyAction) {
            $report->update([
                'status' => 'resolved',
                'moderator_notes' => $notes,
            ]);

            // Appliquer une action si demandé (masquer le contenu)
            if ($applyAction && $report->reportable) {
                $this->applyModerationAction($report->reportable, $notes ?? 'Content violates community guidelines.');
            }

            return $report->fresh();
        });
    }

    /**
     * Rejette un signalement (non fondé).
     */
    public function rejectReport(Report $report, User $moderator, string $notes = null): Report
    {
        if (!$moderator->can('reject', $report)) {
            throw new RuntimeException('Moderator cannot reject this report.');
        }

        $report->update([
            'status' => 'rejected',
            'moderator_notes' => $notes,
        ]);

        return $report->fresh();
    }

    /**
     * Applique une action de modération sur un contenu.
     */
    protected function applyModerationAction(Model $content, string $reason): void
    {
        if ($content instanceof Post) {
            $content->update([
                'is_hidden' => true,
                'hidden_reason' => $reason,
            ]);
        } elseif ($content instanceof Topic) {
            $content->update([
                'status' => 'closed',
            ]);
        }
    }

    /**
     * Crée une sanction pour un utilisateur.
     */
    public function createSanction(
        User $targetUser,
        User $moderator,
        string $type,
        string $reason,
        ?\DateTime $expiresAt = null
    ): Sanction {
        if (!$moderator->can('create', [Sanction::class, $targetUser])) {
            throw new RuntimeException('Moderator cannot sanction this user.');
        }

        return Sanction::create([
            'user_id' => $targetUser->id,
            'moderator_id' => $moderator->id,
            'type' => $type,
            'reason' => $reason,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Crée un avertissement.
     */
    public function warnUser(User $targetUser, User $moderator, string $reason): Sanction
    {
        return $this->createSanction($targetUser, $moderator, 'warning', $reason);
    }

    /**
     * Mute un utilisateur (temporaire).
     */
    public function muteUser(User $targetUser, User $moderator, string $reason, int $durationInDays = 7): Sanction
    {
        $expiresAt = now()->addDays($durationInDays);
        return $this->createSanction($targetUser, $moderator, 'mute', $reason, $expiresAt);
    }

    /**
     * Ban un utilisateur (permanent ou temporaire).
     */
    public function banUser(User $targetUser, User $moderator, string $reason, ?int $durationInDays = null): Sanction
    {
        $expiresAt = $durationInDays ? now()->addDays($durationInDays) : null;
        return $this->createSanction($targetUser, $moderator, 'ban', $reason, $expiresAt);
    }

    /**
     * Révoque une sanction.
     */
    public function revokeSanction(Sanction $sanction, User $moderator): Sanction
    {
        if (!$moderator->can('revoke', $sanction)) {
            throw new RuntimeException('Moderator cannot revoke this sanction.');
        }

        $sanction->update([
            'revoked_at' => now(),
        ]);

        return $sanction->fresh();
    }

    /**
     * Obtient les sanctions actives d'un utilisateur.
     */
    public function getActiveSanctions(User $user): Collection
    {
        return Sanction::where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->whereNull('revoked_at')
            ->get();
    }

    /**
     * Obtient l'historique des sanctions d'un utilisateur.
     */
    public function getSanctionHistory(User $user): Collection
    {
        return Sanction::with('moderator')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtient les signalements prioritaires (multiples rapports sur le même contenu).
     */
    public function getPriorityReports(): Collection
    {
        return Report::select('reportable_type', 'reportable_id', DB::raw('COUNT(*) as report_count'))
            ->where('status', 'pending')
            ->groupBy('reportable_type', 'reportable_id')
            ->having('report_count', '>', 1)
            ->orderBy('report_count', 'desc')
            ->get()
            ->map(function ($item) {
                $reports = Report::where('reportable_type', $item->reportable_type)
                    ->where('reportable_id', $item->reportable_id)
                    ->with('reporter')
                    ->get();

                return [
                    'reportable_type' => $item->reportable_type,
                    'reportable_id' => $item->reportable_id,
                    'report_count' => $item->report_count,
                    'reports' => $reports,
                ];
            });
    }

    /**
     * Obtient les statistiques de modération.
     */
    public function getModerationStats(?int $days = 30): array
    {
        $since = now()->subDays($days);

        return [
            'reports' => [
                'total' => Report::where('created_at', '>=', $since)->count(),
                'pending' => Report::where('status', 'pending')->count(),
                'reviewing' => Report::where('status', 'reviewing')->count(),
                'resolved' => Report::where('status', 'resolved')->where('created_at', '>=', $since)->count(),
                'rejected' => Report::where('status', 'rejected')->where('created_at', '>=', $since)->count(),
            ],
            'sanctions' => [
                'total' => Sanction::where('created_at', '>=', $since)->count(),
                'warnings' => Sanction::where('type', 'warning')->where('created_at', '>=', $since)->count(),
                'mutes' => Sanction::where('type', 'mute')->where('created_at', '>=', $since)->count(),
                'bans' => Sanction::where('type', 'ban')->where('created_at', '>=', $since)->count(),
                'active' => Sanction::whereNull('revoked_at')
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->count(),
            ],
            'period' => "{$days} days",
        ];
    }

    /**
     * Obtient les modérateurs les plus actifs.
     */
    public function getTopModerators(?int $days = 30, int $limit = 10): Collection
    {
        $since = now()->subDays($days);

        $reportModerators = Report::where('status', 'resolved')
            ->where('created_at', '>=', $since)
            ->groupBy('moderator_id')
            ->select('moderator_id', DB::raw('COUNT(*) as reports_handled'))
            ->pluck('reports_handled', 'moderator_id');

        $sanctionModerators = Sanction::where('created_at', '>=', $since)
            ->groupBy('moderator_id')
            ->select('moderator_id', DB::raw('COUNT(*) as sanctions_given'))
            ->pluck('sanctions_given', 'moderator_id');

        $moderatorIds = array_unique(array_merge(
            $reportModerators->keys()->toArray(),
            $sanctionModerators->keys()->toArray()
        ));

        return collect($moderatorIds)
            ->map(function ($moderatorId) use ($reportModerators, $sanctionModerators) {
                $moderator = User::find($moderatorId);
                return [
                    'moderator' => $moderator ? $moderator->name : 'Unknown',
                    'reports_handled' => $reportModerators->get($moderatorId, 0),
                    'sanctions_given' => $sanctionModerators->get($moderatorId, 0),
                    'total_actions' => $reportModerators->get($moderatorId, 0) + $sanctionModerators->get($moderatorId, 0),
                ];
            })
            ->sortByDesc('total_actions')
            ->take($limit)
            ->values();
    }

    /**
     * Vérifie si un contenu appartient à l'utilisateur.
     */
    protected function isOwnContent(User $user, Model $content): bool
    {
        if ($content instanceof Post) {
            return $content->user_id === $user->id;
        }

        if ($content instanceof Topic) {
            return $content->author_id === $user->id;
        }

        return false;
    }

    /**
     * Masque un post et crée un rapport automatique.
     */
    public function hidePostWithReport(Post $post, User $moderator, string $reason): array
    {
        return DB::transaction(function () use ($post, $moderator, $reason) {
            // Masquer le post
            $post->update([
                'is_hidden' => true,
                'hidden_reason' => $reason,
            ]);

            // Créer ou résoudre les rapports associés
            $reports = Report::where('reportable_type', Post::class)
                ->where('reportable_id', $post->id)
                ->where('status', 'pending')
                ->get();

            foreach ($reports as $report) {
                $report->update([
                    'status' => 'resolved',
                    'moderator_id' => $moderator->id,
                    'moderator_notes' => "Post hidden: {$reason}",
                ]);
            }

            return [
                'post' => $post->fresh(),
                'reports_resolved' => $reports->count(),
            ];
        });
    }
}

