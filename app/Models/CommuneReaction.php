<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommuneReaction extends Model
{
    protected $fillable = [
        'user_id',
        'reactable_type',
        'reactable_id',
        'type',
    ];

    public const TYPES = ['like', 'love', 'celebrate', 'surprise', 'discuss'];

    public const LABELS = [
        'like' => '👍',
        'love' => '❤️',
        'celebrate' => '🎉',
        'surprise' => '😮',
        'discuss' => '💬',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }
}
