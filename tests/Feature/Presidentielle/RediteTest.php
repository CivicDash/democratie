<?php

use App\Exceptions\ModerationException;
use App\Models\CandidatPresidentielle;
use App\Models\IngestionDocument;
use App\Models\IngestionProposition;
use App\Models\PresidentielleModerationLog;
use App\Models\ProgrammeMesure;
use App\Models\User;
use App\Services\Presidentielle\ModerationService;
use App\Services\Presidentielle\PresidentielleExporter;
use Spatie\Permission\Models\Permission;

/**
 * Redites : une proposition qui reprend une mesure déjà au comparateur lui est rattachée.
 * La mesure gagne une prise de parole au lieu d'être créée deux fois ; à partir de
 * PresidentielleExporter::SEUIL_RECURRENCE prises de parole, elle est « récurrente ».
 */
function moderateurRedites(): User
{
    Permission::findOrCreate('moderer_presidentielle', 'web');
    $u = User::factory()->create();
    $u->givePermissionTo('moderer_presidentielle');

    return $u;
}

function rediteDe(ProgrammeMesure $mesure, string $date, array $o = []): IngestionProposition
{
    $doc = IngestionDocument::create(['type' => 'video', 'titre' => "Meeting du {$date}", 'statut' => 'extrait',
        'date_publication' => $date, 'url' => 'https://www.youtube.com/watch?v=test'.uniqid()]);

    return IngestionProposition::create(array_merge([
        'document_id' => $doc->id, 'candidat_id' => $mesure->candidat_id, 'theme_id' => $mesure->theme_id,
        'type' => 'mesure', 'resume_propose' => 'Reprend la mesure.', 'citation_verbatim' => 'je le redis ici',
        'timestamp_ou_paragraphe' => '00:10:00', 'statut' => 'detecte', 'confiance' => 0.9,
        'verbatim_verifie' => true, 'redite_de_mesure_id' => $mesure->id,
    ], $o));
}

it('rattache une redite à sa mesure, sans créer de mesure, et le trace au journal', function () {
    [, , $mesure] = candidatPubliePublic();
    $p = rediteDe($mesure, '2026-10-01');
    $avant = ProgrammeMesure::count();

    $this->actingAs(moderateurRedites())
        ->post(route('admin.presidentielle.propositions.action'), ['id' => $p->id, 'action' => 'rattacher'])
        ->assertRedirect()->assertSessionHasNoErrors();

    expect($p->fresh()->statut)->toBe('rattachee')
        ->and($p->fresh()->mesure_id)->toBe($mesure->id)
        ->and(ProgrammeMesure::count())->toBe($avant)
        ->and(PresidentielleModerationLog::where('action', 'rattachement_redite')->count())->toBe(1);
});

it('refuse de rattacher à la mesure d\'un autre candidat, ou une citation non confirmée', function () {
    [, , $mesure] = candidatPubliePublic();
    $autre = CandidatPresidentielle::factory()->create();
    $service = app(ModerationService::class);
    $user = moderateurRedites();

    $etranger = rediteDe($mesure, '2026-10-01', ['candidat_id' => $autre->id]);
    expect(fn () => $service->rattacherAMesure($etranger, $mesure, $user))->toThrow(ModerationException::class);

    $douteuse = rediteDe($mesure, '2026-10-02', ['verbatim_verifie' => false]);
    expect(fn () => $service->rattacherAMesure($douteuse, $mesure, $user))->toThrow(ModerationException::class)
        ->and($etranger->fresh()->statut)->toBe('detecte')
        ->and($douteuse->fresh()->statut)->toBe('detecte');
});

it('« Valider tout » rattache les redites au lieu de dupliquer la mesure', function () {
    [, , $mesure] = candidatPubliePublic();
    $p = rediteDe($mesure, '2026-10-01');
    $nouvelle = IngestionProposition::create([
        'document_id' => $p->document_id, 'candidat_id' => $mesure->candidat_id, 'theme_id' => $mesure->theme_id,
        'type' => 'mesure', 'resume_propose' => 'Une vraie nouveauté.', 'citation_verbatim' => 'nouveau',
        'statut' => 'detecte', 'confiance' => 0.9, 'verbatim_verifie' => true,
    ]);
    $avant = ProgrammeMesure::count();

    $this->actingAs(moderateurRedites())
        ->post(route('admin.presidentielle.documents.valider-tout', $p->document_id), ['mode' => 'mesures'])
        ->assertRedirect()->assertSessionHasNoErrors();

    expect($p->fresh()->mesure_id)->toBe($mesure->id)
        ->and($nouvelle->fresh()->statut)->toBe('rattachee')
        ->and(ProgrammeMesure::count())->toBe($avant + 1);
});

it('compte les prises de parole distinctes d\'une mesure et la signale récurrente au seuil', function () {
    [$candidat, $theme, $mesure] = candidatPubliePublic();
    $service = app(ModerationService::class);
    $user = moderateurRedites();
    $exporter = fn () => collect(app(PresidentielleExporter::class)->build('2027')['candidats'][$candidat->personnePolitique->slug]['mesures_par_theme'][$theme->slug])
        ->firstWhere('titre', $mesure->titre)['prises_de_parole'];

    foreach (['2026-09-12', '2026-10-28'] as $date) {
        $service->rattacherAMesure(rediteDe($mesure, $date), $mesure, $user);
    }
    expect($exporter())->toBe(['nombre' => 2, 'derniere' => '2026-10-28', 'recurrente' => false]);

    // Deux citations du même document ne font qu'une prise de parole.
    $troisieme = rediteDe($mesure, '2026-10-14');
    $service->rattacherAMesure($troisieme, $mesure, $user);
    $service->rattacherAMesure(rediteDe($mesure, '2026-10-14', ['document_id' => $troisieme->document_id]), $mesure, $user);

    expect($exporter())->toBe(['nombre' => 3, 'derniere' => '2026-10-28', 'recurrente' => true])
        ->and(PresidentielleExporter::SEUIL_RECURRENCE)->toBe(3);
});

it('lit « redite_de_mesure » à l\'import, et ignore le lien vers la mesure d\'un autre candidat', function () {
    (new \Database\Seeders\ProgrammeThemesSeeder())->run();
    (new \Database\Seeders\CandidatsPresidentielle2027Seeder())->run();
    $melenchon = CandidatPresidentielle::whereHas('personnePolitique', fn ($q) => $q->where('slug', 'jean-luc-melenchon'))->firstOrFail();
    $autre = CandidatPresidentielle::where('id', '!=', $melenchon->id)->firstOrFail();
    $sienne = ProgrammeMesure::factory()->create(['candidat_id' => $melenchon->id]);
    $etrangere = ProgrammeMesure::factory()->create(['candidat_id' => $autre->id]);
    $base = ['candidat_slug' => 'jean-luc-melenchon', 'theme_slug' => 'fiscalite-budget', 'type' => 'mesure',
        'resume_propose' => 'Une redite.', 'citation_verbatim' => 'une citation de test'];

    $chemin = jsonTemporaire([
        'contrat' => 'presidentielle.propositions.v1',
        'source' => ['titre' => 'Meeting de test', 'type' => 'meeting', 'url' => 'https://www.youtube.com/watch?v=abcdefghijk', 'date' => '2026-10-28'],
        'propositions' => [$base + ['redite_de_mesure' => $sienne->id], $base + ['redite_de_mesure' => $etrangere->id]],
    ]);
    expect(\Illuminate\Support\Facades\Artisan::call('presidentielle:import-propositions', ['fichier' => $chemin]))->toBe(0);

    expect(IngestionProposition::orderBy('id')->pluck('redite_de_mesure_id')->all())->toBe([$sienne->id, null])
        ->and(\Illuminate\Support\Facades\Artisan::output())->toContain('lien ignoré');
});
