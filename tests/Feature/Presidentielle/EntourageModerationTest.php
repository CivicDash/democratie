<?php

use App\Exceptions\ModerationException;
use App\Models\CandidatPresidentielle;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Models\PresidentielleModerationLog;
use App\Models\ProgrammeMesure;
use App\Services\Presidentielle\ModerationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Circuit de modération selon qui parle.
 *
 *  - le candidat et son équipe de campagne : « Valider → mesure » ou « Valider sans mesure » ;
 *  - la parole d'un parti (locuteur `parti`, ou document de parti ou de groupe) : jamais une
 *    mesure, seulement « Autour du candidat », validée puis publiée sous conditions.
 */
function candidatModeration(): CandidatPresidentielle
{
    return CandidatPresidentielle::factory()->publie()->create();
}

it('refuse de faire une mesure de la parole d\'un parti — service et écran', function () {
    $mod = moderateurPresidentielle();
    $p = propositionPour(candidatModeration(), parole('parti'));

    expect(fn () => app(ModerationService::class)->creerMesureDepuisProposition($p, $mod))
        ->toThrow(ModerationException::class, 'jamais une mesure');

    $this->actingAs($mod)->post(route('admin.presidentielle.propositions.action'), ['id' => $p->id, 'action' => 'valider'])
        ->assertSessionHasErrors('action');

    expect(ProgrammeMesure::count())->toBe(0)
        ->and($p->fresh()->statut)->toBe('detecte');
});

it('refuse de faire une mesure d\'une proposition issue d\'un document de groupe, même sans locuteur', function () {
    $mod = moderateurPresidentielle();
    $doc = IngestionDocument::factory()->porte('groupe_parlementaire')->create();
    $p = propositionPour(candidatModeration(), [], $doc);

    $this->actingAs($mod)->post(route('admin.presidentielle.propositions.action'), ['id' => $p->id, 'action' => 'valider'])
        ->assertSessionHasErrors('action');
    $this->actingAs($mod)->post(route('admin.presidentielle.propositions.action'), ['id' => $p->id, 'action' => 'valider_sans_mesure'])
        ->assertSessionHasErrors('action');

    expect(ProgrammeMesure::count())->toBe(0)->and($p->fresh()->statut)->toBe('detecte');
});

it('fait une mesure d\'une proposition de l\'équipe de campagne, en gardant son locuteur', function () {
    $mod = moderateurPresidentielle();
    $p = propositionPour(candidatModeration(), parole('equipe_campagne', 'Éric Coquerel', 'député'));

    $this->actingAs($mod)->post(route('admin.presidentielle.propositions.action'), ['id' => $p->id, 'action' => 'valider'])
        ->assertSessionHasNoErrors();

    $p->refresh();
    expect($p->statut)->toBe('rattachee')
        ->and($p->mesure_id)->not->toBeNull()
        ->and($p->locuteur)->toBe('Éric Coquerel')
        ->and(ProgrammeMesure::find($p->mesure_id)->propositionsSource()->first()->qualite_locuteur)->toBe('equipe_campagne');
});

it('refuse de valider un locuteur sans rôle, côté candidat', function () {
    $mod = moderateurPresidentielle();
    $p = propositionPour(candidatModeration(), parole('equipe_campagne', 'Éric Coquerel', null));

    foreach (['valider', 'valider_sans_mesure'] as $action) {
        $this->actingAs($mod)->post(route('admin.presidentielle.propositions.action'), ['id' => $p->id, 'action' => $action])
            ->assertSessionHasErrors('action');
    }
    expect($p->fresh()->statut)->toBe('detecte');
});

it('« Valider sans mesure » valide une proposition du candidat, sans mesure, et le journalise', function () {
    $mod = moderateurPresidentielle();
    $p = propositionPour(candidatModeration(), ['type' => 'declaration']);

    $this->actingAs($mod)->post(route('admin.presidentielle.propositions.action'), ['id' => $p->id, 'action' => 'valider_sans_mesure'])
        ->assertSessionHasNoErrors();

    expect($p->fresh()->statut)->toBe('validee')
        ->and($p->fresh()->mesure_id)->toBeNull()
        ->and(ProgrammeMesure::count())->toBe(0)
        ->and(PresidentielleModerationLog::where('entite_id', $p->id)->where('action', 'validation_sans_mesure')->exists())->toBeTrue();

    // Une seconde fois : déjà traitée.
    expect(fn () => app(ModerationService::class)->validerSansMesure($p->fresh(), $mod))->toThrow(ModerationException::class);
});

it('publie la parole d\'un parti dans « Autour du candidat » seulement si elle est attribuée, vérifiée et endossée', function () {
    $mod = moderateurPresidentielle();
    $service = app(ModerationService::class);
    $candidat = candidatModeration();

    // Sans rôle : la validation passe (c'est un tri), la publication non.
    $sansRole = propositionPour($candidat, parole('parti', 'Paul Parti', null));
    $this->actingAs($mod)->post(route('admin.presidentielle.propositions.action'), ['id' => $sansRole->id, 'action' => 'valider_entourage'])
        ->assertSessionHasNoErrors();
    expect($sansRole->fresh()->statut)->toBe('validee');
    $this->actingAs($mod)->post(route('admin.presidentielle.propositions.action'), ['id' => $sansRole->id, 'action' => 'publier_entourage'])
        ->assertSessionHasErrors('action');

    // Citation non vérifiée.
    $nonVerifiee = propositionPour($candidat, parole('parti') + ['verbatim_verifie' => false]);
    $service->validerEntourage($nonVerifiee, $mod);
    expect(fn () => $service->publierEntourage($nonVerifiee->fresh(), $mod))->toThrow(ModerationException::class, 'vérifiée');

    // Document de groupe sans endossement complet.
    $docIncomplet = IngestionDocument::factory()->create(['porteur_type' => 'groupe_parlementaire', 'porteur_libelle' => 'Groupe X']);
    $sansEndossement = propositionPour($candidat, [], $docIncomplet);
    $service->validerEntourage($sansEndossement, $mod);
    expect(fn () => $service->publierEntourage($sansEndossement->fresh(), $mod))->toThrow(ModerationException::class, 'endossement');

    // Conforme.
    $conforme = propositionPour($candidat, parole('parti'));
    $this->actingAs($mod)->post(route('admin.presidentielle.propositions.action'), ['id' => $conforme->id, 'action' => 'valider_entourage'])->assertSessionHasNoErrors();
    $this->actingAs($mod)->post(route('admin.presidentielle.propositions.action'), ['id' => $conforme->id, 'action' => 'publier_entourage'])->assertSessionHasNoErrors();
    expect($conforme->fresh()->affiche_publiquement)->toBeTrue()
        ->and(PresidentielleModerationLog::where('entite_id', $conforme->id)->pluck('action')->all())
        ->toContain('validation_entourage', 'publication_entourage');

    $this->actingAs($mod)->post(route('admin.presidentielle.propositions.action'), ['id' => $conforme->id, 'action' => 'depublier_entourage'])->assertSessionHasNoErrors();
    expect($conforme->fresh()->affiche_publiquement)->toBeFalse();
});

it('refuse « Valider pour Autour du candidat » à une proposition du candidat ou de son équipe', function () {
    $p = propositionPour(candidatModeration(), parole('equipe_campagne', 'Éric Coquerel', 'député'));

    expect(fn () => app(ModerationService::class)->validerEntourage($p, moderateurPresidentielle()))->toThrow(ModerationException::class);
});

it('requalifie un locuteur : refus vers « parti » sur une proposition rattachée, retour en file sinon', function () {
    $mod = moderateurPresidentielle();
    $candidat = candidatModeration();

    $rattachee = propositionPour($candidat);
    app(ModerationService::class)->creerMesureDepuisProposition($rattachee, $mod);
    $this->actingAs($mod)->post(route('admin.presidentielle.propositions.locuteur', $rattachee->id), [
        'locuteur' => 'Paul Parti', 'role_locuteur' => 'président du parti', 'qualite_locuteur' => 'parti',
    ])->assertSessionHasErrors('locuteur');
    expect($rattachee->fresh()->locuteur)->toBeNull();

    // Vers l'équipe de campagne : admis, la mesure reste.
    $this->actingAs($mod)->post(route('admin.presidentielle.propositions.locuteur', $rattachee->id), [
        'locuteur' => 'Éric Coquerel', 'role_locuteur' => 'député', 'qualite_locuteur' => 'equipe_campagne',
    ])->assertSessionHasNoErrors();
    expect($rattachee->fresh()->statut)->toBe('rattachee')->and($rattachee->fresh()->locuteur)->toBe('Éric Coquerel');

    // Une proposition validée revient en file, dépubliée.
    $validee = propositionPour($candidat, parole('parti') + ['statut' => 'validee', 'affiche_publiquement' => true]);
    $this->actingAs($mod)->post(route('admin.presidentielle.propositions.locuteur', $validee->id), [
        'locuteur' => 'Pierre Autre', 'role_locuteur' => 'sénateur', 'qualite_locuteur' => 'parti',
    ])->assertSessionHasNoErrors();
    expect($validee->fresh()->statut)->toBe('detecte')
        ->and($validee->fresh()->affiche_publiquement)->toBeFalse()
        ->and(PresidentielleModerationLog::where('entite_id', $validee->id)->where('action', 'requalification_locuteur')->exists())->toBeTrue();

    // Un rôle est exigé avec un nom.
    $this->actingAs($mod)->post(route('admin.presidentielle.propositions.locuteur', $validee->id), [
        'locuteur' => 'Pierre Autre', 'qualite_locuteur' => 'parti',
    ])->assertSessionHasErrors('role_locuteur');
});

it('refuse de porter vers un groupe un document dont des propositions sont rattachées, et renvoie en file les validées quand il change de côté', function () {
    $mod = moderateurPresidentielle();
    $candidat = candidatModeration();
    $doc = IngestionDocument::factory()->create();
    $rattachee = propositionPour($candidat, [], $doc);
    app(ModerationService::class)->creerMesureDepuisProposition($rattachee, $mod);

    $porteurGroupe = ['porteur_type' => 'groupe_parlementaire', 'porteur_libelle' => 'Groupe Exemple'];
    $this->actingAs($mod)->post(route('admin.presidentielle.documents.porteur', $doc->id), $porteurGroupe)
        ->assertSessionHasErrors('porteur_type');
    expect($doc->fresh()->porteur_type)->toBe('candidat');

    $doc2 = IngestionDocument::factory()->create();
    $validee = propositionPour($candidat, ['statut' => 'validee'], $doc2);
    $this->actingAs($mod)->post(route('admin.presidentielle.documents.porteur', $doc2->id), $porteurGroupe)
        ->assertSessionHasNoErrors();
    expect($doc2->fresh()->porteur_type)->toBe('groupe_parlementaire')
        ->and($validee->fresh()->statut)->toBe('detecte');
});

it('refuse de supprimer une prise de parole dont une proposition est publiée « Autour du candidat »', function () {
    $mod = moderateurPresidentielle();
    $p = propositionPour(candidatModeration(), parole('parti') + ['statut' => 'validee', 'affiche_publiquement' => true]);

    $this->actingAs($mod)->delete(route('admin.presidentielle.documents.destroy', $p->document_id))
        ->assertSessionHasErrors('document');
    expect(IngestionDocument::find($p->document_id))->not->toBeNull();
});

it('la base refuse une mesure sur la parole d\'un parti (contrainte CHECK)', function () {
    $p = propositionPour(candidatModeration(), parole('parti'));
    $mesure = ProgrammeMesure::factory()->create(['candidat_id' => $p->candidat_id]);

    expect(fn () => DB::table('ingestion_propositions')->where('id', $p->id)->update(['mesure_id' => $mesure->id]))
        ->toThrow(QueryException::class);
});

it('refuse de publier une mesure issue d\'un document de groupe, et le contrôle d\'intégrité la signale', function () {
    $mod = moderateurPresidentielle();
    [$candidat, , $mesure] = candidatPubliePublic();
    $doc = IngestionDocument::factory()->create();
    $p = propositionPour($candidat, ['statut' => 'rattachee', 'mesure_id' => $mesure->id], $doc);

    // Le document passe au groupe par une écriture qui contourne l'écran (donnée ancienne).
    DB::table('ingestion_documents')->where('id', $doc->id)->update(['porteur_type' => 'groupe_parlementaire', 'porteur_libelle' => 'Groupe X']);

    expect(app(ModerationService::class)->raisonsNonPubliable($mesure->fresh()))->not->toBeEmpty();
    $types = array_column(app(\App\Services\Presidentielle\IntegriteChecker::class)->analyser()['violations'], 'type');
    expect($types)->toContain('mesure_issue_entourage');
});
