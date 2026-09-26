<?php

use App\Models\EurostatIndicateur;
use App\Models\User;
use App\Services\Presidentielle\Eurostat\ClientEurostat;
use App\Services\Presidentielle\Eurostat\ExtractionEurostat;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Extraction Eurostat (portage de fetch_eurostat.py).
 *
 * La réponse `prc_hicp_aind` ci-dessous est une réponse réelle de l'API (IPCH, indice
 * annuel moyen, Allemagne et France, 2021-2025), réduite aux champs lus. Rebasée sur 2021,
 * elle doit redonner les chiffres du dossier : France 115,6 et Allemagne 120,8 en 2025.
 *
 * La parité complète avec le script Python — 26 indicateurs, 1 756 points, aucun écart —
 * a été vérifiée le 26/09/2026 en lançant les deux le même jour.
 */
function reponseIpch(array $valeurs = []): array
{
    return [
        'version' => '2.0', 'class' => 'dataset',
        'value' => $valeurs + ['0' => 109.2, '1' => 118.7, '2' => 125.9, '3' => 129.0, '4' => 131.9,
            '5' => 107.68, '6' => 114.04, '7' => 120.5, '8' => 123.29, '9' => 124.43],
        'id' => ['freq', 'unit', 'coicop', 'geo', 'time'],
        'size' => [1, 1, 1, 2, 5],
        'dimension' => [
            'freq' => ['category' => ['index' => ['A' => 0]]],
            'unit' => ['category' => ['index' => ['INX_A_AVG' => 0]]],
            'coicop' => ['category' => ['index' => ['CP00' => 0]]],
            'geo' => ['category' => ['index' => ['DE' => 0, 'FR' => 1]]],
            'time' => ['category' => ['index' => ['2021' => 0, '2022' => 1, '2023' => 2, '2024' => 3, '2025' => 4]]],
        ],
    ];
}

/** Réponse synthétique à une dimension géographique et deux années, avec statuts. */
function reponseSimple(array $valeurs, array $statuts = []): array
{
    return [
        'value' => $valeurs, 'status' => $statuts,
        'id' => ['unit', 'geo', 'time'], 'size' => [1, 2, 2],
        'dimension' => [
            'unit' => ['category' => ['index' => ['NR' => 0]]],
            'geo' => ['category' => ['index' => ['DE' => 0, 'FR' => 1]]],
            'time' => ['category' => ['index' => ['2024' => 0, '2025' => 1]]],
        ],
    ];
}

beforeEach(function () {
    config([
        'eurostat.panel' => ['FR', 'DE'],
        'eurostat.depuis' => 2021,
        'eurostat.indicateurs' => [
            ['code' => 'niveau_prix', 'fiche' => 'inflation-explose', 'titre' => 'Niveau des prix, base 100 en 2021', 'unite' => 'indice',
                'jeu' => 'prc_hicp_aind', 'filtres' => ['unit' => 'INX_A_AVG', 'coicop' => 'CP00'],
                'calcul' => ['type' => 'base100', 'annee' => 2021], 'note' => 'Rebasé.', 'pertinence' => 'haute'],
            ['code' => 'departs_pour_1000', 'fiche' => 'trop-d-immigration', 'titre' => 'Départs pour 1 000', 'unite' => '‰',
                'jeu' => 'migr_emi1ctz', 'filtres' => ['citizen' => 'NAT'],
                'calcul' => ['type' => 'ratio', 'facteur' => 1000, 'jeu' => 'migr_pop1ctz', 'filtres' => ['citizen' => 'TOTAL']],
                'note' => 'Mal mesuré.', 'pertinence' => 'moyenne'],
        ],
    ]);

    // Réponse modifiable en cours de test : un second Http::fake() s'ajouterait après
    // celui-ci, et la première règle qui correspond l'emporterait.
    $this->ipch = reponseIpch();
    Http::fake([
        '*prc_hicp_aind*' => fn () => Http::response($this->ipch),
        '*migr_emi1ctz*' => Http::response(reponseSimple(['0' => 180000, '1' => 181000, '2' => 183617, '3' => 190000], ['1' => 'p'])),
        '*migr_pop1ctz*' => Http::response(reponseSimple(['0' => 83000000, '1' => 83500000, '2' => 68300000, '3' => 68500000], ['2' => 'b'])),
    ]);
});

afterEach(function () {
    foreach ($this->archives ?? [] as $a) {
        File::delete($a);
    }
});

it('rebase un indice et retrouve les chiffres du dossier', function () {
    $r = app(ExtractionEurostat::class)->extraire();
    $prix = collect($r['indicateurs'])->firstWhere('id', 'niveau_prix');

    $fr = collect($prix['series']['FR'])->keyBy('annee');
    expect($fr[2021]['valeur'])->toBe(100.0)
        ->and($fr[2025]['valeur'])->toBe(115.6)
        ->and(collect($prix['series']['DE'])->last()['valeur'])->toBe(120.8);
});

it('calcule un ratio arrondi comme Python, fusionne les statuts et cite le dénominateur', function () {
    $r = app(ExtractionEurostat::class)->extraire();
    $departs = collect($r['indicateurs'])->firstWhere('id', 'departs_pour_1000');

    $fr = collect($departs['series']['FR'])->keyBy('annee');
    // 183617 / 68300000 × 1000 = 2,6884… ; statuts « b » (dénominateur) et rien → « b ».
    expect($fr[2024]['valeur'])->toBe(2.69)->and($fr[2024]['statut'])->toBe('b')
        // DE 2025 : numérateur « p ».
        ->and(collect($departs['series']['DE'])->keyBy('annee')[2025]['statut'])->toBe('p')
        ->and(collect($departs['sources'])->pluck('code')->all())->toBe(['migr_emi1ctz', 'migr_pop1ctz']);
});

it('refuse une réponse dont une dimension n\'est pas filtrée', function () {
    $d = reponseIpch();
    $d['size'][1] = 2;

    expect(fn () => app(ClientEurostat::class)->decoder('prc_hicp_aind', $d))
        ->toThrow(RuntimeException::class, 'dimension unit non filtrée');
});

it('ne change jamais la série publiée sans validation, et décrit la révision', function () {
    $extraction = app(ExtractionEurostat::class);

    // 1. Première extraction : tout est nouveau, rien n'est publié.
    $bilan = $extraction->enregistrer($extraction->extraire());
    $this->archives[] = $bilan['archive'];
    expect($bilan['nouveaux'])->toBe(2)
        ->and(File::exists($bilan['archive']))->toBeTrue();
    $prix = EurostatIndicateur::where('code', 'niveau_prix')->sole();
    expect($prix->etat())->toBe('nouveau')->and($prix->series_publiees)->toBeNull();

    // 2. Validation : la série détectée devient celle du site.
    $extraction->valider($prix, User::factory()->create());
    $prix->refresh();
    expect($prix->etat())->toBe('a_jour')
        ->and(collect($prix->series_publiees['FR'])->last()['valeur'])->toBe(115.6);

    // 3. Eurostat révise la France 2025 : la série publiée ne bouge pas, la révision attend.
    $this->ipch = reponseIpch(['9' => 125.0]);
    $bilan = app(ExtractionEurostat::class)->enregistrer(app(ExtractionEurostat::class)->extraire());
    $this->archives[] = $bilan['archive'];
    $prix->refresh();

    expect($prix->etat())->toBe('revision')
        ->and(collect($prix->series_publiees['FR'])->last()['valeur'])->toBe(115.6)
        ->and($prix->diff['revisions'])->toBe([['pays' => 'FR', 'annee' => 2025, 'avant' => 115.6, 'apres' => 116.1]]);
});

it('n\'enregistre rien en simulation', function () {
    $this->artisan('presidentielle:eurostat-extraire', ['--dry-run' => true])->assertSuccessful();

    expect(EurostatIndicateur::count())->toBe(0);
});
