<?php

use App\Models\CadrageBudgetaire;
use App\Models\CadrageLigne;
use App\Models\CandidatPresidentielle;
use App\Services\Presidentielle\ModerationService;
use App\Services\Presidentielle\PresidentielleExporter;

/**
 * budgets.json : une grille commune, la référence officielle en regard, et l'état de chaque
 * candidat. Ce que l'export ne fait jamais : additionner, convertir, ou dériver quoi que ce
 * soit de la date du jour. Seules les lignes vérifiées sortent.
 */
function budgets(): array
{
    return app(PresidentielleExporter::class)->build('2027')['budgets'];
}

function cadragePublie(): array
{
    [$candidat, $cadrage, $reference, $document] = cadragePubliable();
    app(ModerationService::class)->publier($cadrage, moderateurPresidentielle());

    return [$candidat, $cadrage->fresh(), $reference, $document];
}

it('écrit budgets.json', function () {
    cadragePublie();
    $dir = sys_get_temp_dir().'/export_budgets_'.uniqid();
    $exporter = app(PresidentielleExporter::class);
    $exporter->write($exporter->build('2027'), $dir);

    $budgets = json_decode(file_get_contents("{$dir}/budgets.json"), true);
    expect($budgets['election'])->toBe('2027')
        ->and(array_column($budgets['rubriques'], 'cle'))->toBe(['horizon', 'economies', 'depenses_nouvelles', 'recettes_nouvelles', 'solde_vise', 'dette_visee', 'hypothese', 'autre_objectif']);
});

it('sans référence publiée ni cadrage, garde une forme stable et vide', function () {
    candidatPubliePublic();
    $b = budgets();

    expect($b['theme'])->toBeNull()
        ->and($b['reference'])->toBeNull()
        ->and(array_values($b['candidats'])[0])->toBe(['etat' => 'non_exprime']);
});

it('exporte un cadrage publié, sa ligne vérifiée et la référence la plus récente', function () {
    \App\Models\ProgrammeTheme::factory()->create(['slug' => 'fiscalite-budget', 'actif' => true]);
    [$candidat, $cadrage, $reference] = cadragePublie();
    // Une référence plus ancienne publiée : seule la plus récente sort.
    $ancienne = CadrageBudgetaire::factory()->reference()->publie()->create(['date_source' => '2026-04-15', 'titre' => 'Programme de stabilité 2026']);
    CadrageLigne::factory()->deReference()->verifiee()->create(['cadrage_id' => $ancienne->id]);

    $b = budgets();
    $c = $b['candidats'][$candidat->personnePolitique->slug];
    $ligne = $c['cadrages'][0]['rubriques']['recettes_nouvelles']['lignes'][0];

    expect($b['theme'])->toBe('fiscalite-budget')
        ->and($b['reference']['ref'])->toBe($reference->uuid)
        ->and($b['reference']['rubriques']['solde_vise']['lignes'][0]['source'])->toMatchArray(['producteur' => 'Ministère de l\'Économie et des Finances', 'date' => '2026-10-01'])
        ->and($c['etat'])->toBe('publie')
        ->and($c['cadrages'][0])->toMatchArray(['ref' => $cadrage->uuid, 'nature' => 'budget_premiere_annee', 'date_source' => '2026-10-14', 'a_verifier' => 0])
        ->and($c['cadrages'][0]['source']['porteur']['type'])->toBe('candidat')
        ->and($ligne)->toMatchArray(['montant' => 106.4, 'unite' => 'md_eur', 'portee' => 'annuelle', 'annee' => 2027, 'reperage' => '00:54:06', 'locuteur' => null, 'source' => null])
        ->and($ligne['url'])->toEndWith('&t=3246s')
        ->and($c['cadrages'][0]['rubriques']['economies'])->toBe(['etat' => 'non_precise']);
});

it('distingue « en cours de vérification » de « non précisé », et ne livre aucune valeur non vérifiée', function () {
    [$candidat, $cadrage] = cadragePublie();
    CadrageLigne::factory()->create(['cadrage_id' => $cadrage->id, 'rubrique' => 'economies', 'montant' => 12345.67, 'citation' => 'Phrase non vérifiée']);

    $b = budgets();
    $c = $b['candidats'][$candidat->personnePolitique->slug]['cadrages'][0];

    expect($c['rubriques']['economies'])->toBe(['etat' => 'en_verification'])
        ->and($c['rubriques']['dette_visee'])->toBe(['etat' => 'non_precise'])
        ->and($c['a_verifier'])->toBe(1)
        ->and(json_encode($b))->not->toContain('12345')->not->toContain('Phrase non vérifiée');
});

it('attribue une ligne dite par l\'équipe de campagne', function () {
    [$candidat, $cadrage] = cadragePublie();
    CadrageLigne::factory()->verifiee()->create([
        'cadrage_id' => $cadrage->id, 'rubrique' => 'poste', 'libelle' => 'Point d\'indice', 'montant' => 17,
        'locuteur' => 'Éric Coquerel', 'role_locuteur' => 'député', 'qualite_locuteur' => 'equipe_campagne',
    ]);

    $poste = budgets()['candidats'][$candidat->personnePolitique->slug]['cadrages'][0]['postes'][0];

    expect($poste)->toMatchArray(['libelle' => 'Point d\'indice', 'montant' => 17.0, 'locuteur' => 'Éric Coquerel', 'role_locuteur' => 'député', 'qualite_locuteur' => 'equipe_campagne']);
});

it('donne l\'état de chaque candidat publié : publié, en traitement (sans contenu), non exprimé', function () {
    [$publie] = cadragePublie();
    [$enCours] = candidatPubliePublic();
    CadrageBudgetaire::factory()->create(['candidat_id' => $enCours->id]);
    [$muet] = candidatPubliePublic();
    $cache = CandidatPresidentielle::factory()->create();
    CadrageBudgetaire::factory()->create(['candidat_id' => $cache->id]);

    $candidats = budgets()['candidats'];

    expect($candidats[$publie->personnePolitique->slug]['etat'])->toBe('publie')
        ->and($candidats[$enCours->personnePolitique->slug])->toBe(['etat' => 'en_traitement'])
        ->and($candidats[$muet->personnePolitique->slug])->toBe(['etat' => 'non_exprime'])
        ->and($candidats)->not->toHaveKey($cache->personnePolitique->slug);
});

it('n\'exporte que des lignes sourcées, autant que de lignes vérifiées, sans rien agréger', function () {
    [, $cadrage] = cadragePublie();
    CadrageLigne::factory()->verifiee()->create(['cadrage_id' => $cadrage->id, 'rubrique' => 'poste', 'libelle' => 'A', 'montant' => 3]);
    CadrageLigne::factory()->verifiee()->create(['cadrage_id' => $cadrage->id, 'rubrique' => 'poste', 'libelle' => 'B', 'montant' => 4]);
    CadrageLigne::factory()->create(['cadrage_id' => $cadrage->id, 'rubrique' => 'poste', 'libelle' => 'C', 'montant' => 5]);

    $b = budgets();
    $lignes = collect($b['candidats'])->flatMap(fn ($c) => $c['cadrages'] ?? [])
        ->flatMap(fn ($c) => [...collect($c['rubriques'])->flatMap(fn ($r) => $r['lignes'] ?? []), ...$c['postes']]);

    expect($lignes)->toHaveCount(CadrageLigne::where('cadrage_id', $cadrage->id)->where('verification', 'verifie')->count());
    foreach ($lignes as $l) {
        expect($l['citation'])->not->toBeEmpty()
            ->and($l['reperage'])->not->toBeEmpty()
            ->and($l['url'])->toStartWith('https://');
    }
    // Aucune somme : 3 + 4 = 7 n'apparaît nulle part.
    expect(json_encode($b))->not->toContain('"montant":7.0');
});

it('change le hash quand une ligne publiée change, pas quand un cadrage non publié change', function () {
    [, $cadrage] = cadragePublie();
    $exporter = app(PresidentielleExporter::class);
    [$autre] = candidatPubliePublic();
    $brouillon = CadrageBudgetaire::factory()->create(['candidat_id' => $autre->id]);
    CadrageLigne::factory()->create(['cadrage_id' => $brouillon->id]);
    $etatEnTraitement = $exporter->build('2027')['meta']['content_hash'];
    CadrageLigne::where('cadrage_id', $brouillon->id)->update(['montant' => 1]);
    expect($exporter->build('2027')['meta']['content_hash'])->toBe($etatEnTraitement);

    $cadrage->lignes()->first()->update(['montant' => 97.5]);
    expect($exporter->build('2027')['meta']['content_hash'])->not->toBe($etatEnTraitement);
});

it('rend un hash identique d\'un build à l\'autre, et 400 jours plus tard', function () {
    cadragePublie();
    $exporter = app(PresidentielleExporter::class);
    $hash = $exporter->build('2027')['meta']['content_hash'];

    expect($exporter->build('2027')['meta']['content_hash'])->toBe($hash);

    $this->travel(400)->days();
    expect($exporter->build('2027')['meta']['content_hash'])->toBe($hash);
});
