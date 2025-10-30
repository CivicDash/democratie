import { ref, reactive } from 'vue';

// Confirmation modal state
const modalState = reactive({
  show: false,
  title: '',
  message: '',
  type: 'info',
  confirmLabel: 'Confirmer',
  cancelLabel: 'Annuler',
  loading: false,
  onConfirm: null,
  onCancel: null,
});

/**
 * Composable for confirmation modals
 */
export function useConfirm() {
  /**
   * Show a confirmation modal
   */
  const confirm = (options) => {
    return new Promise((resolve, reject) => {
      modalState.show = true;
      modalState.title = options.title || 'Confirmation';
      modalState.message = options.message || 'Êtes-vous sûr ?';
      modalState.type = options.type || 'info';
      modalState.confirmLabel = options.confirmLabel || 'Confirmer';
      modalState.cancelLabel = options.cancelLabel || 'Annuler';
      modalState.loading = false;

      modalState.onConfirm = async () => {
        if (options.onConfirm) {
          modalState.loading = true;
          try {
            await options.onConfirm();
            modalState.loading = false;
            modalState.show = false;
            resolve(true);
          } catch (error) {
            modalState.loading = false;
            reject(error);
          }
        } else {
          modalState.show = false;
          resolve(true);
        }
      };

      modalState.onCancel = () => {
        modalState.show = false;
        if (options.onCancel) {
          options.onCancel();
        }
        resolve(false);
      };
    });
  };

  /**
   * Show a danger confirmation modal
   */
  const confirmDanger = (message, title = 'Attention', options = {}) => {
    return confirm({
      ...options,
      type: 'danger',
      title,
      message,
      confirmLabel: options.confirmLabel || 'Supprimer',
    });
  };

  /**
   * Show a warning confirmation modal
   */
  const confirmWarning = (message, title = 'Avertissement', options = {}) => {
    return confirm({
      ...options,
      type: 'warning',
      title,
      message,
      confirmLabel: options.confirmLabel || 'Continuer',
    });
  };

  /**
   * Show an info confirmation modal
   */
  const confirmInfo = (message, title = 'Information', options = {}) => {
    return confirm({
      ...options,
      type: 'info',
      title,
      message,
      confirmLabel: options.confirmLabel || 'OK',
    });
  };

  /**
   * Close the modal
   */
  const close = () => {
    modalState.show = false;
  };

  return {
    modalState,
    confirm,
    confirmDanger,
    confirmWarning,
    confirmInfo,
    close,
  };
}

