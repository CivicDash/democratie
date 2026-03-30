<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Texte législatif au format Akoma Ntoso (Sénat)
 *
 * Source: https://www.senat.fr/akomantoso/
 */
class TexteAkomaNtoso extends Model
{
    protected $table = 'textes_akoma_ntoso';

    protected $primaryKey = 'uid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'type',
        'annee',
        'numero',
        'session',
        'titre',
        'titre_court',
        'url_senat',
        'url_dossier',
        'signet_dossier',
        'auteur_id',
        'auteur_nom',
        'commission',
        'date_depot',
        'date_presentation',
        'date_adoption',
        'date_publication_xml',
        'etape_actuelle',
        'statut',
        'preambule',
        'corps_texte',
        'nb_articles',
        'nb_titres',
        'source_url',
        'last_modified',
    ];

    protected $casts = [
        'numero' => 'integer',
        'nb_articles' => 'integer',
        'nb_titres' => 'integer',
        'date_depot' => 'date',
        'date_presentation' => 'date',
        'date_adoption' => 'date',
        'date_publication_xml' => 'date',
        'last_modified' => 'datetime',
    ];

    /**
     * Types de textes
     */
    public const TYPES = [
        'ppl' => 'Proposition de loi',
        'pjl' => 'Projet de loi',
        'ppr' => 'Proposition de résolution',
        'pjr' => 'Projet de résolution',
        'plf' => 'Projet de loi de finances',
        'plfss' => 'Projet de loi de financement de la sécurité sociale',
    ];

    /**
     * Scope par type de texte
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope propositions de loi
     */
    public function scopePropositionsLoi($query)
    {
        return $query->where('type', 'ppl');
    }

    /**
     * Scope projets de loi
     */
    public function scopeProjetsLoi($query)
    {
        return $query->where('type', 'pjl');
    }

    /**
     * Scope textes adoptés
     */
    public function scopeAdoptes($query)
    {
        return $query->whereNotNull('date_adoption');
    }

    /**
     * Scope textes récents (30 derniers jours)
     */
    public function scopeRecents($query, int $jours = 30)
    {
        return $query->where('date_depot', '>=', now()->subDays($jours));
    }

    /**
     * Libellé du type
     */
    public function getTypeLibelleAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * URL officielle du texte
     */
    public function getUrlOfficielleAttribute(): string
    {
        return $this->url_senat ?? "https://www.senat.fr/leg/{$this->uid}.html";
    }

    /**
     * URL du fichier XML Akoma Ntoso
     */
    public function getUrlXmlAttribute(): string
    {
        return $this->source_url ?? "https://www.senat.fr/akomantoso/{$this->uid}.akn.xml";
    }

    /**
     * Extrait du préambule (premiers 500 caractères)
     */
    public function getExtraitPreambuleAttribute(): ?string
    {
        if (! $this->preambule) {
            return null;
        }
        $text = strip_tags($this->preambule);

        return mb_substr($text, 0, 500).(mb_strlen($text) > 500 ? '...' : '');
    }
}
