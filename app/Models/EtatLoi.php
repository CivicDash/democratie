<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EtatLoi extends Model
{
    protected $table = 'senat_dosleg_etaloi';
    protected $primaryKey = 'etaloicod';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'etaloicod',
        'etaloilib',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function lois(): HasMany
    {
        return $this->hasMany(Loi::class, 'etaloicod', 'etaloicod');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getLibelleAttribute(): string
    {
        return trim($this->etaloilib ?? '');
    }

    public function getCouleurAttribute(): string
    {
        return match (trim($this->etaloicod)) {
            '01' => 'blue',    // En cours
            '02' => 'gray',    // Fusionné
            '03' => 'red',     // Rejeté
            '04' => 'green',   // Promulgué
            '05' => 'yellow',  // Caduc
            '06' => 'orange',  // Retiré
            default => 'gray',
        };
    }

    public function getIconeAttribute(): string
    {
        return match (trim($this->etaloicod)) {
            '01' => '🔄',
            '02' => '🔗',
            '03' => '❌',
            '04' => '✅',
            '05' => '⏰',
            '06' => '↩️',
            default => '❓',
        };
    }
}

