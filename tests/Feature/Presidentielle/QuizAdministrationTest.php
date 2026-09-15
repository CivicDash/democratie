<?php

use App\Models\CandidatPresidentielle;
use App\Models\ProgrammeMesure;
use App\Models\ProgrammeTheme;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Spatie\Permission\Models\Permission;

/**
 * Le parcours complet d'administration d'une question de quiz, par HTTP.
 *
 * Les règles de neutralité sont déjà couvertes unitairement par
 * `Guardrails/QuizNeutraliteTest`. Ce que l'on vérifie ici, c'est qu'un modérateur peut
 * réellement les atteindre : créer, ajouter des options, trouver des mesures, les
 * rattacher — et se heurter au refus quand la question n'oppose personne.
 */
function moderateurQuiz(): User
{
    Permission::findOrCreate('moderer_presidentielle', 'web');
    $u = User::factory()->create();
    $u->givePermissionTo('moderer_presidentielle');

    return $u;
}

function mesurePublieeDe(?int $candidatId = null, ?int $themeId = null): ProgrammeMesure
{
    return ProgrammeMesure::factory()->create([
        'candidat_id' => $candidatId ?? CandidatPresidentielle::factory()->create([
            'election' => '2027', 'statut_validation' => 'valide', 'affiche_publiquement' => true,
        ])->id,
        'theme_id' => $themeId ?? ProgrammeTheme::factory()->create()->id,
        'statut_validation' => 'valide',
        'affiche_publiquement' => true,
        'source_officielle_url' => 'https://exemple.fr/programme',
    ]);
}

it('mène une question de la création à la publication', function () {
    $mod = moderateurQuiz();
    $theme = ProgrammeTheme::factory()->create(['nom' => 'Fiscalité & budget']);

    // 1. Création — toujours en `detecte`, et la redirection mène au détail.
    $this->actingAs($mod)->post(route('admin.presidentielle.quiz.store'), [
        'theme_id' => $theme->id,
        'format' => 'arbitrage',
        'intitule' => 'Allègements de cotisations : réduire, maintenir ou rétablir ?',
    ])->assertSessionHasNoErrors();

    $question = QuizQuestion::firstOrFail();
    expect($question->statut_validation)->toBe('detecte')
        ->and($question->affiche_publiquement)->toBeFalse();

    // 2. Deux options.
    foreach (['Réduire', 'Rétablir'] as $libelle) {
        $this->actingAs($mod)->post(route('admin.presidentielle.quiz.options.store'), [
            'question_id' => $question->id, 'libelle' => $libelle,
        ])->assertSessionHasNoErrors();
    }
    [$reduire, $retablir] = QuizQuestion::find($question->id)->options->all();

    // 3. Rattachement de mesures de DEUX candidats différents.
    $m1 = mesurePublieeDe(null, $theme->id);
    $m2 = mesurePublieeDe(null, $theme->id);

    $this->actingAs($mod)->post(route('admin.presidentielle.quiz.options.mesures.attach', $reduire->id), [
        'mesure_id' => $m1->id,
    ])->assertSessionHasNoErrors();

    // Tant qu'une seule option est adossée, la publication reste refusée.
    $this->actingAs($mod)->post(route('admin.presidentielle.moderation.action'), [
        'type' => 'quiz_question', 'id' => $question->id, 'action' => 'valider',
    ])->assertSessionHasNoErrors();

    $this->actingAs($mod)->post(route('admin.presidentielle.moderation.action'), [
        'type' => 'quiz_question', 'id' => $question->id, 'action' => 'publier',
    ])->assertSessionHasErrors('action');
    expect($question->fresh()->affiche_publiquement)->toBeFalse();

    // 4. Seconde option adossée : la question oppose enfin deux candidats.
    $this->actingAs($mod)->post(route('admin.presidentielle.quiz.options.mesures.attach', $retablir->id), [
        'mesure_id' => $m2->id,
    ])->assertSessionHasNoErrors();

    $this->actingAs($mod)->post(route('admin.presidentielle.moderation.action'), [
        'type' => 'quiz_question', 'id' => $question->id, 'action' => 'publier',
    ])->assertSessionHasNoErrors();

    expect($question->fresh()->affiche_publiquement)->toBeTrue();
});

it('refuse d\'adosser une option à une mesure non publiée', function () {
    $mod = moderateurQuiz();
    $option = QuizOption::factory()->create();
    $brouillon = ProgrammeMesure::factory()->create([
        'candidat_id' => CandidatPresidentielle::factory(),
        'statut_validation' => 'valide',
        'affiche_publiquement' => false,
    ]);

    // Le refus doit venir AVANT l'écriture : sinon la question deviendrait impubliable
    // sans que rien n'explique pourquoi, et le contrôle d'intégrité figerait l'export.
    $this->actingAs($mod)->post(route('admin.presidentielle.quiz.options.mesures.attach', $option->id), [
        'mesure_id' => $brouillon->id,
    ])->assertSessionHasErrors('mesure_id');

    expect($option->fresh()->mesures)->toHaveCount(0);
});

it('traduit le doublon de rattachement en erreur lisible', function () {
    $mod = moderateurQuiz();
    $option = QuizOption::factory()->create();
    $mesure = mesurePublieeDe();

    $this->actingAs($mod)->post(route('admin.presidentielle.quiz.options.mesures.attach', $option->id), [
        'mesure_id' => $mesure->id,
    ])->assertSessionHasNoErrors();

    $this->actingAs($mod)->post(route('admin.presidentielle.quiz.options.mesures.attach', $option->id), [
        'mesure_id' => $mesure->id,
    ])->assertSessionHasErrors('mesure_id');

    expect($option->fresh()->mesures)->toHaveCount(1);
});

it('ne propose à la recherche que des mesures publiées', function () {
    $mod = moderateurQuiz();
    $theme = ProgrammeTheme::factory()->create();

    $publiee = mesurePublieeDe(null, $theme->id);
    $publiee->update(['titre' => 'Supprimer la contribution audiovisuelle publique']);

    ProgrammeMesure::factory()->create([
        'candidat_id' => CandidatPresidentielle::factory(),
        'theme_id' => $theme->id,
        'titre' => 'Supprimer autre chose, mais non publiée',
        'statut_validation' => 'valide',
        'affiche_publiquement' => false,
    ]);

    $reponse = $this->actingAs($mod)
        ->getJson(route('admin.presidentielle.quiz.mesures.search', ['q' => 'Supprimer']));

    $reponse->assertOk();
    $titres = collect($reponse->json('mesures'))->pluck('titre');

    expect($titres)->toHaveCount(1)
        ->and($titres->first())->toBe('Supprimer la contribution audiovisuelle publique');
});

it('ne cherche pas sur un terme trop court et sans filtre de thème', function () {
    $mod = moderateurQuiz();
    mesurePublieeDe();

    // Sans garde, chaque frappe ramènerait les 523 mesures publiées.
    $this->actingAs($mod)
        ->getJson(route('admin.presidentielle.quiz.mesures.search', ['q' => 'a']))
        ->assertOk()
        ->assertJson(['mesures' => []]);
});

it('refuse l\'accès sans la permission', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.presidentielle.quiz'))
        ->assertForbidden();
});
