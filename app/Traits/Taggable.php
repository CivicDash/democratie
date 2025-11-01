<?php

namespace App\Traits;

use App\Models\Hashtag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Trait Taggable
 * 
 * Ajoute la capacité d'ajouter des hashtags à un modèle (Post, Topic, etc.)
 */
trait Taggable
{
    /**
     * Relation hashtags polymorphic
     */
    public function hashtags(): MorphToMany
    {
        return $this->morphToMany(Hashtag::class, 'taggable');
    }

    /**
     * Attache des hashtags depuis un tableau de strings
     * 
     * @param array $hashtagStrings Ex: ['climat', '#santé', 'Éducation']
     */
    public function attachHashtags(array $hashtagStrings): void
    {
        $hashtagIds = [];

        foreach ($hashtagStrings as $hashtagString) {
            $hashtag = Hashtag::findOrCreate($hashtagString);
            $hashtagIds[] = $hashtag->id;
        }

        // Sync (supprime anciens, ajoute nouveaux)
        $this->hashtags()->sync($hashtagIds);

        // Mettre à jour les compteurs
        foreach ($hashtagIds as $id) {
            Hashtag::find($id)?->incrementUsage();
        }
    }

    /**
     * Détache tous les hashtags
     */
    public function detachAllHashtags(): void
    {
        // Décrémenter compteurs
        foreach ($this->hashtags as $hashtag) {
            $hashtag->decrementUsage();
        }

        $this->hashtags()->detach();
    }

    /**
     * Vérifie si le modèle a un hashtag spécifique
     */
    public function hasHashtag(string $slug): bool
    {
        $normalizedSlug = Hashtag::normalize($slug);
        return $this->hashtags()->where('slug', $normalizedSlug)->exists();
    }

    /**
     * Récupère les hashtags sous forme de strings
     * 
     * @return array Ex: ['climat', 'sante', 'education']
     */
    public function getHashtagStrings(): array
    {
        return $this->hashtags->pluck('slug')->toArray();
    }

    /**
     * Récupère les hashtags avec # pour affichage
     * 
     * @return array Ex: ['#Climat', '#Santé', '#Éducation']
     */
    public function getHashtagDisplay(): array
    {
        return $this->hashtags->map(fn($h) => '#' . $h->display_name)->toArray();
    }

    /**
     * Scope : filtre par hashtag
     */
    public function scopeWithHashtag($query, string $slug)
    {
        $normalizedSlug = Hashtag::normalize($slug);
        
        return $query->whereHas('hashtags', function ($q) use ($normalizedSlug) {
            $q->where('slug', $normalizedSlug);
        });
    }

    /**
     * Scope : filtre par plusieurs hashtags (AND)
     */
    public function scopeWithAllHashtags($query, array $slugs)
    {
        foreach ($slugs as $slug) {
            $query->withHashtag($slug);
        }
        
        return $query;
    }

    /**
     * Scope : filtre par au moins un hashtag (OR)
     */
    public function scopeWithAnyHashtag($query, array $slugs)
    {
        $normalizedSlugs = array_map(fn($s) => Hashtag::normalize($s), $slugs);
        
        return $query->whereHas('hashtags', function ($q) use ($normalizedSlugs) {
            $q->whereIn('slug', $normalizedSlugs);
        });
    }
}

