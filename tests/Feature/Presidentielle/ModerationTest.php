<?php

use App\Exceptions\ModerationException;
use App\Models\Argument;
use App\Models\ArgumentSource;
use App\Models\CandidatPresidentielle;
use App\Models\PresidentielleModerationLog;
use App\Models\ProgrammeMesure;
use App\Models\ProgrammeTheme;
use App\Models\User;
use App\Services\Presidentielle\ModerationService;
use Spatie\Permission\Models\Permission;

function moderateur(): User
{
    // Le seed global ne persiste pas dans la transaction de test : on garantit la permission ici.
    Permission::findOrCreate('moderer_presidentielle', 'web');
    $u = User::factory()->create();
    $u->givePermissionTo('moderer_presidentielle');

    return $u;
}

/** Mesure « valide » (pas encore publiée) avec pour+contre publiés et sourcés. */
function mesureConforme(): ProgrammeMesure
{
    $candidat = CandidatPresidentielle::factory()->publie()->create();
    $theme = ProgrammeTheme::factory()->create();
    $mesure = ProgrammeMesure::factory()->create([
        'candidat_id' => $candidat->id, 'theme_id' => $theme->id,
        'statut_validation' => 'valide', 'affiche_publiquement' => false,
        'source_officielle_url' => 'https://exemple.fr/#m',
    ]);
    foreach (['pour', 'contre'] as $sens) {
        $arg = Argument::factory()->publie()->create(['mesure_id' => $mesure->id, 'sens' => $sens]);
        ArgumentSource::factory()->create(['argument_id' => $arg->id, 'fiabilite' => 'haute']);
    }

    return $mesure;
}

it('un modérateur fait passer une mesure de detecte à publié et journalise', function () {
    $service = app(ModerationService::class);
    $mod = moderateur();

    $mesure = mesureConforme();
    $mesure->update(['statut_validation' => 'detecte', 'affiche_publiquement' => false]);

    $service->valider($mesure, $mod);
    expect($mesure->fresh()->statut_validation)->toBe('valide');

    $service->publier($mesure, $mod);
    expect($mesure->fresh()->affiche_publiquement)->toBeTrue();

    expect(PresidentielleModerationLog::where('entite_id', $mesure->id)
        ->where('entite_type', $mesure->getMorphClass())
        ->whereIn('action', ['validation', 'publication'])->count())->toBe(2);
});

it('refuse de publier une mesure sans contre-argument', function () {
    $service = app(ModerationService::class);
    $candidat = CandidatPresidentielle::factory()->publie()->create();
    $mesure = ProgrammeMesure::factory()->create([
        'candidat_id' => $candidat->id, 'theme_id' => ProgrammeTheme::factory()->create()->id,
        'statut_validation' => 'valide', 'source_officielle_url' => 'https://x.fr/#m',
    ]);
    $arg = Argument::factory()->publie()->create(['mesure_id' => $mesure->id, 'sens' => 'pour']);
    ArgumentSource::factory()->create(['argument_id' => $arg->id, 'fiabilite' => 'haute']);

    expect(fn () => $service->publier($mesure, moderateur()))
        ->toThrow(ModerationException::class);
    expect($mesure->fresh()->affiche_publiquement)->toBeFalse();
});

it('exige une double validation par un second modérateur pour un argument « contre »', function () {
    $service = app(ModerationService::class);
    $mod1 = moderateur();
    $mod2 = moderateur();

    $contre = Argument::factory()->create(['sens' => 'contre', 'statut_validation' => 'detecte']);
    $service->valider($contre, $mod1);

    // le même modérateur ne peut pas doubler sa propre validation
    expect(fn () => $service->doubleValider($contre->fresh(), $mod1))->toThrow(ModerationException::class);

    // publier sans double validation est refusé
    expect(fn () => $service->publier($contre->fresh(), $mod1))->toThrow(ModerationException::class);

    $service->doubleValider($contre->fresh(), $mod2);
    expect($contre->fresh()->double_valide_par)->toBe($mod2->id);
    expect($contre->fresh()->estValide())->toBeTrue();
});

it('expose la file de modération au modérateur et refuse les autres (403)', function () {
    // en-tête X-Inertia : réponse JSON (pas de rendu blade/Vite en environnement de test)
    $this->actingAs(moderateur())
        ->withHeader('X-Inertia', 'true')
        ->get('/admin/presidentielle/moderation')
        ->assertOk();

    $this->actingAs(User::factory()->create())          // sans permission
        ->get('/admin/presidentielle/moderation')
        ->assertForbidden();
});

it('applique une action via l’endpoint HTTP et bloque une publication non conforme', function () {
    $mod = moderateur();
    $mesure = mesureConforme();

    // publication OK via HTTP
    $this->actingAs($mod)->post('/admin/presidentielle/moderation/action', [
        'type' => 'mesure', 'id' => $mesure->id, 'action' => 'publier',
    ])->assertSessionHasNoErrors();
    expect($mesure->fresh()->affiche_publiquement)->toBeTrue();

    // mesure sans contre -> l'endpoint renvoie une erreur de validation
    $candidat = CandidatPresidentielle::factory()->publie()->create();
    $incomplete = ProgrammeMesure::factory()->create([
        'candidat_id' => $candidat->id, 'theme_id' => ProgrammeTheme::factory()->create()->id,
        'statut_validation' => 'valide', 'source_officielle_url' => 'https://x.fr/#m',
    ]);
    $this->actingAs($mod)->post('/admin/presidentielle/moderation/action', [
        'type' => 'mesure', 'id' => $incomplete->id, 'action' => 'publier',
    ])->assertSessionHasErrors('action');
    expect($incomplete->fresh()->affiche_publiquement)->toBeFalse();
});
