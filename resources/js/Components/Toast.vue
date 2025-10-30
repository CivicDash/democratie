<script setup>
import { computed } from 'vue';

const props = defineProps({
  id: {
    type: [String, Number],
    required: true,
  },
  type: {
    type: String,
    default: 'info', // success, error, warning, info
    validator: (value) => ['success', 'error', 'warning', 'info'].includes(value),
  },
  title: {
    type: String,
    default: null,
  },
  message: {
    type: String,
    required: true,
  },
  duration: {
    type: Number,
    default: 5000,
  },
  closable: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['close']);

const icon = computed(() => {
  const icons = {
    success: '✓',
    error: '✕',
    warning: '⚠',
    info: 'ℹ',
  };
  return icons[props.type];
});

const colorClasses = computed(() => {
  const classes = {
    success: 'toast-success',
    error: 'toast-error',
    warning: 'toast-warning',
    info: 'toast-info',
  };
  return classes[props.type];
});

const handleClose = () => {
  emit('close', props.id);
};

// Auto-close after duration
if (props.duration > 0) {
  setTimeout(() => {
    handleClose();
  }, props.duration);
}
</script>

<template>
  <div :class="['toast', colorClasses]" @click="closable && handleClose()">
    <div class="toast-icon">
      {{ icon }}
    </div>
    <div class="toast-content">
      <h4 v-if="title" class="toast-title">{{ title }}</h4>
      <p class="toast-message">{{ message }}</p>
    </div>
    <button
      v-if="closable"
      @click.stop="handleClose"
      class="toast-close"
      aria-label="Fermer"
    >
      ✕
    </button>
  </div>
</template>

<style scoped>
.toast {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  min-width: 320px;
  max-width: 420px;
  cursor: pointer;
  transition: all 0.3s;
  animation: slideIn 0.3s ease-out;
  backdrop-filter: blur(10px);
}

.toast:hover {
  transform: translateY(-2px);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.toast-icon {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  font-weight: 700;
  border-radius: 50%;
}

.toast-content {
  flex: 1;
  min-width: 0;
}

.toast-title {
  margin: 0 0 4px 0;
  font-size: 0.95rem;
  font-weight: 600;
}

.toast-message {
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.5;
}

.toast-close {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 1rem;
  opacity: 0.6;
  transition: opacity 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.toast-close:hover {
  opacity: 1;
}

/* Success */
.toast-success {
  background: rgba(220, 252, 231, 0.95);
  border-left: 4px solid #10b981;
}

.toast-success .toast-icon {
  color: #10b981;
  background: rgba(16, 185, 129, 0.1);
}

.toast-success .toast-title {
  color: #065f46;
}

.toast-success .toast-message {
  color: #047857;
}

.toast-success .toast-close {
  color: #047857;
}

/* Error */
.toast-error {
  background: rgba(254, 226, 226, 0.95);
  border-left: 4px solid #ef4444;
}

.toast-error .toast-icon {
  color: #ef4444;
  background: rgba(239, 68, 68, 0.1);
}

.toast-error .toast-title {
  color: #991b1b;
}

.toast-error .toast-message {
  color: #b91c1c;
}

.toast-error .toast-close {
  color: #b91c1c;
}

/* Warning */
.toast-warning {
  background: rgba(254, 243, 199, 0.95);
  border-left: 4px solid #f59e0b;
}

.toast-warning .toast-icon {
  color: #f59e0b;
  background: rgba(245, 158, 11, 0.1);
}

.toast-warning .toast-title {
  color: #78350f;
}

.toast-warning .toast-message {
  color: #92400e;
}

.toast-warning .toast-close {
  color: #92400e;
}

/* Info */
.toast-info {
  background: rgba(219, 234, 254, 0.95);
  border-left: 4px solid #3b82f6;
}

.toast-info .toast-icon {
  color: #3b82f6;
  background: rgba(59, 130, 246, 0.1);
}

.toast-info .toast-title {
  color: #1e3a8a;
}

.toast-info .toast-message {
  color: #1e40af;
}

.toast-info .toast-close {
  color: #1e40af;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .toast-success {
    background: rgba(6, 78, 59, 0.95);
  }

  .toast-error {
    background: rgba(127, 29, 29, 0.95);
  }

  .toast-warning {
    background: rgba(120, 53, 15, 0.95);
  }

  .toast-info {
    background: rgba(30, 58, 138, 0.95);
  }

  .toast-title {
    color: #f1f5f9 !important;
  }

  .toast-message {
    color: #e2e8f0 !important;
  }

  .toast-close {
    color: #e2e8f0 !important;
  }
}

/* Responsive */
@media (max-width: 640px) {
  .toast {
    min-width: 280px;
    max-width: calc(100vw - 32px);
    padding: 12px;
  }

  .toast-title {
    font-size: 0.875rem;
  }

  .toast-message {
    font-size: 0.8125rem;
  }
}
</style>

