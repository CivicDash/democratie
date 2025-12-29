<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TexteJO extends Model
{
    protected $table = 'textes_jo';

    protected $fillable = [
        'jorf_id',
        'eli_url',
        'nor',
        'nature',
        'numero',
        'titre',
        'titre_court',
        'date_signature',
        'date_publication',
        'num_parution_jo',
        'visa',
        'nb_articles',
        'loi_loicod',
    ];

    protected $casts = [
        'date_signature' => 'date',
        'date_publication' => 'date',
        'nb_articles' => 'integer',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function articles(): HasMany
    {
        return $this->hasMany(ArticleJO::class, 'texte_jo_id');
    }

    public function loi(): BelongsTo
    {
        return $this->belongsTo(Loi::class, 'loi_loicod', 'loicod');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'texte_jo_tag')
            ->withPivot(['source', 'confidence', 'validated', 'suggested_by'])
            ->withTimestamps();
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeLois($query)
    {
        return $query->where('nature', 'LOI');
    }

    public function scopeDecrets($query)
    {
        return $query->where('nature', 'DECRET');
    }

    public function scopeOrdonnances($query)
    {
        return $query->where('nature', 'ORDONNANCE');
    }

    public function scopeRecents($query)
    {
        return $query->orderByDesc('date_publication');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getNatureIconeAttribute(): string
    {
        return match ($this->nature) {
            'LOI' => '📜',
            'DECRET' => '📋',
            'ORDONNANCE' => '⚖️',
            'ARRETE' => '📝',
            default => '📄',
        };
    }

    public function getLegifranceUrlAttribute(): string
    {
        if ($this->eli_url) {
            return $this->eli_url;
        }
        return "https://www.legifrance.gouv.fr/jorf/id/{$this->jorf_id}";
    }
}

