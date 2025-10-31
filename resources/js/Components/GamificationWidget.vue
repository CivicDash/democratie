<template>
    <div class="gamification-widget">
        <div v-if="loading" class="widget-skeleton">
            <div class="skeleton-circle"></div>
            <div class="skeleton-lines">
                <div class="skeleton-line"></div>
                <div class="skeleton-line short"></div>
            </div>
        </div>
        
        <div v-else-if="stats" class="widget-content" @click="goToProfile">
            <!-- Level Badge -->
            <div class="level-badge" :class="levelColorClass">
                <span class="level-number">{{ stats.level }}</span>
            </div>
            
            <!-- Info -->
            <div class="widget-info">
                <div class="widget-header">
                    <span class="level-label">Niv. {{ stats.level }}</span>
                    <span v-if="stats.current_streak > 0" class="streak-badge">
                        🔥 {{ stats.current_streak }}
                    </span>
                </div>
                <div class="xp-bar">
                    <div class="xp-fill" :class="levelColorClass" :style="{ width: xpPercentage + '%' }"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const stats = ref(null);
const loading = ref(true);

const xpPercentage = computed(() => {
    if (!stats.value || stats.value.xp_to_next_level === 0) return 0;
    return Math.min(100, Math.round((stats.value.xp / stats.value.xp_to_next_level) * 100));
});

const levelColorClass = computed(() => {
    if (!stats.value) return 'color-gray';
    const level = stats.value.level;
    if (level >= 50) return 'color-legendary';
    if (level >= 40) return 'color-purple';
    if (level >= 30) return 'color-red';
    if (level >= 20) return 'color-blue';
    if (level >= 10) return 'color-green';
    return 'color-gray';
});

const loadStats = async () => {
    try {
        const response = await axios.get('/api/gamification/my-stats');
        stats.value = response.data.data.stats;
    } catch (error) {
        console.error('Error loading gamification stats:', error);
    } finally {
        loading.value = false;
    }
};

const goToProfile = () => {
    router.visit('/profile/gamification');
};

onMounted(() => {
    loadStats();
    
    // Refresh every 5 minutes
    setInterval(loadStats, 5 * 60 * 1000);
});
</script>

<style scoped>
.gamification-widget {
    padding: 8px 12px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.2s;
}

.gamification-widget:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.widget-content {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    min-width: 160px;
}

.level-badge {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    color: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    flex-shrink: 0;
}

.level-badge.color-gray { background: linear-gradient(135deg, #6B7280, #4B5563); }
.level-badge.color-green { background: linear-gradient(135deg, #10B981, #059669); }
.level-badge.color-blue { background: linear-gradient(135deg, #3B82F6, #2563EB); }
.level-badge.color-red { background: linear-gradient(135deg, #EF4444, #DC2626); }
.level-badge.color-purple { background: linear-gradient(135deg, #8B5CF6, #7C3AED); }
.level-badge.color-legendary { 
    background: linear-gradient(135deg, #FBBF24, #F59E0B, #EF4444);
    animation: legendary-pulse 2s ease-in-out infinite;
}

@keyframes legendary-pulse {
    0%, 100% { box-shadow: 0 2px 8px rgba(251, 191, 36, 0.3); }
    50% { box-shadow: 0 4px 16px rgba(251, 191, 36, 0.6); }
}

.level-number {
    font-size: 18px;
}

.widget-info {
    flex: 1;
    min-width: 0;
}

.widget-header {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 4px;
}

.level-label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
}

.streak-badge {
    font-size: 11px;
    padding: 2px 6px;
    background: linear-gradient(135deg, #FFF7ED, #FFEDD5);
    border-radius: 6px;
    border: 1px solid #FED7AA;
}

.xp-bar {
    width: 100%;
    height: 6px;
    background: #E5E7EB;
    border-radius: 3px;
    overflow: hidden;
}

.xp-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.5s ease;
}

.xp-fill.color-gray { background: linear-gradient(90deg, #6B7280, #4B5563); }
.xp-fill.color-green { background: linear-gradient(90deg, #10B981, #059669); }
.xp-fill.color-blue { background: linear-gradient(90deg, #3B82F6, #2563EB); }
.xp-fill.color-red { background: linear-gradient(90deg, #EF4444, #DC2626); }
.xp-fill.color-purple { background: linear-gradient(90deg, #8B5CF6, #7C3AED); }
.xp-fill.color-legendary { 
    background: linear-gradient(90deg, #FBBF24, #F59E0B, #EF4444);
}

/* Skeleton loading */
.widget-skeleton {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 160px;
}

.skeleton-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(90deg, #F3F4F6 0%, #E5E7EB 50%, #F3F4F6 100%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

.skeleton-lines {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.skeleton-line {
    height: 12px;
    background: linear-gradient(90deg, #F3F4F6 0%, #E5E7EB 50%, #F3F4F6 100%);
    background-size: 200% 100%;
    border-radius: 4px;
    animation: skeleton-loading 1.5s ease-in-out infinite;
}

.skeleton-line.short {
    width: 60%;
    height: 6px;
}

@keyframes skeleton-loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Responsive */
@media (max-width: 640px) {
    .gamification-widget {
        padding: 6px 10px;
    }
    
    .widget-content {
        min-width: 140px;
    }
    
    .level-badge {
        width: 36px;
        height: 36px;
    }
    
    .level-number {
        font-size: 16px;
    }
}
</style>

