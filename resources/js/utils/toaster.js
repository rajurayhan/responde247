// Simple toast functions that work with our custom Toast component
// These will be called from components where the toast instance is available

// Success toaster
export const showSuccessToast = (toast, message, duration = 3000) => {
  if (toast && toast.success) {
    return toast.success(message, duration)
  }
}

// Info toaster
export const showInfoToast = (toast, message, duration = 3000) => {
  if (toast && toast.info) {
    return toast.info(message, duration)
  }
}

// Warning toaster
export const showWarningToast = (toast, message, duration = 4000) => {
  if (toast && toast.warning) {
    return toast.warning(message, duration)
  }
}

// Error toaster
export const showErrorToast = (toast, message, duration = 5000) => {
  if (toast && toast.error) {
    return toast.error(message, duration)
  }
}

// Custom toaster
export const showCustomToast = (toast, message, type = 'info', duration = 3000) => {
  if (toast && toast.showToast) {
    return toast.showToast(message, type, duration)
  }
}

// Loading toaster
export const showLoadingToast = (toast, message = 'Loading...', options = {}) => {
  return toast.loading(message, {
    position: 'top-right',
    timeout: false,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: false,
    hideProgressBar: false,
    closeButton: 'button',
    icon: true,
    rtl: false,
    ...options,
  })
}

// Dismiss specific toast
export const dismissToast = (toast, toastId) => {
  toast.dismiss(toastId)
}

// Dismiss all toasts
export const dismissAllToasts = (toast) => {
  toast.dismiss()
} 