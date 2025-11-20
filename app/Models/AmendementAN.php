<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class AmendementAN extends Model
{
    use HasFactory;

    protected $table = 'amendements_an';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'texte_legislatif_ref',
        'examen_ref',
        'legislature',
        'numero_long',
        'numero_ordre_depot',
        'prefixe_organe_examen',
        'auteur_type',
        'auteur_acteur_ref',
        'auteur_groupe_ref',
        'auteur_libelle',
        'cosignataires_acteur_refs',
        'nombre_cosignataires',
        'article_designation',
        'article_designation_courte',
        'division_titre',
        'division_type',
        'cartouche_informatif',
        'dispositif',
        'expose',
        'date_depot',
        'date_publication',
        'soumis_article_40',
        'etat_code',
        'etat_libelle',
        'sous_etat_code',
        'sous_etat_libelle',
        'date_sort',
        'sort_code',
        'sort_libelle',
    ];

    protected $casts = [
        'date_depot' => 'date',
        'date_publication' => 'date',
        'date_sort' => 'date',
        'legislature' => 'integer',
        'numero_ordre_depot' => 'integer',
        'nombre_cosignataires' => 'integer',
        'soumis_article_40' => 'boolean',
        'cosignataires_acteur_refs' => 'array',
    ];

    /**
     * Relations
     */
    public function texteLegislatif(): BelongsTo
    {
        return $this->belongsTo(TexteLegislatifAN::class, 'texte_legislatif_ref', 'uid');
    }

    // Alias pour la relation texte
    public function texte(): BelongsTo
    {
        return $this->texteLegislatif();
    }

    // Relation indirecte vers le dossier via le texte
    public function dossier()
    {
        return $this->hasOneThrough(
            DossierLegislatifAN::class,
            TexteLegislatifAN::class,
            'uid', // Foreign key sur textes_legislatifs_an
            'uid', // Foreign key sur dossiers_legislatifs_an
            'texte_legislatif_ref', // Local key sur amendements_an
            'dossier_ref' // Local key sur textes_legislatifs_an
        );
    }

    public function auteurActeur(): BelongsTo
    {
        return $this->belongsTo(ActeurAN::class, 'auteur_acteur_ref', 'uid');
    }

    public function auteurGroupe(): BelongsTo
    {
        return $this->belongsTo(OrganeAN::class, 'auteur_groupe_ref', 'uid');
    }

    /**
     * Scopes
     */
    public function scopeLegislature($query, int $legislature)
    {
        return $query->where('legislature', $legislature);
    }

    public function scopeAdoptes($query)
    {
        return $query->where('sort_code', 'ADO');
    }

    public function scopeRejetes($query)
    {
        return $query->where('sort_code', 'REJ');
    }

    public function scopeRetires($query)
    {
        return $query->where('sort_code', 'RET');
    }

    public function scopeTombes($query)
    {
        return $query->where('sort_code', 'TOM');
    }

    public function scopeParAuteur($query, string $acteurUid)
    {
        return $query->where('auteur_acteur_ref', $acteurUid);
    }

    public function scopeParGroupe($query, string $groupeUid)
    {
        return $query->where('auteur_groupe_ref', $groupeUid);
    }

    public function scopeGouvernement($query)
    {
        return $query->where('auteur_type', 'Gouvernement');
    }

    /**
     * Accessors
     */
    public function getEstAdopteAttribute(): bool
    {
        return $this->sort_code === 'ADO';
    }

    public function getEstRejeteAttribute(): bool
    {
        return $this->sort_code === 'REJ';
    }

    public function getEstRetireAttribute(): bool
    {
        return $this->sort_code === 'RET';
    }

    public function getEstTombeAttribute(): bool
    {
        return $this->sort_code === 'TOM';
    }

    public function getEstIrrecevableAttribute(): bool
    {
        return str_starts_with($this->etat_code ?? '', 'IRR');
    }

    public function getADesCosignatairesAttribute(): bool
    {
        return $this->nombre_cosignataires > 0;
    }
}

