<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Activité de consultant déclarée à la HATVP
 */
class HatvpActiviteConsultant extends Model
{
    use HasFactory;

    protected $table = 'hatvp_activites_consultant';

    protected $fillable = [
        'declaration_id',
        'nom_employeur',
        'description',
        'date_debut',
        'date_fin',
        'conservee',
        'commentaire',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'conservee' => 'boolean',
    ];

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(HatvpDeclaration::class, 'declaration_id');
    }
}

