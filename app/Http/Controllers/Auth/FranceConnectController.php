<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\FranceConnectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FranceConnectController extends Controller
{
    public function __construct(
        protected FranceConnectService $franceConnectService
    ) {}

    /**
     * Rediriger vers FranceConnect
     */
    public function redirect()
    {
        return $this->franceConnectService->redirectToProvider();
    }

    /**
     * Gérer le callback de FranceConnect
     */
    public function callback()
    {
        try {
            $user = $this->franceConnectService->handleCallback();

            Auth::login($user, true);

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Connexion réussie via FranceConnect+');
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Erreur lors de l\'authentification FranceConnect+: ' . $e->getMessage());
        }
    }

    /**
     * Déconnecter de FranceConnect
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        // Déconnexion Laravel
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Si l'utilisateur était connecté via FranceConnect, rediriger vers logout FC
        if ($user && $this->franceConnectService->isConnectedWithFranceConnect($user)) {
            $logoutUrl = $this->franceConnectService->logout();
            return redirect()->away($logoutUrl);
        }

        return redirect()->route('home');
    }
}
