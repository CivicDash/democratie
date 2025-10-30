<script setup>
const props = defineProps({
  show: {
    type: Boolean,
    required: true,
  },
  title: {
    type: String,
    default: 'Confirmation',
  },
  message: {
    type: String,
    required: true,
  },
  type: {
    type: String,
    default: 'info', // info, warning, danger
    validator: (value) => ['info', 'warning', 'danger'].includes(value),
  },
  confirmLabel: {
    type: String,
    default: 'Confirmer',
  },
  cancelLabel: {
    type: String,
    default: 'Annuler',
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['confirm', 'cancel', 'close']);

const handleConfirm = () => {
  emit('confirm');
};

const handleCancel = () => {
  emit('cancel');
  emit('close');
};

const handleBackdropClick = () => {
  if (!props.loading) {
    handleCancel();
  }
};
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="modal-overlay" @click="handleBackdropClick">
        <Transition name="modal-content">
          <div
            v-if="show"
            class="modal-container"
            @click.stop
            role="dialog"
            aria-modal="true"
          >
            <!-- Icon -->
            <div :class="['modal-icon', `modal-icon-${type}`]">
              <span v-if="type === 'info'">ℹ️</span>
              <span v-else-if="type === 'warning'">⚠️</span>
              <span v-else-if="type === 'danger'">🚨</span>
            </div>

            <!-- Content -->
            <div class="modal-content">
              <h3 class="modal-title">{{ title }}</h3>
              <p class="modal-message">{{ message }}</p>
            </div>

            <!-- Actions -->
            <div class="modal-actions">
              <button
                @click="handleCancel"
                :disabled="loading"
                class="modal-button modal-button-cancel"
              >
                {{ cancelLabel }}
              </button>
              <button
                @click="handleConfirm"
                :disabled="loading"
                :class="['modal-button', 'modal-button-confirm', `modal-button-${type}`]"
              >
                <span v-if="loading" class="button-spinner"></span>
                <span :class="{ 'opacity-0': loading }">{{ confirmLabel }}</span>
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  padding: 16px;
}

.modal-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
  max-width: 400px;
  width: 100%;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.modal-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  margin: 0 auto;
}

.modal-icon-info {
  background: rgba(59, 130, 246, 0.1);
}

.modal-icon-warning {
  background: rgba(245, 158, 11, 0.1);
}

.modal-icon-danger {
  background: rgba(239, 68, 68, 0.1);
}

.modal-content {
  text-align: center;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 8px 0;
}

.modal-message {
  font-size: 1rem;
  color: #6b7280;
  line-height: 1.6;
  margin: 0;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.modal-button {
  flex: 1;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.modal-button:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.modal-button:not(:disabled):active {
  transform: translateY(0);
}

.modal-button-cancel {
  background: #f3f4f6;
  color: #6b7280;
}

.modal-button-cancel:not(:disabled):hover {
  background: #e5e7eb;
  color: #374151;
}

.modal-button-confirm {
  color: white;
}

.modal-button-info {
  background: #3b82f6;
}

.modal-button-info:not(:disabled):hover {
  background: #2563eb;
}

.modal-button-warning {
  background: #f59e0b;
}

.modal-button-warning:not(:disabled):hover {
  background: #d97706;
}

.modal-button-danger {
  background: #ef4444;
}

.modal-button-danger:not(:disabled):hover {
  background: #dc2626;
}

.button-spinner {
  position: absolute;
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.opacity-0 {
  opacity: 0;
}

/* Transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-content-enter-active {
  transition: all 0.3s ease;
}

.modal-content-leave-active {
  transition: all 0.2s ease;
}

.modal-content-enter-from {
  opacity: 0;
  transform: scale(0.9) translateY(-20px);
}

.modal-content-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .modal-container {
    background: #1e293b;
  }

  .modal-title {
    color: #f1f5f9;
  }

  .modal-message {
    color: #94a3b8;
  }

  .modal-button-cancel {
    background: #334155;
    color: #94a3b8;
  }

  .modal-button-cancel:not(:disabled):hover {
    background: #475569;
    color: #e2e8f0;
  }
}

/* Responsive */
@media (max-width: 640px) {
  .modal-container {
    padding: 20px;
  }

  .modal-icon {
    width: 56px;
    height: 56px;
    font-size: 1.75rem;
  }

  .modal-title {
    font-size: 1.25rem;
  }

  .modal-message {
    font-size: 0.9375rem;
  }

  .modal-actions {
    flex-direction: column-reverse;
  }

  .modal-button {
    width: 100%;
  }
}
</style>

