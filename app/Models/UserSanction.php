<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSanction extends Model
{
    protected $fillable = [
        'user_id',
        'moderator_id',
        'type',
        'reason',
        'duration_days',
        'starts_at',
        'ends_at',
        'is_active',
        'appeal_message',
        'appeal_status',
        'appeal_reviewed_by',
        'appeal_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'appeal_reviewed_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // Types de sanctions
    public const TYPE_WARNING = 'warning';

    public const TYPE_SUSPENSION = 'suspension';

    public const TYPE_BAN = 'ban';

    public const TYPE_UNBAN = 'unban';

    public const TYPES = [
        self::TYPE_WARNING => 'Avertissement',
        self::TYPE_SUSPENSION => 'Suspension temporaire',
        self::TYPE_BAN => 'Bannissement définitif',
        self::TYPE_UNBAN => 'Levée de sanction',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function appealReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'appeal_reviewed_by');
    }

    // Helpers
    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function isExpired(): bool
    {
        if (! $this->ends_at) {
            return false; // Permanent
        }

        return $this->ends_at->isPast();
    }

    public function getRemainingDays(): ?int
    {
        if (! $this->ends_at || $this->ends_at->isPast()) {
            return null;
        }

        return (int) now()->diffInDays($this->ends_at, false);
    }

    public function getRemainingTime(): ?string
    {
        if (! $this->ends_at) {
            return null;
        }
        if ($this->ends_at->isPast()) {
            return 'Expirée';
        }

        return $this->ends_at->diffForHumans(['parts' => 2]);
    }
}
