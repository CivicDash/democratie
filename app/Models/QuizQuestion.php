<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Question du quiz thématique.
 *
 * `arbitrage` : plusieurs candidats s'opposent sur un même choix, les options sont leurs
 * positions. `accord` : une mesure isolée, une seule option, à approuver ou non.
 */
class QuizQuestion extends Model
{
    use HasFactory, SoftDeletes;

    public const FORMATS = [
        'arbitrage' => 'Arbitrage entre positions',
        'accord' => 'Accord sur une mesure isolée',
    ];

    protected $fillable = [
        'uuid', 'election', 'theme_id', 'controverse_id', 'format', 'intitule',
        'precision_contexte', 'ordre', 'statut_validation', 'affiche_publiquement',
        'valide_par', 'valide_at', 'commentaire_validation',
    ];

    protected $casts = [
        'affiche_publiquement' => 'boolean',
        'ordre' => 'integer',
        'valide_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $q) {
            $q->uuid ??= (string) \Illuminate\Support\Str::uuid();
        });
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(ProgrammeTheme::class, 'theme_id');
    }

    public function controverse(): BelongsTo
    {
        return $this->belongsTo(Controverse::class, 'controverse_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuizOption::class, 'question_id')->orderBy('ordre')->orderBy('id');
    }

    public function scopePublie($query)
    {
        return $query->where('statut_validation', 'valide')->where('affiche_publiquement', true);
    }
}
