<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeputeCirconscription extends Model
{
    protected $table = 'deputes_circonscriptions';

    protected $fillable = [
        'acteur_uid',
        'mandat_uid',
        'legislature',
        'circonscription_ref',
        'departement',
        'num_departement',
        'num_circo',
        'region',
        'region_type',
        'cause_mandat',
        'date_debut',
        'date_fin',
        'date_prise_fonction',
        'cause_fin',
        'premiere_election',
        'place_hemicycle',
        'suppleant_ref',
    ];

    protected $casts = [
        'legislature' => 'integer',
        'num_circo' => 'integer',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_prise_fonction' => 'date',
        'premiere_election' => 'boolean',
        'place_hemicycle' => 'integer',
    ];

    /**
     * Relation vers le député (acteur AN)
     */
    public function depute(): BelongsTo
    {
        return $this->belongsTo(ActeurAN::class, 'acteur_uid', 'uid');
    }

    /**
     * Relation vers le mandat
     */
    public function mandat(): BelongsTo
    {
        return $this->belongsTo(MandatAN::class, 'mandat_uid', 'uid');
    }

    /**
     * Relation vers le suppléant
     */
    public function suppleant(): BelongsTo
    {
        return $this->belongsTo(ActeurAN::class, 'suppleant_ref', 'uid');
    }

    /**
     * Relation vers l'organe circonscription
     */
    public function organeCirconscription(): BelongsTo
    {
        return $this->belongsTo(OrganeAN::class, 'circonscription_ref', 'uid');
    }

    /**
     * Scope pour la législature courante
     */
    public function scopeLegislature($query, int $legislature = 17)
    {
        return $query->where('legislature', $legislature);
    }

    /**
     * Scope pour les mandats actifs
     */
    public function scopeActif($query)
    {
        return $query->whereNull('date_fin');
    }

    /**
     * Scope par département
     */
    public function scopeDepartement($query, string $numDepartement)
    {
        return $query->where('num_departement', $numDepartement);
    }

    /**
     * Scope par région
     */
    public function scopeRegion($query, string $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Obtenir le libellé complet de la circonscription
     */
    public function getLibelleCirconscriptionAttribute(): string
    {
        $ordinal = $this->getOrdinal($this->num_circo);

        return "{$ordinal} circonscription {$this->getDepartementPreposition()}{$this->departement}";
    }

    /**
     * Obtenir le libellé court
     */
    public function getLibelleCourtAttribute(): string
    {
        return "{$this->departement} ({$this->num_circo})";
    }

    /**
     * Préposition correcte pour le département
     */
    private function getDepartementPreposition(): string
    {
        $dep = strtolower($this->departement);

        // Commence par une voyelle
        if (preg_match('/^[aeiouéèêëàâäîïôöûü]/i', $this->departement)) {
            return "de l'";
        }

        // Départements avec "du"
        $duDepartements = [
            'calvados', 'cantal', 'cher', 'doubs', 'finistère', 'gard',
            'gers', 'jura', 'loiret', 'lot', 'morbihan', 'nord', 'pas-de-calais',
            'puy-de-dôme', 'rhône', 'tarn', 'var', 'vaucluse',
        ];

        foreach ($duDepartements as $d) {
            if (str_contains($dep, $d)) {
                return 'du ';
            }
        }

        // Départements avec "de la"
        $delaDepartements = [
            'charente', 'corrèze', 'côte', 'creuse', 'dordogne', 'drôme',
            'gironde', 'haute', 'loire', 'lozère', 'manche', 'marne',
            'mayenne', 'meuse', 'moselle', 'nièvre', 'sarthe', 'savoie',
            'seine', 'somme', 'vendée', 'vienne',
        ];

        foreach ($delaDepartements as $d) {
            if (str_contains($dep, $d)) {
                return 'de la ';
            }
        }

        // Par défaut
        return 'de ';
    }

    /**
     * Obtenir l'ordinal français
     */
    private function getOrdinal(int $n): string
    {
        if ($n === 1) {
            return '1ère';
        }

        return "{$n}ème";
    }
}
