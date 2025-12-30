<?php

namespace App\Console\Commands;

use App\Models\ActeurAN;
use App\Models\ElusGlobalStats;
use App\Models\Maire;
use App\Models\Senateur;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateElusGlobalStats extends Command
{
    protected $signature = 'calculate:elus-global-stats {--force : Forcer le recalcul même si récent}';
    protected $description = 'Calcule les statistiques globales des élus (députés, sénateurs, maires)';

    public function handle(): int
    {
        $this->info('📊 Calcul des statistiques globales des élus...');

        // Vérifier si les stats sont récentes (moins de 6h)
        $lastStat = ElusGlobalStats::orderBy('calculated_at', 'desc')->first();
        if (!$this->option('force') && $lastStat && $lastStat->calculated_at > now()->subHours(6)) {
            $this->info('Les statistiques sont à jour (calculées il y a moins de 6h)');
            return Command::SUCCESS;
        }

        $this->calculateDeputesStats();
        $this->calculateSenateursStats();
        $this->calculateMairesStats();

        $this->info('✅ Statistiques globales calculées avec succès !');
        return Command::SUCCESS;
    }

    protected function calculateDeputesStats(): void
    {
        $this->info('  → Calcul des statistiques des députés...');

        $deputes = ActeurAN::whereHas('mandats', function ($q) {
            $q->where('type_organe', 'ASSEMBLEE')->whereNull('date_fin');
        })->get();

        $total = ActeurAN::whereHas('mandats', function ($q) {
            $q->where('type_organe', 'ASSEMBLEE');
        })->count();

        $actifs = $deputes->count();

        // Parité (par civilité)
        $hommes = $deputes->where('civilite', 'M.')->count();
        $femmes = $deputes->where('civilite', 'Mme')->count();

        // Âges
        $ages = $deputes->map(fn($d) => $d->date_naissance ? Carbon::parse($d->date_naissance)->age : null)
            ->filter()
            ->values();

        $tranchesAge = $this->calculateTrancheAges($ages->toArray());

        // Professions
        $professions = $deputes->groupBy('profession')
            ->map(fn($group, $key) => ['nom' => $key ?: 'Non renseigné', 'count' => $group->count()])
            ->sortByDesc('count')
            ->take(10)
            ->values()
            ->toArray();

        // Groupes politiques
        $groupes = DB::table('organes_an')
            ->where('code_type', 'GP')
            ->whereNull('date_fin')
            ->get()
            ->map(function ($groupe) {
                $count = DB::table('mandats_an')
                    ->where('organe_ref', $groupe->uid)
                    ->whereNull('date_fin')
                    ->count();
                return [
                    'nom' => $groupe->libelle_abrege ?: $groupe->libelle,
                    'count' => $count,
                    'couleur' => $groupe->couleur ?? '#6B7280',
                ];
            })
            ->filter(fn($g) => $g['count'] > 0)
            ->sortByDesc('count')
            ->take(10)
            ->values()
            ->toArray();

        ElusGlobalStats::updateOrCreate(
            ['type_elu' => 'deputes'],
            [
                'total' => $total,
                'actifs' => $actifs,
                'hommes' => $hommes,
                'femmes' => $femmes,
                'pct_femmes' => $actifs > 0 ? round(($femmes / $actifs) * 100, 1) : 0,
                'age_moyen' => $ages->isNotEmpty() ? round($ages->avg(), 1) : null,
                'age_min' => $ages->min(),
                'age_max' => $ages->max(),
                'tranches_age' => $tranchesAge,
                'top_professions' => $professions,
                'top_groupes' => $groupes,
                'calculated_at' => now(),
            ]
        );

        $this->info("    ✓ {$actifs} députés actifs");
    }

    protected function calculateSenateursStats(): void
    {
        $this->info('  → Calcul des statistiques des sénateurs...');

        $senateurs = Senateur::actifs()->get();
        $total = Senateur::count();
        $actifs = $senateurs->count();

        // Parité (par civilité car pas de champ sexe)
        $hommes = $senateurs->where('civilite', 'M.')->count();
        $femmes = $senateurs->where('civilite', 'Mme')->count();

        // Âges
        $ages = $senateurs->map(fn($s) => $s->date_naissance ? Carbon::parse($s->date_naissance)->age : null)
            ->filter()
            ->values();

        $tranchesAge = $this->calculateTrancheAges($ages->toArray());

        // Professions
        $professions = $senateurs->groupBy('profession')
            ->map(fn($group, $key) => ['nom' => $key ?: 'Non renseigné', 'count' => $group->count()])
            ->sortByDesc('count')
            ->take(10)
            ->values()
            ->toArray();

        // Groupes politiques
        $groupes = $senateurs->groupBy('groupe_politique')
            ->map(fn($group, $key) => [
                'nom' => $key ?: 'Non inscrit',
                'count' => $group->count(),
                'couleur' => $this->getGroupeCouleur($key),
            ])
            ->sortByDesc('count')
            ->take(10)
            ->values()
            ->toArray();

        ElusGlobalStats::updateOrCreate(
            ['type_elu' => 'senateurs'],
            [
                'total' => $total,
                'actifs' => $actifs,
                'hommes' => $hommes,
                'femmes' => $femmes,
                'pct_femmes' => $actifs > 0 ? round(($femmes / $actifs) * 100, 1) : 0,
                'age_moyen' => $ages->isNotEmpty() ? round($ages->avg(), 1) : null,
                'age_min' => $ages->min(),
                'age_max' => $ages->max(),
                'tranches_age' => $tranchesAge,
                'top_professions' => $professions,
                'top_groupes' => $groupes,
                'calculated_at' => now(),
            ]
        );

        $this->info("    ✓ {$actifs} sénateurs actifs");
    }

    protected function calculateMairesStats(): void
    {
        $this->info('  → Calcul des statistiques des maires...');

        $total = Maire::count();
        $actifs = Maire::where('en_exercice', true)->count();

        // Parité (par civilité)
        $hommes = Maire::where('en_exercice', true)->where('civilite', 'M.')->count();
        $femmes = Maire::where('en_exercice', true)->where('civilite', 'Mme')->count();

        // Âges des maires (si disponible)
        $maires = Maire::where('en_exercice', true)->whereNotNull('date_naissance')->get();
        $ages = $maires->map(fn($m) => Carbon::parse($m->date_naissance)->age)->filter()->values();
        $tranchesAge = $this->calculateTrancheAges($ages->toArray());

        // Professions (catégorie socio-professionnelle)
        $professions = Maire::where('en_exercice', true)
            ->whereNotNull('profession')
            ->where('profession', '!=', '')
            ->select('profession', DB::raw('count(*) as count'))
            ->groupBy('profession')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($p) => ['nom' => $p->profession, 'count' => $p->count])
            ->toArray();

        // Nuances politiques
        $nuances = Maire::where('en_exercice', true)
            ->whereNotNull('nuance_politique')
            ->where('nuance_politique', '!=', '')
            ->select('nuance_politique', DB::raw('count(*) as count'))
            ->groupBy('nuance_politique')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($n) => [
                'nom' => $this->getNuanceLibelle($n->nuance_politique),
                'count' => $n->count,
                'couleur' => $this->getNuanceCouleur($n->nuance_politique),
            ])
            ->toArray();

        ElusGlobalStats::updateOrCreate(
            ['type_elu' => 'maires'],
            [
                'total' => $total,
                'actifs' => $actifs,
                'hommes' => $hommes,
                'femmes' => $femmes,
                'pct_femmes' => $actifs > 0 ? round(($femmes / $actifs) * 100, 1) : 0,
                'age_moyen' => $ages->isNotEmpty() ? round($ages->avg(), 1) : null,
                'age_min' => $ages->min(),
                'age_max' => $ages->max(),
                'tranches_age' => $tranchesAge,
                'top_professions' => $professions,
                'top_groupes' => $nuances,
                'calculated_at' => now(),
            ]
        );

        $this->info("    ✓ {$actifs} maires actifs");
    }

    protected function calculateTrancheAges(array $ages): array
    {
        $tranches = [
            '18-30' => 0,
            '31-40' => 0,
            '41-50' => 0,
            '51-60' => 0,
            '61-70' => 0,
            '71+' => 0,
        ];

        foreach ($ages as $age) {
            if ($age <= 30) $tranches['18-30']++;
            elseif ($age <= 40) $tranches['31-40']++;
            elseif ($age <= 50) $tranches['41-50']++;
            elseif ($age <= 60) $tranches['51-60']++;
            elseif ($age <= 70) $tranches['61-70']++;
            else $tranches['71+']++;
        }

        return $tranches;
    }

    protected function getGroupeCouleur(?string $groupe): string
    {
        $couleurs = [
            'Les Républicains' => '#0066CC',
            'LR' => '#0066CC',
            'Socialiste' => '#FF6666',
            'PS' => '#FF6666',
            'Union Centriste' => '#FF9900',
            'UC' => '#FF9900',
            'RDSE' => '#9966CC',
            'CRCE' => '#DD0000',
            'Communiste' => '#DD0000',
            'RDPI' => '#FFCC00',
            'GEST' => '#00AA00',
            'Écologiste' => '#00AA00',
            'RN' => '#0D378A',
            'Non inscrit' => '#999999',
        ];

        foreach ($couleurs as $key => $color) {
            if (stripos($groupe ?? '', $key) !== false) {
                return $color;
            }
        }

        return '#6B7280';
    }

    protected function getNuanceLibelle(string $code): string
    {
        $labels = [
            'DVD' => 'Divers droite',
            'DVG' => 'Divers gauche',
            'UDI' => 'Union des Démocrates',
            'LR' => 'Les Républicains',
            'RN' => 'Rassemblement National',
            'PS' => 'Parti Socialiste',
            'EELV' => 'Écologistes',
            'PCF' => 'Parti Communiste',
            'REM' => 'Renaissance',
            'MDM' => 'Modem',
            'DVC' => 'Divers centre',
            'SE' => 'Sans étiquette',
            'DIV' => 'Divers',
        ];

        return $labels[$code] ?? $code;
    }

    protected function getNuanceCouleur(string $code): string
    {
        $couleurs = [
            'DVD' => '#4169E1',
            'DVG' => '#FFB6C1',
            'UDI' => '#00BFFF',
            'LR' => '#0066CC',
            'RN' => '#0D378A',
            'PS' => '#FF6666',
            'EELV' => '#00AA00',
            'PCF' => '#DD0000',
            'REM' => '#FFCC00',
            'MDM' => '#FF9900',
            'DVC' => '#87CEEB',
            'SE' => '#999999',
            'DIV' => '#AAAAAA',
        ];

        return $couleurs[$code] ?? '#6B7280';
    }
}
