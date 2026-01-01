<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PersonnePolitique extends Model
{
    use HasFactory;

    protected $table = 'personnes_politiques';

    protected $fillable = [
        'slug',
        'civilite',
        'prenom',
        'nom',
        'date_naissance',
        'lieu_naissance',
        'date_deces',
        'profession',
        'biographie',
        'parti_politique',
        'nuance_politique',
        'photo_url',
        'photo_officielle_url',
        'wikipedia_url',
        'wikipedia_extract',
        'twitter_url',
        'facebook_url',
        'linkedin_url',
        'instagram_url',
        'site_web',
        'uid_an',
        'uid_senat',
        'maire_id',
        'metadata',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_deces' => 'date',
        'metadata' => 'array',
    ];

    protected $appends = ['nom_complet', 'age', 'photo'];

    /**
     * Boot du modèle
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($personne) {
            if (empty($personne->slug)) {
                $personne->slug = Str::slug($personne->prenom . '-' . $personne->nom);
            }
        });
    }

    /**
     * Relations
     */
    
    // Tous les postes occupés par cette personne
    public function postes(): HasMany
    {
        return $this->hasMany(PosteMinisteriel::class, 'personne_id')
            ->orderBy('date_debut', 'desc');
    }

    // Poste actuel
    public function posteActuel(): HasMany
    {
        return $this->hasMany(PosteMinisteriel::class, 'personne_id')
            ->where('actif', true);
    }

    // Lien vers le député (si applicable)
    public function depute(): BelongsTo
    {
        return $this->belongsTo(ActeurAN::class, 'uid_an', 'uid');
    }

    // Lien vers le sénateur (si applicable)
    public function senateur(): BelongsTo
    {
        return $this->belongsTo(Senateur::class, 'uid_senat', 'matricule');
    }

    // Lien vers le maire (si applicable)
    public function maire(): BelongsTo
    {
        return $this->belongsTo(Maire::class, 'maire_id');
    }

    /**
     * Accessors
     */
    public function getNomCompletAttribute(): string
    {
        $civilite = $this->civilite ? $this->civilite . ' ' : '';
        return trim($civilite . $this->prenom . ' ' . $this->nom);
    }

    public function getAgeAttribute(): ?int
    {
        if (!$this->date_naissance) {
            return null;
        }
        $endDate = $this->date_deces ?? now();
        return $this->date_naissance->diffInYears($endDate);
    }

    public function getPhotoAttribute(): ?string
    {
        // Priorité : photo officielle > photo URL > photo Wikipedia du député/sénateur
        return $this->photo_officielle_url 
            ?? $this->photo_url 
            ?? $this->depute?->photo_officielle
            ?? $this->senateur?->photo_wikipedia_url;
    }

    /**
     * Scopes
     */
    public function scopeActifs($query)
    {
        return $query->whereHas('posteActuel');
    }

    public function scopeAvecPoste($query, string $type)
    {
        return $query->whereHas('postes', fn($q) => $q->where('type_fonction', $type));
    }

    /**
     * Méthodes
     */
    
    // Récupérer l'historique des postes formaté
    public function getHistoriquePostes(): array
    {
        return $this->postes->map(function ($poste) {
            $periode = $poste->date_debut->format('d/m/Y');
            $periode .= $poste->date_fin ? ' - ' . $poste->date_fin->format('d/m/Y') : ' - en cours';
            
            return [
                'gouvernement' => $poste->gouvernement->nom_complet,
                'fonction' => $poste->fonction,
                'ministere' => $poste->ministere?->nom,
                'periode' => $periode,
                'duree' => $poste->duree_fonction,
            ];
        })->toArray();
    }

    // Créer ou trouver une personne à partir d'un nom
    public static function findOrCreateByName(string $prenom, string $nom, array $attributes = []): self
    {
        $slug = Str::slug($prenom . '-' . $nom);
        
        return static::firstOrCreate(
            ['slug' => $slug],
            array_merge([
                'prenom' => $prenom,
                'nom' => $nom,
            ], $attributes)
        );
    }
}
