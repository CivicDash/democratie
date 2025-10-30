import { reactive } from 'vue';

// Toast state (reactive global state)
const toasts = reactive([]);
let nextId = 1;

/**
 * Composable for toast notifications
 */
export function useToast() {
  /**
   * Show a toast notification
   */
  const show = (options) => {
    const toast = {
      id: nextId++,
      type: options.type || 'info',
      title: options.title || null,
      message: options.message || '',
      duration: options.duration ?? 5000,
      closable: options.closable ?? true,
    };

    toasts.push(toast);

    return toast.id;
  };

  /**
   * Show success toast
   */
  const success = (message, title = null, duration = 5000) => {
    return show({ type: 'success', message, title, duration });
  };

  /**
   * Show error toast
   */
  const error = (message, title = null, duration = 5000) => {
    return show({ type: 'error', message, title, duration });
  };

  /**
   * Show warning toast
   */
  const warning = (message, title = null, duration = 5000) => {
    return show({ type: 'warning', message, title, duration });
  };

  /**
   * Show info toast
   */
  const info = (message, title = null, duration = 5000) => {
    return show({ type: 'info', message, title, duration });
  };

  /**
   * Close a specific toast
   */
  const close = (id) => {
    const index = toasts.findIndex((t) => t.id === id);
    if (index !== -1) {
      toasts.splice(index, 1);
    }
  };

  /**
   * Close all toasts
   */
  const clearAll = () => {
    toasts.splice(0, toasts.length);
  };

  return {
    toasts,
    show,
    success,
    error,
    warning,
    info,
    close,
    clearAll,
  };
}

