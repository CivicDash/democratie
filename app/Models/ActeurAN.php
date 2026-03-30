<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class ActeurAN extends Model
{
    use HasFactory, Searchable;

    protected $table = 'acteurs_an';

    protected $primaryKey = 'uid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'civilite',
        'prenom',
        'nom',
        'trigramme',
        'date_naissance',
        'ville_naissance',
        'departement_naissance',
        'pays_naissance',
        'profession',
        'categorie_socio_pro',
        'url_hatvp',
        'wikipedia_url',
        'photo_wikipedia_url',
        'wikipedia_extract',
        'wikipedia_last_sync',
        'twitter_url',
        'facebook_url',
        'linkedin_url',
        'instagram_url',
        'adresses',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'wikipedia_last_sync' => 'datetime',
        'adresses' => 'array',
    ];

    protected $appends = ['photo_url'];

    /**
     * Relations
     */
    public function mandats(): HasMany
    {
        return $this->hasMany(MandatAN::class, 'acteur_ref', 'uid');
    }

    public function votesIndividuels(): HasMany
    {
        return $this->hasMany(VoteIndividuelAN::class, 'acteur_ref', 'uid');
    }

    public function amendementsAuteur(): HasMany
    {
        return $this->hasMany(AmendementAN::class, 'auteur_acteur_ref', 'uid');
    }

    public function deports(): HasMany
    {
        return $this->hasMany(DeportAN::class, 'acteur_ref', 'uid');
    }

    public function circonscriptions(): HasMany
    {
        return $this->hasMany(DeputeCirconscription::class, 'acteur_uid', 'uid');
    }

    public function affairesJudiciaires(): HasMany
    {
        return $this->hasMany(AffaireJudiciaire::class, 'acteur_an_uid', 'uid');
    }

    public function affairesPubliques(): HasMany
    {
        return $this->affairesJudiciaires()->publiques();
    }

    /**
     * Scopes
     */
    public function scopeDeputes($query)
    {
        // Députés actifs (avec mandat ASSEMBLEE en cours)
        return $query->whereHas('mandats', function ($q) {
            $q->where('type_organe', 'ASSEMBLEE')
                ->whereNull('date_fin');
        });
    }

    /**
     * Accessors
     */
    public function getNomCompletAttribute(): string
    {
        return trim("{$this->civilite} {$this->prenom} {$this->nom}");
    }

    /**
     * Photo officielle de l'Assemblée Nationale
     * Format: https://www.assemblee-nationale.fr/dyn/static/tribun/{legislature}/photos/{uid_numerique}.jpg
     */
    public function getPhotoOfficielleAttribute(): ?string
    {
        if (! $this->uid) {
            return null;
        }

        // Extraire l'ID numérique du UID (format: PAxxxxxx)
        $uidNumerique = preg_replace('/[^0-9]/', '', $this->uid);

        if (empty($uidNumerique)) {
            return null;
        }

        // Legislature 17 par défaut (à adapter si besoin)
        $legislature = 17;

        return "https://www.assemblee-nationale.fr/dyn/static/tribun/{$legislature}/photos/{$uidNumerique}.jpg";
    }

    /**
     * Photo URL avec fallback : officielle > Wikipedia > null
     */
    public function getPhotoUrlAttribute(): ?string
    {
        // Priorité à la photo officielle de l'AN
        $photoOfficielle = $this->photo_officielle;
        if ($photoOfficielle) {
            return $photoOfficielle;
        }

        // Fallback sur Wikipedia
        return $this->photo_wikipedia_url;
    }

    /**
     * Récupère le mandat d'ASSEMBLEE actif
     */
    public function getMandatActifAttribute()
    {
        return $this->mandats()
            ->where('type_organe', 'ASSEMBLEE')
            ->whereNull('date_fin')
            ->with('organe')
            ->first();
    }

    /**
     * Récupère le groupe politique actuel (via mandat GP actif)
     */
    public function getGroupePolitiqueActuelAttribute()
    {
        $mandatGP = $this->mandats()
            ->where('type_organe', 'GP')
            ->whereNull('date_fin')
            ->with('organe')
            ->first();

        return $mandatGP ? $mandatGP->organe : null;
    }

    /**
     * Récupère les commissions actuelles
     */
    public function getCommissionsActuellesAttribute()
    {
        return $this->mandats()
            ->whereIn('type_organe', ['COMPER', 'DELEG'])
            ->whereNull('date_fin')
            ->with('organe')
            ->get()
            ->pluck('organe');
    }

    /**
     * Récupère la circonscription actuelle (législature 17)
     */
    public function getCirconscriptionActuelleAttribute(): ?DeputeCirconscription
    {
        return $this->circonscriptions()
            ->legislature(17)
            ->actif()
            ->first();
    }

    /**
     * Récupère les infos de circonscription formatées
     */
    public function getCirconscriptionInfoAttribute(): ?array
    {
        $circo = $this->circonscription_actuelle;

        if (! $circo) {
            return null;
        }

        return [
            'departement' => $circo->departement,
            'num_departement' => $circo->num_departement,
            'num_circo' => $circo->num_circo,
            'region' => $circo->region,
            'libelle' => $circo->libelle_circonscription,
            'libelle_court' => $circo->libelle_court,
            'place_hemicycle' => $circo->place_hemicycle,
            'premiere_election' => $circo->premiere_election,
            'cause_mandat' => $circo->cause_mandat,
        ];
    }

    /**
     * Meilisearch: Données indexées pour la recherche
     */
    public function toSearchableArray(): array
    {
        $groupe = $this->groupe_politique_actuel;
        $circo = $this->circonscription_actuelle;

        return [
            'uid' => $this->uid,
            'nom_complet' => $this->nom_complet,
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'profession' => $this->profession,
            'groupe_politique' => $groupe?->libelle ?? null,
            'groupe_politique_sigle' => $groupe?->libelle_abrege ?? null,
            'circonscription' => $circo?->libelle_circonscription ?? null,
            'departement' => $circo?->departement ?? null,
            'region' => $circo?->region ?? null,
            'legislature' => 17,
            'est_depute_actif' => $this->mandat_actif !== null,
            'photo_url' => $this->photo_url,
        ];
    }

    /**
     * Meilisearch: Nom de l'index
     */
    public function searchableAs(): string
    {
        return 'acteurs_an';
    }
}
