<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'color',
        'icon',
        'description',
        'usage_count',
    ];

    /**
     * Dossiers législatifs associés
     */
    public function dossiersLegislatifs(): BelongsToMany
    {
        return $this->belongsToMany(
            DossierLegislatifAN::class,
            'dossier_legislatif_tag',
            'tag_id',
            'dossier_legislatif_uid',
            'id',
            'uid'
        );
    }

    /**
     * Scrutins associés
     */
    public function scrutins(): BelongsToMany
    {
        return $this->belongsToMany(
            ScrutinAN::class,
            'scrutin_tag',
            'tag_id',
            'scrutin_uid',
            'id',
            'uid'
        );
    }

    /**
     * Topics associés
     */
    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class, 'tag_topic');
    }

    /**
     * Incrémenter le compteur d'usage
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Scope pour les tags populaires
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }
}

