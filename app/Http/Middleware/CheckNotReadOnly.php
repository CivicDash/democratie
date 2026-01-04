<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckNotReadOnly
{
    /**
     * Handle an incoming request.
     * Bloque les actions d'écriture pour les comptes en lecture seule (démo)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isReadOnly()) {
            // Pour les requêtes AJAX/API
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les comptes de démonstration sont en lecture seule. Créez un compte pour participer.',
                    'is_demo' => true,
                ], 403);
            }

            // Pour les requêtes web
            return redirect()->back()
                ->with('error', 'Les comptes de démonstration sont en lecture seule. Créez un compte pour participer.')
                ->with('is_demo', true);
        }

        return $next($request);
    }
}
