<?php

use App\Models\Argument;
use App\Models\ArgumentMesureLien;
use App\Models\ArgumentSource;
use App\Models\CandidatPresidentielle;
use App\Models\ProgrammeMesure;
use App\Models\User;
use App\Services\Presidentielle\IntegriteChecker;
use App\Services\Presidentielle\ModerationService;

/**
 * La symétrie porte sur l'argumentaire, pas sur la mesure.
 *
 * Une mesure sans argument publié est un relevé sourcé de ce que propose un candidat :
 * rien à équilibrer. Dès qu'un argument est publié, les deux sens deviennent obligatoires.
 *
 * La règle vit en DEUX exemplaires — le service de modération, qui bloque le bouton
 * « Publier », et le contrôle d'intégrité, dont une violation REFUSE l'export et fige
 * donc objectif2027.fr. Les deux sont vérifiés ici : si elles divergeaient, publier une
 * mesure autorisée par le back-office gèlerait le site entier, sans rapport visible avec
 * le geste qui l'a causé.
 */
beforeEach(function () {
    $this->service = app(ModerationService::class);
    $this->moderateur = User::factory()->create();

    $this->candidat = CandidatPresidentielle::factory()->create([
        'election' => '2027', 'statut_validation' => 'valide', 'affiche_publiquement' => true,
    ]);

    $this->mesure = ProgrammeMesure::factory()->create([
        'candidat_id' => $this->candidat->id,
        'statut_validation' => 'valide',
        'affiche_publiquement' => false,
        'source_officielle_url' => 'https://exemple.fr/programme',
    ]);
});

/** Un fait publié, sourcé fiablement, relié à la mesure dans le sens demandé. */
function argumentPublie(ProgrammeMesure $m, string $sens): ArgumentMesureLien
{
    $a = Argument::factory()->create(['statut_validation' => 'valide', 'affiche_publiquement' => true]);
    ArgumentSource::factory()->create(['argument_id' => $a->id, 'fiabilite' => 'haute']);

    return ArgumentMesureLien::factory()->create([
        'argument_id' => $a->id,
        'mesure_id' => $m->id,
        'sens' => $sens,
        'note_contextuelle' => 'Pourquoi ce fait porte sur cette mesure.',
        'statut_validation' => 'valide',
        'affiche_publiquement' => true,
        'double_valide_par' => $sens === 'contre' ? User::factory()->create()->id : null,
    ]);
}

it('publie une mesure sourcée qui n\'a aucun argumentaire', function () {
    expect($this->service->raisonsNonPubliable($this->mesure))->toBe([]);

    $this->service->publier($this->mesure, $this->moderateur);

    expect($this->mesure->fresh()->affiche_publiquement)->toBeTrue();
});

it('refuse toujours une mesure sans source officielle', function () {
    $this->mesure->update(['source_officielle_url' => null]);

    expect($this->service->raisonsNonPubliable($this->mesure))
        ->toContain('aucune source officielle');
});

it('refuse un argumentaire à sens unique', function (string $present, string $manquant) {
    argumentPublie($this->mesure, $present);

    expect($this->service->raisonsNonPubliable($this->mesure->fresh()))
        ->toContain("argumentaire déséquilibré : aucun argument « {$manquant} » publié et sourcé");
})->with([
    'seulement contre' => ['contre', 'pour'],
    'seulement pour' => ['pour', 'contre'],
]);

it('publie une mesure dont l\'argumentaire porte les deux sens', function () {
    argumentPublie($this->mesure, 'pour');
    argumentPublie($this->mesure, 'contre');

    expect($this->service->raisonsNonPubliable($this->mesure->fresh()))->toBe([]);
});

it('ne refuse pas l\'export pour une mesure publiée sans argumentaire', function () {
    $this->mesure->update(['affiche_publiquement' => true]);

    // Le point décisif : sans cela, publier une mesure gèlerait objectif2027.fr.
    $resultat = app(IntegriteChecker::class)->analyser('2027');

    expect($resultat['violations'])->toBe([]);
});

it('refuse l\'export si une mesure publiée a un argumentaire à sens unique', function () {
    $this->mesure->update(['affiche_publiquement' => true]);
    argumentPublie($this->mesure, 'contre');

    $types = collect(app(IntegriteChecker::class)->analyser('2027')['violations'])->pluck('type');

    expect($types)->toContain('mesure_argumentaire_desequilibre');
});
