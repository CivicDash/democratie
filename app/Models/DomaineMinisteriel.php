<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DomaineMinisteriel extends Model
{
    protected $table = 'domaines_ministeriels';

    protected $fillable = [
        'nom', 'slug', 'sigle', 'description',
        'wikipedia_url', 'wikipedia_extract',
        'site_web', 'adresse', 'telephone', 'email',
        'couleur', 'icone', 'logo_url',
        'date_creation', 'ordre', 'actif',
    ];

    protected $casts = [
        'ordre' => 'integer',
        'actif' => 'boolean',
        'date_creation' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->nom);
            }
            if (empty($model->couleur)) {
                $model->couleur = self::getCouleurDefaut($model->nom);
            }
        });
    }

    // Relations
    public function ministeres(): HasMany
    {
        return $this->hasMany(Ministere::class, 'domaine_ministeriel_id');
    }

    public function postes(): HasMany
    {
        return $this->hasMany(PosteMinisteriel::class, 'domaine_ministeriel_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    // Accessors
    public function getMinistresHistoriqueAttribute()
    {
        return PersonnePolitique::whereHas('postes', function ($q) {
            $q->where('domaine_ministeriel_id', $this->id);
        })
            ->with(['postes' => function ($q) {
                $q->where('domaine_ministeriel_id', $this->id)
                    ->with('gouvernement')
                    ->orderByDesc('date_debut');
            }])
            ->get();
    }

    public function getMinistreActuelAttribute(): ?PersonnePolitique
    {
        return PersonnePolitique::whereHas('postes', function ($q) {
            $q->where('domaine_ministeriel_id', $this->id)
                ->where('actif', true);
        })->first();
    }

    public function getNombreMinistresAttribute(): int
    {
        return $this->postes()->distinct('personne_politique_id')->count('personne_politique_id');
    }

    // Couleurs par défaut selon le domaine
    public static function getCouleurDefaut(string $nom): string
    {
        $couleurs = [
            'intérieur' => '#dc2626',
            'armées' => '#1e3a5f',
            'défense' => '#1e3a5f',
            'économie' => '#0891b2',
            'finances' => '#0891b2',
            'éducation' => '#2563eb',
            'enseignement' => '#2563eb',
            'justice' => '#78350f',
            'santé' => '#ec4899',
            'écologie' => '#059669',
            'environnement' => '#059669',
            'transition' => '#059669',
            'culture' => '#8b5cf6',
            'travail' => '#f59e0b',
            'emploi' => '#f59e0b',
            'agriculture' => '#22c55e',
            'europe' => '#3b82f6',
            'affaires étrangères' => '#3b82f6',
            'outre-mer' => '#06b6d4',
            'sport' => '#84cc16',
            'jeunesse' => '#84cc16',
            'logement' => '#a855f7',
            'transports' => '#64748b',
            'numérique' => '#0ea5e9',
            'industrie' => '#475569',
            'commerce' => '#f97316',
            'recherche' => '#6366f1',
            'solidarités' => '#f472b6',
        ];

        $nomLower = strtolower($nom);
        foreach ($couleurs as $key => $color) {
            if (str_contains($nomLower, $key)) {
                return $color;
            }
        }

        return '#6b7280';
    }

    // Liste des domaines ministériels standards
    public static function getDomainesStandards(): array
    {
        return [
            [
                'nom' => 'Intérieur',
                'sigle' => 'MI',
                'description' => 'Sécurité intérieure, police, gendarmerie, préfectures, collectivités territoriales',
                'site_web' => 'https://www.interieur.gouv.fr/',
                'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Ministère_de_l\'Intérieur_(France)',
                'ordre' => 1,
            ],
            [
                'nom' => 'Affaires étrangères',
                'sigle' => 'MEAE',
                'description' => 'Diplomatie, relations internationales, Français de l\'étranger',
                'site_web' => 'https://www.diplomatie.gouv.fr/',
                'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Ministère_de_l\'Europe_et_des_Affaires_étrangères',
                'ordre' => 2,
            ],
            [
                'nom' => 'Justice',
                'sigle' => 'MJ',
                'description' => 'Système judiciaire, prisons, accès au droit',
                'site_web' => 'https://www.justice.gouv.fr/',
                'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Ministère_de_la_Justice_(France)',
                'ordre' => 3,
            ],
            [
                'nom' => 'Armées',
                'sigle' => 'MINARM',
                'description' => 'Défense nationale, forces armées, anciens combattants',
                'site_web' => 'https://www.defense.gouv.fr/',
                'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Ministère_des_Armées',
                'ordre' => 4,
            ],
            [
                'nom' => 'Économie et Finances',
                'sigle' => 'MEF',
                'description' => 'Budget, fiscalité, trésor, douanes, industrie',
                'site_web' => 'https://www.economie.gouv.fr/',
                'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Ministère_de_l\'Économie_(France)',
                'ordre' => 5,
            ],
            [
                'nom' => 'Éducation nationale',
                'sigle' => 'MEN',
                'description' => 'Enseignement scolaire, programmes, personnels éducatifs',
                'site_web' => 'https://www.education.gouv.fr/',
                'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Ministère_de_l\'Éducation_nationale_(France)',
                'ordre' => 6,
            ],
            [
                'nom' => 'Enseignement supérieur et Recherche',
                'sigle' => 'MESR',
                'description' => 'Universités, grandes écoles, recherche scientifique',
                'site_web' => 'https://www.enseignementsup-recherche.gouv.fr/',
                'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Ministère_de_l\'Enseignement_supérieur_et_de_la_Recherche',
                'ordre' => 7,
            ],
            [
                'nom' => 'Santé',
                'sigle' => 'MS',
                'description' => 'Santé publique, hôpitaux, sécurité sociale',
                'site_web' => 'https://solidarites-sante.gouv.fr/',
                'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Ministère_de_la_Santé_(France)',
                'ordre' => 8,
            ],
            [
                'nom' => 'Travail et Emploi',
                'sigle' => 'MTEI',
                'description' => 'Droit du travail, emploi, formation professionnelle',
                'site_web' => 'https://travail-emploi.gouv.fr/',
                'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Ministère_du_Travail_(France)',
                'ordre' => 9,
            ],
            [
                'nom' => 'Transition écologique',
                'sigle' => 'MTE',
                'description' => 'Environnement, énergie, transports, logement',
                'site_web' => 'https://www.ecologie.gouv.fr/',
                'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Ministère_de_la_Transition_écologique',
                'ordre' => 10,
            ],
            [
                'nom' => 'Agriculture',
                'sigle' => 'MAA',
                'description' => 'Agriculture, alimentation, pêche, forêts',
                'site_web' => 'https://agriculture.gouv.fr/',
                'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Ministère_de_l\'Agriculture_(France)',
                'ordre' => 11,
            ],
            [
                'nom' => 'Culture',
                'sigle' => 'MC',
                'description' => 'Patrimoine, création artistique, médias',
                'site_web' => 'https://www.culture.gouv.fr/',
                'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Ministère_de_la_Culture_(France)',
                'ordre' => 12,
            ],
            [
                'nom' => 'Sports',
                'sigle' => 'MS',
                'description' => 'Sports, Jeux olympiques, vie associative sportive',
                'site_web' => 'https://www.sports.gouv.fr/',
                'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Ministère_des_Sports_(France)',
                'ordre' => 13,
            ],
            [
                'nom' => 'Outre-mer',
                'sigle' => 'MOM',
                'description' => 'Territoires ultramarins, DOM-TOM',
                'site_web' => 'https://www.outre-mer.gouv.fr/',
                'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Ministère_des_Outre-mer',
                'ordre' => 14,
            ],
            [
                'nom' => 'Cohésion des territoires',
                'sigle' => 'MCT',
                'description' => 'Logement, urbanisme, politique de la ville, collectivités',
                'site_web' => 'https://www.cohesion-territoires.gouv.fr/',
                'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Ministère_de_la_Cohésion_des_territoires',
                'ordre' => 15,
            ],
            [
                'nom' => 'Solidarités',
                'sigle' => 'MSO',
                'description' => 'Action sociale, famille, personnes âgées, handicap',
                'site_web' => 'https://solidarites.gouv.fr/',
                'wikipedia_url' => 'https://fr.wikipedia.org/wiki/Ministère_des_Solidarités_(France)',
                'ordre' => 16,
            ],
        ];
    }
}
