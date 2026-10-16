<?php

use App\Models\CadrageBudgetaire;
use App\Models\CadrageLigne;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Services\Presidentielle\IntegriteChecker;
use App\Services\Presidentielle\ModerationService;

/**
 * Règles de publication d'un cadrage budgétaire — le même code pour le bouton « Publier »
 * et pour le contrôle d'intégrité. Chaque chiffre affiché est cité, repéré et attribué.
 */
function raisonsCadrage(CadrageBudgetaire $c): array
{
    return app(ModerationService::class)->raisonsNonPubliable($c->fresh());
}

function ligneDe(CadrageBudgetaire $c): CadrageLigne
{
    return $c->lignes()->first();
}

it('accepte un cadrage complet', function () {
    [, $cadrage] = cadragePubliable();

    expect(raisonsCadrage($cadrage))->toBe([]);

    app(ModerationService::class)->publier($cadrage, moderateurPresidentielle());
    expect($cadrage->fresh()->affiche_publiquement)->toBeTrue()
        ->and(array_column(app(IntegriteChecker::class)->analyser()['violations'], 'type'))->not->toContain('cadrage_impubliable');
});

dataset('lignes non publiables', [
    'sans citation' => [['citation' => null], 'citation manquante'],
    'sans repérage' => [['reperage' => null], 'repérage manquant'],
    'repérage vidéo hors hh:mm:ss' => [['reperage' => '54:06'], 'hh:mm:ss'],
    'déficit visé sans année' => [['rubrique' => 'solde_vise', 'unite' => 'pct_pib', 'montant' => 5, 'annee' => null], 'année manquante'],
    'poste sans libellé' => [['rubrique' => 'poste', 'libelle' => null], 'libellé manquant'],
    'unité incohérente' => [['rubrique' => 'horizon', 'unite' => 'md_eur'], 'incohérente'],
    'montant manquant' => [['montant' => null], 'montant manquant'],
    'texte sans valeur' => [['unite' => 'texte', 'valeur_texte' => null], 'toutes lettres manquante'],
    'locuteur sans rôle' => [['locuteur' => 'Éric Coquerel', 'qualite_locuteur' => 'equipe_campagne', 'role_locuteur' => null], 'sans rôle'],
    'rubrique de la référence' => [['rubrique' => 'deficit_constate'], 'non admise'],
]);

it('refuse une ligne vérifiée incomplète', function (array $defaut, string $attendu) {
    [, $cadrage] = cadragePubliable();
    ligneDe($cadrage)->update($defaut);

    expect(implode(' ; ', raisonsCadrage($cadrage)))->toContain($attendu);
})->with('lignes non publiables');

it('ne contrôle pas une ligne non vérifiée (elle ne paraît pas)', function () {
    [, $cadrage] = cadragePubliable();
    CadrageLigne::factory()->create(['cadrage_id' => $cadrage->id, 'rubrique' => 'poste', 'libelle' => null, 'citation' => null]);

    expect(raisonsCadrage($cadrage))->toBe([]);
});

it('refuse une proposition source non validée, ou d\'un autre candidat', function () {
    [$candidat, $cadrage, , $document] = cadragePubliable();
    $detectee = IngestionProposition::factory()->create(['document_id' => $document->id, 'candidat_id' => $candidat->id, 'statut' => 'detecte']);
    ligneDe($cadrage)->update(['proposition_id' => $detectee->id]);
    expect(implode(' ', raisonsCadrage($cadrage)))->toContain('non validée');

    $autre = IngestionProposition::factory()->create(['document_id' => $document->id, 'statut' => 'rattachee']);
    ligneDe($cadrage)->update(['proposition_id' => $autre->id]);
    expect(implode(' ', raisonsCadrage($cadrage)))->toContain('autre candidat');

    $bonne = IngestionProposition::factory()->create(['document_id' => $document->id, 'candidat_id' => $candidat->id, 'statut' => 'validee']);
    ligneDe($cadrage)->update(['proposition_id' => $bonne->id]);
    expect(raisonsCadrage($cadrage))->toBe([]);
});

it('exige l\'endossement d\'un document de l\'équipe de campagne, et refuse un document de groupe', function () {
    [, $cadrage, , $document] = cadragePubliable();

    $document->update(['porteur_type' => 'equipe_campagne', 'porteur_libelle' => 'Équipe de campagne']);
    expect(implode(' ', raisonsCadrage($cadrage)))->toContain('endossement');

    $document->update(['porteur_type' => 'groupe_parlementaire', 'endossement_url' => 'https://e.fr/e', 'endossement_date' => '2026-10-06', 'endossement_note' => 'n']);
    expect(implode(' ', raisonsCadrage($cadrage)))->toContain('parti ou un groupe parlementaire');
});

it('refuse une ligne de référence sans producteur, URL ou date', function () {
    [, , $reference] = cadragePubliable();
    $reference->lignes()->first()->update(['source_producteur' => null, 'source_url' => 'https://A_COMPLETER', 'source_date' => null]);

    $raisons = implode(' ; ', raisonsCadrage($reference));
    expect($raisons)->toContain('producteur')->toContain('URL')->toContain('date de la source');
});

it('refuse de publier un cadrage tant qu\'aucune référence n\'est publiée', function () {
    [, $cadrage, $reference] = cadragePubliable();
    $reference->update(['affiche_publiquement' => false]);

    expect(implode(' ', raisonsCadrage($cadrage)))->toContain('référence officielle publiée');
});

it('refuse deux lignes vérifiées pour la même case', function () {
    [, $cadrage] = cadragePubliable();
    CadrageLigne::factory()->verifiee()->create(['cadrage_id' => $cadrage->id, 'montant' => 97.5]);

    expect(implode(' ', raisonsCadrage($cadrage)))->toContain('même case');
});

it('refuse de dépublier la référence tant qu\'un cadrage reste publié', function () {
    $mod = moderateurPresidentielle();
    [, $cadrage, $reference] = cadragePubliable();
    app(ModerationService::class)->publier($cadrage, $mod);

    $this->actingAs($mod)->post(route('admin.presidentielle.moderation.action'), ['type' => 'cadrage', 'id' => $reference->id, 'action' => 'depublier'])
        ->assertSessionHasErrors('integrite');
    expect($reference->fresh()->affiche_publiquement)->toBeTrue();
});

it('refuse de vider la citation de la seule ligne vérifiée d\'un cadrage publié', function () {
    $mod = moderateurPresidentielle();
    [, $cadrage] = cadragePubliable();
    app(ModerationService::class)->publier($cadrage, $mod);
    $ligne = ligneDe($cadrage);

    $this->actingAs($mod)->post(route('admin.presidentielle.budgets.lignes.update', $ligne->id), [
        'rubrique' => $ligne->rubrique, 'unite' => $ligne->unite, 'montant' => 106.4, 'annee' => 2027,
        'citation' => null, 'reperage' => $ligne->reperage,
    ])->assertSessionHasErrors('integrite');

    expect($ligne->fresh()->citation)->not->toBeNull()
        ->and($ligne->fresh()->verification)->toBe('verifie');
});

it('remet « à vérifier » une ligne dont le montant change', function () {
    $mod = moderateurPresidentielle();
    [, $cadrage] = cadragePubliable();
    $ligne = ligneDe($cadrage);
    CadrageLigne::factory()->verifiee()->create(['cadrage_id' => $cadrage->id, 'rubrique' => 'depenses_nouvelles', 'montant' => 80, 'qualificatif' => 'plus_de']);

    $this->actingAs($mod)->post(route('admin.presidentielle.budgets.lignes.update', $ligne->id), [
        'rubrique' => 'recettes_nouvelles', 'unite' => 'md_eur', 'montant' => 97.5, 'annee' => 2027, 'portee' => 'annuelle',
        'valeur_texte' => '106,4 Md€', 'citation' => $ligne->citation, 'reperage' => $ligne->reperage,
    ])->assertSessionHasNoErrors();

    expect($ligne->fresh()->verification)->toBe('a_verifier')
        ->and((float) $ligne->fresh()->montant)->toBe(97.5);

    // Seule la note change : la vérification tient.
    $this->actingAs($mod)->post(route('admin.presidentielle.budgets.lignes.verification', $ligne->id), ['verification' => 'verifie'])->assertSessionHasNoErrors();
    $this->actingAs($mod)->post(route('admin.presidentielle.budgets.lignes.update', $ligne->id), [
        'rubrique' => 'recettes_nouvelles', 'unite' => 'md_eur', 'montant' => 97.5, 'annee' => 2027, 'portee' => 'annuelle',
        'valeur_texte' => '106,4 Md€', 'citation' => $ligne->citation, 'reperage' => $ligne->reperage, 'note' => 'relu à l\'écoute',
    ])->assertSessionHasNoErrors();
    expect($ligne->fresh()->verification)->toBe('verifie')->and($ligne->fresh()->verifie_par)->toBe($mod->id);
});

it('n\'admet dans le cadrage d\'un candidat que son équipe de campagne, jamais un parti', function () {
    $mod = moderateurPresidentielle();
    [, $cadrage] = cadragePubliable();

    $this->actingAs($mod)->post(route('admin.presidentielle.budgets.lignes.store', $cadrage->id), [
        'rubrique' => 'poste', 'libelle' => 'Taxe X', 'unite' => 'md_eur', 'montant' => 3,
        'citation' => 'trois milliards', 'reperage' => '00:10:00',
        'locuteur' => 'Paul Parti', 'role_locuteur' => 'président du parti', 'qualite_locuteur' => 'parti',
    ])->assertSessionHasErrors('qualite_locuteur');

    $this->actingAs($mod)->post(route('admin.presidentielle.budgets.lignes.store', $cadrage->id), [
        'rubrique' => 'poste', 'libelle' => 'Taxe X', 'unite' => 'md_eur', 'montant' => 3,
        'citation' => 'trois milliards', 'reperage' => '00:10:00',
        'locuteur' => 'Éric Coquerel', 'role_locuteur' => 'député', 'qualite_locuteur' => 'equipe_campagne',
    ])->assertSessionHasNoErrors();

    expect(CadrageLigne::where('locuteur', 'Éric Coquerel')->sole()->verification)->toBe('a_verifier');
});

it('crée la référence et un cadrage depuis l\'écran, avec des refus cohérents', function () {
    $mod = moderateurPresidentielle();
    [$candidat] = candidatPubliePublic();

    $this->actingAs($mod)->post(route('admin.presidentielle.budgets.store'), [
        'candidat_id' => $candidat->id, 'nature' => 'reference_officielle', 'titre' => 'X', 'date_source' => '2026-10-01',
    ])->assertSessionHasErrors('nature');

    $this->actingAs($mod)->post(route('admin.presidentielle.budgets.store'), [
        'candidat_id' => null, 'nature' => 'reference_officielle', 'titre' => 'PLF 2027, comptes INSEE, avis du HCFP', 'date_source' => '2026-10-01',
    ])->assertSessionHasNoErrors();

    $groupe = IngestionDocument::factory()->porte('groupe_parlementaire')->create();
    $this->actingAs($mod)->post(route('admin.presidentielle.budgets.store'), [
        'candidat_id' => $candidat->id, 'nature' => 'budget_premiere_annee', 'titre' => 'Contre-budget', 'date_source' => '2026-10-06',
        'ingestion_document_id' => $groupe->id,
    ])->assertSessionHasErrors('ingestion_document_id');

    expect(CadrageBudgetaire::reference()->sole()->statut_validation)->toBe('detecte')
        ->and(CadrageBudgetaire::count())->toBe(1);
});

it('supprime un cadrage non publié, refuse un cadrage publié', function () {
    $mod = moderateurPresidentielle();
    [, $cadrage] = cadragePubliable();
    app(ModerationService::class)->publier($cadrage, $mod);

    $this->actingAs($mod)->post(route('admin.presidentielle.moderation.action'), ['type' => 'cadrage', 'id' => $cadrage->id, 'action' => 'supprimer'])
        ->assertSessionHasErrors('action');

    $this->actingAs($mod)->post(route('admin.presidentielle.moderation.action'), ['type' => 'cadrage', 'id' => $cadrage->id, 'action' => 'depublier'])->assertSessionHasNoErrors();
    $this->actingAs($mod)->post(route('admin.presidentielle.moderation.action'), ['type' => 'cadrage', 'id' => $cadrage->id, 'action' => 'supprimer'])->assertSessionHasNoErrors();
    expect(CadrageBudgetaire::find($cadrage->id))->toBeNull();

    // Le journal d'un cadrage se lit.
    $this->actingAs($mod)->getJson(route('admin.presidentielle.journal', ['type' => 'cadrage', 'id' => $cadrage->id]))->assertOk();
});
