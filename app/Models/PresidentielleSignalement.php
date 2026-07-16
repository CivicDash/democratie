<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Signalement citoyen d'une erreur (formulaire public objectif2027.fr → ticket BO).
 * File interne : nouveau → en_cours → resolu | rejete. Aucune donnée publiée.
 */
class PresidentielleSignalement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'presidentielle_signalements';

    /** Types d'incident proposés au signaleur (slug => libellé). */
    public const TYPES_INCIDENT = [
        'inexactitude_proposition' => 'Inexactitude d’une proposition / mesure',
        'source_erronee' => 'Source erronée ou lien mort',
        'affaire_judiciaire' => 'Problème sur une affaire judiciaire',
        'parcours_autre' => 'Parcours, autre ou droit de réponse',
    ];

    public const STATUTS = ['nouveau', 'en_cours', 'resolu', 'rejete'];

    protected $fillable = [
        'type_incident', 'description', 'email',
        'candidat_slug', 'theme_slug', 'argument_ref', 'contexte_url', 'content_hash',
        'statut', 'moderator_id', 'resolution_note', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function scopeParStatut($query, ?string $statut)
    {
        return $statut && $statut !== 'tous' ? $query->where('statut', $statut) : $query;
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'nouveau');
    }
}
