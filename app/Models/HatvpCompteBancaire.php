<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Compte bancaire déclaré à la HATVP
 */
class HatvpCompteBancaire extends Model
{
    use HasFactory;

    protected $table = 'hatvp_comptes_bancaires';

    protected $fillable = [
        'declaration_id',
        'type_compte',
        'etablissement',
        'titulaire',
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
