<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EluActivityNotification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'elu_follower_id',
        'activity_type',
        'activity_id',
        'notification_id',
        'email_sent',
        'notified_at',
    ];

    protected $casts = [
        'email_sent' => 'boolean',
        'notified_at' => 'datetime',
    ];

    /**
     * Le suivi concerné
     */
    public function eluFollower(): BelongsTo
    {
        return $this->belongsTo(EluFollower::class);
    }

    /**
     * La notification in-app associée
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
