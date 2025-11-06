<template>
    <AuthenticatedLayout>
        <Head title="Mon Profil Gamification" />
        
        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">🎮 Mon Profil Gamification</h1>
                    <p class="mt-2 text-gray-600">Suivez votre progression et débloquez des achievements !</p>
                </div>
                
                <!-- Row 1: Level Progress + Streak -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Level Progress -->
                    <LevelProgressBar
                        v-if="stats"
                        :level="stats.level"
                        :level-title="stats.level_title"
                        :xp="stats.xp"
                        :xp-to-next-level="stats.xp_to_next_level"
                    />
                    
                    <!-- Streak Counter -->
                    <StreakCounter
                        v-if="stats"
                        :current-streak="stats.current_streak"
                        :longest-streak="stats.longest_streak"
                        :last-activity-date="stats.last_activity_date"
                    />
                </div>
                
                <!-- Row 2: Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg p-6 shadow">
                        <div class="text-3xl mb-2">🏆</div>
                        <div class="text-2xl font-bold text-gray-900">{{ stats?.total_achievements || 0 }}</div>
                        <div class="text-sm text-gray-600">Badges débloqués</div>
                    </div>
                    
                    <div class="bg-white rounded-lg p-6 shadow">
                        <div class="text-3xl mb-2">⭐</div>
                        <div class="text-2xl font-bold text-gray-900">{{ stats?.reputation_score || 0 }}</div>
                        <div class="text-sm text-gray-600">Réputation</div>
                    </div>
                    
                    <div class="bg-white rounded-lg p-6 shadow">
                        <div class="text-3xl mb-2">🗳️</div>
                        <div class="text-2xl font-bold text-gray-900">{{ stats?.total_votes || 0 }}</div>
                        <div class="text-sm text-gray-600">Votes</div>
                    </div>
                    
                    <div class="bg-white rounded-lg p-6 shadow">
                        <div class="text-3xl mb-2">💡</div>
                        <div class="text-2xl font-bold text-gray-900">{{ stats?.total_topics_created || 0 }}</div>
                        <div class="text-sm text-gray-600">Sujets créés</div>
                    </div>
                </div>
                
                <!-- Row 3: Achievements -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Recent Achievements -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-lg p-6 shadow mb-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-4">🏅 Mes Badges</h2>
                            
                            <!-- Filters -->
                            <div class="flex gap-2 mb-4 flex-wrap">
                                <button 
                                    v-for="cat in categories" 
                                    :key="cat.value"
                                    @click="selectedCategory = cat.value"
                                    class="px-4 py-2 rounded-lg text-sm font-semibold transition"
                                    :class="selectedCategory === cat.value 
                                        ? 'bg-blue-500 text-white' 
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                >
                                    {{ cat.icon }} {{ cat.label }}
                                </button>
                            </div>
                            
                            <!-- Achievements Grid -->
                            <div v-if="loadingAchievements" class="text-center py-8">
                                <div class="inline-block w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                                <p class="mt-2 text-gray-600">Chargement...</p>
                            </div>
                            
                            <div v-else-if="filteredAchievements.length === 0" class="text-center py-8 text-gray-500">
                                Aucun badge dans cette catégorie
                            </div>
                            
                            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <BadgeCard
                                    v-for="ua in filteredAchievements"
                                    :key="ua.id"
                                    :achievement="ua.achievement"
                                    :is-unlocked="ua.is_unlocked"
                                    :progress="ua.progress"
                                    :target="ua.progress_target"
                                    :unlocked-at="ua.unlocked_at"
                                />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Leaderboard -->
                    <div>
                        <LeaderboardPanel
                            :current-user-id="$page.props.auth.user.id"
                            :limit="10"
                        />
                    </div>
                </div>
                
                <!-- Row 4: Activity Feed -->
                <div class="mt-6">
                    <ActivityFeed
                        :activities="mockActivities"
                        :loading="false"
                        :has-more="false"
                    />
                </div>
            </div>
        </div>
        
        <!-- Achievement Unlock Modal -->
        <AchievementUnlocked
            :show="showUnlockModal"
            :achievement="unlockedAchievement"
            @close="showUnlockModal = false"
            @share="handleShare"
        />
        
        <!-- Welcome Modal (first visit) -->
        <WelcomeModal
            :show="showWelcome"
            @close="showWelcome = false"
            @complete="handleWelcomeComplete"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LevelProgressBar from '@/Components/LevelProgressBar.vue';
import StreakCounter from '@/Components/StreakCounter.vue';
import BadgeCard from '@/Components/BadgeCard.vue';
import LeaderboardPanel from '@/Components/LeaderboardPanel.vue';
import ActivityFeed from '@/Components/ActivityFeed.vue';
import AchievementUnlocked from '@/Components/AchievementUnlocked.vue';
import WelcomeModal from '@/Components/WelcomeModal.vue';
import axios from 'axios';

const stats = ref(null);
const achievements = ref([]);
const loadingAchievements = ref(false);
const selectedCategory = ref('all');
const showUnlockModal = ref(false);
const unlockedAchievement = ref(null);
const showWelcome = ref(false);

const categories = [
    { value: 'all', label: 'Tous', icon: '🎯' },
    { value: 'participation', label: 'Participation', icon: '👥' },
    { value: 'legislative', label: 'Législatif', icon: '🏛️' },
    { value: 'budget', label: 'Budget', icon: '💰' },
    { value: 'social', label: 'Social', icon: '❤️' },
    { value: 'engagement', label: 'Engagement', icon: '🔥' },
    { value: 'expertise', label: 'Expertise', icon: '⭐' },
];

const filteredAchievements = computed(() => {
    if (selectedCategory.value === 'all') {
        return achievements.value;
    }
    return achievements.value.filter(ua => 
        ua.achievement.category === selectedCategory.value
    );
});

// Mock activities for demo
const mockActivities = ref([
    {
        id: 1,
        type: 'achievement',
        text: 'Vous avez débloqué le badge <strong>Premier Pas</strong> !',
        points: 10,
        created_at: new Date().toISOString(),
    },
    {
        id: 2,
        type: 'vote',
        text: 'Vous avez voté sur <strong>Réforme des retraites</strong>',
        points: 5,
        created_at: new Date(Date.now() - 3600000).toISOString(),
    },
    {
        id: 3,
        type: 'level_up',
        text: 'Vous êtes passé <strong>niveau 2</strong> ! 🎉',
        points: 50,
        created_at: new Date(Date.now() - 7200000).toISOString(),
    },
]);

const loadStats = async () => {
    try {
        const response = await axios.get('/api/gamification/my-stats');
        stats.value = response.data.data.stats;
    } catch (error) {
        console.error('Error loading stats:', error);
    }
};

const loadAchievements = async () => {
    loadingAchievements.value = true;
    try {
        const response = await axios.get('/api/gamification/my-achievements');
        achievements.value = response.data.data;
    } catch (error) {
        console.error('Error loading achievements:', error);
    } finally {
        loadingAchievements.value = false;
    }
};

const handleShare = (achievement) => {
    console.log('Sharing achievement:', achievement);
    // Implement social share logic
};

const handleWelcomeComplete = () => {
    console.log('Welcome completed!');
    // Save to localStorage or backend
    localStorage.setItem('welcomeCompleted', 'true');
};

onMounted(async () => {
    await loadStats();
    await loadAchievements();
    
    // Show welcome modal if first visit
    const welcomeCompleted = localStorage.getItem('welcomeCompleted');
    if (!welcomeCompleted) {
        showWelcome.value = true;
    }
    
    // Simulate achievement unlock after 3 seconds (for demo)
    setTimeout(() => {
        if (achievements.value.length > 0) {
            const unlockedAch = achievements.value.find(ua => ua.is_unlocked);
            if (unlockedAch) {
                unlockedAchievement.value = unlockedAch.achievement;
                // showUnlockModal.value = true;
            }
        }
    }, 3000);
});
</script>

