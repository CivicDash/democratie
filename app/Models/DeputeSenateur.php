<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle pour les députés et sénateurs
 * 
 * @property int $id
 * @property string $source
 * @property string $uid
 * @property string $nom
 * @property string $prenom
 * @property string|null $civilite
 * @property string|null $groupe_politique
 * @property string|null $groupe_sigle
 * @property string|null $circonscription
 * @property string|null $numero_circonscription
 * @property string|null $profession
 * @property \Carbon\Carbon|null $date_naissance
 * @property int|null $legislature
 * @property \Carbon\Carbon|null $debut_mandat
 * @property \Carbon\Carbon|null $fin_mandat
 * @property bool $en_exercice
 * @property string|null $photo_url
 * @property string|null $url_profil
 * @property array|null $fonctions
 * @property array|null $commissions
 * @property int $nb_propositions
 * @property int $nb_amendements
 * @property float|null $taux_presence
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class DeputeSenateur extends Model
{
    use HasFactory;

    protected $table = 'deputes_senateurs';

    protected $fillable = [
        'source',
        'uid',
        'nom',
        'prenom',
        'civilite',
        'groupe_politique',
        'groupe_sigle',
        'circonscription',
        'numero_circonscription',
        'profession',
        'date_naissance',
        'legislature',
        'debut_mandat',
        'fin_mandat',
        'en_exercice',
        'photo_url',
        'url_profil',
        'fonctions',
        'commissions',
        'nb_propositions',
        'nb_amendements',
        'taux_presence',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'debut_mandat' => 'date',
        'fin_mandat' => 'date',
        'en_exercice' => 'boolean',
        'legislature' => 'integer',
        'nb_propositions' => 'integer',
        'nb_amendements' => 'integer',
        'taux_presence' => 'decimal:2',
        'fonctions' => 'array',
        'commissions' => 'array',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    /**
     * Groupe parlementaire du député/sénateur
     */
    public function groupeParlementaire(): BelongsTo
    {
        return $this->belongsTo(GroupeParlementaire::class, 'groupe_sigle', 'sigle')
            ->where('source', $this->source);
    }

    /**
     * Département du député/sénateur
     */
    public function department(): BelongsTo
    {
        // Extraire le code département de la circonscription (ex: "75-01" -> "75")
        $deptCode = substr($this->circonscription ?? '', 0, 2);
        return $this->belongsTo(TerritoryDepartment::class, 'code', $deptCode);
    }

    /**
     * Propositions de loi déposées
     */
    public function propositions(): HasMany
    {
        return $this->hasMany(PropositionLoi::class, 'auteurs->0->uid', 'uid');
    }

    /**
     * Amendements déposés
     */
    public function amendements(): HasMany
    {
        return $this->hasMany(Amendement::class, 'auteur_uid', 'uid');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    public function scopeDeputes($query)
    {
        return $query->where('source', 'assemblee');
    }

    public function scopeSenateurs($query)
    {
        return $query->where('source', 'senat');
    }

    public function scopeEnExercice($query)
    {
        return $query->where('en_exercice', true);
    }

    public function scopeByGroupe($query, string $groupe)
    {
        return $query->where('groupe_politique', 'like', "%{$groupe}%");
    }

    public function scopeByCirconscription($query, string $circonscription)
    {
        return $query->where('circonscription', 'like', "%{$circonscription}%");
    }

    public function scopeActifs($query)
    {
        return $query->where('en_exercice', true)
                     ->where(function ($q) {
                         $q->whereNull('fin_mandat')
                           ->orWhere('fin_mandat', '>', now());
                     });
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nom', 'like', "%{$search}%")
              ->orWhere('prenom', 'like', "%{$search}%")
              ->orWhere('circonscription', 'like', "%{$search}%");
        });
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    public function getNomCompletAttribute(): string
    {
        $civilite = $this->civilite ? $this->civilite . ' ' : '';
        return $civilite . $this->prenom . ' ' . $this->nom;
    }

    public function getSourceLabelAttribute(): string
    {
        return match($this->source) {
            'assemblee' => 'Député',
            'senat' => 'Sénateur',
            default => $this->source,
        };
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

    public function getEstPresidentAttribute(): bool
    {
        if (empty($this->fonctions)) {
            return false;
        }

        foreach ($this->fonctions as $fonction) {
            $libelle = is_string($fonction) ? $fonction : ($fonction['libelle'] ?? '');
            if (str_contains(strtolower($libelle), 'président')) {
                return true;
            }
        }

        return false;
    }

    public function getEstRapporteurAttribute(): bool
    {
        if (empty($this->fonctions)) {
            return false;
        }

        foreach ($this->fonctions as $fonction) {
            $libelle = is_string($fonction) ? $fonction : ($fonction['libelle'] ?? '');
            if (str_contains(strtolower($libelle), 'rapporteur')) {
                return true;
            }
        }

        return false;
    }

    public function getActiviteScoreAttribute(): float
    {
        // Score d'activité basé sur nombre de propositions, amendements et présence
        $score = 0;
        
        // Propositions (max 40 points)
        $score += min($this->nb_propositions * 2, 40);
        
        // Amendements (max 30 points)
        $score += min($this->nb_amendements * 0.5, 30);
        
        // Présence (max 30 points)
        if ($this->taux_presence) {
            $score += ($this->taux_presence / 100) * 30;
        }

        return round($score, 2);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'source' => $this->source,
            'source_label' => $this->source_label,
            'nom_complet' => $this->nom_complet,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'civilite' => $this->civilite,
            'groupe_politique' => $this->groupe_politique,
            'groupe_sigle' => $this->groupe_sigle,
            'circonscription' => $this->circonscription,
            'numero_circonscription' => $this->numero_circonscription,
            'profession' => $this->profession,
            'age' => $this->age,
            'en_exercice' => $this->en_exercice,
            'legislature' => $this->legislature,
            'duree_mandat_annees' => $this->duree_mandat,
            'photo_url' => $this->photo_url,
            'url_profil' => $this->url_profil,
            'fonctions' => $this->fonctions,
            'commissions' => $this->commissions,
            'statistiques' => [
                'nb_propositions' => $this->nb_propositions,
                'nb_amendements' => $this->nb_amendements,
                'taux_presence' => $this->taux_presence,
                'activite_score' => $this->activite_score,
            ],
            'badges' => [
                'est_president' => $this->est_president,
                'est_rapporteur' => $this->est_rapporteur,
            ],
        ];
    }
}

