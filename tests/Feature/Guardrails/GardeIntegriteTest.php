<?php

use App\Models\Argument;
use App\Models\ArgumentMesureLien;
use App\Models\ArgumentSource;
use App\Models\ProgrammeMesure;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\Presidentielle\IntegriteChecker;
use Spatie\Permission\Models\Permission;

/**
 * Aucune écriture du back-office ne doit pouvoir figer objectif2027.fr.
 *
 * Une violation d'intégrité fait refuser l'export, donc le rebuild : le site reste sur
 * son dernier état. Les gestes testés ici ne touchent pas l'élément fautif mais ce dont
 * il dépend — c'est pourquoi aucune règle de publication ne les arrêtait.
 */
function moderateurGarde(): User
{
    Permission::findOrCreate('moderer_presidentielle', 'web');
    $u = User::factory()->create();
    $u->givePermissionTo('moderer_presidentielle');

    return $u;
}

/** Une question « accord » publiée, adossée à la mesure donnée. */
function questionPublieeSur(ProgrammeMesure $mesure): QuizOption
{
    $q = QuizQuestion::factory()->create([
        'format' => 'accord', 'statut_validation' => 'valide', 'affiche_publiquement' => true,
    ]);
    $option = QuizOption::factory()->create(['question_id' => $q->id]);
    $option->mesures()->attach($mesure->id);

    return $option;
}

function violationsGarde(): array
{
    return collect(app(IntegriteChecker::class)->analyser('2027')['violations'])->pluck('type')->all();
}

it('part d\'un état sans violation', function () {
    [, , $mesure] = candidatPubliePublic();
    questionPublieeSur($mesure);

    expect(violationsGarde())->toBe([]);
});

it('refuse de dépublier une mesure sur laquelle repose une question de quiz publiée', function () {
    [, , $mesure] = candidatPubliePublic();
    questionPublieeSur($mesure);

    $this->actingAs(moderateurGarde())
        ->post(route('admin.presidentielle.moderation.action'), [
            'type' => 'mesure', 'id' => $mesure->id, 'action' => 'depublier',
        ])
        ->assertSessionHasErrors('integrite');

    expect($mesure->fresh()->affiche_publiquement)->toBeTrue()
        ->and(violationsGarde())->toBe([]);
});

it('refuse de détacher la dernière mesure d\'une option publiée', function () {
    [, , $mesure] = candidatPubliePublic();
    $option = questionPublieeSur($mesure);

    $this->actingAs(moderateurGarde())
        ->delete(route('admin.presidentielle.quiz.options.mesures.detach', $option), ['mesure_id' => $mesure->id])
        ->assertSessionHasErrors('integrite');

    expect($option->mesures()->count())->toBe(1);
});

it('refuse de dépublier le seul argument « contre » d\'une mesure publiée', function () {
    [, , $mesure] = candidatPubliePublic();
    $contre = $mesure->liens()->where('sens', 'contre')->firstOrFail();

    $this->actingAs(moderateurGarde())
        ->post(route('admin.presidentielle.moderation.action'), [
            'type' => 'argument', 'id' => $contre->argument_id, 'action' => 'depublier',
        ])
        ->assertSessionHasErrors('integrite');

    expect(Argument::find($contre->argument_id)->affiche_publiquement)->toBeTrue();
});

it('refuse de publier une première liaison « pour » sur une mesure publiée sans argumentaire', function () {
    $mesure = ProgrammeMesure::factory()->publie()->create([
        'candidat_id' => candidatPubliePublic()[0]->id,
        'source_officielle_url' => 'https://exemple.fr/programme#m2',
    ]);
    $arg = Argument::factory()->publie()->create();
    ArgumentSource::factory()->create(['argument_id' => $arg->id, 'fiabilite' => 'haute']);
    $lien = ArgumentMesureLien::factory()->pour()->create([
        'argument_id' => $arg->id, 'mesure_id' => $mesure->id,
        'statut_validation' => 'valide', 'affiche_publiquement' => false,
    ]);

    $this->actingAs(moderateurGarde())
        ->post(route('admin.presidentielle.moderation.action'), [
            'type' => 'argument_lien', 'id' => $lien->id, 'action' => 'publier',
        ])
        ->assertSessionHasErrors('integrite');

    expect($lien->fresh()->affiche_publiquement)->toBeFalse();
});

it('n\'empêche pas un geste sans rapport avec une violation déjà présente', function () {
    // Violation préexistante : une mesure publiée sans source.
    [$candidat] = candidatPubliePublic();
    ProgrammeMesure::factory()->publie()->create([
        'candidat_id' => $candidat->id, 'source_officielle_url' => null,
    ]);
    expect(violationsGarde())->toContain('mesure_sans_source');

    // Geste sans rapport : dépublier une autre mesure, dont rien ne dépend.
    $libre = ProgrammeMesure::factory()->publie()->create([
        'candidat_id' => $candidat->id, 'source_officielle_url' => 'https://exemple.fr/p#3',
    ]);

    $this->actingAs(moderateurGarde())
        ->post(route('admin.presidentielle.moderation.action'), [
            'type' => 'mesure', 'id' => $libre->id, 'action' => 'depublier',
        ])
        ->assertSessionHasNoErrors();

    expect($libre->fresh()->affiche_publiquement)->toBeFalse();
});

it('annule tout un lot qui introduirait une violation, et le dit', function () {
    [, , $adossee] = candidatPubliePublic();
    questionPublieeSur($adossee);
    [, , $libre] = candidatPubliePublic();

    $this->actingAs(moderateurGarde())
        ->post(route('admin.presidentielle.moderation.action-lot'), [
            'type' => 'mesure', 'ids' => [$adossee->id, $libre->id], 'action' => 'depublier',
        ])
        ->assertSessionHasErrors('integrite')
        ->assertSessionMissing('success');

    expect($adossee->fresh()->affiche_publiquement)->toBeTrue()
        ->and($libre->fresh()->affiche_publiquement)->toBeTrue();
});

it('ne tient plus pour fiable une source dont l\'URL est un placeholder', function () {
    $arg = Argument::factory()->publie()->create();
    ArgumentSource::factory()->create([
        'argument_id' => $arg->id, 'fiabilite' => 'haute', 'url' => 'A_COMPLETER',
    ]);

    // Le back-office et le contrôle d'intégrité disent désormais la même chose.
    expect($arg->fresh()->aSourceFiable())->toBeFalse();
});
