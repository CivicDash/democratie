<template>
    <div class="activity-feed">
        <div class="feed-header">
            <h3 class="feed-title">
                <span class="feed-icon">📊</span>
                Activité Récente
            </h3>
            <div class="feed-filters">
                <button 
                    v-for="filter in filters" 
                    :key="filter.value"
                    @click="activeFilter = filter.value"
                    class="filter-btn"
                    :class="{ active: activeFilter === filter.value }"
                >
                    {{ filter.label }}
                </button>
            </div>
        </div>
        
        <div class="feed-content">
            <div v-if="loading" class="feed-loading">
                <div class="spinner"></div>
                <p>Chargement de l'activité...</p>
            </div>
            
            <div v-else-if="filteredActivities.length === 0" class="feed-empty">
                <span class="empty-icon">📭</span>
                <p>Aucune activité récente</p>
            </div>
            
            <TransitionGroup v-else name="activity-list" tag="div" class="activity-list">
                <div 
                    v-for="activity in filteredActivities" 
                    :key="activity.id"
                    class="activity-item"
                    :class="`activity-${activity.type}`"
                >
                    <div class="activity-icon">
                        <span>{{ getActivityIcon(activity.type) }}</span>
                    </div>
                    
                    <div class="activity-content">
                        <p class="activity-text" v-html="activity.text"></p>
                        <span class="activity-time">{{ formatTime(activity.created_at) }}</span>
                    </div>
                    
                    <div v-if="activity.points" class="activity-points">
                        +{{ activity.points }} XP
                    </div>
                </div>
            </TransitionGroup>
        </div>
        
        <div v-if="hasMore" class="feed-footer">
            <button @click="loadMore" class="btn-load-more" :disabled="loadingMore">
                {{ loadingMore ? 'Chargement...' : 'Voir plus' }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    activities: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    hasMore: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['load-more']);

const activeFilter = ref('all');
const loadingMore = ref(false);

const filters = [
    { value: 'all', label: 'Tout' },
    { value: 'achievement', label: 'Badges' },
    { value: 'vote', label: 'Votes' },
    { value: 'topic', label: 'Sujets' },
    { value: 'post', label: 'Commentaires' },
];

const filteredActivities = computed(() => {
    if (activeFilter.value === 'all') {
        return props.activities;
    }
    return props.activities.filter(a => a.type === activeFilter.value);
});

const getActivityIcon = (type) => {
    const icons = {
        achievement: '🏆',
        level_up: '⬆️',
        vote: '🗳️',
        topic: '💡',
        post: '💬',
        legislative_vote: '🏛️',
        budget: '💰',
        follow: '👁️',
        upvote_received: '👍',
        streak: '🔥',
    };
    return icons[type] || '📌';
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'À l\'instant';
    if (diff < 3600) return `Il y a ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `Il y a ${Math.floor(diff / 3600)}h`;
    if (diff < 604800) return `Il y a ${Math.floor(diff / 86400)}j`;
    
    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'short',
    }).format(date);
};

const loadMore = async () => {
    loadingMore.value = true;
    emit('load-more');
    setTimeout(() => {
        loadingMore.value = false;
    }, 500);
};
</script>

<style scoped>
.activity-feed {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.feed-header {
    margin-bottom: 20px;
}

.feed-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 16px 0;
}

.feed-icon {
    font-size: 24px;
}

.feed-filters {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    background: #F3F4F6;
    color: #6B7280;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-btn:hover {
    background: #E5E7EB;
}

.filter-btn.active {
    background: linear-gradient(135deg, #3B82F6, #2563EB);
    color: white;
}

.feed-content {
    min-height: 200px;
}

.feed-loading,
.feed-empty {
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

.empty-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: #F9FAFB;
    border-radius: 12px;
    border-left: 3px solid #E5E7EB;
    transition: all 0.2s;
}

.activity-item:hover {
    background: #F3F4F6;
    transform: translateX(4px);
}

.activity-achievement {
    border-left-color: #FBBF24;
    background: linear-gradient(90deg, #FFF7ED 0%, #F9FAFB 100%);
}

.activity-level_up {
    border-left-color: #8B5CF6;
    background: linear-gradient(90deg, #F5F3FF 0%, #F9FAFB 100%);
}

.activity-vote {
    border-left-color: #3B82F6;
}

.activity-icon {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    background: white;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.activity-content {
    flex: 1;
    min-width: 0;
}

.activity-text {
    font-size: 14px;
    color: #374151;
    margin: 0 0 4px 0;
    line-height: 1.5;
}

.activity-text :deep(strong) {
    font-weight: 600;
    color: #111827;
}

.activity-time {
    font-size: 12px;
    color: #9CA3AF;
}

.activity-points {
    flex-shrink: 0;
    padding: 4px 10px;
    background: linear-gradient(135deg, #10B981, #059669);
    color: white;
    font-size: 12px;
    font-weight: 700;
    border-radius: 12px;
}

.feed-footer {
    margin-top: 16px;
    text-align: center;
}

.btn-load-more {
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

.btn-load-more:hover:not(:disabled) {
    background: #3B82F6;
    color: white;
}

.btn-load-more:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* List transitions */
.activity-list-enter-active {
    transition: all 0.3s ease-out;
}

.activity-list-leave-active {
    transition: all 0.2s ease-in;
}

.activity-list-enter-from {
    opacity: 0;
    transform: translateY(-20px);
}

.activity-list-leave-to {
    opacity: 0;
    transform: translateX(20px);
}
</style>

