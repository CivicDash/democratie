<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoteGroupeParlementaire extends Model
{
    use HasFactory;

    protected $table = 'votes_groupes_parlementaires';

    protected $fillable = [
        'vote_legislatif_id',
        'groupe_parlementaire_id',
        'position_groupe',
        'nombre_pour',
        'nombre_contre',
        'nombre_abstention',
        'nombre_absents',
        'pourcentage_discipline',
        'commentaire_officiel',
        'deputes_dissidents',
    ];

    protected $casts = [
        'nombre_pour' => 'integer',
        'nombre_contre' => 'integer',
        'nombre_abstention' => 'integer',
        'nombre_absents' => 'integer',
        'pourcentage_discipline' => 'decimal:2',
        'deputes_dissidents' => 'array',
    ];

    /**
     * Relations
     */
    
    public function voteLegislatif(): BelongsTo
    {
        return $this->belongsTo(VoteLegislatif::class);
    }

    public function groupeParlementaire(): BelongsTo
    {
        return $this->belongsTo(GroupeParlementaire::class);
    }

    /**
     * Scopes
     */
    
    public function scopePour($query)
    {
        return $query->where('position_groupe', 'pour');
    }

    public function scopeContre($query)
    {
        return $query->where('position_groupe', 'contre');
    }

    public function scopeAbstention($query)
    {
        return $query->where('position_groupe', 'abstention');
    }

    public function scopeGroupe($query, int $groupeId)
    {
        return $query->where('groupe_parlementaire_id', $groupeId);
    }

    /**
     * Accessors
     */
    
    public function getTotalVotantsAttribute(): int
    {
        return $this->nombre_pour + $this->nombre_contre + $this->nombre_abstention;
    }

    public function getTotalMemebresAttribute(): int
    {
        return $this->total_votants + $this->nombre_absents;
    }

    public function getPourcentagePourAttribute(): float
    {
        if ($this->total_votants === 0) {
            return 0;
        }
        return round(($this->nombre_pour / $this->total_votants) * 100, 2);
    }

    public function getPourcentageContreAttribute(): float
    {
        if ($this->total_votants === 0) {
            return 0;
        }
        return round(($this->nombre_contre / $this->total_votants) * 100, 2);
    }

    public function getPourcentageAbstentionAttribute(): float
    {
        if ($this->total_votants === 0) {
            return 0;
        }
        return round(($this->nombre_abstention / $this->total_votants) * 100, 2);
    }

    public function getPositionLabelAttribute(): string
    {
        return match($this->position_groupe) {
            'pour' => 'Pour',
            'contre' => 'Contre',
            'abstention' => 'Abstention',
            'mixte' => 'Vote mixte',
            default => 'Non défini',
        };
    }

    public function getCouleurPositionAttribute(): string
    {
        return match($this->position_groupe) {
            'pour' => '#10b981', // green
            'contre' => '#ef4444', // red
            'abstention' => '#6b7280', // gray
            'mixte' => '#f59e0b', // amber
            default => '#9ca3af',
        };
    }

    /**
     * Méthodes métier
     */
    
    /**
     * Détermine automatiquement la position du groupe
     * en fonction des nombres de votes
     */
    public function determinerPosition(): string
    {
        $total = $this->nombre_pour + $this->nombre_contre + $this->nombre_abstention;
        
        if ($total === 0) {
            return 'abstention';
        }

        // Seuil pour considérer un vote comme "mixte"
        $seuilMixte = 0.3; // 30%

        $pourcentagePour = $this->nombre_pour / $total;
        $pourcentageContre = $this->nombre_contre / $total;
        $pourcentageAbstention = $this->nombre_abstention / $total;

        // Si aucune position ne domine nettement (> 70%), c'est mixte
        if ($pourcentagePour < 0.7 && $pourcentageContre < 0.7 && $pourcentageAbstention < 0.7) {
            return 'mixte';
        }

        // Sinon, on prend la position majoritaire
        $max = max($this->nombre_pour, $this->nombre_contre, $this->nombre_abstention);

        if ($this->nombre_pour === $max) {
            return 'pour';
        } elseif ($this->nombre_contre === $max) {
            return 'contre';
        } else {
            return 'abstention';
        }
    }

    /**
     * Calcule le pourcentage de discipline
     * (% de membres ayant voté dans la position du groupe)
     */
    public function calculerDiscipline(): float
    {
        $total = $this->total_votants;
        
        if ($total === 0) {
            return 0;
        }

        $votesPosition = match($this->position_groupe) {
            'pour' => $this->nombre_pour,
            'contre' => $this->nombre_contre,
            'abstention' => $this->nombre_abstention,
            'mixte' => max($this->nombre_pour, $this->nombre_contre, $this->nombre_abstention),
            default => 0,
        };

        return round(($votesPosition / $total) * 100, 2);
    }

    /**
     * Vérifie si le groupe a voté majoritairement avec succès
     * (c'est-à-dire dans le sens du résultat final du vote)
     */
    public function aVoteAvecSucces(): bool
    {
        $vote = $this->voteLegislatif;
        if (!$vote) {
            return false;
        }

        $resultat = $vote->resultat; // 'adopte', 'rejete', 'null'

        if ($resultat === 'adopte' && $this->position_groupe === 'pour') {
            return true;
        }

        if ($resultat === 'rejete' && $this->position_groupe === 'contre') {
            return true;
        }

        return false;
    }
}

