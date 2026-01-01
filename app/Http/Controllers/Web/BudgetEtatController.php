<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BudgetMission;
use App\Models\BudgetProgramme;
use App\Models\BudgetMinistere;
use App\Models\BudgetAnnuel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetEtatController extends Controller
{
    /**
     * Page principale du budget de l'État
     */
    public function index(Request $request): Response
    {
        $annee = $request->input('annee', date('Y'));
        $vue = $request->input('vue', 'missions'); // missions, ministeres, evolution

        // Années disponibles
        $anneesDisponibles = BudgetAnnuel::orderBy('annee', 'desc')
            ->pluck('annee')
            ->toArray();

        if (empty($anneesDisponibles)) {
            $anneesDisponibles = range(date('Y'), 2020);
        }

        // Données budget annuel
        $budgetAnnuel = BudgetAnnuel::where('annee', $annee)->first();

        // Missions par crédits (top 15)
        $missions = BudgetMission::where('annee', $annee)
            ->orderByDesc('credits_cp')
            ->limit(20)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'code' => $m->code,
                'libelle' => $m->libelle,
                'credits_cp' => $m->credits_cp,
                'credits_cp_md' => $m->credits_cp_md,
                'credits_cp_formate' => $m->credits_cp_formate,
                'nb_programmes' => $m->nb_programmes,
                'couleur' => BudgetMission::getCouleurMission($m->code),
            ]);

        // Ministères par budget
        $ministeres = BudgetMinistere::where('annee', $annee)
            ->orderByDesc('budget_cp')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'code' => $m->code,
                'nom' => $m->nom,
                'sigle' => $m->sigle,
                'budget_cp' => $m->budget_cp,
                'budget_formate' => $m->budget_formate,
                'nb_programmes' => $m->nb_programmes,
                'couleur' => $m->couleur,
            ]);

        // Évolution historique
        $evolution = BudgetAnnuel::orderBy('annee')
            ->get()
            ->map(fn($b) => [
                'annee' => $b->annee,
                'recettes' => round(($b->recettes_nettes ?? 0) / 1_000_000_000, 1),
                'depenses' => round(($b->depenses_nettes ?? 0) / 1_000_000_000, 1),
                'deficit' => round(($b->deficit ?? 0) / 1_000_000_000, 1),
                'dette_pib' => $b->dette_pib_pct,
                'deficit_pib' => $b->deficit_pib_pct,
            ]);

        // Stats globales
        $totalCP = BudgetMission::where('annee', $annee)->sum('credits_cp');
        $nbMissions = BudgetMission::where('annee', $annee)->count();
        $nbProgrammes = BudgetProgramme::where('annee', $annee)->count();

        return Inertia::render('BudgetEtat/Index', [
            'annee' => (int) $annee,
            'vue' => $vue,
            'anneesDisponibles' => $anneesDisponibles,
            'budgetAnnuel' => $budgetAnnuel ? [
                'recettes' => $budgetAnnuel->recettes_formate,
                'depenses' => $budgetAnnuel->depenses_formate,
                'deficit' => $budgetAnnuel->deficit_formate,
                'dette' => $budgetAnnuel->dette_formate,
                'deficit_pib' => $budgetAnnuel->deficit_pib_pct,
                'dette_pib' => $budgetAnnuel->dette_pib_pct,
                'sante_indicateur' => $budgetAnnuel->sante_indicateur,
                'dette_indicateur' => $budgetAnnuel->dette_indicateur,
            ] : null,
            'missions' => $missions,
            'ministeres' => $ministeres,
            'evolution' => $evolution,
            'stats' => [
                'total_cp' => $totalCP,
                'total_cp_formate' => $this->formatMontant($totalCP),
                'nb_missions' => $nbMissions,
                'nb_programmes' => $nbProgrammes,
                'nb_ministeres' => $ministeres->count(),
            ],
        ]);
    }

    /**
     * Détail d'une mission budgétaire
     */
    public function showMission(Request $request, string $code): Response
    {
        $annee = $request->input('annee', date('Y'));

        $mission = BudgetMission::where('code', $code)
            ->where('annee', $annee)
            ->firstOrFail();

        $programmes = $mission->programmes()
            ->orderByDesc('credits_cp')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'libelle' => $p->libelle,
                'ministere' => $p->ministere,
                'credits_cp' => $p->credits_cp,
                'credits_cp_formate' => $p->credits_cp_formate,
                'evolution_pct' => $p->evolution_pct,
                'evolution_badge' => $p->evolution_badge,
            ]);

        return Inertia::render('BudgetEtat/Mission', [
            'mission' => [
                'id' => $mission->id,
                'code' => $mission->code,
                'libelle' => $mission->libelle,
                'annee' => $mission->annee,
                'credits_ae' => $mission->credits_ae_formate,
                'credits_cp' => $mission->credits_cp_formate,
                'nb_programmes' => $mission->nb_programmes,
                'couleur' => BudgetMission::getCouleurMission($mission->code),
            ],
            'programmes' => $programmes,
            'annee' => (int) $annee,
        ]);
    }

    /**
     * API : données pour graphiques
     */
    public function apiData(Request $request)
    {
        $annee = $request->input('annee', date('Y'));
        $type = $request->input('type', 'missions');

        if ($type === 'treemap') {
            return $this->getTreemapData($annee);
        }

        if ($type === 'evolution') {
            return BudgetAnnuel::orderBy('annee')->get();
        }

        return BudgetMission::where('annee', $annee)
            ->orderByDesc('credits_cp')
            ->get();
    }

    private function getTreemapData(int $annee): array
    {
        $missions = BudgetMission::where('annee', $annee)
            ->with('programmes')
            ->orderByDesc('credits_cp')
            ->get();

        return $missions->map(fn($m) => [
            'name' => $m->libelle,
            'value' => round($m->credits_cp / 1_000_000_000, 2),
            'color' => BudgetMission::getCouleurMission($m->code),
            'children' => $m->programmes->map(fn($p) => [
                'name' => $p->libelle,
                'value' => round($p->credits_cp / 1_000_000_000, 2),
            ])->toArray(),
        ])->toArray();
    }

    private function formatMontant(?float $montant): string
    {
        if ($montant === null) return 'N/A';
        
        if ($montant >= 1_000_000_000) {
            return number_format($montant / 1_000_000_000, 1, ',', ' ') . ' Md€';
        }
        return number_format($montant / 1_000_000, 0, ',', ' ') . ' M€';
    }
}
