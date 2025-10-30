<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle pour les votes citoyens sur les propositions de loi
 * 
 * @property int $id
 * @property int $user_id
 * @property int $proposition_loi_id
 * @property string $type_vote (upvote|downvote)
 * @property string|null $commentaire
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class VotePropositionLoi extends Model
{
    use HasFactory;

    protected $table = 'votes_propositions_loi';

    protected $fillable = [
        'user_id',
        'proposition_loi_id',
        'type_vote',
        'commentaire',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'proposition_loi_id' => 'integer',
    ];

    const TYPE_UPVOTE = 'upvote';
    const TYPE_DOWNVOTE = 'downvote';

    // ========================================================================
    // RELATIONS
    // ========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function proposition(): BelongsTo
    {
        return $this->belongsTo(PropositionLoi::class, 'proposition_loi_id');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeUpvotes($query)
    {
        return $query->where('type_vote', self::TYPE_UPVOTE);
    }

    public function scopeDownvotes($query)
    {
        return $query->where('type_vote', self::TYPE_DOWNVOTE);
    }

    public function scopeForProposition($query, int $propositionId)
    {
        return $query->where('proposition_loi_id', $propositionId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeWithCommentaire($query)
    {
        return $query->whereNotNull('commentaire');
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getIsUpvoteAttribute(): bool
    {
        return $this->type_vote === self::TYPE_UPVOTE;
    }

    public function getIsDownvoteAttribute(): bool
    {
        return $this->type_vote === self::TYPE_DOWNVOTE;
    }

    // ========================================================================
    // MÉTHODES STATIQUES
    // ========================================================================

    /**
     * Crée ou met à jour un vote
     */
    public static function castVote(int $userId, int $propositionId, string $typeVote, ?string $commentaire = null): self
    {
        if (!in_array($typeVote, [self::TYPE_UPVOTE, self::TYPE_DOWNVOTE])) {
            throw new \InvalidArgumentException("Type de vote invalide: {$typeVote}");
        }

        $existingVote = static::where('user_id', $userId)
            ->where('proposition_loi_id', $propositionId)
            ->first();

        if ($existingVote) {
            // Décrémenter l'ancien compteur
            static::decrementPropositionCounter($propositionId, $existingVote->type_vote);
            
            // Mettre à jour le vote
            $existingVote->update([
                'type_vote' => $typeVote,
                'commentaire' => $commentaire,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
            $vote = $existingVote;
        } else {
            // Créer un nouveau vote
            $vote = static::create([
                'user_id' => $userId,
                'proposition_loi_id' => $propositionId,
                'type_vote' => $typeVote,
                'commentaire' => $commentaire,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        // Incrémenter le nouveau compteur
        static::incrementPropositionCounter($propositionId, $typeVote);

        return $vote;
    }

    /**
     * Supprime un vote
     */
    public static function removeVote(int $userId, int $propositionId): bool
    {
        $vote = static::where('user_id', $userId)
            ->where('proposition_loi_id', $propositionId)
            ->first();

        if (!$vote) {
            return false;
        }

        // Décrémenter le compteur
        static::decrementPropositionCounter($propositionId, $vote->type_vote);

        $vote->delete();

        return true;
    }

    /**
     * Récupère le vote d'un utilisateur pour une proposition
     */
    public static function getUserVote(int $userId, int $propositionId): ?self
    {
        return static::where('user_id', $userId)
            ->where('proposition_loi_id', $propositionId)
            ->first();
    }

    /**
     * Vérifie si un utilisateur a voté
     */
    public static function hasVoted(int $userId, int $propositionId): bool
    {
        return static::where('user_id', $userId)
            ->where('proposition_loi_id', $propositionId)
            ->exists();
    }

    /**
     * Récupère les statistiques de vote d'une proposition
     */
    public static function getPropositionStats(int $propositionId): array
    {
        $upvotes = static::forProposition($propositionId)->upvotes()->count();
        $downvotes = static::forProposition($propositionId)->downvotes()->count();
        $total = $upvotes + $downvotes;
        $score = $upvotes - $downvotes;

        return [
            'upvotes' => $upvotes,
            'downvotes' => $downvotes,
            'total' => $total,
            'score' => $score,
            'pourcentage_pour' => $total > 0 ? round(($upvotes / $total) * 100, 1) : 0,
            'pourcentage_contre' => $total > 0 ? round(($downvotes / $total) * 100, 1) : 0,
        ];
    }

    // ========================================================================
    // MÉTHODES PRIVÉES
    // ========================================================================

    private static function incrementPropositionCounter(int $propositionId, string $typeVote): void
    {
        $column = $typeVote === self::TYPE_UPVOTE ? 'votes_pour' : 'votes_contre';
        
        PropositionLoi::where('id', $propositionId)->increment($column);
    }

    private static function decrementPropositionCounter(int $propositionId, string $typeVote): void
    {
        $column = $typeVote === self::TYPE_UPVOTE ? 'votes_pour' : 'votes_contre';
        
        PropositionLoi::where('id', $propositionId)->decrement($column);
    }
}

