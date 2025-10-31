<template>
    <div class="achievement-badge" :class="[rarityClass, { 'unlocked': isUnlocked, 'locked': !isUnlocked }]">
        <div class="badge-glow" v-if="isUnlocked"></div>
        
        <div class="badge-content">
            <!-- Icon -->
            <div class="badge-icon" :class="{ 'grayscale': !isUnlocked }">
                <span class="text-4xl">{{ achievement.icon }}</span>
            </div>
            
            <!-- Rarity indicator -->
            <div class="badge-rarity" :style="{ background: rarityGradient }">
                {{ rarityLabel }}
            </div>
            
            <!-- Name & Description -->
            <div class="badge-info">
                <h3 class="badge-name">{{ achievement.name }}</h3>
                <p class="badge-description">{{ achievement.description }}</p>
            </div>
            
            <!-- Progress bar (if not unlocked) -->
            <div v-if="!isUnlocked && showProgress" class="badge-progress">
                <div class="progress-bar">
                    <div class="progress-fill" :style="{ width: progressPercentage + '%' }"></div>
                </div>
                <span class="progress-text">{{ progress }} / {{ target }}</span>
            </div>
            
            <!-- Unlocked date & points -->
            <div v-if="isUnlocked" class="badge-footer">
                <span class="badge-points">+{{ achievement.points }} XP</span>
                <span class="badge-date">{{ formatDate(unlockedAt) }}</span>
            </div>
            
            <!-- Locked overlay -->
            <div v-if="!isUnlocked && achievement.is_secret" class="badge-secret">
                <span class="text-2xl">🔒</span>
                <p>Badge Secret</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    achievement: {
        type: Object,
        required: true,
    },
    isUnlocked: {
        type: Boolean,
        default: false,
    },
    progress: {
        type: Number,
        default: 0,
    },
    target: {
        type: Number,
        default: 1,
    },
    unlockedAt: {
        type: String,
        default: null,
    },
    showProgress: {
        type: Boolean,
        default: true,
    },
});

const rarityClass = computed(() => {
    return `rarity-${props.achievement.rarity}`;
});

const rarityGradient = computed(() => {
    const gradients = {
        common: 'linear-gradient(135deg, #6B7280 0%, #4B5563 100%)',
        rare: 'linear-gradient(135deg, #3B82F6 0%, #2563EB 100%)',
        epic: 'linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%)',
        legendary: 'linear-gradient(135deg, #FBBF24 0%, #F59E0B 50%, #EF4444 100%)',
    };
    return gradients[props.achievement.rarity] || gradients.common;
});

const rarityLabel = computed(() => {
    const labels = {
        common: 'Commun',
        rare: 'Rare',
        epic: 'Épique',
        legendary: 'Légendaire',
    };
    return labels[props.achievement.rarity] || 'Commun';
});

const progressPercentage = computed(() => {
    if (props.target === 0) return 100;
    return Math.min(100, Math.round((props.progress / props.target) * 100));
});

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(date);
};
</script>

<style scoped>
.achievement-badge {
    position: relative;
    background: white;
    border-radius: 16px;
    padding: 20px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.achievement-badge:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.achievement-badge.locked {
    opacity: 0.6;
    background: #F9FAFB;
}

.achievement-badge.unlocked {
    animation: unlock-pulse 0.6s ease-out;
}

@keyframes unlock-pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.badge-glow {
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    border-radius: 18px;
    z-index: -1;
    opacity: 0.5;
    animation: glow-pulse 2s ease-in-out infinite;
}

@keyframes glow-pulse {
    0%, 100% { opacity: 0.3; }
    50% { opacity: 0.6; }
}

.rarity-common .badge-glow {
    background: linear-gradient(135deg, #6B7280, #4B5563);
}

.rarity-rare .badge-glow {
    background: linear-gradient(135deg, #3B82F6, #2563EB);
}

.rarity-epic .badge-glow {
    background: linear-gradient(135deg, #8B5CF6, #7C3AED);
}

.rarity-legendary .badge-glow {
    background: linear-gradient(135deg, #FBBF24, #F59E0B, #EF4444);
    animation: legendary-glow 2s ease-in-out infinite;
}

@keyframes legendary-glow {
    0%, 100% {
        opacity: 0.5;
        filter: blur(8px);
    }
    50% {
        opacity: 0.8;
        filter: blur(12px);
    }
}

.badge-content {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.badge-icon {
    transition: filter 0.3s ease;
}

.badge-icon.grayscale {
    filter: grayscale(100%) opacity(0.5);
}

.badge-rarity {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-info {
    text-align: center;
}

.badge-name {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 4px;
}

.locked .badge-name {
    color: #6B7280;
}

.badge-description {
    font-size: 14px;
    color: #6B7280;
    line-height: 1.5;
}

.badge-progress {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #E5E7EB;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3B82F6, #2563EB);
    border-radius: 4px;
    transition: width 0.5s ease;
}

.progress-text {
    font-size: 12px;
    color: #6B7280;
    text-align: center;
}

.badge-footer {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 8px;
    border-top: 1px solid #E5E7EB;
}

.badge-points {
    font-size: 14px;
    font-weight: 600;
    color: #10B981;
}

.badge-date {
    font-size: 12px;
    color: #9CA3AF;
}

.badge-secret {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.badge-secret p {
    font-size: 14px;
    font-weight: 600;
    color: #6B7280;
}
</style>

