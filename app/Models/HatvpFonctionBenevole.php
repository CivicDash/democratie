<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fonction bénévole déclarée à la HATVP
 */
class HatvpFonctionBenevole extends Model
{
    use HasFactory;

    protected $table = 'hatvp_fonctions_benevoles';

    protected $fillable = [
        'declaration_id',
        'nom_structure',
        'description_activite',
        'conservee',
        'commentaire',
    ];

    protected $casts = [
        'conservee' => 'boolean',
    ];

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(HatvpDeclaration::class, 'declaration_id');
    }
}
