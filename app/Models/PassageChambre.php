<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PassageChambre extends Model
{
    protected $table = 'senat_dosleg_lecass';
    protected $primaryKey = 'lecassidt';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'lecassidt',
        'lecidt',
        'codass',
        'ordreass',
        'sesann',
        'orgcod',
        'lecassame',
        'lecassameses',
        'lecassameado',
        'lecassameadodat',
        'debatsurl',
        'depot_only',
    ];

    protected $appends = ['chambre_nom', 'chambre_couleur'];

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

    public function getChambreNomAttribute(): string
    {
        return match (trim($this->codass)) {
            'A' => 'Assemblée Nationale',
            'S' => 'Sénat',
            'I' => 'Commission Mixte Paritaire',
            default => 'Autre',
        };
    }

    public function getChambreCodeAttribute(): string
    {
        return match (trim($this->codass)) {
            'A' => 'an',
            'S' => 'senat',
            'I' => 'cmp',
            default => 'autre',
        };
    }

    public function getChambreCouleurAttribute(): string
    {
        return match (trim($this->codass)) {
            'A' => '#0066CC',
            'S' => '#CC0066',
            'I' => '#6B7280',
            default => '#9CA3AF',
        };
    }

    public function getChambreIconeAttribute(): string
    {
        return match (trim($this->codass)) {
            'A' => '🏛️',
            'S' => '🏛️',
            'I' => '⚖️',
            default => '📋',
        };
    }

    public function getSessionLibelleAttribute(): string
    {
        if (!$this->sesann) {
            return '';
        }
        return "Session {$this->sesann}-" . ($this->sesann + 1);
    }

    public function getNbAmendementsAttribute(): int
    {
        return (int) ($this->lecassame ?? 0);
    }

    public function getAmendementsAdoptesAttribute(): int
    {
        return (int) ($this->lecassameado ?? 0);
    }

    public function getTauxAdoptionAttribute(): ?float
    {
        if (!$this->lecassame || $this->lecassame == 0) {
            return null;
        }
        return round(($this->lecassameado / $this->lecassame) * 100, 1);
    }
}

