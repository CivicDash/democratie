<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    /**
     * Liste des utilisateurs avec filtres
     */
    public function index(Request $request): Response
    {
        $query = User::query()
            ->with('roles')
            ->withCount(['topics', 'posts']);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        // Filtre par rôle
        if ($request->filled('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $request->role));
        }

        // Filtre par statut élu
        if ($request->filled('elu_status')) {
            if ($request->elu_status === 'verified') {
                $query->where('is_verified_elu', true);
            } elseif ($request->elu_status === 'pending') {
                $query->where('is_verified_elu', false)
                      ->whereNotNull('elu_type');
            }
        }

        // Tri
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $users = $query->paginate(30)->withQueryString();

        // Transformer les données
        $usersData = $users->through(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->toArray(),
            'primary_role' => $user->primary_role,
            'primary_role_label' => $user->primary_role_label,
            'is_verified_elu' => $user->is_verified_elu,
            'elu_type' => $user->elu_type,
            'is_demo' => $user->isDemoAccount(),
            'is_read_only' => $user->isReadOnly(),
            'topics_count' => $user->topics_count,
            'posts_count' => $user->posts_count,
            'created_at' => $user->created_at->format('d/m/Y H:i'),
            'email_verified_at' => $user->email_verified_at?->format('d/m/Y'),
        ]);

        // Statistiques
        $stats = [
            'total' => User::count(),
            'admins' => User::role('admin')->count(),
            'moderators' => User::role('moderator')->count(),
            'elus' => User::where('is_verified_elu', true)->count(),
            'citizens' => User::role('citizen')->count(),
            'demo' => User::where('email', 'LIKE', '%demo%')->count(),
        ];

        // Rôles disponibles
        $roles = Role::all()->map(fn($r) => [
            'name' => $r->name,
            'label' => $this->getRoleLabel($r->name),
        ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $usersData,
            'stats' => $stats,
            'roles' => $roles,
            'filters' => $request->only(['search', 'role', 'elu_status', 'sort', 'order']),
        ]);
    }

    /**
     * Formulaire de création d'utilisateur
     */
    public function create(): Response
    {
        $roles = Role::all()->map(fn($r) => [
            'name' => $r->name,
            'label' => $this->getRoleLabel($r->name),
        ]);

        return Inertia::render('Admin/Users/Create', [
            'roles' => $roles,
            'elu_types' => [
                ['value' => 'depute', 'label' => 'Député'],
                ['value' => 'senateur', 'label' => 'Sénateur'],
                ['value' => 'maire', 'label' => 'Maire'],
            ],
        ]);
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::defaults()],
            'role' => 'required|string|exists:roles,name',
            'elu_type' => 'nullable|string|in:depute,senateur,maire',
            'elu_ref' => 'nullable|string',
            'is_verified_elu' => 'boolean',
            'send_welcome_email' => 'boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'elu_type' => $validated['elu_type'] ?? null,
            'elu_ref' => $validated['elu_ref'] ?? null,
            'is_verified_elu' => $validated['is_verified_elu'] ?? false,
            'email_verified_at' => now(), // Admin-created accounts are verified
        ]);

        $user->assignRole($validated['role']);

        // Si c'est un élu, assigner aussi le rôle legislator
        if ($validated['elu_type'] && $validated['is_verified_elu']) {
            $user->assignRole('legislator');
        }

        return redirect()->route('admin.users.index')
            ->with('success', "Utilisateur {$user->name} créé avec succès.");
    }

    /**
     * Afficher le détail d'un utilisateur
     */
    public function show(User $user): Response
    {
        $user->load(['roles', 'topics', 'posts', 'sanctions']);

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'primary_role' => $user->primary_role,
                'primary_role_label' => $user->primary_role_label,
                'is_verified_elu' => $user->is_verified_elu,
                'elu_type' => $user->elu_type,
                'elu_ref' => $user->elu_ref,
                'elu_data' => $user->elu_data,
                'is_demo' => $user->isDemoAccount(),
                'is_read_only' => $user->isReadOnly(),
                'can_post' => $user->canPost(),
                'can_vote' => $user->canVote(),
                'can_moderate' => $user->canModerate(),
                'is_muted' => $user->isMuted(),
                'is_banned' => $user->isBanned(),
                'two_factor_enabled' => $user->hasTwoFactorEnabled(),
                'created_at' => $user->created_at->format('d/m/Y H:i'),
                'email_verified_at' => $user->email_verified_at?->format('d/m/Y H:i'),
                'verified_at' => $user->verified_at?->format('d/m/Y H:i'),
                'topics_count' => $user->topics->count(),
                'posts_count' => $user->posts->count(),
                'sanctions' => $user->sanctions->map(fn($s) => [
                    'id' => $s->id,
                    'type' => $s->type,
                    'reason' => $s->reason,
                    'expires_at' => $s->expires_at?->format('d/m/Y H:i'),
                    'is_active' => $s->isActive(),
                ]),
            ],
            'roles' => Role::all()->map(fn($r) => [
                'name' => $r->name,
                'label' => $this->getRoleLabel($r->name),
            ]),
        ]);
    }

    /**
     * Formulaire d'édition
     */
    public function edit(User $user): Response
    {
        return $this->show($user);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => ['nullable', Password::defaults()],
            'role' => 'sometimes|string|exists:roles,name',
            'elu_type' => 'nullable|string|in:depute,senateur,maire',
            'elu_ref' => 'nullable|string',
            'is_verified_elu' => 'boolean',
        ]);

        // Mise à jour des champs de base
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        // Mise à jour des champs élu
        $user->elu_type = $validated['elu_type'] ?? null;
        $user->elu_ref = $validated['elu_ref'] ?? null;
        $user->is_verified_elu = $validated['is_verified_elu'] ?? false;

        if ($user->is_verified_elu && $user->elu_type) {
            $user->verified_at = now();
        }

        $user->save();

        // Mise à jour des rôles
        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
            
            // Si c'est un élu vérifié, ajouter le rôle legislator
            if ($user->is_verified_elu && $user->elu_type) {
                $user->assignRole('legislator');
            }
        }

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Changer le rôle d'un utilisateur (action rapide)
     */
    public function changeRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->syncRoles([$validated['role']]);

        return back()->with('success', "Rôle de {$user->name} changé en {$validated['role']}.");
    }

    /**
     * Vérifier un élu
     */
    public function verifyElu(User $user)
    {
        if (!$user->elu_type) {
            return back()->with('error', 'Cet utilisateur n\'est pas associé à un élu.');
        }

        $user->is_verified_elu = true;
        $user->verified_at = now();
        $user->save();

        $user->assignRole('legislator');

        return back()->with('success', "Élu {$user->name} vérifié avec succès.");
    }

    /**
     * Révoquer la vérification d'un élu
     */
    public function revokeElu(User $user)
    {
        $user->is_verified_elu = false;
        $user->verified_at = null;
        $user->save();

        $user->removeRole('legislator');

        return back()->with('success', "Vérification de {$user->name} révoquée.");
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(User $user)
    {
        // Protection contre la suppression des admins et comptes démo
        if ($user->isAdmin() && User::role('admin')->count() <= 1) {
            return back()->with('error', 'Impossible de supprimer le dernier administrateur.');
        }

        if ($user->isDemoAccount()) {
            return back()->with('error', 'Les comptes de démonstration ne peuvent pas être supprimés.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Utilisateur {$name} supprimé.");
    }

    /**
     * Label pour les rôles
     */
    private function getRoleLabel(string $role): string
    {
        return match ($role) {
            'admin', 'super-admin' => 'Administrateur',
            'moderator' => 'Modérateur',
            'legislator' => 'Élu',
            'citizen' => 'Citoyen',
            'journalist' => 'Journaliste',
            'ong' => 'ONG',
            'state' => 'Service public',
            'public_figure' => 'Personnalité publique',
            default => ucfirst($role),
        };
    }
}
