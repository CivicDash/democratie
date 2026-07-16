<?php

use App\Models\Argument;
use App\Models\ArgumentMesureLien;
use App\Models\CandidatPresidentielle;
use App\Models\Controverse;
use App\Models\PersonnePolitique;
use App\Models\ProgrammeMesure;
use App\Models\ProgrammeTheme;
use Illuminate\Support\Facades\Artisan;

function ecrireJsonArguments(array $data): string
{
    $path = sys_get_temp_dir().'/args_'.uniqid().'.json';
    file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE));

    return $path;
}

function melenchonAvecMesure(string $titre, string $resume = ''): array
{
    $theme = ProgrammeTheme::factory()->create(['slug' => 'travail-retraites']);
    $personne = PersonnePolitique::factory()->create(['slug' => 'jean-luc-melenchon']);
    $candidat = CandidatPresidentielle::factory()->create(['personne_politique_id' => $personne->id, 'election' => '2027']);
    $mesure = ProgrammeMesure::factory()->create([
        'candidat_id' => $candidat->id, 'theme_id' => $theme->id, 'titre' => $titre, 'resume' => $resume,
    ]);

    return [$candidat, $theme, $mesure];
}

it('importe une controverse v1.2 : faits autonomes, sources normalisées, liaisons auto-appariées', function () {
    [, $theme, $mesure] = melenchonAvecMesure('Retour de la retraite à 60 ans', 'Abrogation de la réforme des 64 ans');

    $path = ecrireJsonArguments([
        'contrat_version' => '1.2',
        'type_import' => 'arguments_controverse',
        'controverse' => [
            'slug' => 'age-depart-retraite', 'titre' => 'Âge de départ', 'theme_slug' => 'travail-retraites',
            'mesures_liees' => [
                ['ref' => 'M1', 'candidat_slug' => 'jean-luc-melenchon', 'mesure' => 'retour de la retraite à 60 ans abrogation de la réforme des 64 ans'],
            ],
        ],
        'note_methodologique_a_afficher' => 'Indicateurs différents.',
        'arguments' => [[
            'id' => 'A1', 'titre' => 'EVSI 64 ans', 'contenu' => 'Espérance de vie sans incapacité de 64 ans (DREES).',
            'type' => 'etude',
            'sources' => [['institution' => 'DREES', 'url' => 'https://drees.gouv.fr/x', 'fiabilite' => 'haute — source primaire', 'archive_url' => null]],
            'liens' => [['mesure_ref' => 'M1', 'sens' => 'pour', 'note' => 'maximise les années sans incapacité']],
        ]],
    ]);

    expect(Artisan::call('presidentielle:import-arguments', ['fichier' => $path]))->toBe(0);

    $controverse = Controverse::where('slug', 'age-depart-retraite')->first();
    expect($controverse)->not->toBeNull()
        ->and($controverse->theme_id)->toBe($theme->id)
        ->and($controverse->note_methodologique)->toBe('Indicateurs différents.')
        ->and($controverse->statut_validation)->toBe('detecte');

    $arg = Argument::first();
    expect($arg->titre)->toBe('EVSI 64 ans')
        ->and($arg->controverse_id)->toBe($controverse->id)
        ->and($arg->statut_validation)->toBe('detecte')
        ->and($arg->sources)->toHaveCount(1)
        ->and($arg->sources->first()->fiabilite)->toBe('haute');

    $lien = ArgumentMesureLien::first();
    expect($lien->sens)->toBe('pour')
        ->and($lien->mesure_id)->toBe($mesure->id)              // auto-match résolu
        ->and($lien->note_contextuelle)->toBe('maximise les années sans incapacité')
        ->and($lien->source_detection)->toBe('suggestion_auto')
        ->and((float) $lien->detection_confidence)->toBeGreaterThan(0.34);
});

it('laisse la liaison « à résoudre » quand aucune mesure ne correspond', function () {
    melenchonAvecMesure('Sujet totalement différent', 'école primaire numérique');

    $path = ecrireJsonArguments([
        'type_import' => 'arguments_controverse',
        'controverse' => [
            'slug' => 'c-x', 'titre' => 'X',
            'mesures_liees' => [['ref' => 'M1', 'candidat_slug' => 'jean-luc-melenchon', 'mesure' => 'retraite à soixante ans']],
        ],
        'arguments' => [[
            'titre' => 'Fait', 'contenu' => 'Contenu.', 'type' => 'chiffrage', 'sources' => [],
            'liens' => [['mesure_ref' => 'M1', 'sens' => 'contre', 'note' => 'note']],
        ]],
    ]);

    expect(Artisan::call('presidentielle:import-arguments', ['fichier' => $path]))->toBe(0);

    $lien = ArgumentMesureLien::first();
    expect($lien->mesure_id)->toBeNull()                        // sous le seuil → non apparié
        ->and($lien->candidat_slug_propose)->toBe('jean-luc-melenchon')
        ->and($lien->mesure_proposee)->toBe('retraite à soixante ans');
});

it('importe le format v1.1 (arguments_pour/contre) et porte la note dans une controverse', function () {
    [, , $mesure] = melenchonAvecMesure('SMIC à 1700 euros', 'hausse du salaire minimum');

    $path = ecrireJsonArguments([
        'contrat_version' => '1.1',
        'type_import' => 'arguments',
        'mesures_cibles' => [['candidat_slug' => 'jean-luc-melenchon', 'reference' => 'smic à 1700 euros hausse du salaire minimum', 'note' => 'hausse ~20%']],
        'note_methodologique_a_afficher' => 'Périmètres différents.',
        'arguments_pour' => [[
            'titre' => 'Sortir du travail pauvre', 'contenu' => 'Contenu factuel.', 'type' => 'etude',
            'sources' => [['institution' => 'OFCE', 'url' => null, 'fiabilite' => 'moyenne — à lier']],
        ]],
        'arguments_contre' => [[
            'titre' => 'Destructions d\'emplois', 'contenu' => 'Contenu factuel.', 'type' => 'chiffrage',
            'sources' => [['institution' => 'IFRAP', 'url' => 'https://ifrap.org/x', 'fiabilite' => 'moyenne']],
        ]],
    ]);

    expect(Artisan::call('presidentielle:import-arguments', ['fichier' => $path]))->toBe(0);

    expect(Argument::count())->toBe(2)
        ->and(ArgumentMesureLien::where('sens', 'pour')->count())->toBe(1)
        ->and(ArgumentMesureLien::where('sens', 'contre')->count())->toBe(1)
        ->and(Controverse::count())->toBe(1);                   // note méthodologique portée

    // les deux liaisons sont appariées sur la même mesure cible
    expect(ArgumentMesureLien::whereNotNull('mesure_id')->where('mesure_id', $mesure->id)->count())->toBe(2);

    // une source sans URL est acceptée à l'import (à lier avant publication)
    $sansUrl = App\Models\ArgumentSource::whereNull('url')->first();
    expect($sansUrl)->not->toBeNull()->and($sansUrl->fiabilite)->toBe('moyenne');
});

it('n\'écrit rien en --dry-run', function () {
    melenchonAvecMesure('SMIC à 1700 euros');
    $path = ecrireJsonArguments([
        'type_import' => 'arguments',
        'mesures_cibles' => [['candidat_slug' => 'jean-luc-melenchon', 'reference' => 'smic']],
        'arguments_pour' => [['titre' => 'T', 'contenu' => 'C', 'type' => 'etude', 'sources' => []]],
    ]);

    expect(Artisan::call('presidentielle:import-arguments', ['fichier' => $path, '--dry-run' => true]))->toBe(0)
        ->and(Argument::count())->toBe(0)
        ->and(ArgumentMesureLien::count())->toBe(0);
});
