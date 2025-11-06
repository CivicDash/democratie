<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupeParlementaire extends Model
{
    use HasFactory;

    protected $table = 'groupes_parlementaires';

    protected $fillable = [
        'source',
        'chambre', // Alias pour source
        'uid',
        'nom',
        'slug',
        'sigle',
        'couleur_hex',
        'position_politique',
        'nombre_membres',
        'president_nom',
        'president', // Alias pour president_nom
        'logo_url',
        'url_officiel',
        'site_web', // Alias pour url_officiel
        'legislature',
        'actif',
        'est_actif', // Alias pour actif
        'apparentes',
        'description',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'legislature' => 'integer',
        'nombre_membres' => 'integer',
        'apparentes' => 'array',
    ];

    /**
     * Relations
     */
    
    public function deputes(): HasMany
    {
        return $this->hasMany(DeputeSenateur::class, 'groupe_politique', 'sigle')
            ->where('source', $this->source);
    }

    public function votesGroupes(): HasMany
    {
        return $this->hasMany(VoteGroupeParlementaire::class);
    }

    /**
     * Scopes
     */
    
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeAssemblee($query)
    {
        return $query->where('source', 'assemblee');
    }

    public function scopeSenat($query)
    {
        return $query->where('source', 'senat');
    }

    public function scopeLegislature($query, int $legislature)
    {
        return $query->where('legislature', $legislature);
    }

    public function scopePosition($query, string $position)
    {
        return $query->where('position_politique', $position);
    }

    /**
     * Accessors & Mutators
     */
    
    public function getNomCompletAttribute(): string
    {
        return $this->nom . ' (' . $this->sigle . ')';
    }

    public function getPositionLabelAttribute(): string
    {
        return match($this->position_politique) {
            'extreme_gauche' => 'Extrême gauche',
            'gauche' => 'Gauche',
            'centre_gauche' => 'Centre gauche',
            'centre' => 'Centre',
            'centre_droit' => 'Centre droit',
            'droite' => 'Droite',
            'extreme_droite' => 'Extrême droite',
            'non_inscrit' => 'Non inscrit',
            default => 'Non défini',
        };
    }

    public function getCouleurCssAttribute(): string
    {
        return $this->couleur_hex;
    }

    /**
     * Méthodes métier
     */
    
    /**
     * Calcule les statistiques de vote du groupe
     */
    public function getStatistiquesVote(?\DateTime $debut = null, ?\DateTime $fin = null): array
    {
        $query = $this->votesGroupes();

        if ($debut) {
            $query->where('created_at', '>=', $debut);
        }

        if ($fin) {
            $query->where('created_at', '<=', $fin);
        }

        $votes = $query->get();

        return [
            'total_votes' => $votes->count(),
            'votes_pour' => $votes->where('position_groupe', 'pour')->count(),
            'votes_contre' => $votes->where('position_groupe', 'contre')->count(),
            'votes_abstention' => $votes->where('position_groupe', 'abstention')->count(),
            'votes_mixte' => $votes->where('position_groupe', 'mixte')->count(),
            'discipline_moyenne' => round($votes->avg('pourcentage_discipline'), 2),
            'pourcentage_pour' => $votes->count() > 0 
                ? round(($votes->where('position_groupe', 'pour')->count() / $votes->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Obtient les thématiques favorites du groupe
     */
    public function getThematiquesFavorites(int $limit = 5): array
    {
        $thematiquesCounts = [];

        // Récupérer tous les votes du groupe
        $votes = $this->votesGroupes()
            ->with('voteLegislatif.propositionLoi.thematiques')
            ->get();

        foreach ($votes as $vote) {
            $proposition = $vote->voteLegislatif?->propositionLoi;
            if (!$proposition) continue;

            foreach ($proposition->thematiques as $thematique) {
                $code = $thematique->code;
                if (!isset($thematiquesCounts[$code])) {
                    $thematiquesCounts[$code] = [
                        'thematique' => $thematique,
                        'count' => 0,
                    ];
                }
                $thematiquesCounts[$code]['count']++;
            }
        }

        // Trier par count décroissant
        usort($thematiquesCounts, fn($a, $b) => $b['count'] <=> $a['count']);

        return array_slice($thematiquesCounts, 0, $limit);
    }

    /**
     * Vérifie si le groupe est de gauche
     */
    public function isGauche(): bool
    {
        return in_array($this->position_politique, ['extreme_gauche', 'gauche', 'centre_gauche']);
    }

    /**
     * Vérifie si le groupe est de droite
     */
    public function isDroite(): bool
    {
        return in_array($this->position_politique, ['extreme_droite', 'droite', 'centre_droit']);
    }

    /**
     * Vérifie si le groupe est au centre
     */
    public function isCentre(): bool
    {
        return $this->position_politique === 'centre';
    }
}

