import { ref, computed, onMounted, onUnmounted } from 'vue';

/**
 * Composable pour gérer les visites guidées de l'application
 * 
 * Usage:
 * const { startTour, nextStep, prevStep, endTour, currentStep, isActive, steps } = useGuidedTour('dashboard');
 */

// Tours disponibles avec leurs étapes
const tours = {
    dashboard: {
        id: 'dashboard',
        name: 'Découverte du tableau de bord',
        steps: [
            {
                target: '[data-tour="dashboard-stats"]',
                title: 'Vos statistiques',
                content: 'Retrouvez ici un aperçu de votre activité et des statistiques clés du Parlement.',
                position: 'bottom',
            },
            {
                target: '[data-tour="dashboard-scrutins"]',
                title: 'Derniers scrutins',
                content: 'Suivez les votes récents à l\'Assemblée Nationale et au Sénat.',
                position: 'left',
            },
            {
                target: '[data-tour="dashboard-lois"]',
                title: 'Lois en cours',
                content: 'Explorez les textes de loi actuellement en discussion.',
                position: 'right',
            },
            {
                target: '[data-tour="navigation"]',
                title: 'Navigation',
                content: 'Utilisez le menu pour accéder à toutes les fonctionnalités : Parlement, Gouvernement, Législation, Participation...',
                position: 'bottom',
            },
        ],
    },
    participation: {
        id: 'participation',
        name: 'Participer au débat citoyen',
        steps: [
            {
                target: '[data-tour="participation-hub"]',
                title: 'Hub Participation',
                content: 'Bienvenue dans l\'espace de participation citoyenne !',
                position: 'bottom',
            },
            {
                target: '[data-tour="participation-create"]',
                title: 'Créer une idée',
                content: 'Proposez vos idées, posez des questions ou interpellez vos élus.',
                position: 'left',
            },
            {
                target: '[data-tour="participation-vote"]',
                title: 'Voter',
                content: 'Soutenez ou opposez-vous aux propositions des autres citoyens.',
                position: 'right',
            },
        ],
    },
    lois: {
        id: 'lois',
        name: 'Comprendre les lois',
        steps: [
            {
                target: '[data-tour="loi-timeline"]',
                title: 'Parcours législatif',
                content: 'Suivez le chemin d\'une loi depuis son dépôt jusqu\'à sa promulgation.',
                position: 'bottom',
            },
            {
                target: '[data-tour="loi-vote"]',
                title: 'Votre avis',
                content: 'Exprimez votre opinion sur les lois et comparez avec le vote des parlementaires.',
                position: 'left',
            },
            {
                target: '[data-tour="loi-amendements"]',
                title: 'Amendements',
                content: 'Découvrez les modifications proposées par les parlementaires.',
                position: 'right',
            },
        ],
    },
    elu: {
        id: 'elu',
        name: 'Espace élu',
        steps: [
            {
                target: '[data-tour="elu-stats"]',
                title: 'Vos statistiques',
                content: 'Suivez votre taux de réponse et votre délai moyen de réponse.',
                position: 'bottom',
            },
            {
                target: '[data-tour="elu-interpellations"]',
                title: 'Interpellations',
                content: 'Consultez et répondez aux interpellations citoyennes.',
                position: 'left',
            },
            {
                target: '[data-tour="elu-profil"]',
                title: 'Votre profil public',
                content: 'Accédez à votre fiche publique vue par les citoyens.',
                position: 'right',
            },
        ],
    },
};

// État global des tours
const activeTour = ref(null);
const currentStepIndex = ref(0);
const completedTours = ref([]);

// Charger les tours complétés depuis localStorage
const loadCompletedTours = () => {
    const saved = localStorage.getItem('civicdash_completed_tours');
    if (saved) {
        completedTours.value = JSON.parse(saved);
    }
};

// Sauvegarder les tours complétés
const saveCompletedTours = () => {
    localStorage.setItem('civicdash_completed_tours', JSON.stringify(completedTours.value));
};

export function useGuidedTour(tourId = null) {
    const isActive = computed(() => activeTour.value !== null);
    
    const currentTour = computed(() => {
        if (!activeTour.value) return null;
        return tours[activeTour.value] || null;
    });
    
    const currentStep = computed(() => {
        if (!currentTour.value) return null;
        return currentTour.value.steps[currentStepIndex.value] || null;
    });
    
    const totalSteps = computed(() => {
        if (!currentTour.value) return 0;
        return currentTour.value.steps.length;
    });
    
    const progress = computed(() => {
        if (totalSteps.value === 0) return 0;
        return ((currentStepIndex.value + 1) / totalSteps.value) * 100;
    });
    
    const isFirstStep = computed(() => currentStepIndex.value === 0);
    const isLastStep = computed(() => currentStepIndex.value === totalSteps.value - 1);
    
    const isTourCompleted = (id) => {
        return completedTours.value.includes(id);
    };
    
    const startTour = (id = tourId) => {
        if (!tours[id]) {
            console.warn(`Tour "${id}" not found`);
            return false;
        }
        activeTour.value = id;
        currentStepIndex.value = 0;
        return true;
    };
    
    const nextStep = () => {
        if (!currentTour.value) return;
        
        if (currentStepIndex.value < currentTour.value.steps.length - 1) {
            currentStepIndex.value++;
        } else {
            endTour(true);
        }
    };
    
    const prevStep = () => {
        if (currentStepIndex.value > 0) {
            currentStepIndex.value--;
        }
    };
    
    const goToStep = (index) => {
        if (currentTour.value && index >= 0 && index < currentTour.value.steps.length) {
            currentStepIndex.value = index;
        }
    };
    
    const endTour = (completed = false) => {
        if (completed && activeTour.value && !completedTours.value.includes(activeTour.value)) {
            completedTours.value.push(activeTour.value);
            saveCompletedTours();
        }
        activeTour.value = null;
        currentStepIndex.value = 0;
    };
    
    const resetTours = () => {
        completedTours.value = [];
        saveCompletedTours();
    };
    
    // Charger au montage
    onMounted(() => {
        loadCompletedTours();
    });
    
    // Raccourci clavier pour fermer
    const handleKeydown = (e) => {
        if (e.key === 'Escape' && isActive.value) {
            endTour();
        }
    };
    
    onMounted(() => {
        document.addEventListener('keydown', handleKeydown);
    });
    
    onUnmounted(() => {
        document.removeEventListener('keydown', handleKeydown);
    });
    
    return {
        // État
        isActive,
        currentTour,
        currentStep,
        currentStepIndex,
        totalSteps,
        progress,
        isFirstStep,
        isLastStep,
        completedTours,
        
        // Actions
        startTour,
        nextStep,
        prevStep,
        goToStep,
        endTour,
        resetTours,
        isTourCompleted,
        
        // Données
        availableTours: tours,
    };
}
