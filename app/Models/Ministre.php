<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ministre extends Model
{
    protected $table = 'ministres';

    protected $fillable = [
        'ministere_id', 'gouvernement_id',
        'civilite', 'prenom', 'nom', 'slug', 'fonction', 'type_fonction',
        'date_debut', 'date_fin', 'actif',
        'date_naissance', 'lieu_naissance', 'profession', 'parti_politique', 'sexe',
        'photo_url', 'twitter', 'wikipedia_url',
        'decret_nomination', 'date_decret',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_naissance' => 'date',
        'date_decret' => 'date',
        'actif' => 'boolean',
    ];

    // Relations
    public function ministere(): BelongsTo
    {
        return $this->belongsTo(Ministere::class);
    }

    public function gouvernement(): BelongsTo
    {
        return $this->belongsTo(Gouvernement::class);
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeMinistresPleins($query)
    {
        return $query->where('type_fonction', 'ministre');
    }

    public function scopeSecretairesEtat($query)
    {
        return $query->where('type_fonction', 'secretaire_etat');
    }

    // Accessors
    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    public function getAgeAttribute(): ?int
    {
        if (!$this->date_naissance) return null;
        return $this->date_naissance->age;
    }

    public function getDureeFonctionAttribute(): string
    {
        $debut = $this->date_debut;
        $fin = $this->date_fin ?? now();
        $jours = $debut->diffInDays($fin);
        
        if ($jours < 30) return $jours . ' jours';
        if ($jours < 365) return floor($jours / 30) . ' mois';
        
        return floor($jours / 365) . ' an(s)';
    }

    public function getTypeFonctionLibelleAttribute(): string
    {
        return match($this->type_fonction) {
            'ministre' => 'Ministre',
            'ministre_delegue' => 'Ministre délégué',
            'secretaire_etat' => 'Secrétaire d\'État',
            'premier_ministre' => 'Premier ministre',
            default => 'Membre du gouvernement',
        };
    }

    public function getPhotoUrlAttribute($value): string
    {
        return $value ?: 'https://www.gouvernement.fr/sites/default/files/styles/minister_portrait/public/default_images/placeholder.png';
    }
}
