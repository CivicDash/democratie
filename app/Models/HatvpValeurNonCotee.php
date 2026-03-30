<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Valeur mobilière non cotée déclarée à la HATVP
 */
class HatvpValeurNonCotee extends Model
{
    use HasFactory;

    protected $table = 'hatvp_valeurs_non_cotees';

    protected $fillable = [
        'declaration_id',
        'denomination',
        'valeur_actuelle',
        'participation',
        'droit_reel',
        'conservee',
        'commentaire',
    ];

    protected $casts = [
        'valeur_actuelle' => 'decimal:2',
        'conservee' => 'boolean',
    ];

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(HatvpDeclaration::class, 'declaration_id');
    }
}
