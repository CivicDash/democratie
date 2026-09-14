<?php

use App\Models\BudgetAnnuel;
use App\Models\FranceBudgetRevenue;
use App\Models\FranceBudgetSpending;
use App\Models\FranceDemographics;
use App\Models\FranceEconomy;
use App\Models\FranceEducation;
use App\Models\FranceEmploymentDetailed;
use App\Models\FranceEnvironment;
use App\Models\FranceHealth;
use App\Models\FranceSecurity;
use App\Models\User;
use App\Support\ChampsStatistiques;

/**
 * Ce que personne ne vérifiait : que la saisie arrive en base.
 *
 * Les dix écrans affichaient « mises à jour » quoi qu'il arrive. Sur soixante-quatre
 * champs, dix-neuf seulement étaient écrits — et quatre écrans n'écrivaient rien du
 * tout. Un test qui se contente d'attendre une redirection 302 ne l'aurait jamais vu :
 * celui-ci relit la ligne.
 */
$ecrans = [
    'demographie' => [FranceDemographics::class, 'year'],
    'economie' => [FranceEconomy::class, 'year'],
    'budget' => [BudgetAnnuel::class, 'annee'],
    'recettes' => [FranceBudgetRevenue::class, 'year'],
    'depenses' => [FranceBudgetSpending::class, 'year'],
    'education' => [FranceEducation::class, 'year'],
    'sante' => [FranceHealth::class, 'year'],
    'environnement' => [FranceEnvironment::class, 'year'],
    'securite' => [FranceSecurity::class, 'year'],
    'emploi' => [FranceEmploymentDetailed::class, 'year'],
];

dataset('ecrans statistiques', array_map(
    fn (string $slug) => [$slug, ...$ecrans[$slug]],
    array_combine(array_keys($ecrans), array_keys($ecrans)),
));

it('écrit réellement en base ce que le formulaire envoie', function (string $slug, string $modele, string $cleAnnee) {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $annee = 2024;
    $champs = ChampsStatistiques::pour($modele);

    expect($champs)->not->toBeEmpty("L'écran « {$slug} » n'expose aucun champ éditable.");

    // Valeurs sentinelles : distinctes par champ, pour qu'un mélange de colonnes se voie.
    $envoi = [];
    foreach ($champs as $i => $champ) {
        $envoi[$champ['nom']] = $champ['type'] === 'texte'
            ? "source-test-{$i}"
            : $i + 11;
    }

    $this->actingAs($admin)
        ->put(route("admin.stats-france.{$slug}.update", $annee), $envoi)
        ->assertRedirect();

    $ligne = $modele::where($cleAnnee, $annee)->first();

    expect($ligne)->not->toBeNull("Aucune ligne écrite pour {$slug} en {$annee}.");

    $perdus = [];
    foreach ($envoi as $colonne => $attendu) {
        $obtenu = $ligne->getAttribute($colonne);
        if ($obtenu === null || (string) $obtenu !== (string) $attendu && (float) $obtenu !== (float) $attendu) {
            $perdus[] = $colonne;
        }
    }

    expect($perdus)->toBe([], sprintf(
        "Écran « %s » : %d champ(s) sur %d envoyés ne sont pas arrivés en base — %s",
        $slug, count($perdus), count($envoi), implode(', ', $perdus),
    ));
})->with('ecrans statistiques');
