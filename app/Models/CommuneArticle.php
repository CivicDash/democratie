<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CommuneArticle extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'commune_page_id',
        'auteur_id',
        'titre',
        'slug',
        'contenu',
        'extrait',
        'image_path',
        'categorie',
        'epingle',
        'publie',
        'publie_at',
        'vues_count',
    ];

    protected $casts = [
        'epingle' => 'boolean',
        'publie' => 'boolean',
        'publie_at' => 'datetime',
        'vues_count' => 'integer',
    ];

    public const CATEGORIES = [
        'info_generale' => 'Information générale',
        'travaux' => 'Travaux',
        'culture' => 'Culture',
        'sport' => 'Sport',
        'association' => 'Vie associative',
        'urbanisme' => 'Urbanisme',
        'securite' => 'Sécurité',
        'environnement' => 'Environnement',
        'education' => 'Éducation',
        'social' => 'Social',
        'officiel' => 'Communication officielle',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->titre);
            }
        });
    }

    // ========================================================================
    // RELATIONS
    // ========================================================================

    public function communePage(): BelongsTo
    {
        return $this->belongsTo(CommunePage::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopePublies($query)
    {
        return $query->where('publie', true)
            ->where('publie_at', '<=', now());
    }

    public function scopeEpingles($query)
    {
        return $query->where('epingle', true);
    }

    public function scopeParCategorie($query, string $categorie)
    {
        return $query->where('categorie', $categorie);
    }

    public function scopeRecents($query)
    {
        return $query->orderByDesc('publie_at');
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getCategorieLabellAttribute(): string
    {
        return self::CATEGORIES[$this->categorie] ?? ucfirst($this->categorie);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    public function getExtraitAutoAttribute(): string
    {
        if ($this->extrait) {
            return $this->extrait;
        }

        return Str::limit(strip_tags($this->contenu), 200);
    }

    public function getEstPublieAttribute(): bool
    {
        return $this->publie && $this->publie_at?->isPast();
    }

    // ========================================================================
    // MÉTHODES
    // ========================================================================

    public function publier(): void
    {
        $this->update([
            'publie' => true,
            'publie_at' => $this->publie_at ?? now(),
        ]);
    }

    public function depublier(): void
    {
        $this->update(['publie' => false]);
    }

    public function incrementerVues(): void
    {
        $this->increment('vues_count');
    }
}
