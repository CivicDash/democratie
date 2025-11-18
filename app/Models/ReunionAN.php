<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReunionAN extends Model
{
    use HasFactory;

    protected $table = 'reunions_an';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'organe_ref',
        'legislature',
        'date_reunion',
        'type_reunion',
        'details',
    ];

    protected $casts = [
        'date_reunion' => 'date',
        'legislature' => 'integer',
        'details' => 'array',
    ];

    /**
     * Relations
     */
    public function organe(): BelongsTo
    {
        return $this->belongsTo(OrganeAN::class, 'organe_ref', 'uid');
    }

    /**
     * Scopes
     */
    public function scopeLegislature($query, int $legislature)
    {
        return $query->where('legislature', $legislature);
    }

    public function scopeParOrgane($query, string $organeUid)
    {
        return $query->where('organe_ref', $organeUid);
    }

    public function scopeDateBetween($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('date_reunion', [$dateDebut, $dateFin]);
    }
}

