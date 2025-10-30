<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Cache des données récupérées depuis data.gouv.fr
 * 
 * @property int $id
 * @property string $dataset_id
 * @property string|null $resource_id
 * @property string|null $code_insee
 * @property int|null $annee
 * @property string $data_type
 * @property array $data
 * @property array|null $metadata
 * @property \Carbon\Carbon $fetched_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class DataGouvCache extends Model
{
    use HasFactory;

    protected $table = 'datagouv_cache';

    protected $fillable = [
        'dataset_id',
        'resource_id',
        'code_insee',
        'annee',
        'data_type',
        'data',
        'metadata',
        'fetched_at',
    ];

    protected $casts = [
        'data' => 'array',
        'metadata' => 'array',
        'fetched_at' => 'datetime',
        'annee' => 'integer',
    ];

    /**
     * Scope pour filtrer par type de données
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('data_type', $type);
    }

    /**
     * Scope pour filtrer par code INSEE
     */
    public function scopeForCommune($query, string $codeInsee)
    {
        return $query->where('code_insee', $codeInsee);
    }

    /**
     * Scope pour filtrer par année
     */
    public function scopeForYear($query, int $annee)
    {
        return $query->where('annee', $annee);
    }

    /**
     * Scope pour les données récentes (moins de X jours)
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('fetched_at', '>=', now()->subDays($days));
    }

    /**
     * Vérifie si les données sont périmées
     */
    public function isStale(int $maxAgeDays = 7): bool
    {
        return $this->fetched_at->diffInDays(now()) > $maxAgeDays;
    }

    /**
     * Récupère les données avec fallback
     */
    public static function getCached(string $datasetId, ?string $codeInsee = null, ?int $annee = null): ?array
    {
        $query = static::where('dataset_id', $datasetId)
            ->recent();

        if ($codeInsee) {
            $query->forCommune($codeInsee);
        }

        if ($annee) {
            $query->forYear($annee);
        }

        $cache = $query->first();

        return $cache?->data;
    }
}

