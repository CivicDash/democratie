<?php

namespace Database\Seeders;

use App\Models\DeputeSenateur;
use App\Models\TerritoryDepartment;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class DeputesSenateursSe

eder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');
        
        $groupes = [
            ['sigle' => 'RE', 'nom' => 'Renaissance', 'nb_deputes' => 169],
            ['sigle' => 'RN', 'nom' => 'Rassemblement National', 'nb_deputes' => 142],
            ['sigle' => 'LFI-NFP', 'nom' => 'La France Insoumise - NFP', 'nb_deputes' => 71],
            ['sigle' => 'LR', 'nom' => 'Les Républicains', 'nb_deputes' => 47],
            ['sigle' => 'SOC', 'nom' => 'Socialistes', 'nb_deputes' => 66],
            ['sigle' => 'HOR', 'nom' => 'Horizons', 'nb_deputes' => 34],
            ['sigle' => 'ECOLO', 'nom' => 'Écologistes', 'nb_deputes' => 33],
            ['sigle' => 'NI', 'nom' => 'Non-inscrits', 'nb_deputes' => 15],
        ];

        $professions = [
            'Avocat(e)', 'Médecin', 'Professeur', 'Chef d\'entreprise', 'Fonctionnaire',
            'Ingénieur', 'Journaliste', 'Agriculteur', 'Cadre', 'Consultant',
            'Élu local', 'Pharmacien', 'Architecte', 'Artisan', 'Directeur'
        ];

        $this->command->info('🏛️ Génération de 577 députés...');

        $deputeCount = 0;
        $departments = TerritoryDepartment::pluck('code')->toArray();

        foreach ($groupes as $groupe) {
            for ($i = 0; $i < $groupe['nb_deputes']; $i++) {
                $deputeCount++;
                
                // Circonscription (département + numéro)
                $dept = $departments[array_rand($departments)];
                $circonscription = sprintf('%s-%02d', $dept, rand(1, 10));

                DeputeSenateur::create([
                    'source' => 'assemblee',
                    'uid' => 'PA' . str_pad($deputeCount, 6, '0', STR_PAD_LEFT),
                    'nom' => $faker->lastName,
                    'prenom' => $faker->firstName,
                    'civilite' => rand(0, 1) ? 'M.' : 'Mme',
                    'groupe_politique' => $groupe['nom'],
                    'groupe_sigle' => $groupe['sigle'],
                    'circonscription' => $circonscription,
                    'numero_circonscription' => rand(1, 10),
                    'profession' => $professions[array_rand($professions)],
                    'date_naissance' => $faker->dateTimeBetween('-70 years', '-30 years'),
                    'legislature' => 17,
                    'debut_mandat' => '2024-07-08',
                    'fin_mandat' => null,
                    'en_exercice' => true,
                    'photo_url' => null,
                    'url_profil' => 'https://www.assemblee-nationale.fr/dyn/17/deputes',
                    'fonctions' => rand(0, 1) ? [
                        'Membre de la commission des lois',
                        'Vice-président de la délégation aux collectivités territoriales'
                    ] : null,
                    'commissions' => [
                        ['Finances', 'Lois', 'Affaires sociales', 'Défense', 'Affaires étrangères'][array_rand(['Finances', 'Lois', 'Affaires sociales', 'Défense', 'Affaires étrangères'])]
                    ],
                    'nb_propositions' => rand(0, 15),
                    'nb_amendements' => rand(5, 120),
                    'taux_presence' => rand(70, 98) + (rand(0, 99) / 100),
                ]);
            }
        }

        $this->command->info('✓ 577 députés générés');

        // SÉNATEURS
        $this->command->info('🏛️ Génération de 348 sénateurs...');

        $groupesSenat = [
            ['sigle' => 'LR', 'nom' => 'Les Républicains', 'nb' => 142],
            ['sigle' => 'SOC', 'nom' => 'Socialistes', 'nb' => 69],
            ['sigle' => 'UC', 'nom' => 'Union Centriste', 'nb' => 51],
            ['sigle' => 'RDPI', 'nom' => 'RDPI', 'nb' => 23],
            ['sigle' => 'CRCE', 'nom' => 'Communistes', 'nb' => 15],
            ['sigle' => 'RDSE', 'nom' => 'RDSE', 'nb' => 13],
            ['sigle' => 'NI', 'nom' => 'Non-inscrits', 'nb' => 35],
        ];

        $senateurCount = 0;

        foreach ($groupesSenat as $groupe) {
            for ($i = 0; $i < $groupe['nb']; $i++) {
                $senateurCount++;
                $dept = $departments[array_rand($departments)];

                DeputeSenateur::create([
                    'source' => 'senat',
                    'uid' => 'SE' . str_pad($senateurCount, 6, '0', STR_PAD_LEFT),
                    'nom' => $faker->lastName,
                    'prenom' => $faker->firstName,
                    'civilite' => rand(0, 1) ? 'M.' : 'Mme',
                    'groupe_politique' => $groupe['nom'],
                    'groupe_sigle' => $groupe['sigle'],
                    'circonscription' => $dept,
                    'numero_circonscription' => null,
                    'profession' => $professions[array_rand($professions)],
                    'date_naissance' => $faker->dateTimeBetween('-75 years', '-35 years'),
                    'legislature' => null,
                    'debut_mandat' => rand(0, 1) ? '2020-10-01' : '2023-10-01',
                    'fin_mandat' => rand(0, 1) ? '2026-09-30' : '2029-09-30',
                    'en_exercice' => true,
                    'photo_url' => null,
                    'url_profil' => 'https://www.senat.fr/senateurs',
                    'fonctions' => rand(0, 1) ? [
                        'Vice-président de commission',
                        'Membre du bureau du Sénat'
                    ] : null,
                    'commissions' => [
                        ['Finances', 'Lois', 'Affaires sociales', 'Aménagement du territoire', 'Culture'][array_rand(['Finances', 'Lois', 'Affaires sociales', 'Aménagement du territoire', 'Culture'])]
                    ],
                    'nb_propositions' => rand(0, 25),
                    'nb_amendements' => rand(10, 200),
                    'taux_presence' => rand(75, 99) + (rand(0, 99) / 100),
                ]);
            }
        }

        $this->command->info('✓ 348 sénateurs générés');
        $this->command->info('');
        $this->command->info('🎉 Total : 925 parlementaires créés !');
    }
}

