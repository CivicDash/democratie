<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CandidatureDocument;
use App\Models\ListeElectorale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ModerationMunicipalesController extends Controller
{
    public function index()
    {
        $listesEnAttente = ListeElectorale::whereIn('statut', ['en_attente', 'en_verification'])
            ->with(['candidats', 'documents', 'createur'])
            ->orderBy('created_at')
            ->paginate(20)
            ->through(fn($liste) => [
                'uuid' => $liste->uuid,
                'nom_liste' => $liste->nom_liste,
                'commune_nom' => $liste->commune_nom,
                'departement_code' => $liste->departement_code,
                'statut' => $liste->statut,
                'statut_formate' => $liste->statut_formate,
                'nombre_candidats' => $liste->candidats->count(),
                'nombre_documents' => $liste->documents->count(),
                'documents_en_attente' => $liste->documents->where('statut_verification', 'en_attente')->count(),
                'createur' => $liste->createur?->name,
                'created_at' => $liste->created_at->format('d/m/Y H:i'),
            ]);

        $stats = [
            'en_attente' => ListeElectorale::where('statut', 'en_attente')->count(),
            'en_verification' => ListeElectorale::where('statut', 'en_verification')->count(),
            'valides_aujourdhui' => ListeElectorale::where('statut', 'valide')
                ->whereDate('validated_at', today())
                ->count(),
        ];

        return Inertia::render('Elections/Municipales/Moderation/Index', [
            'listes' => $listesEnAttente,
            'stats' => $stats,
        ]);
    }

    public function show(string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)
            ->with([
                'candidats',
                'documents.uploader',
                'createur',
                'moderationLogs.moderator',
            ])
            ->firstOrFail();

        return Inertia::render('Elections/Municipales/Moderation/Show', [
            'liste' => [
                'uuid' => $liste->uuid,
                'nom_liste' => $liste->nom_liste,
                'commune_nom' => $liste->commune_nom,
                'commune_code_insee' => $liste->commune_code_insee,
                'departement_code' => $liste->departement_code,
                'nuance_politique' => $liste->nuance_politique,
                'parti_principal' => $liste->parti_principal,
                'slogan' => $liste->slogan,
                'description' => $liste->description,
                'couleur_principale' => $liste->couleur_principale,
                'email_contact' => $liste->email_contact,
                'telephone_contact' => $liste->telephone_contact,
                'site_web' => $liste->site_web,
                'reseaux_sociaux' => $liste->reseaux_sociaux,
                'logo_url' => $liste->logo_url,
                'programme_pdf_url' => $liste->programme_pdf_url,
                'statut' => $liste->statut,
                'statut_formate' => $liste->statut_formate,
                'motif_rejet' => $liste->motif_rejet,
                'created_at' => $liste->created_at->format('d/m/Y H:i'),
            ],
            'createur' => $liste->createur ? [
                'id' => $liste->createur->id,
                'name' => $liste->createur->name,
                'email' => $liste->createur->email,
            ] : null,
            'candidats' => $liste->candidats->map(fn($c) => [
                'uuid' => $c->uuid,
                'nom_complet' => $c->nom_complet,
                'civilite' => $c->civilite,
                'date_naissance' => $c->date_naissance?->format('d/m/Y'),
                'age' => $c->age,
                'profession' => $c->profession,
                'position' => $c->position,
                'est_tete_de_liste' => $c->est_tete_de_liste,
                'fonction_visee' => $c->fonction_label,
                'photo_url' => $c->photo_url,
                'initiales' => $c->initiales,
                'est_eligible' => $c->est_eligible,
            ]),
            'documents' => $liste->documents->map(fn($d) => [
                'uuid' => $d->uuid,
                'type' => $d->type,
                'type_formate' => $d->type_formate,
                'nom_fichier' => $d->nom_fichier,
                'url' => $d->url,
                'taille_formatee' => $d->taille_formatee,
                'est_image' => $d->est_image,
                'est_pdf' => $d->est_pdf,
                'statut' => $d->statut_verification,
                'statut_formate' => $d->statut_formate,
                'statut_couleur' => $d->statut_couleur,
                'commentaire' => $d->commentaire_verification,
                'numero_reference' => $d->numero_reference,
                'date_document' => $d->date_document?->format('d/m/Y'),
                'uploader' => $d->uploader?->name,
                'uploaded_at' => $d->created_at->format('d/m/Y H:i'),
            ]),
            'historique' => $liste->moderationLogs()
                ->orderByDesc('created_at')
                ->get()
                ->map(fn($log) => [
                    'action' => $log->action_formatee,
                    'icone' => $log->action_icone,
                    'couleur' => $log->action_couleur,
                    'ancien_statut' => $log->ancien_statut,
                    'nouveau_statut' => $log->nouveau_statut,
                    'commentaire' => $log->commentaire,
                    'date' => $log->created_at->format('d/m/Y H:i'),
                    'moderateur' => $log->moderator?->name,
                ]),
        ]);
    }

    public function validerDocument(Request $request, string $uuid)
    {
        $document = CandidatureDocument::where('uuid', $uuid)->firstOrFail();
        $document->valider(Auth::user(), $request->input('commentaire'));

        return back()->with('success', 'Document validé !');
    }

    public function invaliderDocument(Request $request, string $uuid)
    {
        $document = CandidatureDocument::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'raison' => 'required|string|max:500',
        ]);

        $document->invalider(Auth::user(), $validated['raison']);

        return back()->with('success', 'Document marqué comme invalide.');
    }

    public function validerListe(Request $request, string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)->firstOrFail();
        $liste->valider(Auth::user(), $request->input('commentaire'));

        return redirect()
            ->route('elections.municipales.moderation.index')
            ->with('success', "Liste \"{$liste->nom_liste}\" validée !");
    }

    public function rejeterListe(Request $request, string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'motif' => 'required|string|max:1000',
        ]);

        $liste->rejeter(Auth::user(), $validated['motif']);

        return redirect()
            ->route('elections.municipales.moderation.index')
            ->with('success', 'Liste rejetée.');
    }

    public function demanderDocuments(Request $request, string $uuid)
    {
        $liste = ListeElectorale::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'commentaire' => 'required|string|max:1000',
        ]);

        $liste->demanderDocuments(Auth::user(), $validated['commentaire']);

        return back()->with('success', 'Demande de documents envoyée.');
    }
}
