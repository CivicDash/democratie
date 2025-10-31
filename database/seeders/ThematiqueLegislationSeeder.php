<?php

namespace Database\Seeders;

use App\Models\ThematiqueLegislation;
use Illuminate\Database\Seeder;

class ThematiqueLegislationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $thematiques = [
            [
                'code' => 'SECU',
                'nom' => 'Sécurité & Justice',
                'description' => 'Sécurité publique, justice, police, gendarmerie, droit pénal, prisons, cybersécurité',
                'couleur_hex' => '#DC2626',
                'icone' => '🛡️',
                'ordre' => 1,
                'mots_cles' => [
                    'sécurité', 'police', 'gendarmerie', 'justice', 'tribunal', 'juge', 'procureur',
                    'prison', 'détenu', 'délinquance', 'criminalité', 'terrorisme', 'cyber',
                    'vidéo-protection', 'surveillance', 'pénal', 'procès', 'condamnation'
                ],
                'synonymes' => [
                    'sûreté', 'forces de l\'ordre', 'magistrat', 'ordre public', 'délit', 'crime'
                ],
            ],
            [
                'code' => 'FISC',
                'nom' => 'Finance & Fiscalité',
                'description' => 'Budget de l\'État, impôts, taxes, TVA, fiscalité, finances publiques',
                'couleur_hex' => '#059669',
                'icone' => '💰',
                'ordre' => 2,
                'mots_cles' => [
                    'impôt', 'taxe', 'TVA', 'ISF', 'IFI', 'budget', 'fiscal', 'finances publiques',
                    'déficit', 'dette', 'contribuable', 'prélèvement', 'douane', 'fisc',
                    'dépense publique', 'recette', 'trésor public', 'bercy'
                ],
                'synonymes' => [
                    'fiscalité', 'trésorerie', 'contribution', 'imposition', 'budget de l\'État'
                ],
            ],
            [
                'code' => 'SANTE',
                'nom' => 'Santé & Protection sociale',
                'description' => 'Santé publique, hôpitaux, sécurité sociale, retraites, assurance maladie, aide sociale',
                'couleur_hex' => '#3B82F6',
                'icone' => '🏥',
                'ordre' => 3,
                'mots_cles' => [
                    'santé', 'hôpital', 'médecin', 'sécurité sociale', 'assurance maladie', 'sécu',
                    'retraite', 'pension', 'handicap', 'soins', 'médicament', 'pharma',
                    'épidémie', 'pandémie', 'vaccin', 'patient', 'aide sociale', 'RSA', 'allocation'
                ],
                'synonymes' => [
                    'système de santé', 'protection sociale', 'couverture santé', 'aide sociale'
                ],
            ],
            [
                'code' => 'EDUC',
                'nom' => 'Éducation & Recherche',
                'description' => 'Éducation nationale, enseignement, université, recherche scientifique, formation',
                'couleur_hex' => '#8B5CF6',
                'icone' => '🎓',
                'ordre' => 4,
                'mots_cles' => [
                    'éducation', 'école', 'collège', 'lycée', 'université', 'enseignement', 'professeur',
                    'étudiant', 'élève', 'formation', 'diplôme', 'recherche', 'scientifique',
                    'CNRS', 'campus', 'baccalauréat', 'parcoursup', 'apprentissage'
                ],
                'synonymes' => [
                    'système éducatif', 'enseignement supérieur', 'éducation nationale', 'instruction'
                ],
            ],
            [
                'code' => 'ENVT',
                'nom' => 'Environnement & Climat',
                'description' => 'Écologie, climat, transition énergétique, biodiversité, pollution, développement durable',
                'couleur_hex' => '#10B981',
                'icone' => '🌍',
                'ordre' => 5,
                'mots_cles' => [
                    'environnement', 'écologie', 'climat', 'réchauffement', 'carbone', 'CO2',
                    'pollution', 'biodiversité', 'transition énergétique', 'renouvelable',
                    'déchet', 'recyclage', 'nucléaire', 'eau', 'air', 'forêt', 'faune', 'flore'
                ],
                'synonymes' => [
                    'écologique', 'climatique', 'développement durable', 'transition écologique'
                ],
            ],
            [
                'code' => 'ECO',
                'nom' => 'Économie & Entreprises',
                'description' => 'Économie, entreprises, commerce, industrie, emploi, chômage, travail',
                'couleur_hex' => '#F59E0B',
                'icone' => '🏭',
                'ordre' => 6,
                'mots_cles' => [
                    'économie', 'entreprise', 'commerce', 'industrie', 'emploi', 'chômage',
                    'travail', 'salarié', 'employeur', 'PME', 'startup', 'innovation',
                    'croissance', 'PIB', 'investissement', 'marché', 'concurrence'
                ],
                'synonymes' => [
                    'économique', 'professionnel', 'secteur privé', 'tissu économique'
                ],
            ],
            [
                'code' => 'LOG',
                'nom' => 'Logement & Urbanisme',
                'description' => 'Logement, construction, urbanisme, aménagement du territoire, HLM',
                'couleur_hex' => '#EC4899',
                'icone' => '🏠',
                'ordre' => 7,
                'mots_cles' => [
                    'logement', 'habitat', 'construction', 'urbanisme', 'aménagement', 'territoire',
                    'HLM', 'loyer', 'propriétaire', 'locataire', 'immobilier', 'bâtiment',
                    'ville', 'commune', 'métropole', 'ZAC'
                ],
                'synonymes' => [
                    'habitation', 'immobilier', 'aménagement urbain', 'politique du logement'
                ],
            ],
            [
                'code' => 'AGRI',
                'nom' => 'Agriculture & Alimentation',
                'description' => 'Agriculture, élevage, pêche, alimentation, PAC, bio, agroalimentaire',
                'couleur_hex' => '#84CC16',
                'icone' => '🌾',
                'ordre' => 8,
                'mots_cles' => [
                    'agriculture', 'agriculteur', 'paysan', 'élevage', 'pêche', 'alimentation',
                    'PAC', 'bio', 'agroalimentaire', 'ferme', 'exploitation', 'rural',
                    'pesticide', 'engrais', 'culture', 'bétail', 'viande', 'lait'
                ],
                'synonymes' => [
                    'agricole', 'agraire', 'filière alimentaire', 'monde rural'
                ],
            ],
            [
                'code' => 'TRANS',
                'nom' => 'Énergie & Transports',
                'description' => 'Transports, mobilité, routes, trains, énergie, électricité, carburant',
                'couleur_hex' => '#EF4444',
                'icone' => '⚡',
                'ordre' => 9,
                'mots_cles' => [
                    'transport', 'mobilité', 'route', 'autoroute', 'train', 'SNCF', 'métro',
                    'bus', 'voiture', 'vélo', 'aviation', 'aéroport', 'énergie', 'électricité',
                    'carburant', 'essence', 'diesel', 'pétrole', 'gaz', 'EDF'
                ],
                'synonymes' => [
                    'déplacement', 'circulation', 'énergétique', 'secteur des transports'
                ],
            ],
            [
                'code' => 'NUM',
                'nom' => 'Numérique & Technologies',
                'description' => 'Numérique, internet, télécoms, technologies, IA, data, cybersécurité',
                'couleur_hex' => '#06B6D4',
                'icone' => '🌐',
                'ordre' => 10,
                'mots_cles' => [
                    'numérique', 'digital', 'internet', 'web', 'télécommunication', 'télécom',
                    'technologie', 'innovation', 'IA', 'intelligence artificielle', 'data',
                    'données', 'algorithme', 'RGPD', 'CNIL', 'cybersécurité', 'informatique'
                ],
                'synonymes' => [
                    'tech', 'IT', 'high-tech', 'transformation numérique', 'digital'
                ],
            ],
            [
                'code' => 'INST',
                'nom' => 'Institutions & Démocratie',
                'description' => 'Institutions, démocratie, élections, assemblée, sénat, collectivités, fonction publique',
                'couleur_hex' => '#6366F1',
                'icone' => '🗳️',
                'ordre' => 11,
                'mots_cles' => [
                    'institution', 'démocratie', 'élection', 'vote', 'scrutin', 'assemblée',
                    'sénat', 'député', 'sénateur', 'collectivité', 'région', 'département',
                    'commune', 'maire', 'fonction publique', 'fonctionnaire', 'administration'
                ],
                'synonymes' => [
                    'institutionnel', 'démocratique', 'électoral', 'république', 'État'
                ],
            ],
            [
                'code' => 'INTER',
                'nom' => 'International & Défense',
                'description' => 'Relations internationales, Union européenne, défense, armée, diplomatie, sécurité nationale',
                'couleur_hex' => '#7C3AED',
                'icone' => '🌍',
                'ordre' => 12,
                'mots_cles' => [
                    'international', 'Europe', 'Union européenne', 'UE', 'défense', 'armée',
                    'militaire', 'soldat', 'OTAN', 'diplomatie', 'guerre', 'conflit',
                    'traité', 'accord', 'coopération', 'souveraineté', 'sécurité nationale'
                ],
                'synonymes' => [
                    'extérieur', 'européen', 'géopolitique', 'forces armées'
                ],
            ],
            [
                'code' => 'CULT',
                'nom' => 'Culture & Médias',
                'description' => 'Culture, patrimoine, médias, presse, audiovisuel, arts, spectacle',
                'couleur_hex' => '#F97316',
                'icone' => '🎭',
                'ordre' => 13,
                'mots_cles' => [
                    'culture', 'culturel', 'patrimoine', 'musée', 'monument', 'média',
                    'presse', 'journal', 'audiovisuel', 'télévision', 'radio', 'cinéma',
                    'spectacle', 'théâtre', 'arts', 'artiste', 'livre', 'édition'
                ],
                'synonymes' => [
                    'culturel', 'médiatique', 'artistique', 'secteur culturel'
                ],
            ],
            [
                'code' => 'DROIT',
                'nom' => 'Droits & Libertés',
                'description' => 'Droits de l\'homme, libertés publiques, égalité, discrimination, laïcité, vie privée',
                'couleur_hex' => '#14B8A6',
                'icone' => '⚖️',
                'ordre' => 14,
                'mots_cles' => [
                    'droit', 'liberté', 'égalité', 'discrimination', 'racisme', 'sexisme',
                    'laïcité', 'religion', 'vie privée', 'RGPD', 'données personnelles',
                    'droits de l\'homme', 'minorité', 'LGBT', 'femme', 'enfant', 'handicap'
                ],
                'synonymes' => [
                    'libertés publiques', 'droits fondamentaux', 'égalité des chances'
                ],
            ],
            [
                'code' => 'IMMIG',
                'nom' => 'Immigration & Intégration',
                'description' => 'Immigration, intégration, asile, nationalité, étrangers, naturalisation',
                'couleur_hex' => '#A855F7',
                'icone' => '👥',
                'ordre' => 15,
                'mots_cles' => [
                    'immigration', 'immigré', 'migrant', 'étranger', 'asile', 'réfugié',
                    'naturalisation', 'nationalité', 'intégration', 'visa', 'titre de séjour',
                    'expulsion', 'reconduite', 'frontière', 'Schengen', 'sans-papier'
                ],
                'synonymes' => [
                    'migratoire', 'étranger', 'immigration', 'politique migratoire'
                ],
            ],
        ];

        foreach ($thematiques as $thematique) {
            ThematiqueLegislation::updateOrCreate(
                ['code' => $thematique['code']],
                $thematique
            );
        }

        $this->command->info('✅ 15 thématiques législatives créées avec succès !');
    }
}

