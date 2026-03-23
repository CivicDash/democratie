<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserMention;
use App\Notifications\UserMentionedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MentionService
{
    /**
     * Pattern pour détecter les mentions @utilisateur
     * Formats supportés: @nom, @"nom avec espaces", @[id:123]
     */
    protected const MENTION_PATTERN = '/@(?:"([^"]+)"|(\w+)|\[id:(\d+)\])/u';

    /**
     * Traite le contenu et crée les mentions
     */
    public function processContent(string $content, Model $mentionable, User $author): array
    {
        $mentions = $this->extractMentions($content);
        $createdMentions = [];

        foreach ($mentions as $mention) {
            $user = $this->resolveUser($mention);
            
            if ($user && $user->id !== $author->id) {
                $createdMention = $this->createMention($user, $author, $mentionable);
                if ($createdMention) {
                    $createdMentions[] = $createdMention;
                }
            }
        }

        return $createdMentions;
    }

    /**
     * Extrait les mentions d'un texte
     */
    public function extractMentions(string $content): array
    {
        preg_match_all(self::MENTION_PATTERN, $content, $matches, PREG_SET_ORDER);
        
        $mentions = [];
        foreach ($matches as $match) {
            if (!empty($match[3])) {
                // Format @[id:123]
                $mentions[] = ['type' => 'id', 'value' => (int) $match[3]];
            } elseif (!empty($match[1])) {
                // Format @"nom avec espaces"
                $mentions[] = ['type' => 'name', 'value' => $match[1]];
            } elseif (!empty($match[2])) {
                // Format @nom
                $mentions[] = ['type' => 'name', 'value' => $match[2]];
            }
        }

        return $mentions;
    }

    /**
     * Résout un utilisateur à partir d'une mention
     */
    protected function resolveUser(array $mention): ?User
    {
        if ($mention['type'] === 'id') {
            return User::find($mention['value']);
        }

        // Recherche par nom (insensible à la casse)
        return User::where('name', 'ILIKE', $mention['value'])->first();
    }

    /**
     * Crée une mention et notifie l'utilisateur
     */
    protected function createMention(User $user, User $author, Model $mentionable): ?UserMention
    {
        // Éviter les doublons
        $existing = UserMention::where([
            'user_id' => $user->id,
            'mentioned_by' => $author->id,
            'mentionable_type' => get_class($mentionable),
            'mentionable_id' => $mentionable->id,
        ])->first();

        if ($existing) {
            return null;
        }

        try {
            $mention = UserMention::create([
                'user_id' => $user->id,
                'mentioned_by' => $author->id,
                'mentionable_type' => get_class($mentionable),
                'mentionable_id' => $mentionable->id,
            ]);

            // Envoyer notification
            $this->notifyUser($mention);

            return $mention;
        } catch (\Exception $e) {
            Log::error('Erreur création mention', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Notifie l'utilisateur mentionné
     */
    protected function notifyUser(UserMention $mention): void
    {
        try {
            $mention->user->notify(new UserMentionedNotification($mention));
            $mention->update(['notified_at' => now()]);
        } catch (\Exception $e) {
            Log::error('Erreur notification mention', [
                'mention_id' => $mention->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Transforme les mentions en liens HTML
     */
    public function renderMentions(string $content): string
    {
        return preg_replace_callback(self::MENTION_PATTERN, function ($match) {
            $user = null;
            $displayName = '';

            if (!empty($match[3])) {
                $user = User::find((int) $match[3]);
                $displayName = $user?->name ?? "Utilisateur #{$match[3]}";
            } elseif (!empty($match[1])) {
                $displayName = $match[1];
                $user = User::where('name', 'ILIKE', $match[1])->first();
            } elseif (!empty($match[2])) {
                $displayName = $match[2];
                $user = User::where('name', 'ILIKE', $match[2])->first();
            }

            if ($user) {
                $profileUrl = route('profile.show', $user->id);
                return sprintf(
                    '<a href="%s" class="mention-link text-indigo-600 dark:text-indigo-400 font-medium hover:underline" data-user-id="%d">@%s</a>',
                    $profileUrl,
                    $user->id,
                    e($displayName)
                );
            }

            return sprintf(
                '<span class="mention-invalid text-gray-400">@%s</span>',
                e($displayName)
            );
        }, $content);
    }

    /**
     * Suggestions d'utilisateurs pour l'autocomplete
     */
    public function suggestUsers(string $query, int $limit = 10): Collection
    {
        return User::with('profile')
            ->where('name', 'ILIKE', "%{$query}%")
            ->whereNull('email_verified_at')
            ->orWhereNotNull('email_verified_at')
            ->limit($limit)
            ->get(['id', 'name'])
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->display_name,
                'mention' => "@{$user->display_name}",
            ]);
    }

    /**
     * Récupère les mentions non lues pour un utilisateur
     */
    public function getUnreadMentions(User $user, int $limit = 20): Collection
    {
        return UserMention::forUser($user->id)
            ->unread()
            ->with(['author', 'mentionable'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Marque toutes les mentions comme lues
     */
    public function markAllAsRead(User $user): int
    {
        return UserMention::forUser($user->id)
            ->unread()
            ->update(['is_read' => true]);
    }
}
