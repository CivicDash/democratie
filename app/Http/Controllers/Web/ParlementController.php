<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActeurAN;
use App\Models\Senateur;
use App\Models\OrganeAN;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ParlementController extends Controller
{
    /**
     * Page de comparaison Assemblée Nationale vs Sénat
     * 
     * GET /parlement/comparaison
     */
    public function comparaison(): Response
    {
        // Effectifs
        $effectifs = [
            'deputes_total' => 577,
            'deputes_actifs' => ActeurAN::whereHas('mandats', function($q) {
                $q->where('type_organe', 'ASSEMBLEE')
                  ->whereNull('date_fin');
            })->count(),
            'senateurs_total' => 348,
            'senateurs_actifs' => Senateur::actifs()->count(),
        ];

        // Répartition par âge
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

        $ages = [
            'deputes' => [
                'moyenne' => $ageDeputes->avg() ? round($ageDeputes->avg(), 1) : 0,
                'median' => $ageDeputes->median() ?? 0,
                'min' => $ageDeputes->min() ?? 0,
                'max' => $ageDeputes->max() ?? 0,
                'distribution' => [
                    '< 30 ans' => $ageDeputes->filter(fn($a) => $a < 30)->count(),
                    '30-39 ans' => $ageDeputes->filter(fn($a) => $a >= 30 && $a < 40)->count(),
                    '40-49 ans' => $ageDeputes->filter(fn($a) => $a >= 40 && $a < 50)->count(),
                    '50-59 ans' => $ageDeputes->filter(fn($a) => $a >= 50 && $a < 60)->count(),
                    '60-69 ans' => $ageDeputes->filter(fn($a) => $a >= 60 && $a < 70)->count(),
                    '70+ ans' => $ageDeputes->filter(fn($a) => $a >= 70)->count(),
                ],
            ],
            'senateurs' => [
                'moyenne' => $ageSenateurs->avg() ? round($ageSenateurs->avg(), 1) : 0,
                'median' => $ageSenateurs->median() ?? 0,
                'min' => $ageSenateurs->min() ?? 0,
                'max' => $ageSenateurs->max() ?? 0,
                'distribution' => [
                    '< 30 ans' => $ageSenateurs->filter(fn($a) => $a < 30)->count(),
                    '30-39 ans' => $ageSenateurs->filter(fn($a) => $a >= 30 && $a < 40)->count(),
                    '40-49 ans' => $ageSenateurs->filter(fn($a) => $a >= 40 && $a < 50)->count(),
                    '50-59 ans' => $ageSenateurs->filter(fn($a) => $a >= 50 && $a < 60)->count(),
                    '60-69 ans' => $ageSenateurs->filter(fn($a) => $a >= 60 && $a < 70)->count(),
                    '70+ ans' => $ageSenateurs->filter(fn($a) => $a >= 70)->count(),
                ],
            ],
        ];

        // Parité Hommes/Femmes
        $parite = [
            'deputes' => [
                'hommes' => ActeurAN::whereHas('mandats', fn($q) => $q->where('type_organe', 'ASSEMBLEE')->whereNull('date_fin'))
                    ->where('civilite', 'M.')->count(),
                'femmes' => ActeurAN::whereHas('mandats', fn($q) => $q->where('type_organe', 'ASSEMBLEE')->whereNull('date_fin'))
                    ->where('civilite', 'Mme')->count(),
            ],
            'senateurs' => [
                'hommes' => Senateur::actifs()->where('civilite', 'M.')->count(),
                'femmes' => Senateur::actifs()->where('civilite', 'Mme')->count(),
            ],
        ];

        // Calcul pourcentages
        $parite['deputes']['pct_femmes'] = $effectifs['deputes_actifs'] > 0 
            ? round(($parite['deputes']['femmes'] / $effectifs['deputes_actifs']) * 100, 1) 
            : 0;
        $parite['senateurs']['pct_femmes'] = $effectifs['senateurs_actifs'] > 0 
            ? round(($parite['senateurs']['femmes'] / $effectifs['senateurs_actifs']) * 100, 1) 
            : 0;

        // Top 10 professions - Députés
        $professionsDeputes = ActeurAN::whereHas('mandats', fn($q) => $q->where('type_organe', 'ASSEMBLEE')->whereNull('date_fin'))
            ->whereNotNull('profession')
            ->select('profession', DB::raw('count(*) as count'))
            ->groupBy('profession')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'profession' => $p->profession,
                'count' => $p->count,
            ]);

        // Top 10 professions - Sénateurs
        $professionsSenateurs = Senateur::actifs()
            ->whereNotNull('description_profession')
            ->select('description_profession as profession', DB::raw('count(*) as count'))
            ->groupBy('description_profession')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'profession' => $p->profession,
                'count' => $p->count,
            ]);

        // Groupes politiques - Députés
        $groupesDeputes = OrganeAN::where('type_organe', 'GP')
            ->where('legislature', 17)
            ->withCount(['mandats' => function($q) {
                $q->where('type_organe', 'GP')
                  ->whereNull('date_fin');
            }])
            ->orderBy('mandats_count', 'desc')
            ->get()
            ->map(fn($g) => [
                'sigle' => $g->libelleAbrev,
                'nom' => $g->libelle,
                'effectif' => $g->mandats_count,
            ]);

        // Groupes politiques - Sénateurs (simple count par groupe)
        $groupesSenateurs = Senateur::actifs()
            ->whereNotNull('groupe_politique')
            ->select('groupe_politique as sigle', DB::raw('count(*) as effectif'))
            ->groupBy('groupe_politique')
            ->orderBy('effectif', 'desc')
            ->get()
            ->map(fn($g) => [
                'sigle' => $g->sigle,
                'nom' => $g->sigle, // Pas de libellé complet dans la table
                'effectif' => $g->effectif,
            ]);

        return Inertia::render('Parlement/Comparaison', [
            'effectifs' => $effectifs,
            'ages' => $ages,
            'parite' => $parite,
            'professions' => [
                'deputes' => $professionsDeputes,
                'senateurs' => $professionsSenateurs,
            ],
            'groupes' => [
                'deputes' => $groupesDeputes,
                'senateurs' => $groupesSenateurs,
            ],
        ]);
    }
}

