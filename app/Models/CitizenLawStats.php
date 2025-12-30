<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Statistiques pré-calculées des votes citoyens sur les lois
 */
class CitizenLawStats extends Model
{
    protected $table = 'citizen_law_stats';

    protected $fillable = [
        'loi_cod',
        'legislature',
        'votes_pour',
        'votes_contre',
        'total_votes',
        'pct_pour',
        'pct_contre',
        'calculated_at',
    ];

    protected $casts = [
        'votes_pour' => 'integer',
        'votes_contre' => 'integer',
        'total_votes' => 'integer',
        'pct_pour' => 'float',
        'pct_contre' => 'float',
        'calculated_at' => 'datetime',
    ];

    public function loi(): BelongsTo
    {
        return $this->belongsTo(Loi::class, 'loi_cod', 'loicod');
    }

    /**
     * Recalcule les stats pour une loi
     */
    public static function recalculateForLoi(string $loiCod): self
    {
        $stats = CitizenLawVote::getStatsForLoi($loiCod);

        return self::updateOrCreate(
            ['loi_cod' => $loiCod],
            [
                'votes_pour' => $stats['pour'],
                'votes_contre' => $stats['contre'],
                'total_votes' => $stats['total'],
                'pct_pour' => $stats['pct_pour'],
                'pct_contre' => $stats['pct_contre'],
                'calculated_at' => now(),
            ]
        );
    }

    /**
     * Récupère les stats pour une loi (avec fallback calcul)
     */
    public static function getForLoi(string $loiCod): array
    {
        $stats = self::where('loi_cod', $loiCod)->first();

        if ($stats) {
            return [
                'pour' => $stats->votes_pour,
                'contre' => $stats->votes_contre,
                'total' => $stats->total_votes,
                'pct_pour' => $stats->pct_pour,
                'pct_contre' => $stats->pct_contre,
                'calculated_at' => $stats->calculated_at?->toIso8601String(),
            ];
        }

        // Fallback: calcul à la volée
        return CitizenLawVote::getStatsForLoi($loiCod);
    }
}
