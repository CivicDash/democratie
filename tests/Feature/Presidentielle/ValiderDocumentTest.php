<?php

use App\Models\CandidatPresidentielle;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Models\PresidentielleModerationLog;
use App\Models\ProgrammeMesure;
use App\Models\ProgrammeTheme;
use App\Models\User;
use Spatie\Permission\Models\Permission;

/**
 * « Valider tout le document » : seules les citations confirmées mot pour mot du candidat
 * ou de son équipe passent, chacune par le circuit unitaire ; le reste reste en file.
 */
function moderateurDuLot(): User
{
    Permission::findOrCreate('moderer_presidentielle', 'web');
    $u = User::factory()->create();
    $u->givePermissionTo('moderer_presidentielle');

    return $u;
}

function documentAValider(): array
{
    $doc = IngestionDocument::create(['type' => 'video', 'titre' => 'Conférence test', 'statut' => 'extrait']);
    $candidat = CandidatPresidentielle::factory()->create();
    $theme = ProgrammeTheme::factory()->create();
    $base = ['document_id' => $doc->id, 'candidat_id' => $candidat->id, 'theme_id' => $theme->id,
        'citation_verbatim' => 'je ferai la mesure X', 'statut' => 'detecte', 'confiance' => 0.9, 'verbatim_verifie' => true];

    $p = [
        'mesure' => IngestionProposition::create($base + ['type' => 'mesure', 'resume_propose' => 'Instaurer la mesure X.']),
        'declaration' => IngestionProposition::create($base + ['type' => 'declaration', 'resume_propose' => 'Affirme que Y. Chiffre à vérifier.']),
        'equipe' => IngestionProposition::create($base + ['type' => 'mesure', 'resume_propose' => 'Mesure Z.',
            'locuteur' => 'Une Porte-parole', 'role_locuteur' => 'porte-parole de campagne', 'qualite_locuteur' => 'equipe_campagne']),
        'non_confirmee' => IngestionProposition::create(array_merge($base, ['type' => 'mesure', 'resume_propose' => 'Mesure W.', 'verbatim_verifie' => false])),
        'parti' => IngestionProposition::create($base + ['type' => 'position', 'resume_propose' => 'Position du parti.',
            'locuteur' => 'Un Député', 'role_locuteur' => 'député', 'qualite_locuteur' => 'parti']),
        'deja' => IngestionProposition::create(array_merge($base, ['type' => 'mesure', 'resume_propose' => 'Déjà traitée.', 'statut' => 'rejetee'])),
    ];

    return [$doc, $p];
}

it('crée les mesures des propositions « mesure », valide le reste sans mesure, et laisse le doute en file', function () {
    [$doc, $p] = documentAValider();
    $mod = moderateurDuLot();

    $this->actingAs($mod)->post(route('admin.presidentielle.documents.valider-tout', $doc->id), ['mode' => 'mesures'])
        ->assertRedirect()->assertSessionHasNoErrors();

    expect($p['mesure']->fresh()->statut)->toBe('rattachee')
        ->and($p['equipe']->fresh()->statut)->toBe('rattachee')
        ->and($p['declaration']->fresh()->statut)->toBe('validee')
        ->and($p['declaration']->fresh()->mesure_id)->toBeNull()
        ->and($p['non_confirmee']->fresh()->statut)->toBe('detecte')
        ->and($p['parti']->fresh()->statut)->toBe('detecte')
        ->and($p['deja']->fresh()->statut)->toBe('rejetee')
        ->and(ProgrammeMesure::count())->toBe(2)
        ->and(ProgrammeMesure::where('statut_validation', 'detecte')->where('affiche_publiquement', false)->count())->toBe(2);
});

it('valide tout sans créer de mesure en mode « sans mesure »', function () {
    [$doc, $p] = documentAValider();

    $this->actingAs(moderateurDuLot())->post(route('admin.presidentielle.documents.valider-tout', $doc->id), ['mode' => 'sans_mesure'])
        ->assertRedirect();

    expect($p['mesure']->fresh()->statut)->toBe('validee')
        ->and($p['equipe']->fresh()->statut)->toBe('validee')
        ->and(ProgrammeMesure::count())->toBe(0)
        ->and($p['non_confirmee']->fresh()->statut)->toBe('detecte');
});

it('trace chaque décision au journal, comme une validation à la main', function () {
    [$doc, $p] = documentAValider();

    $this->actingAs(moderateurDuLot())->post(route('admin.presidentielle.documents.valider-tout', $doc->id), ['mode' => 'sans_mesure']);

    expect(PresidentielleModerationLog::where('action', 'validation_sans_mesure')->count())->toBe(3);
});

it('rend les refus des règles sans interrompre le lot', function () {
    [$doc, $p] = documentAValider();
    // Un membre de l'équipe sans rôle : la règle du locuteur refuse, les autres passent.
    $p['equipe']->update(['role_locuteur' => null]);

    $this->actingAs(moderateurDuLot())->post(route('admin.presidentielle.documents.valider-tout', $doc->id), ['mode' => 'sans_mesure'])
        ->assertSessionHas('echecs_lot', fn ($e) => count($e) === 1 && $e[0]['id'] === $p['equipe']->id);

    expect($p['equipe']->fresh()->statut)->toBe('detecte')
        ->and($p['mesure']->fresh()->statut)->toBe('validee');
});

it('refuse une prise de parole portée par un parti', function () {
    [$doc] = documentAValider();
    $doc->update(['porteur_type' => 'parti', 'porteur_libelle' => 'Un parti']);

    $this->actingAs(moderateurDuLot())->post(route('admin.presidentielle.documents.valider-tout', $doc->id), ['mode' => 'mesures'])
        ->assertSessionHasErrors('document');

    expect(IngestionProposition::where('statut', 'detecte')->count())->toBe(5);
});

it('refuse un mode inconnu et un visiteur sans droit de modération', function () {
    [$doc] = documentAValider();

    $this->actingAs(moderateurDuLot())->post(route('admin.presidentielle.documents.valider-tout', $doc->id), ['mode' => 'publier'])
        ->assertSessionHasErrors('mode');
    $this->actingAs(User::factory()->create())
        ->post(route('admin.presidentielle.documents.valider-tout', $doc->id), ['mode' => 'mesures'])
        ->assertForbidden();
});
