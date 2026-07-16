<?php

use App\Exceptions\ModerationException;
use App\Models\Argument;
use App\Models\ArgumentMesureLien;
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
        lierArgumentPublie($mesure, $sens);
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
    lierArgumentPublie($mesure, 'pour'); // uniquement un « pour », pas de « contre »

    expect(fn () => $service->publier($mesure, moderateur()))
        ->toThrow(ModerationException::class);
    expect($mesure->fresh()->affiche_publiquement)->toBeFalse();
});

it('exige une double validation par un second modérateur pour une liaison « contre »', function () {
    $service = app(ModerationService::class);
    $mod1 = moderateur();
    $mod2 = moderateur();

    // Liaison contre publiable en tout sauf la double validation.
    $arg = Argument::factory()->publie()->create();
    ArgumentSource::factory()->create(['argument_id' => $arg->id, 'fiabilite' => 'haute']);
    $contre = ArgumentMesureLien::factory()->contre()->create([
        'argument_id' => $arg->id,
        'mesure_id' => ProgrammeMesure::factory()->create()->id,
        'note_contextuelle' => 'ce fait joue contre cette mesure',
        'statut_validation' => 'detecte',
    ]);
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
            'note_contextuelle' => "Pourquoi ce fait joue {$sens} cette mesure.",
        ])->assertSessionHasNoErrors();
    }
    $lienPour = $mesure->liens()->where('sens', 'pour')->first();
    $lienContre = $mesure->liens()->where('sens', 'contre')->first();

    // Sources sur les faits sous-jacents.
    foreach ([$lienPour, $lienContre] as $l) {
        $this->actingAs($mod1)->post(route('admin.presidentielle.arguments.sources.store'), [
            'argument_id' => $l->argument_id, 'type_source' => 'insee', 'url' => 'https://insee.fr/etude', 'fiabilite' => 'haute',
        ])->assertSessionHasNoErrors();
    }

    $svc = app(ModerationService::class);
    // Publier les faits (validés une fois, sourcés).
    foreach ([$lienPour, $lienContre] as $l) {
        $arg = $l->argument()->first();
        $svc->valider($arg, $mod1);
        $svc->publier($arg->fresh(), $mod1);
    }
    // Publier les liaisons (le « contre » exige une double validation).
    $svc->valider($lienPour->fresh(), $mod1);
    $svc->publier($lienPour->fresh(), $mod1);
    $svc->valider($lienContre->fresh(), $mod1);
    $svc->doubleValider($lienContre->fresh(), $mod2);
    $svc->publier($lienContre->fresh(), $mod1);

    // la mesure devient publiable via l'endpoint
    $this->actingAs($mod1)->post('/admin/presidentielle/moderation/action', [
        'type' => 'mesure', 'id' => $mesure->id, 'action' => 'publier',
    ])->assertSessionHasNoErrors();
    expect($mesure->fresh()->affiche_publiquement)->toBeTrue();
});

it('refuse de retraiter une proposition déjà rattachée (anti double-clic)', function () {
    $mod = moderateur();
    $prop = propositionIngestion();
    $svc = app(ModerationService::class);

    $svc->creerMesureDepuisProposition($prop->fresh(), $mod);

    expect(fn () => $svc->creerMesureDepuisProposition($prop->fresh(), $mod))
        ->toThrow(ModerationException::class);
    expect(ProgrammeMesure::count())->toBe(1);
});

it('marque et retire une mesure « phare » via l endpoint (comparateur + quiz)', function () {
    $mod = moderateur();
    $mesure = mesureConforme();

    $this->actingAs($mod)->post('/admin/presidentielle/moderation/action', [
        'type' => 'mesure', 'id' => $mesure->id, 'action' => 'mettre_en_avant',
    ])->assertSessionHasNoErrors();
    expect($mesure->fresh()->est_mise_en_avant)->toBeTrue();

    $this->actingAs($mod)->post('/admin/presidentielle/moderation/action', [
        'type' => 'mesure', 'id' => $mesure->id, 'action' => 'retirer_en_avant',
    ])->assertSessionHasNoErrors();
    expect($mesure->fresh()->est_mise_en_avant)->toBeFalse();
});

it('supprime une prise de parole et ses propositions, refuse si rattachée', function () {
    $mod = moderateur();
    $prop = propositionIngestion();
    $docId = $prop->document_id;

    // suppression OK (detecte)
    $this->actingAs($mod)->delete(route('admin.presidentielle.documents.destroy', $docId))
        ->assertSessionHasNoErrors();
    expect(\App\Models\IngestionDocument::find($docId))->toBeNull()
        ->and(\App\Models\IngestionProposition::find($prop->id))->toBeNull();

    // refus si une proposition est rattachée
    $prop2 = propositionIngestion(['statut' => 'rattachee']);
    $this->actingAs($mod)->delete(route('admin.presidentielle.documents.destroy', $prop2->document_id))
        ->assertSessionHasErrors('document');
    expect(\App\Models\IngestionDocument::find($prop2->document_id))->not->toBeNull();
});
