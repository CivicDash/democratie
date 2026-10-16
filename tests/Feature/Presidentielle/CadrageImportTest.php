<?php

use App\Models\CadrageBudgetaire;
use App\Models\CadrageLigne;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use Database\Seeders\CandidatsPresidentielle2027Seeder;
use Database\Seeders\ProgrammeThemesSeeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Import du cadrage budgétaire (contrat presidentielle.cadrage_budgetaire.v1) : plusieurs
 * cadrages par source, une ligne par valeur, tout en `detecte` et « à vérifier ».
 */
beforeEach(function () {
    (new ProgrammeThemesSeeder())->run();
    (new CandidatsPresidentielle2027Seeder())->run();
});

function cadrageV1(array $cadrages, array $extra = []): array
{
    return $extra + [
        'contrat' => 'presidentielle.cadrage_budgetaire.v1',
        'candidat_slug' => 'jean-luc-melenchon',
        'source' => ['titre' => 'Conférence comptes publics', 'type' => 'conference', 'url' => 'https://www.youtube.com/watch?v=u4_Iyt1L1I8', 'date' => '2026-10-14'],
        'cadrages' => $cadrages,
    ];
}

function cadrageSimple(array $lignes, string $nature = 'budget_premiere_annee'): array
{
    return ['nature' => $nature, 'titre' => 'Budget de première année 2027', 'non_precise' => ['Taxe GAFAM : montant non prononcé.'], 'lignes' => $lignes];
}

it('crée un cadrage en `detecte` avec ses rubriques, ses unités et ses locuteurs', function () {
    $chemin = jsonTemporaire(cadrageV1([cadrageSimple([
        ['rubrique' => 'recettes_nouvelles', 'montant' => 106.4, 'annee' => 2027, 'valeur_texte' => '106,4 Md€',
            'citation' => 'C\'est une taxe totale 106,4 milliards d\'euros.', 'timecode' => '00:54:06',
            'locuteur' => 'Éric Coquerel', 'role_locuteur' => 'député'],
        ['rubrique' => 'solde_vise', 'libelle' => 'Déficit public', 'montant' => 5, 'annee' => 2027, 'citation' => 'à 5 % du PIB', 'timecode' => '00:54:51'],
        ['rubrique' => 'depenses_nouvelles', 'montant' => 80, 'qualificatif' => 'plus_de', 'citation' => 'plus de 80 milliards', 'timecode' => '00:54:17'],
        ['rubrique' => 'poste', 'libelle' => 'Plafond du livret A', 'montant' => 30, 'montant_max' => 40, 'citation' => '30 à 40 milliards', 'timecode' => '00:57:05'],
        ['rubrique' => 'hypothese', 'libelle' => 'Croissance', 'valeur_texte' => 'environ +0,5 point', 'citation' => 'à peu près d\'autant', 'timecode' => '00:55:27'],
        ['rubrique' => 'horizon', 'valeur_texte' => '2027', 'citation' => 'ce que nous ferions en 2027', 'timecode' => '01:04:15',
            'locuteur' => 'Jean-Luc Mélenchon', 'role_locuteur' => 'candidat'],
    ])]));

    $code = Artisan::call('presidentielle:import-cadrage', ['fichier' => $chemin]);
    expect($code)->toBe(0, Artisan::output());

    $cadrage = CadrageBudgetaire::sole();
    $lignes = $cadrage->lignes->keyBy('rubrique');

    expect($cadrage->statut_validation)->toBe('detecte')
        ->and($cadrage->affiche_publiquement)->toBeFalse()
        ->and($cadrage->nature)->toBe('budget_premiere_annee')
        ->and($cadrage->date_source->toDateString())->toBe('2026-10-14')
        ->and($cadrage->rubriques_non_precisees)->toBe(['Taxe GAFAM : montant non prononcé.'])
        ->and($cadrage->document->url)->toBe('https://www.youtube.com/watch?v=u4_Iyt1L1I8')
        ->and($lignes['recettes_nouvelles']->unite)->toBe('md_eur')
        ->and($lignes['recettes_nouvelles']->qualite_locuteur)->toBe('equipe_campagne')
        ->and($lignes['recettes_nouvelles']->reperage)->toBe('00:54:06')
        ->and($lignes['solde_vise']->unite)->toBe('pct_pib')
        ->and($lignes['depenses_nouvelles']->qualificatif)->toBe('plus_de')
        ->and((float) $lignes['poste']->montant_max)->toBe(40.0)
        ->and($lignes['hypothese']->unite)->toBe('texte')
        ->and($lignes['horizon']->locuteur)->toBeNull()
        ->and(CadrageLigne::where('verification', 'verifie')->count())->toBe(0);
});

it('crée autant de cadrages que la source en présente (programme quinquennal et contre-budget)', function () {
    $chemin = jsonTemporaire(cadrageV1([
        cadrageSimple([['rubrique' => 'economies', 'montant' => 140, 'annee' => 2032, 'citation' => '140 milliards', 'timecode' => '00:08:26']], 'premier_cadrage'),
        cadrageSimple([['rubrique' => 'economies', 'montant' => 64, 'annee' => 2027, 'citation' => '64 milliards', 'timecode' => '00:54:05',
            'locuteur' => 'Jordan Exemple', 'role_locuteur' => 'président du parti']]),
    ]));

    expect(Artisan::call('presidentielle:import-cadrage', ['fichier' => $chemin]))->toBe(0);

    expect(CadrageBudgetaire::orderBy('id')->pluck('nature')->all())->toBe(['premier_cadrage', 'budget_premiere_annee'])
        ->and(CadrageBudgetaire::pluck('ingestion_document_id')->unique())->toHaveCount(1)
        ->and(IngestionDocument::count())->toBe(1);
});

it('importe une ligne sans citation, à vérifier et non publiable en l\'état', function () {
    $chemin = jsonTemporaire(cadrageV1([cadrageSimple([['rubrique' => 'economies', 'montant' => 100]])]));

    expect(Artisan::call('presidentielle:import-cadrage', ['fichier' => $chemin]))->toBe(0)
        ->and(Artisan::output())->toContain('sans citation');

    $ligne = CadrageLigne::sole();
    expect($ligne->verification)->toBe('a_verifier')->and($ligne->citation)->toBeNull();
});

dataset('cadrages refusés', [
    'nature inconnue' => [fn () => cadrageV1([cadrageSimple([], 'grand_plan')]), 'nature'],
    'rubrique inconnue' => [fn () => cadrageV1([cadrageSimple([['rubrique' => 'tresorerie', 'montant' => 1]])]), 'rubrique'],
    'montant en texte' => [fn () => cadrageV1([cadrageSimple([['rubrique' => 'economies', 'montant' => '150 Md€']])]), 'nombre attendu'],
    'unité inconnue' => [fn () => cadrageV1([cadrageSimple([['rubrique' => 'economies', 'montant' => 1, 'unite' => 'francs']])]), 'unite'],
    'locuteur d\'un parti' => [fn () => cadrageV1([cadrageSimple([['rubrique' => 'economies', 'montant' => 1, 'citation' => 'x', 'locuteur' => 'Paul Parti', 'role_locuteur' => 'p', 'qualite_locuteur' => 'parti']])]), 'parle pour un parti'],
    'URL provisoire' => [fn () => cadrageV1([cadrageSimple([])], ['source' => ['titre' => 'x', 'url' => 'https://A_COMPLETER', 'date' => '2026-10-14']]), 'provisoire'],
    'candidat inconnu' => [fn () => cadrageV1([cadrageSimple([])], ['candidat_slug' => 'personne-inconnue']), 'introuvable'],
    'format libre de l\'ingestion' => [fn () => ['horizon' => ['valeur' => '2027'], 'economies' => ['montant' => null]], 'format non reconnu'],
]);

it('refuse un fichier non conforme, sans rien écrire', function (Closure $contenu, string $attendu) {
    $code = Artisan::call('presidentielle:import-cadrage', ['fichier' => jsonTemporaire($contenu())]);

    expect($code)->toBe(1)
        ->and(Artisan::output())->toContain($attendu)
        ->and(CadrageBudgetaire::count())->toBe(0)
        ->and(IngestionDocument::count())->toBe(0);
})->with('cadrages refusés');

it('relie le cadrage au document créé par import-propositions --cadrage, et la ligne à sa proposition', function () {
    $transcription = tempnam(sys_get_temp_dir(), 'tr_');
    file_put_contents($transcription, "00:54:06 C'est une taxe totale 106,4 milliards d'euros. Et nous faisons 25 milliards de réduction du déficit.");

    $props = jsonTemporaire([
        'contrat' => 'presidentielle.propositions.v1',
        'source' => ['titre' => 'Conférence', 'type' => 'conference', 'url' => 'https://www.youtube.com/watch?v=u4_Iyt1L1I8', 'date' => '2026-10-14'],
        'propositions' => [[
            'candidat_slug' => 'jean-luc-melenchon', 'theme_slug' => 'fiscalite-budget', 'type' => 'declaration',
            'resume_propose' => 'Recettes nouvelles de 106,4 Md€.', 'citation_verbatim' => 'C\'est une taxe totale 106,4 milliards d\'euros.',
            'timestamp_ou_paragraphe' => '00:54:06', 'locuteur' => 'Éric Coquerel', 'role_locuteur' => 'député',
        ]],
    ]);
    $cadrage = jsonTemporaire(cadrageV1([cadrageSimple([
        ['rubrique' => 'recettes_nouvelles', 'montant' => 106.4, 'annee' => 2027, 'citation' => 'une taxe totale 106,4 milliards d\'euros', 'timecode' => '00:54:06',
            'locuteur' => 'Éric Coquerel', 'role_locuteur' => 'député'],
        ['rubrique' => 'economies', 'montant' => 12, 'citation' => 'une phrase que personne n\'a prononcée pendant la conférence', 'timecode' => '00:10:00'],
    ])], ['candidat_slug' => null]));

    $code = Artisan::call('presidentielle:import-propositions', ['fichier' => $props, '--source' => $transcription, '--cadrage' => $cadrage]);
    expect($code)->toBe(0, Artisan::output());

    $document = IngestionDocument::sole();
    $c = CadrageBudgetaire::sole();
    $recettes = $c->lignes->firstWhere('rubrique', 'recettes_nouvelles');
    $economies = $c->lignes->firstWhere('rubrique', 'economies');

    expect($c->ingestion_document_id)->toBe($document->id)
        // Sans candidat_slug : le candidat unique des propositions du document.
        ->and($c->candidat_id)->toBe(IngestionProposition::sole()->candidat_id)
        ->and($recettes->proposition_id)->toBe(IngestionProposition::sole()->id)
        ->and($recettes->verbatim_verifie)->toBeTrue()
        ->and($economies->verbatim_verifie)->toBeFalse()
        ->and($economies->proposition_id)->toBeNull();
});

it('un cadrage refusé annule aussi les propositions de la même importation', function () {
    $props = jsonTemporaire(['source' => ['titre' => 'x'], 'propositions' => [[
        'candidat_slug' => 'jean-luc-melenchon', 'resume_propose' => 'r', 'citation_verbatim' => 'c',
    ]]]);
    $cadrage = jsonTemporaire(cadrageV1([cadrageSimple([], 'grand_plan')]));

    expect(Artisan::call('presidentielle:import-propositions', ['fichier' => $props, '--cadrage' => $cadrage]))->toBe(1)
        ->and(IngestionProposition::count())->toBe(0)
        ->and(IngestionDocument::count())->toBe(0);
});

it('importe la référence officielle sans candidat ni document, sourcée ligne à ligne', function () {
    $chemin = jsonTemporaire([
        'contrat' => 'presidentielle.cadrage_budgetaire.v1',
        'cadrages' => [[
            'nature' => 'reference_officielle', 'titre' => 'PLF 2027, comptes INSEE, avis du HCFP', 'date_source' => '2026-10-01',
            'lignes' => [
                ['rubrique' => 'solde_vise', 'montant' => 4.7, 'annee' => 2027, 'citation' => 'Le déficit public atteindrait 4,7 % du PIB.', 'reperage' => 'p. 9',
                    'source' => ['producteur' => 'Ministère de l\'Économie', 'titre' => 'PLF 2027', 'url' => 'https://www.budget.gouv.fr/plf-2027', 'date' => '2026-10-01']],
                ['rubrique' => 'deficit_constate', 'montant' => 5.4, 'annee' => 2025, 'citation' => 'Le déficit public s\'établit à 5,4 % du PIB.',
                    'source' => ['producteur' => 'INSEE', 'titre' => 'Comptes de la Nation', 'url' => 'https://www.insee.fr/fr/statistiques/1', 'date' => '2026-05-28']],
            ],
        ]],
    ]);

    expect(Artisan::call('presidentielle:import-cadrage', ['fichier' => $chemin]))->toBe(0, Artisan::output());

    $reference = CadrageBudgetaire::sole();
    expect($reference->candidat_id)->toBeNull()
        ->and($reference->ingestion_document_id)->toBeNull()
        ->and($reference->lignes->pluck('source_producteur')->all())->toBe(['Ministère de l\'Économie', 'INSEE'])
        ->and(IngestionDocument::count())->toBe(0);
});
