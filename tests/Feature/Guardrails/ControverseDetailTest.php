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
 * Il existe parce que la liste proposait une validation en lot, par identifiants : un
 * modérateur pouvait confirmer dix liaisons « contredit » sans jamais lire ce qu'il
 * confirmait. Une validation n'ayant de valeur que comme LECTURE, l'écran doit servir le
 * fait, ses sources et la note contextuelle — c'est ce qu'on vérifie ici, pas seulement
 * qu'il répond 200.
 */
beforeEach(function () {
    // Sans manifeste Vite, app.blade.php lève avant d'atteindre le composant : le test
    // échouerait pour la mauvaise raison. Un dépôt fraîchement cloné affichait cinq
    // échecs fantômes là où il n'y avait qu'un `npm run build` manquant.
    if (! file_exists(public_path('build/manifest.json'))) {
        test()->markTestSkipped('Manifeste Vite absent : lancer `npm run build` avant ce test.');
    }

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

it('n\'annonce plus de seconde validation : une liaison « contredit » validée est publiable', function () {
    $this->fait->update(['affiche_publiquement' => true]);
    lien(['sens' => 'contre', 'statut_validation' => 'valide', 'valide_par' => $this->moderateur->id],
        $this->controverse, $this->fait, $this->mesure);

    $this->actingAs($this->moderateur)
        ->get(route('admin.presidentielle.controverses.show', $this->controverse->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing('faits.0.liaisons.0.attend_double_validation')
            ->where('faits.0.liaisons.0.raisons_non_publiable', [])
        );
});

it('garde la trace d\'une seconde validation faite avant l\'abandon de la règle', function () {
    $ancien = User::factory()->create(['name' => 'Second relecteur']);
    lien(['sens' => 'contre', 'statut_validation' => 'valide', 'valide_par' => $this->moderateur->id,
        'double_valide_par' => $ancien->id, 'double_valide_at' => now()],
        $this->controverse, $this->fait, $this->mesure);

    $this->actingAs($this->moderateur)
        ->get(route('admin.presidentielle.controverses.show', $this->controverse->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('faits.0.liaisons.0.double_valide_par_nom', 'Second relecteur')
        );
});
