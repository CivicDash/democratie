<template>
    <div class="leaderboard-panel">
        <div class="panel-header">
            <h3 class="panel-title">
                <span class="title-icon">🏆</span>
                Classement
            </h3>
            <select v-model="selectedMetric" class="metric-select">
                <option value="reputation_score">Réputation</option>
                <option value="xp">Expérience</option>
                <option value="level">Niveau</option>
                <option value="current_streak">Streak</option>
            </select>
        </div>
        
        <div class="leaderboard-list">
            <div v-if="loading" class="loading-state">
                <div class="spinner"></div>
                <p>Chargement...</p>
            </div>
            
            <div v-else-if="leaderboard.length === 0" class="empty-state">
                <span>📊</span>
                <p>Aucune donnée disponible</p>
            </div>
            
            <TransitionGroup v-else name="leaderboard" tag="div">
                <div 
                    v-for="(entry, index) in leaderboard" 
                    :key="entry.user.id"
                    class="leaderboard-entry"
                    :class="{ 
                        'top-1': index === 0, 
                        'top-2': index === 1, 
                        'top-3': index === 2,
                        'current-user': entry.user.id === currentUserId,
                    }"
                >
                    <div class="entry-rank">
                        <span v-if="index === 0" class="medal">🥇</span>
                        <span v-else-if="index === 1" class="medal">🥈</span>
                        <span v-else-if="index === 2" class="medal">🥉</span>
                        <span v-else class="rank-number">{{ index + 1 }}</span>
                    </div>
                    
                    <div class="entry-user">
                        <div class="user-avatar">
                            <img 
                                v-if="entry.user.avatar" 
                                :src="entry.user.avatar" 
                                :alt="entry.user.name"
                            />
                            <span v-else class="avatar-placeholder">
                                {{ entry.user.name.charAt(0).toUpperCase() }}
                            </span>
                        </div>
                        <div class="user-info">
                            <span class="user-name">{{ entry.user.name }}</span>
                            <span class="user-title">{{ entry.level_title }}</span>
                        </div>
                    </div>
                    
                    <div class="entry-stats">
                        <div class="stat-badge" :class="getLevelColorClass(entry.level)">
                            <span class="stat-value">{{ formatMetric(entry) }}</span>
                        </div>
                    </div>
                </div>
            </TransitionGroup>
        </div>
        
        <div v-if="showViewAll" class="panel-footer">
            <button @click="$emit('view-all')" class="btn-view-all">
                Voir le classement complet →
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    limit: {
        type: Number,
        default: 10,
    },
    currentUserId: {
        type: Number,
        default: null,
    },
    showViewAll: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['view-all']);

const selectedMetric = ref('reputation_score');
const leaderboard = ref([]);
const loading = ref(false);

const loadLeaderboard = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/achievements/leaderboard', {
            params: {
                limit: props.limit,
                order_by: selectedMetric.value,
            },
        });
        leaderboard.value = response.data.leaderboard;
    } catch (error) {
        console.error('Error loading leaderboard:', error);
    } finally {
        loading.value = false;
    }
};

watch(selectedMetric, () => {
    loadLeaderboard();
});

const formatMetric = (entry) => {
    switch (selectedMetric.value) {
        case 'reputation_score':
            return entry.reputation_score.toLocaleString();
        case 'xp':
            return entry.xp.toLocaleString() + ' XP';
        case 'level':
            return 'Niv. ' + entry.level;
        case 'current_streak':
            return entry.current_streak + ' 🔥';
        default:
            return entry.reputation_score;
    }
};

const getLevelColorClass = (level) => {
    if (level >= 50) return 'color-legendary';
    if (level >= 40) return 'color-purple';
    if (level >= 30) return 'color-red';
    if (level >= 20) return 'color-blue';
    if (level >= 10) return 'color-green';
    return 'color-gray';
};

// Load on mount
loadLeaderboard();
</script>

<style scoped>
.leaderboard-panel {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.panel-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin: 0;
}

.title-icon {
    font-size: 24px;
}

.metric-select {
    padding: 8px 12px;
    border-radius: 8px;
    border: 2px solid #E5E7EB;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
}

.metric-select:hover {
    border-color: #3B82F6;
}

.leaderboard-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-height: 200px;
}

.loading-state,
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    color: #9CA3AF;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #E5E7EB;
    border-top-color: #3B82F6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.leaderboard-entry {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #F9FAFB;
    border-radius: 12px;
    transition: all 0.2s;
}

.leaderboard-entry:hover {
    background: #F3F4F6;
    transform: translateX(4px);
}

.leaderboard-entry.current-user {
    background: linear-gradient(90deg, #EFF6FF 0%, #F9FAFB 100%);
    border: 2px solid #3B82F6;
}

.leaderboard-entry.top-1 {
    background: linear-gradient(90deg, #FFF7ED 0%, #F9FAFB 100%);
}

.leaderboard-entry.top-2 {
    background: linear-gradient(90deg, #F5F5F5 0%, #F9FAFB 100%);
}

.leaderboard-entry.top-3 {
    background: linear-gradient(90deg, #FEF3E2 0%, #F9FAFB 100%);
}

.entry-rank {
    flex-shrink: 0;
    width: 36px;
    text-align: center;
}

.medal {
    font-size: 24px;
}

.rank-number {
    font-size: 16px;
    font-weight: 700;
    color: #6B7280;
}

.entry-user {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.user-avatar {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, #3B82F6, #2563EB);
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    font-weight: 700;
}

.user-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.user-name {
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-title {
    font-size: 12px;
    color: #6B7280;
}

.entry-stats {
    flex-shrink: 0;
}

.stat-badge {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    color: white;
}

.stat-badge.color-gray { background: linear-gradient(135deg, #6B7280, #4B5563); }
.stat-badge.color-green { background: linear-gradient(135deg, #10B981, #059669); }
.stat-badge.color-blue { background: linear-gradient(135deg, #3B82F6, #2563EB); }
.stat-badge.color-red { background: linear-gradient(135deg, #EF4444, #DC2626); }
.stat-badge.color-purple { background: linear-gradient(135deg, #8B5CF6, #7C3AED); }
.stat-badge.color-legendary { background: linear-gradient(135deg, #FBBF24, #F59E0B, #EF4444); }

.panel-footer {
    margin-top: 16px;
    text-align: center;
}

.btn-view-all {
    padding: 10px 24px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    background: white;
    color: #3B82F6;
    border: 2px solid #3B82F6;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-view-all:hover {
    background: #3B82F6;
    color: white;
}

/* Transitions */
.leaderboard-enter-active {
    transition: all 0.3s ease-out;
}

.leaderboard-leave-active {
    transition: all 0.2s ease-in;
}

.leaderboard-enter-from {
    opacity: 0;
    transform: translateY(-10px);
}

.leaderboard-leave-to {
    opacity: 0;
    transform: translateY(10px);
}
</style>

