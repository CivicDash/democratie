<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Loi extends Model
{
    protected $table = 'senat_dosleg_loi';
    protected $primaryKey = 'loicod';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'loicod',
        'typloicod',
        'etaloicod',
        'numero',
        'loitit',
        'loiint',
        'urgence',
        'url_jo',
        'loidatjo',
        'url_an',
        'date_loi',
        'signet',
        'motclef',
    ];

    protected $casts = [
        'loidatjo' => 'date',
        'date_loi' => 'date',
    ];

    protected $appends = ['titre_court', 'est_promulguee', 'chambre_origine'];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function etat(): BelongsTo
    {
        return $this->belongsTo(EtatLoi::class, 'etaloicod', 'etaloicod');
    }

    public function typeLoi(): BelongsTo
    {
        return $this->belongsTo(TypeLoi::class, 'typloicod', 'typloicod');
    }

    public function lectures(): HasMany
    {
        return $this->hasMany(LectureLoi::class, 'loicod', 'loicod');
    }

    public function thematiques(): BelongsToMany
    {
        return $this->belongsToMany(
            ThematiqueLoi::class,
            'senat_dosleg_loithe',
            'loicod',
            'thecle',
            'loicod',
            'thecle'
        );
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePromulguees($query)
    {
        return $query->where('etaloicod', '04');
    }

    public function scopeEnCours($query)
    {
        return $query->where('etaloicod', '01');
    }

    public function scopeRejetees($query)
    {
        return $query->where('etaloicod', '03');
    }

    public function scopeCaduques($query)
    {
        return $query->where('etaloicod', '05');
    }

    public function scopeRetirees($query)
    {
        return $query->where('etaloicod', '06');
    }

    public function scopeRecentes($query)
    {
        return $query->orderByDesc('loidatjo');
    }

    public function scopeAvecParcours($query)
    {
        return $query->with([
            'lectures.typeLecture',
            'lectures.passages',
            'etat',
            'typeLoi',
        ]);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getTitreCourtAttribute(): string
    {
        $titre = trim($this->loitit ?? $this->loiint ?? '');
        return Str::limit($titre, 100);
    }

    public function getEstPromulgueeAttribute(): bool
    {
        return $this->etaloicod === '04';
    }

    public function getChambreOrigineAttribute(): ?string
    {
        $premierPassage = $this->lectures()
            ->join('senat_dosleg_lecass', 'senat_dosleg_lecture.lecidt', '=', 'senat_dosleg_lecass.lecidt')
            ->where('senat_dosleg_lecture.typleccod', '1') // Première lecture
            ->orderBy('senat_dosleg_lecass.ordreass')
            ->select('senat_dosleg_lecass.codass')
            ->first();

        if (!$premierPassage) {
            return null;
        }

        return match ($premierPassage->codass) {
            'A' => 'Assemblée Nationale',
            'S' => 'Sénat',
            default => null,
        };
    }

    public function getEtatLibelleAttribute(): string
    {
        return match ($this->etaloicod) {
            '01' => 'En cours',
            '02' => 'Fusionné',
            '03' => 'Rejeté',
            '04' => 'Promulgué',
            '05' => 'Caduc',
            '06' => 'Retiré',
            default => 'Inconnu',
        };
    }

    public function getEtatCouleurAttribute(): string
    {
        return match ($this->etaloicod) {
            '01' => 'blue',
            '02' => 'gray',
            '03' => 'red',
            '04' => 'green',
            '05' => 'yellow',
            '06' => 'orange',
            default => 'gray',
        };
    }

    // ==========================================
    // METHODS
    // ==========================================

    /**
     * Récupère le parcours législatif complet
     */
    public function getParcours(): array
    {
        $parcours = [];

        $lectures = $this->lectures()
            ->with(['typeLecture', 'passages'])
            ->get()
            ->sortBy(function ($lecture) {
                return $lecture->typeLecture->typlecord ?? 0;
            });

        foreach ($lectures as $lecture) {
            $passages = $lecture->passages->sortBy('ordreass');

            foreach ($passages as $passage) {
                $parcours[] = [
                    'lecture_id' => $lecture->lecidt,
                    'type_lecture' => trim($lecture->typeLecture->typleclib ?? ''),
                    'type_code' => $lecture->typleccod,
                    'chambre' => $passage->codass,
                    'chambre_nom' => match ($passage->codass) {
                        'A' => 'Assemblée Nationale',
                        'S' => 'Sénat',
                        'I' => 'Commission Mixte Paritaire',
                        default => 'Autre',
                    },
                    'chambre_icone' => match ($passage->codass) {
                        'A' => '🏛️',
                        'S' => '🏛️',
                        'I' => '⚖️',
                        default => '📋',
                    },
                    'chambre_couleur' => match ($passage->codass) {
                        'A' => '#0066CC',
                        'S' => '#CC0066',
                        'I' => '#6B7280',
                        default => '#9CA3AF',
                    },
                    'ordre' => $passage->ordreass,
                    'session' => $passage->sesann,
                    'nb_amendements' => $passage->lecassame,
                    'amendements_adoptes' => $passage->lecassameado,
                    'url_debats' => $passage->debatsurl,
                    'commentaire' => trim($lecture->leccom ?? ''),
                ];
            }
        }

        return $parcours;
    }

    /**
     * Calcule la progression de la loi (0-100%)
     */
    public function getProgressionAttribute(): int
    {
        // Si promulguée = 100%
        if ($this->etaloicod === '04') {
            return 100;
        }

        // Si rejetée, caduque ou retirée = 0%
        if (in_array($this->etaloicod, ['03', '05', '06'])) {
            return 0;
        }

        // Calculer selon les lectures
        $derniereLecture = $this->lectures()
            ->join('senat_dosleg_typlec', 'senat_dosleg_lecture.typleccod', '=', 'senat_dosleg_typlec.typleccod')
            ->orderByDesc('senat_dosleg_typlec.typlecord')
            ->first();

        if (!$derniereLecture) {
            return 10;
        }

        return match ($derniereLecture->typleccod) {
            '1' => 30,   // 1ère lecture
            '2' => 50,   // 2ème lecture
            '3' => 60,   // 3ème lecture
            '4' => 70,   // CMP
            '5' => 80,   // Nouvelle lecture
            '6' => 95,   // Lecture définitive
            '7' => 65,   // 4ème lecture
            '8' => 98,   // Congrès
            '9' => 90,   // Référendum
            default => 20,
        };
    }
}

