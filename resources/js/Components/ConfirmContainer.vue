<script setup>
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useConfirm } from '@/composables/useConfirm';

const { modalState } = useConfirm();

const handleConfirm = () => {
  if (modalState.onConfirm) {
    modalState.onConfirm();
  }
};

const handleCancel = () => {
  if (modalState.onCancel) {
    modalState.onCancel();
  }
};

const handleClose = () => {
  modalState.show = false;
};
</script>

<template>
  <ConfirmModal
    :show="modalState.show"
    :title="modalState.title"
    :message="modalState.message"
    :type="modalState.type"
    :confirm-label="modalState.confirmLabel"
    :cancel-label="modalState.cancelLabel"
    :loading="modalState.loading"
    @confirm="handleConfirm"
    @cancel="handleCancel"
    @close="handleClose"
  />
</template>

