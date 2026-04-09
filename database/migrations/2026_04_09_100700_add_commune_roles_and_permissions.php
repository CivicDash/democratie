<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $communePermissions = [
            'commune.manage_page',
            'commune.publish_articles',
            'commune.manage_events',
            'commune.send_notifications',
            'commune.manage_admins',
            'commune.view_analytics',
            'commune.reclamer',
        ];

        foreach ($communePermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $maireAdmin = Role::firstOrCreate(['name' => 'maire_admin', 'guard_name' => 'web']);
        $maireAdmin->syncPermissions(array_merge([
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
        ], $communePermissions));

        $communeDelegue = Role::firstOrCreate(['name' => 'commune_delegue', 'guard_name' => 'web']);
        $communeDelegue->syncPermissions([
            'create_topics',
            'create_posts',
            'edit_own_posts',
            'delete_own_posts',
            'vote_on_posts',
            'vote_in_ballots',
            'view_ballot_results',
            'view_budget_data',
            'commune.publish_articles',
            'commune.manage_events',
            'commune.view_analytics',
        ]);

        // Give existing citizen role the ability to claim a commune
        $citizen = Role::findByName('citizen', 'web');
        if ($citizen) {
            $citizen->givePermissionTo('commune.reclamer');
        }
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::where('name', 'maire_admin')->delete();
        Role::where('name', 'commune_delegue')->delete();

        Permission::whereIn('name', [
            'commune.manage_page',
            'commune.publish_articles',
            'commune.manage_events',
            'commune.send_notifications',
            'commune.manage_admins',
            'commune.view_analytics',
            'commune.reclamer',
        ])->delete();
    }
};
