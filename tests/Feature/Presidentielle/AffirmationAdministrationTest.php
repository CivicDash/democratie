<?php

use App\Models\Affirmation;
use App\Models\EurostatIndicateur;
use App\Models\ProgrammeTheme;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;

/**
 * Le parcours complet d'une fiche « Ce qu'on entend », par HTTP : import depuis l'écran,
 * vérification phrase par phrase, refus tant qu'il reste une phrase à vérifier, puis
 * publication. Les règles elles-mêmes sont couvertes par Guardrails/AffirmationPublicationTest.
 */
function moderateurAffirmations(): User
{
    Permission::findOrCreate('moderer_presidentielle', 'web');
    $u = User::factory()->create();
    $u->givePermissionTo('moderer_presidentielle');

    return $u;
}

function fichierAffirmations(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('fiches.json', json_encode([
        'contrat' => 'presidentielle.affirmations.v2',
        'reperes' => [[
            'slug' => 'evolution-des-salaires',
            'question' => 'Comment les salaires ont-ils évolué, une fois l\'inflation déduite ?',
            'theme' => 'pouvoir-achat',
            'sources' => [[
                'cle' => 'insee-ip2079', 'producteur' => 'INSEE', 'titre' => 'Les salaires dans le secteur privé en 2024',
                'url' => 'https://www.insee.fr/fr/statistiques/8657156', 'categorie' => 'producteur_public',
            ]],
            'constats' => [
                ['section' => 'chiffres', 'texte' => 'En euros constants : -1,3 % en 2022, -1,0 % en 2023, +0,8 % en 2024.',
                    'verification' => 'verifie', 'sources' => ['insee-ip2079']],
                ['section' => 'limites', 'texte' => 'Le salaire net n\'est pas le coût du travail.', 'verification' => 'a_verifier'],
            ],
        ]],
    ]));
}

beforeEach(function () {
    ProgrammeTheme::factory()->create(['slug' => 'pouvoir-achat', 'actif' => true]);
});

it('mène une fiche de l\'import à la publication', function () {
    $mod = moderateurAffirmations();

    // 1. Import depuis l'écran : en « détecté », non publiée.
    $this->actingAs($mod)->post(route('admin.presidentielle.affirmations.import'), ['fichier' => fichierAffirmations()])
        ->assertSessionHasNoErrors();
    $fiche = Affirmation::sole();
    expect($fiche->statut_validation)->toBe('detecte');

    // 2. L'écran de détail sert les phrases et ce qui empêche la publication.
    $props = $this->actingAs($mod)->withHeaders(enTeteInertia())
        ->get(route('admin.presidentielle.affirmations.show', $fiche))->assertOk()->json('props');
    expect($props['raisons'])->toContain('1 phrase(s) « Ce que les chiffres ne disent pas » à vérifier : une fiche ne paraît jamais sans toutes ses réserves')
        ->and($props['constats'])->toHaveCount(2)
        ->and($props['fiche']['question'])->toBe('Comment les salaires ont-ils évolué, une fois l\'inflation déduite ?')
        ->and($props)->not->toHaveKey('verdicts')
        ->and($props['fiche'])->not->toHaveKeys(['coloration_percue', 'part_de_valeur']);

    // 3. Validée mais pas publiable : il reste une réserve à vérifier.
    $this->actingAs($mod)->post(route('admin.presidentielle.moderation.action'), ['type' => 'affirmation', 'id' => $fiche->id, 'action' => 'valider'])
        ->assertSessionHasNoErrors();
    $this->actingAs($mod)->post(route('admin.presidentielle.moderation.action'), ['type' => 'affirmation', 'id' => $fiche->id, 'action' => 'publier'])
        ->assertSessionHasErrors('action');

    // 4. La limite est vérifiée, par un modérateur identifié.
    $limite = $fiche->constats()->where('section', 'limites')->sole();
    $this->actingAs($mod)->post(route('admin.presidentielle.affirmations.constats.verification', $limite), ['verification' => 'verifie'])
        ->assertSessionHasNoErrors();
    expect($limite->fresh()->verifie_par)->toBe($mod->id);

    // 5. Publication.
    $this->actingAs($mod)->post(route('admin.presidentielle.moderation.action'), ['type' => 'affirmation', 'id' => $fiche->id, 'action' => 'publier'])
        ->assertSessionHasNoErrors();
    expect($fiche->fresh()->affiche_publiquement)->toBeTrue();
});

it('montre la couverture des thèmes, publiés et en préparation', function () {
    $publie = affirmationPubliable(['affiche_publiquement' => true]);
    affirmationPubliable()->update(['theme_id' => $publie->theme_id]);

    $couverture = collect($this->actingAs(moderateurAffirmations())->withHeaders(enTeteInertia())
        ->get(route('admin.presidentielle.affirmations'))->assertOk()->json('props.couverture'))->keyBy('theme');

    expect($couverture[$publie->theme->nom]['publies'])->toBe(1)
        ->and($couverture[$publie->theme->nom]['en_preparation'])->toBe(1);
});

it('enregistre la question du repère', function () {
    $f = affirmationPubliable(['question' => null]);

    $this->actingAs(moderateurAffirmations())->post(route('admin.presidentielle.affirmations.update', $f), [
        'question' => 'Combien de ceci en 2025 ?', 'resume' => 'Résumé.', 'theme_id' => $f->theme_id,
    ])->assertSessionHasNoErrors();

    expect($f->fresh()->question)->toBe('Combien de ceci en 2025 ?');
});

it('exige d\'avoir relu les phrases avant de valider une révision citée par une fiche', function () {
    $mod = moderateurAffirmations();
    $f = affirmationPubliable();
    $f->graphiques()->create(['type' => 'courbes', 'titre' => 'Prix', 'indicateurs' => ['inflation_ipch'], 'constat_id' => $f->constats()->first()->id]);
    $ind = EurostatIndicateur::create([
        'code' => 'inflation_ipch', 'titre' => 'Inflation', 'unite' => '%', 'sources' => [],
        'series_publiees' => ['FR' => [['annee' => 2025, 'valeur' => 0.9, 'statut' => '']]],
        'series_detectees' => ['FR' => [['annee' => 2025, 'valeur' => 1.0, 'statut' => '']]],
    ]);

    $this->actingAs($mod)->post(route('admin.presidentielle.eurostat.valider', $ind))->assertSessionHasErrors('textes_relus');
    expect($ind->fresh()->series_publiees['FR'][0]['valeur'])->toBe(0.9);

    $this->actingAs($mod)->post(route('admin.presidentielle.eurostat.valider', $ind), ['textes_relus' => 1])->assertSessionHasNoErrors();
    // json_encode écrit 1.0 comme 1 : on compare la valeur, pas le type.
    expect($ind->fresh()->series_publiees['FR'][0]['valeur'])->toEqual(1.0)
        ->and($ind->fresh()->etat())->toBe('a_jour');
});

it('supprime une fiche non publiée, jamais une fiche publiée', function () {
    $mod = moderateurAffirmations();
    $publiee = affirmationPubliable(['affiche_publiquement' => true]);
    $brouillon = affirmationPubliable();

    $this->actingAs($mod)->post(route('admin.presidentielle.moderation.action'), ['type' => 'affirmation', 'id' => $publiee->id, 'action' => 'supprimer'])
        ->assertSessionHasErrors('action');
    $this->actingAs($mod)->post(route('admin.presidentielle.moderation.action'), ['type' => 'affirmation', 'id' => $brouillon->id, 'action' => 'supprimer'])
        ->assertSessionHasNoErrors();

    expect(Affirmation::pluck('id')->all())->toBe([$publiee->id]);
});

it('refuse l\'accès sans la permission', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.presidentielle.affirmations'))
        ->assertForbidden();
});
