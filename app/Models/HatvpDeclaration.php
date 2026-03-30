<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Déclaration HATVP (intérêts ou patrimoine)
 *
 * @property int $id
 * @property string $uuid
 * @property \Carbon\Carbon $date_depot
 * @property string $type_declaration DIA, DSP, DIAC, DSPC, DIAI, DSPI
 * @property string|null $origine
 * @property bool $complete
 * @property string|null $version
 * @property string|null $parlementaire_type senateur, depute
 * @property string|null $parlementaire_id matricule ou uid
 * @property string|null $civilite
 * @property string $nom
 * @property string $prenom
 * @property \Carbon\Carbon|null $date_naissance
 * @property string|null $type_mandat
 * @property string|null $code_categorie_mandat
 * @property string|null $code_departement
 * @property string|null $label_organe
 * @property \Carbon\Carbon|null $date_debut_mandat
 * @property \Carbon\Carbon|null $date_fin_mandat
 * @property string|null $observations_interet
 * @property string|null $observations_patrimoine
 */
class HatvpDeclaration extends Model
{
    use HasFactory;

    protected $table = 'hatvp_declarations';

    protected $fillable = [
        'uuid',
        'date_depot',
        'type_declaration',
        'origine',
        'complete',
        'version',
        'parlementaire_type',
        'parlementaire_id',
        'civilite',
        'nom',
        'prenom',
        'date_naissance',
        'type_mandat',
        'code_categorie_mandat',
        'code_departement',
        'label_organe',
        'date_debut_mandat',
        'date_fin_mandat',
        'observations_interet',
        'observations_patrimoine',
        'details_imported_at',
    ];

    protected $casts = [
        'date_depot' => 'datetime',
        'date_naissance' => 'date',
        'date_debut_mandat' => 'date',
        'date_fin_mandat' => 'date',
        'complete' => 'boolean',
        'details_imported_at' => 'datetime',
    ];

    // ==================== RELATIONS ====================

    public function mandatsElectifs(): HasMany
    {
        return $this->hasMany(HatvpMandatElectif::class, 'declaration_id');
    }

    public function fonctionsBenevoles(): HasMany
    {
        return $this->hasMany(HatvpFonctionBenevole::class, 'declaration_id');
    }

    public function participationsDirigeantes(): HasMany
    {
        return $this->hasMany(HatvpParticipationDirigeante::class, 'declaration_id');
    }

    public function participationsFinancieres(): HasMany
    {
        return $this->hasMany(HatvpParticipationFinanciere::class, 'declaration_id');
    }

    public function collaborateurs(): HasMany
    {
        return $this->hasMany(HatvpCollaborateur::class, 'declaration_id');
    }

    public function activitesConsultant(): HasMany
    {
        return $this->hasMany(HatvpActiviteConsultant::class, 'declaration_id');
    }

    public function activitesProfessionnelles(): HasMany
    {
        return $this->hasMany(HatvpActiviteProfessionnelle::class, 'declaration_id');
    }

    public function immeubles(): HasMany
    {
        return $this->hasMany(HatvpImmeuble::class, 'declaration_id');
    }

    public function vehicules(): HasMany
    {
        return $this->hasMany(HatvpVehicule::class, 'declaration_id');
    }

    public function comptesBancaires(): HasMany
    {
        return $this->hasMany(HatvpCompteBancaire::class, 'declaration_id');
    }

    public function assurancesVie(): HasMany
    {
        return $this->hasMany(HatvpAssuranceVie::class, 'declaration_id');
    }

    public function valeursNonCotees(): HasMany
    {
        return $this->hasMany(HatvpValeurNonCotee::class, 'declaration_id');
    }

    public function valeursCotees(): HasMany
    {
        return $this->hasMany(HatvpValeurCotee::class, 'declaration_id');
    }

    public function passif(): HasMany
    {
        return $this->hasMany(HatvpPassif::class, 'declaration_id');
    }

    public function revenus(): HasMany
    {
        return $this->hasMany(HatvpRevenu::class, 'declaration_id');
    }

    // Relation vers le sénateur (si lié)
    public function senateur(): BelongsTo
    {
        return $this->belongsTo(Senateur::class, 'parlementaire_id', 'matricule');
    }

    // Relation vers le député (si lié)
    public function depute(): BelongsTo
    {
        return $this->belongsTo(ActeurAN::class, 'parlementaire_id', 'uid');
    }

    // ==================== SCOPES ====================

    public function scopeInterets($query)
    {
        return $query->whereIn('type_declaration', ['DIA', 'DIAC', 'DIAI']);
    }

    public function scopePatrimoine($query)
    {
        return $query->whereIn('type_declaration', ['DSP', 'DSPC', 'DSPI']);
    }

    public function scopeSenateurs($query)
    {
        return $query->where('parlementaire_type', 'senateur');
    }

    public function scopeDeputes($query)
    {
        return $query->where('parlementaire_type', 'depute');
    }

    public function scopeMaires($query)
    {
        return $query->where('parlementaire_type', 'maire');
    }

    public function scopeRecentes($query, int $jours = 30)
    {
        return $query->where('date_depot', '>=', now()->subDays($jours));
    }

    // ==================== ACCESSORS ====================

    public function getEstInteretAttribute(): bool
    {
        return in_array($this->type_declaration, ['DIA', 'DIAC', 'DIAI']);
    }

    public function getEstPatrimoineAttribute(): bool
    {
        return in_array($this->type_declaration, ['DSP', 'DSPC', 'DSPI']);
    }

    public function getNomCompletAttribute(): string
    {
        return trim("{$this->civilite} {$this->prenom} {$this->nom}");
    }

    public function getTypeDeclarationLabelAttribute(): string
    {
        return config("hatvp.types_declarations.{$this->type_declaration}", $this->type_declaration);
    }

    public function getParlementaireAttribute(): ?Model
    {
        if ($this->parlementaire_type === 'senateur') {
            return $this->senateur;
        }
        if ($this->parlementaire_type === 'depute') {
            return $this->depute;
        }

        return null;
    }

    // ==================== MÉTHODES ====================

    /**
     * Calcule le total des revenus déclarés pour une année
     */
    public function getTotalRevenus(?int $annee = null): float
    {
        $query = $this->revenus();

        if ($annee) {
            $query->where('annee', $annee);
        }

        return $query->sum('montant_elu') ?? 0;
    }

    /**
     * Calcule la valeur totale du patrimoine immobilier
     */
    public function getTotalPatrimoineImmobilier(): float
    {
        return $this->immeubles()->sum('valeur_venale') ?? 0;
    }

    /**
     * Compte le nombre de mandats cumulés
     */
    public function getNombreMandatsCumules(): int
    {
        return $this->mandatsElectifs()->where('conservee', true)->count();
    }

    /**
     * Calcule le total des rémunérations des mandats électifs pour une année
     */
    public function getTotalRemunerationsMandats(?int $annee = null): float
    {
        $total = 0;

        foreach ($this->mandatsElectifs as $mandat) {
            $query = $mandat->remunerations();
            if ($annee) {
                $query->where('annee', $annee);
            }
            $total += $query->sum('montant') ?? 0;
        }

        return $total;
    }

    /**
     * Calcule le total des rémunérations des activités professionnelles pour une année
     */
    public function getTotalRemunerationsActivitesPro(?int $annee = null): float
    {
        $total = 0;

        foreach ($this->activitesProfessionnelles as $activite) {
            $query = $activite->remunerations();
            if ($annee) {
                $query->where('annee', $annee);
            }
            $total += $query->sum('montant') ?? 0;
        }

        return $total;
    }

    /**
     * Calcule le total des rémunérations des activités de consultant pour une année
     */
    public function getTotalRemunerationsConsultant(?int $annee = null): float
    {
        $total = 0;

        foreach ($this->activitesConsultant as $activite) {
            $query = $activite->remunerations();
            if ($annee) {
                $query->where('annee', $annee);
            }
            $total += $query->sum('montant') ?? 0;
        }

        return $total;
    }

    /**
     * Calcule le total des rémunérations des participations dirigeantes pour une année
     */
    public function getTotalRemunerationsDirigeant(?int $annee = null): float
    {
        $total = 0;

        foreach ($this->participationsDirigeantes as $participation) {
            $query = $participation->remunerations();
            if ($annee) {
                $query->where('annee', $annee);
            }
            $total += $query->sum('montant') ?? 0;
        }

        return $total;
    }

    /**
     * Calcule le total consolidé de tous les revenus pour une année
     */
    public function getTotalRevenusConsolides(?int $annee = null): float
    {
        return $this->getTotalRemunerationsMandats($annee)
            + $this->getTotalRemunerationsActivitesPro($annee)
            + $this->getTotalRemunerationsConsultant($annee)
            + $this->getTotalRemunerationsDirigeant($annee);
    }

    /**
     * Retourne les revenus consolidés par année
     */
    public function getRevenusParAnneeAttribute(): array
    {
        $revenusParAnnee = [];

        // Collecter toutes les années disponibles
        $annees = collect();

        foreach ($this->mandatsElectifs as $mandat) {
            $annees = $annees->merge($mandat->remunerations->pluck('annee'));
        }
        foreach ($this->activitesProfessionnelles as $activite) {
            $annees = $annees->merge($activite->remunerations->pluck('annee'));
        }
        foreach ($this->activitesConsultant as $activite) {
            $annees = $annees->merge($activite->remunerations->pluck('annee'));
        }
        foreach ($this->participationsDirigeantes as $participation) {
            $annees = $annees->merge($participation->remunerations->pluck('annee'));
        }

        // Calculer le total pour chaque année
        foreach ($annees->unique()->sortDesc() as $annee) {
            $revenusParAnnee[$annee] = [
                'mandats' => $this->getTotalRemunerationsMandats($annee),
                'activites_pro' => $this->getTotalRemunerationsActivitesPro($annee),
                'consultant' => $this->getTotalRemunerationsConsultant($annee),
                'dirigeant' => $this->getTotalRemunerationsDirigeant($annee),
                'total' => $this->getTotalRevenusConsolides($annee),
            ];
        }

        return $revenusParAnnee;
    }

    /**
     * Compte le nombre total d'emplois/fonctions
     */
    public function getNombreEmploisAttribute(): int
    {
        return $this->mandatsElectifs()->where('conservee', true)->count()
            + $this->activitesProfessionnelles()->where('conservee', true)->count()
            + $this->activitesConsultant()->where('conservee', true)->count()
            + $this->participationsDirigeantes()->where('conservee', true)->count()
            + $this->fonctionsBenevoles()->where('conservee', true)->count();
    }

    /**
     * Compte le nombre de collaborateurs parlementaires
     */
    public function getNombreCollaborateursAttribute(): int
    {
        return $this->collaborateurs()->count();
    }

    /**
     * Accesseur pour le formatage de la date de dépôt
     */
    public function getDateDepotFormatedAttribute(): string
    {
        return $this->date_depot?->format('d/m/Y') ?? '';
    }

    /**
     * URL vers la fiche HATVP
     */
    public function getUrlHatvpAttribute(): string
    {
        $slug = strtolower(str_replace(' ', '-', "{$this->nom}-{$this->prenom}"));

        return "https://www.hatvp.fr/fiche-nominative/?declarant={$slug}";
    }
}
