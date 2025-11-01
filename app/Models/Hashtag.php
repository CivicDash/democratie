<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Laravel\Scout\Searchable;

/**
 * Hashtag (style Twitter)
 * 
 * @property int $id
 * @property string $slug Slug normalisé (lowercase, no accents)
 * @property string $display_name Nom affiché (original case)
 * @property int $usage_count Nombre total utilisations
 * @property bool $is_trending Hashtag tendance
 * @property bool $is_official Hashtag officiel thématique
 * @property bool $is_moderated Nécessite modération
 * @property string|null $description Description si officiel
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Hashtag extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'slug',
        'display_name',
        'usage_count',
        'is_trending',
        'is_official',
        'is_moderated',
        'description',
        'last_used_at',
    ];

    protected $casts = [
        'is_trending' => 'boolean',
        'is_official' => 'boolean',
        'is_moderated' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Posts utilisant ce hashtag
     */
    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }

    /**
     * Topics utilisant ce hashtag
     */
    public function topics(): MorphToMany
    {
        return $this->morphedByMany(Topic::class, 'taggable');
    }

    /**
     * Incrémente le compteur d'utilisation
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Décrémente le compteur d'utilisation
     */
    public function decrementUsage(): void
    {
        if ($this->usage_count > 0) {
            $this->decrement('usage_count');
        }
    }

    /**
     * Normalise un hashtag (slug)
     * 
     * #Climat → climat
     * #Réforme-Retraites → reforme-retraites
     */
    public static function normalize(string $hashtag): string
    {
        // Retirer # si présent
        $hashtag = ltrim($hashtag, '#');
        
        // Lowercase
        $hashtag = mb_strtolower($hashtag);
        
        // Retirer accents
        $hashtag = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $hashtag);
        
        // Conserver uniquement lettres, chiffres, tirets
        $hashtag = preg_replace('/[^a-z0-9\-]/', '', $hashtag);
        
        // Limiter longueur
        return substr($hashtag, 0, 50);
    }

    /**
     * Trouve ou crée un hashtag
     */
    public static function findOrCreate(string $hashtag): self
    {
        $slug = self::normalize($hashtag);
        $displayName = ltrim($hashtag, '#');

        return self::firstOrCreate(
            ['slug' => $slug],
            [
                'display_name' => $displayName,
                'usage_count' => 0,
                'last_used_at' => now(),
            ]
        );
    }

    /**
     * Scope : hashtags tendance (dernières 24h, > 10 usages)
     */
    public function scopeTrending($query)
    {
        return $query->where('last_used_at', '>=', now()->subDay())
            ->where('usage_count', '>=', 10)
            ->orderBy('usage_count', 'desc');
    }

    /**
     * Scope : hashtags officiels
     */
    public function scopeOfficial($query)
    {
        return $query->where('is_official', true);
    }

    /**
     * Scope : les plus utilisés
     */
    public function scopePopular($query, int $limit = 20)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    /**
     * Scope : recherche par slug partiel
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('slug', 'like', '%' . self::normalize($term) . '%')
            ->orWhere('display_name', 'like', '%' . $term . '%');
    }

    /**
     * Configuration Meilisearch
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'display_name' => $this->display_name,
            'usage_count' => $this->usage_count,
            'is_official' => $this->is_official,
            'description' => $this->description,
        ];
    }
}
