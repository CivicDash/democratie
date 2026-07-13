<?php

use App\Models\IngestionProposition;
use Database\Seeders\CandidatsPresidentielle2027Seeder;
use Database\Seeders\ProgrammeThemesSeeder;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    (new ProgrammeThemesSeeder())->run();
    (new CandidatsPresidentielle2027Seeder())->run();
});

function ecrireJson(array $props): string
{
    $path = tempnam(sys_get_temp_dir(), 'props_').'.json';
    file_put_contents($path, json_encode([
        'contrat_version' => '1.0',
        'generateur' => 'test',
        'document_source' => ['type' => 'video', 'titre' => 'Meeting test'],
        'propositions' => $props,
    ]));

    return $path;
}

it('importe les propositions en statut detecte et résout candidat + thème', function () {
    $json = ecrireJson([
        ['candidat_slug' => 'gabriel-attal', 'theme_slug' => 'education', 'type' => 'mesure',
            'resume_propose' => 'Moins de 20 élèves par classe', 'citation_verbatim' => 'moins de 20 élèves par classe'],
    ]);

    Artisan::call('presidentielle:import-propositions', ['fichier' => $json]);

    $prop = IngestionProposition::sole();
    expect($prop->statut)->toBe('detecte')
        ->and($prop->candidat_id)->not->toBeNull()
        ->and($prop->theme_id)->not->toBeNull()
        ->and($prop->verbatim_verifie)->toBeFalse();  // pas de source fournie
});

it('vérifie la citation verbatim contre la source et rejette les citations absentes', function () {
    $source = tempnam(sys_get_temp_dir(), 'src_').'.txt';
    file_put_contents($source, "00:01:13 Et en primaire, moins de 20 élèves par classe comme ailleurs.");

    $json = ecrireJson([
        ['candidat_slug' => 'gabriel-attal', 'theme_slug' => 'education', 'type' => 'mesure',
            'resume_propose' => 'Classes réduites', 'citation_verbatim' => 'moins de 20 élèves par classe'],
        ['candidat_slug' => 'gabriel-attal', 'theme_slug' => 'education', 'type' => 'mesure',
            'resume_propose' => 'Hallucination', 'citation_verbatim' => 'gratuité totale des transports'],
    ]);

    Artisan::call('presidentielle:import-propositions', ['fichier' => $json, '--source' => $source]);

    // Seule la citation réellement présente dans la source est insérée, et marquée vérifiée
    expect(IngestionProposition::count())->toBe(1)
        ->and(IngestionProposition::sole()->verbatim_verifie)->toBeTrue();
});

it('ne publie jamais : tout arrive en file de modération', function () {
    $json = ecrireJson([
        ['candidat_slug' => 'jean-luc-melenchon', 'theme_slug' => 'ecologie-energie', 'type' => 'position',
            'resume_propose' => 'Planification écologique', 'citation_verbatim' => 'la bifurcation écologique'],
    ]);

    Artisan::call('presidentielle:import-propositions', ['fichier' => $json]);

    expect(IngestionProposition::where('statut', 'detecte')->count())->toBe(1)
        ->and(IngestionProposition::whereNot('statut', 'detecte')->count())->toBe(0);
});
