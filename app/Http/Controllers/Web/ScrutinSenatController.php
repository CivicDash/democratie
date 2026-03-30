<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ScrutinSenat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScrutinSenatController extends Controller
{
    /**
     * Liste des scrutins du Sénat
     */
    public function index(Request $request): Response
    {
        $query = ScrutinSenat::query();

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('intitule', 'ilike', "%{$search}%")
                    ->orWhere('intitule_complet', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('resultat')) {
            $query->where('resultat', $request->resultat);
        }

        if ($request->filled('session')) {
            $query->where('session_annee', $request->session);
        }

        // Tri et pagination
        $scrutins = $query
            ->orderByDesc('date_scrutin')
            ->orderByDesc('numero')
            ->paginate(30)
            ->withQueryString();

        // Statistiques
        $stats = [
            'total' => ScrutinSenat::count(),
            'adoptes' => ScrutinSenat::where('resultat', 'Adopté')->count(),
            'rejetes' => ScrutinSenat::where('resultat', 'Rejeté')->count(),
        ];

        // Sessions disponibles pour le filtre
        $sessions = ScrutinSenat::select('session_annee')
            ->distinct()
            ->orderByDesc('session_annee')
            ->pluck('session_annee');

        return Inertia::render('Legislation/ScrutinsSenat/Index', [
            'scrutins' => $scrutins,
            'stats' => $stats,
            'sessions' => $sessions,
            'filters' => $request->only(['search', 'resultat', 'session']),
        ]);
    }

    /**
     * Détail d'un scrutin du Sénat
     */
    public function show(int $id): Response
    {
        $scrutin = ScrutinSenat::with(['votes.senateur'])->findOrFail($id);

        // Grouper les votes par position
        $votesParPosition = $scrutin->votes->groupBy('position')->map(function ($votes) {
            return $votes->map(function ($vote) {
                return [
                    'senateur' => $vote->senateur ? [
                        'matricule' => $vote->senateur->matricule,
                        'nom' => $vote->senateur->nom,
                        'prenom' => $vote->senateur->prenom,
                        'groupe' => $vote->senateur->groupe_politique,
                        'photo_url' => $vote->senateur->photo_url,
                    ] : null,
                    'position' => $vote->position,
                ];
            })->filter(fn ($v) => $v['senateur'] !== null);
        });

        return Inertia::render('Legislation/ScrutinsSenat/Show', [
            'scrutin' => $scrutin,
            'votesParPosition' => $votesParPosition,
        ]);
    }
}
