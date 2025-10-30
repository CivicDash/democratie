<script setup>
import { computed } from 'vue';

const props = defineProps({
  proposition: {
    type: Object,
    required: true,
  },
});

/**
 * Timeline steps based on proposition type and source
 */
const timelineSteps = computed(() => {
  const baseSteps = [
    {
      id: 'depot',
      label: 'Dépôt',
      icon: '📥',
      description: 'Proposition déposée',
    },
    {
      id: 'commission',
      label: 'Commission',
      icon: '👥',
      description: 'Examen en commission',
    },
    {
      id: 'discussion',
      label: 'Discussion',
      icon: '💬',
      description: 'Débat en séance publique',
    },
    {
      id: 'vote',
      label: 'Vote',
      icon: '🗳️',
      description: 'Vote en séance',
    },
    {
      id: 'adopte',
      label: 'Adoption',
      icon: '✅',
      description: props.proposition.source === 'assemblee' 
        ? 'Adopté par l\'Assemblée' 
        : 'Adopté par le Sénat',
    },
  ];

  // Si adopté, ajouter l'autre chambre
  if (props.proposition.statut === 'adopte' || props.proposition.statut === 'promulgue') {
    baseSteps.push({
      id: 'autre_chambre',
      label: props.proposition.source === 'assemblee' ? 'Sénat' : 'Assemblée',
      icon: props.proposition.source === 'assemblee' ? '🏰' : '🏛️',
      description: 'Examen par l\'autre chambre',
    });
  }

  // Si promulgué, ajouter l'étape finale
  if (props.proposition.statut === 'promulgue') {
    baseSteps.push({
      id: 'promulgue',
      label: 'Promulgation',
      icon: '📜',
      description: 'Loi promulguée',
    });
  }

  // Si rejeté, remplacer les dernières étapes
  if (props.proposition.statut === 'rejete') {
    return baseSteps.slice(0, 4).concat([{
      id: 'rejete',
      label: 'Rejet',
      icon: '❌',
      description: 'Proposition rejetée',
    }]);
  }

  return baseSteps;
});

/**
 * Get current step index based on statut
 */
const currentStepIndex = computed(() => {
  const statutMap = {
    'depot': 0,
    'commission': 1,
    'discussion': 2,
    'vote': 3,
    'adopte': 4,
    'autre_chambre': 5,
    'promulgue': 6,
    'rejete': 4,
  };

  return statutMap[props.proposition.statut] ?? 0;
});

/**
 * Check if step is completed
 */
const isStepCompleted = (stepIndex) => {
  return stepIndex < currentStepIndex.value;
};

/**
 * Check if step is current
 */
const isStepCurrent = (stepIndex) => {
  return stepIndex === currentStepIndex.value;
};

/**
 * Get step style
 */
const getStepStyle = (stepIndex) => {
  if (isStepCompleted(stepIndex)) {
    return 'completed';
  } else if (isStepCurrent(stepIndex)) {
    return 'current';
  } else {
    return 'pending';
  }
};
</script>

<template>
  <div class="legislative-timeline">
    <div class="timeline-container">
      <div
        v-for="(step, index) in timelineSteps"
        :key="step.id"
        :class="['timeline-step', getStepStyle(index)]"
      >
        <!-- Connector Line (before step) -->
        <div v-if="index > 0" class="timeline-connector" />

        <!-- Step Content -->
        <div class="step-content">
          <!-- Icon Circle -->
          <div class="step-icon">
            <span class="icon-emoji">{{ step.icon }}</span>
            <div v-if="isStepCompleted(index)" class="checkmark">✓</div>
          </div>

          <!-- Step Info -->
          <div class="step-info">
            <h4 class="step-label">{{ step.label }}</h4>
            <p class="step-description">{{ step.description }}</p>
            <span v-if="isStepCurrent(index)" class="current-badge">En cours</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Progress Bar -->
    <div class="progress-bar-container">
      <div
        class="progress-bar"
        :style="{ width: `${(currentStepIndex / (timelineSteps.length - 1)) * 100}%` }"
      />
    </div>

    <!-- Summary -->
    <div class="timeline-summary">
      <div class="summary-item">
        <span class="summary-label">Étape actuelle:</span>
        <span class="summary-value">{{ timelineSteps[currentStepIndex]?.label }}</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Progression:</span>
        <span class="summary-value">
          {{ Math.round((currentStepIndex / (timelineSteps.length - 1)) * 100) }}%
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.legislative-timeline {
  width: 100%;
}

.timeline-container {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding: 24px;
  background: linear-gradient(to bottom, #f8fafc 0%, #ffffff 100%);
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.timeline-step {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.timeline-connector {
  position: absolute;
  left: 24px;
  top: -24px;
  width: 2px;
  height: 24px;
  background: #e2e8f0;
}

.timeline-step.completed .timeline-connector {
  background: #22c55e;
}

.timeline-step.current .timeline-connector {
  background: linear-gradient(to bottom, #22c55e 0%, #3b82f6 100%);
}

.step-content {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  width: 100%;
}

.step-icon {
  position: relative;
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  background: white;
  border: 2px solid #e2e8f0;
  transition: all 0.3s;
}

.timeline-step.completed .step-icon {
  background: #22c55e;
  border-color: #22c55e;
  color: white;
}

.timeline-step.completed .icon-emoji {
  display: none;
}

.timeline-step.current .step-icon {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
  animation: pulse 2s ease-in-out infinite;
}

.timeline-step.pending .step-icon {
  opacity: 0.5;
}

.checkmark {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: bold;
  color: white;
}

.step-info {
  flex: 1;
  padding-top: 4px;
}

.step-label {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 4px 0;
}

.timeline-step.completed .step-label {
  color: #22c55e;
}

.timeline-step.current .step-label {
  color: #3b82f6;
}

.timeline-step.pending .step-label {
  color: #94a3b8;
}

.step-description {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

.current-badge {
  display: inline-block;
  margin-top: 8px;
  padding: 4px 12px;
  background: #3b82f6;
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 12px;
  animation: fadeIn 0.5s;
}

.progress-bar-container {
  margin-top: 24px;
  height: 8px;
  background: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(to right, #22c55e 0%, #3b82f6 100%);
  border-radius: 4px;
  transition: width 0.5s ease;
}

.timeline-summary {
  margin-top: 16px;
  display: flex;
  justify-content: space-between;
  padding: 16px;
  background: #f1f5f9;
  border-radius: 8px;
}

.summary-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.summary-label {
  font-size: 0.75rem;
  color: #64748b;
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.summary-value {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1e293b;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-5px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .timeline-container {
    background: linear-gradient(to bottom, #1e293b 0%, #0f172a 100%);
    border-color: #334155;
  }

  .timeline-connector {
    background: #334155;
  }

  .step-icon {
    background: #1e293b;
    border-color: #334155;
  }

  .step-label {
    color: #f1f5f9;
  }

  .timeline-step.pending .step-label {
    color: #64748b;
  }

  .step-description {
    color: #94a3b8;
  }

  .progress-bar-container {
    background: #334155;
  }

  .timeline-summary {
    background: #1e293b;
  }

  .summary-label {
    color: #94a3b8;
  }

  .summary-value {
    color: #f1f5f9;
  }
}

/* Responsive */
@media (max-width: 640px) {
  .timeline-container {
    padding: 16px;
    gap: 16px;
  }

  .step-icon {
    width: 40px;
    height: 40px;
    font-size: 1.25rem;
  }

  .step-label {
    font-size: 1rem;
  }

  .step-description {
    font-size: 0.8rem;
  }

  .timeline-summary {
    flex-direction: column;
    gap: 12px;
  }
}
</style>

