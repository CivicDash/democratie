<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthController extends Controller
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Affiche la page de configuration 2FA
     */
    public function show(Request $request): Response
    {
        $user = $request->user();
        
        return Inertia::render('Auth/TwoFactor/Index', [
            'enabled' => $user->two_factor_enabled,
            'confirmedAt' => $user->two_factor_confirmed_at,
            'isEluOrAdmin' => $user->is_verified_elu || $user->hasRole('admin') || $user->hasRole('super-admin'),
            'hasPassword' => !empty($user->password) && $user->franceconnect_sub === null,
        ]);
    }

    /**
     * Initialise la configuration 2FA (génère le secret)
     */
    public function enable(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        
        // Si déjà activé, rediriger
        if ($user->two_factor_enabled) {
            return redirect()->route('two-factor.show')
                ->with('info', 'La double authentification est déjà activée.');
        }
        
        // Générer un nouveau secret
        $secret = $this->google2fa->generateSecretKey();
        
        // Sauvegarder temporairement le secret (non confirmé)
        $user->two_factor_secret = Crypt::encryptString($secret);
        $user->two_factor_enabled = false;
        $user->save();
        
        // Générer le QR code URL
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name', 'CivicDash'),
            $user->email,
            $secret
        );
        
        // Générer le QR code SVG
        $qrCodeSvg = $this->generateQrCodeSvg($qrCodeUrl);
        
        return Inertia::render('Auth/TwoFactor/Enable', [
            'secret' => $secret,
            'qrCodeSvg' => $qrCodeSvg,
        ]);
    }

    /**
     * Confirme l'activation de la 2FA avec un code OTP
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);
        
        $user = $request->user();
        
        if (!$user->two_factor_secret) {
            return back()->withErrors([
                'code' => 'Veuillez d\'abord initialiser la configuration 2FA.',
            ]);
        }
        
        // Décrypter le secret
        $secret = Crypt::decryptString($user->two_factor_secret);
        
        // Vérifier le code OTP
        $valid = $this->google2fa->verifyKey($secret, $request->code);
        
        if (!$valid) {
            return back()->withErrors([
                'code' => 'Le code de vérification est incorrect. Veuillez réessayer.',
            ]);
        }
        
        // Générer les codes de récupération
        $recoveryCodes = $this->generateRecoveryCodes();
        
        // Activer la 2FA
        $user->two_factor_enabled = true;
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = Crypt::encryptString(json_encode($recoveryCodes));
        $user->save();
        
        return redirect()->route('two-factor.recovery-codes')
            ->with('success', 'Double authentification activée avec succès !');
    }

    /**
     * Affiche les codes de récupération
     */
    public function recoveryCodes(Request $request): Response
    {
        $user = $request->user();
        
        if (!$user->two_factor_enabled || !$user->two_factor_recovery_codes) {
            return redirect()->route('two-factor.show');
        }
        
        $recoveryCodes = json_decode(
            Crypt::decryptString($user->two_factor_recovery_codes),
            true
        );
        
        return Inertia::render('Auth/TwoFactor/RecoveryCodes', [
            'recoveryCodes' => $recoveryCodes,
            'justEnabled' => session('success') === 'Double authentification activée avec succès !',
        ]);
    }

    /**
     * Régénère les codes de récupération
     */
    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        if (!$user->two_factor_enabled) {
            return redirect()->route('two-factor.show');
        }
        
        $recoveryCodes = $this->generateRecoveryCodes();
        
        $user->two_factor_recovery_codes = Crypt::encryptString(json_encode($recoveryCodes));
        $user->save();
        
        return redirect()->route('two-factor.recovery-codes')
            ->with('success', 'Codes de récupération régénérés.');
    }

    /**
     * Désactive la 2FA
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);
        
        $user = $request->user();
        
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_enabled = false;
        $user->save();
        
        return redirect()->route('two-factor.show')
            ->with('success', 'Double authentification désactivée.');
    }

    /**
     * Affiche le formulaire de vérification 2FA lors de la connexion
     */
    public function challenge(): Response
    {
        return Inertia::render('Auth/TwoFactor/Challenge');
    }

    /**
     * Vérifie le code 2FA lors de la connexion
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);
        
        $user = $request->user();
        
        // Vérifier si c'est un code de récupération
        if (strlen($request->code) > 6) {
            return $this->verifyRecoveryCode($request, $user);
        }
        
        // Vérifier le code OTP
        $secret = Crypt::decryptString($user->two_factor_secret);
        $valid = $this->google2fa->verifyKey($secret, $request->code);
        
        if (!$valid) {
            return back()->withErrors([
                'code' => 'Le code de vérification est incorrect.',
            ]);
        }
        
        // Marquer la 2FA comme validée pour cette session
        $request->session()->put('two_factor_authenticated', true);
        
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Vérifie un code de récupération
     */
    protected function verifyRecoveryCode(Request $request, $user): RedirectResponse
    {
        $recoveryCodes = json_decode(
            Crypt::decryptString($user->two_factor_recovery_codes),
            true
        );
        
        $code = $request->code;
        
        if (!in_array($code, $recoveryCodes)) {
            return back()->withErrors([
                'code' => 'Le code de récupération est invalide.',
            ]);
        }
        
        // Supprimer le code utilisé
        $recoveryCodes = array_values(array_filter($recoveryCodes, fn($c) => $c !== $code));
        
        $user->two_factor_recovery_codes = Crypt::encryptString(json_encode($recoveryCodes));
        $user->save();
        
        // Marquer la 2FA comme validée pour cette session
        $request->session()->put('two_factor_authenticated', true);
        
        return redirect()->intended(route('dashboard'))
            ->with('warning', 'Vous avez utilisé un code de récupération. Il vous reste ' . count($recoveryCodes) . ' codes.');
    }

    /**
     * Génère des codes de récupération
     */
    protected function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(4) . '-' . Str::random(4));
        }
        return $codes;
    }

    /**
     * Génère un QR code SVG
     */
    protected function generateQrCodeSvg(string $content): string
    {
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        
        $writer = new \BaconQrCode\Writer($renderer);
        
        return $writer->writeString($content);
    }
}
