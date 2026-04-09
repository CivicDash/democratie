<?php

namespace App\Http\Controllers\Web\Commune;

use App\Http\Controllers\Controller;
use App\Models\CommuneAdmin;
use App\Models\CommunePage;
use App\Notifications\CommuneNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CommuneReclamationController extends Controller
{
    public function index(string $codeInsee): Response
    {
        $page = CommunePage::with('ville')->where('code_insee', $codeInsee)->firstOrFail();

        if ($page->est_active) {
            return redirect()->route('commune.index', $codeInsee)
                ->with('info', 'Cette commune est deja reclamee et active.');
        }

        $emailMairie = $page->email_mairie;
        $emailMasque = $emailMairie ? $this->masquerEmail($emailMairie) : null;

        return Inertia::render('Commune/Reclamer', [
            'ville' => ['nom' => $page->ville->nom, 'code_insee' => $codeInsee, 'slug' => $page->ville->slug],
            'page' => ['statut' => $page->statut],
            'email_masque' => $emailMasque,
            'a_email_officiel' => (bool) $emailMairie,
            'niveaux_disponibles' => $this->niveauxDisponibles($page),
        ]);
    }

    public function initier(Request $request, string $codeInsee)
    {
        $page = CommunePage::with('ville')->where('code_insee', $codeInsee)->firstOrFail();

        if ($page->est_active) {
            return back()->with('error', 'Cette commune est deja reclamee.');
        }

        $validated = $request->validate([
            'niveau' => 'required|string|in:email_officiel,domaine_email,manuelle',
        ]);

        $niveau = $validated['niveau'];

        if ($niveau === 'email_officiel' && $page->email_mairie) {
            $code = $page->genererCodeVerification();

            $request->user()->notify(new CommuneNotification(
                'page_reclamee',
                $page,
                $code
            ));

            Log::info('CommuneReclamation: code envoye', [
                'code_insee' => $codeInsee,
                'user_id' => $request->user()->id,
                'email' => $page->email_mairie,
            ]);

            return back()->with('success', 'Un code de verification a ete envoye a l\'email officiel de la mairie.');
        }

        if ($niveau === 'domaine_email') {
            $page->reclamer($request->user(), 'domaine_email');

            return back()->with('success', 'Votre demande est en cours de verification.');
        }

        if ($niveau === 'manuelle') {
            $page->reclamer($request->user(), 'manuelle');

            return redirect()->route('commune.reclamer.document', $codeInsee);
        }

        return back()->with('error', 'Niveau de verification non disponible.');
    }

    public function verifierCode(Request $request, string $codeInsee)
    {
        $page = CommunePage::with('ville')->where('code_insee', $codeInsee)->firstOrFail();

        $validated = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        if (! $page->verifierCode($validated['code'])) {
            return back()->with('error', 'Code invalide ou expire.');
        }

        $page->activer($request->user());

        CommuneAdmin::creerAvecRole($page, $request->user(), 'maire');

        $request->user()->notify(new CommuneNotification('page_activee', $page));

        return redirect()->route('commune.admin.dashboard', $codeInsee)
            ->with('success', 'Votre commune est maintenant active ! Bienvenue dans votre espace d\'administration.');
    }

    public function soumettreDocument(Request $request, string $codeInsee)
    {
        $page = CommunePage::where('code_insee', $codeInsee)->firstOrFail();

        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'type_document' => 'required|string|in:piece_identite,arrete_nomination,pv_installation',
        ]);

        $path = $request->file('document')->store("communes/{$codeInsee}/reclamation", 'public');

        Log::info('CommuneReclamation: document soumis', [
            'code_insee' => $codeInsee,
            'user_id' => $request->user()->id,
            'type' => $request->type_document,
            'path' => $path,
        ]);

        $page->reclamer($request->user(), 'manuelle');

        return redirect()->route('commune.index', $codeInsee)
            ->with('success', 'Document soumis. Notre equipe verifiera votre demande sous 24 a 72h.');
    }

    // ========================================================================
    // HELPERS
    // ========================================================================

    private function masquerEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);
        $masque = substr($local, 0, 2).str_repeat('*', max(3, strlen($local) - 2));

        return $masque.'@'.$domain;
    }

    private function niveauxDisponibles(CommunePage $page): array
    {
        $niveaux = [];

        if ($page->email_mairie) {
            $niveaux[] = [
                'id' => 'email_officiel',
                'label' => 'Verification par email officiel',
                'description' => 'Un code de verification sera envoye a l\'email officiel de la mairie.',
                'delai' => 'Moins de 5 minutes',
                'recommande' => true,
            ];
        }

        $niveaux[] = [
            'id' => 'domaine_email',
            'label' => 'Verification par domaine email',
            'description' => 'Utilisez une adresse email du domaine officiel de la mairie.',
            'delai' => 'Moins de 24 heures',
            'recommande' => false,
        ];

        $niveaux[] = [
            'id' => 'manuelle',
            'label' => 'Verification manuelle',
            'description' => 'Soumettez une piece d\'identite et un arrete de nomination ou PV d\'installation.',
            'delai' => '24 a 72 heures',
            'recommande' => false,
        ];

        return $niveaux;
    }
}
