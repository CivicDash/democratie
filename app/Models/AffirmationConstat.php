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

    /**
     * Sections qui ne paraissent jamais en partie. Une fiche peut être publiée avant que
     * tous ses chiffres soient sourcés (les phrases non vérifiées restent masquées), mais
     * jamais sans toutes ses réserves : en retirer une durcirait la conclusion.
     */
    public const SECTIONS_INTEGRALES = ['limites'];

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

    /** Ce qui paraît sur le site : seules les phrases vérifiées. Règles et export lisent ceci. */
    public function estAffiche(): bool
    {
        return $this->verification === 'verifie';
    }

    public function affirmation(): BelongsTo
    {
        return $this->belongsTo(Affirmation::class);
    }

    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(AffirmationSource::class, 'affirmation_constat_source', 'constat_id', 'source_id');
    }
}
