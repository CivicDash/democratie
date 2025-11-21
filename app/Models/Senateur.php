<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Senateur extends Model
{
    use HasFactory;

    protected $table = 'senateurs';
    // La vue SQL map senmat à la fois vers 'id' (PK Laravel) et 'matricule' (identifiant Sénat)
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'matricule',
        'civilite',
        'nom_usuel',
        'prenom_usuel',
        'etat',
        'date_naissance',
        'date_deces',
        'groupe_politique',
        'type_appartenance_groupe',
        'commission_permanente',
        'circonscription',
        'fonction_bureau_senat',
        'email',
        'pcs_insee',
        'categorie_socio_pro',
        'description_profession',
        'wikipedia_url',
        'wikipedia_photo',
        'wikipedia_extract',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_deces' => 'date',
    ];

    /**
     * Relations
     */
    public function historiqueGroupes(): HasMany
    {
        return $this->hasMany(SenateurHistoriqueGroupe::class, 'senateur_matricule', 'matricule');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(SenateurCommission::class, 'senateur_matricule', 'matricule');
    }

    public function mandats(): HasMany
    {
        return $this->hasMany(SenateurMandat::class, 'senateur_matricule', 'matricule');
    }

    public function etudes(): HasMany
    {
        return $this->hasMany(SenateurEtude::class, 'senateur_matricule', 'matricule');
    }

    public function mandatsLocaux(): HasMany
    {
        return $this->hasMany(SenateurMandatLocal::class, 'senateur_matricule', 'matricule');
    }

    public function votesSenat(): HasMany
    {
        return $this->hasMany(VoteSenat::class, 'senateur_matricule', 'matricule');
    }

    public function amendementsSenat(): HasMany
    {
        return $this->hasMany(AmendementSenat::class, 'auteur_senateur_matricule', 'matricule');
    }

    /**
     * Scopes
     */
    public function scopeActifs($query)
    {
        return $query->where('etat', 'ACTIF');
    }

    public function scopeAnciens($query)
    {
        return $query->where('etat', 'ANCIEN');
    }

    public function scopeParCirconscription($query, string $circonscription)
    {
        return $query->where('circonscription', $circonscription);
    }

    public function scopeParGroupe($query, string $groupe)
    {
        return $query->where('groupe_politique', $groupe);
    }

    /**
     * Accessors
     */
    public function getNomCompletAttribute(): string
    {
        return trim("{$this->civilite} {$this->prenom_usuel} {$this->nom_usuel}");
    }

    public function getEstActifAttribute(): bool
    {
        return $this->etat === 'ACTIF';
    }

    public function getCommissionsActuellesAttribute()
    {
        return $this->commissions()
            ->whereNull('date_fin')
            ->get();
    }

    public function getMandatsActifsAttribute()
    {
        return $this->mandats()
            ->whereNull('date_fin')
            ->get();
    }

    public function getMandatsLocauxActifsAttribute()
    {
        return $this->mandatsLocaux()
            ->where('en_cours', true)
            ->get();
    }

    public function getMandatsLocauxParTypeAttribute()
    {
        return $this->mandatsLocaux()
            ->get()
            ->groupBy('type_mandat');
    }

    public function getAgeAttribute(): ?int
    {
        if (!$this->date_naissance) {
            return null;
        }
        return $this->date_naissance->age;
    }
}

