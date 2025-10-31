<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserFollow extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'followable_type',
        'followable_id',
        'notification_settings',
        'last_notified_at',
    ];

    protected $casts = [
        'notification_settings' => 'array',
        'last_notified_at' => 'datetime',
    ];

    /**
     * Relation : l'utilisateur qui suit
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation polymorphique : l'élément suivi
     */
    public function followable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Vérifier si l'utilisateur suit un élément
     */
    public static function isFollowing(int $userId, string $type, int $id): bool
    {
        return self::where('user_id', $userId)
            ->where('followable_type', $type)
            ->where('followable_id', $id)
            ->exists();
    }

    /**
     * Suivre un élément
     */
    public static function follow(int $userId, string $type, int $id, ?array $settings = null): self
    {
        return self::firstOrCreate(
            [
                'user_id' => $userId,
                'followable_type' => $type,
                'followable_id' => $id,
            ],
            [
                'notification_settings' => $settings ?? [],
            ]
        );
    }

    /**
     * Ne plus suivre un élément
     */
    public static function unfollow(int $userId, string $type, int $id): bool
    {
        return self::where('user_id', $userId)
            ->where('followable_type', $type)
            ->where('followable_id', $id)
            ->delete() > 0;
    }

    /**
     * Obtenir tous les followers d'un élément
     */
    public static function getFollowers(string $type, int $id)
    {
        return self::with('user')
            ->where('followable_type', $type)
            ->where('followable_id', $id)
            ->get();
    }

    /**
     * Mettre à jour la dernière notification
     */
    public function markNotified(): void
    {
        $this->update(['last_notified_at' => now()]);
    }

    /**
     * Vérifier si on peut notifier (éviter le spam)
     */
    public function canNotify(int $cooldownMinutes = 5): bool
    {
        if (!$this->last_notified_at) {
            return true;
        }

        return $this->last_notified_at->diffInMinutes(now()) >= $cooldownMinutes;
    }
}
