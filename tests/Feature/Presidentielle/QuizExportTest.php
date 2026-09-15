<?php

use App\Models\CandidatPresidentielle;
use App\Models\ProgrammeMesure;
use App\Models\ProgrammeTheme;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Services\Presidentielle\PresidentielleExporter;

/**
 * Export du quiz vers le front statique.
 *
 * Deux points sensibles y sont vérifiés : la clé `quiz` doit être prise dans le
 * `content_hash` — c'est lui qui déclenche le rebuild d'objectif2027.fr, et une clé posée
 * hors de `$contenu` ne serait jamais hachée ; et seules les questions publiées doivent
 * sortir, avec leurs seules options adossées à des mesures publiées.
 */
function questionPubliable(?ProgrammeTheme $theme = null): QuizQuestion
{
    $theme ??= ProgrammeTheme::factory()->create();
    $question = QuizQuestion::factory()->create([
        'theme_id' => $theme->id, 'format' => 'arbitrage', 'statut_validation' => 'valide',
    ]);

    foreach (['Réduire', 'Rétablir'] as $libelle) {
        $option = QuizOption::factory()->create(['question_id' => $question->id, 'libelle' => $libelle]);
        $option->mesures()->attach(ProgrammeMesure::factory()->create([
            'candidat_id' => CandidatPresidentielle::factory()->create([
                'election' => '2027', 'statut_validation' => 'valide', 'affiche_publiquement' => true,
            ])->id,
            'theme_id' => $theme->id,
            'statut_validation' => 'valide',
            'affiche_publiquement' => true,
            'source_officielle_url' => 'https://exemple.fr/programme',
        ])->id);
    }

    return $question->fresh();
}

it('n\'exporte que les questions publiées', function () {
    $publiee = questionPubliable();
    $publiee->update(['affiche_publiquement' => true]);
    questionPubliable(); // validée mais non publiée

    $data = app(PresidentielleExporter::class)->build('2027');

    expect($data['quiz']['questions'])->toHaveCount(1)
        ->and($data['quiz']['questions'][0]['ref'])->toBe($publiee->uuid)
        ->and($data['quiz']['questions'][0]['options'])->toHaveCount(2);
});

it('crédite tous les candidats qui portent une même option', function () {
    $theme = ProgrammeTheme::factory()->create();
    $question = questionPubliable($theme);
    $question->update(['affiche_publiquement' => true]);

    // Un second candidat formule la même position autrement.
    $premiere = $question->options->first();
    $premiere->mesures()->attach(ProgrammeMesure::factory()->create([
        'candidat_id' => CandidatPresidentielle::factory()->create([
            'election' => '2027', 'statut_validation' => 'valide', 'affiche_publiquement' => true,
        ])->id,
        'theme_id' => $theme->id,
        'statut_validation' => 'valide', 'affiche_publiquement' => true,
        'source_officielle_url' => 'https://exemple.fr/autre',
    ])->id);

    $data = app(PresidentielleExporter::class)->build('2027');
    $option = collect($data['quiz']['questions'][0]['options'])->firstWhere('ref', $premiere->uuid);

    expect($option['candidats'])->toHaveCount(2)
        ->and($option['mesures'])->toHaveCount(2);
});

it('écarte d\'une option les mesures non publiées', function () {
    $question = questionPubliable();
    $question->update(['affiche_publiquement' => true]);

    $option = $question->options->first();
    $option->mesures()->attach(ProgrammeMesure::factory()->create([
        'candidat_id' => CandidatPresidentielle::factory(),
        'statut_validation' => 'valide', 'affiche_publiquement' => false,
    ])->id);

    $data = app(PresidentielleExporter::class)->build('2027');
    $exportee = collect($data['quiz']['questions'][0]['options'])->firstWhere('ref', $option->uuid);

    expect($exportee['mesures'])->toHaveCount(1);
});

it('fait varier le content_hash quand une question est publiée', function () {
    $question = questionPubliable();

    $avant = app(PresidentielleExporter::class)->build('2027')['meta']['content_hash'];
    $question->update(['affiche_publiquement' => true]);
    $apres = app(PresidentielleExporter::class)->build('2027')['meta']['content_hash'];

    // Sans cela, deploy.sh comparerait deux hash identiques et ne rebâtirait jamais le
    // front : la question resterait invisible sans que rien ne signale pourquoi.
    expect($apres)->not->toBe($avant);
});

it('écrit quiz.json', function () {
    $question = questionPubliable();
    $question->update(['affiche_publiquement' => true]);

    $dir = storage_path('app/testing/quiz-export');
    $exporter = app(PresidentielleExporter::class);
    $exporter->write($exporter->build('2027'), $dir);

    expect(file_exists("$dir/quiz.json"))->toBeTrue();

    $contenu = json_decode(file_get_contents("$dir/quiz.json"), true);
    expect($contenu['questions'])->toHaveCount(1);

    \Illuminate\Support\Facades\File::deleteDirectory($dir);
});
