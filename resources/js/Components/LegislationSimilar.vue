<template>
  <div v-if="loading" class="legislation-skeleton">
    <div class="skeleton-line"></div>
    <div class="skeleton-card"></div>
    <div class="skeleton-card"></div>
  </div>

  <div v-else-if="error" class="legislation-error">
    <p class="error-message">⚠️ {{ error }}</p>
  </div>

  <div v-else-if="similarProposals && similarProposals.length > 0" class="legislation-similar">
    <div class="similar-header">
      <h3 class="similar-title">
        🏛️ Propositions similaires au Parlement
      </h3>
      <p class="similar-subtitle">
        Nous avons trouvé {{ similarProposals.length }} proposition(s) en discussion qui ressemble(nt) à votre idée !
      </p>
    </div>

    <div class="propositions-list">
      <div 
        v-for="(item, index) in similarProposals" 
        :key="index"
        class="proposition-card"
        :class="`score-${getScoreClass(item.score)}`"
      >
        <!-- Score de similarité -->
        <div class="proposition-score">
          <div class="score-circle" :style="{ background: getScoreColor(item.score) }">
            <span class="score-value">{{ Math.round(item.score * 100) }}%</span>
            <span class="score-label">similaire</span>
          </div>
        </div>

        <!-- Contenu -->
        <div class="proposition-content">
          <!-- Header -->
          <div class="proposition-header">
            <span class="proposition-badge" :class="`badge-${item.proposition.source}`">
              {{ item.proposition.source === 'assemblee' ? '🏛️ Assemblée' : '🏛️ Sénat' }}
            </span>
            <span class="proposition-numero">{{ item.proposition.numero }}</span>
            <span class="proposition-statut" :class="`statut-${item.proposition.statut}`">
              {{ getStatutLabel(item.proposition.statut) }}
            </span>
          </div>

          <!-- Titre -->
          <h4 class="proposition-titre">{{ item.proposition.titre }}</h4>

          <!-- Raisons de similarité -->
          <div v-if="item.raisons && item.raisons.length > 0" class="proposition-raisons">
            <p class="raisons-title">💡 Pourquoi c'est similaire :</p>
            <ul class="raisons-list">
              <li v-for="(raison, idx) in item.raisons" :key="idx">
                {{ raison }}
              </li>
            </ul>
          </div>

          <!-- Métadonnées -->
          <div class="proposition-meta">
            <span v-if="item.proposition.auteurs && item.proposition.auteurs.length > 0" class="meta-item">
              👤 {{ getAuteurPrincipal(item.proposition.auteurs) }}
              <span v-if="item.proposition.auteurs.length > 1" class="meta-extra">
                +{{ item.proposition.auteurs.length - 1 }} autre(s)
              </span>
            </span>
            <span v-if="item.proposition.date_depot" class="meta-item">
              📅 {{ formatDate(item.proposition.date_depot) }}
            </span>
            <span v-if="item.proposition.theme" class="meta-item">
              🏷️ {{ item.proposition.theme }}
            </span>
          </div>

          <!-- 👍👎 VOTES CITOYENS -->
          <div v-if="item.proposition.id" class="proposition-votes">
            <PropositionVote
              :proposition-id="item.proposition.id"
              :show-details="false"
              :can-vote="canVote"
              @voted="onVoted(item)"
            />
          </div>

          <!-- Actions -->
          <div class="proposition-actions">
            <a 
              v-if="item.proposition.url_externe" 
              :href="item.proposition.url_externe" 
              target="_blank" 
              rel="noopener noreferrer"
              class="btn btn-primary"
            >
              📄 Voir le texte complet
            </a>
            <button 
              @click="shareProposition(item.proposition)" 
              class="btn btn-secondary"
            >
              📤 Partager
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- CTA -->
    <div class="similar-cta">
      <div class="cta-content">
        <h4 class="cta-title">💡 Votre proposition pourrait enrichir ce débat !</h4>
        <p class="cta-text">
          Ces textes sont actuellement en discussion au Parlement. Votre expérience locale et vos idées
          pourraient être précieuses pour améliorer ces propositions.
        </p>
        <div class="cta-actions">
          <button @click="contactDepute" class="btn btn-primary btn-lg">
            📧 Contacter mon député
          </button>
          <button @click="followPropositions" class="btn btn-secondary btn-lg">
            🔔 Suivre ces propositions
          </button>
        </div>
      </div>
    </div>
  </div>

  <div v-else-if="searched && similarProposals && similarProposals.length === 0" class="legislation-empty">
    <div class="empty-icon">🎉</div>
    <h3 class="empty-title">Votre idée est unique !</h3>
    <p class="empty-text">
      Nous n'avons trouvé aucune proposition similaire au Parlement.
      C'est peut-être le moment de la proposer officiellement !
    </p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import PropositionVote from './PropositionVote.vue';

const props = defineProps({
  titre: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
  tags: {
    type: Array,
    default: () => [],
  },
  autoLoad: {
    type: Boolean,
    default: false,
  },
  canVote: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['loaded', 'error', 'contact-depute', 'follow']);

const similarProposals = ref(null);
const loading = ref(false);
const error = ref(null);
const searched = ref(false);

/**
 * Recherche les propositions similaires
 */
const findSimilar = async () => {
  if (!props.titre || !props.description) {
    return;
  }

  loading.value = true;
  error.value = null;
  searched.value = false;

  try {
    const response = await axios.post('/api/legislation/find-similar', {
      titre: props.titre,
      description: props.description,
      tags: props.tags,
    });

    if (response.data.success) {
      similarProposals.value = response.data.data;
      searched.value = true;
      emit('loaded', similarProposals.value);
    }
  } catch (err) {
    const message = err.response?.data?.error || err.message || 'Erreur lors de la recherche';
    error.value = message;
    emit('error', message);
    console.error('Erreur recherche propositions similaires:', err);
  } finally {
    loading.value = false;
  }
};

/**
 * Obtient la classe CSS selon le score
 */
const getScoreClass = (score) => {
  if (score >= 0.7) return 'high';
  if (score >= 0.4) return 'medium';
  return 'low';
};

/**
 * Obtient la couleur selon le score
 */
const getScoreColor = (score) => {
  if (score >= 0.7) return 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
  if (score >= 0.4) return 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)';
  return 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)';
};

/**
 * Obtient le label du statut
 */
const getStatutLabel = (statut) => {
  const labels = {
    'en_cours': '🔄 En cours',
    'adoptee': '✅ Adoptée',
    'rejetee': '❌ Rejetée',
    'promulguee': '📜 Promulguée',
  };
  return labels[statut] || statut;
};

/**
 * Obtient l'auteur principal
 */
const getAuteurPrincipal = (auteurs) => {
  if (!auteurs || auteurs.length === 0) return 'Auteur inconnu';
  const premier = auteurs[0];
  return typeof premier === 'string' ? premier : (premier.nom || 'Auteur inconnu');
};

/**
 * Formate une date
 */
const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

/**
 * Partage une proposition
 */
const shareProposition = (proposition) => {
  if (navigator.share) {
    navigator.share({
      title: proposition.titre,
      text: `Proposition de loi ${proposition.numero} - ${proposition.titre}`,
      url: proposition.url_externe,
    });
  } else {
    // Fallback: copier l'URL
    navigator.clipboard.writeText(proposition.url_externe);
    alert('Lien copié dans le presse-papiers !');
  }
};

/**
 * Contacter son député
 */
const contactDepute = () => {
  emit('contact-depute', similarProposals.value);
};

/**
 * Suivre les propositions
 */
const followPropositions = () => {
  emit('follow', similarProposals.value);
};

/**
 * Callback quand un utilisateur vote
 */
const onVoted = (item) => {
  console.log('Vote enregistré pour:', item.proposition.titre);
};

// Charger au montage si autoLoad
if (props.autoLoad) {
  findSimilar();
}

// Exposer la méthode de recherche
defineExpose({
  findSimilar,
});
</script>

<style scoped>
.legislation-similar {
  background: #f8f9fa;
  border-radius: 16px;
  padding: 24px;
  margin: 24px 0;
}

.similar-header {
  text-align: center;
  margin-bottom: 32px;
}

.similar-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 12px 0;
}

.similar-subtitle {
  font-size: 1.05rem;
  color: #718096;
  margin: 0;
}

.propositions-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.proposition-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
  display: flex;
  gap: 20px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.proposition-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
}

.proposition-card.score-high {
  border-left: 4px solid #667eea;
}

.proposition-card.score-medium {
  border-left: 4px solid #f093fb;
}

.proposition-card.score-low {
  border-left: 4px solid #4facfe;
}

.proposition-score {
  flex-shrink: 0;
}

.score-circle {
  width: 90px;
  height: 90px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.score-value {
  font-size: 1.5rem;
  font-weight: 700;
  display: block;
}

.score-label {
  font-size: 0.7rem;
  text-transform: uppercase;
  opacity: 0.9;
}

.proposition-content {
  flex: 1;
}

.proposition-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.proposition-badge {
  padding: 4px 12px;
  border-radius: 16px;
  font-size: 0.85rem;
  font-weight: 600;
}

.badge-assemblee {
  background: #ebf4ff;
  color: #2b6cb0;
}

.badge-senat {
  background: #f0fdf4;
  color: #166534;
}

.proposition-numero {
  font-size: 0.9rem;
  color: #718096;
  font-weight: 600;
}

.proposition-statut {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
}

.statut-en_cours {
  background: #fef3c7;
  color: #92400e;
}

.statut-adoptee {
  background: #d1fae5;
  color: #065f46;
}

.statut-rejetee {
  background: #fee2e2;
  color: #991b1b;
}

.proposition-titre {
  font-size: 1.2rem;
  font-weight: 600;
  color: #1a202c;
  margin: 0 0 16px 0;
  line-height: 1.4;
}

.proposition-raisons {
  background: #edf2f7;
  border-radius: 8px;
  padding: 12px 16px;
  margin-bottom: 16px;
}

.raisons-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 8px 0;
}

.raisons-list {
  margin: 0;
  padding-left: 20px;
  list-style: none;
}

.raisons-list li {
  font-size: 0.9rem;
  color: #4a5568;
  margin-bottom: 4px;
  position: relative;
}

.raisons-list li::before {
  content: "→";
  position: absolute;
  left: -16px;
  color: #667eea;
}

.proposition-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 16px;
}

.proposition-votes {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
}

.meta-item {
  font-size: 0.9rem;
  color: #718096;
  display: flex;
  align-items: center;
  gap: 4px;
}

.meta-extra {
  color: #a0aec0;
  font-size: 0.85rem;
}

.proposition-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.btn {
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover {
  background: #5a67d8;
}

.btn-secondary {
  background: #e2e8f0;
  color: #2d3748;
}

.btn-secondary:hover {
  background: #cbd5e0;
}

.btn-lg {
  padding: 12px 24px;
  font-size: 1rem;
}

.similar-cta {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  padding: 32px;
  margin-top: 32px;
  color: white;
  text-align: center;
}

.cta-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 12px 0;
}

.cta-text {
  font-size: 1.05rem;
  line-height: 1.6;
  opacity: 0.95;
  margin: 0 0 24px 0;
  max-width: 700px;
  margin-left: auto;
  margin-right: auto;
}

.cta-actions {
  display: flex;
  justify-content: center;
  gap: 16px;
  flex-wrap: wrap;
}

.cta-actions .btn {
  background: white;
  color: #667eea;
}

.cta-actions .btn:hover {
  background: #f7fafc;
}

/* États de chargement et erreur */
.legislation-skeleton,
.legislation-error,
.legislation-empty {
  padding: 24px;
  text-align: center;
}

.skeleton-line,
.skeleton-card {
  height: 60px;
  background: linear-gradient(90deg, #e2e8f0 25%, #cbd5e0 50%, #e2e8f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 8px;
  margin-bottom: 16px;
}

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

.error-message {
  color: #e53e3e;
  font-size: 1rem;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 16px;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 12px 0;
}

.empty-text {
  font-size: 1.05rem;
  color: #718096;
  max-width: 500px;
  margin: 0 auto;
}

/* Responsive */
@media (max-width: 768px) {
  .proposition-card {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .proposition-actions {
    justify-content: center;
  }

  .cta-actions {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>

