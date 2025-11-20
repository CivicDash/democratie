<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DossierLegislatifSenat extends Model
{
    protected $table = 'dossiers_legislatifs_senat';

    protected $fillable = [
        'numero_senat',
        'numero_an',
        'legislature',
        'type_dossier',
        'titre',
        'titre_court',
        'date_depot',
        'date_adoption_senat',
        'date_promulgation',
        'statut',
        'url_senat',
        'url_legifrance',
        'numero_loi',
        'donnees_source',
        'dossier_an_uid',
    ];

    protected $casts = [
        'date_depot' => 'date',
        'date_adoption_senat' => 'date',
        'date_promulgation' => 'date',
        'donnees_source' => 'array',
    ];

    /**
     * Relations
     */
    public function dossierAN(): BelongsTo
    {
        return $this->belongsTo(DossierLegislatifAN::class, 'dossier_an_uid', 'uid');
    }

    /**
     * Scopes
     */
    public function scopeEnCours($query)
    {
        return $query->where('statut', 'En cours');
    }

    public function scopeAdoptes($query)
    {
        return $query->where('statut', 'Adopté');
    }

    public function scopePromulgues($query)
    {
        return $query->where('statut', 'Promulgué');
    }

    public function scopeRejetes($query)
    {
        return $query->where('statut', 'Rejeté');
    }

    public function scopeParLegislature($query, string $legislature)
    {
        return $query->where('legislature', $legislature);
    }

    public function scopeProjetDeLoi($query)
    {
        return $query->where('type_dossier', 'LIKE', '%Projet de loi%');
    }

    public function scopePropositionDeLoi($query)
    {
        return $query->where('type_dossier', 'LIKE', '%Proposition de loi%');
    }

    /**
     * Accesseurs
     */
    public function getEstLieANAttribute(): bool
    {
        return !is_null($this->dossier_an_uid);
    }

    public function getEstProjetAttribute(): bool
    {
        return str_contains(strtolower($this->type_dossier ?? ''), 'projet');
    }

    public function getEstPropositionAttribute(): bool
    {
        return str_contains(strtolower($this->type_dossier ?? ''), 'proposition');
    }

    public function getEstPromulgueAttribute(): bool
    {
        return $this->statut === 'Promulgué' || !is_null($this->numero_loi);
    }

    public function getEstAdopteAttribute(): bool
    {
        return in_array($this->statut, ['Adopté', 'Promulgué']);
    }

    /**
     * Méthode helper pour récupérer le parcours bicaméral complet
     */
    public function getTimelineBicamerale(): array
    {
        $timeline = [];

        // Étapes Sénat
        if ($this->date_depot) {
            $timeline[] = [
                'date' => $this->date_depot,
                'chambre' => 'Sénat',
                'etape' => 'Dépôt au Sénat',
                'icon' => '🏰',
            ];
        }

        if ($this->date_adoption_senat) {
            $timeline[] = [
                'date' => $this->date_adoption_senat,
                'chambre' => 'Sénat',
                'etape' => 'Adoption par le Sénat',
                'icon' => '✅',
            ];
        }

        // Étapes AN (si lié)
        if ($this->dossierAN) {
            // On peut ajouter les étapes de l'AN depuis le modèle lié
            // Pour l'instant on indique juste qu'il y a un lien
            $timeline[] = [
                'date' => null,
                'chambre' => 'Assemblée Nationale',
                'etape' => 'Examen à l\'AN',
                'icon' => '🏛️',
                'detail' => 'Dossier lié : ' . $this->dossierAN->titre_court,
            ];
        }

        // Promulgation
        if ($this->date_promulgation) {
            $timeline[] = [
                'date' => $this->date_promulgation,
                'chambre' => 'République',
                'etape' => 'Promulgation',
                'icon' => '🇫🇷',
                'detail' => $this->numero_loi ? "Loi n° {$this->numero_loi}" : null,
            ];
        }

        // Trier par date
        usort($timeline, fn($a, $b) => ($a['date'] ?? now())->compare($b['date'] ?? now()));

        return $timeline;
    }
}

