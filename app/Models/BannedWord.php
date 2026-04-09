<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BannedWord extends Model
{
    use HasFactory;

    protected $fillable = [
        'word',
        'category',
        'severity',
        'is_active',
        'is_regex',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_regex' => 'boolean',
    ];

    /**
     * Catégories disponibles
     */
    public const CATEGORIES = [
        'insulte' => 'Insultes',
        'spam' => 'Spam',
        'politique_extreme' => 'Politique extrême',
        'sexisme' => 'Sexisme',
        'racisme' => 'Racisme',
        'violence' => 'Violence',
        'general' => 'Général',
    ];

    /**
     * Niveaux de sévérité
     */
    public const SEVERITIES = [
        'low' => 'Faible',
        'medium' => 'Moyen',
        'high' => 'Élevé',
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
     * Scope: par sévérité minimum
     */
    public function scopeMinSeverity($query, string $severity)
    {
        $levels = ['low' => 1, 'medium' => 2, 'high' => 3];
        $minLevel = $levels[$severity] ?? 1;

        return $query->whereIn('severity', array_keys(array_filter($levels, fn ($l) => $l >= $minLevel)));
    }

    /**
     * Obtenir le pattern de recherche
     */
    public function getPatternAttribute(): string
    {
        if ($this->is_regex) {
            return $this->word;
        }

        // Escape les caractères spéciaux et crée un pattern insensible à la casse
        // qui détecte aussi les variantes avec des caractères spéciaux (m3rde, p*tain, etc.)
        $word = preg_quote($this->word, '/');

        // Remplacer les voyelles par des patterns qui matchent les variantes
        $replacements = [
            'a' => '[a@àáâãäå4]',
            'e' => '[e3éèêë€]',
            'i' => '[i1!|îï]',
            'o' => '[o0ôöò]',
            'u' => '[uùûü]',
        ];

        foreach ($replacements as $letter => $pattern) {
            $word = str_ireplace($letter, $pattern, $word);
        }

        return '/\b'.$word.'\b/iu';
    }
}
