<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vote citoyen sur une loi
 *
 * Permet aux utilisateurs de voter Pour ou Contre une loi
 * et de comparer avec le vote parlementaire
 */
class CitizenLawVote extends Model
{
    protected $table = 'citizen_law_votes';

    protected $fillable = [
        'user_id',
        'loi_cod',
        'legislature',
        'vote',
    ];

    protected $casts = [
        'vote' => 'integer',
        'legislature' => 'integer',
    ];

    // =========================================================================
    // RELATIONS
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loi(): BelongsTo
    {
        return $this->belongsTo(Loi::class, 'loi_cod', 'loicod');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopePour($query)
    {
        return $query->where('vote', 1);
    }

    public function scopeContre($query)
    {
        return $query->where('vote', -1);
    }

    public function scopeForLoi($query, string $loiCod)
    {
        return $query->where('loi_cod', $loiCod);
    }

    // =========================================================================
    // HELPERS STATIQUES
    // =========================================================================

    /**
     * Enregistre ou met à jour un vote
     */
    public static function castVote(int $userId, string $loiCod, int $vote, ?int $legislature = null): self
    {
        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'loi_cod' => $loiCod,
                'legislature' => $legislature,
            ],
            [
                'vote' => $vote,
            ]
        );
    }

    /**
     * Récupère les statistiques de vote pour une loi
     */
    public static function getStatsForLoi(string $loiCod): array
    {
        $votes = self::where('loi_cod', $loiCod)->get();

        $pour = $votes->where('vote', 1)->count();
        $contre = $votes->where('vote', -1)->count();
        $total = $pour + $contre;

        return [
            'pour' => $pour,
            'contre' => $contre,
            'total' => $total,
            'pct_pour' => $total > 0 ? round(($pour / $total) * 100, 1) : 0,
            'pct_contre' => $total > 0 ? round(($contre / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Récupère le vote d'un utilisateur sur une loi
     */
    public static function getUserVote(int $userId, string $loiCod): ?int
    {
        $vote = self::where('user_id', $userId)
            ->where('loi_cod', $loiCod)
            ->first();

        return $vote?->vote;
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    public function getVoteLabelAttribute(): string
    {
        return match ($this->vote) {
            1 => 'Pour',
            -1 => 'Contre',
            default => 'Inconnu',
        };
    }

    public function getVoteIconAttribute(): string
    {
        return match ($this->vote) {
            1 => '👍',
            -1 => '👎',
            default => '❓',
        };
    }
}
