<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Événement législatif unifié (AN + Sénat + Élysée)
 * 
 * @property string $uid
 * @property string $source
 * @property string $type
 * @property string $titre
 * @property string|null $description
 * @property string|null $lieu
 * @property \Carbon\Carbon $date_debut
 * @property \Carbon\Carbon|null $date_fin
 * @property bool $journee_entiere
 * @property string|null $instance_code
 * @property string|null $instance_nom
 * @property string|null $organe_ref
 * @property string|null $url_source
 * @property string|null $url_video
 * @property string|null $url_dossier
 * @property string|null $couleur
 * @property string|null $icone
 * @property string|null $ical_uid
 * @property \Carbon\Carbon|null $ical_last_modified
 * @property string $statut
 */
class EvenementLegislatif extends Model
{
    use HasFactory;

    protected $table = 'evenements_legislatifs';

    protected $fillable = [
        'uid',
        'source',
        'type',
        'titre',
        'description',
        'lieu',
        'date_debut',
        'date_fin',
        'journee_entiere',
        'instance_code',
        'instance_nom',
        'organe_ref',
        'url_source',
        'url_video',
        'url_dossier',
        'couleur',
        'icone',
        'ical_uid',
        'ical_last_modified',
        'statut',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'journee_entiere' => 'boolean',
        'ical_last_modified' => 'datetime',
    ];

    // ========================================
    // CONSTANTES
    // ========================================

    public const SOURCE_AN = 'an';
    public const SOURCE_SENAT = 'senat';
    public const SOURCE_ELYSEE = 'elysee';

    public const TYPE_SEANCE = 'seance';
    public const TYPE_COMMISSION = 'commission';
    public const TYPE_REUNION = 'reunion';
    public const TYPE_VOTE = 'vote';
    public const TYPE_AUDITION = 'audition';
    public const TYPE_AUTRE = 'autre';

    public const STATUT_CONFIRME = 'confirme';
    public const STATUT_ANNULE = 'annule';
    public const STATUT_REPORTE = 'reporte';

    // Couleurs par défaut par source
    public const COULEURS = [
        self::SOURCE_AN => '#0055A4',     // Bleu AN
        self::SOURCE_SENAT => '#DC143C',  // Rouge Sénat
        self::SOURCE_ELYSEE => '#FFD700', // Or Élysée
    ];

    // Icônes par type
    public const ICONES = [
        self::TYPE_SEANCE => '🏛️',
        self::TYPE_COMMISSION => '👥',
        self::TYPE_REUNION => '📋',
        self::TYPE_VOTE => '🗳️',
        self::TYPE_AUDITION => '🎤',
        self::TYPE_AUTRE => '📅',
    ];

    // ========================================
    // SCOPES
    // ========================================

    public function scopeSource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    public function scopeAn(Builder $query): Builder
    {
        return $query->where('source', self::SOURCE_AN);
    }

    public function scopeSenat(Builder $query): Builder
    {
        return $query->where('source', self::SOURCE_SENAT);
    }

    public function scopeElysee(Builder $query): Builder
    {
        return $query->where('source', self::SOURCE_ELYSEE);
    }

    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeSeances(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_SEANCE);
    }

    public function scopeCommissions(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_COMMISSION);
    }

    public function scopeAVenir(Builder $query): Builder
    {
        return $query->where('date_debut', '>=', now());
    }

    public function scopePasses(Builder $query): Builder
    {
        return $query->where('date_debut', '<', now());
    }

    public function scopeConfirmes(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_CONFIRME);
    }

    public function scopePeriode(Builder $query, $debut, $fin): Builder
    {
        return $query->whereBetween('date_debut', [$debut, $fin]);
    }

    public function scopeJour(Builder $query, $date): Builder
    {
        return $query->whereDate('date_debut', $date);
    }

    public function scopeSemaine(Builder $query, $date = null): Builder
    {
        $date = $date ? \Carbon\Carbon::parse($date) : now();
        $debut = $date->copy()->startOfWeek();
        $fin = $date->copy()->endOfWeek();
        
        return $query->whereBetween('date_debut', [$debut, $fin]);
    }

    public function scopeMois(Builder $query, $date = null): Builder
    {
        $date = $date ? \Carbon\Carbon::parse($date) : now();
        $debut = $date->copy()->startOfMonth();
        $fin = $date->copy()->endOfMonth();
        
        return $query->whereBetween('date_debut', [$debut, $fin]);
    }

    // ========================================
    // ACCESSEURS
    // ========================================

    public function getCouleurAttribute($value): string
    {
        return $value ?? self::COULEURS[$this->source] ?? '#6B7280';
    }

    public function getIconeAttribute($value): string
    {
        return $value ?? self::ICONES[$this->type] ?? '📅';
    }

    public function getSourceLabelAttribute(): string
    {
        return match($this->source) {
            self::SOURCE_AN => 'Assemblée nationale',
            self::SOURCE_SENAT => 'Sénat',
            self::SOURCE_ELYSEE => 'Élysée',
            default => ucfirst($this->source),
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_SEANCE => 'Séance publique',
            self::TYPE_COMMISSION => 'Commission',
            self::TYPE_REUNION => 'Réunion',
            self::TYPE_VOTE => 'Vote',
            self::TYPE_AUDITION => 'Audition',
            self::TYPE_AUTRE => 'Autre',
            default => ucfirst($this->type),
        };
    }

    public function getStatutLabelAttribute(): string
    {
        return match($this->statut) {
            self::STATUT_CONFIRME => 'Confirmé',
            self::STATUT_ANNULE => 'Annulé',
            self::STATUT_REPORTE => 'Reporté',
            default => ucfirst($this->statut),
        };
    }

    public function getDureeAttribute(): ?int
    {
        if (!$this->date_fin) {
            return null;
        }
        return $this->date_debut->diffInMinutes($this->date_fin);
    }

    public function getDureeFormattedAttribute(): ?string
    {
        $duree = $this->duree;
        if (!$duree) {
            return null;
        }
        
        $heures = intdiv($duree, 60);
        $minutes = $duree % 60;
        
        if ($heures > 0 && $minutes > 0) {
            return "{$heures}h{$minutes}";
        } elseif ($heures > 0) {
            return "{$heures}h";
        } else {
            return "{$minutes}min";
        }
    }

    // ========================================
    // MÉTHODES
    // ========================================

    public function estAVenir(): bool
    {
        return $this->date_debut->isFuture();
    }

    public function estEnCours(): bool
    {
        $now = now();
        return $this->date_debut <= $now && ($this->date_fin === null || $this->date_fin >= $now);
    }

    public function estPasse(): bool
    {
        return $this->date_fin ? $this->date_fin->isPast() : $this->date_debut->isPast();
    }

    public function estAujourdhui(): bool
    {
        return $this->date_debut->isToday();
    }

    /**
     * Convertir pour l'affichage calendrier Vue
     */
    public function toCalendarEvent(): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'title' => $this->titre,
            'start' => $this->date_debut->toIso8601String(),
            'end' => $this->date_fin?->toIso8601String(),
            'allDay' => $this->journee_entiere,
            'color' => $this->couleur,
            'source' => $this->source,
            'sourceLabel' => $this->source_label,
            'type' => $this->type,
            'typeLabel' => $this->type_label,
            'icon' => $this->icone,
            'lieu' => $this->lieu,
            'description' => $this->description,
            'instance' => $this->instance_nom,
            'urlSource' => $this->url_source,
            'urlVideo' => $this->url_video,
            'statut' => $this->statut,
        ];
    }

    /**
     * Convertir en format iCalendar (VEVENT)
     */
    public function toIcalEvent(): string
    {
        $uid = $this->ical_uid ?: "civicdash-{$this->source}-{$this->uid}@civicdash.fr";
        $dtstamp = now()->format('Ymd\THis\Z');
        $created = $this->created_at->format('Ymd\THis\Z');
        $lastModified = ($this->ical_last_modified ?? $this->updated_at)->format('Ymd\THis\Z');
        
        // Format des dates
        if ($this->journee_entiere) {
            $dtstart = 'VALUE=DATE:' . $this->date_debut->format('Ymd');
            $dtend = $this->date_fin 
                ? 'VALUE=DATE:' . $this->date_fin->addDay()->format('Ymd')  // iCal exclut la date de fin
                : '';
        } else {
            $dtstart = $this->date_debut->format('Ymd\THis');
            $dtend = $this->date_fin ? $this->date_fin->format('Ymd\THis') : '';
        }
        
        // Nettoyer et échapper le texte
        $summary = $this->escapeIcalText($this->icone . ' ' . $this->titre);
        $description = $this->escapeIcalText($this->buildIcalDescription());
        $location = $this->escapeIcalText($this->lieu ?? '');
        $url = $this->url_source ?? '';
        
        // Catégories
        $categories = strtoupper($this->source) . ',' . strtoupper($this->type);
        
        // Couleur (non standard mais supporté par certains clients)
        $color = ltrim($this->couleur, '#');
        
        $lines = [
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTAMP:{$dtstamp}",
            "DTSTART;{$dtstart}",
        ];
        
        if ($dtend) {
            $lines[] = "DTEND;{$dtend}";
        }
        
        $lines[] = "CREATED:{$created}";
        $lines[] = "LAST-MODIFIED:{$lastModified}";
        $lines[] = "SUMMARY:{$summary}";
        
        if ($description) {
            $lines[] = "DESCRIPTION:{$description}";
        }
        
        if ($location) {
            $lines[] = "LOCATION:{$location}";
        }
        
        if ($url) {
            $lines[] = "URL:{$url}";
        }
        
        $lines[] = "CATEGORIES:{$categories}";
        $lines[] = "X-APPLE-CALENDAR-COLOR:#{$color}";
        $lines[] = "X-MICROSOFT-CDO-BUSYSTATUS:BUSY";
        $lines[] = "TRANSP:OPAQUE";
        $lines[] = "STATUS:" . ($this->statut === self::STATUT_ANNULE ? 'CANCELLED' : 'CONFIRMED');
        $lines[] = 'END:VEVENT';
        
        return implode("\r\n", $lines);
    }

    /**
     * Construire la description iCal enrichie
     */
    protected function buildIcalDescription(): string
    {
        $parts = [];
        
        $parts[] = "📍 Source : {$this->source_label}";
        $parts[] = "📋 Type : {$this->type_label}";
        
        if ($this->instance_nom) {
            $parts[] = "🏛️ Instance : {$this->instance_nom}";
        }
        
        if ($this->description) {
            $parts[] = "";
            $parts[] = $this->description;
        }
        
        if ($this->url_source) {
            $parts[] = "";
            $parts[] = "🔗 Plus d'infos : {$this->url_source}";
        }
        
        if ($this->url_video) {
            $parts[] = "📺 Vidéo : {$this->url_video}";
        }
        
        $parts[] = "";
        $parts[] = "---";
        $parts[] = "Exporté depuis CivicDash - civicdash.fr";
        
        return implode("\\n", $parts);
    }

    /**
     * Échapper le texte pour iCalendar
     */
    protected function escapeIcalText(?string $text): string
    {
        if (!$text) {
            return '';
        }
        
        // Échapper les caractères spéciaux iCal
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace("\n", '\\n', $text);
        $text = str_replace("\r", '', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(';', '\\;', $text);
        
        return $text;
    }
}

