<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            // ═══════════════════════════════════════════════════════════════
            // PARTICIPATION - Badges pour débuter
            // ═══════════════════════════════════════════════════════════════
            [
                'code' => 'first_vote',
                'name' => 'Premier Pas',
                'description' => 'Voter pour la première fois sur un sujet citoyen',
                'icon' => '🗳️',
                'color' => 'blue',
                'category' => Achievement::CATEGORY_PARTICIPATION,
                'rarity' => Achievement::RARITY_COMMON,
                'points' => 10,
                'trigger_type' => Achievement::TRIGGER_VOTE_COUNT,
                'required_value' => 1,
                'order' => 1,
            ],
            [
                'code' => 'vote_enthusiast',
                'name' => 'Voix Active',
                'description' => 'Voter 10 fois sur des sujets citoyens',
                'icon' => '👍',
                'color' => 'green',
                'category' => Achievement::CATEGORY_PARTICIPATION,
                'rarity' => Achievement::RARITY_COMMON,
                'points' => 25,
                'trigger_type' => Achievement::TRIGGER_VOTE_COUNT,
                'required_value' => 10,
                'order' => 2,
            ],
            [
                'code' => 'vote_expert',
                'name' => 'Expert du Vote',
                'description' => 'Voter 50 fois et faire entendre votre voix !',
                'icon' => '🎯',
                'color' => 'purple',
                'category' => Achievement::CATEGORY_PARTICIPATION,
                'rarity' => Achievement::RARITY_RARE,
                'points' => 100,
                'trigger_type' => Achievement::TRIGGER_VOTE_COUNT,
                'required_value' => 50,
                'order' => 3,
            ],
            [
                'code' => 'vote_master',
                'name' => 'Maître du Vote',
                'description' => 'Voter 100 fois - Votre opinion compte !',
                'icon' => '👑',
                'color' => 'yellow',
                'category' => Achievement::CATEGORY_PARTICIPATION,
                'rarity' => Achievement::RARITY_EPIC,
                'points' => 250,
                'trigger_type' => Achievement::TRIGGER_VOTE_COUNT,
                'required_value' => 100,
                'order' => 4,
            ],

            // ═══════════════════════════════════════════════════════════════
            // CRÉATION DE CONTENU
            // ═══════════════════════════════════════════════════════════════
            [
                'code' => 'first_topic',
                'name' => 'Visionnaire',
                'description' => 'Créer votre premier sujet de débat citoyen',
                'icon' => '💡',
                'color' => 'yellow',
                'category' => Achievement::CATEGORY_PARTICIPATION,
                'rarity' => Achievement::RARITY_COMMON,
                'points' => 50,
                'trigger_type' => Achievement::TRIGGER_TOPIC_CREATED,
                'required_value' => 1,
                'order' => 5,
            ],
            [
                'code' => 'topic_creator',
                'name' => 'Créateur d\'Idées',
                'description' => 'Créer 5 sujets de débat',
                'icon' => '🌟',
                'color' => 'blue',
                'category' => Achievement::CATEGORY_PARTICIPATION,
                'rarity' => Achievement::RARITY_RARE,
                'points' => 150,
                'trigger_type' => Achievement::TRIGGER_TOPIC_CREATED,
                'required_value' => 5,
                'order' => 6,
            ],
            [
                'code' => 'first_post',
                'name' => 'Première Contribution',
                'description' => 'Publier votre premier commentaire',
                'icon' => '💬',
                'color' => 'green',
                'category' => Achievement::CATEGORY_PARTICIPATION,
                'rarity' => Achievement::RARITY_COMMON,
                'points' => 15,
                'trigger_type' => Achievement::TRIGGER_POST_CREATED,
                'required_value' => 1,
                'order' => 7,
            ],
            [
                'code' => 'active_commenter',
                'name' => 'Contributeur Actif',
                'description' => 'Publier 25 commentaires',
                'icon' => '📝',
                'color' => 'blue',
                'category' => Achievement::CATEGORY_PARTICIPATION,
                'rarity' => Achievement::RARITY_COMMON,
                'points' => 75,
                'trigger_type' => Achievement::TRIGGER_POST_CREATED,
                'required_value' => 25,
                'order' => 8,
            ],

            // ═══════════════════════════════════════════════════════════════
            // LÉGISLATIF - Suivi des lois
            // ═══════════════════════════════════════════════════════════════
            [
                'code' => 'first_legislative_vote',
                'name' => 'Citoyen Informé',
                'description' => 'Voter sur une proposition de loi',
                'icon' => '🏛️',
                'color' => 'indigo',
                'category' => Achievement::CATEGORY_LEGISLATIVE,
                'rarity' => Achievement::RARITY_COMMON,
                'points' => 20,
                'trigger_type' => Achievement::TRIGGER_LEGISLATIVE_VOTE,
                'required_value' => 1,
                'order' => 9,
            ],
            [
                'code' => 'legislative_activist',
                'name' => 'Activiste Législatif',
                'description' => 'Voter sur 10 propositions de loi',
                'icon' => '⚖️',
                'color' => 'purple',
                'category' => Achievement::CATEGORY_LEGISLATIVE,
                'rarity' => Achievement::RARITY_RARE,
                'points' => 100,
                'trigger_type' => Achievement::TRIGGER_LEGISLATIVE_VOTE,
                'required_value' => 10,
                'order' => 10,
            ],
            [
                'code' => 'legislative_expert',
                'name' => 'Expert Législatif',
                'description' => 'Suivre 20 propositions de loi',
                'icon' => '📜',
                'color' => 'yellow',
                'category' => Achievement::CATEGORY_LEGISLATIVE,
                'rarity' => Achievement::RARITY_EPIC,
                'points' => 200,
                'trigger_type' => Achievement::TRIGGER_FOLLOW_COUNT,
                'required_value' => 20,
                'order' => 11,
            ],

            // ═══════════════════════════════════════════════════════════════
            // BUDGET - Allocation citoyenne
            // ═══════════════════════════════════════════════════════════════
            [
                'code' => 'first_budget',
                'name' => 'Budget Citoyen',
                'description' => 'Effectuer votre première allocation budgétaire',
                'icon' => '💰',
                'color' => 'green',
                'category' => Achievement::CATEGORY_BUDGET,
                'rarity' => Achievement::RARITY_COMMON,
                'points' => 30,
                'trigger_type' => Achievement::TRIGGER_BUDGET_ALLOCATION,
                'required_value' => 1,
                'order' => 12,
            ],
            [
                'code' => 'budget_master',
                'name' => 'Maître du Budget',
                'description' => 'Allouer le budget 5 fois',
                'icon' => '💎',
                'color' => 'purple',
                'category' => Achievement::CATEGORY_BUDGET,
                'rarity' => Achievement::RARITY_RARE,
                'points' => 120,
                'trigger_type' => Achievement::TRIGGER_BUDGET_ALLOCATION,
                'required_value' => 5,
                'order' => 13,
            ],

            // ═══════════════════════════════════════════════════════════════
            // SOCIAL - Reconnaissance communautaire
            // ═══════════════════════════════════════════════════════════════
            [
                'code' => 'first_upvote',
                'name' => 'Apprécié',
                'description' => 'Recevoir votre premier upvote',
                'icon' => '❤️',
                'color' => 'pink',
                'category' => Achievement::CATEGORY_SOCIAL,
                'rarity' => Achievement::RARITY_COMMON,
                'points' => 20,
                'trigger_type' => Achievement::TRIGGER_UPVOTES_RECEIVED,
                'required_value' => 1,
                'order' => 14,
            ],
            [
                'code' => 'popular',
                'name' => 'Populaire',
                'description' => 'Recevoir 25 upvotes',
                'icon' => '⭐',
                'color' => 'yellow',
                'category' => Achievement::CATEGORY_SOCIAL,
                'rarity' => Achievement::RARITY_RARE,
                'points' => 100,
                'trigger_type' => Achievement::TRIGGER_UPVOTES_RECEIVED,
                'required_value' => 25,
                'order' => 15,
            ],
            [
                'code' => 'influencer',
                'name' => 'Leader d\'Opinion',
                'description' => 'Recevoir 100 upvotes - Vous influencez les débats !',
                'icon' => '🌟',
                'color' => 'gold',
                'category' => Achievement::CATEGORY_SOCIAL,
                'rarity' => Achievement::RARITY_EPIC,
                'points' => 300,
                'trigger_type' => Achievement::TRIGGER_UPVOTES_RECEIVED,
                'required_value' => 100,
                'order' => 16,
            ],

            // ═══════════════════════════════════════════════════════════════
            // ENGAGEMENT - Streak et régularité
            // ═══════════════════════════════════════════════════════════════
            [
                'code' => 'streak_3',
                'name' => 'Début d\'Habitude',
                'description' => 'Se connecter 3 jours consécutifs',
                'icon' => '🔥',
                'color' => 'orange',
                'category' => Achievement::CATEGORY_ENGAGEMENT,
                'rarity' => Achievement::RARITY_COMMON,
                'points' => 30,
                'trigger_type' => Achievement::TRIGGER_STREAK,
                'required_value' => 3,
                'order' => 17,
            ],
            [
                'code' => 'streak_7',
                'name' => 'Marathonien',
                'description' => 'Se connecter 7 jours consécutifs',
                'icon' => '🏃',
                'color' => 'red',
                'category' => Achievement::CATEGORY_ENGAGEMENT,
                'rarity' => Achievement::RARITY_RARE,
                'points' => 100,
                'trigger_type' => Achievement::TRIGGER_STREAK,
                'required_value' => 7,
                'order' => 18,
            ],
            [
                'code' => 'streak_30',
                'name' => 'Engagement Inébranlable',
                'description' => 'Se connecter 30 jours consécutifs - Impressionnant !',
                'icon' => '💪',
                'color' => 'purple',
                'category' => Achievement::CATEGORY_ENGAGEMENT,
                'rarity' => Achievement::RARITY_EPIC,
                'points' => 500,
                'trigger_type' => Achievement::TRIGGER_STREAK,
                'required_value' => 30,
                'order' => 19,
            ],
            [
                'code' => 'streak_100',
                'name' => 'Légende Vivante',
                'description' => '100 jours consécutifs - Vous êtes une inspiration !',
                'icon' => '🏆',
                'color' => 'gold',
                'category' => Achievement::CATEGORY_ENGAGEMENT,
                'rarity' => Achievement::RARITY_LEGENDARY,
                'points' => 2000,
                'trigger_type' => Achievement::TRIGGER_STREAK,
                'required_value' => 100,
                'order' => 20,
            ],

            // ═══════════════════════════════════════════════════════════════
            // NIVEAUX - Progression globale
            // ═══════════════════════════════════════════════════════════════
            [
                'code' => 'level_5',
                'name' => 'Citoyen Actif',
                'description' => 'Atteindre le niveau 5',
                'icon' => '🎖️',
                'color' => 'green',
                'category' => Achievement::CATEGORY_EXPERTISE,
                'rarity' => Achievement::RARITY_COMMON,
                'points' => 50,
                'trigger_type' => Achievement::TRIGGER_LEVEL_REACHED,
                'required_value' => 5,
                'order' => 21,
            ],
            [
                'code' => 'level_10',
                'name' => 'Expert Engagé',
                'description' => 'Atteindre le niveau 10',
                'icon' => '🏅',
                'color' => 'blue',
                'category' => Achievement::CATEGORY_EXPERTISE,
                'rarity' => Achievement::RARITY_RARE,
                'points' => 150,
                'trigger_type' => Achievement::TRIGGER_LEVEL_REACHED,
                'required_value' => 10,
                'order' => 22,
            ],
            [
                'code' => 'level_25',
                'name' => 'Pilier de la Communauté',
                'description' => 'Atteindre le niveau 25',
                'icon' => '💫',
                'color' => 'purple',
                'category' => Achievement::CATEGORY_EXPERTISE,
                'rarity' => Achievement::RARITY_EPIC,
                'points' => 500,
                'trigger_type' => Achievement::TRIGGER_LEVEL_REACHED,
                'required_value' => 25,
                'order' => 23,
            ],
            [
                'code' => 'level_50',
                'name' => 'Légende Démocratique',
                'description' => 'Atteindre le niveau 50 - Vous êtes une inspiration !',
                'icon' => '👑',
                'color' => 'gold',
                'category' => Achievement::CATEGORY_EXPERTISE,
                'rarity' => Achievement::RARITY_LEGENDARY,
                'points' => 2500,
                'trigger_type' => Achievement::TRIGGER_LEVEL_REACHED,
                'required_value' => 50,
                'order' => 24,
                'is_secret' => true,
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::updateOrCreate(
                ['code' => $achievement['code']],
                $achievement
            );
        }

        $this->command->info('✅ ' . count($achievements) . ' achievements créés avec succès !');
    }
}
