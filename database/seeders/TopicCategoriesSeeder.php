<?php

namespace Database\Seeders;

use App\Models\TopicCategory;
use Illuminate\Database\Seeder;

class TopicCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Politique & Institutions',
                'slug' => 'politique-institutions',
                'description' => 'Débats sur les institutions, la démocratie, les élections et la vie politique.',
                'icon' => '🏛️',
                'color' => '#3B82F6',
                'order' => 1,
            ],
            [
                'name' => 'Économie & Emploi',
                'slug' => 'economie-emploi',
                'description' => 'Discussions sur l\'économie, l\'emploi, les entreprises et le pouvoir d\'achat.',
                'icon' => '💼',
                'color' => '#10B981',
                'order' => 2,
            ],
            [
                'name' => 'Santé & Social',
                'slug' => 'sante-social',
                'description' => 'Santé publique, protection sociale, retraites et solidarité.',
                'icon' => '🏥',
                'color' => '#EF4444',
                'order' => 3,
            ],
            [
                'name' => 'Éducation & Culture',
                'slug' => 'education-culture',
                'description' => 'Système éducatif, enseignement supérieur, culture et patrimoine.',
                'icon' => '📚',
                'color' => '#8B5CF6',
                'order' => 4,
            ],
            [
                'name' => 'Environnement & Climat',
                'slug' => 'environnement-climat',
                'description' => 'Transition écologique, énergies renouvelables et protection de l\'environnement.',
                'icon' => '🌍',
                'color' => '#059669',
                'order' => 5,
            ],
            [
                'name' => 'Justice & Sécurité',
                'slug' => 'justice-securite',
                'description' => 'Justice, police, sécurité publique et droits fondamentaux.',
                'icon' => '⚖️',
                'color' => '#DC2626',
                'order' => 6,
            ],
            [
                'name' => 'Numérique & Innovation',
                'slug' => 'numerique-innovation',
                'description' => 'Technologies, innovation, intelligence artificielle et transformation numérique.',
                'icon' => '💻',
                'color' => '#6366F1',
                'order' => 7,
            ],
            [
                'name' => 'Logement & Urbanisme',
                'slug' => 'logement-urbanisme',
                'description' => 'Politique du logement, aménagement du territoire et urbanisme.',
                'icon' => '🏘️',
                'color' => '#F59E0B',
                'order' => 8,
            ],
            [
                'name' => 'Transport & Mobilité',
                'slug' => 'transport-mobilite',
                'description' => 'Transports en commun, infrastructures et mobilité durable.',
                'icon' => '🚇',
                'color' => '#14B8A6',
                'order' => 9,
            ],
            [
                'name' => 'International & Europe',
                'slug' => 'international-europe',
                'description' => 'Relations internationales, Union européenne et diplomatie.',
                'icon' => '🌐',
                'color' => '#0EA5E9',
                'order' => 10,
            ],
            [
                'name' => 'Agriculture & Alimentation',
                'slug' => 'agriculture-alimentation',
                'description' => 'Agriculture, alimentation, ruralité et circuits courts.',
                'icon' => '🌾',
                'color' => '#84CC16',
                'order' => 11,
            ],
            [
                'name' => 'Autres sujets',
                'slug' => 'autres',
                'description' => 'Tous les autres sujets de débat citoyen.',
                'icon' => '💬',
                'color' => '#6B7280',
                'order' => 99,
            ],
        ];

        foreach ($categories as $category) {
            TopicCategory::create($category);
        }

        $this->command->info('✅ ' . count($categories) . ' catégories de topics créées');
    }
}

