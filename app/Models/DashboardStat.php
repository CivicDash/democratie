<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardStat extends Model
{
    protected $fillable = ['key', 'value', 'calculated_at'];

    protected $casts = [
        'value' => 'array',
        'calculated_at' => 'datetime',
    ];

    /**
     * Récupère une stat par sa clé
     */
    public static function get(string $key, $default = null)
    {
        $stat = static::where('key', $key)->first();

        return $stat ? $stat->value : $default;
    }

    /**
     * Met à jour ou crée une stat
     */
    public static function set(string $key, $value): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'calculated_at' => now(),
            ]
        );
    }

    /**
     * Vérifie si une stat existe et est fraîche (moins de X heures)
     */
    public static function isFresh(string $key, int $hours = 24): bool
    {
        $stat = static::where('key', $key)->first();
        if (! $stat) {
            return false;
        }

        return $stat->calculated_at->diffInHours(now()) < $hours;
    }
}
