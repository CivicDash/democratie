<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource API pour exposition publique des utilisateurs
 * 
 * RGPD Art. 5 (minimisation des données)
 * 
 * OBJECTIF : Anonymiser les citoyens standards, afficher nom réel pour comptes publics
 * 
 * BLACKLIST (JAMAIS exposé) :
 * - users.email
 * - users.password
 * - users.franceconnect_sub
 * - profiles.citizen_ref_hash
 * - profiles.encrypted_*
 * 
 * WHITELIST (Exposé selon contexte) :
 * - id : ID utilisateur (public)
 * - display_name : Pseudonyme anonyme OU nom réel (selon is_public_figure)
 * - roles : Rôles de l'utilisateur
 * - is_verified : Vérification FranceConnect+
 * - is_public_figure : Badge transparent/anonyme
 */
class UserPublicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Récupérer le display_name (anonyme ou réel selon is_public_figure)
        $displayName = $this->profile && !$this->profile->is_public_figure
            ? $this->profile->display_name
            : $this->name;

        return [
            'id' => $this->id,
            
            // Display name : Citoyen1234 (anonyme) OU Nom Prénom (public)
            'display_name' => $displayName,
            
            // Badges et vérifications
            'is_verified' => $this->profile?->is_verified ?? false,
            'is_public_figure' => $this->profile?->is_public_figure ?? false,
            
            // Rôles (pour afficher badge Journaliste, État, etc.)
            'roles' => $this->roles->pluck('name'),
            
            // Statistiques publiques (gamification)
            'stats' => [
                'level' => $this->whenLoaded('userStats', function () {
                    return $this->userStats->level ?? 1;
                }),
                'xp' => $this->whenLoaded('userStats', function () {
                    return $this->userStats->xp ?? 0;
                }),
                'posts_count' => $this->whenCounted('posts'),
                'topics_count' => $this->whenCounted('topics'),
            ],
            
            // Dates (public)
            'member_since' => $this->created_at->format('Y-m-d'),
            
            // ===== BLACKLIST : JAMAIS exposé =====
            // ❌ $this->email
            // ❌ $this->name (si anonyme)
            // ❌ $this->franceconnect_sub
            // ❌ $this->profile->citizen_ref_hash
            // ❌ $this->profile->encrypted_*
        ];
    }

    /**
     * Variante complète pour profil utilisateur (self)
     * Accessible uniquement par l'utilisateur lui-même
     */
    public function toArraySelf(Request $request): array
    {
        $publicData = $this->toArray($request);

        return array_merge($publicData, [
            // Données privées (self uniquement)
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            
            // Profil complet
            'profile' => [
                'scope' => $this->profile?->scope,
                'region_id' => $this->profile?->region_id,
                'department_id' => $this->profile?->department_id,
                'verified_at' => $this->profile?->verified_at,
            ],
            
            // Consentements RGPD
            'consents' => $this->whenLoaded('consents', function () {
                return $this->consents->map(fn ($consent) => [
                    'type' => $consent->consent_type,
                    'granted' => $consent->is_granted,
                    'granted_at' => $consent->granted_at,
                    'policy_version' => $consent->policy_version,
                ]);
            }),
            
            // Statistiques privées
            'private_stats' => [
                'notifications_count' => $this->whenLoaded('notifications', function () {
                    return $this->notifications()->unread()->count();
                }),
            ],
        ]);
    }

    /**
     * Variante admin pour accès complet (audit)
     * Accessible uniquement par les admins
     */
    public function toArrayAdmin(Request $request): array
    {
        return array_merge($this->toArraySelf($request), [
            // Données sensibles (admin uniquement)
            'franceconnect_sub' => $this->franceconnect_sub,
            'citizen_ref_hash' => $this->profile?->citizen_ref_hash,
            
            // Notes : encrypted_* ne sont PAS déchiffrées ici
            // Nécessite action admin explicite avec justification audit
            'has_encrypted_data' => !empty($this->profile?->encrypted_fc_data),
            
            // Audit trail
            'last_login_at' => $this->last_login_at ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
    }
}
