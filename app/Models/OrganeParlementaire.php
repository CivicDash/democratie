<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Organe parlementaire (groupe politique, commission, délégation, mission, office)
 * 
 * @property int $id
 * @property string $source
 * @property string $type
 * @property string $slug
 * @property string|null $sigle
 * @property string $nom
 * @property string|null $nom_long
 * @property string|null $description
 * @property string|null $couleur_hex
 * @property string|null $position_politique
 * @property int $nombre_membres
 * @property string|null $url_nosdeputes
 * @property string|null $url_assemblee
 */
class OrganeParlementaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'type',
        'slug',
        'sigle',
        'nom',
        'nom_long',
        'description',
        'couleur_hex',
        'position_politique',
        'nombre_membres',
        'url_nosdeputes',
        'url_assemblee',
    ];

    protected $casts = [
        'nombre_membres' => 'integer',
    ];

    // ========================================================================
    // RELATIONS
    // ========================================================================

    /**
     * Membres de cet organe
     */
    public function membres(): HasMany
    {
        return $this->hasMany(MembreOrgane::class, 'organe_id');
    }

    /**
     * Membres actuellement actifs
     */
    public function membresActifs(): HasMany
    {
        return $this->hasMany(MembreOrgane::class, 'organe_id')
            ->where('actif', true)
            ->orderBy('ordre');
    }

    /**
     * Députés/Sénateurs membres (relation many-to-many)
     */
    public function deputesSenateurs(): BelongsToMany
    {
        return $this->belongsToMany(
            DeputeSenateur::class,
            'membres_organes',
            'organe_id',
            'depute_senateur_id'
        )
        ->withPivot(['fonction', 'ordre', 'date_debut', 'date_fin', 'actif'])
        ->withTimestamps();
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    /**
     * Groupes politiques uniquement
     */
    public function scopeGroupes($query)
    {
        return $query->where('type', 'groupe');
    }

    /**
     * Commissions uniquement
     */
    public function scopeCommissions($query)
    {
        return $query->where('type', 'commission');
    }

    /**
     * Délégations uniquement
     */
    public function scopeDelegations($query)
    {
        return $query->where('type', 'delegation');
    }

    /**
     * Missions uniquement
     */
    public function scopeMissions($query)
    {
        return $query->where('type', 'mission');
    }

    /**
     * Assemblée nationale
     */
    public function scopeAssemblee($query)
    {
        return $query->where('source', 'assemblee');
    }

    /**
     * Sénat
     */
    public function scopeSenat($query)
    {
        return $query->where('source', 'senat');
    }

    // ========================================================================
    // ACCESSEURS
    // ========================================================================

    /**
     * Label du type d'organe
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'groupe' => 'Groupe politique',
            'commission' => 'Commission',
            'delegation' => 'Délégation',
            'mission' => 'Mission d\'information',
            'office' => 'Office parlementaire',
            default => ucfirst($this->type),
        };
    }

    /**
     * Nom complet avec type
     */
    public function getNomCompletAttribute(): string
    {
        if ($this->type === 'groupe') {
            return $this->nom;
        }
        
        return $this->type_label . ' : ' . $this->nom;
    }

    /**
     * Couleur par défaut si non définie
     */
    public function getCouleurAttribute(): string
    {
        if ($this->couleur_hex) {
            return $this->couleur_hex;
        }

        // Couleurs par défaut selon le type
        return match($this->type) {
            'groupe' => '#6B7280',
            'commission' => '#3B82F6',
            'delegation' => '#10B981',
            'mission' => '#F59E0B',
            'office' => '#8B5CF6',
            default => '#6B7280',
        };
    }
}

