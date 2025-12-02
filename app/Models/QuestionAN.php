<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Question au Gouvernement (Assemblée Nationale)
 * 
 * Source: data.assemblee-nationale.fr
 */
class QuestionAN extends Model
{
    protected $table = 'questions_an';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'numero',
        'legislature',
        'type',
        'acteur_ref',
        'mandat_ref',
        'groupe_ref',
        'groupe_sigle',
        'groupe_nom',
        'ministere_ref',
        'ministere_sigle',
        'ministere_nom',
        'rubrique',
        'analyse',
        'texte_question',
        'texte_reponse',
        'date_question',
        'date_reponse',
        'page_jo',
        'code_cloture',
        'libelle_cloture',
        'date_cloture',
    ];

    protected $casts = [
        'numero' => 'integer',
        'legislature' => 'integer',
        'date_question' => 'date',
        'date_reponse' => 'date',
        'date_cloture' => 'date',
    ];

    /**
     * Relation avec l'acteur (député)
     */
    public function acteur(): BelongsTo
    {
        return $this->belongsTo(ActeurAN::class, 'acteur_ref', 'uid');
    }

    /**
     * Relation avec le groupe parlementaire
     */
    public function groupe(): BelongsTo
    {
        return $this->belongsTo(OrganeAN::class, 'groupe_ref', 'uid');
    }

    /**
     * Scope pour les questions répondues
     */
    public function scopeRepondues($query)
    {
        return $query->whereNotNull('date_reponse');
    }

    /**
     * Scope pour les questions en attente
     */
    public function scopeEnAttente($query)
    {
        return $query->whereNull('date_reponse');
    }

    /**
     * Scope par législature
     */
    public function scopeLegislature($query, int $legislature)
    {
        return $query->where('legislature', $legislature);
    }

    /**
     * Scope par ministère
     */
    public function scopeMinistere($query, string $ministere)
    {
        return $query->where('ministere_sigle', 'ILIKE', "%{$ministere}%")
                     ->orWhere('ministere_nom', 'ILIKE', "%{$ministere}%");
    }

    /**
     * Délai de réponse en jours
     */
    public function getDelaiReponseAttribute(): ?int
    {
        if (!$this->date_reponse || !$this->date_question) {
            return null;
        }
        return $this->date_question->diffInDays($this->date_reponse);
    }

    /**
     * Extrait nettoyé du texte de la réponse (sans HTML)
     */
    public function getExtraitReponseAttribute(): ?string
    {
        if (!$this->texte_reponse) {
            return null;
        }
        $text = strip_tags(html_entity_decode($this->texte_reponse));
        return mb_substr($text, 0, 500) . (mb_strlen($text) > 500 ? '...' : '');
    }

    /**
     * URL vers la page officielle de l'AN
     */
    public function getUrlOfficielleAttribute(): string
    {
        return "https://questions.assemblee-nationale.fr/q{$this->legislature}/17-{$this->numero}QG.htm";
    }
}

