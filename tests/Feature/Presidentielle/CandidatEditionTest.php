<?php

use App\Models\CandidatPresidentielle;
use App\Models\PresidentielleModerationLog;
use App\Models\User;
use Spatie\Permission\Models\Permission;

/**
 * Édition d'un candidat.
 *
 * La route n'existait pas : on pouvait créer un candidat et le modérer, jamais corriger
 * son statut de candidature. Le 15/09/2026, Clémentine Autain s'était retirée depuis deux
 * mois et le site l'affichait toujours « déclarée », sans autre recours que la base.
 */
function moderateurCandidat(): User
{
    Permission::findOrCreate('moderer_presidentielle', 'web');
    $u = User::factory()->create();
    $u->givePermissionTo('moderer_presidentielle');

    return $u;
}

it('corrige le statut de candidature d\'un candidat retiré', function () {
    $mod = moderateurCandidat();
    $candidat = CandidatPresidentielle::factory()->create([
        'election' => '2027', 'statut_candidature' => 'declare',
    ]);

    $this->actingAs($mod)->post(route('admin.presidentielle.candidats.update', $candidat->id), [
        'statut_candidature' => 'retire',
        'parti_soutien' => 'L\'Après',
    ])->assertSessionHasNoErrors();

    expect($candidat->fresh()->statut_candidature)->toBe('retire')
        ->and($candidat->fresh()->parti_soutien)->toBe('L\'Après');
});

it('journalise le changement de statut', function () {
    $mod = moderateurCandidat();
    $candidat = CandidatPresidentielle::factory()->create([
        'election' => '2027', 'statut_candidature' => 'declare',
    ]);

    $this->actingAs($mod)->post(route('admin.presidentielle.candidats.update', $candidat->id), [
        'statut_candidature' => 'retire',
    ]);

    // Qui a retiré un candidat de la course, et quand : cela doit pouvoir se retracer.
    $log = PresidentielleModerationLog::where('entite_id', $candidat->id)
        ->where('action', 'changement_statut_candidature')->first();

    expect($log)->not->toBeNull()
        ->and($log->ancien_statut)->toBe('declare')
        ->and($log->nouveau_statut)->toBe('retire')
        ->and($log->moderator_id)->toBe($mod->id);
});

it('ne journalise rien quand le statut ne change pas', function () {
    $mod = moderateurCandidat();
    $candidat = CandidatPresidentielle::factory()->create([
        'election' => '2027', 'statut_candidature' => 'declare',
    ]);

    $this->actingAs($mod)->post(route('admin.presidentielle.candidats.update', $candidat->id), [
        'statut_candidature' => 'declare', 'parti_soutien' => 'Nouveau parti',
    ]);

    expect(PresidentielleModerationLog::where('entite_id', $candidat->id)
        ->where('action', 'changement_statut_candidature')->count())->toBe(0);
});

it('refuse un statut hors référentiel', function () {
    $mod = moderateurCandidat();
    $candidat = CandidatPresidentielle::factory()->create(['election' => '2027']);

    $this->actingAs($mod)->post(route('admin.presidentielle.candidats.update', $candidat->id), [
        'statut_candidature' => 'vainqueur',
    ])->assertSessionHasErrors('statut_candidature');
});

it('refuse l\'accès sans la permission', function () {
    $candidat = CandidatPresidentielle::factory()->create(['election' => '2027']);

    $this->actingAs(User::factory()->create())
        ->post(route('admin.presidentielle.candidats.update', $candidat->id), [
            'statut_candidature' => 'retire',
        ])->assertForbidden();
});
