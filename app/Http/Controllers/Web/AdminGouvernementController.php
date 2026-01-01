<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Gouvernement;
use App\Models\Ministere;
use App\Models\Ministre;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminGouvernementController extends Controller
{
    /**
     * Liste des gouvernements
     */
    public function index()
    {
        $gouvernements = Gouvernement::withCount('ministres')
            ->orderByDesc('date_debut')
            ->get()
            ->map(fn($g) => [
                ...$g->toArray(),
                'ministres_count' => $g->ministres_count,
                'duree' => $this->calculateDuree($g->date_debut, $g->date_fin),
            ]);

        $stats = [
            'total' => Gouvernement::count(),
            'actif' => Gouvernement::where('actif', true)->count(),
            'total_ministres' => Ministre::where('actif', true)->count(),
        ];

        return Inertia::render('Admin/Gouvernement/Index', [
            'gouvernements' => $gouvernements,
            'stats' => $stats,
        ]);
    }

    /**
     * Formulaire de création d'un gouvernement
     */
    public function create()
    {
        return Inertia::render('Admin/Gouvernement/Create');
    }

    /**
     * Enregistrer un nouveau gouvernement
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'premier_ministre' => 'required|string|max:255',
            'president' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'actif' => 'boolean',
        ]);

        // Si actif, désactiver les autres
        if ($validated['actif'] ?? false) {
            Gouvernement::where('actif', true)->update(['actif' => false]);
        }

        $gouvernement = Gouvernement::create([
            ...$validated,
            'slug' => Str::slug($validated['nom']),
        ]);

        return redirect()
            ->route('admin.gouvernement.show', $gouvernement)
            ->with('success', 'Gouvernement créé avec succès');
    }

    /**
     * Afficher un gouvernement et ses ministres
     */
    public function show(Gouvernement $gouvernement)
    {
        $gouvernement->load(['ministres' => function ($q) {
            $q->orderBy('type_fonction')
              ->orderBy('created_at');
        }, 'ministres.ministere']);

        $ministresParType = [
            'premier_ministre' => $gouvernement->ministres->where('type_fonction', 'premier_ministre')->values(),
            'ministre' => $gouvernement->ministres->where('type_fonction', 'ministre')->values(),
            'ministre_delegue' => $gouvernement->ministres->where('type_fonction', 'ministre_delegue')->values(),
            'secretaire_etat' => $gouvernement->ministres->where('type_fonction', 'secretaire_etat')->values(),
        ];

        $ministeres = Ministere::orderBy('nom')->get();

        return Inertia::render('Admin/Gouvernement/Show', [
            'gouvernement' => $gouvernement,
            'ministresParType' => $ministresParType,
            'ministeres' => $ministeres,
        ]);
    }

    /**
     * Mettre à jour un gouvernement
     */
    public function update(Request $request, Gouvernement $gouvernement)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'premier_ministre' => 'required|string|max:255',
            'president' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'actif' => 'boolean',
        ]);

        // Si actif, désactiver les autres
        if (($validated['actif'] ?? false) && !$gouvernement->actif) {
            Gouvernement::where('actif', true)->update(['actif' => false]);
        }

        $gouvernement->update([
            ...$validated,
            'slug' => Str::slug($validated['nom']),
        ]);

        return back()->with('success', 'Gouvernement mis à jour');
    }

    /**
     * Supprimer un gouvernement
     */
    public function destroy(Gouvernement $gouvernement)
    {
        $gouvernement->delete();

        return redirect()
            ->route('admin.gouvernement.index')
            ->with('success', 'Gouvernement supprimé');
    }

    /**
     * Ajouter un ministre au gouvernement
     */
    public function addMinistre(Request $request, Gouvernement $gouvernement)
    {
        $validated = $request->validate([
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'fonction' => 'required|string|max:500',
            'type_fonction' => 'required|in:premier_ministre,ministre,ministre_delegue,secretaire_etat',
            'ministere_id' => 'nullable|exists:ministeres,id',
            'parti_politique' => 'nullable|string|max:255',
            'photo_url' => 'nullable|url|max:500',
            'sexe' => 'nullable|in:M,F',
        ]);

        $ministre = $gouvernement->ministres()->create([
            ...$validated,
            'slug' => Str::slug($validated['prenom'] . '-' . $validated['nom']),
            'date_debut' => $gouvernement->date_debut,
            'actif' => true,
        ]);

        return back()->with('success', 'Ministre ajouté : ' . $ministre->nom_complet);
    }

    /**
     * Mettre à jour un ministre
     */
    public function updateMinistre(Request $request, Ministre $ministre)
    {
        $validated = $request->validate([
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'fonction' => 'required|string|max:500',
            'type_fonction' => 'required|in:premier_ministre,ministre,ministre_delegue,secretaire_etat',
            'ministere_id' => 'nullable|exists:ministeres,id',
            'parti_politique' => 'nullable|string|max:255',
            'photo_url' => 'nullable|url|max:500',
            'sexe' => 'nullable|in:M,F',
            'actif' => 'boolean',
        ]);

        $ministre->update([
            ...$validated,
            'slug' => Str::slug($validated['prenom'] . '-' . $validated['nom']),
        ]);

        return back()->with('success', 'Ministre mis à jour');
    }

    /**
     * Supprimer un ministre
     */
    public function deleteMinistre(Ministre $ministre)
    {
        $nom = $ministre->nom_complet;
        $ministre->delete();

        return back()->with('success', 'Ministre supprimé : ' . $nom);
    }

    /**
     * Créer un ministère
     */
    public function createMinistere(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'sigle' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'couleur' => 'nullable|string|max:7',
        ]);

        $ministere = Ministere::create([
            ...$validated,
            'slug' => Str::slug($validated['nom']),
            'actif' => true,
        ]);

        return back()->with('success', 'Ministère créé : ' . $ministere->nom);
    }

    /**
     * Export JSON du gouvernement
     */
    public function exportJson(Gouvernement $gouvernement)
    {
        $gouvernement->load('ministres');

        $data = [
            'metadata' => [
                'source' => 'CivicDash Admin',
                'exported_at' => now()->toIso8601String(),
            ],
            'gouvernement' => [
                'nom' => $gouvernement->nom,
                'premier_ministre' => $gouvernement->premier_ministre,
                'president' => $gouvernement->president,
                'date_debut' => $gouvernement->date_debut?->format('Y-m-d'),
                'date_fin' => $gouvernement->date_fin?->format('Y-m-d'),
            ],
            'membres' => $gouvernement->ministres->map(fn($m) => [
                'prenom' => $m->prenom,
                'nom' => $m->nom,
                'fonction' => $m->fonction,
                'type' => $m->type_fonction,
                'ministere' => $m->ministere?->nom,
                'parti' => $m->parti_politique,
                'photo_url' => $m->photo_url,
            ])->toArray(),
        ];

        $filename = 'gouvernement_' . Str::slug($gouvernement->nom) . '.json';

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function calculateDuree($debut, $fin): string
    {
        if (!$debut) return '-';
        
        $start = \Carbon\Carbon::parse($debut);
        $end = $fin ? \Carbon\Carbon::parse($fin) : now();
        
        $diff = $start->diff($end);
        
        if ($diff->y > 0) {
            return $diff->y . ' an' . ($diff->y > 1 ? 's' : '') . ', ' . $diff->m . ' mois';
        }
        if ($diff->m > 0) {
            return $diff->m . ' mois, ' . $diff->d . ' jours';
        }
        return $diff->d . ' jours';
    }
}
