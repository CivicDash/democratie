<template>
    <div class="level-progress-bar">
        <!-- Header with level info -->
        <div class="progress-header">
            <div class="level-badge" :class="levelColorClass">
                <span class="level-number">{{ level }}</span>
            </div>
            <div class="level-info">
                <h4 class="level-title" :class="levelColorClass">{{ levelTitle }}</h4>
                <p class="level-subtitle">{{ xp }} / {{ xpToNextLevel }} XP</p>
            </div>
        </div>
        
        <!-- Progress bar -->
        <div class="progress-bar-container">
            <div class="progress-bar-bg">
                <div 
                    class="progress-bar-fill" 
                    :class="levelColorClass"
                    :style="{ width: progressPercentage + '%' }"
                >
                    <div class="progress-bar-shine"></div>
                </div>
            </div>
            <span class="progress-percentage">{{ progressPercentage }}%</span>
        </div>
        
        <!-- Next level preview -->
        <div v-if="showNextLevel" class="next-level">
            <span class="next-level-text">Prochain niveau :</span>
            <span class="next-level-title">{{ nextLevelTitle }}</span>
            <span class="next-level-xp">{{ xpNeeded }} XP restants</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    level: {
        type: Number,
        required: true,
    },
    levelTitle: {
        type: String,
        required: true,
    },
    xp: {
        type: Number,
        required: true,
    },
    xpToNextLevel: {
        type: Number,
        required: true,
    },
    showNextLevel: {
        type: Boolean,
        default: true,
    },
});

const progressPercentage = computed(() => {
    if (props.xpToNextLevel === 0) return 100;
    return Math.min(100, Math.round((props.xp / props.xpToNextLevel) * 100));
});

const levelColorClass = computed(() => {
    if (props.level >= 50) return 'color-legendary';
    if (props.level >= 40) return 'color-purple';
    if (props.level >= 30) return 'color-red';
    if (props.level >= 20) return 'color-blue';
    if (props.level >= 10) return 'color-green';
    return 'color-gray';
});

const nextLevelTitle = computed(() => {
    const nextLevel = props.level + 1;
    if (nextLevel >= 50) return 'Légende Démocratique';
    if (nextLevel >= 40) return 'Visionnaire Citoyen';
    if (nextLevel >= 30) return 'Leader d\'Opinion';
    if (nextLevel >= 20) return 'Expert Engagé';
    if (nextLevel >= 10) return 'Citoyen Actif';
    if (nextLevel >= 5) return 'Participant Régulier';
    return 'Nouveau Citoyen';
});

const xpNeeded = computed(() => {
    return props.xpToNextLevel - props.xp;
});
</script>

<style scoped>
.level-progress-bar {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.progress-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

.level-badge {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 800;
    color: white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    position: relative;
    overflow: hidden;
}

.level-badge::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(
        45deg,
        transparent,
        rgba(255, 255, 255, 0.3),
        transparent
    );
    transform: rotate(45deg);
    animation: shine 3s infinite;
}

@keyframes shine {
    0% { transform: translateX(-100%) rotate(45deg); }
    100% { transform: translateX(100%) rotate(45deg); }
}

.level-badge.color-gray { background: linear-gradient(135deg, #6B7280, #4B5563); }
.level-badge.color-green { background: linear-gradient(135deg, #10B981, #059669); }
.level-badge.color-blue { background: linear-gradient(135deg, #3B82F6, #2563EB); }
.level-badge.color-red { background: linear-gradient(135deg, #EF4444, #DC2626); }
.level-badge.color-purple { background: linear-gradient(135deg, #8B5CF6, #7C3AED); }
.level-badge.color-legendary { background: linear-gradient(135deg, #FBBF24, #F59E0B, #EF4444); }

.level-number {
    position: relative;
    z-index: 1;
}

.level-info {
    flex: 1;
}

.level-title {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 4px 0;
}

.level-title.color-gray { color: #6B7280; }
.level-title.color-green { color: #10B981; }
.level-title.color-blue { color: #3B82F6; }
.level-title.color-red { color: #EF4444; }
.level-title.color-purple { color: #8B5CF6; }
.level-title.color-legendary { 
    background: linear-gradient(90deg, #FBBF24, #F59E0B, #EF4444);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.level-subtitle {
    font-size: 14px;
    color: #6B7280;
    margin: 0;
}

.progress-bar-container {
    position: relative;
    margin-bottom: 12px;
}

.progress-bar-bg {
    width: 100%;
    height: 24px;
    background: #E5E7EB;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}

.progress-bar-fill {
    height: 100%;
    border-radius: 12px;
    transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.progress-bar-fill.color-gray { background: linear-gradient(90deg, #6B7280, #4B5563); }
.progress-bar-fill.color-green { background: linear-gradient(90deg, #10B981, #059669); }
.progress-bar-fill.color-blue { background: linear-gradient(90deg, #3B82F6, #2563EB); }
.progress-bar-fill.color-red { background: linear-gradient(90deg, #EF4444, #DC2626); }
.progress-bar-fill.color-purple { background: linear-gradient(90deg, #8B5CF6, #7C3AED); }
.progress-bar-fill.color-legendary { 
    background: linear-gradient(90deg, #FBBF24, #F59E0B, #EF4444);
    animation: legendary-flow 3s ease-in-out infinite;
}

@keyframes legendary-flow {
    0%, 100% { filter: brightness(1) saturate(1); }
    50% { filter: brightness(1.2) saturate(1.5); }
}

.progress-bar-shine {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.4),
        transparent
    );
    animation: progress-shine 2s infinite;
}

@keyframes progress-shine {
    0% { left: -100%; }
    100% { left: 200%; }
}

.progress-percentage {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 12px;
    font-weight: 700;
    color: #111827;
    text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
}

.next-level {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background: #F9FAFB;
    border-radius: 8px;
    font-size: 13px;
}

.next-level-text {
    color: #6B7280;
}

.next-level-title {
    font-weight: 600;
    color: #111827;
}

.next-level-xp {
    margin-left: auto;
    color: #3B82F6;
    font-weight: 600;
}
</style>

