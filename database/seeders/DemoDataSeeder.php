<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\AgendaLegislatif;
use App\Models\Amendement;
use App\Models\Document;
use App\Models\GroupeParlementaire;
use App\Models\Hashtag;
use App\Models\LegalReference;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Profile;
use App\Models\PropositionLoi;
use App\Models\PublicRevenue;
use App\Models\PublicSpend;
use App\Models\Report;
use App\Models\Sanction;
use App\Models\Sector;
use App\Models\TerritoryDepartment;
use App\Models\TerritoryRegion;
use App\Models\ThematiqueLegislation;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\UserAllocation;
use App\Models\Verification;
use App\Models\VoteGroupeParlementaire;
use App\Models\VoteLegislatif;
use App\Models\VotePropositionLoi;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    private array $citoyens = [];

    private array $deputes = [];

    private array $propositions = [];

    private array $topics = [];

    private array $groupes = [];

    private array $thematiques = [];

    /**
     * Seed the application's database with realistic demo data.
     */
    public function run(): void
    {
        $this->command->info('🎬 Génération des données de démonstration CivicDash...');
        $this->command->newLine();

        // 1. Charger les données de référence
        $this->loadReferenceData();

        // 2. Créer des citoyens supplémentaires
        $this->createCitizens();

        // 3. Créer des députés/législateurs
        $this->createLegislators();

        // 4. Créer des propositions de loi
        $this->createPropositionsLoi();

        // 5. Créer des topics de débat
        $this->createTopics();

        // 6. Créer des posts et discussions
        $this->createPosts();

        // 7. Créer des votes citoyens
        $this->createVotes();

        // 8. Créer des événements législatifs
        $this->createAgendaEvents();

        // 9. Créer des votes législatifs et amendements
        $this->createVotesLegislatifs();

        // 10. Créer des amendements
        $this->createAmendements();

        // 11. Créer des références juridiques
        $this->createLegalReferences();

        // 12. Créer des hashtags populaires
        $this->createHashtags();

        // 13. Créer des données budgétaires
        $this->createBudgetData();

        // 14. Créer des signalements et sanctions
        $this->createReportsAndSanctions();

        // 15. Créer des documents et vérifications
        $this->createDocumentsAndVerifications();

        // 16. Créer des achievements et les attribuer
        $this->createAchievements();

        // 17. Créer des notifications
        $this->createNotifications();

        $this->command->newLine();
        $this->command->info('🎉 Données de démonstration générées avec succès !');
        $this->displayStats();
    }

    private function loadReferenceData(): void
    {
        $this->command->info('📚 Chargement des données de référence...');

        // Charger les groupes parlementaires (ou les créer si absents)
        if (GroupeParlementaire::count() === 0) {
            $this->command->warn('⚠️  Aucun groupe parlementaire trouvé, création...');
            $this->call(GroupesParlementairesSeeder::class);
        }

        $this->groupes = GroupeParlementaire::all()->keyBy('slug')->toArray();
        $this->thematiques = ThematiqueLegislation::all()->keyBy('slug')->toArray();

        $this->command->info('✓ '.count($this->groupes).' groupes parlementaires chargés');
        $this->command->info('✓ '.count($this->thematiques).' thématiques chargées');
    }

    private function createCitizens(): void
    {
        $this->command->info('👥 Création de 50 citoyens...');

        $regions = TerritoryRegion::all();
        $departments = TerritoryDepartment::all();

        for ($i = 1; $i <= 50; $i++) {
            $user = User::create([
                'name' => "Citoyen Démo $i",
                'email' => "citoyen{$i}@demo.civicdash.fr",
                'password' => Hash::make('demo2025'),
                'email_verified_at' => now(),
            ]);
            $user->assignRole('citizen');

            $region = $regions->random();
            $department = $departments->where('region_id', $region->id)->random();

            $scope = ['national', 'region', 'dept'][array_rand(['national', 'region', 'dept'])];

            // Définir region_id et department_id selon le scope
            $profileData = [
                'user_id' => $user->id,
                'display_name' => Profile::generateDisplayName(),
                'citizen_ref_hash' => Profile::hashCitizenRef("demo-citizen-{$i}"),
                'scope' => $scope,
                'is_verified' => rand(0, 100) > 30, // 70% vérifiés
            ];

            // Ajouter region_id/department_id selon le scope
            if ($scope === 'region') {
                $profileData['region_id'] = $region->id;
                $profileData['department_id'] = null;
            } elseif ($scope === 'dept') {
                $profileData['region_id'] = null;
                $profileData['department_id'] = $department->id;
            } else {
                // national : pas de région ni département
                $profileData['region_id'] = null;
                $profileData['department_id'] = null;
            }

            Profile::create($profileData);

            $this->citoyens[] = $user;
        }

        $this->command->info('✓ 50 citoyens créés');
    }

    private function createLegislators(): void
    {
        $this->command->info('🏛️ Création de 50 députés...');

        $nomsDeputes = [
            // Renaissance (10 députés)
            ['nom' => 'Sophie Martineau', 'groupe' => 'renaissance', 'circonscription' => 'Paris 15e'],
            ['nom' => 'Jean-Pierre Dubois', 'groupe' => 'renaissance', 'circonscription' => 'Lyon 3e'],
            ['nom' => 'Marie Lambert', 'groupe' => 'renaissance', 'circonscription' => 'Marseille 8e'],
            ['nom' => 'François Moreau', 'groupe' => 'renaissance', 'circonscription' => 'Toulouse 2e'],
            ['nom' => 'Isabelle Rousseau', 'groupe' => 'renaissance', 'circonscription' => 'Nantes 1ère'],
            ['nom' => 'Pierre Lefebvre', 'groupe' => 'renaissance', 'circonscription' => 'Bordeaux 3e'],
            ['nom' => 'Catherine Bernard', 'groupe' => 'renaissance', 'circonscription' => 'Strasbourg 2e'],
            ['nom' => 'Michel Petit', 'groupe' => 'renaissance', 'circonscription' => 'Nice 4e'],
            ['nom' => 'Nathalie Durand', 'groupe' => 'renaissance', 'circonscription' => 'Lille 1ère'],
            ['nom' => 'Laurent Leroy', 'groupe' => 'renaissance', 'circonscription' => 'Rennes 2e'],

            // Rassemblement National (8 députés)
            ['nom' => 'Valérie Simon', 'groupe' => 'rassemblement-national', 'circonscription' => 'Pas-de-Calais 11e'],
            ['nom' => 'Thierry Martin', 'groupe' => 'rassemblement-national', 'circonscription' => 'Vaucluse 3e'],
            ['nom' => 'Sandrine Fournier', 'groupe' => 'rassemblement-national', 'circonscription' => 'Aisne 1ère'],
            ['nom' => 'Patrick Girard', 'groupe' => 'rassemblement-national', 'circonscription' => 'Var 8e'],
            ['nom' => 'Céline Bonnet', 'groupe' => 'rassemblement-national', 'circonscription' => 'Oise 7e'],
            ['nom' => 'Olivier Dupont', 'groupe' => 'rassemblement-national', 'circonscription' => 'Gard 6e'],
            ['nom' => 'Sylvie Blanc', 'groupe' => 'rassemblement-national', 'circonscription' => 'Somme 4e'],
            ['nom' => 'Nicolas Mercier', 'groupe' => 'rassemblement-national', 'circonscription' => 'Hérault 9e'],

            // LFI-NFP (7 députés)
            ['nom' => 'Martine Garnier', 'groupe' => 'lfi-nfp', 'circonscription' => 'Seine-Saint-Denis 1ère'],
            ['nom' => 'Christophe Faure', 'groupe' => 'lfi-nfp', 'circonscription' => 'Bouches-du-Rhône 4e'],
            ['nom' => 'Amélie Fontaine', 'groupe' => 'lfi-nfp', 'circonscription' => 'Paris 18e'],
            ['nom' => 'David Roussel', 'groupe' => 'lfi-nfp', 'circonscription' => 'Val-de-Marne 8e'],
            ['nom' => 'Émilie Perrin', 'groupe' => 'lfi-nfp', 'circonscription' => 'Gironde 5e'],
            ['nom' => 'Thomas Leclerc', 'groupe' => 'lfi-nfp', 'circonscription' => 'Rhône 14e'],
            ['nom' => 'Caroline Morel', 'groupe' => 'lfi-nfp', 'circonscription' => 'Nord 20e'],

            // Les Républicains (5 députés)
            ['nom' => 'Philippe Arnaud', 'groupe' => 'les-republicains', 'circonscription' => 'Hauts-de-Seine 6e'],
            ['nom' => 'Brigitte Lemoine', 'groupe' => 'les-republicains', 'circonscription' => 'Yvelines 12e'],
            ['nom' => 'Alain Bertrand', 'groupe' => 'les-republicains', 'circonscription' => 'Alpes-Maritimes 2e'],
            ['nom' => 'Véronique Dumas', 'groupe' => 'les-republicains', 'circonscription' => 'Loire 3e'],
            ['nom' => 'Gérard Fontaine', 'groupe' => 'les-republicains', 'circonscription' => 'Bas-Rhin 7e'],

            // Socialistes (5 députés)
            ['nom' => 'Stéphanie Roux', 'groupe' => 'socialistes', 'circonscription' => 'Haute-Garonne 8e'],
            ['nom' => 'Marc Delorme', 'groupe' => 'socialistes', 'circonscription' => 'Finistère 6e'],
            ['nom' => 'Audrey Chevalier', 'groupe' => 'socialistes', 'circonscription' => 'Puy-de-Dôme 3e'],
            ['nom' => 'Julien Marchand', 'groupe' => 'socialistes', 'circonscription' => 'Meurthe-et-Moselle 4e'],
            ['nom' => 'Laetitia Giraud', 'groupe' => 'socialistes', 'circonscription' => 'Hérault 5e'],

            // Horizons (3 députés)
            ['nom' => 'Antoine Dubois', 'groupe' => 'horizons', 'circonscription' => 'Havre 2e'],
            ['nom' => 'Claire Moreau', 'groupe' => 'horizons', 'circonscription' => 'Essonne 10e'],
            ['nom' => 'Sébastien Blanc', 'groupe' => 'horizons', 'circonscription' => 'Calvados 5e'],

            // Écologistes (4 députés)
            ['nom' => 'Pauline Verdier', 'groupe' => 'ecologistes', 'circonscription' => 'Paris 11e'],
            ['nom' => 'Maxime Forestier', 'groupe' => 'ecologistes', 'circonscription' => 'Isère 4e'],
            ['nom' => 'Camille Dubois', 'groupe' => 'ecologistes', 'circonscription' => 'Ille-et-Vilaine 2e'],
            ['nom' => 'Lucas Bonnet', 'groupe' => 'ecologistes', 'circonscription' => 'Rhône 6e'],

            // Démocrate (3 députés)
            ['nom' => 'Françoise Legrand', 'groupe' => 'democrate', 'circonscription' => 'Pyrénées-Atlantiques 3e'],
            ['nom' => 'Henri Dupuis', 'groupe' => 'democrate', 'circonscription' => 'Maine-et-Loire 7e'],
            ['nom' => 'Monique Fabre', 'groupe' => 'democrate', 'circonscription' => 'Vienne 4e'],

            // LIOT (2 députés)
            ['nom' => 'Bernard Rousseau', 'groupe' => 'liot', 'circonscription' => 'Corse-du-Sud 1ère'],
            ['nom' => 'Sylvain Mercier', 'groupe' => 'liot', 'circonscription' => 'Guadeloupe 3e'],

            // GDR (3 députés)
            ['nom' => 'Jacqueline Renard', 'groupe' => 'gdr', 'circonscription' => 'Allier 2e'],
            ['nom' => 'Robert Lemoine', 'groupe' => 'gdr', 'circonscription' => 'Puy-de-Dôme 5e'],
            ['nom' => 'Danielle Perrot', 'groupe' => 'gdr', 'circonscription' => 'Val-d\'Oise 9e'],
        ];

        foreach ($nomsDeputes as $index => $data) {
            $user = User::create([
                'name' => $data['nom'],
                'email' => 'depute'.($index + 1).'@demo.assemblee-nationale.fr',
                'password' => Hash::make('demo2025'),
                'email_verified_at' => now(),
            ]);
            $user->assignRole('legislator');

            Profile::create([
                'user_id' => $user->id,
                'display_name' => $data['nom'],
                'is_public_figure' => true,
                'scope' => 'national',
                'is_verified' => true,
                'bio' => "Député(e) de la {$data['circonscription']}, membre du groupe {$this->groupes[$data['groupe']]['nom']}.",
            ]);

            $this->deputes[] = [
                'user' => $user,
                'groupe' => $data['groupe'],
                'circonscription' => $data['circonscription'],
            ];
        }

        $this->command->info('✓ 50 députés créés');

        // Afficher la répartition par groupe
        $repartition = [];
        foreach ($this->deputes as $depute) {
            $groupe = $this->groupes[$depute['groupe']]['sigle'];
            $repartition[$groupe] = ($repartition[$groupe] ?? 0) + 1;
        }

        foreach ($repartition as $groupe => $count) {
            $this->command->info("  → $groupe: $count députés");
        }
    }

    private function createPropositionsLoi(): void
    {
        $this->command->info('📜 Création de 30 propositions de loi...');

        $propositionsData = [
            [
                'titre' => 'Proposition de loi visant à renforcer la transparence de la vie publique',
                'resume' => 'Cette proposition vise à améliorer la transparence des activités des élus et à renforcer les dispositifs de prévention des conflits d\'intérêts.',
                'theme' => 'institutions',
                'statut' => 'en_discussion',
            ],
            [
                'titre' => 'Projet de loi relatif à la transition énergétique et écologique',
                'resume' => 'Ce projet de loi fixe les objectifs de réduction des émissions de gaz à effet de serre et de développement des énergies renouvelables pour 2030.',
                'theme' => 'environnement',
                'statut' => 'en_discussion',
            ],
            [
                'titre' => 'Proposition de loi pour l\'amélioration de l\'accès aux soins',
                'resume' => 'Cette proposition vise à réduire les déserts médicaux et à garantir un accès équitable aux soins sur l\'ensemble du territoire.',
                'theme' => 'sante',
                'statut' => 'adopte',
            ],
            [
                'titre' => 'Projet de loi de finances pour 2025',
                'resume' => 'Le projet de loi de finances pour 2025 prévoit un budget de 500 milliards d\'euros avec un déficit de 3,7% du PIB.',
                'theme' => 'budget',
                'statut' => 'en_discussion',
            ],
            [
                'titre' => 'Proposition de loi sur la protection des données personnelles',
                'resume' => 'Cette proposition renforce les droits des citoyens sur leurs données personnelles et les obligations des entreprises.',
                'theme' => 'numerique',
                'statut' => 'en_commission',
            ],
            [
                'titre' => 'Projet de loi pour l\'égalité salariale femmes-hommes',
                'resume' => 'Ce projet impose des sanctions aux entreprises ne respectant pas l\'égalité salariale et renforce les dispositifs de contrôle.',
                'theme' => 'social',
                'statut' => 'adopte',
            ],
            [
                'titre' => 'Proposition de loi relative à la sécurité routière',
                'resume' => 'Cette proposition vise à réduire la mortalité routière par des mesures de prévention et de répression renforcées.',
                'theme' => 'securite',
                'statut' => 'en_discussion',
            ],
            [
                'titre' => 'Projet de loi sur la réforme des retraites',
                'resume' => 'Ce projet propose une réforme systémique du système de retraites avec un âge pivot à 64 ans.',
                'theme' => 'social',
                'statut' => 'rejete',
            ],
            [
                'titre' => 'Proposition de loi pour le développement de l\'apprentissage',
                'resume' => 'Cette proposition vise à faciliter l\'accès à l\'apprentissage et à améliorer son attractivité.',
                'theme' => 'education',
                'statut' => 'adopte',
            ],
            [
                'titre' => 'Projet de loi contre les violences faites aux femmes',
                'resume' => 'Ce projet renforce la protection des victimes et les sanctions contre les auteurs de violences.',
                'theme' => 'justice',
                'statut' => 'adopte',
            ],
        ];

        $legislature = 17;
        $numero = 1000;

        foreach ($propositionsData as $data) {
            $depute = $this->deputes[array_rand($this->deputes)];

            $proposition = PropositionLoi::create([
                'source' => 'assemblee',
                'legislature' => $legislature,
                'numero' => (string) $numero++,
                'titre' => $data['titre'],
                'resume' => $data['resume'],
                'texte_integral' => $this->generateTexteIntegral($data['titre']),
                'statut' => $data['statut'],
                'theme' => $data['theme'],
                'date_depot' => Carbon::now()->subDays(rand(10, 180)),
                'date_adoption' => in_array($data['statut'], ['adopte']) ? Carbon::now()->subDays(rand(1, 30)) : null,
                'auteurs' => [
                    [
                        'nom' => $depute['user']->name,
                        'groupe' => $depute['groupe'],
                        'qualite' => 'Auteur principal',
                    ],
                ],
                'etapes' => $this->generateEtapes($data['statut']),
                'votes_resultats' => $this->generateVotesResultats($data['statut']),
                'url_externe' => 'https://www.assemblee-nationale.fr/dyn/17/textes/l17b'.$numero,
                'fetched_at' => now(),
            ]);

            // Associer les thématiques
            $thematique = ThematiqueLegislation::where('code', strtoupper($data['theme']))->first();
            if ($thematique) {
                $proposition->thematiques()->attach($thematique->id, [
                    'est_principal' => true,
                    'confiance' => rand(80, 100),
                    'tags_keywords' => json_encode($this->extractKeywords($data['titre'])),
                    'tagged_by' => 'auto',
                ]);
            }

            $this->propositions[] = $proposition;
        }

        // Générer 20 propositions supplémentaires plus courtes
        for ($i = 0; $i < 20; $i++) {
            $depute = $this->deputes[array_rand($this->deputes)];
            $themeSlug = array_keys($this->thematiques)[array_rand(array_keys($this->thematiques))];

            $proposition = PropositionLoi::create([
                'source' => rand(0, 1) ? 'assemblee' : 'senat',
                'legislature' => $legislature,
                'numero' => (string) $numero++,
                'titre' => $this->generateRandomTitle($themeSlug),
                'resume' => 'Proposition de loi visant à améliorer la législation dans le domaine concerné.',
                'statut' => ['en_commission', 'en_discussion', 'adopte', 'rejete'][array_rand(['en_commission', 'en_discussion', 'adopte', 'rejete'])],
                'theme' => $themeSlug,
                'date_depot' => Carbon::now()->subDays(rand(10, 365)),
                'auteurs' => [
                    [
                        'nom' => $depute['user']->name,
                        'groupe' => $depute['groupe'],
                        'qualite' => 'Auteur principal',
                    ],
                ],
                'fetched_at' => now(),
            ]);

            $thematique = ThematiqueLegislation::where('code', strtoupper($themeSlug))->first();
            if ($thematique) {
                $proposition->thematiques()->attach($thematique->id, [
                    'est_principal' => true,
                    'confiance' => rand(70, 95),
                    'tagged_by' => 'auto',
                ]);
            }

            $this->propositions[] = $proposition;
        }

        $this->command->info('✓ 30 propositions de loi créées');
    }

    private function createTopics(): void
    {
        $this->command->info('💬 Création de 25 topics de débat...');

        $topicsData = [
            [
                'title' => 'Faut-il instaurer un revenu universel en France ?',
                'description' => 'Débat sur la mise en place d\'un revenu universel de base pour tous les citoyens français.',
                'type' => 'debate',
                'has_ballot' => true,
                'ballot_type' => 'yes_no',
            ],
            [
                'title' => 'Réforme de la fiscalité écologique : quelles mesures prioritaires ?',
                'description' => 'Discussion sur les mesures fiscales à mettre en place pour encourager la transition écologique.',
                'type' => 'debate',
                'has_ballot' => false,
            ],
            [
                'title' => 'Budget participatif 2025 : vos priorités pour l\'éducation',
                'description' => 'Votez pour les projets éducatifs que vous souhaitez voir financés en priorité.',
                'type' => 'referendum',
                'has_ballot' => true,
                'ballot_type' => 'multiple_choice',
                'ballot_options' => [
                    'Rénovation des établissements scolaires',
                    'Formation des enseignants au numérique',
                    'Développement des activités périscolaires',
                    'Aide aux devoirs et soutien scolaire',
                ],
            ],
            [
                'title' => 'Gratuité des transports en commun : pour ou contre ?',
                'description' => 'Débat sur la mise en place de la gratuité des transports en commun dans les grandes villes.',
                'type' => 'debate',
                'has_ballot' => true,
                'ballot_type' => 'yes_no',
            ],
            [
                'title' => 'Quelle politique migratoire pour la France ?',
                'description' => 'Discussion sur les orientations de la politique migratoire française.',
                'type' => 'debate',
                'has_ballot' => false,
            ],
        ];

        foreach ($topicsData as $data) {
            $author = $this->citoyens[array_rand($this->citoyens)];

            $topic = Topic::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'scope' => 'national',
                'type' => $data['type'],
                'status' => 'open',
                'author_id' => $author->id,
                'has_ballot' => $data['has_ballot'],
                'voting_opens_at' => $data['has_ballot'] ? Carbon::now()->subDays(rand(1, 10)) : null,
                'voting_deadline_at' => $data['has_ballot'] ? Carbon::now()->addDays(rand(5, 30)) : null,
                'ballot_type' => $data['ballot_type'] ?? null,
                'ballot_options' => $data['ballot_options'] ?? null,
            ]);

            $this->topics[] = $topic;
        }

        // Générer 20 topics supplémentaires
        $regions = TerritoryRegion::all();
        $departments = TerritoryDepartment::all();

        for ($i = 0; $i < 20; $i++) {
            $author = $this->citoyens[array_rand($this->citoyens)];
            $type = ['debate', 'bill', 'referendum'][array_rand(['debate', 'bill', 'referendum'])];
            $scope = ['national', 'region', 'dept'][array_rand(['national', 'region', 'dept'])];

            // Définir region_id et department_id selon le scope
            $topicData = [
                'title' => $this->generateRandomTopicTitle(),
                'description' => 'Discussion ouverte sur ce sujet important pour notre démocratie.',
                'scope' => $scope,
                'type' => $type,
                'status' => ['open', 'closed'][array_rand(['open', 'closed'])],
                'author_id' => $author->id,
                'has_ballot' => $type === 'referendum',
                'voting_opens_at' => $type === 'referendum' ? Carbon::now()->subDays(rand(1, 10)) : null,
                'voting_deadline_at' => $type === 'referendum' ? Carbon::now()->addDays(rand(5, 30)) : null,
                'ballot_type' => $type === 'referendum' ? 'yes_no' : null,
            ];

            // Ajouter region_id/department_id selon le scope
            if ($scope === 'region') {
                $topicData['region_id'] = $regions->random()->id;
                $topicData['department_id'] = null;
            } elseif ($scope === 'dept') {
                $department = $departments->random();
                $topicData['region_id'] = null;
                $topicData['department_id'] = $department->id;
            } else {
                // national : pas de région ni département
                $topicData['region_id'] = null;
                $topicData['department_id'] = null;
            }

            $topic = Topic::create($topicData);

            $this->topics[] = $topic;
        }

        $this->command->info('✓ 25 topics de débat créés');
    }

    private function createPosts(): void
    {
        $this->command->info('💭 Création de 200 posts et réponses...');

        $postsCount = 0;

        foreach ($this->topics as $topic) {
            // Créer 5-15 posts par topic
            $numPosts = rand(5, 15);

            for ($i = 0; $i < $numPosts; $i++) {
                $author = $this->citoyens[array_rand($this->citoyens)];

                $post = Post::create([
                    'topic_id' => $topic->id,
                    'user_id' => $author->id,
                    'content' => $this->generatePostContent(),
                    'is_hidden' => false,
                    'upvotes' => rand(0, 50),
                    'downvotes' => rand(0, 10),
                ]);

                $postsCount++;

                // Créer 0-5 réponses par post
                $numReplies = rand(0, 5);
                for ($j = 0; $j < $numReplies; $j++) {
                    $replyAuthor = $this->citoyens[array_rand($this->citoyens)];

                    Post::create([
                        'topic_id' => $topic->id,
                        'user_id' => $replyAuthor->id,
                        'parent_id' => $post->id,
                        'content' => $this->generateReplyContent(),
                        'is_hidden' => false,
                        'upvotes' => rand(0, 20),
                        'downvotes' => rand(0, 5),
                    ]);

                    $postsCount++;
                }
            }
        }

        $this->command->info("✓ $postsCount posts et réponses créés");
    }

    private function createVotes(): void
    {
        $this->command->info('🗳️ Création de votes citoyens...');

        $votesCount = 0;

        // Votes sur les propositions de loi
        foreach ($this->propositions as $proposition) {
            $numVotes = rand(20, 100);

            for ($i = 0; $i < $numVotes; $i++) {
                $citoyen = $this->citoyens[array_rand($this->citoyens)];

                try {
                    VotePropositionLoi::create([
                        'proposition_loi_id' => $proposition->id,
                        'user_id' => $citoyen->id,
                        'vote' => ['pour', 'contre', 'abstention'][array_rand(['pour', 'contre', 'abstention'])],
                        'commentaire' => rand(0, 100) > 70 ? $this->generateVoteComment() : null,
                    ]);
                    $votesCount++;
                } catch (\Exception $e) {
                    // Ignorer les doublons (même user vote 2 fois)
                }
            }
        }

        $this->command->info("✓ $votesCount votes citoyens créés");
    }

    private function createAgendaEvents(): void
    {
        $this->command->info('📅 Création d\'événements législatifs...');

        $eventsData = [
            [
                'titre' => 'Session de questions au gouvernement',
                'description' => 'Questions orales des députés au Premier ministre et aux ministres.',
                'type' => 'seance',
                'date_debut' => Carbon::now()->addDays(2)->setTime(15, 0),
                'date_fin' => Carbon::now()->addDays(2)->setTime(17, 0),
            ],
            [
                'titre' => 'Commission des finances - Examen du PLF 2025',
                'description' => 'Examen en commission du projet de loi de finances pour 2025.',
                'type' => 'commission',
                'date_debut' => Carbon::now()->addDays(5)->setTime(9, 30),
                'date_fin' => Carbon::now()->addDays(5)->setTime(12, 30),
            ],
            [
                'titre' => 'Débat sur la transition énergétique',
                'description' => 'Débat général sur les orientations de la politique énergétique.',
                'type' => 'seance',
                'date_debut' => Carbon::now()->addDays(7)->setTime(14, 0),
                'date_fin' => Carbon::now()->addDays(7)->setTime(18, 0),
            ],
            [
                'titre' => 'Vote solennel - Loi sur l\'égalité salariale',
                'description' => 'Vote final sur le projet de loi relatif à l\'égalité salariale.',
                'type' => 'vote',
                'date_debut' => Carbon::now()->addDays(10)->setTime(16, 0),
                'date_fin' => Carbon::now()->addDays(10)->setTime(16, 30),
            ],
        ];

        foreach ($eventsData as $data) {
            AgendaLegislatif::create([
                'source' => 'assemblee',
                'date' => $data['date_debut']->toDateString(), // Extraire la date
                'titre' => $data['titre'],
                'description' => $data['description'],
                'type' => $data['type'],
                'date_debut' => $data['date_debut'],
                'date_fin' => $data['date_fin'],
                'heure_debut' => $data['date_debut']->format('H:i:s'), // Extraire l'heure
                'heure_fin' => $data['date_fin']->format('H:i:s'), // Extraire l'heure
                'lieu' => 'Assemblée nationale',
                'statut' => 'planifie',
                'url_externe' => 'https://www.assemblee-nationale.fr/agenda',
            ]);
        }

        // Événements passés
        for ($i = 0; $i < 10; $i++) {
            $dateDebut = Carbon::now()->subDays(rand(1, 30))->setTime(15, 0);
            $dateFin = (clone $dateDebut)->setTime(19, 0);

            AgendaLegislatif::create([
                'source' => rand(0, 1) ? 'assemblee' : 'senat',
                'date' => $dateDebut->toDateString(), // Extraire la date
                'titre' => 'Séance publique du '.$dateDebut->format('d/m/Y'),
                'description' => 'Ordre du jour : questions diverses et votes.',
                'type' => 'seance',
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'heure_debut' => $dateDebut->format('H:i:s'), // Extraire l'heure
                'heure_fin' => $dateFin->format('H:i:s'), // Extraire l'heure
                'lieu' => rand(0, 1) ? 'Assemblée nationale' : 'Sénat',
                'statut' => 'termine',
            ]);
        }

        $this->command->info('✓ 14 événements législatifs créés');
    }

    private function createLegalReferences(): void
    {
        $this->command->info('⚖️ Création de références juridiques...');

        $referencesData = [
            [
                'type' => 'code',
                'code' => 'Code civil',
                'article' => 'Article 1',
                'titre' => 'De la jouissance des droits civils',
                'contenu' => 'Tout Français jouira des droits civils.',
                'url' => 'https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006419283',
            ],
            [
                'type' => 'loi',
                'code' => 'Loi n°78-17',
                'article' => 'Article 1',
                'titre' => 'Loi Informatique et Libertés',
                'contenu' => 'L\'informatique doit être au service de chaque citoyen.',
                'url' => 'https://www.legifrance.gouv.fr/loda/id/JORFTEXT000000886460',
            ],
            [
                'type' => 'constitution',
                'code' => 'Constitution',
                'article' => 'Article 1',
                'titre' => 'La France est une République',
                'contenu' => 'La France est une République indivisible, laïque, démocratique et sociale.',
                'url' => 'https://www.legifrance.gouv.fr/loda/article_lc/LEGIARTI000019240997',
            ],
        ];

        foreach ($referencesData as $data) {
            LegalReference::create([
                'type' => $data['type'],
                'code' => $data['code'],
                'code_name' => $data['code'], // Remplir code_name
                'article' => $data['article'],
                'reference_text' => $data['article'], // Remplir reference_text
                'titre' => $data['titre'],
                'contenu' => $data['contenu'],
                'url_legifrance' => $data['url'],
                'date_version' => Carbon::now()->subYears(rand(1, 10)),
            ]);
        }

        $this->command->info('✓ 3 références juridiques créées');
    }

    private function createVotesLegislatifs(): void
    {
        $this->command->info('🗳️ Création de votes législatifs par groupe...');

        $votesCount = 0;

        // Créer des votes pour les propositions adoptées ou rejetées
        foreach ($this->propositions as $proposition) {
            if (! in_array($proposition->statut, ['adopte', 'rejete'])) {
                continue;
            }

            // Créer un vote législatif principal
            $voteLegislatif = VoteLegislatif::create([
                'proposition_loi_id' => $proposition->id,
                'source' => 'assemblee', // Remplir source
                'numero_scrutin' => 'SCRUTIN-'.str_pad($votesCount + 1, 4, '0', STR_PAD_LEFT), // Générer numéro
                'titre' => 'Vote solennel - '.$proposition->titre,
                'date_vote' => Carbon::now()->subDays(rand(1, 60)),
                'type_vote' => 'solennel',
                'resultat' => $proposition->statut === 'adopte' ? 'adopte' : 'rejete',
                'pour' => $proposition->statut === 'adopte' ? rand(280, 350) : rand(150, 220),
                'contre' => $proposition->statut === 'adopte' ? rand(150, 220) : rand(280, 350),
                'abstention' => rand(20, 50),
            ]);

            // Créer les votes par groupe parlementaire
            foreach ($this->groupes as $slug => $groupe) {
                if ($groupe['chambre'] !== 'assemblee') {
                    continue; // On se concentre sur l'Assemblée pour la démo
                }

                // Déterminer le vote du groupe selon sa position politique et le thème
                $voteGroupe = $this->determineVoteGroupe($groupe, $proposition);

                // Calculer la position majoritaire du groupe
                $total = $voteGroupe['pour'] + $voteGroupe['contre'] + $voteGroupe['abstention'];
                if ($total === 0) {
                    $positionGroupe = 'mixte';
                } else {
                    $pourcentagePour = ($voteGroupe['pour'] / $total) * 100;
                    $pourcentageContre = ($voteGroupe['contre'] / $total) * 100;
                    $pourcentageAbstention = ($voteGroupe['abstention'] / $total) * 100;

                    if ($pourcentagePour > 50) {
                        $positionGroupe = 'pour';
                    } elseif ($pourcentageContre > 50) {
                        $positionGroupe = 'contre';
                    } elseif ($pourcentageAbstention > 50) {
                        $positionGroupe = 'abstention';
                    } else {
                        $positionGroupe = 'mixte';
                    }
                }

                VoteGroupeParlementaire::create([
                    'vote_legislatif_id' => $voteLegislatif->id,
                    'groupe_parlementaire_id' => $groupe['id'],
                    'position_groupe' => $positionGroupe, // Calculer automatiquement
                    'pour' => $voteGroupe['pour'],
                    'contre' => $voteGroupe['contre'],
                    'abstention' => $voteGroupe['abstention'],
                    'non_votants' => rand(0, 3),
                ]);

                $votesCount++;
            }
        }

        $this->command->info("✓ $votesCount votes de groupes parlementaires créés");
    }

    private function determineVoteGroupe(array $groupe, $proposition): array
    {
        $nombreMembres = $groupe['nombre_membres'];
        $position = $groupe['position_politique'];
        $theme = $proposition->theme;

        // Logique de vote selon la position politique et le thème
        $tendancePour = 0.5; // Par défaut 50/50

        // Ajustements selon le thème et la position
        if ($theme === 'environnement' && $position === 'gauche') {
            $tendancePour = 0.9; // 90% pour
        } elseif ($theme === 'environnement' && $groupe['slug'] === 'ecologistes') {
            $tendancePour = 0.95;
        } elseif ($theme === 'social' && $position === 'gauche') {
            $tendancePour = 0.85;
        } elseif ($theme === 'securite' && $position === 'droite') {
            $tendancePour = 0.8;
        } elseif ($theme === 'budget' && $position === 'centre') {
            $tendancePour = 0.7;
        } elseif ($theme === 'institutions' && $groupe['slug'] === 'renaissance') {
            $tendancePour = 0.9;
        }

        // Si la proposition est adoptée, ajuster globalement
        if ($proposition->statut === 'adopte') {
            $tendancePour += 0.1;
        } else {
            $tendancePour -= 0.1;
        }

        $tendancePour = max(0.1, min(0.9, $tendancePour));

        // Calculer les votes avec un peu d'aléatoire
        $pour = (int) ($nombreMembres * $tendancePour * (0.9 + rand(0, 20) / 100));
        $abstention = rand(0, (int) ($nombreMembres * 0.1));
        $contre = $nombreMembres - $pour - $abstention;

        return [
            'pour' => max(0, $pour),
            'contre' => max(0, $contre),
            'abstention' => $abstention,
        ];
    }

    private function createAmendements(): void
    {
        $this->command->info('📝 Création d\'amendements...');

        $amendementsCount = 0;

        // Créer 3-8 amendements pour les propositions en discussion
        foreach ($this->propositions as $proposition) {
            if (! in_array($proposition->statut, ['en_discussion', 'en_commission'])) {
                continue;
            }

            $numAmendements = rand(3, 8);

            for ($i = 1; $i <= $numAmendements; $i++) {
                $depute = $this->deputes[array_rand($this->deputes)];
                $statut = ['depose', 'adopte', 'rejete', 'retire'][array_rand(['depose', 'adopte', 'rejete', 'retire'])];

                Amendement::create([
                    'proposition_loi_id' => $proposition->id,
                    'source' => 'assemblee', // Remplir source
                    'numero' => $i,
                    'auteur_nom' => $depute['user']->name,
                    'auteur_groupe' => $depute['groupe'],
                    'objet' => $this->generateAmendementObjet(),
                    'dispositif' => $this->generateAmendementDispositif(),
                    'expose_sommaire' => $this->generateAmendementExpose(),
                    'statut' => $statut,
                    'sort' => $statut, // Copier statut vers sort
                    'date_depot' => Carbon::now()->subDays(rand(5, 30)),
                    'date_discussion' => $statut !== 'depose' ? Carbon::now()->subDays(rand(1, 15)) : null,
                ]);

                $amendementsCount++;
            }
        }

        $this->command->info("✓ $amendementsCount amendements créés");
    }

    private function generateAmendementObjet(): string
    {
        $objets = [
            'Préciser les modalités d\'application',
            'Renforcer les garanties pour les citoyens',
            'Améliorer la rédaction de l\'article',
            'Supprimer une disposition contraire au droit européen',
            'Étendre le champ d\'application',
            'Limiter la portée de la mesure',
            'Ajouter une clause de sauvegarde',
            'Prévoir une évaluation après 2 ans',
        ];

        return $objets[array_rand($objets)];
    }

    private function generateAmendementDispositif(): string
    {
        return "À l'alinéa ".rand(1, 10).', substituer aux mots : « ... » les mots : « ... ».';
    }

    private function generateAmendementExpose(): string
    {
        $exposes = [
            'Cet amendement vise à clarifier la rédaction de l\'article afin d\'éviter toute ambiguïté dans son application.',
            'Il est nécessaire de renforcer les garanties offertes aux citoyens dans le cadre de cette disposition.',
            'Cet amendement a pour objet de mettre en conformité le texte avec le droit européen.',
            'Il convient d\'étendre le champ d\'application de cette mesure pour en renforcer l\'efficacité.',
            'Cet amendement propose une rédaction plus précise permettant une meilleure application de la loi.',
        ];

        return $exposes[array_rand($exposes)];
    }

    private function createHashtags(): void
    {
        $this->command->info('#️⃣ Création de hashtags populaires...');

        $hashtagsData = [
            ['name' => 'DémocratieParticipative', 'usage' => 150, 'trending' => true, 'official' => true],
            ['name' => 'TransitionÉcologique', 'usage' => 120, 'trending' => true, 'official' => false],
            ['name' => 'Justicesociale', 'usage' => 95, 'trending' => false, 'official' => false],
            ['name' => 'Éducation', 'usage' => 80, 'trending' => false, 'official' => true],
            ['name' => 'Santé', 'usage' => 75, 'trending' => false, 'official' => true],
            ['name' => 'Numérique', 'usage' => 60, 'trending' => true, 'official' => false],
            ['name' => 'Budget2025', 'usage' => 55, 'trending' => true, 'official' => true],
            ['name' => 'Transparence', 'usage' => 50, 'trending' => false, 'official' => true],
        ];

        foreach ($hashtagsData as $data) {
            Hashtag::create([
                'slug' => strtolower($data['name']),
                'display_name' => $data['name'],
                'usage_count' => $data['usage'],
                'is_trending' => $data['trending'],
                'is_official' => $data['official'],
                'last_used_at' => Carbon::now()->subHours(rand(1, 48)),
            ]);
        }

        $this->command->info('✓ 8 hashtags populaires créés');
    }

    // ========================================================================
    // HELPERS - Génération de contenu
    // ========================================================================

    private function generateTexteIntegral(string $titre): string
    {
        return "PROPOSITION DE LOI\n\n".
               "Article 1er\n\n".
               "Les dispositions du présent article visent à...\n\n".
               "Article 2\n\n".
               "Le Gouvernement remet au Parlement, dans un délai de six mois...\n\n".
               "Article 3\n\n".
               "Les modalités d'application du présent article sont fixées par décret.";
    }

    private function generateEtapes(string $statut): array
    {
        $etapes = [
            ['date' => Carbon::now()->subDays(180)->toDateString(), 'libelle' => 'Dépôt de la proposition'],
            ['date' => Carbon::now()->subDays(150)->toDateString(), 'libelle' => 'Examen en commission'],
        ];

        if (in_array($statut, ['en_discussion', 'adopte'])) {
            $etapes[] = ['date' => Carbon::now()->subDays(100)->toDateString(), 'libelle' => 'Discussion en séance publique'];
        }

        if ($statut === 'adopte') {
            $etapes[] = ['date' => Carbon::now()->subDays(50)->toDateString(), 'libelle' => 'Vote favorable'];
            $etapes[] = ['date' => Carbon::now()->subDays(30)->toDateString(), 'libelle' => 'Transmission au Sénat'];
        }

        return $etapes;
    }

    private function generateVotesResultats(string $statut): ?array
    {
        if (! in_array($statut, ['adopte', 'rejete'])) {
            return null;
        }

        if ($statut === 'adopte') {
            return [
                'pour' => rand(280, 350),
                'contre' => rand(150, 220),
                'abstention' => rand(20, 50),
            ];
        } else {
            return [
                'pour' => rand(150, 220),
                'contre' => rand(280, 350),
                'abstention' => rand(20, 50),
            ];
        }
    }

    private function extractKeywords(string $text): array
    {
        $keywords = ['transparence', 'réforme', 'modernisation', 'amélioration', 'renforcement'];

        return array_slice($keywords, 0, rand(2, 4));
    }

    private function generateRandomTitle(string $theme): string
    {
        $prefixes = [
            'Proposition de loi visant à',
            'Projet de loi relatif à',
            'Proposition de loi pour',
            'Projet de loi portant sur',
        ];

        $subjects = [
            'institutions' => 'la modernisation des institutions',
            'environnement' => 'la protection de l\'environnement',
            'sante' => 'l\'amélioration du système de santé',
            'education' => 'la réforme de l\'éducation',
            'social' => 'la justice sociale',
            'economie' => 'le développement économique',
            'securite' => 'le renforcement de la sécurité',
            'justice' => 'la modernisation de la justice',
            'culture' => 'le soutien à la culture',
            'numerique' => 'la transformation numérique',
        ];

        return $prefixes[array_rand($prefixes)].' '.($subjects[$theme] ?? 'la législation');
    }

    private function generateRandomTopicTitle(): string
    {
        $titles = [
            'Quelle place pour l\'intelligence artificielle dans les services publics ?',
            'Comment réduire les inégalités territoriales ?',
            'Faut-il réformer le système électoral français ?',
            'Quelles solutions pour la crise du logement ?',
            'Comment améliorer la démocratie locale ?',
            'Faut-il instaurer le vote obligatoire ?',
            'Quelle politique culturelle pour demain ?',
            'Comment lutter contre la fracture numérique ?',
            'Faut-il réformer la fiscalité locale ?',
            'Quelle place pour les citoyens dans les décisions publiques ?',
        ];

        return $titles[array_rand($titles)];
    }

    private function generatePostContent(): string
    {
        $contents = [
            'Je pense que cette proposition est intéressante et mérite d\'être débattue. Elle répond à un vrai besoin de notre société.',
            'Je suis totalement en désaccord avec cette approche. Il faudrait plutôt envisager des solutions alternatives.',
            'Cette mesure pourrait avoir des conséquences importantes sur notre quotidien. Il faut bien peser le pour et le contre.',
            'Je soutiens pleinement cette initiative qui va dans le bon sens. C\'est une avancée nécessaire.',
            'Je m\'interroge sur la faisabilité de cette proposition. Quels seraient les moyens mis en œuvre ?',
            'Cette question est complexe et nécessite une réflexion approfondie. Merci d\'avoir lancé ce débat.',
            'Je propose que nous envisagions également d\'autres pistes complémentaires à cette mesure.',
            'Les enjeux sont importants et je pense qu\'il faut impliquer davantage les citoyens dans cette réflexion.',
        ];

        return $contents[array_rand($contents)];
    }

    private function generateReplyContent(): string
    {
        $replies = [
            'Je suis d\'accord avec votre analyse.',
            'Intéressant point de vue, mais je pense différemment.',
            'Pourriez-vous développer votre argument ?',
            'Merci pour cette contribution constructive.',
            'Je partage votre avis sur ce point.',
            'C\'est une question pertinente que vous soulevez.',
            'Je ne suis pas sûr de bien comprendre votre position.',
            'Excellente remarque !',
        ];

        return $replies[array_rand($replies)];
    }

    private function generateVoteComment(): string
    {
        $comments = [
            'Cette mesure est nécessaire pour l\'avenir.',
            'Je ne pense pas que ce soit la bonne solution.',
            'Il faut aller plus loin dans cette direction.',
            'Cette proposition me semble équilibrée.',
            'Je m\'abstiens car je manque d\'informations.',
            'C\'est un pas dans la bonne direction.',
        ];

        return $comments[array_rand($comments)];
    }

    private function createBudgetData(): void
    {
        $this->command->info('💰 Création des données budgétaires...');

        $sectors = Sector::all();
        $regions = TerritoryRegion::all();
        $departments = TerritoryDepartment::all();

        // 1. Créer les recettes publiques nationales pour 2024-2025
        $this->createPublicRevenue($sectors, $regions, $departments);

        // 2. Créer les dépenses publiques par secteur pour 2024-2025
        $this->createPublicSpend($sectors, $regions, $departments);

        // 3. Créer les allocations budgétaires des citoyens
        $this->createUserAllocations($sectors);

        $this->command->info('✓ Données budgétaires créées');
    }

    private function createPublicRevenue($sectors, $regions, $departments): void
    {
        $this->command->info('  → Création des recettes publiques...');

        $years = [2024, 2025];

        foreach ($years as $year) {
            // Recettes nationales (budget de l'État français)
            $recettesNationales = [
                ['category' => 'TVA', 'amount' => 93000000000, 'source' => 'DGFiP'],
                ['category' => 'Impôt sur le revenu', 'amount' => 87000000000, 'source' => 'DGFiP'],
                ['category' => 'Impôt sur les sociétés', 'amount' => 71000000000, 'source' => 'DGFiP'],
                ['category' => 'TICPE (taxe carburants)', 'amount' => 13000000000, 'source' => 'DGFiP'],
                ['category' => 'Autres impôts directs', 'amount' => 25000000000, 'source' => 'DGFiP'],
                ['category' => 'Autres impôts indirects', 'amount' => 31000000000, 'source' => 'DGFiP'],
                ['category' => 'Recettes non fiscales', 'amount' => 15000000000, 'source' => 'DGFiP'],
            ];

            foreach ($recettesNationales as $recette) {
                PublicRevenue::create([
                    'year' => $year,
                    'scope' => 'national',
                    'category' => $recette['category'],
                    'amount' => $recette['amount'] * (1 + ($year - 2024) * 0.02), // +2% par an
                    'source' => $recette['source'],
                ]);
            }

            // Recettes régionales (quelques exemples)
            $recettesRegionales = [
                ['region' => 'Île-de-France', 'amount' => 5200000000],
                ['region' => 'Auvergne-Rhône-Alpes', 'amount' => 3100000000],
                ['region' => 'Nouvelle-Aquitaine', 'amount' => 2800000000],
                ['region' => 'Occitanie', 'amount' => 2600000000],
                ['region' => 'Hauts-de-France', 'amount' => 2400000000],
            ];

            foreach ($recettesRegionales as $data) {
                $region = $regions->where('name', $data['region'])->first();
                if ($region) {
                    PublicRevenue::create([
                        'year' => $year,
                        'scope' => 'region', // Corriger : 'regional' → 'region'
                        'region_id' => $region->id,
                        'category' => 'Dotations et fiscalité régionale',
                        'amount' => $data['amount'] * (1 + ($year - 2024) * 0.015),
                        'source' => 'Conseil Régional',
                    ]);
                }
            }

            // Recettes départementales (quelques exemples)
            $recettesDepartementales = [
                ['dept' => '75', 'amount' => 9500000000], // Paris
                ['dept' => '13', 'amount' => 2100000000], // Bouches-du-Rhône
                ['dept' => '69', 'amount' => 1800000000], // Rhône
                ['dept' => '59', 'amount' => 2500000000], // Nord
                ['dept' => '33', 'amount' => 1500000000], // Gironde
            ];

            foreach ($recettesDepartementales as $data) {
                $dept = $departments->where('code', $data['dept'])->first();
                if ($dept) {
                    PublicRevenue::create([
                        'year' => $year,
                        'scope' => 'dept', // Corriger : 'departmental' → 'dept'
                        'region_id' => $dept->region_id,
                        'department_id' => $dept->id,
                        'category' => 'Dotations et fiscalité départementale',
                        'amount' => $data['amount'] * (1 + ($year - 2024) * 0.01),
                        'source' => 'Conseil Départemental',
                    ]);
                }
            }
        }

        $this->command->info('    ✓ '.PublicRevenue::count().' recettes publiques créées');
    }

    private function createPublicSpend($sectors, $regions, $departments): void
    {
        $this->command->info('  → Création des dépenses publiques...');

        $years = [2024, 2025];

        $sectorCodeMap = [
            'education' => 'EDU',
            'sante' => 'HEALTH',
            'defense' => 'DEFENSE',
            'securite' => 'DEFENSE',
            'justice' => 'JUSTICE',
            'environnement' => 'ECO',
            'culture' => 'CULTURE',
            'sport' => 'CULTURE',
            'recherche' => 'RESEARCH',
            'economie' => 'INFRA',
            'agriculture' => 'AGRI',
            'logement' => 'INFRA',
            'transport' => 'INFRA',
            'numerique' => 'RESEARCH',
            'social' => 'SOCIAL',
            'formation' => 'EDU',
        ];

        foreach ($years as $year) {
            $depensesNationales = [
                'education' => 61000000000,
                'sante' => 8500000000,
                'defense' => 43000000000,
                'securite' => 21000000000,
                'justice' => 9500000000,
                'environnement' => 12000000000,
                'culture' => 3500000000,
                'sport' => 1200000000,
                'recherche' => 14000000000,
                'economie' => 18000000000,
                'agriculture' => 3200000000,
                'logement' => 17000000000,
                'transport' => 15000000000,
                'numerique' => 2500000000,
                'social' => 25000000000,
            ];

            foreach ($depensesNationales as $sectorCode => $amount) {
                $mappedCode = $sectorCodeMap[$sectorCode] ?? strtoupper($sectorCode);
                $sector = $sectors->where('code', $mappedCode)->first();
                if ($sector) {
                    PublicSpend::create([
                        'year' => $year,
                        'scope' => 'national',
                        'sector_id' => $sector->id,
                        'amount' => $amount * (1 + ($year - 2024) * 0.025), // +2.5% par an
                        'source' => 'Loi de finances '.$year,
                    ]);
                }
            }

            // Dépenses régionales (Île-de-France comme exemple)
            $idf = $regions->where('name', 'Île-de-France')->first();
            if ($idf) {
                $depensesRegionales = [
                    'education' => 1200000000, // Lycées
                    'transport' => 8500000000, // Transilien, métro
                    'economie' => 450000000,
                    'environnement' => 380000000,
                    'formation' => 620000000,
                ];

                foreach ($depensesRegionales as $sectorCode => $amount) {
                    $mappedCode = $sectorCodeMap[$sectorCode] ?? strtoupper($sectorCode);
                    $sector = $sectors->where('code', $mappedCode)->first();
                    if ($sector) {
                        PublicSpend::create([
                            'year' => $year,
                            'scope' => 'region', // Corriger : 'regional' → 'region'
                            'region_id' => $idf->id,
                            'sector_id' => $sector->id,
                            'amount' => $amount * (1 + ($year - 2024) * 0.02),
                            'program' => 'Budget régional Île-de-France',
                            'source' => 'Conseil Régional IDF',
                        ]);
                    }
                }
            }

            // Dépenses départementales (Paris comme exemple)
            $paris = $departments->where('code', '75')->first();
            if ($paris) {
                $depensesDepartementales = [
                    'social' => 2800000000, // Aide sociale
                    'sante' => 450000000,
                    'education' => 850000000, // Collèges
                    'culture' => 180000000,
                    'sport' => 120000000,
                    'environnement' => 220000000,
                ];

                foreach ($depensesDepartementales as $sectorCode => $amount) {
                    $mappedCode = $sectorCodeMap[$sectorCode] ?? strtoupper($sectorCode);
                    $sector = $sectors->where('code', $mappedCode)->first();
                    if ($sector) {
                        PublicSpend::create([
                            'year' => $year,
                            'scope' => 'dept', // Corriger : 'departmental' → 'dept'
                            'region_id' => $paris->region_id,
                            'department_id' => $paris->id,
                            'sector_id' => $sector->id,
                            'amount' => $amount * (1 + ($year - 2024) * 0.015),
                            'program' => 'Budget départemental Paris',
                            'source' => 'Conseil de Paris',
                        ]);
                    }
                }
            }
        }

        $this->command->info('    ✓ '.PublicSpend::count().' dépenses publiques créées');
    }

    private function createUserAllocations($sectors): void
    {
        $this->command->info('  → Création des allocations budgétaires citoyennes...');

        $allocationsCount = 0;

        // Créer des allocations pour 30 citoyens (sur les 50)
        $citoyensWithAllocations = array_slice($this->citoyens, 0, 30);

        foreach ($citoyensWithAllocations as $citoyen) {
            // Générer une répartition aléatoire mais cohérente
            $allocation = $this->generateRandomAllocation($sectors);

            foreach ($allocation as $sectorId => $percent) {
                UserAllocation::create([
                    'user_id' => $citoyen->id,
                    'sector_id' => $sectorId,
                    'percent' => $percent,
                ]);

                $allocationsCount++;
            }
        }

        $this->command->info('    ✓ '.$allocationsCount.' allocations citoyennes créées pour 30 citoyens');
    }

    private function generateRandomAllocation($sectors): array
    {
        $allocation = [];
        $remaining = 100.0;

        $activeSectors = $sectors->where('is_active', true)->shuffle();

        foreach ($activeSectors as $index => $sector) {
            if ($index === $activeSectors->count() - 1) {
                // Dernier secteur : on alloue le reste
                $percent = round($remaining, 2);
            } else {
                // Générer un pourcentage aléatoire dans les limites
                $minPercent = max($sector->min_percent, 0);
                $maxPercent = min($sector->max_percent, $remaining);

                if ($maxPercent <= $minPercent) {
                    $percent = $minPercent;
                } else {
                    $percent = round(rand($minPercent * 100, $maxPercent * 100) / 100, 2);
                }

                $remaining -= $percent;
            }

            // S'assurer que le pourcentage est dans les limites
            $percent = max($sector->min_percent, min($sector->max_percent, $percent));

            if ($percent > 0) {
                $allocation[$sector->id] = $percent;
            }
        }

        // Normaliser pour que la somme soit exactement 100%
        $total = array_sum($allocation);
        if ($total != 100.0) {
            $diff = 100.0 - $total;
            // Ajouter/retirer la différence au secteur le plus important
            arsort($allocation);
            $firstKey = array_key_first($allocation);
            $allocation[$firstKey] = round($allocation[$firstKey] + $diff, 2);
        }

        return $allocation;
    }

    private function createReportsAndSanctions(): void
    {
        $this->command->info('🚨 Création de signalements et sanctions...');

        $moderator = User::role('moderator')->first();
        $reportsCount = 0;
        $sanctionsCount = 0;

        // Créer 15-20 signalements sur des posts
        $posts = Post::inRandomOrder()->limit(20)->get();

        foreach ($posts as $post) {
            if (rand(0, 100) > 30) { // 70% de chances de créer un signalement
                $reporter = $this->citoyens[array_rand($this->citoyens)];

                $reasons = ['spam', 'harassment', 'misinformation', 'off_topic', 'inappropriate']; // Corriger 'hate_speech' → 'inappropriate'
                $reason = $reasons[array_rand($reasons)];

                $statuses = ['pending', 'reviewing', 'resolved', 'dismissed']; // Corriger 'under_review' → 'reviewing'
                $status = $statuses[array_rand($statuses)];

                $report = Report::create([
                    'reporter_id' => $reporter->id,
                    'reportable_type' => Post::class,
                    'reportable_id' => $post->id,
                    'reason' => $reason,
                    'description' => $this->generateReportDescription($reason),
                    'status' => $status,
                    'moderator_id' => in_array($status, ['reviewing', 'resolved', 'dismissed']) ? $moderator->id : null, // Corriger 'under_review' → 'reviewing'
                    'moderator_notes' => $status === 'resolved' ? 'Signalement traité, contenu modéré.' : null,
                    'resolved_at' => $status === 'resolved' ? Carbon::now()->subDays(rand(1, 10)) : null,
                ]);

                $reportsCount++;

                // Créer une sanction si le signalement est résolu
                if ($status === 'resolved' && rand(0, 100) > 40) { // 60% de sanctions
                    $sanctionTypes = ['warning', 'mute', 'ban'];
                    $weights = [60, 30, 10]; // Warning plus fréquent
                    $type = $this->weightedRandom($sanctionTypes, $weights);

                    $durations = [
                        'warning' => null,
                        'mute' => rand(1, 7), // 1-7 jours
                        'ban' => rand(7, 30), // 7-30 jours
                    ];

                    Sanction::create([
                        'user_id' => $post->user_id,
                        'moderator_id' => $moderator->id,
                        'report_id' => $report->id,
                        'type' => $type,
                        'reason' => $this->generateSanctionReason($reason),
                        'starts_at' => Carbon::now()->subDays(rand(1, 5)),
                        'expires_at' => $durations[$type] ? Carbon::now()->addDays($durations[$type] - rand(1, 3)) : null,
                        'is_active' => rand(0, 100) > 30, // 70% actives
                    ]);

                    $sanctionsCount++;
                }
            }
        }

        $this->command->info("✓ $reportsCount signalements créés");
        $this->command->info("✓ $sanctionsCount sanctions créées");
    }

    private function generateReportDescription(string $reason): string
    {
        $descriptions = [
            'spam' => 'Ce message contient du spam et n\'apporte rien au débat.',
            'harassment' => 'Ce commentaire contient des propos harcelants envers d\'autres utilisateurs.',
            'misinformation' => 'Ce post contient des informations manifestement fausses.',
            'off_topic' => 'Ce message est hors-sujet et n\'a pas sa place dans ce débat.',
            'hate_speech' => 'Ce contenu contient des propos haineux inacceptables.',
        ];

        return $descriptions[$reason] ?? 'Contenu inapproprié.';
    }

    private function generateSanctionReason(string $reportReason): string
    {
        $reasons = [
            'spam' => 'Publication répétée de contenu non pertinent',
            'harassment' => 'Harcèlement d\'autres utilisateurs',
            'misinformation' => 'Diffusion d\'informations fausses',
            'off_topic' => 'Messages répétés hors-sujet',
            'hate_speech' => 'Propos haineux',
        ];

        return $reasons[$reportReason] ?? 'Violation des règles de la communauté';
    }

    private function weightedRandom(array $values, array $weights): mixed
    {
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);

        $currentWeight = 0;
        foreach ($values as $index => $value) {
            $currentWeight += $weights[$index];
            if ($random <= $currentWeight) {
                return $value;
            }
        }

        return $values[0];
    }

    private function createDocumentsAndVerifications(): void
    {
        $this->command->info('📄 Création de documents et vérifications...');

        $journalist = User::role('journalist')->first();
        $documentsCount = 0;
        $verificationsCount = 0;

        // Créer 10-15 documents attachés aux propositions de loi
        foreach (array_slice($this->propositions, 0, 15) as $proposition) {
            $depute = $this->deputes[array_rand($this->deputes)];

            $documentTypes = [
                ['title' => 'Étude d\'impact', 'filename' => 'etude_impact.pdf'],
                ['title' => 'Rapport de commission', 'filename' => 'rapport_commission.pdf'],
                ['title' => 'Avis du Conseil d\'État', 'filename' => 'avis_conseil_etat.pdf'],
                ['title' => 'Texte adopté en commission', 'filename' => 'texte_commission.pdf'],
                ['title' => 'Amendements adoptés', 'filename' => 'amendements.pdf'],
            ];

            $docType = $documentTypes[array_rand($documentTypes)];

            $statuses = ['pending', 'verified', 'rejected'];
            $status = $statuses[array_rand($statuses)];

            $document = Document::create([
                'title' => $docType['title'].' - '.mb_substr($proposition->titre, 0, 50, 'UTF-8'),
                'description' => 'Document officiel relatif à la proposition de loi.',
                'filename' => $docType['filename'],
                'path' => 'documents/propositions/'.$proposition->id.'/'.$docType['filename'],
                'mime_type' => 'application/pdf',
                'size' => rand(100000, 5000000), // 100KB - 5MB
                'hash' => hash('sha256', uniqid()),
                'documentable_type' => PropositionLoi::class,
                'documentable_id' => $proposition->id,
                'uploader_id' => $depute['user']->id,
                'status' => $status,
                'is_public' => true,
            ]);

            $documentsCount++;

            // Créer une vérification si le document n'est pas pending
            if ($status !== 'pending' && $journalist) {
                $verificationStatuses = ['verified', 'rejected', 'needs_review'];
                $verificationStatus = $status === 'verified' ? 'verified' : ($status === 'rejected' ? 'rejected' : $verificationStatuses[array_rand($verificationStatuses)]);

                Verification::create([
                    'document_id' => $document->id,
                    'verifier_id' => $journalist->id,
                    'status' => $verificationStatus,
                    'notes' => $this->generateVerificationNotes($verificationStatus),
                    'metadata' => [
                        'verification_date' => Carbon::now()->subDays(rand(1, 30))->toDateString(),
                        'method' => 'manual_review',
                    ],
                ]);

                $verificationsCount++;
            }
        }

        // Créer quelques documents sur des topics
        foreach (array_slice($this->topics, 0, 5) as $topic) {
            $author = User::find($topic->author_id);

            $document = Document::create([
                'title' => 'Pièce jointe - '.mb_substr($topic->title, 0, 50, 'UTF-8'),
                'description' => 'Document complémentaire au débat.',
                'filename' => 'document_'.uniqid().'.pdf',
                'path' => 'documents/topics/'.$topic->id.'/document.pdf',
                'mime_type' => 'application/pdf',
                'size' => rand(50000, 2000000),
                'hash' => hash('sha256', uniqid()),
                'documentable_type' => Topic::class,
                'documentable_id' => $topic->id,
                'uploader_id' => $author->id,
                'status' => 'verified',
                'is_public' => true,
            ]);

            $documentsCount++;
        }

        $this->command->info("✓ $documentsCount documents créés");
        $this->command->info("✓ $verificationsCount vérifications créées");
    }

    private function generateVerificationNotes(string $status): string
    {
        $notes = [
            'verified' => 'Document authentique vérifié. Source officielle confirmée.',
            'rejected' => 'Document non authentifiable. Source douteuse.',
            'needs_review' => 'Nécessite une vérification supplémentaire par un expert.',
        ];

        return $notes[$status] ?? 'Vérification en cours.';
    }

    private function createAchievements(): void
    {
        $this->command->info('🏆 Création des achievements et attribution...');

        // Les achievements devraient déjà exister via AchievementSeeder
        $achievements = Achievement::all();

        if ($achievements->isEmpty()) {
            $this->command->warn('⚠️  Aucun achievement trouvé, création...');
            $this->call(\Database\Seeders\AchievementSeeder::class);
            $achievements = Achievement::all();
        }

        $userAchievementsCount = 0;

        // Attribuer des achievements aux citoyens actifs
        foreach ($this->citoyens as $citoyen) {
            // Chaque citoyen a 30-70% de chances d'avoir des achievements
            if (rand(0, 100) > 30) {
                $numAchievements = rand(1, 5);
                $citizenAchievements = $achievements->random(min($numAchievements, $achievements->count()));

                foreach ($citizenAchievements as $achievement) {
                    try {
                        UserAchievement::create([
                            'user_id' => $citoyen->id,
                            'achievement_id' => $achievement->id,
                            'unlocked_at' => Carbon::now()->subDays(rand(1, 60)),
                            'progress' => 100, // Achievement débloqué
                        ]);

                        $userAchievementsCount++;
                    } catch (\Exception $e) {
                        // Ignorer les doublons
                    }
                }
            }
        }

        // Attribuer quelques achievements aux députés
        foreach (array_slice($this->deputes, 0, 10) as $depute) {
            $deputeAchievements = $achievements->where('category', 'legislation')->random(min(2, $achievements->where('category', 'legislation')->count()));

            foreach ($deputeAchievements as $achievement) {
                try {
                    UserAchievement::create([
                        'user_id' => $depute['user']->id,
                        'achievement_id' => $achievement->id,
                        'unlocked_at' => Carbon::now()->subDays(rand(1, 90)),
                        'progress' => 100,
                    ]);

                    $userAchievementsCount++;
                } catch (\Exception $e) {
                    // Ignorer les doublons
                }
            }
        }

        $this->command->info("✓ $userAchievementsCount achievements attribués");
    }

    private function createNotifications(): void
    {
        $this->command->info('🔔 Création de notifications...');

        $notificationsCount = 0;

        // Créer des notifications pour 20 citoyens
        foreach (array_slice($this->citoyens, 0, 20) as $citoyen) {
            $numNotifications = rand(2, 8);

            for ($i = 0; $i < $numNotifications; $i++) {
                $types = [
                    'new_post_reply',
                    'vote_result',
                    'achievement_unlocked',
                    'new_proposition',
                    'topic_closed',
                    'document_verified',
                ];

                $type = $types[array_rand($types)];
                $isRead = rand(0, 100) > 40; // 60% lues

                Notification::create([
                    'user_id' => $citoyen->id,
                    'type' => $type,
                    'title' => $this->generateNotificationTitle($type),
                    'message' => $this->generateNotificationMessage($type),
                    'data' => $this->generateNotificationData($type),
                    'read_at' => $isRead ? Carbon::now()->subDays(rand(1, 10)) : null,
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                ]);

                $notificationsCount++;
            }
        }

        $this->command->info("✓ $notificationsCount notifications créées");
    }

    private function generateNotificationTitle(string $type): string
    {
        $titles = [
            'new_post_reply' => 'Nouvelle réponse à votre message',
            'vote_result' => 'Résultats du vote disponibles',
            'achievement_unlocked' => 'Nouveau badge débloqué !',
            'new_proposition' => 'Nouvelle proposition de loi',
            'topic_closed' => 'Débat clôturé',
            'document_verified' => 'Document vérifié',
        ];

        return $titles[$type] ?? 'Notification';
    }

    private function generateNotificationMessage(string $type): string
    {
        $messages = [
            'new_post_reply' => 'Un utilisateur a répondu à votre message dans le débat.',
            'vote_result' => 'Les résultats du vote sur la proposition sont maintenant disponibles.',
            'achievement_unlocked' => 'Félicitations ! Vous avez débloqué un nouveau badge.',
            'new_proposition' => 'Une nouvelle proposition de loi a été déposée dans votre thématique favorite.',
            'topic_closed' => 'Le débat auquel vous avez participé a été clôturé.',
            'document_verified' => 'Le document que vous avez consulté a été vérifié par un journaliste.',
        ];

        return $messages[$type] ?? 'Vous avez une nouvelle notification.';
    }

    private function generateNotificationData(string $type): array
    {
        return [
            'type' => $type,
            'timestamp' => Carbon::now()->subDays(rand(1, 30))->toIso8601String(),
            'priority' => rand(0, 100) > 70 ? 'high' : 'normal',
        ];
    }

    private function displayStats(): void
    {
        $this->command->newLine();
        $this->command->table(
            ['Type de données', 'Quantité'],
            [
                ['👥 Citoyens', count($this->citoyens)],
                ['🏛️ Députés', count($this->deputes)],
                ['🏛️ Groupes parlementaires', GroupeParlementaire::count()],
                ['📜 Propositions de loi', count($this->propositions)],
                ['🗳️ Votes législatifs', VoteLegislatif::count()],
                ['🏛️ Votes par groupe', VoteGroupeParlementaire::count()],
                ['📝 Amendements', Amendement::count()],
                ['💬 Topics de débat', count($this->topics)],
                ['💭 Posts et réponses', Post::count()],
                ['🗳️ Votes citoyens', VotePropositionLoi::count()],
                ['📅 Événements législatifs', AgendaLegislatif::count()],
                ['⚖️ Références juridiques', LegalReference::count()],
                ['#️⃣ Hashtags', Hashtag::count()],
                ['🎯 Thématiques', ThematiqueLegislation::count()],
                ['💰 Recettes publiques', PublicRevenue::count()],
                ['💸 Dépenses publiques', PublicSpend::count()],
                ['📊 Allocations citoyennes', UserAllocation::count()],
                ['🏦 Secteurs budgétaires', Sector::count()],
                ['🚨 Signalements', Report::count()],
                ['⚠️ Sanctions', Sanction::count()],
                ['📄 Documents', Document::count()],
                ['✅ Vérifications', Verification::count()],
                ['🏆 Achievements attribués', UserAchievement::count()],
                ['🔔 Notifications', Notification::count()],
            ]
        );

        $this->command->newLine();
        $this->command->info('🔐 Identifiants de connexion démo :');
        $this->command->info('   Email : citoyen1@demo.civicdash.fr à citoyen50@demo.civicdash.fr');
        $this->command->info('   Mot de passe : demo2025');
        $this->command->newLine();
        $this->command->info('🏛️ Députés : depute1@demo.assemblee-nationale.fr à depute50@demo.assemblee-nationale.fr');
        $this->command->info('   Mot de passe : demo2025');
        $this->command->newLine();

        // Afficher la répartition des groupes
        $this->command->info('📊 Répartition des groupes parlementaires :');
        $repartition = [];
        foreach ($this->deputes as $depute) {
            $groupe = $this->groupes[$depute['groupe']]['sigle'];
            $repartition[$groupe] = ($repartition[$groupe] ?? 0) + 1;
        }
        arsort($repartition);
        foreach ($repartition as $groupe => $count) {
            $this->command->info("   → $groupe: $count députés");
        }
    }
}
