<?php

namespace App\Http\Controllers\Web\Commune;

use App\Http\Controllers\Controller;
use App\Models\CommuneAdmin;
use App\Models\CommuneGalerieImage;
use App\Models\CommunePage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CommuneAdminController extends Controller
{
    public function dashboard(string $codeInsee): Response
    {
        $page = $this->resolveAdminPage($codeInsee);

        $stats = [
            'vues_totales' => $page->vues_totales,
            'abonnes' => $page->abonnes_count,
            'articles_publies' => $page->articles()->publies()->count(),
            'articles_brouillon' => $page->articles()->where('publie', false)->count(),
            'evenements_a_venir' => $page->evenements()->publies()->aVenir()->count(),
            'topics_forum' => $page->topics()->where('status', 'published')->count(),
        ];

        $derniersArticles = $page->articles()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'titre' => $a->titre,
                'slug' => $a->slug,
                'publie' => $a->publie,
                'publie_at' => $a->publie_at?->format('d/m/Y'),
                'vues_count' => $a->vues_count,
            ]);

        $prochainsEvenements = $page->evenements()
            ->publies()
            ->prochains()
            ->limit(5)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'titre' => $e->titre,
                'slug' => $e->slug,
                'date_debut' => $e->date_debut->format('d/m/Y H:i'),
                'inscrits_count' => $e->inscrits_count,
                'places_max' => $e->places_max,
            ]);

        return Inertia::render('Commune/Admin/Dashboard', [
            'ville' => $this->formatVilleAdmin($page),
            'page' => $this->formatPageAdmin($page),
            'stats' => $stats,
            'derniers_articles' => $derniersArticles,
            'prochains_evenements' => $prochainsEvenements,
        ]);
    }

    public function parametres(string $codeInsee): Response
    {
        $page = $this->resolveAdminPage($codeInsee, 'peut_modifier_page');

        return Inertia::render('Commune/Admin/Parametres', [
            'ville' => $this->formatVilleAdmin($page),
            'page' => $this->formatPageAdmin($page),
        ]);
    }

    public function updateParametres(Request $request, string $codeInsee)
    {
        $page = $this->resolveAdminPage($codeInsee, 'peut_modifier_page');

        $validated = $request->validate([
            'description_courte' => 'nullable|string|max:1000',
            'mot_du_maire' => 'nullable|string|max:5000',
            'couleur_primaire' => 'nullable|string|size:7|regex:/^#[0-9a-fA-F]{6}$/',
            'couleur_secondaire' => 'nullable|string|size:7|regex:/^#[0-9a-fA-F]{6}$/',
            'telephone' => 'nullable|string|max:20',
            'email_mairie' => 'nullable|email|max:255',
            'adresse_mairie' => 'nullable|string|max:500',
            'site_officiel' => 'nullable|url|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'actus_actives' => 'boolean',
            'evenements_actifs' => 'boolean',
            'forum_actif' => 'boolean',
            'notifications_actives' => 'boolean',
        ]);

        $page->update($validated);

        return back()->with('success', 'Parametres mis a jour.');
    }

    public function uploadLogo(Request $request, string $codeInsee)
    {
        $page = $this->resolveAdminPage($codeInsee, 'peut_modifier_page');

        $request->validate(['logo' => 'required|image|max:1024']);

        if ($page->logo_path) {
            Storage::disk('public')->delete($page->logo_path);
        }

        $path = $request->file('logo')->store("communes/{$codeInsee}", 'public');
        $page->update(['logo_path' => $path]);

        return back()->with('success', 'Logo mis a jour.');
    }

    public function uploadCouverture(Request $request, string $codeInsee)
    {
        $page = $this->resolveAdminPage($codeInsee, 'peut_modifier_page');

        $request->validate(['couverture' => 'required|image|max:4096']);

        if ($page->image_couverture_path) {
            Storage::disk('public')->delete($page->image_couverture_path);
        }

        $path = $request->file('couverture')->store("communes/{$codeInsee}", 'public');
        $page->update(['image_couverture_path' => $path]);

        return back()->with('success', 'Image de couverture mise a jour.');
    }

    public function delegues(string $codeInsee): Response
    {
        $page = $this->resolveAdminPage($codeInsee, 'peut_deleguer');

        $admins = $page->admins()
            ->with('user:id,name,email')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'user' => ['id' => $a->user->id, 'name' => $a->user->name, 'email' => $a->user->email],
                'role' => $a->role,
                'role_label' => $a->role_label,
                'peut_publier_actus' => $a->peut_publier_actus,
                'peut_gerer_evenements' => $a->peut_gerer_evenements,
                'peut_envoyer_notifications' => $a->peut_envoyer_notifications,
                'peut_modifier_page' => $a->peut_modifier_page,
                'peut_deleguer' => $a->peut_deleguer,
                'expire_le' => $a->expire_le?->format('d/m/Y'),
                'est_expire' => $a->est_expire,
            ]);

        return Inertia::render('Commune/Admin/Delegues', [
            'ville' => $this->formatVilleAdmin($page),
            'admins' => $admins,
        ]);
    }

    public function ajouterDelegue(Request $request, string $codeInsee)
    {
        $page = $this->resolveAdminPage($codeInsee, 'peut_deleguer');

        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'role' => 'required|string|in:adjoint,delegue,communication',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        if ($page->estAdministrePar($user)) {
            return back()->with('error', 'Cet utilisateur est deja administrateur.');
        }

        CommuneAdmin::creerAvecRole($page, $user, $validated['role'], $request->user());

        return back()->with('success', 'Delegue ajoute.');
    }

    public function updateDelegue(Request $request, string $codeInsee, int $id)
    {
        $page = $this->resolveAdminPage($codeInsee, 'peut_deleguer');
        $admin = $page->admins()->findOrFail($id);

        if ($admin->est_maire) {
            return back()->with('error', 'Impossible de modifier le role du maire.');
        }

        $validated = $request->validate([
            'role' => 'nullable|string|in:adjoint,delegue,communication',
            'peut_publier_actus' => 'boolean',
            'peut_gerer_evenements' => 'boolean',
            'peut_envoyer_notifications' => 'boolean',
            'peut_modifier_page' => 'boolean',
        ]);

        $admin->update($validated);

        return back()->with('success', 'Permissions mises a jour.');
    }

    public function supprimerDelegue(string $codeInsee, int $id)
    {
        $page = $this->resolveAdminPage($codeInsee, 'peut_deleguer');
        $admin = $page->admins()->findOrFail($id);

        if ($admin->est_maire) {
            return back()->with('error', 'Impossible de supprimer le maire.');
        }

        $admin->delete();

        return back()->with('success', 'Delegue supprime.');
    }

    public function notifications(string $codeInsee): Response
    {
        $page = $this->resolveAdminPage($codeInsee, 'peut_envoyer_notifications');

        return Inertia::render('Commune/Admin/Notifications', [
            'ville' => $this->formatVilleAdmin($page),
            'abonnes_count' => $page->abonnes_count,
        ]);
    }

    public function envoyerNotification(Request $request, string $codeInsee)
    {
        $page = $this->resolveAdminPage($codeInsee, 'peut_envoyer_notifications');

        $validated = $request->validate([
            'sujet' => 'required|string|max:255',
            'contenu' => 'required|string|max:2000',
            'type' => 'required|string|in:info,evenement,alerte,urgence',
            'cible' => 'required|string|in:tous,email_only,app_only',
        ]);

        // TODO: dispatch job for mass notification
        return back()->with('success', 'Notification envoyee a ' . $page->abonnes_count . ' abonnes.');
    }

    public function analytics(string $codeInsee): Response
    {
        $page = $this->resolveAdminPage($codeInsee);

        $topArticles = $page->articles()
            ->publies()
            ->orderByDesc('vues_count')
            ->limit(5)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'titre' => $a->titre,
                'categorie' => $a->categorie,
                'vues_count' => $a->vues_count,
            ]);

        $topEvenements = $page->evenements()
            ->publies()
            ->withCount('inscriptions')
            ->orderByDesc('inscriptions_count')
            ->limit(5)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'titre' => $e->titre,
                'date_debut' => $e->date_debut->toISOString(),
                'inscrits_count' => $e->inscriptions_count,
            ]);

        return Inertia::render('Commune/Admin/Analytics', [
            'ville' => $this->formatVilleAdmin($page),
            'stats' => [
                'vues_totales' => $page->vues_totales,
                'abonnes_count' => $page->abonnes_count,
                'articles_count' => $page->articles()->publies()->count(),
                'evenements_count' => $page->evenements()->publies()->aVenir()->count(),
            ],
            'evolution' => [
                'vues' => [],
                'abonnes' => [],
            ],
            'top_articles' => $topArticles,
            'top_evenements' => $topEvenements,
        ]);
    }

    // ========================================================================
    // GALERIE
    // ========================================================================

    public function galerie(string $codeInsee): Response
    {
        $page = $this->resolveAdminPage($codeInsee, 'peut_modifier_page');

        $images = $page->galerieImages()
            ->orderBy('ordre')
            ->get()
            ->map(fn ($img) => [
                'id' => $img->id,
                'image_url' => $img->image_url,
                'legende' => $img->legende,
                'credit' => $img->credit,
                'ordre' => $img->ordre,
                'source' => $img->source,
                'visible' => $img->visible,
            ]);

        return Inertia::render('Commune/Admin/Galerie', [
            'ville' => $this->formatVilleAdmin($page),
            'images' => $images,
        ]);
    }

    public function uploadImage(Request $request, string $codeInsee)
    {
        $page = $this->resolveAdminPage($codeInsee, 'peut_modifier_page');

        $request->validate([
            'images' => 'required|array|max:10',
            'images.*' => 'image|max:4096',
        ]);

        $maxOrdre = $page->galerieImages()->max('ordre') ?? 0;

        foreach ($request->file('images') as $file) {
            $path = $file->store("communes/{$codeInsee}/galerie", 'public');
            $maxOrdre++;

            CommuneGalerieImage::create([
                'commune_page_id' => $page->id,
                'image_path' => $path,
                'source' => 'upload',
                'ordre' => $maxOrdre,
                'legende' => $request->input('legende'),
            ]);
        }

        return back()->with('success', count($request->file('images')).' image(s) ajoutee(s).');
    }

    public function updateImage(Request $request, string $codeInsee, int $id)
    {
        $page = $this->resolveAdminPage($codeInsee, 'peut_modifier_page');
        $image = $page->galerieImages()->findOrFail($id);

        $validated = $request->validate([
            'legende' => 'nullable|string|max:255',
            'credit' => 'nullable|string|max:255',
            'visible' => 'boolean',
        ]);

        $image->update($validated);

        return back()->with('success', 'Image mise a jour.');
    }

    public function reorderImages(Request $request, string $codeInsee)
    {
        $page = $this->resolveAdminPage($codeInsee, 'peut_modifier_page');

        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:commune_galerie_images,id',
        ]);

        foreach ($request->input('order') as $index => $imageId) {
            $page->galerieImages()->where('id', $imageId)->update(['ordre' => $index]);
        }

        return back()->with('success', 'Ordre mis a jour.');
    }

    public function deleteImage(string $codeInsee, int $id)
    {
        $page = $this->resolveAdminPage($codeInsee, 'peut_modifier_page');
        $image = $page->galerieImages()->findOrFail($id);

        if ($image->image_path) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        return back()->with('success', 'Image supprimee.');
    }

    // ========================================================================
    // HELPERS
    // ========================================================================

    private function resolveAdminPage(string $codeInsee, ?string $permission = null): CommunePage
    {
        $page = CommunePage::with('ville')->where('code_insee', $codeInsee)->firstOrFail();
        $user = auth()->user();

        if (! $page->estAdministrePar($user) && ! $user->hasRole('admin')) {
            abort(403);
        }

        if ($permission) {
            $admin = $page->admins()->where('user_id', $user->id)->first();
            if ($admin && ! $admin->peutFaire($permission) && ! $user->hasRole('admin')) {
                abort(403, 'Permission insuffisante.');
            }
        }

        return $page;
    }

    private function formatVilleAdmin(CommunePage $page): array
    {
        return [
            'nom' => $page->ville->nom,
            'code_insee' => $page->code_insee,
            'slug' => $page->ville->slug,
        ];
    }

    private function formatPageAdmin(CommunePage $page): array
    {
        return [
            'id' => $page->id,
            'statut' => $page->statut,
            'description_courte' => $page->description_courte,
            'mot_du_maire' => $page->mot_du_maire,
            'couleur_primaire' => $page->couleur_primaire,
            'couleur_secondaire' => $page->couleur_secondaire,
            'telephone' => $page->telephone,
            'email_mairie' => $page->email_mairie,
            'adresse_mairie' => $page->adresse_mairie,
            'site_officiel' => $page->site_officiel,
            'facebook_url' => $page->facebook_url,
            'twitter_url' => $page->twitter_url,
            'instagram_url' => $page->instagram_url,
            'youtube_url' => $page->youtube_url,
            'linkedin_url' => $page->linkedin_url,
            'logo_url' => $page->logo_url,
            'image_couverture_url' => $page->image_couverture_url,
            'actus_actives' => $page->actus_actives,
            'evenements_actifs' => $page->evenements_actifs,
            'forum_actif' => $page->forum_actif,
            'notifications_actives' => $page->notifications_actives,
        ];
    }
}
