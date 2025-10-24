<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Profil citoyen avec pseudonyme aléatoire et scope territorial
 * 
 * @property int $id
 * @property int $user_id
 * @property string $display_name Pseudonyme aléatoire (ex: Citoyen123)
 * @property string $citizen_ref_hash Hash du numéro de sécu + PEPPER
 * @property string $scope national|region|dept
 * @property int|null $region_id
 * @property int|null $department_id
 * @property bool $is_verified Identité vérifiée (FranceConnect+)
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'display_name',
        'citizen_ref_hash',
        'scope',
        'region_id',
        'department_id',
        'is_verified',
        'verified_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Utilisateur associé
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Région (si scope region ou dept)
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(TerritoryRegion::class, 'region_id');
    }

    /**
     * Département (si scope dept)
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(TerritoryDepartment::class, 'department_id');
    }

    /**
     * Génère un pseudonyme aléatoire
     */
    public static function generateDisplayName(): string
    {
        return 'Citoyen' . rand(1000, 9999);
    }

    /**
     * Hash une référence citoyenne avec PEPPER
     */
    public static function hashCitizenRef(string $citizenRef): string
    {
        $pepper = config('app.pepper');
        if (!$pepper) {
            throw new \RuntimeException('PEPPER not configured in .env');
        }
        
        return hash('sha256', $citizenRef . $pepper);
    }

    /**
     * Scope: profils vérifiés
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope: profils par scope territorial
     */
    public function scopeByScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Scope: profils nationaux
     */
    public function scopeNational($query)
    {
        return $query->where('scope', 'national');
    }

    /**
     * Scope: profils régionaux
     */
    public function scopeRegional($query, ?int $regionId = null)
    {
        $query = $query->where('scope', 'region');
        
        if ($regionId) {
            $query->where('region_id', $regionId);
        }
        
        return $query;
    }

    /**
     * Scope: profils départementaux
     */
    public function scopeDepartmental($query, ?int $departmentId = null)
    {
        $query = $query->where('scope', 'dept');
        
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        return $query;
    }
}

