<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vote détaillé d'un député ou sénateur
 * 
 * @property int $id
 * @property int $depute_senateur_id
 * @property string $numero_scrutin
 * @property \Carbon\Carbon $date_vote
 * @property string $titre
 * @property string $position
 * @property string|null $resultat
 * @property int|null $pour
 * @property int|null $contre
 * @property int|null $abstentions
 * @property int|null $absents
 * @property string|null $type_vote
 * @property string|null $url_scrutin
 * @property string|null $contexte
 */
class VoteDepute extends Model
{
    use HasFactory;

    protected $table = 'votes_deputes';

    protected $fillable = [
        'depute_senateur_id',
        'numero_scrutin',
        'date_vote',
        'titre',
        'position',
        'resultat',
        'pour',
        'contre',
        'abstentions',
        'absents',
        'type_vote',
        'url_scrutin',
        'contexte',
    ];

    protected $casts = [
        'date_vote' => 'date',
        'pour' => 'integer',
        'contre' => 'integer',
        'abstentions' => 'integer',
        'absents' => 'integer',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    public function deputeSenateur(): BelongsTo
    {
        return $this->belongsTo(DeputeSenateur::class, 'depute_senateur_id');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopePour($query)
    {
        return $query->where('position', 'pour');
    }

    public function scopeContre($query)
    {
        return $query->where('position', 'contre');
    }

    public function scopeAbstention($query)
    {
        return $query->where('position', 'abstention');
    }

    public function scopeAbsent($query)
    {
        return $query->where('position', 'absent');
    }

    public function scopeAdopte($query)
    {
        return $query->where('resultat', 'adopte');
    }

    public function scopeRejete($query)
    {
        return $query->where('resultat', 'rejete');
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getPositionLabelAttribute(): string
    {
        return match($this->position) {
            'pour' => 'Pour',
            'contre' => 'Contre',
            'abstention' => 'Abstention',
            'absent' => 'Absent',
            default => $this->position,
        };
    }

    public function getResultatLabelAttribute(): ?string
    {
        if (!$this->resultat) {
            return null;
        }

        return match($this->resultat) {
            'adopte' => 'Adopté',
            'rejete' => 'Rejeté',
            default => $this->resultat,
        };
    }

    public function getTotalVotantsAttribute(): int
    {
        return ($this->pour ?? 0) + ($this->contre ?? 0) + ($this->abstentions ?? 0);
    }
}

