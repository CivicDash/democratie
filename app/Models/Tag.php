<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = [
        'nom',
        'slug',
        'description',
        'couleur',
        'icone',
        'type',
        'source',
        'validated',
        'validated_by',
        'validated_at',
        'usage_count',
    ];

    protected $casts = [
        'validated' => 'boolean',
        'validated_at' => 'datetime',
        'usage_count' => 'integer',
    ];

    // ==========================================
    // BOOT
    // ==========================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->nom);
            }
        });
    }

    // ==========================================
    // RELATIONS
    // ==========================================

    public function validatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function lois(): BelongsToMany
    {
        return $this->belongsToMany(Loi::class, 'loi_tag', 'tag_id', 'loi_loicod', 'id', 'loicod')
            ->withPivot(['source', 'confidence', 'validated', 'suggested_by'])
            ->withTimestamps();
    }

    public function textesJo(): BelongsToMany
    {
        return $this->belongsToMany(TexteJO::class, 'texte_jo_tag', 'tag_id', 'texte_jo_id')
            ->withPivot(['source', 'confidence', 'validated'])
            ->withTimestamps();
    }

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class, 'topic_tag')
            ->withPivot(['source', 'confidence'])
            ->withTimestamps();
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeValidated($query)
    {
        return $query->where('validated', true);
    }

    public function scopeOfficial($query)
    {
        return $query->where('source', 'official');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeThematiques($query)
    {
        return $query->where('type', 'thematique');
    }

    public function scopeKeywords($query)
    {
        return $query->where('type', 'keyword');
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('usage_count');
    }

    // ==========================================
    // MÉTHODES
    // ==========================================

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function validate(User $user): void
    {
        $this->update([
            'validated' => true,
            'validated_by' => $user->id,
            'validated_at' => now(),
        ]);
    }

    // ==========================================
    // COULEURS PAR THÉMATIQUE
    // ==========================================

    public static array $thematicColors = [
        'agriculture' => '#059669',      // Emerald
        'culture' => '#8B5CF6',          // Violet
        'defense' => '#1E40AF',          // Blue
        'economie' => '#F59E0B',         // Amber
        'education' => '#06B6D4',        // Cyan
        'environnement' => '#10B981',    // Green
        'justice' => '#6366F1',          // Indigo
        'sante' => '#EF4444',            // Red
        'travail' => '#F97316',          // Orange
        'transport' => '#64748B',        // Slate
        'logement' => '#A855F7',         // Purple
        'securite' => '#DC2626',         // Red dark
        'europe' => '#3B82F6',           // Blue
        'outre-mer' => '#14B8A6',        // Teal
        'collectivites' => '#84CC16',    // Lime
        'default' => '#6B7280',          // Gray
    ];

    public static array $thematicIcons = [
        'agriculture' => '🌾',
        'culture' => '🎭',
        'defense' => '🛡️',
        'economie' => '💰',
        'education' => '📚',
        'environnement' => '🌍',
        'justice' => '⚖️',
        'sante' => '🏥',
        'travail' => '💼',
        'transport' => '🚄',
        'logement' => '🏠',
        'securite' => '🚨',
        'europe' => '🇪🇺',
        'outre-mer' => '🏝️',
        'collectivites' => '🏛️',
        'default' => '🏷️',
    ];

    public static function getColorForTheme(string $theme): string
    {
        $slug = Str::slug($theme);
        foreach (self::$thematicColors as $key => $color) {
            if (str_contains($slug, $key)) {
                return $color;
            }
        }
        return self::$thematicColors['default'];
    }

    public static function getIconForTheme(string $theme): string
    {
        $slug = Str::slug($theme);
        foreach (self::$thematicIcons as $key => $icon) {
            if (str_contains($slug, $key)) {
                return $icon;
            }
        }
        return self::$thematicIcons['default'];
    }
}
