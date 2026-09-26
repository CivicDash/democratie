<?php

use App\Models\Affirmation;
use App\Models\EurostatIndicateur;
use App\Models\User;
use App\Services\Presidentielle\IntegriteChecker;
use App\Services\Presidentielle\ModerationService;
use Spatie\Permission\Models\Permission;

/**
 * Les règles de publication de « Ce qu'on entend ».
 *
 * Chaque règle est vérifiée deux fois : elle refuse le bouton « Publier » (service de
 * modération) ET elle apparaît au contrôle d'intégrité, dont une violation refuse
 * l'export. Les deux appellent le même code (ReglesAffirmation) : ce test garantit que ça
 * reste vrai.
 */
function raisonsFiche(Affirmation $f): array
{
    return app(ModerationService::class)->raisonsNonPubliable($f->fresh());
}

/** Publie la fiche en base, sans passer par le service, et renvoie les violations d'export. */
function violationsSiPubliee(Affirmation $f): array
{
    $f->update(['statut_validation' => 'valide', 'affiche_publiquement' => true]);

    return collect(app(IntegriteChecker::class)->analyser('2027')['violations'])
        ->where('type', 'affirmation_impubliable')->pluck('message')->all();
}

it('publie une fiche conforme', function () {
    $f = affirmationPubliable();

    expect(raisonsFiche($f))->toBe([]);
    app(ModerationService::class)->publier($f, User::factory()->create());

    expect($f->fresh()->affiche_publiquement)->toBeTrue()
        ->and(violationsSiPubliee($f))->toBe([]);
});

it('refuse une fiche, et la signale à l\'export, quand une règle manque', function (Closure $casser, string $raison) {
    $f = affirmationPubliable();
    $casser($f);

    expect(raisonsFiche($f))->toContain($raison)
        ->and(implode(' ', violationsSiPubliee($f)))->toContain($raison);
})->with([
    'aucun verdict' => [fn ($f) => $f->verdicts()->delete(), 'aucun verdict'],
    'deux verdicts sans portée' => [
        fn ($f) => $f->verdicts()->create(['ordre' => 1, 'verdict' => 'confirme']),
        'plusieurs verdicts : chacun doit dire sur quoi il porte',
    ],
    'aucun chiffre' => [fn ($f) => $f->constats()->where('section', 'chiffres')->delete(), 'aucun constat « Ce que disent les chiffres »'],
    'aucune limite' => [
        fn ($f) => $f->constats()->where('section', 'limites')->delete(),
        'aucune limite : la fiche doit dire ce que les chiffres ne disent pas',
    ],
    'un constat à vérifier' => [
        fn ($f) => $f->constats()->where('section', 'limites')->update(['verification' => 'a_verifier']),
        '1 constat(s) encore à vérifier',
    ],
    'un chiffre sans source' => [
        fn ($f) => $f->constats()->create(['section' => 'europe', 'texte' => 'Chiffre orphelin (2024).', 'verification' => 'verifie']),
        '1 constat(s) chiffré(s) sans source',
    ],
    'une source sans URL' => [fn ($f) => $f->sources()->update(['url' => null]), 'source(s) sans URL valide : insee-test'],
    'une source placeholder' => [fn ($f) => $f->sources()->update(['url' => 'A_COMPLETER']), 'source(s) sans URL valide : insee-test'],
    'un domaine exclu' => [
        fn ($f) => $f->sources()->update(['url' => 'https://www.cnews.fr/article']),
        'source(s) d\'un domaine exclu par le cadre éditorial : insee-test',
    ],
    'un thème inactif' => [fn ($f) => $f->theme->update(['actif' => false]), 'thème principal absent ou inactif'],
]);

it('n\'accepte un graphique qu\'accompagné d\'une phrase et sur des séries relues', function () {
    $f = affirmationPubliable();
    $g = $f->graphiques()->create([
        'type' => 'courbes', 'titre' => 'Part de la population née à l\'étranger',
        'indicateurs' => ['part_nes_etranger'],
    ]);

    $raisons = raisonsFiche($f);
    expect(collect($raisons)->contains(fn ($r) => str_contains($r, 'rattaché à aucune phrase')))->toBeTrue()
        ->and(collect($raisons)->contains(fn ($r) => str_contains($r, 'série Eurostat non validée (part_nes_etranger)')))->toBeTrue();

    // Une extraction détectée ne suffit pas : il faut la série PUBLIÉE.
    $ind = EurostatIndicateur::create([
        'code' => 'part_nes_etranger', 'titre' => 'Part', 'unite' => '%', 'sources' => [],
        'series_detectees' => ['FR' => [['annee' => 2025, 'valeur' => 14.0, 'statut' => '']]],
    ]);
    expect(collect(raisonsFiche($f))->contains(fn ($r) => str_contains($r, 'non validée')))->toBeTrue();

    $ind->update(['series_publiees' => $ind->series_detectees, 'series_detectees' => null]);
    $g->update(['constat_id' => $f->constats()->where('section', 'chiffres')->value('id')]);

    expect(raisonsFiche($f))->toBe([]);
});

it('refuse un graphique au mauvais nombre d\'indicateurs', function () {
    $f = affirmationPubliable();
    $f->graphiques()->create([
        'type' => 'barres_groupees', 'titre' => 'Emploi', 'indicateurs' => ['taux_emploi_nat'],
        'constat_id' => $f->constats()->first()->id,
    ]);

    expect(collect(raisonsFiche($f))->contains(fn ($r) => str_contains($r, '1 indicateur(s), le type en attend 2')))->toBeTrue();
});

it('refuse, sur une fiche publiée, la modification qui la rendrait impubliable', function () {
    Permission::findOrCreate('moderer_presidentielle', 'web');
    $mod = User::factory()->create();
    $mod->givePermissionTo('moderer_presidentielle');

    $f = affirmationPubliable();
    $f->update(['affiche_publiquement' => true]);
    $limite = $f->constats()->where('section', 'limites')->firstOrFail();

    $this->actingAs($mod)
        ->post(route('admin.presidentielle.affirmations.constats.verification', $limite), ['verification' => 'a_verifier'])
        ->assertSessionHasErrors('integrite');

    expect($limite->fresh()->verification)->toBe('verifie');

    // Dépubliée, la même modification passe.
    $f->update(['affiche_publiquement' => false]);
    $this->actingAs($mod)
        ->post(route('admin.presidentielle.affirmations.constats.verification', $limite), ['verification' => 'a_verifier'])
        ->assertSessionHasNoErrors();

    expect($limite->fresh()->verification)->toBe('a_verifier');
});

it('laisse passer contrôle et export tant que la migration n\'a pas tourné', function () {
    // Le code peut être déployé avant la migration. Le contrôle d'intégrité et l'export
    // tournent à chaque passage cron : s'ils levaient, le site serait figé. PostgreSQL
    // annule le DROP avec la transaction du test.
    \Illuminate\Support\Facades\DB::statement('DROP TABLE affirmations CASCADE');

    expect(fn () => app(IntegriteChecker::class)->analyser('2027'))->not->toThrow(Throwable::class)
        ->and(app(\App\Services\Presidentielle\PresidentielleExporter::class)->build('2027')['affirmations']['affirmations'])->toBe([]);
});
