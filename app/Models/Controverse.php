<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Regroupe les arguments d'une même question de fond (ex. « âge de départ à la
 * retraite »). Porte la note méthodologique affichée en tête du dépliant pour/contre
 * (pourquoi des études sérieuses divergent : périmètres, indicateurs, hypothèses).
 */
class Controverse extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'controverses';

    protected $fillable = [
        'slug', 'titre', 'theme_id', 'note_methodologique',
        'statut_validation', 'affiche_publiquement', 'ordre',
        'valide_par', 'valide_at', 'commentaire_validation',
    ];

    protected $casts = [
        'affiche_publiquement' => 'boolean',
        'valide_at' => 'datetime',
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(ProgrammeTheme::class, 'theme_id');
    }

    public function arguments(): HasMany
    {
        return $this->hasMany(Argument::class, 'controverse_id');
    }

    public function scopePublie($query)
    {
        return $query->where('statut_validation', 'valide')->where('affiche_publiquement', true);
    }
}
