<?php

use App\Models\CandidatPresidentielle;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Models\PersonnePolitique;
use App\Models\ProgrammeMesure;
use App\Models\ProgrammeTheme;
use App\Services\Presidentielle\PresidentielleExporter;

/**
 * Vivier du jeu « Qui a dit quoi ? ».
 *
 * Deux garanties s'y jouent, et elles sont l'essentiel du dispositif :
 *  - on ne sert que des citations VERBATIM, jamais nos résumés de mesures — personne ne
 *    les a prononcés, et demander « qui a dit ça » à leur propos serait un contresens ;
 *  - le tirage est plafonné par candidat. À volume libre, le candidat le plus dépouillé
 *    pèserait un cinquième du corpus, et répondre son nom à chaque carte vaudrait le
 *    hasard : le jeu mesurerait notre travail, pas la connaissance du joueur.
 */
function candidatAvecCitations(string $nom, int $combien, int $longueur = 90): CandidatPresidentielle
{
    $personne = PersonnePolitique::factory()->create([
        'prenom' => 'Camille', 'nom' => $nom, 'slug' => \Illuminate\Support\Str::slug($nom),
    ]);
    $candidat = CandidatPresidentielle::factory()->create([
        'personne_politique_id' => $personne->id,
        'election' => '2027', 'statut_validation' => 'valide', 'affiche_publiquement' => true,
    ]);
    $theme = ProgrammeTheme::factory()->create();
    $doc = IngestionDocument::create(['type' => 'video', 'titre' => "Meeting {$nom}", 'statut' => 'extrait']);

    for ($i = 0; $i < $combien; $i++) {
        $mesure = ProgrammeMesure::factory()->create([
            'candidat_id' => $candidat->id, 'theme_id' => $theme->id,
            'statut_validation' => 'valide', 'affiche_publiquement' => true,
            'source_officielle_url' => 'https://exemple.fr/programme',
        ]);
        IngestionProposition::create([
            'document_id' => $doc->id, 'candidat_id' => $candidat->id, 'theme_id' => $theme->id,
            'mesure_id' => $mesure->id, 'type' => 'mesure',
            'resume_propose' => 'Résumé rédigé par nous, que personne n\'a prononcé.',
            'citation_verbatim' => str_pad("phrase {$i} de {$nom} ", $longueur, 'et je le ferai '),
            'statut' => 'rattachee', 'confiance' => 0.9,
        ]);
    }

    return $candidat;
}

function jeu(): array
{
    return app(PresidentielleExporter::class)->build('2027')['jeu'];
}

it('plafonne le nombre de citations par candidat', function () {
    candidatAvecCitations('Prolixe', 30);

    $parCandidat = collect(jeu()['citations'])->countBy('candidat');

    expect($parCandidat['prolixe'])->toBe(12);
});

it('écarte un candidat qui n\'a pas assez de matière', function () {
    candidatAvecCitations('Prolixe', 20);
    candidatAvecCitations('Discret', 3);   // sous le seuil de 8

    $j = jeu();

    expect(collect($j['candidats'])->pluck('slug'))->toContain('prolixe')->not->toContain('discret')
        ->and(collect($j['citations'])->pluck('candidat')->unique())->not->toContain('discret');
});

it('fait coïncider le vivier de réponses et celui des auteurs', function () {
    candidatAvecCitations('Alpha', 15);
    candidatAvecCitations('Beta', 15);

    $j = jeu();
    $slugs = collect($j['candidats'])->pluck('slug');
    $auteurs = collect($j['citations'])->pluck('candidat')->unique();

    // Un auteur absent du vivier serait une bonne réponse impossible à choisir.
    expect($auteurs->diff($slugs))->toBeEmpty()->and($slugs->diff($auteurs))->toBeEmpty();
});

it('sert le verbatim et jamais notre résumé', function () {
    candidatAvecCitations('Alpha', 10);

    foreach (jeu()['citations'] as $c) {
        expect($c['texte'])->not->toContain('Résumé rédigé par nous')
            ->and(mb_strlen($c['texte']))->toBeGreaterThanOrEqual(40)
            ->and(mb_strlen($c['texte']))->toBeLessThanOrEqual(180);
    }
});

it('écarte les citations trop courtes ou trop longues', function () {
    candidatAvecCitations('Alpha', 10);          // longueur jouable
    candidatAvecCitations('Bavard', 10, 400);    // au-delà de 180

    expect(collect(jeu()['candidats'])->pluck('slug'))->not->toContain('bavard');
});

it('compose le nom sans civilité', function () {
    candidatAvecCitations('Alpha', 10);

    // L'accesseur `nom_complet` préfixe d'une civilité (« M. Bruno Retailleau ») que tout
    // le reste du front retire ensuite. On compose donc directement prénom + nom.
    expect(collect(jeu()['candidats'])->pluck('nom'))->toContain('Camille Alpha');
});

it('pointe le lien à la seconde quand le repérage est un timecode', function () {
    $candidat = candidatAvecCitations('Alpha', 10);
    $doc = \App\Models\IngestionDocument::create([
        'type' => 'video', 'titre' => 'Débat', 'url' => 'https://www.youtube.com/watch?v=abc123',
        'statut' => 'extrait',
    ]);
    \App\Models\IngestionProposition::where('candidat_id', $candidat->id)->update([
        'document_id' => $doc->id, 'source_url' => null, 'timestamp_ou_paragraphe' => '01:57:01',
    ]);

    $url = collect(jeu()['citations'])->firstWhere('candidat', 'alpha')['source']['url'];

    // 1×3600 + 57×60 + 1 = 7021
    expect($url)->toBe('https://www.youtube.com/watch?v=abc123&t=7021s');
});

it('ne touche pas au lien quand le repérage n\'est pas un timecode', function () {
    $candidat = candidatAvecCitations('Alpha', 10);
    $doc = \App\Models\IngestionDocument::create([
        'type' => 'article', 'titre' => 'Discours publié',
        'url' => 'https://exemple.fr/discours', 'statut' => 'extrait',
    ]);
    \App\Models\IngestionProposition::where('candidat_id', $candidat->id)->update([
        'document_id' => $doc->id, 'source_url' => null,
        'timestamp_ou_paragraphe' => 'paragraphe « Alors, camarades »',
    ]);

    expect(collect(jeu()['citations'])->firstWhere('candidat', 'alpha')['source']['url'])
        ->toBe('https://exemple.fr/discours');
});
