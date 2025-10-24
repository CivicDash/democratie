<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * Bulletin de vote anonyme (SANS user_id pour garantir l'anonymat)
 * 
 * ⚠️ CRITIQUE: Ce modèle ne contient AUCUNE référence à l'identité du votant
 * 
 * @property int $id
 * @property int $topic_id
 * @property string $encrypted_vote Vote chiffré
 * @property string $vote_hash Hash du vote (unicité)
 * @property \Illuminate\Support\Carbon $cast_at
 */
class TopicBallot extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // Pas de updated_at pour les bulletins

    protected $fillable = [
        'topic_id',
        'encrypted_vote',
        'vote_hash',
        'cast_at',
    ];

    protected $casts = [
        'cast_at' => 'datetime',
    ];

    /**
     * Topic associé
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Chiffre un vote
     */
    public static function encryptVote(mixed $vote): string
    {
        return Crypt::encryptString(json_encode($vote));
    }

    /**
     * Déchiffre un vote
     */
    public function decryptVote(): mixed
    {
        return json_decode(Crypt::decryptString($this->encrypted_vote), true);
    }

    /**
     * Génère un hash unique du vote
     */
    public static function hashVote(int $topicId, mixed $vote, string $nonce): string
    {
        return hash('sha256', $topicId . json_encode($vote) . $nonce . microtime(true));
    }

    /**
     * Crée un bulletin de vote anonyme
     * 
     * ⚠️ IMPORTANT: Cette méthode ne doit jamais stocker de user_id
     */
    public static function cast(int $topicId, mixed $vote): self
    {
        $nonce = bin2hex(random_bytes(16));
        
        return self::create([
            'topic_id' => $topicId,
            'encrypted_vote' => self::encryptVote($vote),
            'vote_hash' => self::hashVote($topicId, $vote, $nonce),
            'cast_at' => now(),
        ]);
    }

    /**
     * Scope: bulletins d'un topic
     */
    public function scopeForTopic($query, int $topicId)
    {
        return $query->where('topic_id', $topicId);
    }

    /**
     * Scope: bulletins dans une période
     */
    public function scopeCastBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('cast_at', [$startDate, $endDate]);
    }

    /**
     * Scope: compte les bulletins par topic
     */
    public function scopeCountByTopic($query)
    {
        return $query->selectRaw('topic_id, COUNT(*) as ballot_count')
            ->groupBy('topic_id');
    }

    /**
     * Note: Aucune méthode pour lier un bulletin à un user
     * C'est intentionnel pour garantir l'anonymat
     */
}

