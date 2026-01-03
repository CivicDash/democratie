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
        icon: '🏠',
        description: 'Apprenez à utiliser votre tableau de bord CivicDash',
        steps: [
            {
                target: '[data-tour="dashboard-stats"]',
                title: 'Statistiques globales',
                content: 'Retrouvez ici un aperçu de l\'activité citoyenne : nombre de discussions, votes et propositions en cours.',
                position: 'bottom',
            },
            {
                target: '[data-tour="dashboard-quick-actions"]',
                title: 'Accès rapides',
                content: 'Naviguez rapidement vers les députés, sénateurs, scrutins, idées citoyennes et vos représentants locaux.',
                position: 'bottom',
            },
            {
                target: '[data-tour="dashboard-scrutins"]',
                title: 'Derniers scrutins',
                content: 'Suivez les votes récents à l\'Assemblée Nationale. Cliquez sur un scrutin pour voir le détail des votes par groupe politique.',
                position: 'left',
            },
            {
                target: '[data-tour="dashboard-lois"]',
                title: 'Propositions de loi',
                content: 'Explorez les textes de loi en discussion et donnez votre avis citoyen en votant Pour ou Contre.',
                position: 'right',
            },
            {
                target: '[data-tour="navigation"]',
                title: 'Navigation principale',
                content: 'Utilisez le menu pour accéder à toutes les fonctionnalités : Parlement, Gouvernement, Législation, Participation... Raccourci : ⌘K',
                position: 'bottom',
            },
        ],
    },
    participation: {
        id: 'participation',
        name: 'Participer au débat citoyen',
        icon: '💬',
        description: 'Découvrez comment proposer des idées et interpeller vos élus',
        steps: [
            {
                target: '[data-tour="participation-hub"]',
                title: 'Hub Participation',
                content: 'Bienvenue dans l\'espace de participation citoyenne ! Ici, vous pouvez proposer des idées, lancer des débats ou interpeller directement vos élus.',
                position: 'bottom',
            },
            {
                target: '[data-tour="participation-create"]',
                title: 'Créer une idée',
                content: 'Proposez vos idées avec notre assistant en 6 étapes. Vous pouvez lier votre idée à une loi en cours ou interpeller des élus.',
                position: 'left',
            },
            {
                target: '[data-tour="participation-filters"]',
                title: 'Filtrer les idées',
                content: 'Filtrez par type (idée, question, interpellation), par thématique ou par niveau géographique (national, régional, local).',
                position: 'right',
            },
            {
                target: '[data-tour="participation-vote"]',
                title: 'Voter',
                content: 'Soutenez les idées que vous approuvez avec un vote Pour, ou opposez-vous avec un vote Contre. Votre voix compte !',
                position: 'bottom',
            },
        ],
    },
    lois: {
        id: 'lois',
        name: 'Comprendre les lois',
        icon: '📜',
        description: 'Suivez le parcours d\'une loi de son dépôt à sa promulgation',
        steps: [
            {
                target: '[data-tour="loi-header"]',
                title: 'Fiche de la loi',
                content: 'Retrouvez toutes les informations sur cette loi : numéro, titre, état d\'avancement et dates clés.',
                position: 'bottom',
            },
            {
                target: '[data-tour="loi-timeline"]',
                title: 'Parcours législatif',
                content: 'Suivez le chemin de la loi à travers l\'Assemblée Nationale et le Sénat, des commissions aux votes en séance.',
                position: 'bottom',
            },
            {
                target: '[data-tour="loi-vote"]',
                title: 'Votre avis citoyen',
                content: 'Exprimez votre opinion sur cette loi ! Votre vote sera comparé à celui des parlementaires.',
                position: 'left',
            },
            {
                target: '[data-tour="loi-scrutins"]',
                title: 'Scrutins parlementaires',
                content: 'Consultez les votes officiels des députés et sénateurs sur cette loi.',
                position: 'right',
            },
            {
                target: '[data-tour="loi-amendements"]',
                title: 'Amendements',
                content: 'Découvrez les modifications proposées par les parlementaires et leur sort (adoptés, rejetés, retirés).',
                position: 'bottom',
            },
        ],
    },
    deputes: {
        id: 'deputes',
        name: 'Explorer les députés',
        icon: '🏛️',
        description: 'Découvrez les 577 députés de l\'Assemblée Nationale',
        steps: [
            {
                target: '[data-tour="deputes-search"]',
                title: 'Rechercher un député',
                content: 'Recherchez un député par son nom, son groupe politique ou sa circonscription.',
                position: 'bottom',
            },
            {
                target: '[data-tour="deputes-filters"]',
                title: 'Filtrer par groupe',
                content: 'Filtrez les députés par groupe politique pour voir les élus d\'un parti en particulier.',
                position: 'right',
            },
            {
                target: '[data-tour="deputes-list"]',
                title: 'Liste des députés',
                content: 'Cliquez sur un député pour accéder à sa fiche détaillée : votes, interventions, déclarations d\'intérêts...',
                position: 'top',
            },
        ],
    },
    senateurs: {
        id: 'senateurs',
        name: 'Explorer les sénateurs',
        icon: '🏛️',
        description: 'Découvrez les 348 sénateurs de la République',
        steps: [
            {
                target: '[data-tour="senateurs-search"]',
                title: 'Rechercher un sénateur',
                content: 'Recherchez un sénateur par son nom, son groupe politique ou son département.',
                position: 'bottom',
            },
            {
                target: '[data-tour="senateurs-filters"]',
                title: 'Filtrer par groupe',
                content: 'Filtrez les sénateurs par groupe politique ou par département.',
                position: 'right',
            },
            {
                target: '[data-tour="senateurs-list"]',
                title: 'Liste des sénateurs',
                content: 'Cliquez sur un sénateur pour accéder à sa fiche : votes, questions écrites, mandats locaux...',
                position: 'top',
            },
        ],
    },
    gouvernement: {
        id: 'gouvernement',
        name: 'Le Gouvernement',
        icon: '🏰',
        description: 'Explorez la composition du gouvernement actuel et l\'historique',
        steps: [
            {
                target: '[data-tour="gouv-president"]',
                title: 'Sélection du président',
                content: 'Naviguez entre les différentes présidences pour voir l\'évolution des gouvernements.',
                position: 'bottom',
            },
            {
                target: '[data-tour="gouv-organigramme"]',
                title: 'Organigramme',
                content: 'Visualisez la composition complète du gouvernement : Premier ministre, ministres et secrétaires d\'État.',
                position: 'top',
            },
            {
                target: '[data-tour="gouv-ministeres"]',
                title: 'Ministères',
                content: 'Explorez les 16 domaines ministériels et leur historique à travers les différents gouvernements.',
                position: 'left',
            },
        ],
    },
    elu: {
        id: 'elu',
        name: 'Espace élu',
        icon: '👔',
        description: 'Guide pour les élus : gérez vos interpellations et votre profil',
        steps: [
            {
                target: '[data-tour="elu-stats"]',
                title: 'Vos statistiques',
                content: 'Suivez votre taux de réponse aux interpellations et votre délai moyen de réponse. Un bon taux améliore votre visibilité !',
                position: 'bottom',
            },
            {
                target: '[data-tour="elu-interpellations"]',
                title: 'Interpellations citoyennes',
                content: 'Consultez les questions que les citoyens vous adressent et répondez-y directement depuis cette interface.',
                position: 'left',
            },
            {
                target: '[data-tour="elu-respond"]',
                title: 'Répondre',
                content: 'Utilisez les modèles de réponse proposés ou rédigez une réponse personnalisée. Les citoyens seront notifiés.',
                position: 'right',
            },
            {
                target: '[data-tour="elu-profil"]',
                title: 'Votre profil public',
                content: 'Accédez à votre fiche publique pour voir ce que les citoyens voient de vous.',
                position: 'bottom',
            },
        ],
    },
    bienvenue: {
        id: 'bienvenue',
        name: 'Bienvenue sur CivicDash',
        icon: '👋',
        description: 'Découvrez les fonctionnalités essentielles de la plateforme',
        steps: [
            {
                target: '[data-tour="navigation"]',
                title: 'Bienvenue sur CivicDash !',
                content: 'CivicDash est la plateforme citoyenne qui rend la politique accessible. Commençons par découvrir les fonctionnalités principales.',
                position: 'bottom',
            },
            {
                target: '[data-tour="search"]',
                title: 'Recherche globale',
                content: 'Tapez ⌘K (ou Ctrl+K) pour ouvrir la recherche. Trouvez instantanément députés, sénateurs, lois ou discussions.',
                position: 'bottom',
            },
            {
                target: '[data-tour="dashboard-quick-actions"]',
                title: 'Accès rapides',
                content: 'Accédez en un clic aux sections principales : représentants, scrutins, idées citoyennes...',
                position: 'bottom',
            },
            {
                target: '[data-tour="notifications"]',
                title: 'Notifications',
                content: 'Restez informé des réponses à vos interpellations, des nouveaux scrutins et des mentions.',
                position: 'left',
            },
            {
                target: '[data-tour="user-menu"]',
                title: 'Votre profil',
                content: 'Accédez à vos paramètres, vos badges et la double authentification pour sécuriser votre compte.',
                position: 'left',
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
