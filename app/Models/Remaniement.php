<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Remaniement extends Model
{
    protected $table = 'remaniements';

    protected $fillable = [
        'gouvernement_id', 'date', 'type', 'description',
        'decret_jorf', 'nb_entrants', 'nb_sortants',
    ];

    protected $casts = [
        'date' => 'date',
        'nb_entrants' => 'integer',
        'nb_sortants' => 'integer',
    ];

    // Relations
    public function gouvernement(): BelongsTo
    {
        return $this->belongsTo(Gouvernement::class);
    }

    // Accessors
    public function getTypeLibelleAttribute(): string
    {
        return match ($this->type) {
            'formation' => 'Formation du gouvernement',
            'remaniement' => 'Remaniement ministériel',
            'demission' => 'Démission',
            default => $this->type,
        };
    }
}
