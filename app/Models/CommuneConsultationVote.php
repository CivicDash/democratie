<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommuneConsultationVote extends Model
{
    protected $fillable = [
        'consultation_id',
        'user_id',
        'option_key',
    ];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(CommuneConsultation::class, 'consultation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
