<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffirmationVerdict extends Model
{
    protected $fillable = ['affirmation_id', 'ordre', 'portee', 'verdict'];

    protected $casts = ['ordre' => 'integer'];

    public function affirmation(): BelongsTo
    {
        return $this->belongsTo(Affirmation::class);
    }
}
