<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  icon: {
    type: String,
    default: '📭',
  },
  title: {
    type: String,
    default: 'Aucun contenu',
  },
  description: {
    type: String,
    default: 'Il n\'y a rien ici pour le moment',
  },
  actionLabel: {
    type: String,
    default: null,
  },
  actionHref: {
    type: String,
    default: null,
  },
  actionType: {
    type: String,
    default: 'button', // button or link
  },
  size: {
    type: String,
    default: 'medium', // small, medium, large
    validator: (value) => ['small', 'medium', 'large'].includes(value),
  },
});

const emit = defineEmits(['action']);

const handleAction = () => {
  emit('action');
};

const sizeClasses = {
  small: 'empty-state-small',
  medium: 'empty-state-medium',
  large: 'empty-state-large',
};
</script>

<template>
  <div :class="['empty-state', sizeClasses[size]]">
    <div class="empty-icon">{{ icon }}</div>
    <h3 class="empty-title">{{ title }}</h3>
    <p class="empty-description">{{ description }}</p>
    
    <div v-if="actionLabel" class="empty-action">
      <Link
        v-if="actionType === 'link' && actionHref"
        :href="actionHref"
        class="empty-button"
      >
        {{ actionLabel }}
      </Link>
      <button
        v-else
        @click="handleAction"
        class="empty-button"
      >
        {{ actionLabel }}
      </button>
    </div>

    <div v-if="$slots.default" class="empty-slot">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 48px 24px;
  background: #f9fafb;
  border-radius: 12px;
  border: 2px dashed #e5e7eb;
  transition: all 0.3s;
}

.empty-state:hover {
  border-color: #d1d5db;
  background: #f3f4f6;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 16px;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 8px 0;
}

.empty-description {
  font-size: 1rem;
  color: #6b7280;
  margin: 0 0 24px 0;
  max-width: 400px;
  line-height: 1.6;
}

.empty-action {
  margin-bottom: 16px;
}

.empty-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 12px 24px;
  background: #3b82f6;
  color: white;
  font-weight: 600;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}

.empty-button:hover {
  background: #2563eb;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.empty-button:active {
  transform: translateY(0);
}

.empty-slot {
  margin-top: 16px;
}

/* Size variants */
.empty-state-small {
  padding: 32px 16px;
}

.empty-state-small .empty-icon {
  font-size: 3rem;
  margin-bottom: 12px;
}

.empty-state-small .empty-title {
  font-size: 1.25rem;
}

.empty-state-small .empty-description {
  font-size: 0.875rem;
  margin-bottom: 16px;
}

.empty-state-large {
  padding: 64px 32px;
}

.empty-state-large .empty-icon {
  font-size: 6rem;
  margin-bottom: 24px;
}

.empty-state-large .empty-title {
  font-size: 2rem;
}

.empty-state-large .empty-description {
  font-size: 1.125rem;
  margin-bottom: 32px;
  max-width: 500px;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .empty-state {
    background: #1e293b;
    border-color: #334155;
  }

  .empty-state:hover {
    border-color: #475569;
    background: #0f172a;
  }

  .empty-title {
    color: #f1f5f9;
  }

  .empty-description {
    color: #94a3b8;
  }
}

/* Responsive */
@media (max-width: 640px) {
  .empty-state {
    padding: 32px 16px;
  }

  .empty-icon {
    font-size: 3rem;
  }

  .empty-title {
    font-size: 1.25rem;
  }

  .empty-description {
    font-size: 0.875rem;
  }

  .empty-button {
    width: 100%;
  }
}
</style>
