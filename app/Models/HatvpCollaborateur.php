<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Collaborateur parlementaire déclaré à la HATVP
 */
class HatvpCollaborateur extends Model
{
    use HasFactory;

    protected $table = 'hatvp_collaborateurs';

    protected $fillable = [
        'declaration_id',
        'nom',
        'employeur',
        'description_activite',
        'commentaire',
    ];

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(HatvpDeclaration::class, 'declaration_id');
    }
}

