<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSanction;
use App\Services\UserSanctionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserSanctionController extends Controller
{
    protected UserSanctionService $sanctionService;

    public function __construct(UserSanctionService $sanctionService)
    {
        $this->sanctionService = $sanctionService;
    }

    /**
     * Historique des sanctions d'un utilisateur
     */
    public function history(User $user)
    {
        $sanctions = UserSanction::with(['moderator:id,name'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'type' => $s->type,
                'type_label' => $s->getTypeLabel(),
                'reason' => $s->reason,
                'duration_days' => $s->duration_days,
                'starts_at' => $s->starts_at->format('d/m/Y H:i'),
                'ends_at' => $s->ends_at?->format('d/m/Y H:i'),
                'remaining' => $s->getRemainingTime(),
                'is_active' => $s->is_active,
                'is_expired' => $s->isExpired(),
                'moderator' => $s->moderator?->name ?? 'Système',
                'created_at' => $s->created_at->format('d/m/Y H:i'),
            ]);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account_status' => $user->account_status,
                'suspension_count' => $user->suspension_count,
            ],
            'sanctions' => $sanctions,
        ]);
    }

    /**
     * Suspendre un utilisateur
     */
    public function suspend(Request $request, User $user)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365',
            'reason' => 'required|string|max:1000',
        ]);

        // Empêcher de suspendre un admin
        if ($user->hasRole('admin')) {
            return back()->with('error', 'Impossible de suspendre un administrateur.');
        }

        // Empêcher de se suspendre soi-même
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas vous suspendre vous-même.');
        }

        $this->sanctionService->suspend($user, $request->days, $request->reason);

        return back()->with('success', "{$user->name} a été suspendu pour {$request->days} jour(s).");
    }

    /**
     * Bannir un utilisateur définitivement
     */
    public function ban(Request $request, User $user)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        // Empêcher de bannir un admin
        if ($user->hasRole('admin')) {
            return back()->with('error', 'Impossible de bannir un administrateur.');
        }

        // Empêcher de se bannir soi-même
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas vous bannir vous-même.');
        }

        $this->sanctionService->ban($user, $request->reason);

        return back()->with('success', "{$user->name} a été banni définitivement.");
    }

    /**
     * Lever une sanction
     */
    public function unban(Request $request, User $user)
    {
        $reason = $request->input('reason', 'Sanction levée par un administrateur');

        $this->sanctionService->unban($user, $reason);

        return back()->with('success', "La sanction de {$user->name} a été levée.");
    }

    /**
     * Supprimer un compte utilisateur (soft delete)
     */
    public function delete(Request $request, User $user)
    {
        // Empêcher de supprimer un admin
        if ($user->hasRole('admin')) {
            return back()->with('error', 'Impossible de supprimer un administrateur.');
        }

        // Empêcher de se supprimer soi-même
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $userName = $user->name;
        
        // Soft delete
        $user->update(['account_status' => 'deleted']);
        $user->delete();

        return back()->with('success', "Le compte de {$userName} a été supprimé.");
    }

    /**
     * Supprimer définitivement (force delete) - Admin uniquement
     */
    public function forceDelete(Request $request, $userId)
    {
        $user = User::withTrashed()->findOrFail($userId);

        // Empêcher de supprimer un admin
        if ($user->hasRole('admin')) {
            return back()->with('error', 'Impossible de supprimer définitivement un administrateur.');
        }

        $userName = $user->name;
        
        // Force delete
        $user->forceDelete();

        return back()->with('success', "Le compte de {$userName} a été supprimé définitivement.");
    }

    /**
     * Restaurer un compte supprimé
     */
    public function restore($userId)
    {
        $user = User::withTrashed()->findOrFail($userId);
        
        $user->restore();
        $user->update(['account_status' => 'active']);

        return back()->with('success', "Le compte de {$user->name} a été restauré.");
    }
}
