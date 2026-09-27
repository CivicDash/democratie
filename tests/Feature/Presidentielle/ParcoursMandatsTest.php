<?php

use App\Models\CandidatPresidentielle;
use App\Models\ParcoursEvenement;
use App\Models\PersonnePolitique;
use Illuminate\Support\Facades\DB;

/**
 * La synchro du parcours tire des mandats DATÉS des tables CivicDash.
 *
 * Elle créait une seule ligne « Députée/Député à l'Assemblée nationale », sans date ni
 * circonscription : lue sur une page candidat, elle passait pour un mandat en cours.
 */
function deputeRattache(string $civilite = 'Mme'): PersonnePolitique
{
    DB::table('acteurs_an')->insert(['uid' => 'PA999001', 'civilite' => $civilite, 'prenom' => 'Test', 'nom' => 'Rattachée', 'created_at' => now(), 'updated_at' => now()]);
    $p = PersonnePolitique::factory()->create(['uid_an' => 'PA999001']);
    CandidatPresidentielle::factory()->create(['personne_politique_id' => $p->id, 'election' => '2027']);

    return $p;
}

function mandatAN(array $valeurs): void
{
    DB::table('deputes_circonscriptions')->insert($valeurs + [
        'acteur_uid' => 'PA999001', 'departement' => 'Haut-Rhin', 'num_departement' => '68',
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

it('crée un mandat daté par législature, avec sa circonscription', function () {
    $p = deputeRattache();
    mandatAN(['mandat_uid' => 'PM1', 'legislature' => 16, 'num_circo' => 1, 'date_debut' => '2022-06-22', 'date_fin' => '2024-06-09']);
    mandatAN(['mandat_uid' => 'PM2', 'legislature' => 17, 'num_circo' => 5, 'date_debut' => '2024-07-07', 'date_fin' => null]);

    $this->artisan('presidentielle:import-parcours', ['--candidat' => $p->slug])->assertSuccessful();

    $lignes = ParcoursEvenement::where('personne_politique_id', $p->id)->where('type', 'mandat')->orderBy('date_debut')->get();
    expect($lignes->pluck('titre')->all())->toBe(['Députée', 'Députée'])
        ->and($lignes->pluck('organisation')->all())->toBe([
            'Assemblée nationale — Haut-Rhin, 1re circonscription',
            'Assemblée nationale — Haut-Rhin, 5e circonscription',
        ])
        ->and($lignes[0]->date_fin->toDateString())->toBe('2024-06-09')
        ->and($lignes[1]->en_cours)->toBeTrue()
        ->and($lignes[1]->source_url)->toBe('https://www.assemblee-nationale.fr/dyn/deputes/PA999001')
        ->and($lignes->pluck('statut_validation')->unique()->all())->toBe(['detecte']);

    // Relancée, la synchro ne recrée rien.
    $this->artisan('presidentielle:import-parcours', ['--candidat' => $p->slug])->assertSuccessful();
    expect(ParcoursEvenement::where('personne_politique_id', $p->id)->count())->toBe(2);
});

it('ne crée la ligne générique sans date qu\'à défaut de mandat daté', function () {
    $p = deputeRattache('M.');

    $this->artisan('presidentielle:import-parcours', ['--candidat' => $p->slug])->assertSuccessful();

    expect(ParcoursEvenement::where('personne_politique_id', $p->id)->pluck('titre')->all())
        ->toBe(['Députée/Député à l\'Assemblée nationale']);
});
