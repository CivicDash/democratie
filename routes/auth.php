<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorAuthController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    // Two-Factor Authentication (2FA)
    Route::prefix('two-factor')->name('two-factor.')->group(function () {
        // Page principale 2FA (affiche l'état)
        Route::get('/', [TwoFactorAuthController::class, 'show'])
            ->name('show');

        // Activer la 2FA (génère le secret et affiche le QR code)
        Route::get('/enable', [TwoFactorAuthController::class, 'enable'])
            ->name('enable');

        // Confirmer l'activation avec un code OTP
        Route::post('/confirm', [TwoFactorAuthController::class, 'confirm'])
            ->name('confirm');

        // Afficher les codes de récupération
        Route::get('/recovery-codes', [TwoFactorAuthController::class, 'recoveryCodes'])
            ->name('recovery-codes');

        // Régénérer les codes de récupération
        Route::post('/regenerate-recovery-codes', [TwoFactorAuthController::class, 'regenerateRecoveryCodes'])
            ->name('regenerate-recovery-codes');

        // Désactiver la 2FA
        Route::delete('/disable', [TwoFactorAuthController::class, 'disable'])
            ->name('disable');

        // Challenge 2FA (lors de la connexion)
        Route::get('/challenge', [TwoFactorAuthController::class, 'challenge'])
            ->name('challenge');

        // Vérifier le code 2FA
        Route::post('/verify', [TwoFactorAuthController::class, 'verify'])
            ->name('verify');
    });
});
