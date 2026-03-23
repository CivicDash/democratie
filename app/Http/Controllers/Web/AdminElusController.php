<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActeurAN;
use App\Models\Senateur;
use App\Models\Maire;
use App\Models\Ministre;
use App\Models\PersonnePolitique;
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
                // Utilise le scope deputes() qui vérifie les mandats actifs
                'actifs' => ActeurAN::deputes()->count(),
                'avec_photo' => ActeurAN::whereNotNull('photo_wikipedia_url')->count(),
                'sans_photo' => ActeurAN::whereNull('photo_wikipedia_url')->count(),
            ],
            'senateurs' => [
                'total' => Senateur::count(),
                // Sénateurs en exercice (etat = 'En exercice')
                'actifs' => Senateur::where('etat', 'En exercice')->count(),
                'avec_photo' => Senateur::whereNotNull('photo_wikipedia_url')->count(),
                'sans_photo' => Senateur::whereNull('photo_wikipedia_url')->count(),
            ],
            'maires' => [
                'total' => Maire::count(),
                'avec_email' => Maire::whereNotNull('email')->count(),
                'sans_email' => Maire::whereNull('email')->count(),
            ],
            'ministres' => [
                'total' => PersonnePolitique::count(),
                'actifs' => PersonnePolitique::whereHas('postes', fn($q) => $q->whereNull('date_fin'))->count(),
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
                  ->orWhere('prenom', 'ilike', "%{$search}%");
            });
        }

        // Filtre par députés actifs uniquement
        if ($request->filled('actifs_only') && $request->actifs_only === 'true') {
            $query->deputes(); // Scope qui filtre les mandats actifs
        }

        if ($request->filled('sans_photo')) {
            $query->whereNull('photo_wikipedia_url');
        }

        $deputes = $query->paginate(50);

        return Inertia::render('Admin/Elus/Deputes', [
            'deputes' => $deputes,
            'filters' => $request->only(['search', 'actifs_only', 'sans_photo']),
        ]);
    }

    /**
     * Éditer un député
     */
    public function editDepute(ActeurAN $acteurAn)
    {
        return Inertia::render('Admin/Elus/EditDepute', [
            'depute' => $acteurAn,
        ]);
    }

    /**
     * Mettre à jour un député
     */
    public function updateDepute(Request $request, ActeurAN $acteurAn)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'civilite' => 'nullable|in:M.,Mme',
            'date_naissance' => 'nullable|date',
            'ville_naissance' => 'nullable|string|max:255',
            'profession' => 'nullable|string|max:500',
            'twitter_url' => 'nullable|url|max:500',
            'facebook_url' => 'nullable|url|max:500',
            'linkedin_url' => 'nullable|url|max:500',
            'instagram_url' => 'nullable|url|max:500',
            'wikipedia_url' => 'nullable|url|max:500',
        ]);

        $acteurAn->update($validated);

        return back()->with('success', 'Député mis à jour : ' . $acteurAn->nom_complet);
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
            $query->where('groupe_politique_code', $request->groupe);
        }

        if ($request->filled('actifs_only') && $request->actifs_only === 'true') {
            $query->where('etat', 'En exercice');
        }

        $senateurs = $query->paginate(50);

        $groupes = Senateur::select('groupe_politique_code')
            ->whereNotNull('groupe_politique_code')
            ->distinct()
            ->orderBy('groupe_politique_code')
            ->pluck('groupe_politique_code');

        return Inertia::render('Admin/Elus/Senateurs', [
            'senateurs' => $senateurs,
            'groupes' => $groupes,
            'filters' => $request->only(['search', 'groupe', 'actifs_only']),
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
            'civilite' => 'nullable|in:M.,Mme',
            'date_naissance' => 'nullable|date',
            'profession' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
            'wikipedia_url' => 'nullable|url|max:500',
            'circonscription' => 'nullable|string|max:255',
            'groupe_politique_code' => 'nullable|string|max:50',
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
            ->orderBy('nom_commune');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'ilike', "%{$search}%")
                  ->orWhere('prenom', 'ilike', "%{$search}%")
                  ->orWhere('nom_commune', 'ilike', "%{$search}%")
                  ->orWhere('code_commune', 'like', "{$search}%");
            });
        }

        if ($request->filled('departement')) {
            $query->where('code_departement', $request->departement);
        }

        $maires = $query->paginate(50);

        $departements = Maire::select('code_departement', 'nom_departement')
            ->whereNotNull('code_departement')
            ->distinct()
            ->orderBy('code_departement')
            ->get()
            ->mapWithKeys(fn($d) => [$d->code_departement => $d->nom_departement ?? $d->code_departement]);

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
            'civilite' => 'nullable|in:M.,Mme',
            'date_naissance' => 'nullable|date',
            'profession' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:50',
            'site_web' => 'nullable|url|max:500',
            'nom_commune' => 'required|string|max:255',
            'code_commune' => 'nullable|string|max:10',
            'code_departement' => 'nullable|string|max:10',
        ]);

        $maire->update($validated);

        return back()->with('success', 'Maire mis à jour : ' . $maire->nom_complet);
    }

    /**
     * Liste des ministres (personnes politiques)
     */
    public function ministres(Request $request)
    {
        $query = PersonnePolitique::query()
            ->withCount('postes')
            ->with(['postes' => function ($q) {
                $q->latest('date_debut')->limit(1);
            }])
            ->orderBy('nom');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'ilike', "%{$search}%")
                  ->orWhere('prenom', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('parti')) {
            $query->where('parti_politique', $request->parti);
        }

        if ($request->filled('actif')) {
            $query->whereHas('postes', function ($q) {
                $q->whereNull('date_fin');
            });
        }

        $ministres = $query->paginate(50)->through(function ($personne) {
            $dernierPoste = $personne->postes->first();
            return [
                'id' => $personne->id,
                'nom' => $personne->nom,
                'prenom' => $personne->prenom,
                'civilite' => $personne->civilite,
                'nom_complet' => $personne->nom_complet,
                'slug' => $personne->slug,
                'parti_politique' => $personne->parti_politique,
                'photo_url' => $personne->photo_url,
                'nb_postes' => $personne->postes_count,
                'dernier_poste' => $dernierPoste?->fonction,
                'actif' => $dernierPoste && !$dernierPoste->date_fin,
            ];
        });

        $partis = PersonnePolitique::select('parti_politique')
            ->whereNotNull('parti_politique')
            ->where('parti_politique', '!=', '')
            ->distinct()
            ->orderBy('parti_politique')
            ->pluck('parti_politique');

        return Inertia::render('Admin/Elus/Ministres', [
            'ministres' => $ministres,
            'partis' => $partis,
            'filters' => $request->only(['search', 'parti', 'actif']),
        ]);
    }

    /**
     * Éditer un ministre (personne politique)
     */
    public function editMinistre(PersonnePolitique $personne)
    {
        $personne->load(['postes' => function ($q) {
            $q->with(['gouvernement', 'ministere'])->orderByDesc('date_debut');
        }]);

        return Inertia::render('Admin/Elus/EditMinistre', [
            'personne' => $personne,
        ]);
    }

    /**
     * Mettre à jour un ministre (personne politique)
     */
    public function updateMinistre(Request $request, PersonnePolitique $personne)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'civilite' => 'nullable|in:M.,Mme',
            'date_naissance' => 'nullable|date',
            'profession' => 'nullable|string|max:500',
            'parti_politique' => 'nullable|string|max:255',
            'photo_url' => 'nullable|url|max:1000',
            'wikipedia_url' => 'nullable|url|max:500',
        ]);

        $validated['slug'] = \Illuminate\Support\Str::slug($validated['prenom'] . '-' . $validated['nom']);
        
        $personne->update($validated);

        return back()->with('success', 'Ministre mis à jour : ' . $personne->nom_complet);
    }

    /**
     * Recherche globale d'élus (pour autocomplétion)
     */
    public function search(Request $request)
    {
        $search = $request->input('q', '');
        
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
