<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NiceWord extends Model
{
    use HasFactory;

    protected $fillable = [
        'word',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Catégories de mots gentils
     */
    public const CATEGORIES = [
        'compliment' => 'Compliments',
        'emoji' => 'Emojis',
        'nature' => 'Nature',
        'animal' => 'Animaux mignons',
        'nourriture' => 'Nourriture',
        'positif' => 'Mots positifs',
        'drole' => 'Drôle',
    ];

    /**
     * Scope: mots actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: par catégorie
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Récupérer un mot gentil aléatoire
     */
    public static function getRandomNice(): string
    {
        $word = static::active()->inRandomOrder()->first();

        return $word ? $word->word : '💖';
    }

    /**
     * Récupérer un mot gentil aléatoire par catégorie
     */
    public static function getRandomNiceFromCategory(string $category): string
    {
        $word = static::active()
            ->inCategory($category)
            ->inRandomOrder()
            ->first();

        return $word ? $word->word : static::getRandomNice();
    }
}
