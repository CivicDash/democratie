<?php

namespace App\Services;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Service FranceConnect+ pour authentification État français
 * 
 * FranceConnect+ est le système d'authentification de l'État français
 * permettant aux citoyens de s'identifier avec leurs comptes administratifs.
 * 
 * @see https://franceconnect.gouv.fr/
 */
class FranceConnectService
{
    /**
     * Rediriger vers FranceConnect pour authentification
     */
    public function redirectToProvider(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('franceconnect')
            ->scopes([
                'openid',
                'given_name',
                'family_name',
                'email',
                'preferred_username',
                'birthdate',
                'gender',
                'birthplace',
                'birthcountry',
            ])
            ->redirect();
    }

    /**
     * Gérer le callback de FranceConnect
     */
    public function handleCallback(): User
    {
        $socialiteUser = Socialite::driver('franceconnect')->user();

        return $this->findOrCreateUser($socialiteUser);
    }

    /**
     * Trouver ou créer un utilisateur depuis FranceConnect
     */
    protected function findOrCreateUser(SocialiteUser $socialiteUser): User
    {
        return DB::transaction(function () use ($socialiteUser) {
            // Chercher l'utilisateur par sub (identifiant unique FranceConnect)
            $user = User::where('franceconnect_sub', $socialiteUser->getId())->first();

            if ($user) {
                // Mettre à jour les données si elles ont changé
                $this->updateUserFromFranceConnect($user, $socialiteUser);
                return $user;
            }

            // Créer un nouvel utilisateur
            return $this->createUserFromFranceConnect($socialiteUser);
        });
    }

    /**
     * Créer un utilisateur depuis les données FranceConnect
     */
    protected function createUserFromFranceConnect(SocialiteUser $socialiteUser): User
    {
        $user = User::create([
            'name' => $this->formatName($socialiteUser),
            'email' => $socialiteUser->getEmail(),
            'franceconnect_sub' => $socialiteUser->getId(),
            'email_verified_at' => now(), // Auto-vérifié par FranceConnect
            'password' => bcrypt(Str::random(32)), // Mot de passe aléatoire (non utilisé)
        ]);

        // Créer le profil avec les données d'identité
        $user->profile()->create([
            'given_name' => $socialiteUser->user['given_name'] ?? null,
            'family_name' => $socialiteUser->user['family_name'] ?? null,
            'birthdate' => $socialiteUser->user['birthdate'] ?? null,
            'gender' => $socialiteUser->user['gender'] ?? null,
            'birthplace' => $socialiteUser->user['birthplace'] ?? null,
            'birthcountry' => $socialiteUser->user['birthcountry'] ?? null,
            'franceconnect_data' => json_encode($socialiteUser->user),
        ]);

        // Assigner le rôle citoyen par défaut
        $user->assignRole('citizen');

        return $user;
    }

    /**
     * Mettre à jour un utilisateur depuis les données FranceConnect
     */
    protected function updateUserFromFranceConnect(User $user, SocialiteUser $socialiteUser): void
    {
        $user->update([
            'name' => $this->formatName($socialiteUser),
            'email' => $socialiteUser->getEmail(),
        ]);

        // Mettre à jour le profil
        if ($user->profile) {
            $user->profile->update([
                'given_name' => $socialiteUser->user['given_name'] ?? null,
                'family_name' => $socialiteUser->user['family_name'] ?? null,
                'franceconnect_data' => json_encode($socialiteUser->user),
            ]);
        }
    }

    /**
     * Formater le nom complet
     */
    protected function formatName(SocialiteUser $socialiteUser): string
    {
        $givenName = $socialiteUser->user['given_name'] ?? '';
        $familyName = $socialiteUser->user['family_name'] ?? '';

        return trim("{$givenName} {$familyName}") ?: $socialiteUser->getName();
    }

    /**
     * Vérifier si l'utilisateur est connecté via FranceConnect
     */
    public function isConnectedWithFranceConnect(User $user): bool
    {
        return !empty($user->franceconnect_sub);
    }

    /**
     * Déconnecter un utilisateur de FranceConnect (logout FranceConnect)
     */
    public function logout(): string
    {
        // URL de logout FranceConnect
        $logoutUrl = config('services.franceconnect.logout_url');
        $redirectUri = route('home');

        return "{$logoutUrl}?post_logout_redirect_uri=" . urlencode($redirectUri);
    }
}

