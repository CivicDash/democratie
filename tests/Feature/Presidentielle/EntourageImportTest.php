<?php

use App\Models\CadrageBudgetaire;
use App\Models\CandidatPresidentielle;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Models\PersonnePolitique;
use Database\Seeders\CandidatsPresidentielle2027Seeder;
use Database\Seeders\ProgrammeThemesSeeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Qui parle, à l'import : le locuteur réel de chaque proposition, sa qualité (équipe de
 * campagne ou parti), et le porteur du document. Une valeur inconnue fait échouer l'import
 * sans rien écrire — classer la parole d'un parti comme celle du candidat serait la pire
 * erreur.
 */
beforeEach(function () {
    (new ProgrammeThemesSeeder())->run();
    (new CandidatsPresidentielle2027Seeder())->run();
});

function importLocuteurs(array $propositions, array $source = []): array
{
    $chemin = jsonTemporaire([
        'contrat' => 'presidentielle.propositions.v1',
        'source' => $source + ['titre' => 'Conférence de test', 'type' => 'conference', 'url' => 'https://www.youtube.com/watch?v=abcdefghijk', 'date' => '2026-10-14'],
        'propositions' => $propositions,
    ]);
    $code = Artisan::call('presidentielle:import-propositions', ['fichier' => $chemin]);

    return [$code, Artisan::output()];
}

function propositionJson(array $overrides = []): array
{
    return $overrides + [
        'candidat_slug' => 'jean-luc-melenchon', 'theme_slug' => 'fiscalite-budget', 'type' => 'mesure',
        'resume_propose' => 'Une mesure.', 'citation_verbatim' => 'une citation de test',
    ];
}

it('range le locuteur et son rôle dans leurs colonnes, et laisse le candidat sans locuteur', function () {
    [$code] = importLocuteurs([
        propositionJson(['locuteur' => 'Éric Coquerel', 'role_locuteur' => 'député, président de la commission des finances']),
        propositionJson(),
        propositionJson(['locuteur' => 'Jean-Luc Mélenchon', 'role_locuteur' => 'candidat']),
    ]);

    expect($code)->toBe(0);
    [$coquerel, $sansCle, $candidat] = IngestionProposition::orderBy('id')->get()->all();

    expect($coquerel->locuteur)->toBe('Éric Coquerel')
        ->and($coquerel->role_locuteur)->toBe('député, président de la commission des finances')
        // Document du candidat, pas de qualité donnée : l'équipe de campagne.
        ->and($coquerel->qualite_locuteur)->toBe('equipe_campagne')
        ->and($sansCle->locuteur)->toBeNull()
        ->and($sansCle->qualite_locuteur)->toBeNull()
        // « Jean-Luc Mélenchon » est le candidat lui-même.
        ->and($candidat->locuteur)->toBeNull()
        ->and($candidat->role_locuteur)->toBeNull()
        ->and($candidat->qualite_locuteur)->toBeNull();
});

it('respecte une qualité « parti » explicite, et la déduit d\'un document de groupe parlementaire', function () {
    importLocuteurs([propositionJson(['locuteur' => 'Paul Parti', 'role_locuteur' => 'président du parti', 'qualite_locuteur' => 'parti'])]);
    expect(IngestionProposition::sole()->qualite_locuteur)->toBe('parti');

    IngestionProposition::query()->delete();
    importLocuteurs(
        [propositionJson(['locuteur' => 'Jordan Exemple', 'role_locuteur' => 'président du groupe'])],
        ['porteur' => ['type' => 'groupe_parlementaire', 'libelle' => 'Groupe Exemple']],
    );
    expect(IngestionProposition::sole()->qualite_locuteur)->toBe('parti');
});

it('refuse une qualité de locuteur inconnue, sans rien écrire', function () {
    [$code, $sortie] = importLocuteurs([
        propositionJson(),
        propositionJson(['locuteur' => 'Quelqu\'un', 'role_locuteur' => 'x', 'qualite_locuteur' => 'soutien']),
    ]);

    expect($code)->toBe(1)
        ->and($sortie)->toContain('qualite_locuteur inconnue')
        ->and(IngestionProposition::count())->toBe(0)
        ->and(IngestionDocument::count())->toBe(0);
});

it('signale un locuteur sans rôle (importé, mais non publiable)', function () {
    [$code, $sortie] = importLocuteurs([propositionJson(['locuteur' => 'Éric Coquerel'])]);

    expect($code)->toBe(0)
        ->and($sortie)->toContain('sans role_locuteur')
        ->and(IngestionProposition::sole()->role_locuteur)->toBeNull();
});

it('stocke le bloc porteur sur le document', function () {
    importLocuteurs([propositionJson()], ['porteur' => [
        'type' => 'groupe_parlementaire',
        'libelle' => 'Groupe Rassemblement national à l\'Assemblée',
        'endossement' => ['url' => 'https://exemple.fr/endossement', 'date' => '2026-10-06', 'note' => 'Présenté en présence de la candidate.'],
    ]]);

    $doc = IngestionDocument::sole();
    expect($doc->porteur_type)->toBe('groupe_parlementaire')
        ->and($doc->porteur_libelle)->toBe('Groupe Rassemblement national à l\'Assemblée')
        ->and($doc->endossement_url)->toBe('https://exemple.fr/endossement')
        ->and($doc->endossement_date->toDateString())->toBe('2026-10-06')
        ->and($doc->endossement_note)->toBe('Présenté en présence de la candidate.')
        // Toutes ses propositions relèvent de « Autour du candidat ».
        ->and(IngestionProposition::sole()->estEntourage())->toBeTrue();
});

it('sans bloc porteur, le document est celui du candidat', function () {
    importLocuteurs([propositionJson()]);

    expect(IngestionDocument::sole()->porteur_type)->toBe('candidat');
});

it('refuse un porteur inconnu, sans rien écrire', function () {
    [$code, $sortie] = importLocuteurs([propositionJson()], ['porteur' => ['type' => 'association']]);

    expect($code)->toBe(1)
        ->and($sortie)->toContain('porteur inconnu')
        ->and(IngestionDocument::count())->toBe(0)
        ->and(IngestionProposition::count())->toBe(0);
});

it('ignore une URL d\'endossement provisoire, avec un avertissement', function () {
    [$code, $sortie] = importLocuteurs([propositionJson()], ['porteur' => [
        'type' => 'parti', 'libelle' => 'Parti Exemple', 'endossement' => ['url' => 'https://A_COMPLETER', 'date' => '2026-10-06', 'note' => 'n'],
    ]]);

    expect($code)->toBe(0)
        ->and($sortie)->toContain('endossement')
        ->and(IngestionDocument::sole()->endossement_url)->toBeNull();
});

it('--dry-run éprouve toutes les écritures, puis les annule', function () {
    $chemin = jsonTemporaire(['source' => ['titre' => 'x'], 'propositions' => [propositionJson(['locuteur' => 'Éric Coquerel', 'role_locuteur' => 'député'])]]);

    expect(Artisan::call('presidentielle:import-propositions', ['fichier' => $chemin, '--dry-run' => true]))->toBe(0)
        ->and(Artisan::output())->toContain('1 de l\'équipe de campagne')
        ->and(IngestionProposition::count())->toBe(0)
        ->and(IngestionDocument::count())->toBe(0);
});

/*
| Les quatre fichiers réels des conférences du 06/10 (Marine Le Pen) et du 14/10
| (Jean-Luc Mélenchon), passés en dry-run avec leur transcription et leur cadrage converti
| au contrat v1. Ignorés si le dossier d'ingestion n'est pas sur la machine.
*/
dataset('conferences reelles', [
    'Mélenchon, comptes publics (14/10)' => [
        'Jean Luc Melanchon/Conference comptes publics 20261014', 'import_lfi_comptes_publics.json', 'conference-lfi.transcription.txt',
        ['insérées (detecte) : 67', 'dont 58 de l\'équipe de campagne', '1 cadrage(s), 41 ligne(s)'],
    ],
    'Le Pen, plan financier (06/10)' => [
        'Marine Le Pen/Conference plan financier 20261006', 'import_rn_trajectoire.json', 'conference-rn.transcription.txt',
        ['insérées (detecte) : 80', 'dont 27 de l\'équipe de campagne', '2 cadrage(s), 67 ligne(s)'],
    ],
]);

it('lit entièrement les imports réels (propositions, locuteurs, cadrage) en dry-run', function (string $dossier, string $props, string $transcription, array $attendus) {
    $base = dirname(base_path()).'/'.$dossier;
    if (! is_file("{$base}/{$props}") || ! is_file("{$base}/cadrage_budgetaire.import.json")) {
        $this->markTestSkipped("Dossier d'ingestion absent : {$base}");
    }
    $personne = PersonnePolitique::firstOrCreate(['slug' => 'marine-le-pen'], ['civilite' => 'Mme', 'prenom' => 'Marine', 'nom' => 'Le Pen']);
    CandidatPresidentielle::firstOrCreate(['personne_politique_id' => $personne->id, 'election' => '2027'], ['statut_candidature' => 'declare']);

    $code = Artisan::call('presidentielle:import-propositions', [
        'fichier' => "{$base}/{$props}",
        '--source' => "{$base}/{$transcription}",
        '--cadrage' => "{$base}/cadrage_budgetaire.import.json",
        '--dry-run' => true,
    ]);
    $sortie = Artisan::output();

    expect($code)->toBe(0, $sortie)
        ->and($sortie)->toContain('Rejetées (verbatim absent) : 0')
        ->and($sortie)->toContain('Candidat non résolu : 0');
    foreach ($attendus as $a) {
        expect($sortie)->toContain($a);
    }
    expect(IngestionProposition::count())->toBe(0)
        ->and(CadrageBudgetaire::count())->toBe(0);
})->with('conferences reelles');
