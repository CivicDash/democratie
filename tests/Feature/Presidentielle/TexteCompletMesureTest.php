<?php

use App\Models\CandidatPresidentielle;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Models\ProgrammeMesure;
use App\Models\ProgrammeTheme;
use App\Models\User;
use App\Services\Presidentielle\ModerationService;
use App\Services\Presidentielle\PresidentielleExporter;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

/**
 * Le texte d'une mesure n'est jamais coupé : il est entier dans `resume`. Le titre, court,
 * s'arrête au dernier mot entier et le signale par « … ». Jusqu'au 03/11/2026, les deux
 * étaient coupés à 295 caractères au milieu d'un mot, sans marque (« … de 2005 sur le t »).
 */
function texteLongMesure(): string
{
    return 'Organiser dès la première année du mandat un référendum sur la réforme des institutions, '
        .'afin de rendre aux Français la parole confisquée depuis le référendum de 2005 sur le traité '
        .'constitutionnel européen, en inscrivant dans la Constitution le référendum d\'initiative '
        .'citoyenne, déclenché par cinq cent mille électeurs, sur tout sujet, y compris la révision '
        .'de la Constitution et la ratification des traités, avec un contrôle préalable du Conseil '
        .'constitutionnel limité au respect des droits fondamentaux.';
}

function moderateurTexteMesure(): User
{
    Permission::findOrCreate('moderer_presidentielle', 'web');
    $u = User::factory()->create();
    $u->givePermissionTo('moderer_presidentielle');

    return $u;
}

function propositionTexteMesure(string $resume): IngestionProposition
{
    $doc = IngestionDocument::create(['type' => 'video', 'titre' => 'Meeting test', 'statut' => 'extrait']);

    return IngestionProposition::create([
        'document_id' => $doc->id,
        'candidat_id' => CandidatPresidentielle::factory()->create()->id,
        'theme_id' => ProgrammeTheme::factory()->create()->id,
        'type' => 'mesure',
        'resume_propose' => $resume,
        'citation_verbatim' => 'je ferai ce référendum',
        'statut' => 'detecte',
        'confiance' => 0.9,
    ]);
}

it('garde le texte complet dans le résumé et coupe le titre au dernier mot entier, avec « … »', function () {
    $texte = texteLongMesure();
    // Le cas de production : la coupe à 295 tombait dans un mot.
    expect(mb_strlen($texte))->toBeGreaterThan(ProgrammeMesure::LONGUEUR_TITRE)
        ->and(Str::limit($texte, 295, ''))->toEndWith(' déclenché par c');

    $mesure = app(ModerationService::class)
        ->creerMesureDepuisProposition(propositionTexteMesure($texte), moderateurTexteMesure())
        ->fresh();

    $prefixe = mb_substr($mesure->titre, 0, -1);
    expect($mesure->resume)->toBe($texte)
        ->and(mb_strlen($mesure->titre))->toBeLessThanOrEqual(ProgrammeMesure::LONGUEUR_TITRE)
        ->and($mesure->titre)->toEndWith(' citoyenne, déclenché par cinq…')
        ->and(str_starts_with($texte, $prefixe))->toBeTrue()
        // Après le titre, le texte reprend sur une espace ou une ponctuation, jamais dans un mot.
        ->and(mb_substr($texte, mb_strlen($prefixe), 1))->not->toMatch('/\pL/u');
});

it('laisse intact un texte qui tient dans le titre', function () {
    $court = 'Instaurer le référendum d\'initiative citoyenne.';
    $mesure = app(ModerationService::class)
        ->creerMesureDepuisProposition(propositionTexteMesure($court), moderateurTexteMesure())
        ->fresh();

    expect($mesure->titre)->toBe($court)->and($mesure->resume)->toBe($court);

    $pile = str_repeat('a', ProgrammeMesure::LONGUEUR_TITRE);
    expect(ProgrammeMesure::titreDepuis($pile))->toBe($pile);
});

it('ne laisse ni ponctuation pendante ni mot coupé avant « … »', function () {
    $l = ProgrammeMesure::LONGUEUR_TITRE;

    // Coupe pile entre deux mots : rien n'est retiré.
    $texte = str_repeat('a', $l - 1).' suite';
    expect(ProgrammeMesure::titreDepuis($texte))->toBe(str_repeat('a', $l - 1).'…');

    // Le mot coupé part, la virgule qui le précédait aussi.
    $texte = str_repeat('a', $l - 10).', milliards d\'euros';
    expect(ProgrammeMesure::titreDepuis($texte))->toBe(str_repeat('a', $l - 10).'…');

    // Sans espace, on coupe quand même, et on le dit.
    $titre = ProgrammeMesure::titreDepuis(str_repeat('é', $l + 50));
    expect(mb_strlen($titre))->toBe($l)->and($titre)->toEndWith('é…');
});

it('exporte le texte complet des mesures publiées et des mesures relevées', function () {
    [$candidat, $theme, $publiee] = candidatPubliePublic();
    $texte = texteLongMesure();
    $publiee->update(['titre' => ProgrammeMesure::titreDepuis($texte), 'resume' => $texte]);
    ProgrammeMesure::factory()->create([
        'candidat_id' => $candidat->id, 'theme_id' => $theme->id,
        'statut_validation' => 'valide', 'affiche_publiquement' => false,
        'titre' => ProgrammeMesure::titreDepuis($texte.' Bis.'), 'resume' => $texte.' Bis.',
    ]);

    $export = app(PresidentielleExporter::class)->build('2027')['candidats'][$candidat->personnePolitique->slug];

    expect($export['mesures_par_theme'][$theme->slug][0]['resume'])->toBe($texte)
        ->and($export['mesures_relevees_par_theme'][$theme->slug][0]['resume'])->toBe($texte.' Bis.')
        ->and($export['mesures_relevees_par_theme'][$theme->slug][0]['titre'])->toEndWith('…');
});
