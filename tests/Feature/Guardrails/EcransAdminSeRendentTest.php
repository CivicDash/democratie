<?php

use App\Models\User;

/**
 * Les écrans d'administration doivent se rendre, pas seulement exister.
 *
 * Une réponse 200 fait passer la requête par app.blade.php, donc par
 * `@vite("resources/js/Pages/{$composant}.vue")` : un composant absent du manifeste
 * fait échouer ce test exactement comme il fait échouer la page. C'est ce contrôle qui
 * manquait quand /cookies et la fiche élu publique renvoyaient un 500.
 */
beforeEach(function () {
    // Sans manifeste Vite, app.blade.php lève avant même d'atteindre le composant :
    // le test échouerait pour la mauvaise raison. Le message le dit plutôt que de
    // laisser une trace d'exception opaque.
    if (! file_exists(public_path('build/manifest.json'))) {
        $this->markTestSkipped('Manifeste Vite absent : lancer `npm run build` avant ce test.');
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

dataset('écrans admin', [
    'tableau de bord' => ['admin.dashboard'],
    'utilisateurs' => ['admin.users.index'],
    'création utilisateur' => ['admin.users.create'],
    'santé des données' => ['admin.data-health'],
    'blocages IP' => ['admin.ip-bans.index'],
    'adhérents' => ['admin.association.index'],
    'domaines' => ['admin.domaines.index'],
    'budget' => ['admin.budget.index'],
    'finances publiques' => ['admin.finances.index'],
    'mots modérés' => ['admin.moderation.words'],
    'photos' => ['admin.moderation.photos.index'],
    'historique photos' => ['admin.moderation.photos.history'],
    'affaires judiciaires' => ['admin.affaires.index'],
    'statistiques France' => ['admin.stats-france.index'],
    'élus' => ['admin.elus.index'],
    'gouvernement' => ['admin.gouvernement.index'],
    'imports' => ['admin.imports'],
    'test email' => ['admin.email.index'],
    // Statistiques : les dix écrans pilotés par le schéma.
    'stats démographie' => ['admin.stats-france.demographie'],
    'stats économie' => ['admin.stats-france.economie'],
    'stats budget' => ['admin.stats-france.budget'],
    'stats recettes' => ['admin.stats-france.recettes'],
    'stats dépenses' => ['admin.stats-france.depenses'],
    'stats éducation' => ['admin.stats-france.education'],
    'stats santé' => ['admin.stats-france.sante'],
    'stats environnement' => ['admin.stats-france.environnement'],
    'stats sécurité' => ['admin.stats-france.securite'],
    'stats emploi' => ['admin.stats-france.emploi'],
    // Module présidentielle : l'usage principal du back-office.
    'présidentielle — file' => ['admin.presidentielle.moderation'],
    'présidentielle — propositions' => ['admin.presidentielle.propositions'],
    'présidentielle — mesures' => ['admin.presidentielle.mesures'],
    'présidentielle — controverses' => ['admin.presidentielle.controverses'],
    'présidentielle — candidats' => ['admin.presidentielle.candidats'],
    'présidentielle — parcours' => ['admin.presidentielle.parcours'],
    'présidentielle — signalements' => ['admin.presidentielle.signalements'],
    'présidentielle — événements' => ['admin.presidentielle.evenements'],
    'présidentielle — thèmes' => ['admin.presidentielle.themes'],
    'présidentielle — audience' => ['admin.presidentielle.audience'],
    'présidentielle — médias' => ['admin.presidentielle.medias'],
    'présidentielle — HATVP' => ['admin.presidentielle.hatvp'],
    // Modération citoyenne.
    'modération — tableau de bord' => ['moderation.dashboard'],
    'modération — signalements' => ['moderation.reports.index'],
    'modération — sanctions' => ['moderation.sanctions.index'],
]);

it('se rend sans erreur', function (string $nomRoute) {
    $this->actingAs($this->admin)
        ->get(route($nomRoute))
        ->assertSuccessful();
})->with('écrans admin');

it('rend les pages légales publiques', function () {
    // /cookies renvoyait un 500 : le composant Vue n'existait pas, alors que les CGU
    // et la politique de confidentialité pointaient toutes deux vers cette page.
    foreach (['privacy', 'terms', 'cookies'] as $nomRoute) {
        $this->get(route($nomRoute))->assertSuccessful();
    }
});
