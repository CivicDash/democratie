<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeportAN extends Model
{
    use HasFactory;

    protected $table = 'deports_an';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'acteur_ref',
        'scrutin_ref',
        'legislature',
        'raison',
        'details',
    ];

    protected $casts = [
        'legislature' => 'integer',
        'details' => 'array',
    ];

    /**
     * Relations
     */
    public function acteur(): BelongsTo
    {
        return $this->belongsTo(ActeurAN::class, 'acteur_ref', 'uid');
    }

    public function scrutin(): BelongsTo
    {
        return $this->belongsTo(ScrutinAN::class, 'scrutin_ref', 'uid');
    }

    /**
     * Scopes
     */
    public function scopeLegislature($query, int $legislature)
    {
        return $query->where('legislature', $legislature);
    }

    public function scopeParActeur($query, string $acteurUid)
    {
        return $query->where('acteur_ref', $acteurUid);
    }
}

