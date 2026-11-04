<?php

use App\Models\BudgetAnnuel;
use App\Models\FranceEconomy;
use App\Services\InseeApiService;

/**
 * Tables de finances publiques « France » corrigées le 04/11/2026 : la dette était multipliée
 * par 1 000, et le repli de l'import Insee réécrivait chaque mois des valeurs dépassées.
 */
it('enregistre la dette et le PIB en euros, pas en milliers de milliards', function () {
    $this->artisan('import:budget-etat', ['--annee' => 2025])->assertSuccessful();

    $b = BudgetAnnuel::where('annee', 2025)->first();
    expect((float) $b->dette_publique)->toBe(3_460_111_000_000.0)
        ->and($b->dette_formate)->toBe('3 460,1 Md€')
        ->and((float) $b->deficit)->toBe(-124_205_700_000.0)
        ->and($b->metadata['sources']['etat'])->toContain('pjl25-726');
});

it("ne remplace jamais une année déjà en base avec les valeurs de repli de l'Insee", function () {
    $this->mock(InseeApiService::class, fn ($m) => $m->shouldReceive('isConfigured')->andReturn(false));
    FranceEconomy::create(['year' => 2024, 'quarter' => null, 'public_debt_billions_euros' => 9999.0]);

    $this->artisan('import:insee-economy', ['--year' => 2025])->assertSuccessful();

    expect((float) FranceEconomy::forYear(2024)->annual()->first()->public_debt_billions_euros)->toBe(9999.0)
        ->and((float) FranceEconomy::forYear(2025)->annual()->first()->public_debt_billions_euros)->toBe(3460.1);
});
