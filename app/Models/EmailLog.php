<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    protected $fillable = [
        'to_email',
        'to_name',
        'subject',
        'mailable_class',
        'status',
        'message_id',
        'error_message',
        'metadata',
        'user_id',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * Utilisateur destinataire (si applicable)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marquer comme envoyé
     */
    public function markAsSent(?string $messageId = null): void
    {
        $this->update([
            'status' => 'sent',
            'message_id' => $messageId,
            'sent_at' => now(),
        ]);
    }

    /**
     * Marquer comme échoué
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }

    /**
     * Scope : emails échoués
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope : emails envoyés
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope : par type de mailable
     */
    public function scopeOfType($query, string $mailableClass)
    {
        return $query->where('mailable_class', $mailableClass);
    }

    /**
     * Statistiques des emails
     */
    public static function getStats(int $days = 7): array
    {
        $since = now()->subDays($days);

        return [
            'total' => self::where('created_at', '>=', $since)->count(),
            'sent' => self::sent()->where('created_at', '>=', $since)->count(),
            'failed' => self::failed()->where('created_at', '>=', $since)->count(),
            'by_type' => self::where('created_at', '>=', $since)
                ->selectRaw('mailable_class, count(*) as count')
                ->groupBy('mailable_class')
                ->pluck('count', 'mailable_class')
                ->toArray(),
        ];
    }
}
