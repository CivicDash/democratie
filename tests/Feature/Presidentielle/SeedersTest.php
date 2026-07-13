<?php

use App\Models\CandidatPresidentielle;
use App\Models\ProgrammeTheme;
use Database\Seeders\CandidatsPresidentielle2027Seeder;
use Database\Seeders\ProgrammeThemesSeeder;

it('amorce 15 thèmes neutres, tous documentés par une source de taxonomie', function () {
    (new ProgrammeThemesSeeder())->run();

    expect(ProgrammeTheme::count())->toBe(15)
        ->and(ProgrammeTheme::whereNull('sources_taxonomie')->count())->toBe(0)
        ->and(ProgrammeTheme::where('slug', 'travail-retraites')->exists())->toBeTrue();
});

it('est idempotent (ré-exécution sans doublon)', function () {
    (new ProgrammeThemesSeeder())->run();
    (new ProgrammeThemesSeeder())->run();

    expect(ProgrammeTheme::count())->toBe(15);
});

it('amorce les 3 candidats 2027 en statut detecte, aucun publié', function () {
    (new ProgrammeThemesSeeder())->run();
    (new CandidatsPresidentielle2027Seeder())->run();

    expect(CandidatPresidentielle::count())->toBe(3)
        ->and(CandidatPresidentielle::where('statut_validation', 'detecte')->count())->toBe(3)
        ->and(CandidatPresidentielle::publie()->count())->toBe(0);
});
