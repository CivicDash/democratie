<?php

use App\Models\Sector;
use App\Models\User;
use App\Models\UserAllocation;

/**
 * TESTS POUR LE BUDGET PARTICIPATIF
 * 
 * Les citoyens répartissent 100% de leur budget entre différents secteurs.
 * Chaque secteur a des contraintes min/max d'allocation.
 */

test('sector has min and max allocation constraints', function () {
    $sector = Sector::factory()->create([
        'min_allocation_percent' => 5.00,
        'max_allocation_percent' => 30.00,
    ]);
    
    expect($sector->min_allocation_percent)->toBe(5.00)
        ->and($sector->max_allocation_percent)->toBe(30.00);
});

test('user allocation must be within sector constraints', function () {
    $sector = Sector::factory()->create([
        'min_allocation_percent' => 5.00,
        'max_allocation_percent' => 30.00,
    ]);
    $user = User::factory()->citizen()->create();
    
    // Allocation valide
    $validAllocation = UserAllocation::factory()->create([
        'user_id' => $user->id,
        'sector_id' => $sector->id,
        'allocated_percent' => 15.00, // Entre min et max
    ]);
    
    expect($validAllocation->allocated_percent)->toBeGreaterThanOrEqual($sector->min_allocation_percent)
        ->and($validAllocation->allocated_percent)->toBeLessThanOrEqual($sector->max_allocation_percent);
});

test('user cannot allocate less than minimum', function () {
    $sector = Sector::factory()->create([
        'min_allocation_percent' => 5.00,
    ]);
    $user = User::factory()->citizen()->create();
    
    $allocation = UserAllocation::factory()->make([
        'user_id' => $user->id,
        'sector_id' => $sector->id,
        'allocated_percent' => 3.00, // Moins que le minimum
    ]);
    
    expect($allocation->validate())->toBeFalse();
});

test('user cannot allocate more than maximum', function () {
    $sector = Sector::factory()->create([
        'max_allocation_percent' => 30.00,
    ]);
    $user = User::factory()->citizen()->create();
    
    $allocation = UserAllocation::factory()->make([
        'user_id' => $user->id,
        'sector_id' => $sector->id,
        'allocated_percent' => 40.00, // Plus que le maximum
    ]);
    
    expect($allocation->validate())->toBeFalse();
});

test('user total allocation must equal 100 percent', function () {
    $user = User::factory()->citizen()->create();
    $sectors = Sector::factory()->count(5)->create([
        'min_allocation_percent' => 10.00,
        'max_allocation_percent' => 30.00,
    ]);
    
    // Répartition valide (total = 100%)
    UserAllocation::factory()->create([
        'user_id' => $user->id,
        'sector_id' => $sectors[0]->id,
        'allocated_percent' => 20.00,
    ]);
    UserAllocation::factory()->create([
        'user_id' => $user->id,
        'sector_id' => $sectors[1]->id,
        'allocated_percent' => 20.00,
    ]);
    UserAllocation::factory()->create([
        'user_id' => $user->id,
        'sector_id' => $sectors[2]->id,
        'allocated_percent' => 20.00,
    ]);
    UserAllocation::factory()->create([
        'user_id' => $user->id,
        'sector_id' => $sectors[3]->id,
        'allocated_percent' => 20.00,
    ]);
    UserAllocation::factory()->create([
        'user_id' => $user->id,
        'sector_id' => $sectors[4]->id,
        'allocated_percent' => 20.00,
    ]);
    
    $totalAllocated = $user->allocations->sum('allocated_percent');
    
    expect($totalAllocated)->toBe(100.00);
});

test('user cannot allocate more than 100 percent total', function () {
    $user = User::factory()->citizen()->create();
    $sectors = Sector::factory()->count(3)->create();
    
    UserAllocation::factory()->create([
        'user_id' => $user->id,
        'sector_id' => $sectors[0]->id,
        'allocated_percent' => 40.00,
    ]);
    UserAllocation::factory()->create([
        'user_id' => $user->id,
        'sector_id' => $sectors[1]->id,
        'allocated_percent' => 40.00,
    ]);
    
    // Total actuel = 80%, tentative d'ajouter 30% (total = 110%)
    $allocation = UserAllocation::factory()->make([
        'user_id' => $user->id,
        'sector_id' => $sectors[2]->id,
        'allocated_percent' => 30.00,
    ]);
    
    $totalWithNew = $user->allocations->sum('allocated_percent') + $allocation->allocated_percent;
    
    expect($totalWithNew)->toBeGreaterThan(100.00)
        ->and($allocation->validateTotal($user))->toBeFalse();
});

test('user cannot have multiple allocations for same sector', function () {
    $sector = Sector::factory()->create();
    $user = User::factory()->citizen()->create();
    
    UserAllocation::factory()->create([
        'user_id' => $user->id,
        'sector_id' => $sector->id,
        'allocated_percent' => 20.00,
    ]);
    
    // Tentative de créer une deuxième allocation pour le même secteur
    expect(fn() => UserAllocation::factory()->create([
        'user_id' => $user->id,
        'sector_id' => $sector->id,
        'allocated_percent' => 10.00,
    ]))->toThrow(\Exception::class);
});

test('user can update allocation for a sector', function () {
    $sector = Sector::factory()->create([
        'min_allocation_percent' => 5.00,
        'max_allocation_percent' => 50.00,
    ]);
    $user = User::factory()->citizen()->create();
    
    $allocation = UserAllocation::factory()->create([
        'user_id' => $user->id,
        'sector_id' => $sector->id,
        'allocated_percent' => 20.00,
    ]);
    
    // Mise à jour
    $allocation->update(['allocated_percent' => 30.00]);
    
    expect($allocation->fresh()->allocated_percent)->toBe(30.00);
});

test('user model has helper to check budget completion', function () {
    $user = User::factory()->citizen()->create();
    
    // Pas encore d'allocation
    expect($user->hasCompletedBudgetAllocation())->toBeFalse();
    
    // Allocation partielle
    $sectors = Sector::factory()->count(2)->create();
    UserAllocation::factory()->create([
        'user_id' => $user->id,
        'sector_id' => $sectors[0]->id,
        'allocated_percent' => 50.00,
    ]);
    
    expect($user->fresh()->hasCompletedBudgetAllocation())->toBeFalse();
    
    // Allocation complète
    UserAllocation::factory()->create([
        'user_id' => $user->id,
        'sector_id' => $sectors[1]->id,
        'allocated_percent' => 50.00,
    ]);
    
    expect($user->fresh()->hasCompletedBudgetAllocation())->toBeTrue();
});

test('sector has public spend records', function () {
    $sector = Sector::factory()->create();
    
    $spend1 = \App\Models\PublicSpend::factory()->create([
        'sector_id' => $sector->id,
        'amount' => 1000000.00,
    ]);
    $spend2 = \App\Models\PublicSpend::factory()->create([
        'sector_id' => $sector->id,
        'amount' => 2000000.00,
    ]);
    
    expect($sector->spends)->toHaveCount(2)
        ->and($sector->spends->sum('amount'))->toBe(3000000.00);
});

test('public revenue has correct structure', function () {
    $revenue = \App\Models\PublicRevenue::factory()->create([
        'source' => 'TVA',
        'amount' => 5000000.00,
        'year' => 2024,
    ]);
    
    expect($revenue->source)->toBe('TVA')
        ->and($revenue->amount)->toBe(5000000.00)
        ->and($revenue->year)->toBe(2024);
});

test('public spend has correct structure', function () {
    $sector = Sector::factory()->create();
    $spend = \App\Models\PublicSpend::factory()->create([
        'sector_id' => $sector->id,
        'item' => 'Infrastructure routière',
        'amount' => 3000000.00,
        'year' => 2024,
    ]);
    
    expect($spend->sector_id)->toBe($sector->id)
        ->and($spend->item)->toBe('Infrastructure routière')
        ->and($spend->amount)->toBe(3000000.00)
        ->and($spend->year)->toBe(2024);
});

test('sector aggregates user allocations', function () {
    $sector = Sector::factory()->create();
    $users = User::factory()->citizen()->count(3)->create();
    
    UserAllocation::factory()->create([
        'user_id' => $users[0]->id,
        'sector_id' => $sector->id,
        'allocated_percent' => 20.00,
    ]);
    UserAllocation::factory()->create([
        'user_id' => $users[1]->id,
        'sector_id' => $sector->id,
        'allocated_percent' => 30.00,
    ]);
    UserAllocation::factory()->create([
        'user_id' => $users[2]->id,
        'sector_id' => $sector->id,
        'allocated_percent' => 25.00,
    ]);
    
    $avgAllocation = $sector->allocations->avg('allocated_percent');
    
    expect($sector->allocations)->toHaveCount(3)
        ->and($avgAllocation)->toBe(25.00); // (20+30+25)/3
});

