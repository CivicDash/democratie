<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SenateurQuestion extends Model
{
    protected $table = 'senateurs_questions';

    protected $fillable = [
        'senateur_matricule',
        'numero',
        'type',
        'texte_question',
        'ministre_destinataire',
        'date_question',
        'texte_reponse',
        'date_reponse',
        'a_reponse',
        'theme',
        'sous_theme',
    ];

    protected $casts = [
        'date_question' => 'date',
        'date_reponse' => 'date',
        'a_reponse' => 'boolean',
    ];

    /**
     * Relation vers le sénateur
     */
    public function senateur(): BelongsTo
    {
        return $this->belongsTo(Senateur::class, 'senateur_matricule', 'matricule');
    }

    /**
     * Scope: questions avec réponse
     */
    public function scopeRepondues($query)
    {
        return $query->where('a_reponse', true);
    }

    /**
     * Scope: questions sans réponse
     */
    public function scopeEnAttente($query)
    {
        return $query->where('a_reponse', false);
    }

    /**
     * Scope: par thème
     */
    public function scopeTheme($query, string $theme)
    {
        return $query->where('theme', $theme);
    }
}

