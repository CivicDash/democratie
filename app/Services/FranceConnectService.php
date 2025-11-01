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
            'name' => Profile::generateDisplayName(), // Pseudonyme anonyme par défaut
            'email' => $socialiteUser->getEmail(),
            'franceconnect_sub' => $socialiteUser->getId(),
            'email_verified_at' => now(), // Auto-vérifié par FranceConnect
            'password' => bcrypt(Str::random(32)), // Mot de passe aléatoire (non utilisé)
        ]);

        // Créer le profil avec anonymisation RGPD
        $user->profile()->create([
            'display_name' => Profile::generateDisplayName(),
            'citizen_ref_hash' => Profile::hashCitizenRef($socialiteUser->getId()),
            'scope' => 'national',
            'is_verified' => true,
            'is_public_figure' => false, // Citoyen anonyme par défaut
            'verified_at' => now(),
            // Chiffrer données sensibles FranceConnect+ (RGPD Art. 32)
            'encrypted_fc_data' => $this->encryptFranceConnectData($socialiteUser->user),
            'encrypted_real_name' => encrypt($this->formatName($socialiteUser)),
            'encrypted_real_email' => encrypt($socialiteUser->getEmail()),
        ]);

        // Assigner le rôle citoyen par défaut
        $user->assignRole('citizen');

        // Consentement automatique traitement données FranceConnect+
        $user->grantConsent(
            UserConsent::TYPE_FRANCECONNECT_DATA,
            PolicyVersion::getCurrentVersionNumber(PolicyVersion::TYPE_PRIVACY)
        );

        return $user;
    }

    /**
     * Mettre à jour un utilisateur depuis les données FranceConnect
     */
    protected function updateUserFromFranceConnect(User $user, SocialiteUser $socialiteUser): void
    {
        // Ne pas mettre à jour users.name (rester anonyme)
        // Seulement email si changé
        $user->update([
            'email' => $socialiteUser->getEmail(),
        ]);

        // Mettre à jour le profil avec données chiffrées
        if ($user->profile) {
            $user->profile->update([
                'encrypted_fc_data' => $this->encryptFranceConnectData($socialiteUser->user),
                'encrypted_real_name' => encrypt($this->formatName($socialiteUser)),
                'encrypted_real_email' => encrypt($socialiteUser->getEmail()),
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

    /**
     * Chiffre les données FranceConnect+ (RGPD Art. 32)
     * 
     * Conforme minimisation des données : Ne stocke que ce qui est nécessaire
     * - birthdate : Pour vérification majorité si nécessaire
     * - gender : Pour statistiques anonymes
     * - birthplace/birthcountry : Pour audit anti-fraude uniquement
     * 
     * Note : given_name/family_name → encrypted_real_name (séparé)
     */
    protected function encryptFranceConnectData(array $fcData): string
    {
        $dataToEncrypt = [
            'birthdate' => $fcData['birthdate'] ?? null,
            'gender' => $fcData['gender'] ?? null,
            'birthplace' => $fcData['birthplace'] ?? null,
            'birthcountry' => $fcData['birthcountry'] ?? null,
            'preferred_username' => $fcData['preferred_username'] ?? null,
            // Timestamp du chiffrement pour audit
            'encrypted_at' => now()->toIso8601String(),
        ];

        // Filtrer valeurs nulles (minimisation)
        $dataToEncrypt = array_filter($dataToEncrypt, fn ($value) => $value !== null);

        return encrypt(json_encode($dataToEncrypt));
    }
}

