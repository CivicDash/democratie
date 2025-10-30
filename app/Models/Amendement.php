<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle pour les amendements
 * 
 * @property int $id
 * @property int $proposition_loi_id
 * @property string $source
 * @property string $numero
 * @property string|null $numero_parent
 * @property string $dispositif
 * @property string|null $expose_motifs
 * @property array|null $auteurs
 * @property string|null $groupe_politique
 * @property string $sort
 * @property \Carbon\Carbon|null $date_depot
 * @property \Carbon\Carbon|null $date_discussion
 * @property string|null $lieu_discussion
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Amendement extends Model
{
    use HasFactory;

    protected $table = 'amendements';

    protected $fillable = [
        'proposition_loi_id',
        'source',
        'numero',
        'numero_parent',
        'dispositif',
        'expose_motifs',
        'auteurs',
        'groupe_politique',
        'sort',
        'date_depot',
        'date_discussion',
        'lieu_discussion',
    ];

    protected $casts = [
        'auteurs' => 'array',
        'date_depot' => 'date',
        'date_discussion' => 'date',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    public function proposition(): BelongsTo
    {
        return $this->belongsTo(PropositionLoi::class, 'proposition_loi_id');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeAdoptes($query)
    {
        return $query->where('sort', 'adopte');
    }

    public function scopeRejetes($query)
    {
        return $query->where('sort', 'rejete');
    }

    public function scopeEnDiscussion($query)
    {
        return $query->where('sort', 'en_discussion');
    }

    public function scopeByGroupe($query, string $groupe)
    {
        return $query->where('groupe_politique', 'like', "%{$groupe}%");
    }

    public function scopeEnCommission($query)
    {
        return $query->where('lieu_discussion', 'commission');
    }

    public function scopeEnHemicycle($query)
    {
        return $query->where('lieu_discussion', 'hemicycle');
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getSortLabelAttribute(): string
    {
        return match($this->sort) {
            'adopte' => 'Adopté',
            'rejete' => 'Rejeté',
            'retire' => 'Retiré',
            'non_soutenu' => 'Non soutenu',
            'tombe' => 'Tombé',
            'en_discussion' => 'En discussion',
            default => $this->sort,
        };
    }

    public function getSortBadgeAttribute(): string
    {
        return match($this->sort) {
            'adopte' => 'success',
            'rejete' => 'danger',
            'retire' => 'secondary',
            'non_soutenu' => 'warning',
            'tombe' => 'secondary',
            'en_discussion' => 'info',
            default => 'secondary',
        };
    }

    public function getEstSousAmendementAttribute(): bool
    {
        return !empty($this->numero_parent);
    }

    public function getAuteurPrincipalAttribute(): ?string
    {
        if (empty($this->auteurs) || !is_array($this->auteurs)) {
            return null;
        }

        $premier = $this->auteurs[0];
        return is_string($premier) ? $premier : ($premier['nom'] ?? null);
    }
}

