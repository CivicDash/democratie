<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour l'agenda législatif
 * 
 * @property int $id
 * @property string $source
 * @property \Carbon\Carbon $date
 * @property \Carbon\Carbon|null $heure_debut
 * @property \Carbon\Carbon|null $heure_fin
 * @property string $type
 * @property string|null $lieu
 * @property string $titre
 * @property string|null $description
 * @property array|null $sujets
 * @property array|null $textes_examines
 * @property string|null $url_externe
 * @property string|null $url_video
 * @property string $statut
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AgendaLegislatif extends Model
{
    use HasFactory;

    protected $table = 'agenda_legislatif';

    protected $fillable = [
        'source',
        'date',
        'heure_debut',
        'heure_fin',
        'type',
        'lieu',
        'titre',
        'description',
        'sujets',
        'textes_examines',
        'url_externe',
        'url_video',
        'statut',
    ];

    protected $casts = [
        'date' => 'date',
        'heure_debut' => 'datetime',
        'heure_fin' => 'datetime',
        'sujets' => 'array',
        'textes_examines' => 'array',
    ];

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeAssemblee($query)
    {
        return $query->where('source', 'assemblee');
    }

    public function scopeSenat($query)
    {
        return $query->where('source', 'senat');
    }

    public function scopeFutur($query)
    {
        return $query->where('date', '>=', now()->toDateString())
                     ->where('statut', 'prevu');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeTermine($query)
    {
        return $query->where('statut', 'termine');
    }

    public function scopeSeancePublique($query)
    {
        return $query->where('type', 'seance_publique');
    }

    public function scopeCommission($query)
    {
        return $query->where('type', 'commission');
    }

    public function scopeBetween($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('date', [$dateDebut, $dateFin]);
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'seance_publique' => 'Séance publique',
            'commission' => 'Commission',
            'questions_gouvernement' => 'Questions au Gouvernement',
            default => $this->type,
        };
    }

    public function getStatutLabelAttribute(): string
    {
        return match($this->statut) {
            'prevu' => 'Prévu',
            'en_cours' => 'En cours',
            'termine' => 'Terminé',
            'annule' => 'Annulé',
            default => $this->statut,
        };
    }

    public function getEstAujourdhuiAttribute(): bool
    {
        return $this->date->isToday();
    }

    public function getEstDemainAttribute(): bool
    {
        return $this->date->isTomorrow();
    }

    public function getDureeAttribute(): ?int
    {
        if (!$this->heure_debut || !$this->heure_fin) {
            return null;
        }

        return $this->heure_debut->diffInMinutes($this->heure_fin);
    }
}

