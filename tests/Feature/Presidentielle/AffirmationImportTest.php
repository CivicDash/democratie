<?php

use App\Models\Affirmation;
use App\Models\ProgrammeTheme;
use Illuminate\Support\Facades\File;

/**
 * Import du contrat presidentielle.affirmations.v1.
 *
 * Plus strict que les imports existants : ce JSON sort d'une conversion du dossier de
 * sourçage, et toute anomalie y signale une erreur de conversion — il vaut mieux refuser
 * le fichier que publier un placeholder.
 */
function contratAffirmations(array $surcharge = []): array
{
    $fiche = array_replace_recursive([
        'slug' => 'trop-de-normes',
        'enonce' => 'Il y a trop de normes pour les entreprises',
        'resume' => 'Le volume de droit croît ; son coût n\'est pas mesuré.',
        'theme' => 'institutions',
        'themes_secondaires' => [],
        'part_de_valeur' => true,
        'derniere_verification' => '2026-09-26',
        'coloration_percue' => 'droite',
        'verdicts' => [['portee' => 'sur le volume de droit', 'verdict' => 'confirme']],
        'sources' => [[
            'cle' => 'sgg-2026', 'producteur' => 'SGG', 'titre' => 'Indicateurs de suivi de l\'activité normative',
            'url' => 'https://www.legifrance.gouv.fr/contenu/indicateurs.pdf', 'archive_url' => null,
            'categorie' => 'producteur_public', 'date_publication' => null, 'date_consultation' => '2026-09-26',
        ]],
        'constats' => [
            ['ref' => 'c1', 'section' => 'chiffres', 'texte' => '366 999 articles en vigueur en 2026.', 'verification' => 'a_verifier',
                'note_verification' => 'confirmer dans le PDF', 'sources' => ['sgg-2026']],
            ['section' => 'limites', 'texte' => 'Volume ≠ charge.', 'verification' => 'verifie', 'sources' => []],
        ],
        'graphiques' => [],
    ], $surcharge);

    return ['contrat' => 'presidentielle.affirmations.v1', 'election' => '2027', 'affirmations' => [$fiche]];
}

function importerAffirmations(array $data, array $options = []): array
{
    $chemin = storage_path('app/testing/affirmations-'.uniqid().'.json');
    File::ensureDirectoryExists(dirname($chemin));
    File::put($chemin, json_encode($data));
    $code = Artisan::call('presidentielle:import-affirmations', ['fichier' => $chemin] + $options);
    File::delete($chemin);

    return [$code, Artisan::output()];
}

beforeEach(function () {
    ProgrammeTheme::factory()->create(['slug' => 'institutions', 'actif' => true]);
});

it('importe une fiche complète, en « détecté » et non publiée', function () {
    [$code, $sortie] = importerAffirmations(contratAffirmations());

    expect($code)->toBe(0)->and($sortie)->toContain('dont 1 à vérifier');
    $f = Affirmation::with(['verdicts', 'constats.sources', 'sources'])->firstOrFail();
    expect($f->statut_validation)->toBe('detecte')
        ->and($f->affiche_publiquement)->toBeFalse()
        ->and($f->coloration_percue)->toBe('droite')
        ->and($f->verdicts->first()->verdict)->toBe('confirme')
        ->and($f->constats)->toHaveCount(2)
        ->and($f->constats->first()->sources->first()->cle)->toBe('sgg-2026')
        ->and($f->constats->first()->note_verification)->toBe('confirmer dans le PDF');
});

it('n\'écrit rien en simulation', function () {
    [$code] = importerAffirmations(contratAffirmations(), ['--dry-run' => true]);

    expect($code)->toBe(0)->and(Affirmation::count())->toBe(0);
});

it('refuse le fichier entier au moindre écart au contrat', function (array $surcharge, string $attendu) {
    $data = contratAffirmations($surcharge['fiche'] ?? []);
    if (isset($surcharge['racine'])) {
        $data = array_replace($data, $surcharge['racine']);
    }

    [$code, $sortie] = importerAffirmations($data);

    expect($code)->toBe(1)->and($sortie)->toContain($attendu)
        ->and(Affirmation::count())->toBe(0);
})->with([
    'contrat inconnu' => [['racine' => ['contrat' => 'presidentielle.propositions.v1']], 'attendu « presidentielle.affirmations.v1 »'],
    'thème inconnu' => [['fiche' => ['theme' => 'astrologie']], 'thème inconnu « astrologie »'],
    'verdict inconnu' => [['fiche' => ['verdicts' => [['verdict' => 'faux']]]], 'verdict inconnu « faux »'],
    'placeholder d\'URL' => [['fiche' => ['sources' => [['url' => 'A_COMPLETER']]]], 'ni une URL http(s) ni null'],
    'domaine exclu' => [['fiche' => ['sources' => [['url' => 'https://www.cnews.fr/x']]]], 'domaine exclu'],
    'source inconnue' => [['fiche' => ['constats' => [['sources' => ['inconnue']]]]], 'clé de source inconnue « inconnue »'],
    'état de vérification inconnu' => [['fiche' => ['constats' => [['verification' => 'ok']]]], 'attendu « verifie » ou « a_verifier »'],
    'graphique sans phrase' => [['fiche' => ['graphiques' => [['type' => 'courbes', 'titre' => 'X', 'indicateurs' => ['part_nes_etranger']]]]], 'doit désigner la « ref » d\'un constat'],
    'indicateur hors catalogue' => [['fiche' => ['graphiques' => [['constat' => 'c1', 'type' => 'courbes', 'titre' => 'X', 'indicateurs' => ['pib_martien']]]]], 'absent du catalogue Eurostat'],
    'type de graphique interdit' => [['fiche' => ['graphiques' => [['constat' => 'c1', 'type' => 'camembert', 'titre' => 'X', 'indicateurs' => ['part_nes_etranger']]]]], 'type non autorisé « camembert »'],
]);

it('refuse une source citée par aucun constat', function () {
    $data = contratAffirmations();
    $data['affirmations'][0]['sources'][] = [
        'cle' => 'orpheline', 'producteur' => 'X', 'titre' => 'Y', 'url' => null, 'categorie' => 'presse',
    ];

    [$code, $sortie] = importerAffirmations($data);

    expect($code)->toBe(1)->and($sortie)->toContain('(orpheline) : source citée par aucun constat');
});

it('ne remplace une fiche existante qu\'à la demande, et jamais une fiche publiée', function () {
    importerAffirmations(contratAffirmations());

    [$code, $sortie] = importerAffirmations(contratAffirmations(['enonce' => 'Nouvel énoncé']));
    expect($code)->toBe(1)->and($sortie)->toContain('relancer avec « remplacer »');

    [$code] = importerAffirmations(contratAffirmations(['enonce' => 'Nouvel énoncé']), ['--remplacer' => true]);
    expect($code)->toBe(0)
        ->and(Affirmation::sole()->enonce)->toBe('Nouvel énoncé')
        ->and(Affirmation::sole()->constats()->count())->toBe(2);

    Affirmation::sole()->update(['statut_validation' => 'valide', 'affiche_publiquement' => true]);
    [$code, $sortie] = importerAffirmations(contratAffirmations(), ['--remplacer' => true]);
    expect($code)->toBe(1)->and($sortie)->toContain('fiche publiée');
});
