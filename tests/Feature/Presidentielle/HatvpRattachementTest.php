<?php

use App\Models\CandidatPresidentielle;
use App\Models\HatvpDeclaration;
use App\Models\PersonnePolitique;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function modHatvp(): User
{
    Permission::findOrCreate('moderer_presidentielle', 'web');
    $u = User::factory()->create();
    $u->givePermissionTo('moderer_presidentielle');

    return $u;
}

function declarationHatvp(array $o = []): HatvpDeclaration
{
    return HatvpDeclaration::create(array_merge([
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'date_depot' => now()->subMonths(2),
        'type_declaration' => 'DIA',
        'nom' => 'DURAND', 'prenom' => 'Camille',
    ], $o));
}

it('declarationsHatvp privilégie le rattachement explicite (FK) sur le matching par nom', function () {
    $personne = PersonnePolitique::factory()->create(['nom' => 'DURAND', 'prenom' => 'Camille']);

    // Déclaration homonyme (match par nom) — sans FK.
    $parNom = declarationHatvp(['nom' => 'DURAND', 'prenom' => 'Camille']);
    // Déclaration explicitement rattachée (FK) — nom différent.
    $parFk = declarationHatvp(['nom' => 'AUTRE', 'prenom' => 'Nom', 'personne_politique_id' => $personne->id]);

    $ids = $personne->declarationsHatvp()->pluck('id');

    expect($ids)->toContain($parFk->id)
        ->and($ids)->not->toContain($parNom->id); // la FG court-circuite le matching par nom
});

it('retombe sur le matching par nom quand aucune FK n\'est posée', function () {
    $personne = PersonnePolitique::factory()->create(['nom' => 'MARTIN', 'prenom' => 'Alex']);
    $d = declarationHatvp(['nom' => 'MARTIN', 'prenom' => 'Alex']);

    expect($personne->declarationsHatvp()->pluck('id'))->toContain($d->id);
});

it('recherche des déclarations HATVP par nom (BO)', function () {
    declarationHatvp(['nom' => 'BERTRAND', 'prenom' => 'Xavier']);
    declarationHatvp(['nom' => 'AUTRE', 'prenom' => 'Personne']);

    $res = $this->actingAs(modHatvp())->getJson(route('admin.presidentielle.hatvp.search', ['q' => 'bertrand']));

    $res->assertOk();
    expect(collect($res->json('resultats'))->pluck('nom'))->toContain('BERTRAND')
        ->and(collect($res->json('resultats'))->pluck('nom'))->not->toContain('AUTRE');
});

it('prévisualise une déclaration (résumé intérêts, façon CivicDash)', function () {
    $d = declarationHatvp();

    $res = $this->actingAs(modHatvp())->getJson(route('admin.presidentielle.hatvp.preview', $d->uuid));

    $res->assertOk()->assertJsonPath('summary.declaration_date', $d->date_depot->format('d/m/Y'));
    expect($res->json('summary'))->toHaveKeys(['revenus_par_annee', 'mandats_electifs', 'participations_dirigeantes']);
});

it('rattache puis détache une déclaration à un candidat (pose/retire la FK + statut)', function () {
    $mod = modHatvp();
    $personne = PersonnePolitique::factory()->create();
    $candidat = CandidatPresidentielle::factory()->create(['personne_politique_id' => $personne->id, 'election' => '2027']);
    $d = declarationHatvp();

    $this->actingAs($mod)->post(route('admin.presidentielle.hatvp.rattacher'), [
        'candidat_id' => $candidat->id, 'declaration_uuid' => $d->uuid,
    ])->assertSessionHasNoErrors();

    expect($d->fresh()->personne_politique_id)->toBe($personne->id)
        ->and($candidat->fresh()->hatvp_statut)->toBe('lie');

    $this->actingAs($mod)->post(route('admin.presidentielle.hatvp.detacher'), ['candidat_id' => $candidat->id])
        ->assertSessionHasNoErrors();

    expect($d->fresh()->personne_politique_id)->toBeNull()
        ->and($candidat->fresh()->hatvp_statut)->toBe('a_verifier');
});

it('fixe l\'état d\'honnêteté HATVP (non_soumis / non_disponible)', function () {
    $candidat = CandidatPresidentielle::factory()->create(['election' => '2027']);

    $this->actingAs(modHatvp())->post(route('admin.presidentielle.hatvp.statut'), [
        'candidat_id' => $candidat->id, 'statut' => 'non_soumis',
    ])->assertSessionHasNoErrors();

    expect($candidat->fresh()->hatvp_statut)->toBe('non_soumis');
});

it('refuse l\'accès HATVP sans la permission (403)', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.presidentielle.hatvp'))->assertForbidden();
});
