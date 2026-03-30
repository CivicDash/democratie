<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeanceLoi extends Model
{
    protected $table = 'senat_dosleg_date_seance';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'lecidt',
        'date_s',
        'code',
        'statut',
    ];

    protected $casts = [
        'date_s' => 'datetime',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function lecture(): BelongsTo
    {
        return $this->belongsTo(LectureLoi::class, 'lecidt', 'lecidt');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getDateSeanceAttribute(): ?\Carbon\Carbon
    {
        return $this->date_s;
    }
}
