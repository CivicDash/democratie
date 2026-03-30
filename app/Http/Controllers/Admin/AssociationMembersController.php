<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssociationMembersController extends Controller
{
    /**
     * Liste des membres de l'association Civis-Consilium
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'active'); // active, all

        $query = User::query();

        if ($status === 'active') {
            $query->where('is_association_member', true);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('username', 'ilike', "%{$search}%")
                    ->orWhere('association_member_id', 'ilike', "%{$search}%");
            });
        }

        $members = $query->orderBy('association_member_since', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->through(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email_visible_to_admin ? $user->email : '(masqué)',
                'photo_url' => $user->profile_photo_url,
                'photo_status' => $user->profile_photo_status,
                'is_association_member' => $user->is_association_member,
                'association_member_since' => $user->association_member_since?->format('d/m/Y'),
                'association_member_id' => $user->association_member_id,
                'roles' => $user->getRoleNames()->toArray(),
                'created_at' => $user->created_at->format('d/m/Y'),
            ]);

        // Stats
        $stats = [
            'total_members' => User::where('is_association_member', true)->count(),
            'pending_photos' => User::where('is_association_member', true)
                ->where('profile_photo_status', 'pending')
                ->count(),
            'new_this_month' => User::where('is_association_member', true)
                ->where('association_member_since', '>=', now()->startOfMonth())
                ->count(),
        ];

        return Inertia::render('Admin/AssociationMembers/Index', [
            'members' => $members,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Ajouter le statut membre à un utilisateur existant
     * L'ID Dolibarr est obligatoire car c'est Dolibarr qui valide l'adhésion
     */
    public function addMember(Request $request, User $user)
    {
        $request->validate([
            'member_id' => 'required|string|max:50',
        ], [
            'member_id.required' => 'L\'ID membre Dolibarr est obligatoire (adhésion validée dans Dolibarr).',
        ]);

        $user->update([
            'is_association_member' => true,
            'association_member_since' => now(),
            'association_member_id' => $request->member_id,
        ]);

        // Assigner le rôle association_member si Spatie est configuré
        if (! $user->hasRole('association_member')) {
            try {
                $user->assignRole('association_member');
            } catch (\Exception $e) {
                // Rôle pas encore créé, ignorer
            }
        }

        return back()->with('success', "{$user->name} est maintenant membre de l'association (ID: {$request->member_id}).");
    }

    /**
     * Retirer le statut membre
     */
    public function removeMember(User $user)
    {
        $user->update([
            'is_association_member' => false,
            'association_member_since' => null,
            'association_member_id' => null,
        ]);

        return back()->with('success', "{$user->name} n'est plus membre de l'association.");
    }

    /**
     * Mettre à jour l'ID membre (Dolibarr)
     */
    public function updateMemberId(Request $request, User $user)
    {
        $request->validate([
            'member_id' => 'nullable|string|max:50',
        ]);

        $user->update([
            'association_member_id' => $request->member_id,
        ]);

        return back()->with('success', 'ID membre mis à jour.');
    }

    /**
     * Rechercher un utilisateur pour l'ajouter comme membre
     */
    public function searchUsers(Request $request)
    {
        $search = $request->input('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $users = User::where('is_association_member', false)
            ->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('username', 'ilike', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'email', 'username', 'profile_photo_path']);

        return response()->json($users->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'username' => $u->username,
            'photo_url' => $u->profile_photo_url,
        ]));
    }

    /**
     * Export des membres pour Dolibarr (CSV)
     */
    public function export(Request $request)
    {
        $members = User::where('is_association_member', true)
            ->where('email_visible_to_admin', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'username', 'association_member_id', 'association_member_since', 'created_at']);

        $csv = "ID,Nom,Email,Pseudo,ID_Membre,Membre_Depuis,Inscrit_Le\n";

        foreach ($members as $m) {
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $m->id,
                str_replace('"', '""', $m->name),
                $m->email,
                $m->username ?? '',
                $m->association_member_id ?? '',
                $m->association_member_since?->format('Y-m-d') ?? '',
                $m->created_at->format('Y-m-d')
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="membres-civis-consilium-'.date('Y-m-d').'.csv"');
    }
}
