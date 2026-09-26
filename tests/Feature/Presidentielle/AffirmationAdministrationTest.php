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
        'contrat' => 'presidentielle.affirmations.v1',
        'affirmations' => [[
            'slug' => 'salaires-n-augmentent-pas',
            'enonce' => 'Les salaires n\'augmentent pas',
            'theme' => 'pouvoir-achat',
            'coloration_percue' => 'transversale',
            'verdicts' => [
                ['portee' => 'sur 2019-2024', 'verdict' => 'plutot_confirme'],
                ['portee' => 'sur trente ans', 'verdict' => 'nuance'],
            ],
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
    expect($props['raisons'])->toContain('1 constat(s) encore à vérifier')
        ->and($props['constats'])->toHaveCount(2)
        ->and($props['fiche']['coloration_percue'])->toBe('transversale');

    // 3. Validée mais pas publiable : il reste une phrase à vérifier.
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

it('recalcule le contrôle de symétrie, sans rien exposer d\'autre que des comptes', function () {
    affirmationPubliable(['coloration_percue' => 'gauche']);
    $droite = affirmationPubliable(['coloration_percue' => 'droite', 'affiche_publiquement' => true]);
    $droite->verdicts()->update(['verdict' => 'confirme']);

    $symetrie = $this->actingAs(moderateurAffirmations())->withHeaders(enTeteInertia())
        ->get(route('admin.presidentielle.affirmations'))->assertOk()->json('props.symetrie');

    $parColoration = fn ($vue) => collect($symetrie[$vue])->keyBy('coloration');
    expect($parColoration('toutes')['gauche']['familles']['nuance'])->toBe(1)
        ->and($parColoration('toutes')['droite']['familles']['confirme'])->toBe(1)
        ->and($parColoration('publiees')['gauche']['fiches'])->toBe(0)
        ->and($parColoration('publiees')['droite']['fiches'])->toBe(1);
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
