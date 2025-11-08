<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Intervention parlementaire (discours, prise de parole)
 */
class InterventionParlementaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'depute_senateur_id',
        'date_intervention',
        'type',
        'titre',
        'sujet',
        'contenu',
        'duree_secondes',
        'nb_mots',
        'url_video',
        'url_texte',
    ];

    protected $casts = [
        'date_intervention' => 'date',
        'duree_secondes' => 'integer',
        'nb_mots' => 'integer',
    ];

    public function deputeSenateur(): BelongsTo
    {
        return $this->belongsTo(DeputeSenateur::class, 'depute_senateur_id');
    }

    public function getDureeMinutesAttribute(): ?int
    {
        if (!$this->duree_secondes) {
            return null;
        }
        return (int) round($this->duree_secondes / 60);
    }
}

