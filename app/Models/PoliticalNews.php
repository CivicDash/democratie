<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoliticalNews extends Model
{
    protected $table = 'political_news';

    protected $fillable = [
        'title',
        'description',
        'url',
        'image_url',
        'source_feed',
        'category',
        'published_at',
        'guid',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeRecent($query, int $limit = 20)
    {
        return $query->orderBy('published_at', 'desc')->limit($limit);
    }

    public function scopeFeed($query, string $feed)
    {
        return $query->where('source_feed', $feed);
    }

    public function getRelativeTimeAttribute(): string
    {
        return $this->published_at?->diffForHumans() ?? '';
    }

    public function getSourceLabelAttribute(): string
    {
        return match ($this->source_feed) {
            'front-national' => 'RN',
            'nouveau-front-populaire' => 'NFP',
            'les-republicains' => 'LR',
            'parti-communiste-francais' => 'PCF',
            'eelv' => 'EELV',
            'ps' => 'PS',
            'elections' => 'Élections',
            'assemblee-nationale' => 'Assemblée nationale',
            'politique' => 'Politique',
            'plans-sociaux' => 'Plans sociaux',
            'economie' => 'Économie',
            default => ucfirst($this->source_feed),
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'partis' => 'Partis politiques',
            'institutions' => 'Institutions',
            'thematique' => 'Thématique',
            default => ucfirst($this->category),
        };
    }
}
