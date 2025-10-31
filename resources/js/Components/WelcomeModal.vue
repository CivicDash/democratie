<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div v-if="show" class="welcome-modal-overlay" @click="handleSkip">
                <div class="welcome-modal" @click.stop>
                    <!-- Step indicator -->
                    <div class="step-indicator">
                        <div 
                            v-for="(step, index) in steps" 
                            :key="index"
                            class="step-dot"
                            :class="{ active: index === currentStep }"
                        ></div>
                    </div>
                    
                    <!-- Content -->
                    <div class="modal-content">
                        <Transition name="slide-fade" mode="out-in">
                            <div :key="currentStep" class="step-content">
                                <div class="step-icon">{{ steps[currentStep].icon }}</div>
                                <h2 class="step-title">{{ steps[currentStep].title }}</h2>
                                <p class="step-description">{{ steps[currentStep].description }}</p>
                            </div>
                        </Transition>
                    </div>
                    
                    <!-- Actions -->
                    <div class="modal-actions">
                        <button 
                            v-if="currentStep < steps.length - 1"
                            @click="handleSkip" 
                            class="btn-skip"
                        >
                            Passer
                        </button>
                        
                        <button 
                            v-if="currentStep > 0"
                            @click="previousStep" 
                            class="btn-previous"
                        >
                            ← Précédent
                        </button>
                        
                        <button 
                            @click="nextStep" 
                            class="btn-next"
                            :class="{ 'btn-finish': currentStep === steps.length - 1 }"
                        >
                            {{ currentStep === steps.length - 1 ? 'Commencer !' : 'Suivant →' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    show: {
        type: Boolean,
        required: true,
    },
});

const emit = defineEmits(['close', 'complete']);

const currentStep = ref(0);

const steps = [
    {
        icon: '👋',
        title: 'Bienvenue sur Demoscratos !',
        description: 'Une plateforme citoyenne pour débattre, voter et influencer les décisions démocratiques.',
    },
    {
        icon: '🗳️',
        title: 'Votez sur les sujets qui vous tiennent à cœur',
        description: 'Exprimez votre opinion sur les débats citoyens et les propositions de loi en cours à l\'Assemblée.',
    },
    {
        icon: '💡',
        title: 'Créez et partagez vos idées',
        description: 'Proposez de nouveaux sujets de débat et contribuez aux discussions en cours.',
    },
    {
        icon: '🏛️',
        title: 'Suivez l\'activité législative',
        description: 'Restez informé des votes à l\'Assemblée et au Sénat, et comparez avec l\'opinion citoyenne.',
    },
    {
        icon: '💰',
        title: 'Participez à l\'allocation budgétaire',
        description: 'Proposez votre vision de la répartition du budget et comparez-la avec celle de l\'État.',
    },
    {
        icon: '🏆',
        title: 'Gagnez des badges et montez de niveau !',
        description: 'Votre engagement est récompensé : débloquez des achievements et gravissez les échelons de la démocratie participative !',
    },
];

const nextStep = () => {
    if (currentStep.value < steps.length - 1) {
        currentStep.value++;
    } else {
        complete();
    }
};

const previousStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

const handleSkip = () => {
    emit('close');
};

const complete = () => {
    emit('complete');
    emit('close');
};
</script>

<style scoped>
.welcome-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}

.welcome-modal {
    background: white;
    border-radius: 24px;
    padding: 48px;
    max-width: 600px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.step-indicator {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-bottom: 32px;
}

.step-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #E5E7EB;
    transition: all 0.3s ease;
}

.step-dot.active {
    width: 32px;
    border-radius: 5px;
    background: linear-gradient(90deg, #3B82F6, #2563EB);
}

.modal-content {
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.step-content {
    text-align: center;
}

.step-icon {
    font-size: 80px;
    margin-bottom: 24px;
    animation: icon-float 3s ease-in-out infinite;
}

@keyframes icon-float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.step-title {
    font-size: 28px;
    font-weight: 800;
    color: #111827;
    margin: 0 0 16px 0;
}

.step-description {
    font-size: 18px;
    color: #6B7280;
    line-height: 1.6;
    margin: 0;
}

.modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 32px;
    justify-content: center;
}

.modal-actions button {
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-skip {
    background: transparent;
    color: #6B7280;
}

.btn-skip:hover {
    color: #111827;
}

.btn-previous {
    background: white;
    color: #3B82F6;
    border: 2px solid #3B82F6;
}

.btn-previous:hover {
    background: #EFF6FF;
}

.btn-next {
    background: linear-gradient(135deg, #3B82F6, #2563EB);
    color: white;
    padding: 12px 32px;
}

.btn-next:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
}

.btn-finish {
    background: linear-gradient(135deg, #10B981, #059669);
}

.btn-finish:hover {
    box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
}

/* Transitions */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.2s ease-in;
}

.slide-fade-enter-from {
    transform: translateX(20px);
    opacity: 0;
}

.slide-fade-leave-to {
    transform: translateX(-20px);
    opacity: 0;
}

/* Responsive */
@media (max-width: 640px) {
    .welcome-modal {
        padding: 32px 24px;
    }
    
    .step-icon {
        font-size: 60px;
    }
    
    .step-title {
        font-size: 24px;
    }
    
    .step-description {
        font-size: 16px;
    }
    
    .modal-actions {
        flex-direction: column;
    }
    
    .modal-actions button {
        width: 100%;
    }
}
</style>

