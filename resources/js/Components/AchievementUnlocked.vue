<template>
    <Teleport to="body">
        <Transition name="achievement-unlock">
            <div v-if="show" class="achievement-unlock-overlay" @click="close">
                <div class="achievement-unlock-card" @click.stop>
                    <!-- Confetti background -->
                    <div class="confetti-container">
                        <div v-for="i in 50" :key="i" class="confetti" :style="confettiStyle(i)"></div>
                    </div>
                    
                    <!-- Glow effect -->
                    <div class="unlock-glow" :style="{ background: glowGradient }"></div>
                    
                    <!-- Content -->
                    <div class="unlock-content">
                        <div class="unlock-header">
                            <span class="unlock-sparkle">✨</span>
                            <h2 class="unlock-title">Badge Débloqué !</h2>
                            <span class="unlock-sparkle">✨</span>
                        </div>
                        
                        <!-- Badge icon (large) -->
                        <div class="unlock-icon" :class="`rarity-${achievement.rarity}`">
                            <span class="text-8xl">{{ achievement.icon }}</span>
                        </div>
                        
                        <!-- Badge name -->
                        <h3 class="unlock-name">{{ achievement.name }}</h3>
                        
                        <!-- Rarity badge -->
                        <div class="unlock-rarity" :style="{ background: rarityGradient }">
                            {{ rarityLabel }}
                        </div>
                        
                        <!-- Description -->
                        <p class="unlock-description">{{ achievement.description }}</p>
                        
                        <!-- XP earned -->
                        <div class="unlock-xp">
                            <span class="xp-label">+{{ achievement.points }}</span>
                            <span class="xp-text">points d'expérience</span>
                        </div>
                        
                        <!-- Actions -->
                        <div class="unlock-actions">
                            <button @click="share" class="btn-share">
                                <span>📤</span>
                                Partager
                            </button>
                            <button @click="close" class="btn-close">
                                Continuer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    achievement: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close', 'share']);

const toast = useToast();

const rarityGradient = computed(() => {
    const gradients = {
        common: 'linear-gradient(135deg, #6B7280 0%, #4B5563 100%)',
        rare: 'linear-gradient(135deg, #3B82F6 0%, #2563EB 100%)',
        epic: 'linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%)',
        legendary: 'linear-gradient(135deg, #FBBF24 0%, #F59E0B 50%, #EF4444 100%)',
    };
    return gradients[props.achievement.rarity] || gradients.common;
});

const glowGradient = computed(() => {
    return rarityGradient.value;
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

const confettiStyle = (index) => {
    const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE'];
    return {
        left: `${Math.random() * 100}%`,
        backgroundColor: colors[index % colors.length],
        animationDelay: `${Math.random() * 3}s`,
        animationDuration: `${3 + Math.random() * 2}s`,
    };
};

const close = () => {
    emit('close');
};

const share = () => {
    emit('share', props.achievement);
    toast.success('Badge partagé avec succès !');
};

// Play sound on mount (if you have audio)
onMounted(() => {
    if (props.show) {
        // Optional: play achievement sound
        // const audio = new Audio('/sounds/achievement-unlock.mp3');
        // audio.play();
    }
});
</script>

<style scoped>
.achievement-unlock-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}

.achievement-unlock-card {
    position: relative;
    background: white;
    border-radius: 24px;
    padding: 48px;
    max-width: 500px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    animation: card-entrance 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes card-entrance {
    0% {
        opacity: 0;
        transform: scale(0.3) translateY(100px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.confetti-container {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    overflow: hidden;
}

.confetti {
    position: absolute;
    width: 10px;
    height: 10px;
    top: -20px;
    opacity: 0;
    animation: confetti-fall 5s ease-in-out infinite;
}

@keyframes confetti-fall {
    0% {
        top: -20px;
        opacity: 1;
        transform: translateX(0) rotate(0deg);
    }
    100% {
        top: 100%;
        opacity: 0;
        transform: translateX(100px) rotate(720deg);
    }
}

.unlock-glow {
    position: absolute;
    top: -50%;
    left: -50%;
    right: -50%;
    bottom: -50%;
    opacity: 0.15;
    filter: blur(60px);
    animation: glow-rotate 8s linear infinite;
}

@keyframes glow-rotate {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.unlock-content {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}

.unlock-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

.unlock-sparkle {
    font-size: 24px;
    animation: sparkle 1s ease-in-out infinite;
}

@keyframes sparkle {
    0%, 100% { transform: scale(1) rotate(0deg); opacity: 1; }
    50% { transform: scale(1.2) rotate(180deg); opacity: 0.8; }
}

.unlock-title {
    font-size: 32px;
    font-weight: 800;
    color: #111827;
    margin: 0;
    text-align: center;
}

.unlock-icon {
    margin: 20px 0;
    animation: icon-bounce 1s ease-in-out infinite;
}

@keyframes icon-bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.unlock-icon.rarity-legendary {
    animation: icon-bounce 1s ease-in-out infinite, icon-glow 2s ease-in-out infinite;
}

@keyframes icon-glow {
    0%, 100% {
        filter: drop-shadow(0 0 10px rgba(251, 191, 36, 0.5));
    }
    50% {
        filter: drop-shadow(0 0 20px rgba(251, 191, 36, 0.8));
    }
}

.unlock-name {
    font-size: 28px;
    font-weight: 700;
    color: #111827;
    margin: 0;
    text-align: center;
}

.unlock-rarity {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 700;
    color: white;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.unlock-description {
    font-size: 16px;
    color: #6B7280;
    text-align: center;
    line-height: 1.6;
    margin: 0;
}

.unlock-xp {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #10B981, #059669);
    border-radius: 12px;
    color: white;
}

.xp-label {
    font-size: 24px;
    font-weight: 800;
}

.xp-text {
    font-size: 14px;
    font-weight: 500;
}

.unlock-actions {
    display: flex;
    gap: 12px;
    width: 100%;
    margin-top: 16px;
}

.unlock-actions button {
    flex: 1;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-share {
    background: white;
    color: #3B82F6;
    border: 2px solid #3B82F6;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-share:hover {
    background: #3B82F6;
    color: white;
    transform: translateY(-2px);
}

.btn-close {
    background: linear-gradient(135deg, #3B82F6, #2563EB);
    color: white;
}

.btn-close:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
}

/* Transitions */
.achievement-unlock-enter-active,
.achievement-unlock-leave-active {
    transition: opacity 0.3s ease;
}

.achievement-unlock-enter-from,
.achievement-unlock-leave-to {
    opacity: 0;
}
</style>

