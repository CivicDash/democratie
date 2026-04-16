<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Ajoute le rôle candidat_maire pour les élections municipales 2026
 */
return new class extends Migration
{
    public function up(): void
    {
        // Vérifier si le rôle existe déjà
        if (! Role::where('name', 'candidat_maire')->exists()) {
            $candidatMaire = Role::create(['name' => 'candidat_maire']);

            // Récupérer les permissions existantes
            $permissions = Permission::whereIn('name', [
                'create_topics',
                'create_posts',
                'edit_own_posts',
                'delete_own_posts',
                'vote_on_posts',
                'vote_in_ballots',
                'view_ballot_results',
                'budget.allocate',
                'submit_budget_allocation',
                'view_budget_data',
                'upload_documents',
            ])->pluck('name')->toArray();

            $candidatMaire->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        $role = Role::where('name', 'candidat_maire')->first();
        if ($role) {
            $role->delete();
        }
    }
};
