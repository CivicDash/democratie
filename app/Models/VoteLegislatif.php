<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle pour les votes législatifs
 * 
 * @property int $id
 * @property int $proposition_loi_id
 * @property string $source
 * @property string $numero_scrutin
 * @property string $type_vote
 * @property int $votes_pour
 * @property int $votes_contre
 * @property int $abstentions
 * @property int $non_votants
 * @property string $resultat
 * @property array|null $detail_votes
 * @property array|null $detail_groupes
 * @property \Carbon\Carbon $date_vote
 * @property string|null $lieu
 * @property int|null $quorum
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class VoteLegislatif extends Model
{
    use HasFactory;

    protected $table = 'votes_legislatifs';

    protected $fillable = [
        'proposition_loi_id',
        'source',
        'numero_scrutin',
        'type_vote',
        'votes_pour',
        'votes_contre',
        'abstentions',
        'non_votants',
        'resultat',
        'detail_votes',
        'detail_groupes',
        'date_vote',
        'lieu',
        'quorum',
    ];

    protected $casts = [
        'votes_pour' => 'integer',
        'votes_contre' => 'integer',
        'abstentions' => 'integer',
        'non_votants' => 'integer',
        'quorum' => 'integer',
        'detail_votes' => 'array',
        'detail_groupes' => 'array',
        'date_vote' => 'datetime',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    public function proposition(): BelongsTo
    {
        return $this->belongsTo(PropositionLoi::class, 'proposition_loi_id');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeAdoptes($query)
    {
        return $query->where('resultat', 'adopte');
    }

    public function scopeRejetes($query)
    {
        return $query->where('resultat', 'rejete');
    }

    public function scopeSolennels($query)
    {
        return $query->where('type_vote', 'solennel');
    }

    // ========================================================================
    // ACCESSEURS & MÉTHODES
    // ========================================================================

    public function getTotalVotantsAttribute(): int
    {
        return $this->votes_pour + $this->votes_contre + $this->abstentions;
    }

    public function getTotalPresentsAttribute(): int
    {
        return $this->total_votants + $this->non_votants;
    }

    public function getPourcentagePourAttribute(): float
    {
        if ($this->total_votants === 0) {
            return 0.0;
        }
        return round(($this->votes_pour / $this->total_votants) * 100, 2);
    }

    public function getPourcentageContreAttribute(): float
    {
        if ($this->total_votants === 0) {
            return 0.0;
        }
        return round(($this->votes_contre / $this->total_votants) * 100, 2);
    }

    public function getPourcentageAbstentionsAttribute(): float
    {
        if ($this->total_votants === 0) {
            return 0.0;
        }
        return round(($this->abstentions / $this->total_votants) * 100, 2);
    }

    public function getMargeAttribute(): int
    {
        return abs($this->votes_pour - $this->votes_contre);
    }

    public function getEstSerreAttribute(): bool
    {
        return $this->marge <= 10; // Vote serré si marge <= 10 voix
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'numero_scrutin' => $this->numero_scrutin,
            'type_vote' => $this->type_vote,
            'resultat' => $this->resultat,
            'date_vote' => $this->date_vote->format('Y-m-d H:i:s'),
            'lieu' => $this->lieu,
            'votes' => [
                'pour' => $this->votes_pour,
                'contre' => $this->votes_contre,
                'abstentions' => $this->abstentions,
                'non_votants' => $this->non_votants,
            ],
            'pourcentages' => [
                'pour' => $this->pourcentage_pour,
                'contre' => $this->pourcentage_contre,
                'abstentions' => $this->pourcentage_abstentions,
            ],
            'statistiques' => [
                'total_votants' => $this->total_votants,
                'total_presents' => $this->total_presents,
                'marge' => $this->marge,
                'est_serre' => $this->est_serre,
            ],
            'detail_groupes' => $this->detail_groupes,
        ];
    }
}

