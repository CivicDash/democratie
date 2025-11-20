<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScrutinAN extends Model
{
    use HasFactory;

    protected $table = 'scrutins_an';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'numero',
        'organe_ref',
        'legislature',
        'date_scrutin',
        'type_vote_code',
        'type_vote_libelle',
        'resultat_code',
        'resultat_libelle',
        'titre',
        'nombre_votants',
        'suffrages_exprimes',
        'suffrage_requis',
        'pour',
        'contre',
        'abstentions',
        'non_votants',
        'ventilation_votes',
    ];

    protected $casts = [
        'date_scrutin' => 'date',
        'numero' => 'integer',
        'legislature' => 'integer',
        'nombre_votants' => 'integer',
        'suffrages_exprimes' => 'integer',
        'suffrage_requis' => 'integer',
        'pour' => 'integer',
        'contre' => 'integer',
        'abstentions' => 'integer',
        'non_votants' => 'integer',
        'ventilation_votes' => 'array',
    ];

    /**
     * Relations
     */
    public function organe(): BelongsTo
    {
        return $this->belongsTo(OrganeAN::class, 'organe_ref', 'uid');
    }

    public function votesIndividuels(): HasMany
    {
        return $this->hasMany(VoteIndividuelAN::class, 'scrutin_ref', 'uid');
    }

    public function deports(): HasMany
    {
        return $this->hasMany(DeportAN::class, 'scrutin_ref', 'uid');
    }

    /**
     * Tags associés
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            Tag::class,
            'scrutin_tag',
            'scrutin_uid',
            'tag_id',
            'uid',
            'id'
        );
    }

    /**
     * Scopes
     */
    public function scopeLegislature($query, int $legislature)
    {
        return $query->where('legislature', $legislature);
    }

    public function scopeAdopte($query)
    {
        return $query->where('resultat_code', 'adopté');
    }

    public function scopeRejete($query)
    {
        return $query->where('resultat_code', 'rejeté');
    }

    public function scopeDateBetween($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('date_scrutin', [$dateDebut, $dateFin]);
    }

    /**
     * Accessors
     */
    public function getEstAdopteAttribute(): bool
    {
        return $this->resultat_code === 'adopté';
    }

    public function getTauxParticipationAttribute(): float
    {
        if ($this->nombre_votants === 0) {
            return 0.0;
        }
        return round(($this->nombre_votants / 577) * 100, 2); // 577 députés
    }

    public function getTauxPourAttribute(): float
    {
        if ($this->suffrages_exprimes === 0) {
            return 0.0;
        }
        return round(($this->pour / $this->suffrages_exprimes) * 100, 2);
    }

    public function getTauxContreAttribute(): float
    {
        if ($this->suffrages_exprimes === 0) {
            return 0.0;
        }
        return round(($this->contre / $this->suffrages_exprimes) * 100, 2);
    }

    public function getTauxAbstentionAttribute(): float
    {
        if ($this->suffrages_exprimes === 0) {
            return 0.0;
        }
        return round(($this->abstentions / $this->nombre_votants) * 100, 2);
    }
}

