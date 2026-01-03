<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { useGuidedTour } from '@/Composables/useGuidedTour';

const {
    isActive,
    currentStep,
    currentStepIndex,
    totalSteps,
    progress,
    isFirstStep,
    isLastStep,
    nextStep,
    prevStep,
    endTour,
} = useGuidedTour();

const tooltipPosition = ref({ top: 0, left: 0 });
const tooltipRef = ref(null);

// Calculer la position du tooltip
const updatePosition = async () => {
    if (!currentStep.value?.target) return;
    
    await nextTick();
    
    const targetEl = document.querySelector(currentStep.value.target);
    if (!targetEl) {
        console.warn(`Tour target "${currentStep.value.target}" not found`);
        return;
    }
    
    const rect = targetEl.getBoundingClientRect();
    const tooltipEl = tooltipRef.value;
    const tooltipRect = tooltipEl?.getBoundingClientRect() || { width: 320, height: 200 };
    
    let top = 0;
    let left = 0;
    const padding = 16;
    
    switch (currentStep.value.position) {
        case 'top':
            top = rect.top - tooltipRect.height - padding;
            left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
            break;
        case 'bottom':
            top = rect.bottom + padding;
            left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
            break;
        case 'left':
            top = rect.top + (rect.height / 2) - (tooltipRect.height / 2);
            left = rect.left - tooltipRect.width - padding;
            break;
        case 'right':
            top = rect.top + (rect.height / 2) - (tooltipRect.height / 2);
            left = rect.right + padding;
            break;
        default:
            top = rect.bottom + padding;
            left = rect.left;
    }
    
    // Garder dans la viewport
    top = Math.max(padding, Math.min(top, window.innerHeight - tooltipRect.height - padding));
    left = Math.max(padding, Math.min(left, window.innerWidth - tooltipRect.width - padding));
    
    tooltipPosition.value = { top, left };
    
    // Highlight l'élément cible
    targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    targetEl.classList.add('tour-highlight');
};

// Mettre à jour la position quand l'étape change
watch(currentStepIndex, () => {
    // Retirer le highlight précédent
    document.querySelectorAll('.tour-highlight').forEach(el => {
        el.classList.remove('tour-highlight');
    });
    updatePosition();
});

watch(isActive, (active) => {
    if (active) {
        updatePosition();
    } else {
        document.querySelectorAll('.tour-highlight').forEach(el => {
            el.classList.remove('tour-highlight');
        });
    }
});

onMounted(() => {
    if (isActive.value) {
        updatePosition();
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-200"
            leave-to-class="opacity-0"
        >
            <div v-if="isActive" class="fixed inset-0 z-[9999]">
                <!-- Overlay sombre -->
                <div 
                    class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                    @click="endTour"
                ></div>
                
                <!-- Tooltip -->
                <div
                    ref="tooltipRef"
                    class="absolute z-10 w-80 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                    :style="{ top: tooltipPosition.top + 'px', left: tooltipPosition.left + 'px' }"
                >
                    <!-- Progress bar -->
                    <div class="h-1 bg-gray-200 dark:bg-gray-700">
                        <div 
                            class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-300"
                            :style="{ width: progress + '%' }"
                        ></div>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ currentStep?.title }}
                            </h3>
                            <button
                                @click="endTour"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        <p class="text-gray-600 dark:text-gray-300 text-sm leading-relaxed">
                            {{ currentStep?.content }}
                        </p>
                    </div>
                    
                    <!-- Footer -->
                    <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ currentStepIndex + 1 }} / {{ totalSteps }}
                        </span>
                        
                        <div class="flex gap-2">
                            <button
                                v-if="!isFirstStep"
                                @click="prevStep"
                                class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                            >
                                ← Précédent
                            </button>
                            <button
                                @click="nextStep"
                                class="px-4 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors"
                            >
                                {{ isLastStep ? 'Terminer' : 'Suivant →' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style>
/* Style pour les éléments highlightés */
.tour-highlight {
    position: relative;
    z-index: 10000 !important;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.5), 0 0 0 8px rgba(99, 102, 241, 0.2) !important;
    border-radius: 8px;
    animation: tour-pulse 2s infinite;
}

@keyframes tour-pulse {
    0%, 100% {
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.5), 0 0 0 8px rgba(99, 102, 241, 0.2);
    }
    50% {
        box-shadow: 0 0 0 6px rgba(99, 102, 241, 0.6), 0 0 0 12px rgba(99, 102, 241, 0.3);
    }
}
</style>
