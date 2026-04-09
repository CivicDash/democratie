<?php

namespace App\Http\Middleware;

use App\Models\CommunePage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ResolveCommuneSubdomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $baseDomain = config('app.commune_domain', 'civicdash.fr');

        if (! str_ends_with($host, '.'.$baseDomain)) {
            return $next($request);
        }

        $subdomain = str_replace('.'.$baseDomain, '', $host);

        if (in_array($subdomain, ['www', 'api', 'admin', 'mail', 'demo'])) {
            return $next($request);
        }

        $communePage = Cache::remember(
            "commune_subdomain:{$subdomain}",
            3600,
            function () use ($subdomain) {
                return CommunePage::with(['ville' => function ($q) {
                    $q->select('id', 'code_insee', 'nom', 'slug', 'departement_nom', 'region_nom', 'population', 'blason_url');
                }])
                    ->whereHas('ville', fn ($q) => $q->where('slug', $subdomain))
                    ->first();
            }
        );

        if (! $communePage) {
            abort(404, 'Commune introuvable');
        }

        $request->attributes->set('commune_page', $communePage);
        $request->attributes->set('commune_code_insee', $communePage->code_insee);

        Inertia::share('communePage', fn () => [
            'id' => $communePage->id,
            'code_insee' => $communePage->code_insee,
            'statut' => $communePage->statut,
            'est_active' => $communePage->est_active,
            'couleur_primaire' => $communePage->couleur_primaire,
            'couleur_secondaire' => $communePage->couleur_secondaire,
            'logo_url' => $communePage->logo_url,
            'image_couverture_url' => $communePage->image_couverture_url,
            'ville' => [
                'nom' => $communePage->ville->nom,
                'slug' => $communePage->ville->slug,
                'departement' => $communePage->ville->departement_nom,
                'region' => $communePage->ville->region_nom,
                'population' => $communePage->ville->population,
                'blason_url' => $communePage->ville->blason_url,
            ],
            'fonctionnalites' => [
                'actus' => $communePage->actus_actives,
                'evenements' => $communePage->evenements_actifs,
                'forum' => $communePage->forum_actif,
                'notifications' => $communePage->notifications_actives,
            ],
        ]);

        $communePage->incrementerVues();

        return $next($request);
    }
}
