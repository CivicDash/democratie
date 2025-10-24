<?php

use App\Models\TerritoryDepartment;
use App\Models\TerritoryRegion;

test('region has departments', function () {
    $region = TerritoryRegion::factory()->create();
    TerritoryDepartment::factory()->count(3)->create(['region_id' => $region->id]);
    
    expect($region->departments)->toHaveCount(3);
});

test('department belongs to region', function () {
    $region = TerritoryRegion::factory()->create();
    $department = TerritoryDepartment::factory()->create(['region_id' => $region->id]);
    
    expect($department->region)->toBeInstanceOf(TerritoryRegion::class)
        ->and($department->region->id)->toBe($region->id);
});

test('region has unique insee code', function () {
    $code = '11';
    TerritoryRegion::factory()->create(['insee_code' => $code]);
    
    expect(fn() => TerritoryRegion::factory()->create(['insee_code' => $code]))
        ->toThrow(\Exception::class);
});

test('department has unique insee code', function () {
    $code = '75';
    TerritoryDepartment::factory()->create(['insee_code' => $code]);
    
    expect(fn() => TerritoryDepartment::factory()->create(['insee_code' => $code]))
        ->toThrow(\Exception::class);
});

