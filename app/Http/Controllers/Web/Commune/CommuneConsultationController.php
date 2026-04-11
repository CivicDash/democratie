<?php

namespace App\Http\Controllers\Web\Commune;

use App\Http\Controllers\Controller;
use App\Models\CommuneConsultation;
use App\Models\CommunePage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CommuneConsultationController extends Controller
{
    public function index(string $codeInsee): Response
    {
        $page = CommunePage::with('ville')->where('code_insee', $codeInsee)->firstOrFail();

        $consultations = $page->consultations()
            ->publiees()
            ->orderByDesc('publie_at')
            ->paginate(12)
            ->through(fn ($c) => [
                'id' => $c->id,
                'titre' => $c->titre,
                'slug' => $c->slug,
                'description' => Str::limit($c->description, 200),
                'votes_count' => $c->votes_count,
                'est_ouverte' => $c->est_ouverte,
                'fermee' => $c->fermee,
                'ferme_at' => $c->ferme_at?->format('d/m/Y'),
                'publie_at' => $c->publie_at?->format('d/m/Y'),
                'options_count' => count($c->options ?? []),
            ]);

        return Inertia::render('Commune/Consultations', [
            'ville' => ['nom' => $page->ville->nom, 'code_insee' => $codeInsee, 'slug' => $page->ville->slug],
            'page' => ['statut' => $page->statut, 'couleur_primaire' => $page->couleur_primaire],
            'consultations' => $consultations,
            'seo' => [
                'title' => "Consultations citoyennes - {$page->ville->nom} - Hub Citoyen",
                'description' => "Participez aux consultations citoyennes de {$page->ville->nom}. Donnez votre avis sur les sujets qui comptent.",
                'url' => url()->current(),
                'type' => 'website',
            ],
        ]);
    }

    public function show(string $codeInsee, string $slug): Response
    {
        $page = CommunePage::with('ville')->where('code_insee', $codeInsee)->firstOrFail();
        $consultation = $page->consultations()->publiees()->where('slug', $slug)->firstOrFail();

        $aVote = false;
        $votesUtilisateur = [];
        if ($user = auth()->user()) {
            $aVote = $consultation->aVote($user);
            $votesUtilisateur = $consultation->votes()
                ->where('user_id', $user->id)
                ->pluck('option_key')
                ->toArray();
        }

        $resultats = ($consultation->fermee || $aVote) ? $consultation->getResultats() : null;

        return Inertia::render('Commune/ConsultationShow', [
            'ville' => ['nom' => $page->ville->nom, 'code_insee' => $codeInsee, 'slug' => $page->ville->slug],
            'consultation' => [
                'id' => $consultation->id,
                'titre' => $consultation->titre,
                'slug' => $consultation->slug,
                'description' => $consultation->description,
                'options' => $consultation->options,
                'multiple' => $consultation->multiple,
                'est_ouverte' => $consultation->est_ouverte,
                'fermee' => $consultation->fermee,
                'votes_count' => $consultation->votes_count,
                'ferme_at' => $consultation->ferme_at?->format('d/m/Y H:i'),
                'publie_at' => $consultation->publie_at?->format('d/m/Y'),
            ],
            'a_vote' => $aVote,
            'votes_utilisateur' => $votesUtilisateur,
            'resultats' => $resultats,
            'seo' => [
                'title' => "{$consultation->titre} - Consultation - {$page->ville->nom}",
                'description' => Str::limit($consultation->description, 160),
                'url' => url()->current(),
                'type' => 'website',
            ],
        ]);
    }

    public function voter(Request $request, string $codeInsee, string $slug)
    {
        $page = CommunePage::where('code_insee', $codeInsee)->firstOrFail();
        $consultation = $page->consultations()->publiees()->where('slug', $slug)->firstOrFail();

        if (! $consultation->est_ouverte) {
            return back()->with('error', 'Cette consultation est fermee.');
        }

        if ($consultation->aVote($request->user())) {
            return back()->with('error', 'Vous avez deja vote.');
        }

        $validated = $request->validate([
            'options' => 'required|array|min:1',
            'options.*' => 'string',
        ]);

        if (! $consultation->multiple && count($validated['options']) > 1) {
            return back()->with('error', 'Une seule option autorisee.');
        }

        $validKeys = collect($consultation->options)->pluck('key')->toArray();
        foreach ($validated['options'] as $optionKey) {
            if (! in_array($optionKey, $validKeys)) {
                continue;
            }
            $consultation->votes()->create([
                'user_id' => $request->user()->id,
                'option_key' => $optionKey,
            ]);
        }

        $consultation->increment('votes_count');

        return back()->with('success', 'Votre vote a ete enregistre.');
    }

    // ========================================================================
    // ADMIN
    // ========================================================================

    public function adminIndex(string $codeInsee): Response
    {
        $page = $this->resolveAdminPage($codeInsee);

        $consultations = $page->consultations()
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn ($c) => [
                'id' => $c->id,
                'titre' => $c->titre,
                'slug' => $c->slug,
                'publie' => $c->publie,
                'fermee' => $c->fermee,
                'votes_count' => $c->votes_count,
                'publie_at' => $c->publie_at?->format('d/m/Y'),
                'ferme_at' => $c->ferme_at?->format('d/m/Y'),
                'options_count' => count($c->options ?? []),
            ]);

        return Inertia::render('Commune/Admin/Consultations', [
            'ville' => ['nom' => $page->ville->nom, 'code_insee' => $codeInsee],
            'consultations' => $consultations,
        ]);
    }

    public function create(string $codeInsee): Response
    {
        $this->resolveAdminPage($codeInsee);

        return Inertia::render('Commune/Admin/ConsultationForm', [
            'code_insee' => $codeInsee,
            'consultation' => null,
        ]);
    }

    public function store(Request $request, string $codeInsee)
    {
        $page = $this->resolveAdminPage($codeInsee);

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'options' => 'required|array|min:2|max:20',
            'options.*.key' => 'required|string|max:50',
            'options.*.label' => 'required|string|max:255',
            'multiple' => 'boolean',
            'publie' => 'boolean',
            'ferme_at' => 'nullable|date|after:now',
        ]);

        $page->consultations()->create([
            'auteur_id' => $request->user()->id,
            'titre' => $validated['titre'],
            'description' => $validated['description'] ?? null,
            'options' => $validated['options'],
            'multiple' => $validated['multiple'] ?? false,
            'publie' => $validated['publie'] ?? false,
            'publie_at' => ($validated['publie'] ?? false) ? now() : null,
            'ferme_at' => $validated['ferme_at'] ?? null,
        ]);

        return redirect()->route('commune.admin.consultations', $codeInsee)
            ->with('success', 'Consultation creee.');
    }

    public function fermer(string $codeInsee, string $slug)
    {
        $page = $this->resolveAdminPage($codeInsee);
        $consultation = $page->consultations()->where('slug', $slug)->firstOrFail();

        $consultation->update(['fermee' => true]);

        return back()->with('success', 'Consultation fermee.');
    }

    public function destroy(string $codeInsee, string $slug)
    {
        $page = $this->resolveAdminPage($codeInsee);
        $page->consultations()->where('slug', $slug)->firstOrFail()->delete();

        return redirect()->route('commune.admin.consultations', $codeInsee)
            ->with('success', 'Consultation supprimee.');
    }

    private function resolveAdminPage(string $codeInsee): CommunePage
    {
        $page = CommunePage::with('ville')->where('code_insee', $codeInsee)->firstOrFail();
        if (! $page->estAdministrePar(auth()->user()) && ! auth()->user()->hasRole('admin')) {
            abort(403);
        }
        return $page;
    }
}
