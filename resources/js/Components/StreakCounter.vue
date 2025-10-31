<template>
    <div class="streak-counter" :class="{ 'on-fire': isOnFire }">
        <div class="streak-icon">
            <span class="fire-emoji" :class="{ 'animated': isOnFire }">🔥</span>
        </div>
        
        <div class="streak-content">
            <div class="streak-header">
                <span class="streak-number">{{ currentStreak }}</span>
                <span class="streak-label">{{ streakLabel }}</span>
            </div>
            
            <p class="streak-message">{{ streakMessage }}</p>
            
            <div v-if="currentStreak > 0" class="streak-footer">
                <span class="streak-best">Meilleur : {{ longestStreak }} jours</span>
                <span v-if="showNextMilestone" class="streak-next">
                    {{ nextMilestone - currentStreak }} jours jusqu'au prochain objectif
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    currentStreak: {
        type: Number,
        required: true,
    },
    longestStreak: {
        type: Number,
        required: true,
    },
    lastActivityDate: {
        type: String,
        default: null,
    },
});

const isOnFire = computed(() => {
    return props.currentStreak >= 3;
});

const streakLabel = computed(() => {
    return props.currentStreak === 1 ? 'jour' : 'jours';
});

const streakMessage = computed(() => {
    if (props.currentStreak === 0) {
        return 'Connectez-vous pour démarrer votre série !';
    }
    if (props.currentStreak === 1) {
        return 'Bon début ! Revenez demain pour continuer.';
    }
    if (props.currentStreak >= 30) {
        return '🌟 Engagement exceptionnel ! Vous êtes incroyable !';
    }
    if (props.currentStreak >= 7) {
        return '💪 Une semaine complète ! Vous êtes déterminé !';
    }
    if (props.currentStreak >= 3) {
        return '🔥 Vous êtes en feu ! Continuez comme ça !';
    }
    return 'Excellent ! Continuez votre série demain.';
});

const milestones = [3, 7, 14, 30, 60, 100, 365];

const nextMilestone = computed(() => {
    return milestones.find(m => m > props.currentStreak) || null;
});

const showNextMilestone = computed(() => {
    return nextMilestone.value !== null;
});
</script>

<style scoped>
.streak-counter {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    display: flex;
    gap: 16px;
    transition: all 0.3s ease;
}

.streak-counter.on-fire {
    background: linear-gradient(135deg, #FFF7ED 0%, #FFEDD5 100%);
    box-shadow: 0 4px 16px rgba(251, 146, 60, 0.2);
}

.streak-icon {
    flex-shrink: 0;
}

.fire-emoji {
    font-size: 48px;
    display: block;
    filter: grayscale(100%);
    transition: filter 0.3s ease;
}

.fire-emoji.animated {
    filter: grayscale(0%);
    animation: fire-dance 1s ease-in-out infinite;
}

@keyframes fire-dance {
    0%, 100% {
        transform: scale(1) rotate(-5deg);
    }
    25% {
        transform: scale(1.1) rotate(5deg);
    }
    50% {
        transform: scale(1.05) rotate(-3deg);
    }
    75% {
        transform: scale(1.1) rotate(3deg);
    }
}

.streak-content {
    flex: 1;
}

.streak-header {
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-bottom: 8px;
}

.streak-number {
    font-size: 36px;
    font-weight: 800;
    color: #111827;
}

.on-fire .streak-number {
    background: linear-gradient(135deg, #F97316, #EA580C);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.streak-label {
    font-size: 16px;
    font-weight: 600;
    color: #6B7280;
}

.streak-message {
    font-size: 14px;
    color: #6B7280;
    margin: 0 0 12px 0;
    line-height: 1.5;
}

.streak-footer {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding-top: 12px;
    border-top: 1px solid #E5E7EB;
}

.on-fire .streak-footer {
    border-top-color: #FED7AA;
}

.streak-best {
    font-size: 13px;
    color: #9CA3AF;
    font-weight: 500;
}

.streak-next {
    font-size: 13px;
    color: #F97316;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 640px) {
    .streak-counter {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .streak-footer {
        align-items: center;
    }
}
</style>

