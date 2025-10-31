<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThematiqueLegislation extends Model
{
    use HasFactory;

    protected $table = 'thematiques_legislation';

    protected $fillable = [
        'code',
        'nom',
        'description',
        'couleur_hex',
        'icone',
        'parent_id',
        'ordre',
        'mots_cles',
        'synonymes',
        'nb_propositions',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'ordre' => 'integer',
        'nb_propositions' => 'integer',
        'mots_cles' => 'array',
        'synonymes' => 'array',
    ];

    /**
     * Relations
     */
    
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ThematiqueLegislation::class, 'parent_id');
    }

    public function enfants(): HasMany
    {
        return $this->hasMany(ThematiqueLegislation::class, 'parent_id')->orderBy('ordre');
    }

    public function propositions(): BelongsToMany
    {
        return $this->belongsToMany(PropositionLoi::class, 'proposition_loi_thematique')
            ->withPivot(['est_principal', 'confiance', 'tags_keywords', 'tagged_by'])
            ->withTimestamps();
    }

    public function propositionsPrincipales(): BelongsToMany
    {
        return $this->propositions()->wherePivot('est_principal', true);
    }

    /**
     * Scopes
     */
    
    public function scopePrincipales($query)
    {
        return $query->whereNull('parent_id')->orderBy('ordre');
    }

    public function scopeAvecEnfants($query)
    {
        return $query->with('enfants');
    }

    public function scopeRecherche($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nom', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Accessors
     */
    
    public function getMotsClesCombinesAttribute(): array
    {
        $motsCles = $this->mots_cles ?? [];
        $synonymes = $this->synonymes ?? [];
        
        return array_unique(array_merge($motsCles, $synonymes));
    }

    public function getCouleurCssAttribute(): string
    {
        return $this->couleur_hex;
    }

    /**
     * Méthodes métier
     */
    
    /**
     * Incrémente le compteur de propositions
     */
    public function incrementNbPropositions(): void
    {
        $this->increment('nb_propositions');
    }

    /**
     * Décrémente le compteur de propositions
     */
    public function decrementNbPropositions(): void
    {
        $this->decrement('nb_propositions');
    }

    /**
     * Recalcule le nombre de propositions
     */
    public function recalculerNbPropositions(): void
    {
        $count = $this->propositions()->count();
        $this->update(['nb_propositions' => $count]);
    }

    /**
     * Obtient les propositions récentes de cette thématique
     */
    public function getPropositionsRecentes(int $limit = 10)
    {
        return $this->propositions()
            ->orderBy('propositions_loi.date_depot', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calcule le score de pertinence d'un texte pour cette thématique
     * Retourne un score entre 0 et 100
     */
    public function calculerScore(string $texte): int
    {
        $texte = mb_strtolower($texte);
        $motsCles = $this->mots_cles_combines;
        
        if (empty($motsCles)) {
            return 0;
        }

        $score = 0;
        $totalMotsCles = count($motsCles);

        foreach ($motsCles as $motCle) {
            $motCle = mb_strtolower($motCle);
            
            // Recherche exacte
            if (str_contains($texte, $motCle)) {
                $score += 10;
                
                // Bonus si le mot-clé est dans le titre (supposons que les 100 premiers caractères sont le titre)
                if (str_contains(mb_substr($texte, 0, 100), $motCle)) {
                    $score += 5;
                }
            }
        }

        // Normaliser sur 100
        $scoreMax = $totalMotsCles * 15; // 10 + 5 bonus max
        $scoreNormalise = $scoreMax > 0 ? min(100, round(($score / $scoreMax) * 100)) : 0;

        return $scoreNormalise;
    }

    /**
     * Obtient les statistiques de la thématique
     */
    public function getStatistiques(): array
    {
        $propositions = $this->propositions;

        return [
            'total_propositions' => $propositions->count(),
            'propositions_adoptees' => $propositions->where('statut', 'adoptee')->count(),
            'propositions_rejetees' => $propositions->where('statut', 'rejetee')->count(),
            'propositions_en_cours' => $propositions->whereIn('statut', ['depose', 'en_discussion'])->count(),
            'source_assemblee' => $propositions->where('source', 'assemblee')->count(),
            'source_senat' => $propositions->where('source', 'senat')->count(),
        ];
    }

    /**
     * Obtient les groupes les plus actifs sur cette thématique
     */
    public function getGroupesActifs(int $limit = 5): array
    {
        $groupesCounts = [];

        foreach ($this->propositions as $proposition) {
            // Récupérer les votes liés
            $votes = $proposition->votesLegislatifs()->with('votesGroupes.groupeParlementaire')->get();

            foreach ($votes as $vote) {
                foreach ($vote->votesGroupes as $voteGroupe) {
                    $groupe = $voteGroupe->groupeParlementaire;
                    if (!$groupe) continue;

                    $key = $groupe->id;
                    if (!isset($groupesCounts[$key])) {
                        $groupesCounts[$key] = [
                            'groupe' => $groupe,
                            'votes_pour' => 0,
                            'votes_contre' => 0,
                            'votes_abstention' => 0,
                        ];
                    }

                    match($voteGroupe->position_groupe) {
                        'pour' => $groupesCounts[$key]['votes_pour']++,
                        'contre' => $groupesCounts[$key]['votes_contre']++,
                        'abstention' => $groupesCounts[$key]['votes_abstention']++,
                        default => null,
                    };
                }
            }
        }

        // Trier par nombre total de votes
        usort($groupesCounts, function($a, $b) {
            $totalA = $a['votes_pour'] + $a['votes_contre'] + $a['votes_abstention'];
            $totalB = $b['votes_pour'] + $b['votes_contre'] + $b['votes_abstention'];
            return $totalB <=> $totalA;
        });

        return array_slice($groupesCounts, 0, $limit);
    }
}

