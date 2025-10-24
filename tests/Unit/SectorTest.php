<?php

use App\Models\Sector;

test('sector has unique name', function () {
    Sector::factory()->create(['name' => 'Santé']);
    
    expect(fn() => Sector::factory()->create(['name' => 'Santé']))
        ->toThrow(\Exception::class);
});

test('sector has icon and color', function () {
    $sector = Sector::factory()->create([
        'icon' => 'health',
        'color' => '#FF5733',
    ]);
    
    expect($sector->icon)->toBe('health')
        ->and($sector->color)->toBe('#FF5733');
});

test('sector has description', function () {
    $sector = Sector::factory()->create([
        'description' => 'Budget alloué aux infrastructures de santé',
    ]);
    
    expect($sector->description)->toBe('Budget alloué aux infrastructures de santé');
});

