<?php

namespace App\Services;

use App\Mail\AccountBannedMail;
use App\Mail\AccountSuspendedMail;
use App\Models\User;
use App\Models\UserSanction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class UserSanctionService
{
    /**
     * Suspendre un utilisateur temporairement
     */
    public function suspend(User $user, int $days, string $reason, ?User $moderator = null): UserSanction
    {
        $moderator = $moderator ?? Auth::user();
        $startsAt = now();
        $endsAt = now()->addDays($days);

        // Désactiver les sanctions précédentes
        UserSanction::where('user_id', $user->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Créer la nouvelle sanction
        $sanction = UserSanction::create([
            'user_id' => $user->id,
            'moderator_id' => $moderator?->id,
            'type' => UserSanction::TYPE_SUSPENSION,
            'reason' => $reason,
            'duration_days' => $days,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_active' => true,
        ]);

        // Mettre à jour l'utilisateur
        $user->update([
            'account_status' => 'suspended',
            'suspended_at' => $startsAt,
            'suspended_until' => $endsAt,
            'suspension_reason' => $reason,
            'suspended_by' => $moderator?->id,
            'suspension_count' => $user->suspension_count + 1,
        ]);

        // Envoyer l'email de notification
        try {
            Mail::to($user->email)->send(new AccountSuspendedMail(
                $user,
                $days,
                $reason,
                $endsAt
            ));
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email suspension: '.$e->getMessage());
        }

        return $sanction;
    }

    /**
     * Bannir un utilisateur définitivement
     */
    public function ban(User $user, string $reason, ?User $moderator = null): UserSanction
    {
        $moderator = $moderator ?? Auth::user();

        // Désactiver les sanctions précédentes
        UserSanction::where('user_id', $user->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Créer la nouvelle sanction
        $sanction = UserSanction::create([
            'user_id' => $user->id,
            'moderator_id' => $moderator?->id,
            'type' => UserSanction::TYPE_BAN,
            'reason' => $reason,
            'duration_days' => null, // Permanent
            'starts_at' => now(),
            'ends_at' => null,
            'is_active' => true,
        ]);

        // Mettre à jour l'utilisateur
        $user->update([
            'account_status' => 'banned',
            'suspended_at' => now(),
            'suspended_until' => null,
            'suspension_reason' => $reason,
            'suspended_by' => $moderator?->id,
        ]);

        // Envoyer l'email de notification
        try {
            Mail::to($user->email)->send(new AccountBannedMail(
                $user,
                $reason
            ));
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email bannissement: '.$e->getMessage());
        }

        return $sanction;
    }

    /**
     * Lever une sanction
     */
    public function unban(User $user, string $reason = 'Sanction levée', ?User $moderator = null): void
    {
        $moderator = $moderator ?? Auth::user();

        // Désactiver toutes les sanctions actives
        UserSanction::where('user_id', $user->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Créer un enregistrement de levée
        UserSanction::create([
            'user_id' => $user->id,
            'moderator_id' => $moderator?->id,
            'type' => UserSanction::TYPE_UNBAN,
            'reason' => $reason,
            'starts_at' => now(),
            'is_active' => false,
        ]);

        // Réactiver le compte
        $user->update([
            'account_status' => 'active',
            'suspended_at' => null,
            'suspended_until' => null,
            'suspension_reason' => null,
            'suspended_by' => null,
        ]);
    }

    /**
     * Avertissement (sans suspension)
     */
    public function warn(User $user, string $reason, ?User $moderator = null): UserSanction
    {
        $moderator = $moderator ?? Auth::user();

        return UserSanction::create([
            'user_id' => $user->id,
            'moderator_id' => $moderator?->id,
            'type' => UserSanction::TYPE_WARNING,
            'reason' => $reason,
            'starts_at' => now(),
            'is_active' => true,
        ]);
    }
}
