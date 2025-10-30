<template>
  <div class="proposition-vote">
    <!-- Boutons de vote -->
    <div class="vote-buttons">
      <button
        @click="vote('upvote')"
        class="vote-btn upvote-btn"
        :class="{ active: userVote === 'upvote', loading: voting }"
        :disabled="voting || !canVote"
        :title="getUpvoteTitle()"
      >
        <span class="vote-icon">👍</span>
        <span class="vote-count">{{ stats.upvotes || 0 }}</span>
      </button>

      <button
        @click="vote('downvote')"
        class="vote-btn downvote-btn"
        :class="{ active: userVote === 'downvote', loading: voting }"
        :disabled="voting || !canVote"
        :title="getDownvoteTitle()"
      >
        <span class="vote-icon">👎</span>
        <span class="vote-count">{{ stats.downvotes || 0 }}</span>
      </button>
    </div>

    <!-- Score et statistiques -->
    <div class="vote-stats">
      <div class="score" :class="scoreClass">
        <span class="score-value">{{ formatScore(stats.score) }}</span>
        <span class="score-label">score</span>
      </div>

      <div v-if="showDetails" class="stats-details">
        <!-- Graphique circulaire -->
        <div class="circular-chart">
          <svg viewBox="0 0 100 100" class="chart-svg">
            <!-- Background circle -->
            <circle
              cx="50"
              cy="50"
              r="40"
              fill="none"
              stroke="#f3f4f6"
              stroke-width="10"
            />
            <!-- Upvote arc (green) -->
            <circle
              v-if="stats.total > 0"
              cx="50"
              cy="50"
              r="40"
              fill="none"
              stroke="#10b981"
              stroke-width="10"
              :stroke-dasharray="`${stats.pourcentage_pour * 2.51}, 251`"
              stroke-dashoffset="0"
              transform="rotate(-90 50 50)"
              class="chart-arc upvote-arc"
            />
            <!-- Downvote arc (red) -->
            <circle
              v-if="stats.total > 0"
              cx="50"
              cy="50"
              r="40"
              fill="none"
              stroke="#ef4444"
              stroke-width="10"
              :stroke-dasharray="`${stats.pourcentage_contre * 2.51}, 251`"
              :stroke-dashoffset="`${-stats.pourcentage_pour * 2.51}`"
              transform="rotate(-90 50 50)"
              class="chart-arc downvote-arc"
            />
            <!-- Center text -->
            <text x="50" y="50" text-anchor="middle" dy="0.3em" class="chart-text">
              {{ stats.total }}
            </text>
          </svg>
          <div class="chart-legend">
            <div class="legend-item upvote-legend">
              <span class="legend-dot"></span>
              <span class="legend-label">Pour: {{ stats.pourcentage_pour }}%</span>
            </div>
            <div class="legend-item downvote-legend">
              <span class="legend-dot"></span>
              <span class="legend-label">Contre: {{ stats.pourcentage_contre }}%</span>
            </div>
          </div>
        </div>

        <div class="stat-row">
          <span class="stat-label">Total votes:</span>
          <span class="stat-value">{{ stats.total }}</span>
        </div>
        <div class="stat-row">
          <span class="stat-label">Soutien:</span>
          <span class="stat-value">{{ stats.pourcentage_pour }}%</span>
        </div>
        <div class="stat-row">
          <span class="stat-label">Contre:</span>
          <span class="stat-value">{{ stats.pourcentage_contre }}%</span>
        </div>
      </div>

      <!-- Barre de progression -->
      <div v-if="stats.total > 0" class="vote-progress">
        <div 
          class="progress-bar upvote-bar"
          :style="{ width: stats.pourcentage_pour + '%' }"
        ></div>
        <div 
          class="progress-bar downvote-bar"
          :style="{ width: stats.pourcentage_contre + '%' }"
        ></div>
      </div>
    </div>

    <!-- Message de feedback -->
    <transition name="fade">
      <div v-if="message" class="vote-message" :class="messageType">
        {{ message }}
      </div>
    </transition>

    <!-- Modal pour annuler le vote -->
    <div v-if="userVote && showCancelButton" class="cancel-vote">
      <button @click="removeVote" class="btn-cancel" :disabled="voting">
        <span class="cancel-icon">✖️</span>
        Annuler mon vote
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  propositionId: {
    type: Number,
    required: true,
  },
  showDetails: {
    type: Boolean,
    default: false,
  },
  showCancelButton: {
    type: Boolean,
    default: true,
  },
  autoLoad: {
    type: Boolean,
    default: true,
  },
  canVote: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['voted', 'removed', 'stats-loaded']);

const stats = ref({
  upvotes: 0,
  downvotes: 0,
  total: 0,
  score: 0,
  pourcentage_pour: 0,
  pourcentage_contre: 0,
});

const userVote = ref(null);
const voting = ref(false);
const message = ref('');
const messageType = ref('');

/**
 * Classe CSS selon le score
 */
const scoreClass = computed(() => {
  const score = stats.value.score;
  if (score > 50) return 'score-high';
  if (score > 0) return 'score-positive';
  if (score < -50) return 'score-low';
  if (score < 0) return 'score-negative';
  return 'score-neutral';
});

/**
 * Charge les statistiques de vote
 */
const loadStats = async () => {
  try {
    const response = await axios.get(`/api/legislation/propositions/${props.propositionId}/votes/stats`);
    
    if (response.data.success) {
      stats.value = response.data.data;
      emit('stats-loaded', stats.value);
    }
  } catch (error) {
    console.error('Erreur chargement stats:', error);
  }
};

/**
 * Charge le vote de l'utilisateur
 */
const loadUserVote = async () => {
  if (!props.canVote) return;

  try {
    const response = await axios.get(`/api/legislation/propositions/${props.propositionId}/my-vote`);
    
    if (response.data.success && response.data.data) {
      userVote.value = response.data.data.type;
    }
  } catch (error) {
    // Si 401, l'utilisateur n'est pas connecté
    if (error.response?.status !== 401) {
      console.error('Erreur chargement vote utilisateur:', error);
    }
  }
};

/**
 * Vote pour une proposition
 */
const vote = async (type) => {
  if (!props.canVote) {
    showMessage('Vous devez être connecté pour voter', 'error');
    return;
  }

  if (voting.value) return;

  // Si l'utilisateur reclique sur le même bouton, annuler le vote
  if (userVote.value === type) {
    await removeVote();
    return;
  }

  voting.value = true;
  message.value = '';

  try {
    const response = await axios.post(
      `/api/legislation/propositions/${props.propositionId}/vote`,
      { type }
    );

    if (response.data.success) {
      userVote.value = type;
      stats.value = response.data.data.stats;
      
      showMessage(response.data.message, 'success');
      emit('voted', { type, stats: stats.value });
    }
  } catch (error) {
    const errorMessage = error.response?.data?.message || 'Erreur lors du vote';
    showMessage(errorMessage, 'error');
    console.error('Erreur vote:', error);
  } finally {
    voting.value = false;
  }
};

/**
 * Annule le vote
 */
const removeVote = async () => {
  if (!props.canVote || voting.value) return;

  voting.value = true;
  message.value = '';

  try {
    const response = await axios.delete(
      `/api/legislation/propositions/${props.propositionId}/vote`
    );

    if (response.data.success) {
      userVote.value = null;
      stats.value = response.data.data.stats;
      
      showMessage('Vote annulé', 'info');
      emit('removed', { stats: stats.value });
    }
  } catch (error) {
    const errorMessage = error.response?.data?.message || 'Erreur lors de l\'annulation';
    showMessage(errorMessage, 'error');
    console.error('Erreur annulation vote:', error);
  } finally {
    voting.value = false;
  }
};

/**
 * Affiche un message temporaire
 */
const showMessage = (text, type = 'info') => {
  message.value = text;
  messageType.value = type;
  
  setTimeout(() => {
    message.value = '';
  }, 3000);
};

/**
 * Formate le score
 */
const formatScore = (score) => {
  if (score === 0) return '0';
  return score > 0 ? `+${score}` : score;
};

/**
 * Titre du bouton upvote
 */
const getUpvoteTitle = () => {
  if (!props.canVote) return 'Connectez-vous pour voter';
  if (userVote.value === 'upvote') return 'Retirer mon soutien';
  return 'Je soutiens cette proposition';
};

/**
 * Titre du bouton downvote
 */
const getDownvoteTitle = () => {
  if (!props.canVote) return 'Connectez-vous pour voter';
  if (userVote.value === 'downvote') return 'Retirer mon opposition';
  return 'Je suis contre cette proposition';
};

// Charger au montage
onMounted(async () => {
  if (props.autoLoad) {
    await Promise.all([loadStats(), loadUserVote()]);
  }
});

// Recharger si l'ID change
watch(() => props.propositionId, async () => {
  await Promise.all([loadStats(), loadUserVote()]);
});

// Exposer les méthodes
defineExpose({
  loadStats,
  loadUserVote,
  vote,
  removeVote,
});
</script>

<style scoped>
.proposition-vote {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.vote-buttons {
  display: flex;
  gap: 12px;
  align-items: center;
}

.vote-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 1rem;
  font-weight: 600;
}

.vote-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.vote-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.vote-btn.loading {
  opacity: 0.7;
  cursor: wait;
}

.upvote-btn {
  color: #059669;
  border-color: #d1fae5;
}

.upvote-btn:hover:not(:disabled) {
  background: #d1fae5;
  border-color: #059669;
}

.upvote-btn.active {
  background: #059669;
  color: white;
  border-color: #059669;
}

.downvote-btn {
  color: #dc2626;
  border-color: #fee2e2;
}

.downvote-btn:hover:not(:disabled) {
  background: #fee2e2;
  border-color: #dc2626;
}

.downvote-btn.active {
  background: #dc2626;
  color: white;
  border-color: #dc2626;
}

.vote-icon {
  font-size: 1.2rem;
}

.vote-count {
  font-weight: 700;
  min-width: 20px;
  text-align: center;
}

.vote-stats {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.score {
  display: flex;
  align-items: baseline;
  gap: 8px;
  font-weight: 700;
}

.score-value {
  font-size: 1.5rem;
}

.score-label {
  font-size: 0.85rem;
  opacity: 0.7;
  text-transform: uppercase;
}

.score-high {
  color: #059669;
}

.score-positive {
  color: #10b981;
}

.score-neutral {
  color: #6b7280;
}

.score-negative {
  color: #ef4444;
}

.score-low {
  color: #dc2626;
}

.stats-details {
  background: #f9fafb;
  border-radius: 8px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.circular-chart {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.chart-svg {
  width: 120px;
  height: 120px;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.chart-arc {
  transition: stroke-dasharray 0.5s ease, stroke-dashoffset 0.5s ease;
}

.chart-text {
  font-size: 1.5rem;
  font-weight: 700;
  fill: #1f2937;
}

.chart-legend {
  display: flex;
  gap: 16px;
  font-size: 0.85rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.upvote-legend .legend-dot {
  background: #10b981;
}

.downvote-legend .legend-dot {
  background: #ef4444;
}

.legend-label {
  font-weight: 600;
  color: #4b5563;
}

.stat-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
}

.stat-label {
  color: #6b7280;
}

.stat-value {
  font-weight: 600;
  color: #1f2937;
}

.vote-progress {
  position: relative;
  height: 8px;
  background: #f3f4f6;
  border-radius: 4px;
  overflow: hidden;
  display: flex;
}

.progress-bar {
  height: 100%;
  transition: width 0.3s ease;
}

.upvote-bar {
  background: linear-gradient(90deg, #10b981, #059669);
}

.downvote-bar {
  background: linear-gradient(90deg, #ef4444, #dc2626);
}

.vote-message {
  padding: 12px;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  text-align: center;
}

.vote-message.success {
  background: #d1fae5;
  color: #065f46;
}

.vote-message.error {
  background: #fee2e2;
  color: #991b1b;
}

.vote-message.info {
  background: #dbeafe;
  color: #1e40af;
}

.cancel-vote {
  display: flex;
  justify-content: center;
}

.btn-cancel {
  padding: 8px 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  color: #6b7280;
  font-size: 0.85rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
}

.btn-cancel:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #d1d5db;
  color: #1f2937;
}

.btn-cancel:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.cancel-icon {
  font-size: 0.8rem;
}

/* Animations */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Responsive */
@media (max-width: 640px) {
  .vote-buttons {
    flex-direction: column;
    width: 100%;
  }

  .vote-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>

