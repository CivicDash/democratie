<?php

namespace App\Http\Controllers\Web\Commune;

use App\Http\Controllers\Controller;
use App\Models\CommuneEvenement;
use App\Models\CommunePage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CommuneEvenementController extends Controller
{
    public function index(string $codeInsee): Response
    {
        $page = CommunePage::with('ville')->where('code_insee', $codeInsee)->firstOrFail();

        if (! $page->evenements_actifs) {
            abort(404, 'Evenements non actives pour cette commune.');
        }

        $categorie = request('categorie');
        $periode = request('periode', 'a_venir');

        $query = $page->evenements()->publies();

        if ($categorie) {
            $query->parCategorie($categorie);
        }

        if ($periode === 'passes') {
            $query->passes()->orderByDesc('date_debut');
        } else {
            $query->prochains();
        }

        $evenements = $query->paginate(12)->through(fn ($e) => [
            'id' => $e->id,
            'titre' => $e->titre,
            'slug' => $e->slug,
            'description' => Str::limit(strip_tags($e->description ?? ''), 150),
            'categorie' => $e->categorie,
            'categorie_label' => $e->categorie_label,
            'date_debut' => $e->date_debut->format('d/m/Y H:i'),
            'date_fin' => $e->date_fin?->format('d/m/Y H:i'),
            'journee_entiere' => $e->journee_entiere,
            'lieu_nom' => $e->lieu_nom,
            'image_url' => $e->image_url,
            'inscription_requise' => $e->inscription_requise,
            'places_restantes' => $e->places_restantes,
            'est_complet' => $e->est_complet,
            'annule' => $e->annule,
        ]);

        return Inertia::render('Commune/Evenements', [
            'ville' => ['nom' => $page->ville->nom, 'code_insee' => $codeInsee, 'slug' => $page->ville->slug],
            'page' => ['statut' => $page->statut, 'couleur_primaire' => $page->couleur_primaire],
            'evenements' => $evenements,
            'categories' => CommuneEvenement::CATEGORIES,
            'categorie_active' => $categorie,
            'periode' => $periode,
            'seo' => [
                'title' => "Evenements - {$page->ville->nom} - Hub Citoyen",
                'description' => "Agenda des evenements municipaux a {$page->ville->nom} ({$page->ville->departement_nom}).",
                'image' => $page->image_couverture_url ?? $page->ville->blason_url,
                'url' => url()->current(),
                'type' => 'website',
            ],
        ]);
    }

    public function show(string $codeInsee, string $slug): Response
    {
        $page = CommunePage::with('ville')->where('code_insee', $codeInsee)->firstOrFail();
        $evenement = $page->evenements()->publies()->where('slug', $slug)->firstOrFail();

        $estInscrit = false;
        $inscription = null;
        if ($user = auth()->user()) {
            $inscription = $evenement->inscriptions()
                ->where('user_id', $user->id)
                ->where('statut', '!=', 'annule')
                ->first();
            $estInscrit = (bool) $inscription;
        }

        $descSeo = $evenement->description
            ? \Illuminate\Support\Str::limit(strip_tags($evenement->description), 160)
            : "Evenement a {$page->ville->nom} le {$evenement->date_debut->format('d/m/Y')}.";

        return Inertia::render('Commune/EvenementShow', [
            'ville' => ['nom' => $page->ville->nom, 'code_insee' => $codeInsee, 'slug' => $page->ville->slug],
            'seo' => [
                'title' => "{$evenement->titre} - {$page->ville->nom} - Hub Citoyen",
                'description' => $descSeo,
                'image' => $evenement->image_url ?? $page->image_couverture_url ?? $page->ville->blason_url,
                'url' => url()->current(),
                'type' => 'event',
            ],
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
                'slug' => $evenement->slug,
                'description' => $evenement->description,
                'categorie' => $evenement->categorie,
                'categorie_label' => $evenement->categorie_label,
                'date_debut' => $evenement->date_debut->format('d/m/Y H:i'),
                'date_fin' => $evenement->date_fin?->format('d/m/Y H:i'),
                'journee_entiere' => $evenement->journee_entiere,
                'lieu_nom' => $evenement->lieu_nom,
                'lieu_adresse' => $evenement->lieu_adresse,
                'lieu_latitude' => $evenement->lieu_latitude,
                'lieu_longitude' => $evenement->lieu_longitude,
                'image_url' => $evenement->image_url,
                'inscription_requise' => $evenement->inscription_requise,
                'inscription_ouverte' => $evenement->inscription_ouverte,
                'places_max' => $evenement->places_max,
                'places_restantes' => $evenement->places_restantes,
                'inscrits_count' => $evenement->inscrits_count,
                'est_complet' => $evenement->est_complet,
                'inscription_infos' => $evenement->inscription_infos,
                'annule' => $evenement->annule,
                'est_passe' => $evenement->est_passe,
            ],
            'est_inscrit' => $estInscrit,
            'inscription' => $inscription ? [
                'nb_personnes' => $inscription->nb_personnes,
                'statut' => $inscription->statut,
            ] : null,
        ]);
    }

    public function inscrire(Request $request, string $codeInsee, string $slug)
    {
        $page = CommunePage::where('code_insee', $codeInsee)->firstOrFail();
        $evenement = $page->evenements()->publies()->where('slug', $slug)->firstOrFail();

        if (! $evenement->inscription_ouverte) {
            return back()->with('error', 'Les inscriptions sont fermees.');
        }

        if ($evenement->estInscrit($request->user())) {
            return back()->with('error', 'Vous etes deja inscrit.');
        }

        $validated = $request->validate([
            'nb_personnes' => 'integer|min:1|max:10',
            'commentaire' => 'nullable|string|max:500',
        ]);

        $evenement->inscrireUtilisateur(
            $request->user(),
            $validated['nb_personnes'] ?? 1,
            $validated['commentaire'] ?? null
        );

        return back()->with('success', 'Inscription confirmee.');
    }

    public function desinscrire(Request $request, string $codeInsee, string $slug)
    {
        $page = CommunePage::where('code_insee', $codeInsee)->firstOrFail();
        $evenement = $page->evenements()->where('slug', $slug)->firstOrFail();

        $inscription = $evenement->inscriptions()
            ->where('user_id', $request->user()->id)
            ->where('statut', '!=', 'annule')
            ->first();

        if ($inscription) {
            $inscription->annuler();
            $this->promouvoirListeAttente($evenement);
        }

        return back()->with('success', 'Inscription annulee.');
    }

    public function calendrier(string $codeInsee): Response
    {
        $page = CommunePage::with('ville')->where('code_insee', $codeInsee)->firstOrFail();

        if (! $page->evenements_actifs) {
            abort(404, 'Evenements non actives pour cette commune.');
        }

        $mois = request('mois', now()->format('Y-m'));
        $debut = \Carbon\Carbon::createFromFormat('Y-m', $mois)->startOfMonth();
        $fin = $debut->copy()->endOfMonth();

        $evenements = $page->evenements()
            ->publies()
            ->where(function ($q) use ($debut, $fin) {
                $q->whereBetween('date_debut', [$debut, $fin])
                    ->orWhere(function ($q2) use ($debut, $fin) {
                        $q2->where('date_debut', '<=', $fin)
                            ->where(function ($q3) use ($debut) {
                                $q3->where('date_fin', '>=', $debut)
                                    ->orWhereNull('date_fin');
                            });
                    });
            })
            ->orderBy('date_debut')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'titre' => $e->titre,
                'slug' => $e->slug,
                'categorie' => $e->categorie,
                'categorie_label' => $e->categorie_label,
                'date_debut' => $e->date_debut->toISOString(),
                'date_fin' => $e->date_fin?->toISOString(),
                'journee_entiere' => $e->journee_entiere,
                'lieu_nom' => $e->lieu_nom,
                'annule' => $e->annule,
                'inscription_requise' => $e->inscription_requise,
                'est_complet' => $e->est_complet,
            ]);

        return Inertia::render('Commune/Calendrier', [
            'ville' => ['nom' => $page->ville->nom, 'code_insee' => $codeInsee, 'slug' => $page->ville->slug],
            'page' => ['statut' => $page->statut, 'couleur_primaire' => $page->couleur_primaire],
            'evenements' => $evenements,
            'mois_actuel' => $mois,
            'seo' => [
                'title' => "Calendrier municipal - {$page->ville->nom} - Hub Citoyen",
                'description' => "Calendrier des evenements municipaux a {$page->ville->nom}.",
                'image' => $page->image_couverture_url ?? $page->ville->blason_url,
                'url' => url()->current(),
                'type' => 'website',
            ],
        ]);
    }

    // ========================================================================
    // ADMIN CRUD
    // ========================================================================

    public function adminIndex(string $codeInsee): Response
    {
        $page = $this->resolveAdminPage($codeInsee);

        $evenements = $page->evenements()
            ->orderByDesc('date_debut')
            ->paginate(20)
            ->through(fn ($e) => [
                'id' => $e->id,
                'titre' => $e->titre,
                'slug' => $e->slug,
                'categorie' => $e->categorie,
                'date_debut' => $e->date_debut->format('d/m/Y H:i'),
                'publie' => $e->publie,
                'annule' => $e->annule,
                'inscrits_count' => $e->inscrits_count,
                'places_max' => $e->places_max,
            ]);

        return Inertia::render('Commune/Admin/Evenements', [
            'ville' => ['nom' => $page->ville->nom, 'code_insee' => $codeInsee],
            'evenements' => $evenements,
        ]);
    }

    public function create(string $codeInsee): Response
    {
        $this->resolveAdminPage($codeInsee);

        return Inertia::render('Commune/Admin/EvenementForm', [
            'code_insee' => $codeInsee,
            'categories' => CommuneEvenement::CATEGORIES,
            'evenement' => null,
        ]);
    }

    public function store(Request $request, string $codeInsee)
    {
        $page = $this->resolveAdminPage($codeInsee);

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'categorie' => 'required|string|in:'.implode(',', array_keys(CommuneEvenement::CATEGORIES)),
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'journee_entiere' => 'boolean',
            'lieu_nom' => 'nullable|string|max:255',
            'lieu_adresse' => 'nullable|string|max:500',
            'inscription_requise' => 'boolean',
            'places_max' => 'nullable|integer|min:1',
            'inscription_limite' => 'nullable|date',
            'inscription_infos' => 'nullable|string|max:1000',
            'publie' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store("communes/{$codeInsee}/evenements", 'public');
        }

        $page->evenements()->create([
            'auteur_id' => $request->user()->id,
            'titre' => $validated['titre'],
            'slug' => Str::slug($validated['titre']),
            'description' => $validated['description'] ?? null,
            'categorie' => $validated['categorie'],
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'] ?? null,
            'journee_entiere' => $validated['journee_entiere'] ?? false,
            'lieu_nom' => $validated['lieu_nom'] ?? null,
            'lieu_adresse' => $validated['lieu_adresse'] ?? null,
            'inscription_requise' => $validated['inscription_requise'] ?? false,
            'places_max' => $validated['places_max'] ?? null,
            'inscription_limite' => $validated['inscription_limite'] ?? null,
            'inscription_infos' => $validated['inscription_infos'] ?? null,
            'publie' => $validated['publie'] ?? false,
            'image_path' => $imagePath,
        ]);

        return redirect()->route('commune.admin.evenements', $codeInsee)
            ->with('success', 'Evenement cree.');
    }

    public function edit(string $codeInsee, string $slug): Response
    {
        $page = $this->resolveAdminPage($codeInsee);
        $evenement = $page->evenements()->where('slug', $slug)->firstOrFail();

        return Inertia::render('Commune/Admin/EvenementForm', [
            'code_insee' => $codeInsee,
            'categories' => CommuneEvenement::CATEGORIES,
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
                'slug' => $evenement->slug,
                'description' => $evenement->description,
                'categorie' => $evenement->categorie,
                'date_debut' => $evenement->date_debut->format('Y-m-d\TH:i'),
                'date_fin' => $evenement->date_fin?->format('Y-m-d\TH:i'),
                'journee_entiere' => $evenement->journee_entiere,
                'lieu_nom' => $evenement->lieu_nom,
                'lieu_adresse' => $evenement->lieu_adresse,
                'inscription_requise' => $evenement->inscription_requise,
                'places_max' => $evenement->places_max,
                'inscription_limite' => $evenement->inscription_limite?->format('Y-m-d\TH:i'),
                'inscription_infos' => $evenement->inscription_infos,
                'publie' => $evenement->publie,
                'image_url' => $evenement->image_url,
            ],
        ]);
    }

    public function update(Request $request, string $codeInsee, string $slug)
    {
        $page = $this->resolveAdminPage($codeInsee);
        $evenement = $page->evenements()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'categorie' => 'required|string|in:'.implode(',', array_keys(CommuneEvenement::CATEGORIES)),
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'journee_entiere' => 'boolean',
            'lieu_nom' => 'nullable|string|max:255',
            'lieu_adresse' => 'nullable|string|max:500',
            'inscription_requise' => 'boolean',
            'places_max' => 'nullable|integer|min:1',
            'inscription_limite' => 'nullable|date',
            'inscription_infos' => 'nullable|string|max:1000',
            'publie' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store("communes/{$codeInsee}/evenements", 'public');
        }

        $evenement->update($validated);

        return redirect()->route('commune.admin.evenements', $codeInsee)
            ->with('success', 'Evenement mis a jour.');
    }

    public function destroy(string $codeInsee, string $slug)
    {
        $page = $this->resolveAdminPage($codeInsee);
        $page->evenements()->where('slug', $slug)->firstOrFail()->delete();

        return redirect()->route('commune.admin.evenements', $codeInsee)
            ->with('success', 'Evenement supprime.');
    }

    public function inscriptions(string $codeInsee, string $slug): Response
    {
        $page = $this->resolveAdminPage($codeInsee);
        $evenement = $page->evenements()->where('slug', $slug)->firstOrFail();

        $inscriptions = $evenement->inscriptions()
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'nom' => $i->user?->name ?? 'Utilisateur supprime',
                'email' => $i->user?->email,
                'nb_personnes' => $i->nb_personnes,
                'commentaire' => $i->commentaire,
                'statut' => $i->statut,
                'statut_label' => $i->statut_label,
                'date' => $i->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Commune/Admin/Inscriptions', [
            'ville' => ['nom' => $page->ville->nom, 'code_insee' => $codeInsee],
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
                'slug' => $evenement->slug,
                'date_debut' => $evenement->date_debut->format('d/m/Y H:i'),
                'places_max' => $evenement->places_max,
                'inscrits_count' => $evenement->inscrits_count,
                'inscription_requise' => $evenement->inscription_requise,
            ],
            'inscriptions' => $inscriptions,
        ]);
    }

    public function exportInscriptions(string $codeInsee, string $slug)
    {
        $page = $this->resolveAdminPage($codeInsee);
        $evenement = $page->evenements()->where('slug', $slug)->firstOrFail();

        $inscriptions = $evenement->inscriptions()
            ->with('user:id,name,email')
            ->whereIn('statut', ['inscrit', 'liste_attente'])
            ->orderBy('statut')
            ->orderByDesc('created_at')
            ->get();

        $csv = "Nom,Email,Personnes,Statut,Commentaire,Date\n";
        foreach ($inscriptions as $i) {
            $nom = str_replace('"', '""', $i->user?->name ?? 'N/A');
            $email = $i->user?->email ?? '';
            $commentaire = str_replace('"', '""', $i->commentaire ?? '');
            $csv .= "\"{$nom}\",{$email},{$i->nb_personnes},{$i->statut},\"{$commentaire}\",{$i->created_at->format('d/m/Y H:i')}\n";
        }

        $filename = Str::slug($evenement->titre).'-inscriptions.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function annulerInscription(string $codeInsee, string $slug, int $id)
    {
        $page = $this->resolveAdminPage($codeInsee);
        $evenement = $page->evenements()->where('slug', $slug)->firstOrFail();
        $inscription = $evenement->inscriptions()->findOrFail($id);

        $inscription->annuler();
        $this->promouvoirListeAttente($evenement);

        return back()->with('success', 'Inscription annulee.');
    }

    public function annulerEvenement(string $codeInsee, string $slug)
    {
        $page = $this->resolveAdminPage($codeInsee);
        $evenement = $page->evenements()->where('slug', $slug)->firstOrFail();

        $evenement->annuler();

        return redirect()->route('commune.admin.evenements', $codeInsee)
            ->with('success', 'Evenement annule.');
    }

    private function promouvoirListeAttente(CommuneEvenement $evenement): void
    {
        if (! $evenement->inscription_requise || ! $evenement->places_max) {
            return;
        }

        $evenement->refresh();

        $placesLibres = $evenement->places_max - $evenement->inscrits_count;
        if ($placesLibres <= 0) {
            return;
        }

        $enAttente = $evenement->inscriptions()
            ->where('statut', 'liste_attente')
            ->orderBy('created_at')
            ->get();

        foreach ($enAttente as $inscription) {
            if ($placesLibres < $inscription->nb_personnes) {
                continue;
            }

            $inscription->update(['statut' => 'inscrit']);
            $evenement->increment('inscrits_count', $inscription->nb_personnes);
            $placesLibres -= $inscription->nb_personnes;

            if ($placesLibres <= 0) {
                break;
            }
        }
    }

    private function resolveAdminPage(string $codeInsee): CommunePage
    {
        $page = CommunePage::with('ville')->where('code_insee', $codeInsee)->firstOrFail();

        if (! $page->estAdministrePar(auth()->user())) {
            abort(403);
        }

        return $page;
    }
}
