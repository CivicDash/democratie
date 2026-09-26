<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Graphique de comparaison européenne (annexe D). Il ne porte aucune donnée : il nomme
 * des indicateurs Eurostat, dont seule la série relue est rendue.
 */
class AffirmationGraphique extends Model
{
    /** Types autorisés par l'annexe D.4, et nombre d'indicateurs attendus (null = libre). */
    public const TYPES = [
        'courbes' => 'Courbes (ou indice base 100), un panneau par indicateur',
        'barres_groupees' => 'Barres groupées : deux sous-populations, écart en points',
        'barres_empilees' => 'Barres empilées à 100 % : structure d\'un total',
    ];

    public const NB_INDICATEURS = [
        'courbes' => [1, 2],
        'barres_groupees' => [2, 2],
        'barres_empilees' => [2, 8],
    ];

    protected $fillable = [
        'affirmation_id', 'constat_id', 'ordre', 'type', 'titre', 'sous_titre',
        'indicateurs', 'options', 'note',
    ];

    protected $casts = [
        'ordre' => 'integer',
        'indicateurs' => 'array',
        'options' => 'array',
    ];

    public function affirmation(): BelongsTo
    {
        return $this->belongsTo(Affirmation::class);
    }

    public function constat(): BelongsTo
    {
        return $this->belongsTo(AffirmationConstat::class, 'constat_id');
    }
}
