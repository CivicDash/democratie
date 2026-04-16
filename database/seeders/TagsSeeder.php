<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagsSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            // Thématiques principales
            ['slug' => 'environnement', 'name' => 'Environnement', 'color' => '#10B981', 'icon' => '🌱', 'description' => 'Écologie, climat, transition énergétique'],
            ['slug' => 'sante', 'name' => 'Santé', 'color' => '#EF4444', 'icon' => '🏥', 'description' => 'Système de santé, hôpitaux, médicaments'],
            ['slug' => 'education', 'name' => 'Éducation', 'color' => '#3B82F6', 'icon' => '🎓', 'description' => 'École, université, formation'],
            ['slug' => 'economie', 'name' => 'Économie', 'color' => '#F59E0B', 'icon' => '💼', 'description' => 'Budget, fiscalité, entreprises'],
            ['slug' => 'justice', 'name' => 'Justice', 'color' => '#8B5CF6', 'icon' => '⚖️', 'description' => 'Système judiciaire, droits, libertés'],
            ['slug' => 'securite', 'name' => 'Sécurité', 'color' => '#DC2626', 'icon' => '🛡️', 'description' => 'Police, défense, terrorisme'],
            ['slug' => 'social', 'name' => 'Social', 'color' => '#EC4899', 'icon' => '🤝', 'description' => 'Solidarité, protection sociale, retraites'],
            ['slug' => 'travail', 'name' => 'Travail', 'color' => '#6366F1', 'icon' => '👷', 'description' => 'Emploi, droit du travail, chômage'],
            ['slug' => 'logement', 'name' => 'Logement', 'color' => '#14B8A6', 'icon' => '🏠', 'description' => 'Habitat, urbanisme, loyers'],
            ['slug' => 'transport', 'name' => 'Transport', 'color' => '#06B6D4', 'icon' => '🚆', 'description' => 'Mobilité, infrastructures, transports publics'],
            ['slug' => 'numerique', 'name' => 'Numérique', 'color' => '#0EA5E9', 'icon' => '💻', 'description' => 'Technologies, données, cybersécurité'],
            ['slug' => 'agriculture', 'name' => 'Agriculture', 'color' => '#84CC16', 'icon' => '🌾', 'description' => 'Alimentation, élevage, pêche'],
            ['slug' => 'culture', 'name' => 'Culture', 'color' => '#A855F7', 'icon' => '🎨', 'description' => 'Arts, patrimoine, médias'],
            ['slug' => 'international', 'name' => 'International', 'color' => '#0284C7', 'icon' => '🌍', 'description' => 'Relations extérieures, Europe, coopération'],
            ['slug' => 'immigration', 'name' => 'Immigration', 'color' => '#F97316', 'icon' => '✈️', 'description' => 'Asile, intégration, frontières'],

            // Types de textes
            ['slug' => 'loi', 'name' => 'Loi', 'color' => '#1E40AF', 'icon' => '📜', 'description' => 'Projets et propositions de loi'],
            ['slug' => 'budget', 'name' => 'Budget', 'color' => '#B91C1C', 'icon' => '💰', 'description' => 'Lois de finances'],
            ['slug' => 'constitution', 'name' => 'Constitution', 'color' => '#7C2D12', 'icon' => '⚖️', 'description' => 'Révisions constitutionnelles'],
            ['slug' => 'referendum', 'name' => 'Référendum', 'color' => '#BE123C', 'icon' => '🗳️', 'description' => 'Consultations populaires'],

            // Urgence/Importance
            ['slug' => 'urgent', 'name' => 'Urgent', 'color' => '#DC2626', 'icon' => '🚨', 'description' => 'Textes en procédure accélérée'],
            ['slug' => 'important', 'name' => 'Important', 'color' => '#EA580C', 'icon' => '⭐', 'description' => 'Textes majeurs'],
            ['slug' => 'controverse', 'name' => 'Controversé', 'color' => '#C026D3', 'icon' => '⚡', 'description' => 'Débats houleux'],
        ];

        foreach ($tags as $tagData) {
            Tag::updateOrCreate(
                ['slug' => $tagData['slug']],
                $tagData
            );
        }

        $this->command->info('✅ '.count($tags).' tags créés avec succès !');
    }
}
