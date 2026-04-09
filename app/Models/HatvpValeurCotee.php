<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Valeur mobilière cotée déclarée à la HATVP
 */
class HatvpValeurCotee extends Model
{
    use HasFactory;

    protected $table = 'hatvp_valeurs_cotees';

    protected $fillable = [
        'declaration_id',
        'titulaire',
        'etablissement',
        'nature_placement',
        'valeur',
        'conservee',
        'commentaire',
    ];

    protected $casts = [
        'valeur' => 'decimal:2',
        'conservee' => 'boolean',
    ];

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(HatvpDeclaration::class, 'declaration_id');
    }
}
