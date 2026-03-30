<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosteMinisteriel extends Model
{
    use HasFactory;

    protected $table = 'postes_ministeriels';

    protected $fillable = [
        'personne_id',
        'gouvernement_id',
        'ministere_id',
        'fonction',
        'type_fonction',
        'ordre',
        'date_debut',
        'date_fin',
        'actif',
        'decret_nomination',
        'date_decret',
        'metadata',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_decret' => 'date',
        'actif' => 'boolean',
        'metadata' => 'array',
    ];

    protected $appends = ['duree_fonction', 'type_fonction_libelle', 'est_actif'];

    /**
     * Relations
     */
    public function personne(): BelongsTo
    {
        return $this->belongsTo(PersonnePolitique::class, 'personne_id');
    }

    public function gouvernement(): BelongsTo
    {
        return $this->belongsTo(Gouvernement::class);
    }

    public function ministere(): BelongsTo
    {
        return $this->belongsTo(Ministere::class);
    }

    /**
     * Accessors
     */
    public function getDureeFonctionAttribute(): string
    {
        $debut = $this->date_debut;

        if (! $debut) {
            return 'Durée inconnue';
        }

        $fin = $this->date_fin ?? now();

        $diff = $debut->diff($fin);

        if ($diff->y > 0) {
            return $diff->y.' an'.($diff->y > 1 ? 's' : '').
                   ($diff->m > 0 ? ' et '.$diff->m.' mois' : '');
        }

        if ($diff->m > 0) {
            return $diff->m.' mois'.($diff->d > 0 ? ' et '.$diff->d.' jours' : '');
        }

        return $diff->d.' jour'.($diff->d > 1 ? 's' : '');
    }

    public function getTypeFonctionLibelleAttribute(): string
    {
        return match ($this->type_fonction) {
            'president' => 'Président de la République',
            'premier_ministre' => 'Premier ministre',
            'ministre_etat' => 'Ministre d\'État',
            'ministre' => 'Ministre',
            'ministre_delegue' => 'Ministre délégué',
            'secretaire_etat' => 'Secrétaire d\'État',
            'haut_commissaire' => 'Haut-commissaire',
            default => 'Membre du gouvernement',
        };
    }

    /**
     * Détermine si le poste est actuellement actif
     * Un poste est actif s'il n'a pas de date de fin OU si la date de fin est dans le futur
     */
    public function getEstActifAttribute(): bool
    {
        if ($this->date_fin === null) {
            return true;
        }

        return $this->date_fin->isFuture();
    }

    /**
     * Scopes
     */
    public function scopeActifs($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('date_fin')
                ->orWhere('date_fin', '>', now());
        });
    }

    public function scopeDuGouvernement($query, int $gouvernementId)
    {
        return $query->where('gouvernement_id', $gouvernementId);
    }

    public function scopeParType($query, string $type)
    {
        return $query->where('type_fonction', $type);
    }

    public function scopeOrdreProtocolaire($query)
    {
        return $query->orderBy('ordre')->orderBy('date_debut');
    }
}
