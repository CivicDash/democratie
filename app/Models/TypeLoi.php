<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeLoi extends Model
{
    protected $table = 'senat_dosleg_typloi';
    protected $primaryKey = 'typloicod';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'typloicod',
        'typloilib',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function lois(): HasMany
    {
        return $this->hasMany(Loi::class, 'typloicod', 'typloicod');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getLibelleAttribute(): string
    {
        return trim($this->typloilib ?? '');
    }
}

