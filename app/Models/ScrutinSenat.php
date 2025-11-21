<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScrutinSenat extends Model
{
    protected $table = 'scrutins_senat';

    // Note: Cette table est une VUE SQL en lecture seule
    protected $fillable = [];

    protected $casts = [
        'date_scrutin' => 'date',
        'session_annee' => 'integer',
        'numero' => 'integer',
        'pour' => 'integer',
        'contre' => 'integer',
        'votants' => 'integer',
        'suffrages_exprimes' => 'integer',
        'majorite_requise' => 'integer',
    ];

    /**
     * Relations
     */
    public function votes(): HasMany
    {
        return $this->hasMany(VoteSenat::class, 'scrutin_id', 'id');
    }

    /**
     * Scopes
     */
    public function scopeAdoptes($query)
    {
        return $query->where('resultat', 'Adopté');
    }

    public function scopeRejetes($query)
    {
        return $query->where('resultat', 'Rejeté');
    }

    public function scopeParSession($query, int $sessionAnnee)
    {
        return $query->where('session_annee', $sessionAnnee);
    }

    /**
     * Accesseurs
     */
    public function getAbstentionsAttribute(): int
    {
        return $this->suffrages_exprimes - $this->pour - $this->contre;
    }

    public function getTauxParticipationAttribute(): float
    {
        return $this->votants > 0 ? round(($this->suffrages_exprimes / $this->votants) * 100, 2) : 0;
    }

    public function getTauxAdoptionAttribute(): ?float
    {
        $exprimes = $this->pour + $this->contre;
        return $exprimes > 0 ? round(($this->pour / $exprimes) * 100, 2) : null;
    }

    public function getEstAdopteAttribute(): bool
    {
        return $this->resultat === 'Adopté';
    }
}

