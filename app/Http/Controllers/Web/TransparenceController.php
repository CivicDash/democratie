<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AffaireJudiciaire;
use App\Models\AffaireSource;
use App\Models\StatsAffaireJudiciaire;
use Inertia\Inertia;
use Inertia\Response;

class TransparenceController extends Controller
{
    public function affairesJudiciaires(): Response
    {
        $statsGlobal = StatsAffaireJudiciaire::global()->first();

        $statsParParti = StatsAffaireJudiciaire::parParti()
            ->get()
            ->mapWithKeys(fn ($s) => [$s->scope_value => $s->data]);

        $statsParMandat = StatsAffaireJudiciaire::parTypeMandat()
            ->get()
            ->mapWithKeys(fn ($s) => [$s->scope_value => $s->data]);

        $dernieresValidees = AffaireJudiciaire::publiques()
            ->with('sources')
            ->latest('valide_at')
            ->limit(10)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'titre' => $a->titre,
                'nom' => $a->nom,
                'prenom' => $a->prenom,
                'parti_politique' => $a->parti_politique,
                'fonction_au_moment' => $a->fonction_au_moment,
                'type_affaire' => $a->type_affaire,
                'type_affaire_libelle' => $a->type_affaire_libelle,
                'statut_judiciaire' => $a->statut_judiciaire,
                'statut_libelle' => $a->statut_judiciaire_libelle,
                'statut_couleur' => $a->statut_judiciaire_couleur,
                'peine_resume' => $a->peine_resume,
                'valide_at' => $a->valide_at?->format('d/m/Y'),
                'sources_count' => $a->sources->count(),
            ]);

        return Inertia::render('Transparence/AffairesJudiciaires', [
            'stats_global' => $statsGlobal?->data,
            'stats_par_parti' => $statsParParti,
            'stats_par_mandat' => $statsParMandat,
            'dernieres_validees' => $dernieresValidees,
        ]);
    }

    public function notreDemarche(): Response
    {
        $totalValidees = AffaireJudiciaire::publiques()->count();
        $totalRejetees = AffaireJudiciaire::where('statut_validation', 'rejete')->count();
        $totalDetectees = AffaireJudiciaire::count();

        return Inertia::render('Transparence/NotreDemarche', [
            'stats' => [
                'total_validees' => $totalValidees,
                'total_rejetees' => $totalRejetees,
                'taux_rejet_pct' => $totalDetectees > 0
                    ? round(($totalRejetees / $totalDetectees) * 100, 1)
                    : 0,
                'total_sources' => AffaireSource::whereHas('affaire', fn ($q) => $q->publiques())->count(),
                'derniere_validation' => AffaireJudiciaire::publiques()->max('valide_at'),
            ],
        ]);
    }
}
