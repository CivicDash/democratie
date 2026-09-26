<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Une phrase d'une fiche : un chiffre sourcé, une limite, une lecture croisée.
 */
class AffirmationConstat extends Model
{
    /** Sections de la fiche, dans l'ordre d'affichage. */
    public const SECTIONS = [
        'chiffres' => 'Ce que disent les chiffres',
        'limites' => 'Ce que les chiffres ne disent pas',
        'complement' => 'Compléments',
        'europe' => 'Comparaison européenne',
        'liens' => 'Liens',
    ];

    /** Sections où une phrase doit citer au moins une source. */
    public const SECTIONS_SOURCEES = ['chiffres', 'complement', 'europe'];

    public const VERIFICATIONS = [
        'verifie' => 'Vérifié',
        'a_verifier' => 'À vérifier',
    ];

    protected $fillable = [
        'affirmation_id', 'section', 'groupe', 'ordre', 'texte', 'verification',
        'note_verification', 'verifie_par', 'verifie_at',
    ];

    protected $casts = [
        'ordre' => 'integer',
        'verifie_at' => 'datetime',
    ];

    public function affirmation(): BelongsTo
    {
        return $this->belongsTo(Affirmation::class);
    }

    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(AffirmationSource::class, 'affirmation_constat_source', 'constat_id', 'source_id');
    }
}
