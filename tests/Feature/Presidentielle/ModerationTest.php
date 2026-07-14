<?php

use App\Exceptions\ModerationException;
use App\Models\Argument;
use App\Models\ArgumentSource;
use App\Models\CandidatPresidentielle;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Models\PresidentielleModerationLog;
use App\Models\ProgrammeMesure;
use App\Models\ProgrammeTheme;
use App\Models\User;
use App\Services\Presidentielle\ModerationService;
use Spatie\Permission\Models\Permission;

/** Crée une proposition d'ingestion (avec candidat/thème résolus par défaut). */
function propositionIngestion(array $overrides = []): IngestionProposition
{
    $doc = IngestionDocument::create(['type' => 'video', 'titre' => 'Meeting test', 'statut' => 'extrait']);
    $candidat = CandidatPresidentielle::factory()->create();
    $theme = ProgrammeTheme::factory()->create();

    return IngestionProposition::create(array_merge([
        'document_id' => $doc->id,
        'candidat_id' => $candidat->id,
        'theme_id' => $theme->id,
        'type' => 'mesure',
        'resume_propose' => 'Instaurer la mesure X dès le début du mandat.',
        'citation_verbatim' => 'je ferai la mesure X',
        'statut' => 'detecte',
        'confiance' => 0.9,
    ], $overrides));
}

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

it('valide une proposition en créant une mesure rattachée en statut detecte', function () {
    $mod = moderateur();
    $prop = propositionIngestion();

    $mesure = app(ModerationService::class)->creerMesureDepuisProposition($prop->fresh(), $mod);

    expect($mesure->statut_validation)->toBe('detecte')
        ->and($mesure->affiche_publiquement)->toBeFalse()
        ->and($mesure->candidat_id)->toBe($prop->candidat_id)
        ->and($mesure->detection_raw_data['citation_verbatim'])->toBe('je ferai la mesure X');

    expect($prop->fresh()->statut)->toBe('rattachee')
        ->and($prop->fresh()->mesure_id)->toBe($mesure->id);
});

it('refuse le rattachement si candidat ou thème non résolu', function () {
    $prop = propositionIngestion(['candidat_id' => null]);

    expect(fn () => app(ModerationService::class)->creerMesureDepuisProposition($prop, moderateur()))
        ->toThrow(ModerationException::class);
});

it('rejette une proposition via l’endpoint HTTP', function () {
    $mod = moderateur();
    $prop = propositionIngestion();

    $this->actingAs($mod)->post(route('admin.presidentielle.propositions.action'), [
        'id' => $prop->id, 'action' => 'rejeter', 'commentaire' => 'hors périmètre',
    ])->assertSessionHasNoErrors();

    expect($prop->fresh()->statut)->toBe('rejetee');
});

it('ajoute manuellement un candidat en statut detecte via le BO', function () {
    $mod = moderateur();

    $this->actingAs($mod)->post(route('admin.presidentielle.candidats.store'), [
        'prenom' => 'Test', 'nom' => 'Candidat', 'parti' => 'Parti Test',
        'nuance' => 'DIV', 'statut_candidature' => 'declare',
        'date_declaration' => '2026-07-14', 'source_url' => 'https://exemple.fr/declaration',
    ])->assertSessionHasNoErrors();

    $c = \App\Models\CandidatPresidentielle::whereHas('personnePolitique', fn ($q) => $q->where('slug', 'test-candidat'))->first();
    expect($c)->not->toBeNull()
        ->and($c->statut_validation)->toBe('detecte')
        ->and($c->affiche_publiquement)->toBeFalse();

    // doublon refusé
    $this->actingAs($mod)->post(route('admin.presidentielle.candidats.store'), [
        'prenom' => 'Test', 'nom' => 'Candidat', 'statut_candidature' => 'declare',
    ])->assertSessionHasErrors('nom');
});

it('charge un JSON de propositions depuis le BO (upload)', function () {
    $mod = moderateur();
    (new \Database\Seeders\ProgrammeThemesSeeder())->run();
    $candidat = CandidatPresidentielle::factory()->create();
    $slug = $candidat->personnePolitique->slug;

    $json = \Illuminate\Http\UploadedFile::fake()->createWithContent('props.json', json_encode([
        'contrat_version' => '1.0',
        'document_source' => ['type' => 'video', 'titre' => 'Meeting test upload'],
        'propositions' => [[
            'candidat_slug' => $slug, 'theme_slug' => 'education', 'type' => 'mesure',
            'resume_propose' => 'Mesure test', 'citation_verbatim' => 'je propose la mesure test',
        ]],
    ]));

    $this->actingAs($mod)->post(route('admin.presidentielle.propositions.import'), [
        'fichier' => $json,
    ])->assertSessionHasNoErrors();

    expect(IngestionProposition::where('candidat_slug', $slug)->where('statut', 'detecte')->count())->toBe(1);
});

it('gère les arguments sourcés depuis le BO jusqu à la publication de la mesure', function () {
    $mod1 = moderateur();
    $mod2 = moderateur();
    $candidat = CandidatPresidentielle::factory()->publie()->create();
    $mesure = ProgrammeMesure::factory()->create([
        'candidat_id' => $candidat->id, 'theme_id' => ProgrammeTheme::factory()->create()->id,
        'statut_validation' => 'valide', 'source_officielle_url' => 'https://exemple.fr/#m',
    ]);

    foreach (['pour', 'contre'] as $sens) {
        $this->actingAs($mod1)->post(route('admin.presidentielle.arguments.store'), [
            'mesure_id' => $mesure->id, 'sens' => $sens, 'titre' => "Argument {$sens}",
            'contenu' => 'Contenu factuel appuyé sur une étude.', 'type_argument' => 'chiffrage',
        ])->assertSessionHasNoErrors();
    }
    $pour = $mesure->arguments()->where('sens', 'pour')->first();
    $contre = $mesure->arguments()->where('sens', 'contre')->first();

    foreach ([$pour, $contre] as $a) {
        $this->actingAs($mod1)->post(route('admin.presidentielle.arguments.sources.store'), [
            'argument_id' => $a->id, 'type_source' => 'insee', 'url' => 'https://insee.fr/etude', 'fiabilite' => 'haute',
        ])->assertSessionHasNoErrors();
    }

    $svc = app(ModerationService::class);
    $svc->valider($pour, $mod1);
    $svc->publier($pour->fresh(), $mod1);
    $svc->valider($contre, $mod1);
    $svc->doubleValider($contre->fresh(), $mod2);
    $svc->publier($contre->fresh(), $mod1);

    // la mesure devient publiable via l'endpoint
    $this->actingAs($mod1)->post('/admin/presidentielle/moderation/action', [
        'type' => 'mesure', 'id' => $mesure->id, 'action' => 'publier',
    ])->assertSessionHasNoErrors();
    expect($mesure->fresh()->affiche_publiquement)->toBeTrue();
});
