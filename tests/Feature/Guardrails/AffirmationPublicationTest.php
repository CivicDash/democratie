<?php

use App\Models\Affirmation;
use App\Models\EurostatIndicateur;
use App\Models\User;
use App\Services\Presidentielle\IntegriteChecker;
use App\Services\Presidentielle\ModerationService;
use Spatie\Permission\Models\Permission;

/**
 * Les règles de publication des repères chiffrés (ex-« Ce qu'on entend »).
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

it('publie un repère sans aucun verdict', function () {
    // Le format à verdict est abandonné : un repère se publie sans verdict, et un verdict
    // resté en base (ancien format) ne change rien.
    $f = affirmationPubliable();
    expect($f->verdicts()->count())->toBe(0)
        ->and(raisonsFiche($f))->toBe([]);
});

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
    'aucune question' => [fn ($f) => $f->update(['question' => null]), 'aucune question : le titre public d\'un repère est une question neutre'],
    'une affirmation au lieu d\'une question' => [
        fn ($f) => $f->update(['question' => 'Il y a trop de ceci']),
        'la question doit se terminer par un point d\'interrogation',
    ],
    'aucun chiffre' => [fn ($f) => $f->constats()->where('section', 'chiffres')->delete(), 'aucun constat « Ce que disent les chiffres » vérifié'],
    'aucun chiffre vérifié' => [
        fn ($f) => $f->constats()->where('section', 'chiffres')->update(['verification' => 'a_verifier']),
        'aucun constat « Ce que disent les chiffres » vérifié',
    ],
    'aucune limite' => [
        fn ($f) => $f->constats()->where('section', 'limites')->delete(),
        'aucune limite : la fiche doit dire ce que les chiffres ne disent pas',
    ],
    'une réserve à vérifier' => [
        fn ($f) => $f->constats()->where('section', 'limites')->update(['verification' => 'a_verifier']),
        '1 phrase(s) « Ce que les chiffres ne disent pas » à vérifier : une fiche ne paraît jamais sans toutes ses réserves',
    ],
    'un chiffre vérifié sans source' => [
        fn ($f) => $f->constats()->create(['section' => 'europe', 'texte' => 'Chiffre orphelin (2024).', 'verification' => 'verifie']),
        '1 constat(s) chiffré(s) vérifié(s) sans source',
    ],
    'une source sans URL' => [fn ($f) => $f->sources()->update(['url' => null]), 'source(s) sans URL valide : insee-test'],
    'une source placeholder' => [fn ($f) => $f->sources()->update(['url' => 'A_COMPLETER']), 'source(s) sans URL valide : insee-test'],
    'un domaine exclu' => [
        fn ($f) => $f->sources()->update(['url' => 'https://www.cnews.fr/article']),
        'source(s) d\'un domaine exclu par le cadre éditorial : insee-test',
    ],
    'un thème inactif' => [fn ($f) => $f->theme->update(['actif' => false]), 'thème principal absent ou inactif'],
]);

it('publie une fiche dont des phrases restent à sourcer : elles sont masquées, pas bloquantes', function () {
    $f = affirmationPubliable();
    // Ni source, ni vérification : masquée, elle ne compte pas.
    $f->constats()->create(['section' => 'chiffres', 'ordre' => 5, 'texte' => 'Valeur 2024 à reprendre.', 'verification' => 'a_verifier']);
    // Sa source n'a pas d'URL : elle ne paraîtra qu'avec la phrase, donc pas encore.
    $masquee = $f->constats()->create(['section' => 'complement', 'ordre' => 6, 'texte' => 'Départs de France (2023).', 'verification' => 'a_verifier']);
    $masquee->sources()->attach($f->sources()->create(['cle' => 'sans-url', 'producteur' => 'X', 'titre' => 'Y', 'categorie' => 'presse'])->id);
    // Son graphique porte sur une série jamais relue : il ne paraîtra qu'avec sa phrase.
    $f->graphiques()->create(['type' => 'courbes', 'titre' => 'Départs', 'indicateurs' => ['emigration_nat'], 'constat_id' => $masquee->id]);

    expect(raisonsFiche($f))->toBe([])
        ->and(violationsSiPubliee($f))->toBe([]);

    // Vérifiée, la phrase paraîtrait : ses défauts redeviennent bloquants.
    $masquee->update(['verification' => 'verifie']);
    $raisons = raisonsFiche($f);
    expect($raisons)->toContain('source(s) sans URL valide : sans-url')
        ->and(collect($raisons)->contains(fn ($r) => str_contains($r, 'série Eurostat non validée (emigration_nat)')))->toBeTrue();
});

it('n\'accepte un graphique qu\'accompagné d\'une phrase et sur des séries relues', function () {
    $f = affirmationPubliable();
    $g = $f->graphiques()->create([
        'type' => 'courbes', 'titre' => 'Part de la population née à l\'étranger',
        'indicateurs' => ['part_nes_etranger'],
    ]);

    expect(collect(raisonsFiche($f))->contains(fn ($r) => str_contains($r, 'rattaché à aucune phrase')))->toBeTrue();

    // Rattaché à une phrase vérifiée, il paraîtra : sa série doit être relue.
    $g->update(['constat_id' => $f->constats()->where('section', 'chiffres')->value('id')]);
    expect(collect(raisonsFiche($f))->contains(fn ($r) => str_contains($r, 'série Eurostat non validée (part_nes_etranger)')))->toBeTrue();

    // Une extraction détectée ne suffit pas : il faut la série PUBLIÉE.
    $ind = EurostatIndicateur::create([
        'code' => 'part_nes_etranger', 'titre' => 'Part', 'unite' => '%', 'sources' => [],
        'series_detectees' => ['FR' => [['annee' => 2025, 'valeur' => 14.0, 'statut' => '']]],
    ]);
    expect(collect(raisonsFiche($f))->contains(fn ($r) => str_contains($r, 'non validée')))->toBeTrue();

    $ind->update(['series_publiees' => $ind->series_detectees, 'series_detectees' => null]);

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

it('accepte, sur une fiche publiée, de remettre à vérifier un chiffre qui n\'est pas le seul', function () {
    Permission::findOrCreate('moderer_presidentielle', 'web');
    $mod = User::factory()->create();
    $mod->givePermissionTo('moderer_presidentielle');

    $f = affirmationPubliable();
    $second = $f->constats()->create(['section' => 'limites', 'ordre' => 2, 'texte' => 'Autre réserve.', 'verification' => 'verifie']);
    $chiffre = $f->constats()->create(['section' => 'chiffres', 'ordre' => 3, 'texte' => '12 % (2020) → 13 % (2024).', 'verification' => 'verifie']);
    $chiffre->sources()->attach($f->sources()->value('id'));
    $f->update(['statut_validation' => 'valide', 'affiche_publiquement' => true]);

    // Le chiffre disparaît simplement du site au prochain export.
    $this->actingAs($mod)
        ->post(route('admin.presidentielle.affirmations.constats.verification', $chiffre), ['verification' => 'a_verifier'])
        ->assertSessionHasNoErrors();
    expect($chiffre->fresh()->verification)->toBe('a_verifier');

    // Une réserve, jamais.
    $this->actingAs($mod)
        ->post(route('admin.presidentielle.affirmations.constats.verification', $second), ['verification' => 'a_verifier'])
        ->assertSessionHasErrors('integrite');
    expect($second->fresh()->verification)->toBe('verifie');
});

it('laisse passer contrôle et export tant que la migration n\'a pas tourné', function () {
    // Le code peut être déployé avant la migration. Le contrôle d'intégrité et l'export
    // tournent à chaque passage cron : s'ils levaient, le site serait figé. PostgreSQL
    // annule le DROP avec la transaction du test.
    \Illuminate\Support\Facades\DB::statement('DROP TABLE affirmations CASCADE');

    expect(fn () => app(IntegriteChecker::class)->analyser('2027'))->not->toThrow(Throwable::class)
        ->and(app(\App\Services\Presidentielle\PresidentielleExporter::class)->build('2027')['reperes']['reperes'])->toBe([]);
});
