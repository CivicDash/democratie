<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeLecture extends Model
{
    protected $table = 'senat_dosleg_typlec';
    protected $primaryKey = 'typleccod';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'typleccod',
        'typleclib',
        'typlecord',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function lectures(): HasMany
    {
        return $this->hasMany(LectureLoi::class, 'typleccod', 'typleccod');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getLibelleAttribute(): string
    {
        return trim($this->typleclib ?? '');
    }

    public function getOrdreAttribute(): int
    {
        return (int) ($this->typlecord ?? 0);
    }

    public function getIconeAttribute(): string
    {
        return match (trim($this->typleccod)) {
            '1' => '1️⃣',
            '2' => '2️⃣',
            '3' => '3️⃣',
            '4' => '⚖️',
            '5' => '🔄',
            '6' => '✅',
            '7' => '4️⃣',
            '8' => '🏛️',
            '9' => '🗳️',
            default => '📋',
        };
    }
}

