<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActeurAN;
use App\Models\Senateur;
use App\Models\Maire;
use App\Models\Ministre;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminElusController extends Controller
{
    /**
     * Dashboard de gestion des élus
     */
    public function index()
    {
        $stats = [
            'deputes' => [
                'total' => ActeurAN::count(),
                'actifs' => ActeurAN::where('en_mandat', true)->count(),
                'avec_photo' => ActeurAN::whereNotNull('photo_url')->count(),
                'sans_photo' => ActeurAN::whereNull('photo_url')->count(),
            ],
            'senateurs' => [
                'total' => Senateur::count(),
                'actifs' => Senateur::where('en_mandat', true)->count(),
                'avec_photo' => Senateur::whereNotNull('photo_url')->count(),
                'sans_photo' => Senateur::whereNull('photo_url')->count(),
            ],
            'maires' => [
                'total' => Maire::count(),
                'avec_email' => Maire::whereNotNull('email')->count(),
                'sans_email' => Maire::whereNull('email')->count(),
            ],
            'ministres' => [
                'total' => Ministre::count(),
                'actifs' => Ministre::where('actif', true)->count(),
            ],
        ];

        return Inertia::render('Admin/Elus/Index', [
            'stats' => $stats,
        ]);
    }

    /**
     * Liste des députés avec filtre et recherche
     */
    public function deputes(Request $request)
    {
        $query = ActeurAN::query()
            ->orderBy('nom');

        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'ilike', "%{$search}%")
                  ->orWhere('prenom', 'ilike', "%{$search}%")
                  ->orWhere('circonscription', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('groupe')) {
            $query->where('groupe_sigle', $request->groupe);
        }

        if ($request->filled('en_mandat')) {
            $query->where('en_mandat', $request->en_mandat === 'true');
        }

        if ($request->filled('sans_photo')) {
            $query->whereNull('photo_url');
        }

        $deputes = $query->paginate(50);

        // Groupes pour le filtre
        $groupes = ActeurAN::select('groupe_sigle')
            ->whereNotNull('groupe_sigle')
            ->distinct()
            ->orderBy('groupe_sigle')
            ->pluck('groupe_sigle');

        return Inertia::render('Admin/Elus/Deputes', [
            'deputes' => $deputes,
            'groupes' => $groupes,
            'filters' => $request->only(['search', 'groupe', 'en_mandat', 'sans_photo']),
        ]);
    }

    /**
     * Éditer un député
     */
    public function editDepute(ActeurAN $depute)
    {
        return Inertia::render('Admin/Elus/EditDepute', [
            'depute' => $depute,
        ]);
    }

    /**
     * Mettre à jour un député
     */
    public function updateDepute(Request $request, ActeurAN $depute)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'sexe' => 'nullable|in:M,F',
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string|max:255',
            'profession' => 'nullable|string|max:500',
            'photo_url' => 'nullable|url|max:500',
            'twitter' => 'nullable|string|max:100',
            'facebook' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'site_web' => 'nullable|url|max:500',
            'wikipedia_url' => 'nullable|url|max:500',
            'wikipedia_resume' => 'nullable|string|max:5000',
            'circonscription' => 'nullable|string|max:255',
            'groupe_sigle' => 'nullable|string|max:50',
            'en_mandat' => 'boolean',
        ]);

        $depute->update($validated);

        return back()->with('success', 'Député mis à jour : ' . $depute->nom_complet);
    }

    /**
     * Liste des sénateurs
     */
    public function senateurs(Request $request)
    {
        $query = Senateur::query()
            ->orderBy('nom');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'ilike', "%{$search}%")
                  ->orWhere('prenom', 'ilike', "%{$search}%")
                  ->orWhere('circonscription', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('groupe')) {
            $query->where('groupe_sigle', $request->groupe);
        }

        if ($request->filled('en_mandat')) {
            $query->where('en_mandat', $request->en_mandat === 'true');
        }

        $senateurs = $query->paginate(50);

        $groupes = Senateur::select('groupe_sigle')
            ->whereNotNull('groupe_sigle')
            ->distinct()
            ->orderBy('groupe_sigle')
            ->pluck('groupe_sigle');

        return Inertia::render('Admin/Elus/Senateurs', [
            'senateurs' => $senateurs,
            'groupes' => $groupes,
            'filters' => $request->only(['search', 'groupe', 'en_mandat']),
        ]);
    }

    /**
     * Éditer un sénateur
     */
    public function editSenateur(Senateur $senateur)
    {
        return Inertia::render('Admin/Elus/EditSenateur', [
            'senateur' => $senateur,
        ]);
    }

    /**
     * Mettre à jour un sénateur
     */
    public function updateSenateur(Request $request, Senateur $senateur)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'sexe' => 'nullable|in:M,F',
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string|max:255',
            'profession' => 'nullable|string|max:500',
            'photo_url' => 'nullable|url|max:500',
            'twitter' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'site_web' => 'nullable|url|max:500',
            'wikipedia_url' => 'nullable|url|max:500',
            'wikipedia_resume' => 'nullable|string|max:5000',
            'circonscription' => 'nullable|string|max:255',
            'groupe_sigle' => 'nullable|string|max:50',
            'en_mandat' => 'boolean',
        ]);

        $senateur->update($validated);

        return back()->with('success', 'Sénateur mis à jour : ' . $senateur->nom_complet);
    }

    /**
     * Liste des maires
     */
    public function maires(Request $request)
    {
        $query = Maire::query()
            ->orderBy('commune');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'ilike', "%{$search}%")
                  ->orWhere('prenom', 'ilike', "%{$search}%")
                  ->orWhere('commune', 'ilike', "%{$search}%")
                  ->orWhere('code_postal', 'like', "{$search}%");
            });
        }

        if ($request->filled('departement')) {
            $query->where('departement', $request->departement);
        }

        $maires = $query->paginate(50);

        $departements = Maire::select('departement')
            ->whereNotNull('departement')
            ->distinct()
            ->orderBy('departement')
            ->pluck('departement');

        return Inertia::render('Admin/Elus/Maires', [
            'maires' => $maires,
            'departements' => $departements,
            'filters' => $request->only(['search', 'departement']),
        ]);
    }

    /**
     * Éditer un maire
     */
    public function editMaire(Maire $maire)
    {
        return Inertia::render('Admin/Elus/EditMaire', [
            'maire' => $maire,
        ]);
    }

    /**
     * Mettre à jour un maire
     */
    public function updateMaire(Request $request, Maire $maire)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'sexe' => 'nullable|in:M,F',
            'date_naissance' => 'nullable|date',
            'profession' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:50',
            'site_web' => 'nullable|url|max:500',
            'commune' => 'required|string|max:255',
            'code_postal' => 'nullable|string|max:10',
            'departement' => 'nullable|string|max:100',
        ]);

        $maire->update($validated);

        return back()->with('success', 'Maire mis à jour : ' . $maire->nom_complet);
    }

    /**
     * Recherche globale d'élus (pour autocomplétion)
     */
    public function search(Request $request)
    {
        $search = $request->get('q', '');
        
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $results = [];

        // Députés
        $deputes = ActeurAN::where('nom', 'ilike', "%{$search}%")
            ->orWhere('prenom', 'ilike', "%{$search}%")
            ->limit(5)
            ->get()
            ->map(fn($d) => [
                'id' => $d->id,
                'type' => 'depute',
                'label' => $d->nom_complet . ' (Député)',
                'url' => route('admin.elus.deputes.edit', $d),
            ]);

        // Sénateurs
        $senateurs = Senateur::where('nom', 'ilike', "%{$search}%")
            ->orWhere('prenom', 'ilike', "%{$search}%")
            ->limit(5)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'type' => 'senateur',
                'label' => $s->nom_complet . ' (Sénateur)',
                'url' => route('admin.elus.senateurs.edit', $s),
            ]);

        // Ministres
        $ministres = Ministre::where('nom', 'ilike', "%{$search}%")
            ->orWhere('prenom', 'ilike', "%{$search}%")
            ->limit(5)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'type' => 'ministre',
                'label' => $m->nom_complet . ' (Ministre)',
                'url' => route('admin.gouvernement.show', $m->gouvernement_id),
            ]);

        return response()->json([
            ...$deputes->toArray(),
            ...$senateurs->toArray(),
            ...$ministres->toArray(),
        ]);
    }
}
