<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurisprudenceLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'legal_reference_id',
        'legifrance_juri_id',
        'external_url',
        'jurisdiction',
        'date_decision',
        'decision_number',
        'title',
        'summary',
        'full_text',
        'themes',
        'keywords',
        'relevance_score',
        'citation_count',
        'formation',
        'decision_type',
    ];

    protected $casts = [
        'date_decision' => 'date',
        'themes' => 'array',
        'keywords' => 'array',
    ];

    /**
     * Référence juridique associée
     */
    public function legalReference(): BelongsTo
    {
        return $this->belongsTo(LegalReference::class);
    }

    /**
     * Obtenir le label de la juridiction
     */
    public function getJurisdictionLabelAttribute(): string
    {
        $labels = [
            'CE' => 'Conseil d\'État',
            'CC' => 'Conseil Constitutionnel',
            'Cass.Civ' => 'Cour de Cassation - Chambre Civile',
            'Cass.Crim' => 'Cour de Cassation - Chambre Criminelle',
            'Cass.Soc' => 'Cour de Cassation - Chambre Sociale',
            'Cass.Com' => 'Cour de Cassation - Chambre Commerciale',
            'CA' => 'Cour d\'Appel',
            'TGI' => 'Tribunal de Grande Instance',
            'TJ' => 'Tribunal Judiciaire',
            'TA' => 'Tribunal Administratif',
            'CAA' => 'Cour Administrative d\'Appel',
        ];
        
        return $labels[$this->jurisdiction] ?? $this->jurisdiction;
    }

    /**
     * Obtenir le label du type de décision
     */
    public function getDecisionTypeLabelAttribute(): string
    {
        return match($this->decision_type) {
            'arret' => 'Arrêt',
            'jugement' => 'Jugement',
            'ordonnance' => 'Ordonnance',
            'avis' => 'Avis',
            default => 'Autre',
        };
    }

    /**
     * Obtenir l'URL Légifrance de la jurisprudence
     */
    public function getLegifranceUrlAttribute(): ?string
    {
        if ($this->external_url) {
            return $this->external_url;
        }
        
        if ($this->legifrance_juri_id) {
            return "https://www.legifrance.gouv.fr/juri/id/{$this->legifrance_juri_id}";
        }
        
        return null;
    }

    /**
     * Scope: Par juridiction
     */
    public function scopeByJurisdiction($query, string $jurisdiction)
    {
        return $query->where('jurisdiction', $jurisdiction);
    }

    /**
     * Scope: Par pertinence minimale
     */
    public function scopeMinRelevance($query, int $minScore = 50)
    {
        return $query->where('relevance_score', '>=', $minScore);
    }

    /**
     * Scope: Récentes
     */
    public function scopeRecent($query, int $years = 5)
    {
        return $query->where('date_decision', '>=', now()->subYears($years));
    }

    /**
     * Scope: Ordonnées par pertinence
     */
    public function scopeOrderByRelevance($query)
    {
        return $query->orderBy('relevance_score', 'desc');
    }
}
