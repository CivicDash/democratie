<?php

use App\Models\EurostatIndicateur;
use App\Services\Presidentielle\PresidentielleExporter;
use Illuminate\Support\Facades\File;

/**
 * Export des repères chiffrés vers objectif2027.fr (reperes.json).
 *
 * Ce que le test garde surtout : ce qui ne doit JAMAIS sortir du back-office. Plus aucun
 * verdict, ni l'affirmation d'origine : le site pose une question neutre et décrit, il ne
 * juge pas. Ni la coloration perçue, ni les notes internes.
 */
function exportAffirmations(): array
{
    return app(PresidentielleExporter::class)->build('2027')['reperes'];
}

it('n\'exporte que les fiches publiées', function () {
    $brouillon = affirmationPubliable(['slug' => 'brouillon']);
    $publiee = affirmationPubliable(['slug' => 'publiee', 'affiche_publiquement' => true]);

    $slugs = collect(exportAffirmations()['reperes'])->pluck('slug')->all();

    expect($slugs)->toBe(['publiee']);
});

it('exporte la question, les constats, les sources citées et les thèmes — sans verdict', function () {
    $f = affirmationPubliable(['slug' => 'riches', 'affiche_publiquement' => true]);
    // Une source déclarée mais citée par aucun constat ne part pas.
    $f->sources()->create(['cle' => 'non-citee', 'producteur' => 'X', 'titre' => 'Y', 'url' => 'https://x.fr', 'categorie' => 'presse']);

    // Un verdict resté en base (ancien format) ne sort pas.
    $f->verdicts()->create(['ordre' => 0, 'portee' => null, 'verdict' => 'confirme']);

    $fiche = exportAffirmations()['reperes'][0];

    expect($fiche['question'])->toBe('Combien y a-t-il de ceci ?')
        ->and($fiche)->not->toHaveKeys(['verdicts', 'enonce', 'part_de_valeur'])
        ->and(collect($fiche['constats'])->pluck('section')->all())->toBe(['chiffres', 'limites'])
        ->and($fiche['constats'][0]['sources'])->toBe(['insee-test'])
        ->and(collect($fiche['sources'])->pluck('cle')->all())->toBe(['insee-test'])
        ->and($fiche['theme'])->toBe($f->theme->slug);
});

it('n\'exporte que les phrases vérifiées, et compte les autres par section', function () {
    $f = affirmationPubliable(['affiche_publiquement' => true]);
    $masquee = $f->constats()->create(['section' => 'complement', 'ordre' => 5, 'texte' => 'PHRASE-MASQUEE (2023).', 'verification' => 'a_verifier']);
    $masquee->sources()->attach($f->sources()->create(['cle' => 'source-masquee', 'producteur' => 'X', 'titre' => 'Y', 'url' => 'https://x.fr', 'categorie' => 'presse'])->id);
    $f->constats()->create(['section' => 'chiffres', 'ordre' => 6, 'texte' => 'Autre (2024).', 'verification' => 'a_verifier']);
    $f->graphiques()->create(['type' => 'courbes', 'titre' => 'GRAPHIQUE-MASQUE', 'indicateurs' => ['part_nes_etranger'], 'constat_id' => $masquee->id]);
    EurostatIndicateur::create([
        'code' => 'part_nes_etranger', 'titre' => 'Part', 'unite' => '%', 'sources' => [],
        'series_publiees' => ['FR' => [['annee' => 2025, 'valeur' => 14.0, 'statut' => '']]],
    ]);

    $export = exportAffirmations();
    $fiche = $export['reperes'][0];
    $json = json_encode($export, JSON_UNESCAPED_UNICODE);

    expect(collect($fiche['constats'])->pluck('section')->all())->toBe(['chiffres', 'limites'])
        ->and((array) $fiche['a_sourcer'])->toBe(['complement' => 1, 'chiffres' => 1])
        // Ni le texte, ni la source que seule elle cite, ni son graphique, ni sa série.
        ->and($json)->not->toContain('PHRASE-MASQUEE')
        ->and(collect($fiche['sources'])->pluck('cle')->all())->toBe(['insee-test'])
        ->and($fiche['graphiques'])->toBe([])
        ->and((array) $export['indicateurs'])->toBe([]);
});

it('ne laisse jamais sortir verdict, énoncé d\'origine, coloration ni notes internes', function () {
    affirmationPubliable(['affiche_publiquement' => true, 'coloration_percue' => 'gauche'])
        ->constats()->first()->update(['note_verification' => 'NOTE-INTERNE-42']);

    $dir = storage_path('app/testing/export-affirmations');
    $exporteur = app(PresidentielleExporter::class);
    $exporteur->write($exporteur->build('2027'), $dir);
    $json = File::get("{$dir}/reperes.json");
    File::deleteDirectory($dir);

    expect($json)->not->toContain('coloration')
        ->and($json)->not->toContain('verdict')
        ->and($json)->not->toContain('enonce')
        ->and($json)->not->toContain('Il y a trop de ceci')
        ->and($json)->not->toContain('NOTE-INTERNE-42')
        ->and($json)->not->toContain('note_verification')
        ->and($json)->not->toContain('valide_par')
        ->and($json)->not->toContain('verifie_par');
});

it('n\'exporte que les séries Eurostat relues, et seulement celles des graphiques publiés', function () {
    $f = affirmationPubliable(['affiche_publiquement' => true]);
    $f->graphiques()->create([
        'type' => 'courbes', 'titre' => 'Née à l\'étranger', 'indicateurs' => ['part_nes_etranger'],
        'constat_id' => $f->constats()->first()->id,
    ]);
    EurostatIndicateur::create([
        'code' => 'part_nes_etranger', 'titre' => 'Part', 'unite' => '%', 'sources' => [['code' => 'migr_pop3ctb', 'url' => 'https://ec.europa.eu/x']],
        'series_publiees' => ['FR' => [['annee' => 2025, 'valeur' => 13.99, 'statut' => 'p']]],
        'extraction_publiee' => '2026-09-26',
        // Une révision en attente ne doit pas atteindre le site.
        'series_detectees' => ['FR' => [['annee' => 2025, 'valeur' => 99.9, 'statut' => '']]],
    ]);
    EurostatIndicateur::create([
        'code' => 'inflation_ipch', 'titre' => 'Inflation', 'unite' => '%', 'sources' => [],
        'series_publiees' => ['FR' => [['annee' => 2025, 'valeur' => 0.9, 'statut' => '']]],
    ]);

    $export = exportAffirmations();

    expect(array_keys((array) $export['indicateurs']))->toBe(['part_nes_etranger'])
        ->and($export['indicateurs']['part_nes_etranger']['series']['FR'][0]['valeur'])->toBe(13.99)
        ->and($export['indicateurs']['part_nes_etranger']['extraction'])->toBe('2026-09-26')
        ->and($export['reperes'][0]['graphiques'][0]['constat'])->toBe($f->constats()->first()->id);
});

it('déclenche un rebuild du front quand une fiche est publiée', function () {
    $f = affirmationPubliable();
    $avant = app(PresidentielleExporter::class)->build('2027')['meta']['content_hash'];

    $f->update(['affiche_publiquement' => true]);
    $apres = app(PresidentielleExporter::class)->build('2027')['meta']['content_hash'];

    expect($apres)->not->toBe($avant);
});
