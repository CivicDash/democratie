<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Séance de débat au Sénat
 * 
 * Source: https://data.senat.fr/data/debats/debats.zip
 */
class SenatDebat extends Model
{
    protected $table = 'senat_debats';
    protected $primaryKey = 'date_seance';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'date_seance',
        'numero',
        'url',
        'libelle_special',
        'est_congres',
        'etat_video',
        'cpterr',
    ];

    protected $casts = [
        'date_seance' => 'datetime',
        'numero' => 'integer',
        'est_congres' => 'boolean',
        'cpterr' => 'integer',
    ];

    /**
     * Relations
     */
    public function sectionsDiscussion(): HasMany
    {
        return $this->hasMany(SenatSectionDiscussion::class, 'date_seance', 'date_seance');
    }

    public function sectionsDiverses(): HasMany
    {
        return $this->hasMany(SenatSectionDiverse::class, 'date_seance', 'date_seance');
    }

    public function lecturesDebats(): HasMany
    {
        return $this->hasMany(SenatLectureDebat::class, 'date_seance', 'date_seance');
    }

    /**
     * Accessors
     */
    public function getUrlCompteRenduAttribute(): string
    {
        if ($this->url) {
            return 'https://www.senat.fr' . $this->url;
        }
        
        // URL par défaut basée sur la date
        $date = $this->date_seance;
        return sprintf(
            'https://www.senat.fr/seances/s%s/s%s.html',
            $date->format('Ymd'),
            $date->format('Ymd')
        );
    }

    public function getDateFormateeAttribute(): string
    {
        return $this->date_seance->translatedFormat('l j F Y');
    }

    /**
     * Scopes
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('date_seance', '>=', now()->subDays($days));
    }

    public function scopeAnnee($query, int $year)
    {
        return $query->whereYear('date_seance', $year);
    }

    public function scopeCongres($query)
    {
        return $query->where('est_congres', true);
    }
}
