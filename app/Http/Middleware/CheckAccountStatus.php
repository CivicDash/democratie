<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountStatus
{
    /**
     * Vérifie si le compte utilisateur est suspendu ou banni
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        // Vérifier le statut du compte
        if ($user->account_status === 'banned') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('account.banned')->with([
                'reason' => $user->suspension_reason,
                'banned_at' => $user->suspended_at?->format('d/m/Y'),
            ]);
        }

        if ($user->account_status === 'suspended') {
            // Vérifier si la suspension est expirée
            if ($user->suspended_until && $user->suspended_until->isPast()) {
                // Lever automatiquement la suspension
                $user->update([
                    'account_status' => 'active',
                    'suspended_at' => null,
                    'suspended_until' => null,
                    'suspension_reason' => null,
                    'suspended_by' => null,
                ]);

                return $next($request);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('account.suspended')->with([
                'reason' => $user->suspension_reason,
                'suspended_until' => $user->suspended_until?->format('d/m/Y H:i'),
                'remaining' => $user->suspended_until?->diffForHumans(['parts' => 2]),
            ]);
        }

        return $next($request);
    }
}
