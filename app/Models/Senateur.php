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
        // La vue senateurs_votes a senateur_matricule qui correspond au matricule du sénateur
        return $this->hasMany(VoteSenat::class, 'senateur_matricule', 'matricule');
    }

    public function amendementsSenat(): HasMany
    {
        // La vue amendements_senat a senateur_matricule (via jointure sen_ameli)
        return $this->hasMany(AmendementSenat::class, 'senateur_matricule', 'matricule');
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

    /**
     * Photo officielle du Sénat (priorité sur Wikipedia)
     * Format: https://www.senat.fr/senimg/{nom}_{prenom}{matricule}_carre.jpg
     */
    public function getPhotoOfficielleAttribute(): ?string
    {
        if (!$this->matricule || !$this->nom_usuel || !$this->prenom_usuel) {
            return null;
        }
        
        // Normaliser le nom et prénom pour l'URL
        $nom = $this->normalizeForUrl($this->nom_usuel);
        $prenom = $this->normalizeForUrl($this->prenom_usuel);
        $matricule = strtolower(trim($this->matricule));
        
        return "https://www.senat.fr/senimg/{$nom}_{$prenom}{$matricule}_carre.jpg";
    }

    /**
     * Photo URL avec fallback : officielle > Wikipedia > null
     */
    public function getPhotoUrlAttribute(): ?string
    {
        // Priorité à la photo officielle du Sénat
        $photoOfficielle = $this->photo_officielle;
        if ($photoOfficielle) {
            return $photoOfficielle;
        }
        
        // Fallback sur Wikipedia
        return $this->photo_wikipedia_url ?? $this->wikipedia_photo ?? null;
    }

    /**
     * Normalise une chaîne pour une URL (minuscules, sans accents)
     */
    private function normalizeForUrl(string $text): string
    {
        $text = strtolower(trim($text));
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = preg_replace('/[^a-z0-9]/', '', $text);
        return $text;
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

