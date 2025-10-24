<?php

use App\Models\Profile;
use App\Models\TerritoryDepartment;
use App\Models\TerritoryRegion;
use App\Models\User;

test('profile generates random display name', function () {
    $name1 = Profile::generateDisplayName();
    $name2 = Profile::generateDisplayName();
    
    expect($name1)->toStartWith('Citoyen')
        ->and($name2)->toStartWith('Citoyen')
        ->and($name1)->not->toBe($name2);
});

test('profile hashes citizen reference with pepper', function () {
    config(['app.pepper' => 'test-pepper-secret']);
    
    $ref = '1234567890123';
    $hash = Profile::hashCitizenRef($ref);
    
    expect($hash)->toBeString()
        ->and($hash)->toHaveLength(64) // SHA256
        ->and($hash)->toBe(hash('sha256', $ref . 'test-pepper-secret'));
});

test('profile hash throws exception without pepper', function () {
    config(['app.pepper' => null]);
    
    Profile::hashCitizenRef('test');
})->throws(RuntimeException::class, 'PEPPER not configured');

test('profile has correct scopes', function () {
    $national = Profile::factory()->national()->create();
    $regional = Profile::factory()->regional()->create();
    $departmental = Profile::factory()->departmental()->create();
    
    expect($national->scope)->toBe('national')
        ->and($national->region_id)->toBeNull()
        ->and($national->department_id)->toBeNull()
        ->and($regional->scope)->toBe('region')
        ->and($regional->region_id)->not->toBeNull()
        ->and($departmental->scope)->toBe('dept')
        ->and($departmental->department_id)->not->toBeNull();
});

test('profile belongs to user', function () {
    $user = User::factory()->create();
    $profile = Profile::factory()->create(['user_id' => $user->id]);
    
    expect($profile->user)->toBeInstanceOf(User::class)
        ->and($profile->user->id)->toBe($user->id);
});

test('profile belongs to region when regional', function () {
    $region = TerritoryRegion::factory()->create();
    $profile = Profile::factory()->regional($region)->create();
    
    expect($profile->region)->toBeInstanceOf(TerritoryRegion::class)
        ->and($profile->region->id)->toBe($region->id);
});

test('profile belongs to department when departmental', function () {
    $department = TerritoryDepartment::factory()->create();
    $profile = Profile::factory()->departmental($department)->create();
    
    expect($profile->department)->toBeInstanceOf(TerritoryDepartment::class)
        ->and($profile->department->id)->toBe($department->id)
        ->and($profile->region_id)->toBe($department->region_id);
});

test('verified profile has verified_at timestamp', function () {
    $profile = Profile::factory()->verified()->create();
    
    expect($profile->is_verified)->toBeTrue()
        ->and($profile->verified_at)->not->toBeNull();
});

test('unverified profile has no verified_at timestamp', function () {
    $profile = Profile::factory()->unverified()->create();
    
    expect($profile->is_verified)->toBeFalse()
        ->and($profile->verified_at)->toBeNull();
});

test('citizen_ref_hash is unique', function () {
    config(['app.pepper' => 'test-pepper']);
    
    $ref = 'same-citizen-ref';
    $profile1 = Profile::factory()->create([
        'citizen_ref_hash' => Profile::hashCitizenRef($ref),
    ]);
    
    // Tentative de créer un doublon
    expect(fn() => Profile::factory()->create([
        'citizen_ref_hash' => Profile::hashCitizenRef($ref),
    ]))->toThrow(\Exception::class);
});

