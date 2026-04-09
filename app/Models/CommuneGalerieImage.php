<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CommuneGalerieImage extends Model
{
    protected $table = 'commune_galerie_images';

    protected $fillable = [
        'commune_page_id',
        'image_path',
        'legende',
        'credit',
        'ordre',
        'source',
        'wikimedia_url',
        'visible',
    ];

    protected $casts = [
        'ordre' => 'integer',
        'visible' => 'boolean',
    ];

    public function communePage(): BelongsTo
    {
        return $this->belongsTo(CommunePage::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->source === 'wikimedia' && $this->wikimedia_url) {
            return $this->wikimedia_url;
        }

        if ($this->image_path) {
            return Storage::disk('public')->url($this->image_path);
        }

        return null;
    }

    public function scopeVisibles($query)
    {
        return $query->where('visible', true);
    }

    public function scopeOrdonne($query)
    {
        return $query->orderBy('ordre');
    }
}
