<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Version de politique de confidentialité / CGU
 * 
 * Conforme RGPD Art. 13 (information fournie)
 * 
 * @property int $id
 * @property string $version Ex: 1.0.0
 * @property string $policy_type privacy|terms|cookies
 * @property string $content_summary Résumé changements majeurs
 * @property string $file_path Chemin fichier Markdown
 * @property bool $is_current Version active
 * @property \Illuminate\Support\Carbon $effective_at Date entrée en vigueur
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PolicyVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'policy_type',
        'content_summary',
        'file_path',
        'is_current',
        'effective_at',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'effective_at' => 'datetime',
    ];

    /**
     * Types de politique
     */
    public const TYPE_PRIVACY = 'privacy';
    public const TYPE_TERMS = 'terms';
    public const TYPE_COOKIES = 'cookies';

    public const TYPES = [
        self::TYPE_PRIVACY,
        self::TYPE_TERMS,
        self::TYPE_COOKIES,
    ];

    /**
     * Récupère le contenu Markdown de la politique
     */
    public function getContentAttribute(): string
    {
        $fullPath = storage_path('app/' . $this->file_path);
        
        if (!file_exists($fullPath)) {
            return '';
        }

        return file_get_contents($fullPath);
    }

    /**
     * Marque cette version comme courante (désactive les autres)
     */
    public function markAsCurrent(): void
    {
        // Désactiver toutes les autres versions du même type
        self::where('policy_type', $this->policy_type)
            ->where('id', '!=', $this->id)
            ->update(['is_current' => false]);

        // Activer cette version
        $this->update(['is_current' => true]);
    }

    /**
     * Scope : versions courantes
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope : par type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('policy_type', $type);
    }

    /**
     * Scope : versions effectives
     */
    public function scopeEffective($query)
    {
        return $query->where('effective_at', '<=', now());
    }

    /**
     * Récupère la version courante d'un type de politique
     */
    public static function getCurrentVersion(string $type): ?self
    {
        return self::ofType($type)->current()->first();
    }

    /**
     * Récupère le numéro de version courante (string)
     */
    public static function getCurrentVersionNumber(string $type): string
    {
        $version = self::getCurrentVersion($type);
        return $version ? $version->version : '1.0.0';
    }
}
