<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les permissions
        $permissions = [
            // Forum
            'create_topics',
            'edit_topics',
            'delete_topics',
            'create_posts',
            'edit_own_posts',
            'delete_own_posts',
            'vote_on_posts',
            'pin_posts',
            
            // Scrutins
            'create_ballots',
            'vote_in_ballots',
            'view_ballot_results',
            'manage_ballots',
            
            // Modération
            'view_reports',
            'handle_reports',
            'create_sanctions',
            'revoke_sanctions',
            'hide_posts',
            
            // Budget
            'submit_budget_allocation',
            'view_budget_data',
            'import_budget_data',
            
            // Documents
            'upload_documents',
            'verify_documents',
            'view_pending_documents',
            
            // Administration
            'manage_users',
            'manage_roles',
            'view_admin_panel',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // ==================== RÔLES ====================

        /**
         * 1. CITIZEN (Citoyen)
         * Utilisateur standard de la plateforme
         */
        $citizen = Role::create(['name' => 'citizen']);
        $citizen->givePermissionTo([
            'create_posts',
            'edit_own_posts',
            'delete_own_posts',
            'vote_on_posts',
            'vote_in_ballots',
            'view_ballot_results',
            'submit_budget_allocation',
            'view_budget_data',
        ]);

        /**
         * 2. MODERATOR (Modérateur)
         * Gère les signalements et sanctions
         */
        $moderator = Role::create(['name' => 'moderator']);
        $moderator->givePermissionTo([
            'create_posts',
            'edit_own_posts',
            'delete_own_posts',
            'vote_on_posts',
            'vote_in_ballots',
            'view_ballot_results',
            'submit_budget_allocation',
            'view_budget_data',
            'view_reports',
            'handle_reports',
            'create_sanctions',
            'revoke_sanctions',
            'hide_posts',
        ]);

        /**
         * 3. JOURNALIST (Journaliste)
         * Vérifie les documents officiels
         */
        $journalist = Role::create(['name' => 'journalist']);
        $journalist->givePermissionTo([
            'create_posts',
            'edit_own_posts',
            'delete_own_posts',
            'vote_on_posts',
            'vote_in_ballots',
            'view_ballot_results',
            'submit_budget_allocation',
            'view_budget_data',
            'verify_documents',
            'view_pending_documents',
        ]);

        /**
         * 4. ONG (Organisation)
         * Vérifie les documents officiels
         */
        $ong = Role::create(['name' => 'ong']);
        $ong->givePermissionTo([
            'create_posts',
            'edit_own_posts',
            'delete_own_posts',
            'vote_on_posts',
            'vote_in_ballots',
            'view_ballot_results',
            'submit_budget_allocation',
            'view_budget_data',
            'verify_documents',
            'view_pending_documents',
        ]);

        /**
         * 5. LEGISLATOR (Législateur)
         * Crée topics/lois, upload documents
         */
        $legislator = Role::create(['name' => 'legislator']);
        $legislator->givePermissionTo([
            'create_topics',
            'edit_topics',
            'create_posts',
            'edit_own_posts',
            'delete_own_posts',
            'vote_on_posts',
            'pin_posts',
            'create_ballots',
            'vote_in_ballots',
            'view_ballot_results',
            'manage_ballots',
            'upload_documents',
            'view_budget_data',
        ]);

        /**
         * 6. STATE (État)
         * Entité gouvernementale, upload documents officiels
         */
        $state = Role::create(['name' => 'state']);
        $state->givePermissionTo([
            'create_topics',
            'edit_topics',
            'create_posts',
            'edit_own_posts',
            'pin_posts',
            'create_ballots',
            'vote_in_ballots',
            'view_ballot_results',
            'manage_ballots',
            'upload_documents',
            'view_budget_data',
            'import_budget_data',
        ]);

        /**
         * 7. ADMIN (Administrateur)
         * Tous les pouvoirs
         */
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        $this->command->info('✓ 7 rôles créés : citizen, moderator, journalist, ong, legislator, state, admin');
        $this->command->info('✓ ' . count($permissions) . ' permissions créées');
    }
}

