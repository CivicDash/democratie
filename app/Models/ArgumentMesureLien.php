<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Liaison argument ↔ mesure (plan §4). Le SENS (pour|contre) et la note contextuelle
 * sont portés par la liaison, pas par l'argument : un même fait sourcé peut être « pour »
 * une mesure et « contre » une mesure opposée. Chaque liaison a son propre cycle de
 * validation, le même quel que soit le sens : une seule validation par un modérateur
 * (la double validation des « contre » a été abandonnée le 03/11/2026 ; les colonnes
 * double_valide_par/at gardent la trace des secondes validations faites auparavant).
 *
 * mesure_id peut être NULL quand l'auto-match n'a pas trouvé de mesure : la cible reste
 * proposée en clair (candidat_slug_propose + mesure_proposee) pour résolution au BO.
 */
class ArgumentMesureLien extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'argument_mesure_liens';

    public const SENS = ['pour', 'contre'];

    protected $fillable = [
        'argument_id', 'mesure_id', 'sens', 'note_contextuelle',
        'candidat_slug_propose', 'mesure_proposee', 'source_detection', 'detection_confidence',
        'statut_validation', 'affiche_publiquement',
        'valide_par', 'valide_at', 'double_valide_par', 'double_valide_at', 'commentaire_validation',
    ];

    protected $casts = [
        'affiche_publiquement' => 'boolean',
        'valide_at' => 'datetime',
        'double_valide_at' => 'datetime',
        'detection_confidence' => 'decimal:3',
    ];

    public function argument(): BelongsTo
    {
        return $this->belongsTo(Argument::class, 'argument_id');
    }

    public function mesure(): BelongsTo
    {
        return $this->belongsTo(ProgrammeMesure::class, 'mesure_id');
    }

    public function scopePublie($query)
    {
        return $query->where('statut_validation', 'valide')->where('affiche_publiquement', true);
    }

    /**
     * Une liaison est éditorialement valide si elle est au statut valide, validée, reliée
     * à une mesure et dotée d'une note contextuelle, quel que soit son sens.
     */
    public function estValide(): bool
    {
        if ($this->statut_validation !== 'valide' || $this->valide_par === null) {
            return false;
        }

        return $this->mesure_id !== null && filled($this->note_contextuelle);
    }
}
