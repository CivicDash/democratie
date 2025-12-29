<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LectureLoi extends Model
{
    protected $table = 'senat_dosleg_lecture';
    protected $primaryKey = 'lecidt';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'lecidt',
        'loicod',
        'typleccod',
        'leccom',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function loi(): BelongsTo
    {
        return $this->belongsTo(Loi::class, 'loicod', 'loicod');
    }

    public function typeLecture(): BelongsTo
    {
        return $this->belongsTo(TypeLecture::class, 'typleccod', 'typleccod');
    }

    public function passages(): HasMany
    {
        return $this->hasMany(PassageChambre::class, 'lecidt', 'lecidt');
    }

    public function seances(): HasMany
    {
        return $this->hasMany(SeanceLoi::class, 'lecidt', 'lecidt');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getLibelleAttribute(): string
    {
        return trim($this->typeLecture->typleclib ?? 'Lecture');
    }

    public function getCommentaireAttribute(): string
    {
        return trim($this->leccom ?? '');
    }
}

