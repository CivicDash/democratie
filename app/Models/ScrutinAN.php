<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class ScrutinAN extends Model
{
    use HasFactory, Searchable;

    protected $table = 'scrutins_an';

    protected $primaryKey = 'uid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'numero',
        'organe_ref',
        'seance_ref',
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

    public function seance(): BelongsTo
    {
        return $this->belongsTo(ReunionAN::class, 'seance_ref', 'uid');
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

    /**
     * Calcule les votes "pour" depuis ventilation_votes si la colonne est vide
     */
    public function getPourCalculeAttribute(): int
    {
        if ($this->pour > 0) {
            return $this->pour;
        }

        return $this->calculerTotalVotes('pour');
    }

    /**
     * Calcule les votes "contre" depuis ventilation_votes si la colonne est vide
     */
    public function getContreCalculeAttribute(): int
    {
        if ($this->contre > 0) {
            return $this->contre;
        }

        return $this->calculerTotalVotes('contre');
    }

    /**
     * Calcule les abstentions depuis ventilation_votes si la colonne est vide
     */
    public function getAbstentionsCalculeAttribute(): int
    {
        if ($this->abstentions > 0) {
            return $this->abstentions;
        }

        return $this->calculerTotalVotes('abstentions');
    }

    /**
     * Calcule le total d'un type de vote à partir de ventilation_votes
     */
    protected function calculerTotalVotes(string $type): int
    {
        $ventilation = $this->ventilation_votes;
        if (! $ventilation || ! isset($ventilation['organe']['groupes']['groupe'])) {
            return 0;
        }

        $total = 0;
        $groupes = $ventilation['organe']['groupes']['groupe'];

        foreach ($groupes as $groupe) {
            if (isset($groupe['vote']['decompteVoix'][$type])) {
                $total += (int) $groupe['vote']['decompteVoix'][$type];
            }
        }

        return $total;
    }

    /**
     * Récupère le résultat formaté
     */
    public function getResultatFormatAttribute(): string
    {
        if ($this->resultat_libelle) {
            return $this->resultat_libelle;
        }
        if ($this->resultat_code) {
            return ucfirst($this->resultat_code);
        }

        return $this->pour_calcule > $this->contre_calcule ? 'Adopté' : 'Rejeté';
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

    /**
     * Meilisearch: Données indexées pour la recherche
     */
    public function toSearchableArray(): array
    {
        return [
            'uid' => $this->uid,
            'libelle' => $this->titre,
            'titre' => $this->titre,
            'numero' => $this->numero,
            'sort' => $this->resultat_code,
            'resultat_libelle' => $this->resultat_libelle,
            'legislature' => $this->legislature,
            'annee' => $this->date_scrutin?->year,
            'date_scrutin' => $this->date_scrutin?->timestamp,
            'mode_scrutin' => $this->type_vote_code,
        ];
    }

    /**
     * Meilisearch: Nom de l'index
     */
    public function searchableAs(): string
    {
        return 'scrutins_an';
    }
}
