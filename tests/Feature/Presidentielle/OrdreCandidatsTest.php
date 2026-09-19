<?php

use App\Models\CandidatPresidentielle;
use App\Models\PersonnePolitique;
use App\Services\Presidentielle\PresidentielleExporter;

/**
 * Ordre alphabétique des candidats.
 *
 * La page /candidats annonce « ordre alphabétique » : c'est une promesse de traitement
 * égal, pas une commodité d'affichage. Elle triait en réalité sur `nom_complet`, donc par
 * PRÉNOM — et cette chaîne porte la civilité, si bien que « M. » et « Mme » formaient
 * deux blocs et que les candidates préfixées se retrouvaient groupées.
 *
 * L'export doit donc livrer nom et prénom séparément : `nom_complet` ne permet pas de
 * retrouver le nom de famille de façon fiable (« Dominique de Villepin », « Nicolas
 * Dupont-Aignan »).
 */
function candidatNomme(string $prenom, string $nom): CandidatPresidentielle
{
    $personne = PersonnePolitique::factory()->create([
        'prenom' => $prenom, 'nom' => $nom,
        'slug' => \Illuminate\Support\Str::slug("{$prenom} {$nom}"),
    ]);

    return CandidatPresidentielle::factory()->create([
        'personne_politique_id' => $personne->id,
        'election' => '2027', 'statut_validation' => 'valide', 'affiche_publiquement' => true,
    ]);
}

it('livre le nom et le prénom séparément dans l\'index', function () {
    candidatNomme('Éric', 'Zemmour');

    $exporter = app(PresidentielleExporter::class);
    $dir = storage_path('app/testing/ordre');
    $exporter->write($exporter->build('2027'), $dir);

    $index = json_decode(file_get_contents("$dir/candidats.json"), true);
    $z = collect($index)->firstWhere('slug', 'eric-zemmour');

    expect($z)->toHaveKeys(['nom', 'prenom'])
        ->and($z['nom'])->toBe('Zemmour')
        ->and($z['prenom'])->toBe('Éric');

    \Illuminate\Support\Facades\File::deleteDirectory($dir);
});

it('livre aussi nom et prénom sur la fiche complète', function () {
    candidatNomme('Marine', 'Tondelier');

    $fiche = app(PresidentielleExporter::class)->build('2027')['candidats']['marine-tondelier'];

    expect($fiche['nom'])->toBe('Tondelier')->and($fiche['prenom'])->toBe('Marine');
});

it('classe le vivier du jeu par nom, pas par prénom', function () {
    // Par prénom, Éric passerait avant Nathalie ; par nom, Zemmour vient après Arthaud.
    foreach ([['Éric', 'Zemmour'], ['Nathalie', 'Arthaud']] as [$p, $n]) {
        candidatAvecCitationsNommees($p, $n, 12);
    }

    $noms = collect(app(PresidentielleExporter::class)->build('2027')['jeu']['candidats'])->pluck('nom');

    expect($noms->first())->toBe('Nathalie Arthaud')
        ->and($noms->last())->toBe('Éric Zemmour');
});

/** Un candidat doté d'assez de citations jouables pour entrer dans le vivier du jeu. */
function candidatAvecCitationsNommees(string $prenom, string $nom, int $combien): void
{
    $candidat = candidatNomme($prenom, $nom);
    $theme = \App\Models\ProgrammeTheme::factory()->create();
    $doc = \App\Models\IngestionDocument::create(['type' => 'video', 'titre' => 'Meeting', 'statut' => 'extrait']);

    for ($i = 0; $i < $combien; $i++) {
        $mesure = \App\Models\ProgrammeMesure::factory()->create([
            'candidat_id' => $candidat->id, 'theme_id' => $theme->id,
            'statut_validation' => 'valide', 'affiche_publiquement' => true,
            'source_officielle_url' => 'https://exemple.fr/programme',
            'titre' => 'Propose une mesure qui ne nomme personne.',
        ]);
        \App\Models\IngestionProposition::create([
            'document_id' => $doc->id, 'candidat_id' => $candidat->id, 'theme_id' => $theme->id,
            'mesure_id' => $mesure->id, 'type' => 'mesure',
            'resume_propose' => 'Propose une mesure qui ne nomme personne.',
            'citation_verbatim' => "Nous ferons la mesure numéro {$i} dès le début du mandat, "
                .'et nous la financerons sans créer de nouvel impôt.',
            'statut' => 'rattachee', 'confiance' => 0.9,
        ]);
    }
}
