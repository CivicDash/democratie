<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CommuneConsultation extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'commune_page_id',
        'auteur_id',
        'titre',
        'slug',
        'description',
        'options',
        'multiple',
        'publie',
        'fermee',
        'publie_at',
        'ferme_at',
        'votes_count',
    ];

    protected $casts = [
        'options' => 'array',
        'multiple' => 'boolean',
        'publie' => 'boolean',
        'fermee' => 'boolean',
        'publie_at' => 'datetime',
        'ferme_at' => 'datetime',
        'votes_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($c) => $c->slug = $c->slug ?: Str::slug($c->titre));
    }

    public function communePage(): BelongsTo
    {
        return $this->belongsTo(CommunePage::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(CommuneConsultationVote::class, 'consultation_id');
    }

    public function scopePubliees($query)
    {
        return $query->where('publie', true);
    }

    public function scopeOuvertes($query)
    {
        return $query->publiees()->where('fermee', false);
    }

    public function getEstOuverteAttribute(): bool
    {
        if (! $this->publie || $this->fermee) {
            return false;
        }
        if ($this->ferme_at && $this->ferme_at->isPast()) {
            return false;
        }
        return true;
    }

    public function aVote(User $user): bool
    {
        return $this->votes()->where('user_id', $user->id)->exists();
    }

    public function getResultats(): array
    {
        $options = $this->options ?? [];
        $votes = $this->votes()->selectRaw('option_key, count(*) as total')->groupBy('option_key')->pluck('total', 'option_key');
        $totalVotes = $votes->sum();

        return collect($options)->map(function ($option) use ($votes, $totalVotes) {
            $key = $option['key'];
            $count = $votes[$key] ?? 0;
            return [
                'key' => $key,
                'label' => $option['label'],
                'votes' => $count,
                'pourcentage' => $totalVotes > 0 ? round(($count / $totalVotes) * 100, 1) : 0,
            ];
        })->toArray();
    }
}
