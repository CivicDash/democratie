<?php

use App\Models\Argument;
use App\Models\ArgumentMesureLien;
use App\Models\ArgumentSource;
use App\Models\CandidatPresidentielle;
use App\Models\Controverse;
use App\Models\ProgrammeMesure;
use App\Models\User;

/**
 * L'écran de détail d'une controverse.
 *
 * Il existe parce que la liste proposait une double validation en lot, par identifiants :
 * un modérateur pouvait confirmer dix liaisons « contredit » sans jamais lire ce qu'il
 * confirmait. La seconde validation n'ayant de valeur que comme seconde LECTURE, l'écran
 * doit servir le fait, ses sources et la note contextuelle — c'est ce qu'on vérifie ici,
 * pas seulement qu'il répond 200.
 */
beforeEach(function () {
    $this->moderateur = User::factory()->create();
    $this->moderateur->givePermissionTo('moderer_presidentielle');

    $this->controverse = Controverse::factory()->create(['titre' => 'Annulation de la dette BCE']);

    $this->fait = Argument::factory()->create([
        'controverse_id' => $this->controverse->id,
        'titre' => 'L\'accord de Londres de 1953',
        'statut_validation' => 'valide',
    ]);

    ArgumentSource::factory()->create([
        'argument_id' => $this->fait->id,
        'titre' => 'Agreement on German External Debts',
        'fiabilite' => 'haute',
    ]);

    $this->mesure = ProgrammeMesure::factory()->create([
        'candidat_id' => CandidatPresidentielle::factory(),
        'titre' => 'Annuler la dette détenue par la BCE',
    ]);
});

function lien(array $attrs, Controverse $c, Argument $f, ProgrammeMesure $m): ArgumentMesureLien
{
    return ArgumentMesureLien::factory()->create(array_merge([
        'argument_id' => $f->id,
        'mesure_id' => $m->id,
        'note_contextuelle' => 'Précédent négocié, pas un effacement par une banque centrale.',
    ], $attrs));
}

it('sert le fait, ses sources et la note contextuelle de la liaison', function () {
    lien(['sens' => 'contre', 'statut_validation' => 'valide', 'valide_par' => User::factory()->create()->id],
        $this->controverse, $this->fait, $this->mesure);

    $this->actingAs($this->moderateur)
        ->get(route('admin.presidentielle.controverses.show', $this->controverse->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Presidentielle/ControverseDetail')
            ->where('faits.0.titre', 'L\'accord de Londres de 1953')
            ->where('faits.0.sources.0.fiabilite', 'haute')
            ->where('faits.0.liaisons.0.note_contextuelle',
                'Précédent négocié, pas un effacement par une banque centrale.')
            // Le sens est porté par la liaison : il n'apparaît qu'à l'intérieur du fait,
            // accolé à la mesure visée, et sous forme de libellé.
            ->where('faits.0.liaisons.0.sens_libelle', 'contredit')
            ->where('faits.0.liaisons.0.mesure.titre', 'Annuler la dette détenue par la BCE')
        );
});

it('signale à l\'avance qu\'on ne peut pas doubler sa propre validation', function () {
    lien(['sens' => 'contre', 'statut_validation' => 'valide', 'valide_par' => $this->moderateur->id],
        $this->controverse, $this->fait, $this->mesure);

    $this->actingAs($this->moderateur)
        ->get(route('admin.presidentielle.controverses.show', $this->controverse->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('faits.0.liaisons.0.attend_double_validation', true)
            // Sans ce drapeau, le bouton est cliquable et le service renvoie une erreur :
            // le garde-fou se vivrait comme une panne.
            ->where('faits.0.liaisons.0.double_validation_par_moi_interdite', true)
        );
});

it('laisse un autre modérateur faire la seconde validation', function () {
    lien(['sens' => 'contre', 'statut_validation' => 'valide', 'valide_par' => User::factory()->create()->id],
        $this->controverse, $this->fait, $this->mesure);

    $this->actingAs($this->moderateur)
        ->get(route('admin.presidentielle.controverses.show', $this->controverse->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('faits.0.liaisons.0.attend_double_validation', true)
            ->where('faits.0.liaisons.0.double_validation_par_moi_interdite', false)
        );
});

it('n\'annonce pas de seconde validation pour une liaison « étaye »', function () {
    lien(['sens' => 'pour', 'statut_validation' => 'valide', 'valide_par' => User::factory()->create()->id],
        $this->controverse, $this->fait, $this->mesure);

    $this->actingAs($this->moderateur)
        ->get(route('admin.presidentielle.controverses.show', $this->controverse->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('faits.0.liaisons.0.sens_libelle', 'étaye')
            ->where('faits.0.liaisons.0.attend_double_validation', false)
        );
});
