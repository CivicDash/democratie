<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReunionAN extends Model
{
    protected $table = 'reunions_an';

    protected $primaryKey = 'uid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'legislature',
        'session',
        'type_reunion',
        'date_debut',
        'date_fin',
        'lieu_ref',
        'lieu_libelle',
        'etat',
        'date_creation',
        'date_cloture',
        'organe_ref',
        'compte_rendu_ref',
        'session_ref',
        'demandeur',
        'odj_convocation',
        'odj_resume',
        'points_odj',
        'participants_internes',
        'personnes_auditionnees',
        'format_reunion',
        'visio_conference',
        'ouverture_presse',
        'captation_video',
        'video_id',
        'url_video',
        'reunion_internationale',
        'pays_reunion_internationale',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'date_creation' => 'date',
        'date_cloture' => 'date',
        'odj_convocation' => 'array',
        'odj_resume' => 'array',
        'points_odj' => 'array',
        'participants_internes' => 'array',
        'personnes_auditionnees' => 'array',
        'pays_reunion_internationale' => 'array',
        'visio_conference' => 'boolean',
        'ouverture_presse' => 'boolean',
        'captation_video' => 'boolean',
        'reunion_internationale' => 'boolean',
    ];

    // ===== RELATIONS =====

    /**
     * L'organe qui tient la réunion (commission, groupe, etc.)
     */
    public function organe(): BelongsTo
    {
        return $this->belongsTo(OrganeAN::class, 'organe_ref', 'uid');
    }

    public function videoChapters(): HasMany
    {
        return $this->hasMany(VideoChapter::class, 'reunion_uid', 'uid');
    }

    public function getVideoUrlAttribute(): ?string
    {
        if (! $this->video_id) {
            return null;
        }

        return "https://videos.assemblee-nationale.fr/video.{$this->video_id}";
    }

    // ===== SCOPES =====

    /**
     * Réunions à venir
     */
    public function scopeAVenir($query)
    {
        return $query->where('date_debut', '>', now())
            ->where('etat', '!=', 'Annulé');
    }

    /**
     * Réunions passées
     */
    public function scopePassees($query)
    {
        return $query->where('date_debut', '<', now());
    }

    /**
     * Réunions confirmées
     */
    public function scopeConfirmees($query)
    {
        return $query->where('etat', 'Confirmé');
    }

    /**
     * Réunions annulées
     */
    public function scopeAnnulees($query)
    {
        return $query->where('etat', 'Annulé');
    }

    /**
     * Réunions d'une période
     */
    public function scopePeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date_debut', [$debut, $fin]);
    }

    /**
     * Réunions d'un mois
     */
    public function scopeMois($query, $annee, $mois)
    {
        return $query->whereYear('date_debut', $annee)
            ->whereMonth('date_debut', $mois);
    }

    /**
     * Réunions d'une semaine
     */
    public function scopeSemaine($query, $date = null)
    {
        $date = $date ? \Carbon\Carbon::parse($date) : now();
        $debut = $date->copy()->startOfWeek();
        $fin = $date->copy()->endOfWeek();

        return $query->whereBetween('date_debut', [$debut, $fin]);
    }

    /**
     * Réunions d'aujourd'hui
     */
    public function scopeAujourdhui($query)
    {
        return $query->whereDate('date_debut', today());
    }

    /**
     * Par type de réunion
     */
    public function scopeType($query, $type)
    {
        return $query->where('type_reunion', $type);
    }

    // ===== ACCESSORS =====

    /**
     * Nom de l'organe (via relation ou fallback)
     */
    public function getOrganeNomAttribute(): ?string
    {
        return $this->organe?->libelle ?? $this->organe?->libelle_abrege;
    }

    /**
     * Premier item de l'ODJ comme titre
     */
    public function getTitreOdjAttribute(): ?string
    {
        $items = $this->odj_resume ?? $this->odj_convocation ?? [];

        return $items[0] ?? null;
    }

    /**
     * Nombre d'items à l'ODJ
     */
    public function getNbPointsOdjAttribute(): int
    {
        $items = $this->odj_resume ?? $this->odj_convocation ?? [];

        return count($items);
    }

    /**
     * Couleur selon l'état
     */
    public function getCouleurEtatAttribute(): string
    {
        return match ($this->etat) {
            'Confirmé' => '#10B981', // Vert
            'Annulé' => '#EF4444',   // Rouge
            'Terminé' => '#6B7280', // Gris
            default => '#3B82F6',    // Bleu
        };
    }

    /**
     * Emoji selon le type
     */
    public function getEmojiTypeAttribute(): string
    {
        return match (true) {
            str_contains($this->type_reunion ?? '', 'Commission') => '🏛️',
            str_contains($this->type_reunion ?? '', 'Séance') => '🗳️',
            str_contains($this->type_reunion ?? '', 'Délégation') => '🌍',
            str_contains($this->type_reunion ?? '', 'Mission') => '🔍',
            default => '📅',
        };
    }

    /**
     * Est en cours actuellement
     */
    public function getEstEnCoursAttribute(): bool
    {
        if (! $this->date_debut) {
            return false;
        }

        $fin = $this->date_fin ?? $this->date_debut->copy()->addHours(3);

        return now()->between($this->date_debut, $fin);
    }

    /**
     * Est à venir
     */
    public function getEstAVenirAttribute(): bool
    {
        return $this->date_debut && $this->date_debut->isFuture();
    }

    /**
     * Date formatée pour affichage
     */
    public function getDateFormateeAttribute(): string
    {
        if (! $this->date_debut) {
            return 'Date non définie';
        }

        return $this->date_debut->translatedFormat('l j F Y à H\hi');
    }

    /**
     * Date courte
     */
    public function getDateCourteAttribute(): string
    {
        if (! $this->date_debut) {
            return '-';
        }

        return $this->date_debut->format('d/m/Y H:i');
    }
}
