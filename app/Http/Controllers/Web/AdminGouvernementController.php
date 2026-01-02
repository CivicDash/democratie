<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Gouvernement;
use App\Models\Ministere;
use App\Models\PersonnePolitique;
use App\Models\PosteMinisteriel;
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
        $gouvernements = Gouvernement::withCount('postes')
            ->orderByDesc('date_debut')
            ->get()
            ->map(fn($g) => [
                ...$g->toArray(),
                'postes_count' => $g->postes_count,
                'duree' => $this->calculateDuree($g->date_debut, $g->date_fin),
            ]);

        $stats = [
            'total' => Gouvernement::count(),
            'actif' => Gouvernement::where('actif', true)->count(),
            'total_ministres' => PosteMinisteriel::where('actif', true)->count(),
            'personnes' => PersonnePolitique::count(),
            'ministeres' => Ministere::where('actif', true)->count(),
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
            'numero' => 'nullable|integer',
            'suffixe' => 'nullable|string|max:10',
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
            'slug' => Str::slug($validated['nom'] . ($validated['suffixe'] ?? '')),
        ]);

        return redirect()
            ->route('admin.gouvernement.show', $gouvernement)
            ->with('success', 'Gouvernement créé avec succès');
    }

    /**
     * Afficher un gouvernement et ses postes
     */
    public function show(Gouvernement $gouvernement)
    {
        // Charger les postes avec les personnes et ministères
        $gouvernement->load(['postes' => function ($q) {
            $q->with(['personne', 'ministere'])
              ->orderBy('ordre')
              ->orderBy('type_fonction')
              ->orderBy('date_debut');
        }]);

        // Grouper par type de fonction
        $postesParType = [
            'premier_ministre' => $gouvernement->postes->where('type_fonction', 'premier_ministre')->values(),
            'ministre_etat' => $gouvernement->postes->where('type_fonction', 'ministre_etat')->values(),
            'ministre' => $gouvernement->postes->where('type_fonction', 'ministre')->values(),
            'ministre_delegue' => $gouvernement->postes->where('type_fonction', 'ministre_delegue')->values(),
            'secretaire_etat' => $gouvernement->postes->where('type_fonction', 'secretaire_etat')->values(),
        ];

        // Liste des ministères pour le formulaire
        $ministeres = Ministere::orderBy('nom')->get();

        // Liste des personnes politiques pour l'autocomplétion
        $personnes = PersonnePolitique::orderBy('nom')
            ->orderBy('prenom')
            ->get(['id', 'prenom', 'nom', 'photo_url', 'parti_politique']);

        return Inertia::render('Admin/Gouvernement/Show', [
            'gouvernement' => $gouvernement,
            'postesParType' => $postesParType,
            'ministeres' => $ministeres,
            'personnes' => $personnes,
        ]);
    }

    /**
     * Mettre à jour un gouvernement
     */
    public function update(Request $request, Gouvernement $gouvernement)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'numero' => 'nullable|integer',
            'suffixe' => 'nullable|string|max:10',
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
            'slug' => Str::slug($validated['nom'] . ($validated['suffixe'] ?? '')),
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

    // =====================================================
    // PERSONNES POLITIQUES
    // =====================================================

    /**
     * Créer une nouvelle personne politique
     */
    public function storePersonne(Request $request)
    {
        $validated = $request->validate([
            'civilite' => 'nullable|in:M.,Mme',
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'date_naissance' => 'nullable|date',
            'profession' => 'nullable|string|max:500',
            'parti_politique' => 'nullable|string|max:255',
            'photo_url' => 'nullable|url|max:1000',
            'wikipedia_url' => 'nullable|url|max:500',
        ]);

        $personne = PersonnePolitique::create([
            ...$validated,
            'slug' => Str::slug($validated['prenom'] . '-' . $validated['nom']),
        ]);

        return back()->with('success', 'Personne créée : ' . $personne->nom_complet);
    }

    /**
     * Mettre à jour une personne politique
     */
    public function updatePersonne(Request $request, PersonnePolitique $personne)
    {
        $validated = $request->validate([
            'civilite' => 'nullable|in:M.,Mme',
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'date_naissance' => 'nullable|date',
            'profession' => 'nullable|string|max:500',
            'parti_politique' => 'nullable|string|max:255',
            'photo_url' => 'nullable|url|max:1000',
            'wikipedia_url' => 'nullable|url|max:500',
        ]);

        $personne->update([
            ...$validated,
            'slug' => Str::slug($validated['prenom'] . '-' . $validated['nom']),
        ]);

        return back()->with('success', 'Personne mise à jour');
    }

    // =====================================================
    // POSTES MINISTÉRIELS (Affectations)
    // =====================================================

    /**
     * Ajouter un poste ministériel (affectation)
     */
    public function addPoste(Request $request, Gouvernement $gouvernement)
    {
        // Déterminer si on crée une nouvelle personne
        $hasNouvellePersonne = $request->has('nouvelle_personne') 
            && is_array($request->input('nouvelle_personne'))
            && !empty($request->input('nouvelle_personne.prenom'))
            && !empty($request->input('nouvelle_personne.nom'));

        $rules = [
            // Infos du poste (toujours requis)
            'fonction' => 'required|string|max:500',
            'type_fonction' => 'required|in:premier_ministre,ministre_etat,ministre,ministre_delegue,secretaire_etat',
            'ministere_id' => 'nullable|exists:ministeres,id',
            'ordre' => 'nullable|integer',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after:date_debut',
        ];

        if ($hasNouvellePersonne) {
            // Validation pour nouvelle personne
            $rules['nouvelle_personne'] = 'required|array';
            $rules['nouvelle_personne.prenom'] = 'required|string|max:255';
            $rules['nouvelle_personne.nom'] = 'required|string|max:255';
            $rules['nouvelle_personne.civilite'] = 'nullable|in:M.,Mme';
            $rules['nouvelle_personne.parti_politique'] = 'nullable|string|max:255';
            $rules['nouvelle_personne.photo_url'] = 'nullable|url|max:1000';
        } else {
            // Validation pour personne existante
            $rules['personne_id'] = 'required|exists:personnes_politiques,id';
        }

        $validated = $request->validate($rules, [
            'personne_id.required' => 'Veuillez sélectionner une personne existante ou créer une nouvelle personne.',
            'personne_id.exists' => 'La personne sélectionnée n\'existe pas.',
            'fonction.required' => 'La fonction est obligatoire.',
            'nouvelle_personne.prenom.required' => 'Le prénom est obligatoire.',
            'nouvelle_personne.nom.required' => 'Le nom est obligatoire.',
        ]);

        // Créer ou récupérer la personne
        $personneId = $validated['personne_id'] ?? null;
        
        if ($hasNouvellePersonne) {
            $personne = PersonnePolitique::create([
                'prenom' => $validated['nouvelle_personne']['prenom'],
                'nom' => $validated['nouvelle_personne']['nom'],
                'civilite' => $validated['nouvelle_personne']['civilite'] ?? null,
                'parti_politique' => $validated['nouvelle_personne']['parti_politique'] ?? null,
                'photo_url' => $validated['nouvelle_personne']['photo_url'] ?? null,
                'slug' => Str::slug($validated['nouvelle_personne']['prenom'] . '-' . $validated['nouvelle_personne']['nom']),
            ]);
            $personneId = $personne->id;
        }

        $poste = PosteMinisteriel::create([
            'personne_id' => $personneId,
            'gouvernement_id' => $gouvernement->id,
            'ministere_id' => $validated['ministere_id'],
            'fonction' => $validated['fonction'],
            'type_fonction' => $validated['type_fonction'],
            'ordre' => $validated['ordre'] ?? 0,
            'date_debut' => $validated['date_debut'] ?? $gouvernement->date_debut,
            'date_fin' => $validated['date_fin'],
            'actif' => empty($validated['date_fin']),
        ]);

        $personne = PersonnePolitique::find($personneId);
        
        return back()->with('success', 'Poste ajouté : ' . $personne->nom_complet . ' - ' . $validated['fonction']);
    }

    /**
     * Mettre à jour un poste ministériel
     */
    public function updatePoste(Request $request, PosteMinisteriel $poste)
    {
        $validated = $request->validate([
            'fonction' => 'required|string|max:500',
            'type_fonction' => 'required|in:premier_ministre,ministre_etat,ministre,ministre_delegue,secretaire_etat',
            'ministere_id' => 'nullable|exists:ministeres,id',
            'ordre' => 'nullable|integer',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
            'actif' => 'nullable',
        ]);

        // Convertir les chaînes vides en null pour les dates
        $dateDebut = !empty($validated['date_debut']) ? $validated['date_debut'] : null;
        $dateFin = !empty($validated['date_fin']) ? $validated['date_fin'] : null;
        
        // Déterminer si actif (si pas de date_fin, alors actif)
        $actif = $validated['actif'] ?? ($dateFin === null);

        $poste->update([
            'fonction' => $validated['fonction'],
            'type_fonction' => $validated['type_fonction'],
            'ministere_id' => $validated['ministere_id'],
            'ordre' => $validated['ordre'] ?? 0,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'actif' => $actif,
        ]);

        return back()->with('success', 'Poste mis à jour : ' . $poste->personne?->nom_complet);
    }

    /**
     * Supprimer un poste ministériel
     */
    public function deletePoste(PosteMinisteriel $poste)
    {
        $nom = $poste->personne?->nom_complet ?? 'Inconnu';
        $poste->delete();

        return back()->with('success', 'Poste supprimé : ' . $nom);
    }

    /**
     * Terminer un poste (mettre date_fin = aujourd'hui)
     */
    public function endPoste(PosteMinisteriel $poste)
    {
        $poste->update([
            'date_fin' => now(),
            'actif' => false,
        ]);

        return back()->with('success', 'Poste terminé : ' . $poste->personne?->nom_complet);
    }

    // =====================================================
    // MINISTÈRES
    // =====================================================

    /**
     * Créer un ministère
     */
    public function storeMinistere(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'sigle' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'couleur' => 'nullable|string|max:7',
            'site_web' => 'nullable|url|max:500',
            'adresse' => 'nullable|string|max:500',
            'telephone' => 'nullable|string|max:50',
        ]);

        $ministere = Ministere::create([
            ...$validated,
            'slug' => Str::slug($validated['nom']),
            'actif' => true,
        ]);

        return back()->with('success', 'Ministère créé : ' . $ministere->nom);
    }

    /**
     * Mettre à jour un ministère
     */
    public function updateMinistere(Request $request, Ministere $ministere)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'sigle' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'couleur' => 'nullable|string|max:7',
            'site_web' => 'nullable|url|max:500',
            'adresse' => 'nullable|string|max:500',
            'telephone' => 'nullable|string|max:50',
            'actif' => 'boolean',
        ]);

        $ministere->update([
            ...$validated,
            'slug' => Str::slug($validated['nom']),
        ]);

        return back()->with('success', 'Ministère mis à jour');
    }

    /**
     * Liste des ministères
     */
    public function ministeres()
    {
        $ministeres = Ministere::withCount('postes')
            ->orderBy('nom')
            ->get();

        return Inertia::render('Admin/Gouvernement/Ministeres', [
            'ministeres' => $ministeres,
        ]);
    }

    /**
     * Liste des personnes politiques
     */
    public function personnes(Request $request)
    {
        $query = PersonnePolitique::with(['postes' => function ($q) {
            $q->with('gouvernement', 'ministere')
              ->orderByDesc('date_debut');
        }]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'ilike', "%{$search}%")
                  ->orWhere('prenom', 'ilike', "%{$search}%");
            });
        }

        $personnes = $query->orderBy('nom')
            ->orderBy('prenom')
            ->paginate(50);

        return Inertia::render('Admin/Gouvernement/Personnes', [
            'personnes' => $personnes,
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Export JSON du gouvernement
     */
    public function exportJson(Gouvernement $gouvernement)
    {
        $gouvernement->load(['postes' => function ($q) {
            $q->with(['personne', 'ministere']);
        }]);

        $data = [
            'metadata' => [
                'source' => 'CivicDash Admin',
                'exported_at' => now()->toIso8601String(),
            ],
            'gouvernement' => [
                'numero' => $gouvernement->numero,
                'nom' => $gouvernement->nom,
                'suffixe' => $gouvernement->suffixe,
                'premier_ministre' => $gouvernement->premier_ministre,
                'president' => $gouvernement->president,
                'date_debut' => $gouvernement->date_debut?->format('Y-m-d'),
                'date_fin' => $gouvernement->date_fin?->format('Y-m-d'),
            ],
            'postes' => $gouvernement->postes->map(fn($p) => [
                'personne' => [
                    'prenom' => $p->personne?->prenom,
                    'nom' => $p->personne?->nom,
                    'civilite' => $p->personne?->civilite,
                    'parti_politique' => $p->personne?->parti_politique,
                    'photo_url' => $p->personne?->photo,
                ],
                'fonction' => $p->fonction,
                'type' => $p->type_fonction,
                'ministere' => $p->ministere?->nom,
                'ordre' => $p->ordre,
                'date_debut' => $p->date_debut?->format('Y-m-d'),
                'date_fin' => $p->date_fin?->format('Y-m-d'),
                'actif' => $p->actif,
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
