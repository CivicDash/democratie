<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Argument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'arguments';

    public const SENS = ['pour', 'contre'];
    public const TYPES = [
        'chiffrage', 'precedent_historique', 'avis_institution',
        'etude', 'comparaison_internationale', 'faisabilite_juridique',
    ];

    protected $fillable = [
        'uuid', 'mesure_id', 'sens', 'titre', 'contenu', 'type_argument',
        'statut_validation', 'affiche_publiquement', 'ordre',
        'valide_par', 'valide_at', 'double_valide_par', 'double_valide_at',
        'commentaire_validation',
    ];

    protected $casts = [
        'affiche_publiquement' => 'boolean',
        'valide_at' => 'datetime',
        'double_valide_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $a) {
            if (empty($a->uuid)) {
                $a->uuid = (string) Str::uuid();
            }
        });
    }

    public function mesure(): BelongsTo
    {
        return $this->belongsTo(ProgrammeMesure::class, 'mesure_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(ArgumentSource::class, 'argument_id');
    }

    public function scopePublie($query)
    {
        return $query->where('statut_validation', 'valide')->where('affiche_publiquement', true);
    }

    /** Un argument "contre" requiert une double validation. */
    public function estValide(): bool
    {
        if ($this->statut_validation !== 'valide') {
            return false;
        }
        if ($this->sens === 'contre') {
            return $this->valide_par !== null && $this->double_valide_par !== null;
        }

        return $this->valide_par !== null;
    }
}
