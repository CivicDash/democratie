<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle pour les amendements du Sénat (data.senat.fr)
 * 
 * Basé sur la vue SQL amendements_senat qui joint :
 * - senat_ameli_amd (amendements)
 * - senat_ameli_amdsen (auteurs)
 * - sen_ameli (liaison vers matricule sénateur)
 * 
 * @property int $id
 * @property string|null $senateur_matricule - Matricule du sénateur auteur
 * @property string $numero
 * @property string|null $type_amendement
 * @property string|null $dispositif
 * @property string|null $expose
 * @property string|null $sort_code
 * @property string|null $sort_libelle
 * @property \Carbon\Carbon|null $date_depot
 */
class AmendementSenat extends Model
{
    use HasFactory;

    protected $table = 'amendements_senat';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'id',
        'texte_ref',
        'senateur_matricule', // Colonne de la vue SQL
        'legislature',
        'numero',
        'numero_long',
        'type_amendement',
        'subdiv_type',
        'subdiv_titre',
        'subdiv_mult',
        'auteur_type',
        'auteur_nom',
        'auteur_prenom',
        'auteur_groupe_id',
        'auteur_groupe_sigle',
        'cosignataires',
        'nombre_cosignataires',
        'dispositif',
        'expose',
        'sort_code',
        'sort_libelle',
        'date_depot',
        'date_sort',
        'url_senat',
    ];

    protected $casts = [
        'legislature' => 'integer',
        'nombre_cosignataires' => 'integer',
        'cosignataires' => 'array',
        'date_depot' => 'date',
        'date_sort' => 'date',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    /**
     * Sénateur auteur
     * Note: La vue amendements_senat utilise 'senateur_matricule' (via jointure sen_ameli)
     */
    public function auteur(): BelongsTo
    {
        return $this->belongsTo(Senateur::class, 'senateur_matricule', 'matricule');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    /**
     * Amendements adoptés
     * Codes réels : A (Adopté), AM (Adopté avec modification), AB (Adopté - vote unique)
     */
    public function scopeAdoptes($query)
    {
        return $query->whereIn('sort_code', ['A', 'AM', 'AB']);
    }

    /**
     * Amendements rejetés
     * Codes réels : RJS (Rejeté), RJ (Rejeté), RJB (Rejeté - vote unique)
     */
    public function scopeRejetes($query)
    {
        return $query->whereIn('sort_code', ['RJS', 'RJ', 'RJB']);
    }

    /**
     * Amendements retirés
     * Codes réels : R (Retiré), RET (Retiré)
     */
    public function scopeRetires($query)
    {
        return $query->whereIn('sort_code', ['R', 'RET']);
    }

    /**
     * Amendements tombés
     */
    public function scopeTombes($query)
    {
        return $query->where('sort_code', 'S');
    }

    /**
     * Amendements non soutenus
     */
    public function scopeNonSoutenus($query)
    {
        return $query->where('sort_code', 'N');
    }

    /**
     * Amendements satisfaits ou sans objet
     */
    public function scopeSatisfaits($query)
    {
        return $query->where('sort_code', 'SO');
    }

    /**
     * Amendements d'une législature spécifique
     */
    public function scopeLegislature($query, int $legislature)
    {
        return $query->where('legislature', $legislature);
    }

    // ========================================================================
    // ACCESSORS
    // ========================================================================

    /**
     * Indique si l'amendement est adopté
     */
    public function getEstAdopteAttribute(): bool
    {
        return in_array($this->sort_code, ['A', 'AM', 'AB']);
    }

    /**
     * Indique si l'amendement est rejeté
     */
    public function getEstRejeteAttribute(): bool
    {
        return in_array($this->sort_code, ['RJS', 'RJ', 'RJB']);
    }

    /**
     * Indique si l'amendement est retiré
     */
    public function getEstRetireAttribute(): bool
    {
        return in_array($this->sort_code, ['R', 'RET']);
    }

    /**
     * Indique si l'amendement est tombé
     */
    public function getEstTombeAttribute(): bool
    {
        return $this->sort_code === 'S';
    }

    /**
     * Libellé du sort formaté pour l'affichage
     */
    public function getSortLibelleFormateAttribute(): string
    {
        return match($this->sort_code) {
            'A' => 'Adopté',
            'AM' => 'Adopté (modifié)',
            'AB' => 'Adopté',
            'RJS', 'RJ', 'RJB' => 'Rejeté',
            'R', 'RET' => 'Retiré',
            'S' => 'Tombé',
            'N' => 'Non soutenu',
            'SO' => 'Satisfait',
            default => $this->sort_libelle ?? 'En cours',
        };
    }

    /**
     * Dispositif décodé (HTML entities)
     */
    public function getDispositifDecodeAttribute(): ?string
    {
        if (!$this->dispositif) {
            return null;
        }
        // Décoder les entités HTML et nettoyer le HTML
        $decoded = html_entity_decode($this->dispositif, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Retirer les balises HTML pour un affichage propre
        return strip_tags($decoded);
    }

    /**
     * Exposé décodé (HTML entities)
     */
    public function getExposeDecodeAttribute(): ?string
    {
        if (!$this->expose) {
            return null;
        }
        $decoded = html_entity_decode($this->expose, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return strip_tags($decoded);
    }

    /**
     * URL vers le Sénat pour cet amendement
     * Format: https://www.senat.fr/amendements/{session}/{texte_ref}/{numero}.html
     */
    public function getUrlSenatAttribute(): ?string
    {
        // Si l'URL est déjà stockée, la retourner
        if (!empty($this->attributes['url_senat'])) {
            return $this->attributes['url_senat'];
        }
        
        // Sinon, construire une URL de recherche générique
        $numero = urlencode($this->numero ?? '');
        return "https://www.senat.fr/recherche/amendements?tri=amendement&numero={$numero}";
    }
}

