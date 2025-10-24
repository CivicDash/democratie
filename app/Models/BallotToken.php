<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Jeton éphémère pour voter (à usage unique)
 * 
 * @property int $id
 * @property int $topic_id
 * @property int $user_id
 * @property string $token Jeton opaque signé
 * @property bool $consumed
 * @property \Illuminate\Support\Carbon|null $consumed_at
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class BallotToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'user_id',
        'token',
        'consumed',
        'consumed_at',
        'expires_at',
    ];

    protected $casts = [
        'consumed' => 'boolean',
        'consumed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Topic associé
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Utilisateur associé
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Génère un token unique signé
     */
    public static function generateToken(): string
    {
        return hash('sha512', Str::random(64) . microtime(true) . config('app.key'));
    }

    /**
     * Vérifie si le token est valide (non consommé et non expiré)
     */
    public function isValid(): bool
    {
        return !$this->consumed && now()->lt($this->expires_at);
    }

    /**
     * Vérifie si le token est expiré
     */
    public function isExpired(): bool
    {
        return now()->gte($this->expires_at);
    }

    /**
     * Consomme le token (après vote)
     */
    public function consume(): void
    {
        $this->update([
            'consumed' => true,
            'consumed_at' => now(),
        ]);
    }

    /**
     * Scope: tokens valides (non consommés et non expirés)
     */
    public function scopeValid($query)
    {
        return $query->where('consumed', false)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope: tokens consommés
     */
    public function scopeConsumed($query)
    {
        return $query->where('consumed', true);
    }

    /**
     * Scope: tokens expirés
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope: tokens d'un topic
     */
    public function scopeForTopic($query, int $topicId)
    {
        return $query->where('topic_id', $topicId);
    }

    /**
     * Scope: token d'un user pour un topic
     */
    public function scopeForUserAndTopic($query, int $userId, int $topicId)
    {
        return $query->where('user_id', $userId)
            ->where('topic_id', $topicId);
    }
}

