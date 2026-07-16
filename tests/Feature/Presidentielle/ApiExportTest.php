<?php

use App\Models\Argument;
use App\Models\ArgumentSource;
use App\Models\CandidatPresidentielle;
use App\Models\PersonnePolitique;
use App\Models\ProgrammeMesure;
use App\Models\ProgrammeTheme;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Services\Presidentielle\IntegriteChecker;
use App\Services\Presidentielle\PresidentielleExporter;
use Database\Seeders\ProgrammeThemesSeeder;
use Illuminate\Support\Facades\Artisan;

// helper candidatPubliePublic() défini dans tests/Pest.php

it('n’exporte que le contenu publié', function () {
    [$candidat, $theme] = candidatPubliePublic();
    // un candidat non publié ne doit pas apparaître
    CandidatPresidentielle::factory()->create();

    $data = app(PresidentielleExporter::class)->build('2027');

    expect($data['meta']['nb_candidats'])->toBe(1)
        ->and($data['candidats'])->toHaveKey($candidat->personnePolitique->slug);
});

it('produit un content_hash déterministe (export reproductible)', function () {
    candidatPubliePublic();
    $exporter = app(PresidentielleExporter::class);

    expect($exporter->build('2027')['meta']['content_hash'])
        ->toBe($exporter->build('2027')['meta']['content_hash']);
});

it('écrit les fichiers JSON attendus', function () {
    [$candidat] = candidatPubliePublic();
    $dir = sys_get_temp_dir().'/export_'.uniqid();

    $exporter = app(PresidentielleExporter::class);
    $exporter->write($exporter->build('2027'), $dir);

    expect(file_exists("$dir/meta.json"))->toBeTrue()
        ->and(file_exists("$dir/themes.json"))->toBeTrue()
        ->and(file_exists("$dir/comparateur.json"))->toBeTrue()
        ->and(file_exists("$dir/candidats.json"))->toBeTrue()
        ->and(file_exists("$dir/candidats/{$candidat->personnePolitique->slug}.json"))->toBeTrue();
});

it('check-integrite bloque une mesure publiée sans contre-argument', function () {
    [$candidat, $theme] = candidatPubliePublic();
    // mesure publiée avec seulement un "pour" -> viole la symétrie
    $mesure = ProgrammeMesure::factory()->publie()->create([
        'candidat_id' => $candidat->id, 'theme_id' => $theme->id,
        'source_officielle_url' => 'https://exemple.fr/#m2',
    ]);
    lierArgumentPublie($mesure, 'pour'); // uniquement un « pour »

    $resultat = app(IntegriteChecker::class)->analyser('2027');
    $types = array_column($resultat['violations'], 'type');

    expect($types)->toContain('mesure_sans_contre');
    expect(Artisan::call('presidentielle:check-integrite'))->toBe(1); // échec
});

it('check-integrite passe pour un candidat complet et conforme', function () {
    candidatPubliePublic();

    expect(Artisan::call('presidentielle:check-integrite'))->toBe(0);
});

it('la commande export refuse en cas de violation, sauf --force', function () {
    [$candidat, $theme] = candidatPubliePublic();
    $mesure = ProgrammeMesure::factory()->publie()->create([
        'candidat_id' => $candidat->id, 'theme_id' => $theme->id, 'source_officielle_url' => 'https://x.fr/#m',
    ]);
    lierArgumentPublie($mesure, 'pour'); // pas de contre

    $dir = sys_get_temp_dir().'/exp_'.uniqid();
    expect(Artisan::call('presidentielle:export', ['--path' => $dir]))->toBe(1)
        ->and(file_exists("$dir/meta.json"))->toBeFalse();

    expect(Artisan::call('presidentielle:export', ['--path' => $dir, '--force' => true]))->toBe(0)
        ->and(file_exists("$dir/meta.json"))->toBeTrue();
});

it('calcule les états par thème : publie / en_traitement / non_exprime', function () {
    [$candidat, $themePublie] = candidatPubliePublic();
    (new ProgrammeThemesSeeder())->run();

    // Signal "en traitement" : une proposition sur l'éducation, sans mesure publiée.
    $edu = \App\Models\ProgrammeTheme::where('slug', 'education')->first();
    $doc = IngestionDocument::create(['type' => 'video', 'titre' => 'M', 'statut' => 'extrait']);
    IngestionProposition::create([
        'document_id' => $doc->id, 'candidat_id' => $candidat->id, 'theme_id' => $edu->id,
        'type' => 'mesure', 'resume_propose' => 'x', 'citation_verbatim' => 'y', 'statut' => 'detecte',
    ]);

    $etats = app(PresidentielleExporter::class)->build('2027')['candidats'][$candidat->personnePolitique->slug]['etats_par_theme'];

    expect($etats[$themePublie->slug]['etat'])->toBe('publie')
        ->and($etats['education']['etat'])->toBe('en_traitement')
        ->and($etats['sante']['etat'])->toBe('non_exprime');
});

it('expose l état « relevée » pour une mesure validée sans argumentaire publié', function () {
    [$candidat, $themePublie] = candidatPubliePublic();
    // mesure validée NON publiée sur un autre thème -> relevée
    $autre = \App\Models\ProgrammeTheme::factory()->create(['slug' => 'releve-'.uniqid(), 'actif' => true]);
    \App\Models\ProgrammeMesure::factory()->create([
        'candidat_id' => $candidat->id, 'theme_id' => $autre->id,
        'statut_validation' => 'valide', 'affiche_publiquement' => false,
        'source_officielle_url' => 'https://exemple.fr/#r',
    ]);

    $data = app(PresidentielleExporter::class)->build('2027');
    $c = $data['candidats'][$candidat->personnePolitique->slug];

    expect($c['etats_par_theme'][$autre->slug]['etat'])->toBe('relevee')
        ->and($c['mesures_relevees_par_theme'][$autre->slug])->toHaveCount(1)
        // le comparateur reste strict : uniquement les publiées
        ->and($data['comparateur'][$autre->slug][$candidat->personnePolitique->slug] ?? [])->toHaveCount(0);
});
