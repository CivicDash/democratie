<template>
  <div v-if="loading" class="budget-context-skeleton">
    <div class="skeleton-line"></div>
    <div class="skeleton-line short"></div>
    <div class="skeleton-grid">
      <div class="skeleton-card"></div>
      <div class="skeleton-card"></div>
      <div class="skeleton-card"></div>
    </div>
  </div>

  <div v-else-if="error" class="budget-context-error">
    <p class="error-message">⚠️ {{ error }}</p>
  </div>

  <div v-else-if="context" class="budget-context">
    <div class="context-header">
      <h3 class="context-title">💰 Contexte Budgétaire</h3>
      <p class="context-subtitle">
        {{ context.commune.nom }} ({{ context.commune.population.toLocaleString('fr-FR') }} habitants)
      </p>
    </div>

    <!-- Messages lisibles -->
    <div v-if="context.contexte_lisible" class="context-messages">
      <p class="message-principal">
        <strong>{{ context.contexte_lisible.principal }}</strong>
      </p>
      <p class="message-secondary">
        {{ context.contexte_lisible.par_habitant }}
      </p>
      <p class="message-secondary">
        {{ context.contexte_lisible.temporel }}
      </p>
    </div>

    <!-- Statistiques en cartes -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-content">
          <span class="stat-label">Part du budget</span>
          <span class="stat-value">{{ context.impact.pourcentage_budget_total }}%</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-content">
          <span class="stat-label">Coût par habitant</span>
          <span class="stat-value">{{ formatMontant(context.impact.cout_par_habitant) }}</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">🏗️</div>
        <div class="stat-content">
          <span class="stat-label">Part de l'investissement</span>
          <span class="stat-value">{{ context.impact.pourcentage_investissement }}%</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">⏱️</div>
        <div class="stat-content">
          <span class="stat-label">Équivalent budget</span>
          <span class="stat-value">{{ formatDuree(context.impact.equivalent_jours_budget) }}</span>
        </div>
      </div>
    </div>

    <!-- Comparaisons avec les postes budgétaires -->
    <div v-if="context.comparaisons && context.comparaisons.length > 0" class="comparaisons">
      <h4 class="comparaisons-title">📈 Comparaison avec les postes budgétaires</h4>
      <div class="comparaisons-list">
        <div
          v-for="(comp, index) in context.comparaisons"
          :key="index"
          class="comparaison-item"
          :class="{ 'pertinent': comp.pertinent }"
        >
          <div class="comparaison-header">
            <span class="comparaison-nom">{{ comp.poste }}</span>
            <span v-if="comp.pertinent" class="badge-pertinent">Catégorie similaire</span>
          </div>
          <div class="comparaison-details">
            <span class="comparaison-pourcentage">{{ comp.pourcentage_du_poste.toFixed(1) }}%</span>
            <span class="comparaison-montant">du budget {{ comp.poste.toLowerCase() }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer avec source -->
    <div class="context-footer">
      <p class="context-source">
        <svg class="source-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Données officielles <a href="https://www.data.gouv.fr" target="_blank" rel="noopener">data.gouv.fr</a>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  codeInsee: {
    type: String,
    required: true,
    validator: (value) => /^\d{5}$/.test(value),
  },
  montant: {
    type: Number,
    required: true,
    validator: (value) => value > 0,
  },
  categorie: {
    type: String,
    default: null,
  },
  autoLoad: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['loaded', 'error']);

const context = ref(null);
const loading = ref(false);
const error = ref(null);

/**
 * Charge le contexte budgétaire depuis l'API
 */
const loadContext = async () => {
  if (!props.codeInsee || !props.montant) {
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    const response = await axios.get('/api/datagouv/project/context', {
      params: {
        code_insee: props.codeInsee,
        montant: props.montant,
        categorie: props.categorie,
      },
    });

    if (response.data.success) {
      context.value = response.data.data;
      emit('loaded', context.value);
    } else {
      throw new Error(response.data.error || 'Erreur inconnue');
    }
  } catch (err) {
    const message = err.response?.data?.error || err.message || 'Impossible de charger le contexte budgétaire';
    error.value = message;
    emit('error', message);
    console.error('Erreur chargement contexte budget:', err);
  } finally {
    loading.value = false;
  }
};

/**
 * Formate un montant en euros
 */
const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(montant);
};

/**
 * Formate une durée (jours) en format lisible
 */
const formatDuree = (jours) => {
  if (jours < 1) {
    const heures = jours * 24;
    if (heures < 1) {
      return '< 1 heure';
    }
    return `${heures.toFixed(1)} heures`;
  } else if (jours < 30) {
    return `${jours.toFixed(1)} jours`;
  } else if (jours < 365) {
    const mois = jours / 30;
    return `${mois.toFixed(1)} mois`;
  } else {
    const annees = jours / 365;
    return `${annees.toFixed(1)} ans`;
  }
};

// Charger au montage si autoLoad
onMounted(() => {
  if (props.autoLoad) {
    loadContext();
  }
});

// Recharger si les props changent
watch(() => [props.codeInsee, props.montant, props.categorie], () => {
  if (props.autoLoad) {
    loadContext();
  }
});

// Exposer la méthode de rechargement
defineExpose({
  reload: loadContext,
});
</script>

<style scoped>
.budget-context {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  padding: 24px;
  color: white;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.context-header {
  margin-bottom: 20px;
}

.context-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 8px 0;
}

.context-subtitle {
  font-size: 0.95rem;
  opacity: 0.9;
  margin: 0;
}

.context-messages {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 24px;
}

.message-principal {
  font-size: 1.1rem;
  margin: 0 0 12px 0;
  line-height: 1.5;
}

.message-secondary {
  font-size: 0.95rem;
  opacity: 0.9;
  margin: 8px 0 0 0;
  line-height: 1.4;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.stat-icon {
  font-size: 2rem;
}

.stat-content {
  display: flex;
  flex-direction: column;
}

.stat-label {
  font-size: 0.85rem;
  opacity: 0.9;
  margin-bottom: 4px;
}

.stat-value {
  font-size: 1.3rem;
  font-weight: 700;
}

.comparaisons {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
}

.comparaisons-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0 0 16px 0;
}

.comparaisons-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.comparaison-item {
  background: rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  padding: 12px;
  transition: background 0.2s;
}

.comparaison-item.pertinent {
  background: rgba(255, 255, 255, 0.25);
  border: 2px solid rgba(255, 255, 255, 0.4);
}

.comparaison-item:hover {
  background: rgba(255, 255, 255, 0.2);
}

.comparaison-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.comparaison-nom {
  font-weight: 600;
  font-size: 0.95rem;
}

.badge-pertinent {
  background: rgba(52, 211, 153, 0.9);
  color: white;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.comparaison-details {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.comparaison-pourcentage {
  font-size: 1.2rem;
  font-weight: 700;
}

.comparaison-montant {
  font-size: 0.85rem;
  opacity: 0.9;
}

.context-footer {
  padding-top: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.context-source {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.85rem;
  opacity: 0.9;
  margin: 0;
}

.source-icon {
  width: 16px;
  height: 16px;
}

.context-source a {
  color: white;
  text-decoration: underline;
  font-weight: 600;
}

.context-source a:hover {
  opacity: 0.8;
}

/* États de chargement et erreur */
.budget-context-skeleton {
  background: #f3f4f6;
  border-radius: 16px;
  padding: 24px;
}

.skeleton-line {
  height: 24px;
  background: linear-gradient(90deg, #e5e7eb 25%, #d1d5db 50%, #e5e7eb 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
  margin-bottom: 12px;
}

.skeleton-line.short {
  width: 60%;
}

.skeleton-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-top: 24px;
}

.skeleton-card {
  height: 80px;
  background: linear-gradient(90deg, #e5e7eb 25%, #d1d5db 50%, #e5e7eb 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 12px;
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

.budget-context-error {
  background: #fef2f2;
  border: 2px solid #fecaca;
  border-radius: 12px;
  padding: 20px;
}

.error-message {
  color: #dc2626;
  font-size: 0.95rem;
  margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
  .budget-context {
    padding: 16px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .context-title {
    font-size: 1.2rem;
  }

  .message-principal {
    font-size: 1rem;
  }
}
</style>

