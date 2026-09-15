<?php

use App\Models\CandidatPresidentielle;
use App\Models\ProgrammeMesure;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\Presidentielle\IntegriteChecker;
use App\Services\Presidentielle\ModerationService;

/**
 * Garde-fous de neutralité du quiz.
 *
 * Le quiz est l'endroit où la neutralité est la plus facile à perdre : il produit un
 * rapprochement entre un lecteur et des candidats. Les règles ci-dessous sont donc
 * vérifiées AUX DEUX ENDROITS où elles vivent — le service, qui bloque la publication,
 * et le contrôle d'intégrité, dont une violation refuse l'export et fige le site public.
 * C'est la leçon de la symétrie des mesures : une règle en double qui diverge transforme
 * une publication autorisée en panne sans rapport apparent.
 */
beforeEach(function () {
    $this->service = app(ModerationService::class);
    $this->moderateur = User::factory()->create();
});

/** Une mesure publiée d'un candidat publié : le seul matériau admis dans une option. */
function mesurePubliee(): ProgrammeMesure
{
    return ProgrammeMesure::factory()->create([
        'candidat_id' => CandidatPresidentielle::factory()->create([
            'election' => '2027', 'statut_validation' => 'valide', 'affiche_publiquement' => true,
        ]),
        'statut_validation' => 'valide',
        'affiche_publiquement' => true,
        'source_officielle_url' => 'https://exemple.fr/programme',
    ]);
}

function option(QuizQuestion $q, string $libelle, ?ProgrammeMesure $m = null): QuizOption
{
    $o = QuizOption::factory()->create(['question_id' => $q->id, 'libelle' => $libelle]);
    if ($m) {
        $o->mesures()->attach($m->id);
    }

    return $o;
}

it('publie un arbitrage opposant deux candidats', function () {
    $q = QuizQuestion::factory()->create(['format' => 'arbitrage']);
    option($q, 'Réduire', mesurePubliee());
    option($q, 'Rétablir', mesurePubliee());

    expect($this->service->raisonsNonPubliable($q->fresh()))->toBe([]);

    $this->service->publier($q, $this->moderateur);
    expect($q->fresh()->affiche_publiquement)->toBeTrue();
});

it('refuse un arbitrage à une seule option', function () {
    $q = QuizQuestion::factory()->create(['format' => 'arbitrage']);
    option($q, 'Réduire', mesurePubliee());

    expect($this->service->raisonsNonPubliable($q->fresh()))
        ->toContain('un arbitrage demande au moins deux options — une seule serait un plébiscite');
});

it('refuse un arbitrage dont toutes les options viennent du même candidat', function () {
    $mesure = mesurePubliee();
    $autre = ProgrammeMesure::factory()->create([
        'candidat_id' => $mesure->candidat_id,
        'statut_validation' => 'valide', 'affiche_publiquement' => true,
        'source_officielle_url' => 'https://exemple.fr/programme',
    ]);

    $q = QuizQuestion::factory()->create(['format' => 'arbitrage']);
    option($q, 'Première position', $mesure);
    option($q, 'Seconde position', $autre);

    expect($this->service->raisonsNonPubliable($q->fresh()))
        ->toContain('toutes les options viennent du même candidat — ce n\'est pas un arbitrage');
});

it('refuse une option que rien ne rattache à une mesure publiée', function () {
    $q = QuizQuestion::factory()->create(['format' => 'arbitrage']);
    option($q, 'Réduire', mesurePubliee());
    option($q, 'Une position que nous aurions inventée');

    expect($this->service->raisonsNonPubliable($q->fresh()))
        ->toContain('chaque option doit être adossée à au moins une mesure publiée');
});

it('refuse une option adossée à une mesure non publiée', function () {
    $brouillon = ProgrammeMesure::factory()->create([
        'candidat_id' => CandidatPresidentielle::factory(),
        'statut_validation' => 'valide', 'affiche_publiquement' => false,
    ]);

    $q = QuizQuestion::factory()->create(['format' => 'arbitrage']);
    option($q, 'Réduire', mesurePubliee());
    option($q, 'Rétablir', $brouillon);

    expect($this->service->raisonsNonPubliable($q->fresh()))
        ->toContain('chaque option doit être adossée à au moins une mesure publiée');
});

it('publie un accord portant sur une seule proposition', function () {
    $q = QuizQuestion::factory()->create(['format' => 'accord']);
    option($q, 'Instaurer ce dispositif', mesurePubliee());

    expect($this->service->raisonsNonPubliable($q->fresh()))->toBe([]);
});

it('refuse un accord à plusieurs propositions', function () {
    $q = QuizQuestion::factory()->create(['format' => 'accord']);
    option($q, 'Première', mesurePubliee());
    option($q, 'Seconde', mesurePubliee());

    expect($this->service->raisonsNonPubliable($q->fresh()))
        ->toContain('un accord porte sur une seule proposition');
});

it('crédite tous les candidats qui portent une même position', function () {
    $q = QuizQuestion::factory()->create(['format' => 'arbitrage']);
    $o = option($q, 'Réduire', mesurePubliee());
    $o->mesures()->attach(mesurePubliee()->id);
    option($q, 'Rétablir', mesurePubliee());

    // Le cas que l'ancien modèle ne savait pas représenter : une position défendue par
    // deux candidats n'en créditait qu'un seul.
    expect($o->fresh()->mesures)->toHaveCount(2)
        ->and($this->service->raisonsNonPubliable($q->fresh()))->toBe([]);
});

it('refuse l\'export quand une question publiée est mono-candidat', function () {
    $mesure = mesurePubliee();
    $autre = ProgrammeMesure::factory()->create([
        'candidat_id' => $mesure->candidat_id,
        'statut_validation' => 'valide', 'affiche_publiquement' => true,
        'source_officielle_url' => 'https://exemple.fr/programme',
    ]);

    $q = QuizQuestion::factory()->create(['format' => 'arbitrage', 'affiche_publiquement' => true]);
    option($q, 'Première', $mesure);
    option($q, 'Seconde', $autre);

    $types = collect(app(IntegriteChecker::class)->analyser('2027')['violations'])->pluck('type');

    expect($types)->toContain('quiz_arbitrage_mono_candidat');
});

it('laisse passer l\'export quand la question publiée est régulière', function () {
    $q = QuizQuestion::factory()->create(['format' => 'arbitrage', 'affiche_publiquement' => true]);
    option($q, 'Réduire', mesurePubliee());
    option($q, 'Rétablir', mesurePubliee());

    $types = collect(app(IntegriteChecker::class)->analyser('2027')['violations'])->pluck('type');

    expect($types->filter(fn ($t) => str_starts_with($t, 'quiz_')))->toBeEmpty();
});
