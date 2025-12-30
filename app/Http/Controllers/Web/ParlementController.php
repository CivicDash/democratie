<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActeurAN;
use App\Models\Senateur;
use App\Models\Maire;
use App\Models\OrganeAN;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ParlementController extends Controller
{
    /**
     * Page de statistiques globales : Députés / Sénateurs / Maires
     * 
     * GET /parlement/comparaison
     */
    public function comparaison(): Response
    {
        // Cache les stats pendant 1 heure (calculs lourds)
        $stats = Cache::remember('parlement_stats_globales', 3600, function () {
            return $this->calculateStats();
        });

        return Inertia::render('Parlement/Comparaison', $stats);
    }

    private function calculateStats(): array
    {
        // ========================================================================
        // EFFECTIFS
        // ========================================================================
        $deputesActifs = ActeurAN::whereHas('mandats', function($q) {
            $q->where('type_organe', 'ASSEMBLEE')
              ->whereNull('date_fin');
        })->count();

        $senateursActifs = Senateur::actifs()->count();
        $mairesActifs = Maire::enExercice()->count();

        $effectifs = [
            'deputes' => [
                'total' => 577,
                'actifs' => $deputesActifs,
            ],
            'senateurs' => [
                'total' => 348,
                'actifs' => $senateursActifs,
            ],
            'maires' => [
                'total' => $mairesActifs,
                'actifs' => $mairesActifs,
            ],
        ];

        // ========================================================================
        // PARITÉ HOMMES / FEMMES
        // ========================================================================
        $pariteDeputes = [
            'hommes' => ActeurAN::whereHas('mandats', fn($q) => $q->where('type_organe', 'ASSEMBLEE')->whereNull('date_fin'))
                ->where('civilite', 'M.')->count(),
            'femmes' => ActeurAN::whereHas('mandats', fn($q) => $q->where('type_organe', 'ASSEMBLEE')->whereNull('date_fin'))
                ->where('civilite', 'Mme')->count(),
        ];

        $pariteSenateurs = [
            'hommes' => Senateur::actifs()->where('civilite', 'M.')->count(),
            'femmes' => Senateur::actifs()->where('civilite', 'Mme')->count(),
        ];

        $pariteMaires = [
            'hommes' => Maire::enExercice()->where('civilite', 'M.')->count(),
            'femmes' => Maire::enExercice()->where('civilite', 'Mme')->count(),
        ];

        // Calcul pourcentages
        $pariteDeputes['pct_femmes'] = $deputesActifs > 0 
            ? round(($pariteDeputes['femmes'] / $deputesActifs) * 100, 1) 
            : 0;
        $pariteSenateurs['pct_femmes'] = $senateursActifs > 0 
            ? round(($pariteSenateurs['femmes'] / $senateursActifs) * 100, 1) 
            : 0;
        $pariteMaires['pct_femmes'] = $mairesActifs > 0 
            ? round(($pariteMaires['femmes'] / $mairesActifs) * 100, 1) 
            : 0;

        $parite = [
            'deputes' => $pariteDeputes,
            'senateurs' => $pariteSenateurs,
            'maires' => $pariteMaires,
        ];

        // ========================================================================
        // ÂGE
        // ========================================================================
        $ageDeputes = ActeurAN::whereHas('mandats', function($q) {
            $q->where('type_organe', 'ASSEMBLEE')
              ->whereNull('date_fin');
        })
        ->whereNotNull('date_naissance')
        ->get()
        ->map(fn($d) => $d->date_naissance->age)
        ->filter();

        $ageSenateurs = Senateur::actifs()
            ->whereNotNull('date_naissance')
            ->get()
            ->map(fn($s) => $s->date_naissance->age)
            ->filter();

        $ageMaires = Maire::enExercice()
            ->whereNotNull('date_naissance')
            ->get()
            ->map(fn($m) => $m->date_naissance->age)
            ->filter();

        $ages = [
            'deputes' => $this->calculateAgeStats($ageDeputes),
            'senateurs' => $this->calculateAgeStats($ageSenateurs),
            'maires' => $this->calculateAgeStats($ageMaires),
        ];

        // ========================================================================
        // TOP 10 PROFESSIONS
        // ========================================================================
        $professionsDeputes = ActeurAN::whereHas('mandats', fn($q) => $q->where('type_organe', 'ASSEMBLEE')->whereNull('date_fin'))
            ->whereNotNull('profession')
            ->where('profession', '!=', '')
            ->select('profession', DB::raw('count(*) as count'))
            ->groupBy('profession')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($p) => ['profession' => $p->profession, 'count' => $p->count]);

        $professionsSenateurs = Senateur::actifs()
            ->whereNotNull('description_profession')
            ->where('description_profession', '!=', '')
            ->select('description_profession as profession', DB::raw('count(*) as count'))
            ->groupBy('description_profession')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($p) => ['profession' => $p->profession, 'count' => $p->count]);

        $professionsMaires = Maire::enExercice()
            ->whereNotNull('profession')
            ->where('profession', '!=', '')
            ->select('profession', DB::raw('count(*) as count'))
            ->groupBy('profession')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($p) => ['profession' => $p->profession, 'count' => $p->count]);

        $professions = [
            'deputes' => $professionsDeputes,
            'senateurs' => $professionsSenateurs,
            'maires' => $professionsMaires,
        ];

        // ========================================================================
        // GROUPES POLITIQUES (Députés + Sénateurs) / NUANCES (Maires)
        // ========================================================================
        $groupesDeputes = OrganeAN::where('code_type', 'GP')
            ->where('legislature', 17)
            ->withCount(['mandats' => function($q) {
                $q->whereNull('date_fin');
            }])
            ->orderBy('mandats_count', 'desc')
            ->get()
            ->map(fn($g) => [
                'sigle' => $g->libelle_abrege,
                'nom' => $g->libelle,
                'effectif' => $g->mandats_count,
            ]);

        $groupesSenateurs = Senateur::actifs()
            ->whereNotNull('groupe_politique')
            ->select('groupe_politique as sigle', DB::raw('count(*) as effectif'))
            ->groupBy('groupe_politique')
            ->orderBy('effectif', 'desc')
            ->get()
            ->map(fn($g) => [
                'sigle' => $g->sigle,
                'nom' => $g->sigle,
                'effectif' => $g->effectif,
            ]);

        // Nuances politiques des maires
        $nuancesMaires = Maire::enExercice()
            ->whereNotNull('nuance_politique')
            ->where('nuance_politique', '!=', '')
            ->select('nuance_politique as sigle', DB::raw('count(*) as effectif'))
            ->groupBy('nuance_politique')
            ->orderBy('effectif', 'desc')
            ->limit(15)
            ->get()
            ->map(fn($n) => [
                'sigle' => $n->sigle,
                'nom' => $this->getNuanceLibelle($n->sigle),
                'effectif' => $n->effectif,
            ]);

        $groupes = [
            'deputes' => $groupesDeputes,
            'senateurs' => $groupesSenateurs,
            'maires' => $nuancesMaires,
        ];

        // ========================================================================
        // STATS GLOBALES (résumé)
        // ========================================================================
        $totaux = [
            'elus_total' => $deputesActifs + $senateursActifs + $mairesActifs,
            'femmes_total' => $pariteDeputes['femmes'] + $pariteSenateurs['femmes'] + $pariteMaires['femmes'],
            'hommes_total' => $pariteDeputes['hommes'] + $pariteSenateurs['hommes'] + $pariteMaires['hommes'],
        ];
        $totaux['pct_femmes_global'] = $totaux['elus_total'] > 0
            ? round(($totaux['femmes_total'] / $totaux['elus_total']) * 100, 1)
            : 0;

        return [
            'effectifs' => $effectifs,
            'ages' => $ages,
            'parite' => $parite,
            'professions' => $professions,
            'groupes' => $groupes,
            'totaux' => $totaux,
        ];
    }

    private function calculateAgeStats($ages): array
    {
        if ($ages->isEmpty()) {
            return [
                'moyenne' => 0,
                'median' => 0,
                'min' => 0,
                'max' => 0,
                'distribution' => [
                    '< 30 ans' => 0,
                    '30-39 ans' => 0,
                    '40-49 ans' => 0,
                    '50-59 ans' => 0,
                    '60-69 ans' => 0,
                    '70+ ans' => 0,
                ],
            ];
        }

        return [
            'moyenne' => round($ages->avg(), 1),
            'median' => $ages->median() ?? 0,
            'min' => $ages->min() ?? 0,
            'max' => $ages->max() ?? 0,
            'distribution' => [
                '< 30 ans' => $ages->filter(fn($a) => $a < 30)->count(),
                '30-39 ans' => $ages->filter(fn($a) => $a >= 30 && $a < 40)->count(),
                '40-49 ans' => $ages->filter(fn($a) => $a >= 40 && $a < 50)->count(),
                '50-59 ans' => $ages->filter(fn($a) => $a >= 50 && $a < 60)->count(),
                '60-69 ans' => $ages->filter(fn($a) => $a >= 60 && $a < 70)->count(),
                '70+ ans' => $ages->filter(fn($a) => $a >= 70)->count(),
            ],
        ];
    }

    private function getNuanceLibelle(string $code): string
    {
        return match($code) {
            'LDVG' => 'Divers gauche',
            'LDVD' => 'Divers droite',
            'LDVC' => 'Divers centre',
            'LSOC' => 'Socialiste',
            'LLR' => 'Les Républicains',
            'LREM', 'LRE' => 'Renaissance',
            'LRN' => 'Rassemblement National',
            'LECO', 'LVEC' => 'Écologiste',
            'LCOM' => 'Communiste',
            'LUDI', 'LUC' => 'UDI / Centriste',
            'LDIV' => 'Divers',
            'LEXG' => 'Extrême gauche',
            'LEXT' => 'Extrême droite',
            'LMDM' => 'Modem',
            'LFI' => 'La France Insoumise',
            'LUD' => 'Union de la Droite',
            'LUG' => 'Union de la Gauche',
            default => $code,
        };
    }
}
