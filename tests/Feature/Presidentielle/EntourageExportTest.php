<?php

use App\Models\EvenementCampagne;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Models\ProgrammeDocument;
use App\Models\ProgrammeMesure;
use App\Models\ProgrammeTheme;
use App\Services\Presidentielle\IntegriteChecker;
use App\Services\Presidentielle\PresidentielleExporter;

/**
 * Ce qui part sur objectif2027.fr selon qui parle.
 *
 *  - seul le relu paraît : une proposition détectée n'est plus dans les prises de parole ;
 *  - l'équipe de campagne paraît du côté du candidat, son nom et son rôle sur chaque citation ;
 *  - la parole d'un parti ne paraît que dans « Autour du candidat » — jamais dans les prises
 *    de parole, les mesures, le comparateur, le jeu, la couverture ou le calendrier.
 */
function exportDe($candidat): array
{
    $data = app(PresidentielleExporter::class)->build('2027');

    return [$data, $data['candidats'][$candidat->personnePolitique->slug]];
}

function citationsExportees(array $c): array
{
    return collect($c['prises_de_parole'])->flatMap(fn ($d) => $d['citations'])->pluck('citation')->all();
}

it('ne publie plus les propositions détectées : seulement validées ou rattachées', function () {
    [$candidat] = candidatPubliePublic();
    propositionPour($candidat, ['citation_verbatim' => 'Phrase détectée jamais relue.']);
    propositionPour($candidat, ['citation_verbatim' => 'Phrase validée sans mesure.', 'statut' => 'validee']);
    propositionPour($candidat, ['citation_verbatim' => 'Phrase rattachée à une mesure.', 'statut' => 'rattachee']);
    propositionPour($candidat, ['citation_verbatim' => 'Phrase rejetée.', 'statut' => 'rejetee']);

    [, $c] = exportDe($candidat);

    expect(citationsExportees($c))->toEqualCanonicalizing(['Phrase validée sans mesure.', 'Phrase rattachée à une mesure.']);
});

it('attribue chaque citation de l\'équipe de campagne à son locuteur', function () {
    [$candidat] = candidatPubliePublic();
    propositionPour($candidat, parole('equipe_campagne', 'Éric Coquerel', 'député, président de la commission des finances') + ['statut' => 'validee']);
    propositionPour($candidat, ['statut' => 'validee']);

    [, $c] = exportDe($candidat);
    $citations = collect($c['prises_de_parole'])->flatMap(fn ($d) => $d['citations']);

    expect($citations)->toHaveCount(2)
        ->and($citations->firstWhere('locuteur', 'Éric Coquerel'))->toMatchArray([
            'role_locuteur' => 'député, président de la commission des finances',
            'qualite_locuteur' => 'equipe_campagne',
        ])
        ->and($citations->firstWhere('locuteur', null))->toMatchArray(['role_locuteur' => null, 'qualite_locuteur' => null])
        ->and($c['prises_de_parole'][0]['porteur'])->toBe(['type' => 'candidat', 'libelle' => null, 'endossement' => null]);
});

it('n\'affiche jamais la parole d\'un parti du côté du candidat, quel que soit son statut', function () {
    [$candidat, $theme] = candidatPubliePublic();
    foreach (['detecte', 'validee', 'rejetee'] as $statut) {
        propositionPour($candidat, parole('parti') + ['statut' => $statut, 'affiche_publiquement' => $statut === 'validee', 'citation_verbatim' => "Phrase du parti, {$statut}."]);
    }

    [$data, $c] = exportDe($candidat);

    expect(collect(citationsExportees($c))->filter(fn ($t) => str_contains($t, 'parti')))->toBeEmpty()
        ->and(json_encode($c['mesures_par_theme']).json_encode($c['mesures_relevees_par_theme']).json_encode($data['comparateur']))->not->toContain('Phrase du parti')
        ->and(json_encode($data['jeu']))->not->toContain('Phrase du parti')
        // Validée et publiée : elle est « Autour du candidat », et seulement là.
        ->and($c['autour_du_candidat']['prises_de_parole'][0]['citations'][0])->toMatchArray([
            'locuteur' => 'Paul Parti', 'role_locuteur' => 'président du parti', 'qualite_locuteur' => 'parti',
        ])
        ->and($c['autour_du_candidat']['personnalites'])->toBe([['nom' => 'Paul Parti', 'role' => 'président du parti', 'qualite' => 'parti', 'nb_citations' => 1]]);
});

it('n\'expose dans « Autour du candidat » que ce qui y est publié', function () {
    [$candidat] = candidatPubliePublic();
    propositionPour($candidat, parole('parti') + ['statut' => 'validee', 'affiche_publiquement' => false]);

    [, $c] = exportDe($candidat);

    expect($c['autour_du_candidat'])->toBe(['personnalites' => [], 'prises_de_parole' => [], 'documents' => []]);
});

it('range dans « Autour du candidat » tout un document de groupe, même ses propositions sans locuteur', function () {
    [$candidat] = candidatPubliePublic();
    $doc = IngestionDocument::factory()->porte('groupe_parlementaire')->create(['titre' => 'Contre-budget du groupe']);
    propositionPour($candidat, ['statut' => 'validee', 'affiche_publiquement' => true, 'citation_verbatim' => 'Phrase du groupe.'], $doc);

    [, $c] = exportDe($candidat);

    expect(citationsExportees($c))->toBe([])
        ->and($c['autour_du_candidat']['prises_de_parole'][0]['titre'])->toBe('Contre-budget du groupe')
        ->and($c['autour_du_candidat']['prises_de_parole'][0]['porteur'])->toMatchArray([
            'type' => 'groupe_parlementaire',
            'endossement' => ['url' => 'https://exemple.fr/endossement', 'date' => '2026-10-06', 'note' => 'Présenté le 06/10/2026 en présence du candidat.'],
        ])
        ->and($c['autour_du_candidat']['prises_de_parole'][0]['citations'][0]['locuteur'])->toBeNull()
        // Sans nom, personne à lister : le porteur tient lieu d'auteur.
        ->and($c['autour_du_candidat']['personnalites'])->toBe([]);
});

it('ne compte jamais l\'entourage dans la couverture, et pas davantage le non-relu', function () {
    [$candidat] = candidatPubliePublic();
    $avant = exportDe($candidat)[1]['couverture'];

    $themeParti = ProgrammeTheme::factory()->create(['slug' => 'theme-parti', 'actif' => true]);
    $themeDetecte = ProgrammeTheme::factory()->create(['slug' => 'theme-detecte', 'actif' => true]);
    $doc = IngestionDocument::factory()->create();
    IngestionProposition::factory()->create(parole('parti') + ['document_id' => $doc->id, 'candidat_id' => $candidat->id, 'theme_id' => $themeParti->id, 'statut' => 'validee', 'affiche_publiquement' => true]);
    IngestionProposition::factory()->create(['document_id' => $doc->id, 'candidat_id' => $candidat->id, 'theme_id' => $themeDetecte->id, 'statut' => 'detecte']);

    [, $c] = exportDe($candidat);

    expect($c['etats_par_theme']['theme-parti']['etat'])->toBe('non_exprime')
        ->and($c['etats_par_theme']['theme-detecte']['etat'])->toBe('non_exprime')
        ->and($c['couverture']['themes_exprimes'])->toBe($avant['themes_exprimes']);
});

it('ne compte au calendrier que le relu du candidat et de son équipe', function () {
    [$candidat] = candidatPubliePublic();
    $doc = IngestionDocument::factory()->create();
    propositionPour($candidat, ['statut' => 'rattachee'], $doc);
    propositionPour($candidat, parole('equipe_campagne', 'Éric Coquerel', 'député') + ['statut' => 'validee'], $doc);
    propositionPour($candidat, parole('parti') + ['statut' => 'validee', 'affiche_publiquement' => true], $doc);
    propositionPour($candidat, ['statut' => 'detecte'], $doc);

    $evt = EvenementCampagne::create([
        'election' => '2027', 'titre' => 'Conférence', 'type' => 'debat', 'date_debut' => '2026-10-14',
        'journee_entiere' => true, 'precision_date' => 'jour', 'statut' => 'confirme',
        'ingestion_document_id' => $doc->id, 'url_source' => 'https://exemple.fr/c',
        'statut_validation' => 'valide', 'affiche_publiquement' => true,
    ]);
    $evt->candidats()->attach($candidat->id, ['role' => 'intervenant']);

    $data = app(PresidentielleExporter::class)->build('2027');

    expect($data['calendrier']['evenements'][0]['nb_citations'])->toBe(2)
        ->and($data['calendrier']['evenements'][0]['candidats'][0]['nb_citations'])->toBe(2);
});

it('marque la mesure portée par l\'équipe de campagne, et attribue ses citations d\'appui', function () {
    $mod = moderateurPresidentielle();
    [$candidat] = candidatPubliePublic();
    $equipe = propositionPour($candidat, parole('equipe_campagne', 'Éric Coquerel', 'député'));
    $mesureEquipe = app(\App\Services\Presidentielle\ModerationService::class)->creerMesureDepuisProposition($equipe, $mod);
    $mesureEquipe->update(['statut_validation' => 'valide', 'affiche_publiquement' => true, 'source_officielle_url' => 'https://exemple.fr/m']);

    [$data, $c] = exportDe($candidat);
    $mesures = collect($c['mesures_par_theme'])->flatten(1);
    $exportee = $mesures->firstWhere('titre', $mesureEquipe->titre);
    $duCandidat = $mesures->reject(fn ($m) => $m['titre'] === $mesureEquipe->titre)->first();

    expect($exportee['porte_par'])->toBe(['qualite' => 'equipe_campagne', 'locuteurs' => [['nom' => 'Éric Coquerel', 'role' => 'député']]])
        ->and($exportee['citations'][0])->toMatchArray(['locuteur' => 'Éric Coquerel', 'role_locuteur' => 'député', 'qualite_locuteur' => 'equipe_campagne'])
        // La mesure de candidatPubliePublic() n'a pas de proposition source.
        ->and($duCandidat['porte_par'])->toBeNull()
        ->and($duCandidat['citations'])->toBe([])
        // Le comparateur reprend l'attribution.
        ->and(json_encode($data['comparateur'], JSON_UNESCAPED_UNICODE))->toContain('Éric Coquerel');
});

it('n\'admet au jeu que des phrases du candidat lui-même', function () {
    [$candidat, $theme] = candidatPubliePublic();
    $doc = IngestionDocument::factory()->create();
    // Assez de phrases du candidat pour franchir le seuil d'entrée du jeu (dix), plus quatre
    // de son équipe de campagne.
    for ($i = 1; $i <= 14; $i++) {
        $m = ProgrammeMesure::factory()->publie()->create(['candidat_id' => $candidat->id, 'theme_id' => $theme->id, 'titre' => "Mesure {$i}"]);
        IngestionProposition::factory()->create([
            'document_id' => $doc->id, 'candidat_id' => $candidat->id, 'theme_id' => $theme->id,
            'statut' => 'rattachee', 'mesure_id' => $m->id,
            'citation_verbatim' => "Nous ferons la mesure numéro {$i} dès le premier jour du quinquennat qui vient.",
        ] + ($i <= 4 ? parole('equipe_campagne', 'Éric Coquerel', 'député') : []));
    }

    $jeu = app(PresidentielleExporter::class)->build('2027')['jeu'];
    $textes = collect($jeu['citations'])->pluck('texte');

    expect($textes)->toHaveCount(10)
        ->and($textes->filter(fn ($t) => preg_match('/numéro [1-4] /', $t)))->toBeEmpty();
});

it('ne fait pas d\'un référentiel de parti le « programme complet » du candidat, et le range « Autour »', function () {
    [$candidat] = candidatPubliePublic();
    ProgrammeDocument::create([
        'candidat_id' => $candidat->id, 'titre' => 'Programme du parti', 'url' => 'https://parti.fr/programme',
        'structure' => [['numero' => 1, 'titre' => 'Ch. 1']], 'statut_validation' => 'valide', 'affiche_publiquement' => true,
        'porteur_type' => 'parti', 'porteur_libelle' => 'Parti Exemple',
        'endossement_url' => 'https://parti.fr/endossement', 'endossement_date' => '2026-09-01', 'endossement_note' => 'Programme adopté en congrès.',
    ]);

    [, $c] = exportDe($candidat);

    expect($c['programme_complet'])->toBeNull()
        ->and($c['autour_du_candidat']['documents'][0])->toMatchArray([
            'titre' => 'Programme du parti', 'nb_chapitres' => 1,
            'porteur' => ['type' => 'parti', 'libelle' => 'Parti Exemple', 'endossement' => ['url' => 'https://parti.fr/endossement', 'date' => '2026-09-01', 'note' => 'Programme adopté en congrès.']],
        ]);
});

it('garde un « programme complet » stable avec deux référentiels publiés', function () {
    [$candidat] = candidatPubliePublic();
    foreach (['Programme B', 'Programme A'] as $i => $titre) {
        ProgrammeDocument::create([
            'candidat_id' => $candidat->id, 'titre' => $titre, 'url' => "https://exemple.fr/p{$i}",
            'statut_validation' => 'valide', 'affiche_publiquement' => true,
        ]);
    }
    $exporter = app(PresidentielleExporter::class);
    $a = $exporter->build('2027');
    $b = $exporter->build('2027');

    expect($a['meta']['content_hash'])->toBe($b['meta']['content_hash'])
        ->and($a['candidats'][$candidat->personnePolitique->slug]['programme_complet']['titre'])->toBe('Programme B');
});

it('exporte la civilité, pour accorder « Autour de la candidate »', function () {
    [$candidat] = candidatPubliePublic();
    $candidat->personnePolitique->update(['civilite' => 'Mme']);

    expect(exportDe($candidat)[1]['civilite'])->toBe('Mme');
});

it('le contrôle d\'intégrité refuse une publication « Autour » devenue non conforme', function () {
    [$candidat] = candidatPubliePublic();
    $p = propositionPour($candidat, parole('parti') + ['statut' => 'validee', 'affiche_publiquement' => true]);
    expect(array_column(app(IntegriteChecker::class)->analyser()['violations'], 'type'))->not->toContain('entourage_impubliable');

    $p->update(['verbatim_verifie' => false]);

    expect(array_column(app(IntegriteChecker::class)->analyser()['violations'], 'type'))->toContain('entourage_impubliable');
});
