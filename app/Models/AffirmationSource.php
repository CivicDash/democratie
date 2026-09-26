<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Source d'une fiche « Ce qu'on entend ». La catégorie suit la hiérarchie du cadre
 * éditorial : un chiffre d'association, de syndicat ou de think tank peut être cité,
 * mais comme l'estimation d'un acteur identifié — jamais comme le chiffre de référence.
 */
class AffirmationSource extends Model
{
    public const CATEGORIES = [
        'producteur_public' => 'Producteur public',
        'organisation_internationale' => 'Organisation internationale',
        'recherche' => 'Institut de recherche',
        'presse' => 'Presse',
        'acteur_identifie' => 'Estimation d\'un acteur identifié',
    ];

    protected $fillable = [
        'affirmation_id', 'cle', 'producteur', 'titre', 'url', 'archive_url', 'categorie',
        'date_publication', 'date_consultation',
    ];

    protected $casts = [
        'date_publication' => 'date',
        'date_consultation' => 'date',
    ];

    public function affirmation(): BelongsTo
    {
        return $this->belongsTo(Affirmation::class);
    }

    public function constats(): BelongsToMany
    {
        return $this->belongsToMany(AffirmationConstat::class, 'affirmation_constat_source', 'source_id', 'constat_id');
    }
}
