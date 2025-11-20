<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScrutinSenat extends Model
{
    protected $table = 'scrutins_senat';

    protected $fillable = [
        'numero',
        'legislature',
        'date_scrutin',
        'titre',
        'objet',
        'type_vote',
        'pour',
        'contre',
        'abstentions',
        'non_votants',
        'resultat',
        'url',
        'donnees_source',
    ];

    protected $casts = [
        'date_scrutin' => 'date',
        'donnees_source' => 'array',
    ];

    /**
     * Relations
     */
    public function votes(): HasMany
    {
        return $this->hasMany(VoteSenat::class, 'scrutin_senat_id');
    }

    /**
     * Scopes
     */
    public function scopeAdoptes($query)
    {
        return $query->where('resultat', 'adopté');
    }

    public function scopeRejetes($query)
    {
        return $query->where('resultat', 'rejeté');
    }

    public function scopeParLegislature($query, string $legislature)
    {
        return $query->where('legislature', $legislature);
    }

    /**
     * Accesseurs
     */
    public function getVotantsAttribute(): int
    {
        return $this->pour + $this->contre + $this->abstentions;
    }

    public function getTauxParticipationAttribute(): float
    {
        $total = $this->votants + $this->non_votants;
        return $total > 0 ? round(($this->votants / $total) * 100, 2) : 0;
    }

    public function getTauxAdoptionAttribute(): ?float
    {
        $exprimes = $this->pour + $this->contre;
        return $exprimes > 0 ? round(($this->pour / $exprimes) * 100, 2) : null;
    }

    public function getEstAdopteAttribute(): bool
    {
        return $this->resultat === 'adopté';
    }
}

