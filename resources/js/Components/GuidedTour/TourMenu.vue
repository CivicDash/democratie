<script setup>
import { ref, computed } from 'vue';
import { useGuidedTour } from '@/Composables/useGuidedTour';
import TourIcon from './TourIcon.vue';

const { startTourWithRedirect, availableTours, isTourCompleted, resetTours, completedTours } = useGuidedTour();

const isOpen = ref(false);

const toursList = computed(() => {
    return Object.values(availableTours).map(tour => ({
        ...tour,
        completed: isTourCompleted(tour.id),
    }));
});

const totalCompleted = computed(() => completedTours.value.length);
const totalTours = computed(() => toursList.value.length);

const handleStartTour = (tourId) => {
    isOpen.value = false;
    startTourWithRedirect(tourId);
};

const handleClickOutside = (event) => {
    if (!event.target.closest('[data-tour-menu]')) {
        isOpen.value = false;
    }
};

// Fermer le menu lors de la navigation
import { onMounted, onUnmounted } from 'vue';

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative" data-tour-menu>
        <!-- Bouton déclencheur -->
        <button
            @click="isOpen = !isOpen"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-all"
            :title="`${totalCompleted}/${totalTours} visites complétées`"
        >
            <span class="text-lg">🎯</span>
            <span class="hidden sm:inline">Visite guidée</span>
            <span 
                v-if="totalCompleted > 0"
                class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 rounded-full"
            >
                {{ totalCompleted }}
            </span>
        </button>

        <!-- Menu déroulant -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95 translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-1"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 z-50 overflow-hidden"
            >
                <!-- Header -->
                <div class="px-4 py-3 bg-gradient-to-r from-indigo-500 to-purple-500 text-white">
                    <h3 class="font-bold text-base">Visites guidées</h3>
                    <p class="text-sm text-indigo-100">
                        {{ totalCompleted }}/{{ totalTours }} complétées
                    </p>
                    <!-- Barre de progression -->
                    <div class="mt-2 h-1.5 bg-white/20 rounded-full overflow-hidden">
                        <div 
                            class="h-full bg-white rounded-full transition-all duration-500"
                            :style="{ width: (totalCompleted / totalTours * 100) + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Liste des tours -->
                <div class="max-h-80 overflow-y-auto">
                    <button
                        v-for="tour in toursList"
                        :key="tour.id"
                        @click="handleStartTour(tour.id)"
                        class="w-full px-4 py-3 flex items-start gap-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors text-left border-b border-slate-100 dark:border-slate-700/50 last:border-0"
                    >
                        <!-- Icône -->
                        <TourIcon :tour-id="tour.id" size="lg" class="mt-0.5" />
                        
                        <!-- Contenu -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <h4 class="font-semibold text-slate-900 dark:text-white text-sm">
                                    {{ tour.name }}
                                </h4>
                                <span 
                                    v-if="tour.completed"
                                    class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded"
                                >
                                    ✓ Vue
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2">
                                {{ tour.description || `${tour.steps.length} étapes` }}
                            </p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs text-slate-400 dark:text-slate-500">
                                    {{ tour.steps.length }} étapes
                                </span>
                            </div>
                        </div>

                        <!-- Indicateur -->
                        <svg 
                            class="w-5 h-5 text-slate-400 dark:text-slate-500 mt-1 flex-shrink-0" 
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <!-- Footer -->
                <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <button
                        v-if="totalCompleted > 0"
                        @click="resetTours()"
                        class="text-xs text-slate-500 hover:text-red-600 dark:text-slate-400 dark:hover:text-red-400 transition-colors"
                    >
                        Réinitialiser les visites
                    </button>
                    <span v-else class="text-xs text-slate-400 dark:text-slate-500">
                        Commencez votre première visite !
                    </span>
                    <span class="text-xs text-slate-400">
                        ⌨️ Échap pour fermer
                    </span>
                </div>
            </div>
        </Transition>
    </div>
</template>
