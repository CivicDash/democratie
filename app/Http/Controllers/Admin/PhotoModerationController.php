<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfilePhotoModeration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PhotoModerationController extends Controller
{
    /**
     * Liste des photos en attente de modération
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');

        $query = User::query()
            ->whereNotNull('profile_photo_path')
            ->where('profile_photo_path', '!=', '');

        if ($status === 'pending') {
            $query->where('profile_photo_status', 'pending');
        } elseif ($status === 'approved') {
            $query->where('profile_photo_status', 'approved');
        } elseif ($status === 'rejected') {
            $query->where('profile_photo_status', 'rejected');
        }

        $photos = $query->orderBy('profile_photo_submitted_at', 'desc')
            ->paginate(20)
            ->through(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photo_url' => $user->profile_photo_url,
                'photo_path' => $user->profile_photo_path,
                'status' => $user->profile_photo_status,
                'submitted_at' => $user->profile_photo_submitted_at?->format('d/m/Y H:i'),
                'moderated_at' => $user->profile_photo_moderated_at?->format('d/m/Y H:i'),
                'rejection_reason' => $user->profile_photo_rejection_reason,
                'is_association_member' => $user->is_association_member,
                'roles' => $user->getRoleNames()->toArray(),
            ]);

        // Stats
        $stats = [
            'pending' => User::where('profile_photo_status', 'pending')->count(),
            'approved' => User::where('profile_photo_status', 'approved')->count(),
            'rejected' => User::where('profile_photo_status', 'rejected')->count(),
        ];

        return Inertia::render('Admin/Moderation/Photos', [
            'photos' => $photos,
            'stats' => $stats,
            'currentStatus' => $status,
        ]);
    }

    /**
     * Approuver une photo
     */
    public function approve(User $user)
    {
        $user->update([
            'profile_photo_status' => 'approved',
            'profile_photo_moderated_at' => now(),
            'profile_photo_moderated_by' => Auth::id(),
            'profile_photo_rejection_reason' => null,
        ]);

        // Historique
        ProfilePhotoModeration::create([
            'user_id' => $user->id,
            'moderator_id' => Auth::id(),
            'photo_path' => $user->profile_photo_path,
            'action' => 'approved',
        ]);

        return back()->with('success', "Photo de {$user->name} approuvée.");
    }

    /**
     * Refuser une photo
     */
    public function reject(Request $request, User $user)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user->update([
            'profile_photo_status' => 'rejected',
            'profile_photo_moderated_at' => now(),
            'profile_photo_moderated_by' => Auth::id(),
            'profile_photo_rejection_reason' => $request->reason,
        ]);

        // Historique
        ProfilePhotoModeration::create([
            'user_id' => $user->id,
            'moderator_id' => Auth::id(),
            'photo_path' => $user->profile_photo_path,
            'action' => 'rejected',
            'reason' => $request->reason,
        ]);

        // TODO: Notifier l'utilisateur que sa photo a été refusée

        return back()->with('success', "Photo de {$user->name} refusée.");
    }

    /**
     * Historique des modérations
     */
    public function history(Request $request)
    {
        $history = ProfilePhotoModeration::with(['user:id,name,email', 'moderator:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(50)
            ->through(fn($mod) => [
                'id' => $mod->id,
                'user' => [
                    'id' => $mod->user->id,
                    'name' => $mod->user->name,
                    'email' => $mod->user->email,
                ],
                'moderator' => $mod->moderator ? [
                    'id' => $mod->moderator->id,
                    'name' => $mod->moderator->name,
                ] : null,
                'photo_path' => $mod->photo_path,
                'action' => $mod->action,
                'action_label' => $mod->action_label,
                'action_color' => $mod->action_color,
                'reason' => $mod->reason,
                'created_at' => $mod->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Admin/Moderation/PhotoHistory', [
            'history' => $history,
        ]);
    }
}
