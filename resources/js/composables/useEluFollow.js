import { ref, computed, reactive } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

/**
 * Composable pour gérer le suivi d'élus
 */
export function useEluFollow() {
    const page = usePage();
    const isAuthenticated = computed(() => !!page.props.auth?.user);
    
    // État réactif pour les suivis en cours de chargement
    const loadingStates = reactive({});
    const followStates = reactive({});

    /**
     * Générer une clé unique pour un élu
     */
    const getEluKey = (eluType, eluId) => `${eluType}:${eluId}`;

    /**
     * Vérifier si l'utilisateur suit un élu
     */
    const checkFollowStatus = async (eluType, eluId) => {
        if (!isAuthenticated.value) return false;

        const key = getEluKey(eluType, eluId);
        
        try {
            const response = await axios.get('/api/elu-follows/check', {
                params: { elu_type: eluType, elu_id: eluId }
            });
            
            followStates[key] = response.data.is_following;
            return response.data;
        } catch (error) {
            console.error('Erreur lors de la vérification du suivi:', error);
            return { is_following: false, data: null };
        }
    };

    /**
     * Suivre un élu
     */
    const followElu = async (eluType, eluId, preferences = {}) => {
        if (!isAuthenticated.value) {
            // Rediriger vers la connexion
            window.location.href = route('login', { redirect: window.location.pathname });
            return null;
        }

        const key = getEluKey(eluType, eluId);
        loadingStates[key] = true;

        try {
            const response = await axios.post('/api/elu-follows/follow', {
                elu_type: eluType,
                elu_id: eluId,
                preferences
            });

            followStates[key] = true;
            return response.data;
        } catch (error) {
            console.error('Erreur lors du suivi:', error);
            throw error;
        } finally {
            loadingStates[key] = false;
        }
    };

    /**
     * Ne plus suivre un élu
     */
    const unfollowElu = async (eluType, eluId) => {
        const key = getEluKey(eluType, eluId);
        loadingStates[key] = true;

        try {
            const response = await axios.post('/api/elu-follows/unfollow', {
                elu_type: eluType,
                elu_id: eluId
            });

            followStates[key] = false;
            return response.data;
        } catch (error) {
            console.error('Erreur lors du désabonnement:', error);
            throw error;
        } finally {
            loadingStates[key] = false;
        }
    };

    /**
     * Basculer le suivi
     */
    const toggleFollow = async (eluType, eluId, preferences = {}) => {
        const key = getEluKey(eluType, eluId);
        
        if (followStates[key]) {
            return await unfollowElu(eluType, eluId);
        } else {
            return await followElu(eluType, eluId, preferences);
        }
    };

    /**
     * Mettre à jour les préférences de suivi
     */
    const updatePreferences = async (eluType, eluId, preferences) => {
        try {
            const response = await axios.post('/api/elu-follows/preferences', {
                elu_type: eluType,
                elu_id: eluId,
                preferences
            });

            return response.data;
        } catch (error) {
            console.error('Erreur lors de la mise à jour des préférences:', error);
            throw error;
        }
    };

    /**
     * Récupérer la liste des élus suivis
     */
    const getMyFollowing = async () => {
        try {
            const response = await axios.get('/api/elu-follows');
            return response.data;
        } catch (error) {
            console.error('Erreur lors de la récupération des suivis:', error);
            return { count: 0, data: [] };
        }
    };

    /**
     * Récupérer les stats d'un élu (nombre de followers)
     */
    const getEluStats = async (eluType, eluId) => {
        try {
            const response = await axios.get('/api/elu-follows/stats', {
                params: { elu_type: eluType, elu_id: eluId }
            });
            return response.data;
        } catch (error) {
            console.error('Erreur lors de la récupération des stats:', error);
            return { followers_count: 0, preferences: {} };
        }
    };

    /**
     * Vérifier si un élu est en cours de chargement
     */
    const isLoading = (eluType, eluId) => {
        const key = getEluKey(eluType, eluId);
        return loadingStates[key] || false;
    };

    /**
     * Vérifier si l'utilisateur suit un élu (état local)
     */
    const isFollowing = (eluType, eluId) => {
        const key = getEluKey(eluType, eluId);
        return followStates[key] || false;
    };

    /**
     * Définir l'état de suivi (pour initialisation)
     */
    const setFollowState = (eluType, eluId, state) => {
        const key = getEluKey(eluType, eluId);
        followStates[key] = state;
    };

    /**
     * Types d'activités disponibles
     */
    const activityTypes = {
        votes: {
            label: 'Votes en séance',
            description: 'Scrutins publics à l\'Assemblée ou au Sénat',
            icon: '🗳️',
        },
        interventions: {
            label: 'Interventions',
            description: 'Questions au gouvernement, débats',
            icon: '🎤',
        },
        amendements: {
            label: 'Amendements',
            description: 'Amendements déposés ou co-signés',
            icon: '📝',
        },
        propositions: {
            label: 'Propositions de loi',
            description: 'Propositions de loi déposées',
            icon: '📜',
        },
        rapports: {
            label: 'Rapports',
            description: 'Rapports parlementaires',
            icon: '📊',
        },
        commissions: {
            label: 'Commissions',
            description: 'Activité en commission',
            icon: '👥',
        },
        actualites: {
            label: 'Actualités',
            description: 'Changements de fonction, groupe politique',
            icon: '📰',
        },
    };

    return {
        isAuthenticated,
        followStates,
        loadingStates,
        checkFollowStatus,
        followElu,
        unfollowElu,
        toggleFollow,
        updatePreferences,
        getMyFollowing,
        getEluStats,
        isLoading,
        isFollowing,
        setFollowState,
        activityTypes,
    };
}
