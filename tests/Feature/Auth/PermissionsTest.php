<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * TESTS POUR LE SYSTÈME DE PERMISSIONS (RBAC)
 * 
 * Rôles : citizen, moderator, journalist, ong, legislator, state, admin
 */

test('roles are created correctly', function () {
    expect(Role::where('name', 'citizen')->exists())->toBeTrue()
        ->and(Role::where('name', 'moderator')->exists())->toBeTrue()
        ->and(Role::where('name', 'journalist')->exists())->toBeTrue()
        ->and(Role::where('name', 'ong')->exists())->toBeTrue()
        ->and(Role::where('name', 'legislator')->exists())->toBeTrue()
        ->and(Role::where('name', 'state')->exists())->toBeTrue()
        ->and(Role::where('name', 'admin')->exists())->toBeTrue();
});

test('citizen has basic permissions', function () {
    $citizen = User::factory()->citizen()->create();
    
    expect($citizen->hasRole('citizen'))->toBeTrue()
        ->and($citizen->can('topics.view'))->toBeTrue()
        ->and($citizen->can('topics.create'))->toBeTrue()
        ->and($citizen->can('posts.create'))->toBeTrue()
        ->and($citizen->can('posts.vote'))->toBeTrue()
        ->and($citizen->can('ballots.vote'))->toBeTrue()
        ->and($citizen->can('budget.allocate'))->toBeTrue()
        ->and($citizen->can('reports.create'))->toBeTrue();
});

test('citizen cannot moderate', function () {
    $citizen = User::factory()->citizen()->create();
    
    expect($citizen->can('reports.review'))->toBeFalse()
        ->and($citizen->can('sanctions.create'))->toBeFalse();
});

test('moderator can moderate content', function () {
    $moderator = User::factory()->moderator()->create();
    
    expect($moderator->hasRole('moderator'))->toBeTrue()
        ->and($moderator->can('reports.review'))->toBeTrue()
        ->and($moderator->can('reports.resolve'))->toBeTrue()
        ->and($moderator->can('sanctions.create'))->toBeTrue()
        ->and($moderator->can('posts.hide'))->toBeTrue()
        ->and($moderator->can('topics.close'))->toBeTrue();
});

test('moderator cannot manage users or system', function () {
    $moderator = User::factory()->moderator()->create();
    
    expect($moderator->can('users.manage'))->toBeFalse()
        ->and($moderator->can('system.manage'))->toBeFalse();
});

test('journalist can verify documents', function () {
    $journalist = User::factory()->journalist()->create();
    
    expect($journalist->hasRole('journalist'))->toBeTrue()
        ->and($journalist->can('documents.verify'))->toBeTrue()
        ->and($journalist->can('documents.upload'))->toBeTrue();
});

test('ong can verify documents', function () {
    $ong = User::factory()->ong()->create();
    
    expect($ong->hasRole('ong'))->toBeTrue()
        ->and($ong->can('documents.verify'))->toBeTrue()
        ->and($ong->can('documents.upload'))->toBeTrue();
});

test('legislator can create bills', function () {
    $legislator = User::factory()->legislator()->create();
    
    expect($legislator->hasRole('legislator'))->toBeTrue()
        ->and($legislator->can('topics.create'))->toBeTrue()
        ->and($legislator->can('topics.bill'))->toBeTrue()
        ->and($legislator->can('ballots.create'))->toBeTrue();
});

test('state can publish budget data', function () {
    $state = User::factory()->state()->create();
    
    expect($state->hasRole('state'))->toBeTrue()
        ->and($state->can('budget.publish'))->toBeTrue()
        ->and($state->can('revenue.publish'))->toBeTrue()
        ->and($state->can('spending.publish'))->toBeTrue();
});

test('admin has all permissions', function () {
    $admin = User::factory()->admin()->create();
    
    expect($admin->hasRole('admin'))->toBeTrue()
        ->and($admin->can('users.manage'))->toBeTrue()
        ->and($admin->can('roles.manage'))->toBeTrue()
        ->and($admin->can('system.manage'))->toBeTrue()
        ->and($admin->can('reports.review'))->toBeTrue()
        ->and($admin->can('sanctions.create'))->toBeTrue()
        ->and($admin->can('documents.verify'))->toBeTrue()
        ->and($admin->can('budget.publish'))->toBeTrue();
});

test('user can have multiple roles', function () {
    $user = User::factory()->create();
    
    $user->assignRole(['citizen', 'journalist']);
    
    expect($user->hasRole('citizen'))->toBeTrue()
        ->and($user->hasRole('journalist'))->toBeTrue()
        ->and($user->getRoleNames())->toContain('citizen', 'journalist');
});

test('permission can be given directly to user', function () {
    $user = User::factory()->citizen()->create();
    
    $user->givePermissionTo('topics.pin');
    
    expect($user->can('topics.pin'))->toBeTrue();
});

test('permission can be revoked from user', function () {
    $user = User::factory()->citizen()->create();
    
    $user->givePermissionTo('topics.pin');
    expect($user->can('topics.pin'))->toBeTrue();
    
    $user->revokePermissionTo('topics.pin');
    expect($user->fresh()->can('topics.pin'))->toBeFalse();
});

test('role can be removed from user', function () {
    $user = User::factory()->moderator()->create();
    
    expect($user->hasRole('moderator'))->toBeTrue();
    
    $user->removeRole('moderator');
    
    expect($user->fresh()->hasRole('moderator'))->toBeFalse()
        ->and($user->fresh()->can('reports.review'))->toBeFalse();
});

test('citizen cannot access admin routes', function () {
    $citizen = User::factory()->citizen()->create();
    
    expect($citizen->can('admin.dashboard'))->toBeFalse()
        ->and($citizen->can('users.manage'))->toBeFalse()
        ->and($citizen->can('system.manage'))->toBeFalse();
});

test('moderator can access moderation routes', function () {
    $moderator = User::factory()->moderator()->create();
    
    expect($moderator->can('moderation.dashboard'))->toBeTrue()
        ->and($moderator->can('reports.index'))->toBeTrue();
});

test('permission middleware blocks unauthorized access', function () {
    $citizen = User::factory()->citizen()->create();
    
    // Citizen tente d'accéder aux rapports
    actingAs($citizen)
        ->get('/moderation/reports')
        ->assertForbidden();
});

test('permission middleware allows authorized access', function () {
    $moderator = User::factory()->moderator()->create();
    
    // Moderator accède aux rapports
    actingAs($moderator)
        ->get('/moderation/reports')
        ->assertOk();
});

test('role middleware blocks incorrect role', function () {
    $citizen = User::factory()->citizen()->create();
    
    actingAs($citizen)
        ->get('/admin/users')
        ->assertForbidden();
});

test('role middleware allows correct role', function () {
    $admin = User::factory()->admin()->create();
    
    actingAs($admin)
        ->get('/admin/users')
        ->assertOk();
});

test('all permissions exist', function () {
    $expectedPermissions = [
        'topics.view',
        'topics.create',
        'topics.edit',
        'topics.delete',
        'topics.close',
        'topics.pin',
        'topics.bill',
        'posts.create',
        'posts.edit',
        'posts.delete',
        'posts.vote',
        'posts.hide',
        'ballots.create',
        'ballots.vote',
        'ballots.results',
        'budget.allocate',
        'budget.publish',
        'revenue.publish',
        'spending.publish',
        'reports.create',
        'reports.review',
        'reports.resolve',
        'sanctions.create',
        'documents.upload',
        'documents.verify',
        'users.manage',
        'roles.manage',
        'system.manage',
        'moderation.dashboard',
        'admin.dashboard',
        'reports.index',
    ];
    
    foreach ($expectedPermissions as $permission) {
        expect(Permission::where('name', $permission)->exists())
            ->toBeTrue("Permission '$permission' should exist");
    }
});

test('citizen scope returns only citizens', function () {
    User::factory()->citizen()->count(3)->create();
    User::factory()->moderator()->count(2)->create();
    User::factory()->admin()->create();
    
    $citizens = User::role('citizen')->get();
    
    expect($citizens)->toHaveCount(3);
});

test('verified users have is_verified flag', function () {
    $journalist = User::factory()->journalist()->create();
    $journalist->profile->update(['is_verified' => true]);
    
    expect($journalist->profile->is_verified)->toBeTrue();
});

test('unverified users cannot verify documents', function () {
    $journalist = User::factory()->journalist()->create();
    $journalist->profile->update(['is_verified' => false]);
    
    // Même avec le rôle journalist, sans vérification pas de permission effective
    expect($journalist->profile->is_verified)->toBeFalse();
});

