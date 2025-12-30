<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElusGlobalStats extends Model
{
    protected $table = 'elus_global_stats';

    protected $fillable = [
        'type_elu',
        'total',
        'actifs',
        'hommes',
        'femmes',
        'pct_femmes',
        'age_moyen',
        'age_min',
        'age_max',
        'tranches_age',
        'top_professions',
        'top_groupes',
        'data_parite',
        'data_ages',
        'data_groupes',
        'calculated_at',
    ];

    protected $casts = [
        'tranches_age' => 'array',
        'top_professions' => 'array',
        'top_groupes' => 'array',
        'data_parite' => 'array',
        'data_ages' => 'array',
        'data_groupes' => 'array',
        'calculated_at' => 'datetime',
        'pct_femmes' => 'float',
        'age_moyen' => 'float',
    ];

    /**
     * Récupère les stats pour un type d'élu
     */
    public static function getForType(string $type): ?self
    {
        return self::where('type_elu', $type)->first();
    }

    /**
     * Récupère toutes les stats formatées pour le contrôleur
     */
    public static function getAllForComparison(): array
    {
        $stats = self::all()->keyBy('type_elu');

        $effectifs = [];
        $ages = [];
        $parite = [];
        $professions = [];
        $groupes = [];

        foreach (['deputes', 'senateurs', 'maires'] as $type) {
            $stat = $stats->get($type);
            
            if ($stat) {
                $effectifs[$type] = [
                    'total' => $stat->total,
                    'actifs' => $stat->actifs,
                ];

                $ages[$type] = [
                    'moyen' => $stat->age_moyen,
                    'min' => $stat->age_min,
                    'max' => $stat->age_max,
                    'tranches' => $stat->tranches_age ?? [],
                ];

                $parite[$type] = [
                    'hommes' => $stat->hommes,
                    'femmes' => $stat->femmes,
                    'pct_femmes' => $stat->pct_femmes,
                ];

                $professions[$type] = $stat->top_professions ?? [];
                $groupes[$type] = $stat->top_groupes ?? [];
            } else {
                // Valeurs par défaut si pas de stats
                $effectifs[$type] = ['total' => 0, 'actifs' => 0];
                $ages[$type] = ['moyen' => null, 'min' => null, 'max' => null, 'tranches' => []];
                $parite[$type] = ['hommes' => 0, 'femmes' => 0, 'pct_femmes' => 0];
                $professions[$type] = [];
                $groupes[$type] = [];
            }
        }

        // Calculer les totaux
        $totalElus = array_sum(array_column($effectifs, 'actifs'));
        $totalFemmes = array_sum(array_column($parite, 'femmes'));
        $totalHommes = array_sum(array_column($parite, 'hommes'));

        return [
            'effectifs' => $effectifs,
            'ages' => $ages,
            'parite' => $parite,
            'professions' => $professions,
            'groupes' => $groupes,
            'totaux' => [
                'elus_total' => $totalElus,
                'femmes_total' => $totalFemmes,
                'hommes_total' => $totalHommes,
                'pct_femmes_global' => $totalElus > 0 ? round(($totalFemmes / $totalElus) * 100, 1) : 0,
            ],
        ];
    }
}
