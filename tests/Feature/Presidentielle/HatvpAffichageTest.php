<?php

use App\Models\CandidatPresidentielle;
use App\Models\HatvpDeclaration;
use App\Models\HatvpMandatElectif;
use App\Models\ParcoursEvenement;
use App\Models\PersonnePolitique;
use App\Services\Presidentielle\PresidentielleExporter;
use Illuminate\Support\Facades\Artisan;

function declHatvpLiee(int $personneId, array $o = []): HatvpDeclaration
{
    return HatvpDeclaration::create(array_merge([
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'personne_politique_id' => $personneId,
        'date_depot' => now()->subMonth(),
        'type_declaration' => 'DIA',
        'nom' => 'NOM', 'prenom' => 'Prenom',
    ], $o));
}

it('exporte le bloc HATVP seulement quand le rattachement est validé (lie)', function () {
    [$candidat] = candidatPubliePublic();
    declHatvpLiee($candidat->personne_politique_id);
    $candidat->update(['hatvp_statut' => 'lie']);

    $c = app(PresidentielleExporter::class)->build('2027')['candidats'][$candidat->personnePolitique->slug];

    expect($c['hatvp']['statut'])->toBe('lie')
        ->and($c['hatvp']['summary'])->not->toBeNull()
        ->and($c['hatvp']['summary'])->toHaveKey('revenus_par_annee');
});

it('n\'expose que l\'état honnête quand le HATVP n\'est pas validé', function () {
    [$candidat] = candidatPubliePublic();
    $candidat->update(['hatvp_statut' => 'non_soumis']);

    $c = app(PresidentielleExporter::class)->build('2027')['candidats'][$candidat->personnePolitique->slug];

    expect($c['hatvp']['statut'])->toBe('non_soumis')
        ->and($c['hatvp'])->not->toHaveKey('summary');
});

it('enrichit le parcours depuis la déclaration HATVP rattachée (source hatvp, detecte)', function () {
    $personne = PersonnePolitique::factory()->create(['slug' => 'cand-'.uniqid()]);
    CandidatPresidentielle::factory()->create(['personne_politique_id' => $personne->id, 'election' => '2027']);
    $decl = declHatvpLiee($personne->id);
    HatvpMandatElectif::create([
        'declaration_id' => $decl->id, 'description' => 'Conseiller régional', 'conservee' => true,
    ]);

    Artisan::call('presidentielle:import-parcours', ['--candidat' => $personne->slug]);

    $ev = ParcoursEvenement::where('personne_politique_id', $personne->id)
        ->where('source_detection', 'hatvp')->first();

    expect($ev)->not->toBeNull()
        ->and($ev->type)->toBe('mandat')
        ->and($ev->titre)->toBe('Conseiller régional')
        ->and($ev->statut_validation)->toBe('detecte')
        ->and($ev->source_url)->not->toBeNull();
});
