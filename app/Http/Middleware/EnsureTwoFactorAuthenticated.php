<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorAuthenticated
{
    /**
     * Routes exclues de la vérification 2FA
     */
    protected array $except = [
        'two-factor.*',
        'logout',
        'password.*',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Pas d'utilisateur connecté
        if (! $user) {
            return $next($request);
        }

        // 2FA non activée pour cet utilisateur
        if (! $user->two_factor_enabled) {
            return $next($request);
        }

        // Route exclue
        if ($this->shouldPassThrough($request)) {
            return $next($request);
        }

        // Déjà authentifié 2FA dans cette session
        if ($request->session()->get('two_factor_authenticated', false)) {
            return $next($request);
        }

        // Utilisateur connecté via FranceConnect (déjà authentifié via l'État)
        if ($user->franceconnect_sub !== null) {
            return $next($request);
        }

        // Rediriger vers la page de challenge 2FA
        return redirect()->route('two-factor.challenge');
    }

    /**
     * Vérifie si la route actuelle est exclue
     */
    protected function shouldPassThrough(Request $request): bool
    {
        foreach ($this->except as $except) {
            if ($request->routeIs($except)) {
                return true;
            }
        }

        return false;
    }
}
