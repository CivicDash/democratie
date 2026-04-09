<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoiStats extends Model
{
    protected $table = 'lois_stats';

    protected $fillable = [
        'loicod',
        'etapes_total',
        'etapes_an',
        'etapes_senat',
        'amendements_total',
        'amendements_adoptes',
        'amendements_rejetes',
        'amendements_retires',
        'taux_adoption_amendements',
        'scrutins_total',
        'scrutins_adoptes',
        'scrutins_rejetes',
        'duree_jours',
        'date_premiere_etape',
        'date_derniere_etape',
        'score_engagement',
        'calculated_at',
    ];

    protected $casts = [
        'taux_adoption_amendements' => 'float',
        'date_premiere_etape' => 'date',
        'date_derniere_etape' => 'date',
        'calculated_at' => 'datetime',
    ];

    /**
     * Relation vers la loi
     */
    public function loi(): BelongsTo
    {
        return $this->belongsTo(Loi::class, 'loicod', 'loicod');
    }

    /**
     * Récupérer les stats d'une loi
     */
    public static function forLoi(string $loicod): ?self
    {
        return static::where('loicod', $loicod)->first();
    }

    /**
     * Mettre à jour ou créer les stats d'une loi
     */
    public static function updateLoiStats(string $loicod, array $data): self
    {
        return static::updateOrCreate(
            ['loicod' => $loicod],
            array_merge($data, ['calculated_at' => now()])
        );
    }

    /**
     * Vérifier si les stats sont obsolètes (plus de 24h)
     */
    public function isStale(): bool
    {
        if (! $this->calculated_at) {
            return true;
        }

        return $this->calculated_at->diffInHours(now()) >= 24;
    }

    /**
     * Convertir en tableau pour la vue
     */
    public function toViewArray(): array
    {
        return [
            'etapes_total' => $this->etapes_total,
            'etapes_an' => $this->etapes_an,
            'etapes_senat' => $this->etapes_senat,
            'amendements_total' => $this->amendements_total,
            'amendements_adoptes' => $this->amendements_adoptes,
            'amendements_rejetes' => $this->amendements_rejetes,
            'taux_adoption_amendements' => round($this->taux_adoption_amendements, 1),
            'scrutins_total' => $this->scrutins_total,
            'duree_jours' => $this->duree_jours,
            'score_engagement' => $this->score_engagement,
            'calculated_at' => $this->calculated_at?->format('d/m/Y H:i'),
        ];
    }

    /**
     * Scope pour les lois les plus actives
     */
    public function scopeMostActive($query, int $limit = 10)
    {
        return $query->orderByDesc('score_engagement')->limit($limit);
    }

    /**
     * Scope pour les lois avec beaucoup d'amendements
     */
    public function scopeWithManyAmendements($query, int $minCount = 50)
    {
        return $query->where('amendements_total', '>=', $minCount);
    }
}
