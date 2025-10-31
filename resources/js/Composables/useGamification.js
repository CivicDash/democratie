import { ref } from 'vue';
import axios from 'axios';

const achievements = ref([]);
const userAchievements = ref([]);
const userStats = ref(null);
const loading = ref(false);
const showUnlockModal = ref(false);
const currentUnlockedAchievement = ref(null);

export function useGamification() {
    /**
     * Charger tous les achievements
     */
    const loadAchievements = async (filters = {}) => {
        loading.value = true;
        try {
            const params = new URLSearchParams(filters);
            const response = await axios.get(`/api/achievements?${params}`);
            achievements.value = response.data.achievements;
        } catch (error) {
            console.error('Error loading achievements:', error);
        } finally {
            loading.value = false;
        }
    };

    /**
     * Charger les achievements de l'utilisateur
     */
    const loadUserAchievements = async (unlockedOnly = false) => {
        loading.value = true;
        try {
            const response = await axios.get('/api/achievements/user', {
                params: { unlocked_only: unlockedOnly },
            });
            userAchievements.value = response.data.achievements;
        } catch (error) {
            console.error('Error loading user achievements:', error);
        } finally {
            loading.value = false;
        }
    };

    /**
     * Charger les statistiques utilisateur
     */
    const loadUserStats = async () => {
        loading.value = true;
        try {
            const response = await axios.get('/api/achievements/stats');
            userStats.value = response.data.stats;
        } catch (error) {
            console.error('Error loading user stats:', error);
        } finally {
            loading.value = false;
        }
    };

    /**
     * Charger les achievements récents
     */
    const loadRecentAchievements = async () => {
        try {
            const response = await axios.get('/api/achievements/recent');
            return response.data.achievements;
        } catch (error) {
            console.error('Error loading recent achievements:', error);
            return [];
        }
    };

    /**
     * Charger les achievements presque débloqués
     */
    const loadAlmostUnlocked = async () => {
        try {
            const response = await axios.get('/api/achievements/almost-unlocked');
            return response.data.achievements;
        } catch (error) {
            console.error('Error loading almost unlocked achievements:', error);
            return [];
        }
    };

    /**
     * Afficher la modal de déblocage d'achievement
     */
    const showAchievementUnlock = (achievement) => {
        currentUnlockedAchievement.value = achievement;
        showUnlockModal.value = true;
    };

    /**
     * Fermer la modal de déblocage
     */
    const closeUnlockModal = () => {
        showUnlockModal.value = false;
        setTimeout(() => {
            currentUnlockedAchievement.value = null;
        }, 300);
    };

    /**
     * Marquer un achievement comme notifié
     */
    const markAchievementNotified = async (achievementId) => {
        try {
            await axios.post(`/api/achievements/${achievementId}/mark-notified`);
        } catch (error) {
            console.error('Error marking achievement as notified:', error);
        }
    };

    /**
     * Partager un achievement
     */
    const shareAchievement = async (achievement) => {
        try {
            const response = await axios.post(`/api/achievements/${achievement.id}/share`);
            
            // Copier l'URL de partage dans le presse-papier
            if (navigator.clipboard && response.data.share_url) {
                await navigator.clipboard.writeText(response.data.share_url);
            }
            
            return response.data;
        } catch (error) {
            console.error('Error sharing achievement:', error);
            throw error;
        }
    };

    /**
     * Écouter les événements SSE pour les achievements
     */
    const listenForAchievements = (userId) => {
        if (typeof EventSource === 'undefined') {
            console.warn('SSE not supported');
            return;
        }

        const eventSource = new EventSource(`/api/achievements/stream?user_id=${userId}`);

        eventSource.addEventListener('achievement-unlocked', (event) => {
            const achievement = JSON.parse(event.data);
            showAchievementUnlock(achievement);
            
            // Recharger les stats
            loadUserStats();
            loadUserAchievements();
        });

        eventSource.addEventListener('level-up', (event) => {
            const data = JSON.parse(event.data);
            // Afficher une notification de level up
            console.log('Level up!', data);
        });

        return eventSource;
    };

    /**
     * Calculer la progression globale
     */
    const calculateGlobalProgress = () => {
        if (!userAchievements.value.length) return 0;
        
        const unlocked = userAchievements.value.filter(a => a.is_unlocked).length;
        const total = userAchievements.value.length;
        
        return Math.round((unlocked / total) * 100);
    };

    /**
     * Grouper les achievements par catégorie
     */
    const groupByCategory = (achievementsList) => {
        return achievementsList.reduce((groups, achievement) => {
            const category = achievement.category || 'other';
            if (!groups[category]) {
                groups[category] = [];
            }
            groups[category].push(achievement);
            return groups;
        }, {});
    };

    /**
     * Grouper les achievements par rareté
     */
    const groupByRarity = (achievementsList) => {
        return achievementsList.reduce((groups, achievement) => {
            const rarity = achievement.rarity || 'common';
            if (!groups[rarity]) {
                groups[rarity] = [];
            }
            groups[rarity].push(achievement);
            return groups;
        }, {});
    };

    return {
        // State
        achievements,
        userAchievements,
        userStats,
        loading,
        showUnlockModal,
        currentUnlockedAchievement,
        
        // Methods
        loadAchievements,
        loadUserAchievements,
        loadUserStats,
        loadRecentAchievements,
        loadAlmostUnlocked,
        showAchievementUnlock,
        closeUnlockModal,
        markAchievementNotified,
        shareAchievement,
        listenForAchievements,
        calculateGlobalProgress,
        groupByCategory,
        groupByRarity,
    };
}

