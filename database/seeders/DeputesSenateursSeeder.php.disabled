<?php

namespace Database\Seeders;

use App\Models\DeputeSenateur;
use App\Models\GroupeParlementaire;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class DeputesSenateursSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        // Récupérer les groupes parlementaires
        $groupesAssemblee = GroupeParlementaire::where('source', 'assemblee')->where('actif', true)->get();
        $groupesSenat = GroupeParlementaire::where('source', 'senat')->where('actif', true)->get();

        if ($groupesAssemblee->isEmpty() || $groupesSenat->isEmpty()) {
            $this->command->error('⚠️  Les groupes parlementaires doivent être créés avant les députés/sénateurs.');
            $this->command->info('💡 Lancez d\'abord: php artisan db:seed --class=GroupesParlementairesSeeder');
            return;
        }

        $this->command->info('🏛️  Création de 577 députés...');
        $this->createDeputes($faker, $groupesAssemblee);

        $this->command->info('🎩 Création de 348 sénateurs...');
        $this->createSenateurs($faker, $groupesSenat);

        $this->command->info('✅ Députés et sénateurs créés avec succès !');
    }

    /**
     * Créer 577 députés (1 par circonscription)
     */
    private function createDeputes($faker, $groupes)
    {
        $civilites = ['M.', 'Mme'];
        $professions = [
            'Avocat', 'Médecin', 'Enseignant', 'Cadre du secteur privé', 'Fonctionnaire',
            'Entrepreneur', 'Agriculteur', 'Ingénieur', 'Journaliste', 'Consultant',
            'Chef d\'entreprise', 'Professeur', 'Cadre territorial', 'Directeur',
        ];

        // Départements français (01 à 95 + DOM-TOM)
        $departements = array_merge(
            range(1, 95),
            ['971', '972', '973', '974', '976'] // Guadeloupe, Martinique, Guyane, Réunion, Mayotte
        );

        $deputeId = 1;

        foreach ($departements as $dept) {
            $deptCode = str_pad($dept, 2, '0', STR_PAD_LEFT);
            
            // Nombre de circonscriptions par département (simplifié)
            $nbCirconscriptions = $this->getNbCirconscriptions($dept);

            for ($circ = 1; $circ <= $nbCirconscriptions; $circ++) {
                $civilite = $faker->randomElement($civilites);
                $nom = $faker->lastName;
                $prenom = $faker->firstName($civilite === 'M.' ? 'male' : 'female');
                $groupe = $groupes->random();

                DeputeSenateur::create([
                    'uid' => 'PA' . str_pad($deputeId, 6, '0', STR_PAD_LEFT),
                    'source' => 'assemblee',
                    'civilite' => $civilite,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'nom_complet' => "$prenom $nom",
                    'groupe_sigle' => $groupe->sigle,
                    'circonscription' => $deptCode . '-' . str_pad($circ, 2, '0', STR_PAD_LEFT),
                    'numero_circonscription' => $circ,
                    'profession' => $faker->randomElement($professions),
                    'date_naissance' => $faker->dateTimeBetween('-70 years', '-30 years'),
                    'debut_mandat' => now()->subYears(rand(0, 5)),
                    'en_exercice' => true,
                    'photo_url' => 'https://i.pravatar.cc/300?u=' . $deputeId,
                    'url_profil' => "https://www.assemblee-nationale.fr/dyn/deputes/PA" . str_pad($deputeId, 6, '0', STR_PAD_LEFT),
                    'nb_propositions' => rand(0, 25),
                    'nb_amendements' => rand(0, 150),
                    'taux_presence' => rand(70, 98) + (rand(0, 99) / 100),
                    'fonctions' => $this->generateFonctions($faker),
                    'commissions' => $this->generateCommissions($faker),
                ]);

                $deputeId++;
            }
        }
    }

    /**
     * Créer 348 sénateurs
     */
    private function createSenateurs($faker, $groupes)
    {
        $civilites = ['M.', 'Mme'];
        $professions = [
            'Avocat', 'Médecin', 'Enseignant', 'Cadre du secteur privé', 'Fonctionnaire',
            'Entrepreneur', 'Agriculteur', 'Ingénieur', 'Journaliste', 'Consultant',
            'Maire', 'Conseiller départemental', 'Élu local', 'Directeur',
        ];

        // Départements français (01 à 95 + DOM-TOM)
        $departements = array_merge(
            range(1, 95),
            ['971', '972', '973', '974', '976']
        );

        $senateurId = 1;

        foreach ($departements as $dept) {
            $deptCode = str_pad($dept, 2, '0', STR_PAD_LEFT);
            
            // Nombre de sénateurs par département (simplifié, entre 1 et 12)
            $nbSenateurs = $this->getNbSenateurs($dept);

            for ($i = 1; $i <= $nbSenateurs; $i++) {
                $civilite = $faker->randomElement($civilites);
                $nom = $faker->lastName;
                $prenom = $faker->firstName($civilite === 'M.' ? 'male' : 'female');
                $groupe = $groupes->random();

                DeputeSenateur::create([
                    'uid' => 'SEN' . str_pad($senateurId, 5, '0', STR_PAD_LEFT),
                    'source' => 'senat',
                    'civilite' => $civilite,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'nom_complet' => "$prenom $nom",
                    'groupe_sigle' => $groupe->sigle,
                    'circonscription' => $deptCode,
                    'profession' => $faker->randomElement($professions),
                    'date_naissance' => $faker->dateTimeBetween('-75 years', '-35 years'),
                    'debut_mandat' => now()->subYears(rand(0, 9)),
                    'en_exercice' => true,
                    'photo_url' => 'https://i.pravatar.cc/300?u=sen' . $senateurId,
                    'url_profil' => "https://www.senat.fr/senateur/sen" . str_pad($senateurId, 5, '0', STR_PAD_LEFT) . ".html",
                    'nb_propositions' => rand(0, 15),
                    'nb_amendements' => rand(0, 100),
                    'taux_presence' => rand(75, 98) + (rand(0, 99) / 100),
                    'fonctions' => $this->generateFonctions($faker),
                    'commissions' => $this->generateCommissions($faker),
                ]);

                $senateurId++;

                if ($senateurId > 348) {
                    break 2; // Sortir des deux boucles
                }
            }
        }
    }

    /**
     * Nombre de circonscriptions par département (simplifié)
     */
    private function getNbCirconscriptions($dept): int
    {
        // Départements avec beaucoup de circonscriptions
        $grandes = [
            13 => 16, // Bouches-du-Rhône
            59 => 21, // Nord
            62 => 14, // Pas-de-Calais
            69 => 14, // Rhône
            75 => 18, // Paris
            92 => 13, // Hauts-de-Seine
            93 => 12, // Seine-Saint-Denis
            94 => 11, // Val-de-Marne
        ];

        if (isset($grandes[$dept])) {
            return $grandes[$dept];
        }

        // Départements moyens : 3-8 circonscriptions
        if ($dept >= 1 && $dept <= 95) {
            return rand(3, 8);
        }

        // DOM-TOM : 1-4 circonscriptions
        return rand(1, 4);
    }

    /**
     * Nombre de sénateurs par département (simplifié)
     */
    private function getNbSenateurs($dept): int
    {
        // Départements avec beaucoup de sénateurs
        $grandes = [
            75 => 12, // Paris
            59 => 6,  // Nord
            13 => 6,  // Bouches-du-Rhône
            69 => 4,  // Rhône
        ];

        if (isset($grandes[$dept])) {
            return $grandes[$dept];
        }

        // Départements moyens : 1-3 sénateurs
        if ($dept >= 1 && $dept <= 95) {
            return rand(1, 3);
        }

        // DOM-TOM : 1-2 sénateurs
        return rand(1, 2);
    }

    /**
     * Générer des fonctions parlementaires
     */
    private function generateFonctions($faker): ?array
    {
        if (rand(1, 100) > 30) {
            return null; // 70% n'ont pas de fonction spéciale
        }

        $fonctions = [
            'Président de commission',
            'Vice-président de commission',
            'Rapporteur',
            'Questeur',
            'Secrétaire',
            'Membre du Bureau',
        ];

        return [$faker->randomElement($fonctions)];
    }

    /**
     * Générer des commissions
     */
    private function generateCommissions($faker): ?array
    {
        $commissions = [
            'Commission des affaires économiques',
            'Commission des affaires sociales',
            'Commission des finances',
            'Commission des lois',
            'Commission de la défense',
            'Commission des affaires étrangères',
            'Commission du développement durable',
            'Commission des affaires culturelles',
        ];

        $nbCommissions = rand(1, 3);
        return $faker->randomElements($commissions, $nbCommissions);
    }
}

