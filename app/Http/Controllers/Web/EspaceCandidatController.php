<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CandidatMunicipal;
use App\Models\ListeElectorale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EspaceCandidatController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $mesListes = ListeElectorale::where('created_by', $user->id)
            ->with(['candidats', 'documents'])
            ->latest()
            ->get()
            ->map(fn($liste) => [
                'uuid' => $liste->uuid,
                'nom_liste' => $liste->nom_liste,
                'commune_nom' => $liste->commune_nom,
                'statut' => $liste->statut,
                'statut_formate' => $liste->statut_formate,
                'statut_couleur' => $liste->statut_couleur,
                'nombre_candidats' => $liste->candidats->count(),
                'documents_valides' => $liste->documents->where('statut_verification', 'valide')->count(),
                'documents_en_attente' => $liste->documents->where('statut_verification', 'en_attente')->count(),
                'peut_etre_modifiee' => $liste->peut_etre_modifiee,
                'created_at' => $liste->created_at->format('d/m/Y'),
            ]);

        $maCandidature = CandidatMunicipal::where('user_id', $user->id)
            ->with(['liste'])
            ->first();

        return Inertia::render('Elections/Municipales/EspaceCandidat/Index', [
            'mes_listes' => $mesListes,
            'ma_candidature' => $maCandidature ? [
                'uuid' => $maCandidature->uuid,
                'nom_complet' => $maCandidature->nom_complet,
                'position' => $maCandidature->position,
                'liste_nom' => $maCandidature->liste->nom_liste,
                'liste_uuid' => $maCandidature->liste->uuid,
                'commune_nom' => $maCandidature->liste->commune_nom,
            ] : null,
        ]);
    }

    public function createListe()
    {
        return Inertia::render('Elections/Municipales/EspaceCandidat/CreateListe', [
            'nuances_politiques' => ElectionsMunicipalesController::nuancesPolitiques(),
        ]);
    }

    public function storeListe(Request $request)
    {
        $validated = $request->validate([
            'commune_code_insee' => 'required|string|size:5',
            'commune_nom' => 'required|string|max:255',
            'departement_code' => 'required|string|max:3',
            'nom_liste' => 'required|string|max:255',
            'nuance_politique' => 'nullable|string|max:50',
            'parti_principal' => 'nullable|string|max:100',
            'slogan' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'couleur_principale' => 'nullable|string|max:7',
            'email_contact' => 'nullable|email|max:255',
            'telephone_contact' => 'nullable|string|max:20',
            'site_web' => 'nullable|url|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'tiktok_url' => 'nullable|url|max:255',
            'resume_programme' => 'nullable|string|max:500',
        ]);

        $liste = ListeElectorale::create([
            ...$validated,
            'created_by' => Auth::id(),
            'statut' => 'brouillon',
        ]);

        $liste->moderationLogs()->create([
            'action' => 'creation',
            'nouveau_statut' => 'brouillon',
            'moderator_id' => Auth::id(),
        ]);

        return redirect()
            ->route('elections.municipales.espace-candidat.edit-liste', $liste->uuid)
            ->with('success', 'Liste créée avec succès !');
    }

    public function editListe(string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)
            ->where('created_by', Auth::id())
            ->with(['candidats', 'documents', 'moderationLogs.moderator'])
            ->firstOrFail();

        if (!$liste->peut_etre_modifiee) {
            return redirect()
                ->route('elections.municipales.espace-candidat.index')
                ->with('error', 'Cette liste ne peut plus être modifiée.');
        }

        return Inertia::render('Elections/Municipales/EspaceCandidat/EditListe', [
            'liste' => [
                'uuid' => $liste->uuid,
                'commune_code_insee' => $liste->commune_code_insee,
                'commune_nom' => $liste->commune_nom,
                'departement_code' => $liste->departement_code,
                'nom_liste' => $liste->nom_liste,
                'nuance_politique' => $liste->nuance_politique,
                'parti_principal' => $liste->parti_principal,
                'slogan' => $liste->slogan,
                'description' => $liste->description,
                'couleur_principale' => $liste->couleur_principale,
                'email_contact' => $liste->email_contact,
                'telephone_contact' => $liste->telephone_contact,
                'site_web' => $liste->site_web,
                'facebook_url' => $liste->facebook_url,
                'twitter_url' => $liste->twitter_url,
                'instagram_url' => $liste->instagram_url,
                'youtube_url' => $liste->youtube_url,
                'tiktok_url' => $liste->tiktok_url,
                'resume_programme' => $liste->resume_programme,
                'logo_url' => $liste->logo_url,
                'programme_pdf_url' => $liste->programme_pdf_url,
                'statut' => $liste->statut,
                'statut_formate' => $liste->statut_formate,
                'motif_rejet' => $liste->motif_rejet,
            ],
            'candidats' => $liste->candidats->map(fn($c) => [
                'uuid' => $c->uuid,
                'nom' => $c->nom,
                'prenom' => $c->prenom,
                'position' => $c->position,
                'est_tete_de_liste' => $c->est_tete_de_liste,
                'photo_url' => $c->photo_url,
                'initiales' => $c->initiales,
            ]),
            'documents' => $liste->documents->map(fn($d) => [
                'uuid' => $d->uuid,
                'type' => $d->type,
                'type_formate' => $d->type_formate,
                'nom_fichier' => $d->nom_fichier,
                'taille_formatee' => $d->taille_formatee,
                'statut' => $d->statut_verification,
                'statut_formate' => $d->statut_formate,
                'statut_couleur' => $d->statut_couleur,
                'commentaire' => $d->commentaire_verification,
            ]),
            'historique' => $liste->moderationLogs->map(fn($log) => [
                'action' => $log->action_formatee,
                'icone' => $log->action_icone,
                'couleur' => $log->action_couleur,
                'commentaire' => $log->commentaire,
                'date' => $log->created_at->format('d/m/Y H:i'),
                'moderateur' => $log->moderator?->name,
            ]),
            'nuances_politiques' => ElectionsMunicipalesController::nuancesPolitiques(),
        ]);
    }

    public function updateListe(Request $request, string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        if (!$liste->peut_etre_modifiee) {
            return back()->with('error', 'Cette liste ne peut plus être modifiée.');
        }

        $validated = $request->validate([
            'nom_liste' => 'required|string|max:255',
            'nuance_politique' => 'nullable|string|max:50',
            'parti_principal' => 'nullable|string|max:100',
            'slogan' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'couleur_principale' => 'nullable|string|max:7',
            'email_contact' => 'nullable|email|max:255',
            'telephone_contact' => 'nullable|string|max:20',
            'site_web' => 'nullable|url|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'tiktok_url' => 'nullable|url|max:255',
            'resume_programme' => 'nullable|string|max:500',
        ]);

        $liste->update($validated);

        return back()->with('success', 'Liste mise à jour !');
    }

    public function uploadLogo(Request $request, string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,webp|max:2048',
        ]);

        if ($liste->logo_path) {
            Storage::disk('public')->delete($liste->logo_path);
        }

        $path = $request->file('logo')->store(
            "elections/municipales/{$liste->uuid}/logo",
            'public'
        );

        $liste->update(['logo_path' => $path]);

        return back()->with('success', 'Logo mis à jour !');
    }

    public function uploadProgramme(Request $request, string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        $request->validate([
            'programme' => 'required|file|mimes:pdf|max:10240',
        ]);

        if ($liste->programme_pdf_path) {
            Storage::disk('public')->delete($liste->programme_pdf_path);
        }

        $path = $request->file('programme')->store(
            "elections/municipales/{$liste->uuid}/programme",
            'public'
        );

        $liste->update(['programme_pdf_path' => $path]);

        return back()->with('success', 'Programme mis à jour !');
    }

    public function uploadDocument(Request $request, string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        $validated = $request->validate([
            'type' => ['required', Rule::in([
                'recepisse_prefecture',
                'piece_identite',
                'attestation_eligibilite',
                'declaration_candidature',
                'photo_officielle',
                'autre',
            ])],
            'document' => 'required|file|mimes:pdf,jpeg,png,webp|max:5120',
            'description' => 'nullable|string|max:255',
            'numero_reference' => 'nullable|string|max:100',
            'date_document' => 'nullable|date',
        ]);

        $file = $request->file('document');
        $path = $file->store(
            "elections/municipales/{$liste->uuid}/documents",
            'public'
        );

        $liste->documents()->create([
            'type' => $validated['type'],
            'nom_fichier' => $file->getClientOriginalName(),
            'chemin_fichier' => $path,
            'mime_type' => $file->getMimeType(),
            'taille_octets' => $file->getSize(),
            'description' => $validated['description'] ?? null,
            'numero_reference' => $validated['numero_reference'] ?? null,
            'date_document' => $validated['date_document'] ?? null,
            'uploaded_by' => Auth::id(),
        ]);

        return back()->with('success', 'Document ajouté !');
    }

    public function storeCandidat(Request $request, string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        if (!$liste->peut_etre_modifiee) {
            return back()->with('error', 'La liste ne peut plus être modifiée.');
        }

        $validated = $request->validate([
            'civilite' => 'nullable|in:M.,Mme',
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'nom_usage' => 'nullable|string|max:100',
            'date_naissance' => 'nullable|date|before:today',
            'lieu_naissance' => 'nullable|string|max:255',
            'profession' => 'nullable|string|max:255',
            'est_tete_de_liste' => 'boolean',
            'fonction_visee' => 'nullable|string|max:100',
            'biographie' => 'nullable|string|max:1000',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:20',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
        ]);

        $maxPosition = $liste->candidats()->max('position') ?? 0;

        if ($request->boolean('est_tete_de_liste')) {
            $liste->candidats()->update(['est_tete_de_liste' => false]);
            $validated['position'] = 1;
            $liste->candidats()->where('position', '>=', 1)->increment('position');
        } else {
            $validated['position'] = $maxPosition + 1;
        }

        $candidat = $liste->candidats()->create($validated);

        return back()->with('success', "Candidat {$candidat->nom_complet} ajouté !");
    }

    public function soumettreListe(string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)
            ->where('created_by', Auth::id())
            ->firstOrFail();

        if ($liste->candidats()->count() === 0) {
            return back()->with('error', 'Ajoutez au moins un candidat avant de soumettre.');
        }

        if (!$liste->candidats()->where('est_tete_de_liste', true)->exists()) {
            return back()->with('error', 'Désignez une tête de liste avant de soumettre.');
        }

        $hasRecepisse = $liste->documents()
            ->where('type', 'recepisse_prefecture')
            ->exists();

        if (!$hasRecepisse) {
            return back()->with('error', 'Ajoutez le récépissé de dépôt en préfecture avant de soumettre.');
        }

        if (!$liste->soumettre()) {
            return back()->with('error', 'Impossible de soumettre cette liste.');
        }

        return back()->with('success', 'Liste soumise pour validation ! Vous serez notifié par email.');
    }
}
