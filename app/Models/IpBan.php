<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpBan extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip',
        'scope',
        'ban_key',
        'abuse_key',
        'abuse_count',
        'ban_seconds',
        'reason',
        'expires_at',
        'unbanned_at',
        'unbanned_by',
        'unban_reason',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'unbanned_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function unbannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unbanned_by');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('unbanned_at')
            ->where('expires_at', '>', now());
    }
}
