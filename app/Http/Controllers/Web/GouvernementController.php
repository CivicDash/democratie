<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Gouvernement;
use App\Models\Ministere;
use App\Models\Ministre;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GouvernementController extends Controller
{
    /**
     * Page principale du gouvernement
     */
    public function index(): Response
    {
        // Gouvernement actuel
        $gouvernement = Gouvernement::actuel();

        if (!$gouvernement) {
            return Inertia::render('Gouvernement/Index', [
                'gouvernement' => null,
                'ministeres' => [],
                'ministres' => [],
                'stats' => null,
            ]);
        }

        // Ministères avec leurs ministres
        $ministeres = Ministere::where('gouvernement_id', $gouvernement->id)
            ->where('actif', true)
            ->orderBy('ordre')
            ->with(['ministres' => fn($q) => $q->where('actif', true)])
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'nom' => $m->nom,
                'sigle' => $m->sigle,
                'type' => $m->type,
                'type_libelle' => $m->type_libelle,
                'couleur' => $m->couleur,
                'ordre' => $m->ordre,
                'ministre' => $m->ministres->first() ? [
                    'id' => $m->ministres->first()->id,
                    'nom_complet' => $m->ministres->first()->nom_complet,
                    'fonction' => $m->ministres->first()->fonction,
                    'photo_url' => $m->ministres->first()->photo_url,
                    'parti' => $m->ministres->first()->parti_politique,
                ] : null,
            ]);

        // Tous les ministres pour la vue liste
        $ministres = Ministre::where('gouvernement_id', $gouvernement->id)
            ->where('actif', true)
            ->orderByRaw("CASE type_fonction 
                WHEN 'premier_ministre' THEN 1 
                WHEN 'ministre' THEN 2 
                WHEN 'ministre_delegue' THEN 3 
                WHEN 'secretaire_etat' THEN 4 
                ELSE 5 END")
            ->with('ministere')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'prenom' => $m->prenom,
                'nom' => $m->nom,
                'nom_complet' => $m->nom_complet,
                'fonction' => $m->fonction,
                'type_fonction' => $m->type_fonction,
                'type_fonction_libelle' => $m->type_fonction_libelle,
                'ministere' => $m->ministere?->nom,
                'ministere_sigle' => $m->ministere?->sigle,
                'parti' => $m->parti_politique,
                'photo_url' => $m->photo_url,
                'date_debut' => $m->date_debut?->format('d/m/Y'),
            ]);

        // Statistiques
        $stats = [
            'nb_ministres' => $ministres->where('type_fonction', 'ministre')->count(),
            'nb_ministres_delegues' => $ministres->where('type_fonction', 'ministre_delegue')->count(),
            'nb_secretaires_etat' => $ministres->where('type_fonction', 'secretaire_etat')->count(),
            'total' => $ministres->count(),
            'duree' => $gouvernement->duree,
            'partis' => $ministres->pluck('parti')->filter()->countBy()->sortDesc()->take(5)->toArray(),
        ];

        return Inertia::render('Gouvernement/Index', [
            'gouvernement' => [
                'id' => $gouvernement->id,
                'nom' => $gouvernement->nom,
                'premier_ministre' => $gouvernement->premier_ministre,
                'president' => $gouvernement->president,
                'date_debut' => $gouvernement->date_debut->format('d/m/Y'),
                'date_debut_iso' => $gouvernement->date_debut->toISOString(),
                'duree' => $gouvernement->duree,
                'numero' => $gouvernement->numero,
                'legislature' => $gouvernement->legislature,
            ],
            'ministeres' => $ministeres,
            'ministres' => $ministres,
            'stats' => $stats,
        ]);
    }

    /**
     * Fiche d'un ministre
     */
    public function showMinistre(int $id): Response
    {
        $ministre = Ministre::with(['ministere', 'gouvernement'])->findOrFail($id);

        return Inertia::render('Gouvernement/Ministre', [
            'ministre' => [
                'id' => $ministre->id,
                'prenom' => $ministre->prenom,
                'nom' => $ministre->nom,
                'nom_complet' => $ministre->nom_complet,
                'fonction' => $ministre->fonction,
                'type_fonction_libelle' => $ministre->type_fonction_libelle,
                'ministere' => $ministre->ministere?->nom,
                'parti' => $ministre->parti_politique,
                'photo_url' => $ministre->photo_url,
                'date_debut' => $ministre->date_debut?->format('d/m/Y'),
                'duree' => $ministre->duree_fonction,
                'age' => $ministre->age,
                'profession' => $ministre->profession,
                'twitter' => $ministre->twitter,
                'wikipedia_url' => $ministre->wikipedia_url,
            ],
        ]);
    }

    /**
     * Historique des gouvernements
     */
    public function historique(): Response
    {
        $gouvernements = Gouvernement::orderByDesc('date_debut')
            ->withCount(['ministres', 'ministeres'])
            ->get()
            ->map(fn($g) => [
                'id' => $g->id,
                'nom' => $g->nom,
                'premier_ministre' => $g->premier_ministre,
                'date_debut' => $g->date_debut->format('d/m/Y'),
                'date_fin' => $g->date_fin?->format('d/m/Y'),
                'duree' => $g->duree,
                'actif' => $g->actif,
                'nb_ministres' => $g->ministres_count,
            ]);

        return Inertia::render('Gouvernement/Historique', [
            'gouvernements' => $gouvernements,
        ]);
    }
}
