<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;

/**
 * Modèle pour les maires
 * 
 * @property int $id
 * @property string $uid
 * @property string $nom
 * @property string $prenom
 * @property string|null $nom_complet
 * @property string|null $civilite
 * @property \Carbon\Carbon|null $date_naissance
 * @property string $code_commune
 * @property string $nom_commune
 * @property string $code_departement
 * @property string $nom_departement
 * @property string|null $code_region
 * @property string|null $nom_region
 * @property string|null $profession
 * @property string|null $categorie_socio_pro
 * @property \Carbon\Carbon|null $debut_mandat
 * @property \Carbon\Carbon|null $debut_fonction
 * @property \Carbon\Carbon|null $fin_mandat
 * @property bool $en_exercice
 * @property string|null $photo_url
 * @property string|null $email
 * @property string|null $telephone
 * @property string|null $site_web
 * @property string|null $adresse_mairie
 * @property int|null $population_commune
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Maire extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'uid',
        'nom',
        'prenom',
        'nom_complet',
        'civilite',
        'date_naissance',
        'lieu_naissance',
        'code_commune',
        'nom_commune',
        'code_departement',
        'nom_departement',
        'code_region',
        'nom_region',
        'profession',
        'formation',
        'categorie_socio_pro',
        'debut_mandat',
        'debut_fonction',
        'fin_mandat',
        'en_exercice',
        'photo_url',
        'photo_wikipedia_url',
        'wikipedia_url',
        'wikidata_id',
        'wikipedia_extract',
        'wikipedia_last_sync',
        'mandats_precedents',
        'email',
        'telephone',
        'site_web',
        'adresse_mairie',
        'population_commune',
        'nuance_politique',
        'mandature',
        'latitude',
        'longitude',
        'predecesseur_id',
        'score_election_pct',
        'tour_election',
        'reelu',
        'liste_id',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'debut_mandat' => 'date',
        'debut_fonction' => 'date',
        'fin_mandat' => 'date',
        'en_exercice' => 'boolean',
        'population_commune' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'mandats_precedents' => 'array',
        'wikipedia_last_sync' => 'datetime',
        'score_election_pct' => 'decimal:2',
        'tour_election' => 'integer',
        'reelu' => 'boolean',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    /**
     * Département du maire
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(TerritoryDepartment::class, 'code_departement', 'code');
    }

    /**
     * Codes postaux de la commune
     */
    public function postalCodes()
    {
        return FrenchPostalCode::where('insee_code', $this->code_commune)->get();
    }

    /**
     * Maire prédécesseur sur la même commune
     */
    public function predecesseur(): BelongsTo
    {
        return $this->belongsTo(Maire::class, 'predecesseur_id');
    }

    /**
     * Maire successeur (inverse du prédécesseur)
     */
    public function successeur(): HasOne
    {
        return $this->hasOne(Maire::class, 'predecesseur_id');
    }

    /**
     * Liste électorale gagnante
     */
    public function liste(): BelongsTo
    {
        return $this->belongsTo(ListeElectorale::class, 'liste_id');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeEnExercice($query)
    {
        return $query->where('en_exercice', true);
    }

    public function scopeByCommune($query, string $codeCommune)
    {
        return $query->where('code_commune', $codeCommune);
    }

    public function scopeByDepartement($query, string $codeDepartement)
    {
        return $query->where('code_departement', $codeDepartement);
    }

    public function scopeByPostalCode($query, string $postalCode)
    {
        // Trouver le code commune via les codes postaux
        $communes = FrenchPostalCode::where('postal_code', $postalCode)
            ->pluck('insee_code')
            ->unique();
        
        return $query->whereIn('code_commune', $communes);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nom', 'like', "%{$search}%")
              ->orWhere('prenom', 'like', "%{$search}%")
              ->orWhere('nom_commune', 'like', "%{$search}%");
        });
    }

    public function scopeMandature($query, string $mandature)
    {
        return $query->where('mandature', $mandature);
    }

    public function scopeReelus($query)
    {
        return $query->where('reelu', true);
    }

    public function scopeNouveaux($query)
    {
        return $query->where('reelu', false);
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getNomCompletAttribute(): string
    {
        if ($this->attributes['nom_complet'] ?? null) {
            return $this->attributes['nom_complet'];
        }
        
        $civilite = $this->civilite ? $this->civilite . ' ' : '';
        return $civilite . $this->prenom . ' ' . $this->nom;
    }

    public function getAgeAttribute(): ?int
    {
        if (!$this->date_naissance) {
            return null;
        }

        return $this->date_naissance->age;
    }

    public function getDureeMandatAttribute(): ?int
    {
        if (!$this->debut_mandat) {
            return null;
        }

        $fin = $this->fin_mandat ?? now();
        return $this->debut_mandat->diffInYears($fin);
    }

    public function getNuanceLibelleAttribute(): ?string
    {
        if (!$this->nuance_politique) {
            return null;
        }

        return match($this->nuance_politique) {
            'LDVG' => 'Divers gauche',
            'LDVD' => 'Divers droite',
            'LDVC' => 'Divers centre',
            'LSOC' => 'Socialiste',
            'LLR' => 'Les Républicains',
            'LREM' => 'Renaissance',
            'LREC' => 'Reconquête',
            'LRN' => 'Rassemblement National',
            'LECO' => 'Écologiste',
            'LCOM' => 'Communiste',
            'LUDI' => 'UDI',
            'LDIV' => 'Divers',
            'LEXG' => 'Extrême gauche',
            'LEXT' => 'Extrême droite',
            'LMDM' => 'Modem',
            'LFI' => 'La France Insoumise',
            default => $this->nuance_politique,
        };
    }

    /**
     * Photo du maire (priorité : photo_url > photo_wikipedia_url)
     */
    public function getPhotoAttribute(): ?string
    {
        return $this->photo_url ?? $this->photo_wikipedia_url ?? null;
    }

    public function getNuanceCouleurAttribute(): string
    {
        return match($this->nuance_politique) {
            'LDVG', 'LSOC', 'LCOM', 'LFI', 'LEXG' => '#E11D48', // Rouge/Rose
            'LDVD', 'LLR', 'LUDI' => '#3B82F6', // Bleu
            'LDVC', 'LMDM', 'LREM' => '#F59E0B', // Orange/Jaune
            'LECO' => '#22C55E', // Vert
            'LRN', 'LREC', 'LEXT' => '#1E3A5F', // Bleu marine
            default => '#6B7280', // Gris
        };
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'nom_complet' => $this->nom_complet,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'civilite' => $this->civilite,
            'commune' => [
                'code' => $this->code_commune,
                'nom' => $this->nom_commune,
                'population' => $this->population_commune,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'departement' => [
                'code' => $this->code_departement,
                'nom' => $this->nom_departement,
            ],
            'region' => [
                'code' => $this->code_region,
                'nom' => $this->nom_region,
            ],
            'profession' => $this->profession,
            'age' => $this->age,
            'en_exercice' => $this->en_exercice,
            'duree_mandat_annees' => $this->duree_mandat,
            'debut_mandat' => $this->debut_mandat?->format('Y-m-d'),
            'debut_fonction' => $this->debut_fonction?->format('Y-m-d'),
            'mandature' => $this->mandature,
            'nuance' => $this->nuance_politique ? [
                'code' => $this->nuance_politique,
                'libelle' => $this->nuance_libelle,
                'couleur' => $this->nuance_couleur,
            ] : null,
            'contact' => [
                'email' => $this->email,
                'telephone' => $this->telephone,
                'site_web' => $this->site_web,
                'adresse_mairie' => $this->adresse_mairie,
            ],
        ];
    }

    /**
     * Meilisearch: Données indexées pour la recherche
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'nom_complet' => $this->nom_complet,
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'commune_nom' => $this->nom_commune,
            'departement_nom' => $this->nom_departement,
            'departement_code' => $this->code_departement,
            'region_code' => $this->code_region,
            'mandat_actif' => $this->en_exercice,
            'photo_url' => $this->photo_url,
        ];
    }

    /**
     * Meilisearch: Nom de l'index
     */
    public function searchableAs(): string
    {
        return 'maires';
    }
}

