<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PresidentielleModerationLog extends Model
{
    protected $table = 'presidentielle_moderation_logs';

    public $timestamps = false;

    protected $fillable = [
        'entite_type', 'entite_id', 'action', 'ancien_statut', 'nouveau_statut',
        'commentaire', 'metadata', 'moderator_id', 'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function entite(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'entite_type', 'entite_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
