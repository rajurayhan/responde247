import Swal from 'sweetalert2';

// Success alert
export const showSuccess = (title, message = '') => {
  return Swal.fire({
    icon: 'success',
    title: title,
    text: message,
    confirmButtonColor: '#10b981', // Green color
    confirmButtonText: 'OK',
    timer: 3000,
    timerProgressBar: true
  });
};

// Error alert
export const showError = (title, message = '') => {
  return Swal.fire({
    icon: 'error',
    title: title,
    text: message,
    confirmButtonColor: '#ef4444', // Red color
    confirmButtonText: 'OK'
  });
};

// Warning alert
export const showWarning = (title, message = '') => {
  return Swal.fire({
    icon: 'warning',
    title: title,
    text: message,
    confirmButtonColor: '#f59e0b', // Amber color
    confirmButtonText: 'OK'
  });
};

// Info alert
export const showInfo = (title, message = '') => {
  return Swal.fire({
    icon: 'info',
    title: title,
    text: message,
    confirmButtonColor: '#3b82f6', // Blue color
    confirmButtonText: 'OK',
    timer: 3000,
    timerProgressBar: true
  });
};

// Confirmation dialog
export const showConfirm = (title, message = '', confirmText = 'Yes', cancelText = 'No') => {
  return Swal.fire({
    icon: 'question',
    title: title,
    text: message,
    showCancelButton: true,
    confirmButtonColor: '#10b981', // Green color
    cancelButtonColor: '#6b7280', // Gray color
    confirmButtonText: confirmText,
    cancelButtonText: cancelText
  });
};

// Delete confirmation dialog
export const showDeleteConfirm = (title = 'Are you sure?', message = 'This action cannot be undone.') => {
  return Swal.fire({
    icon: 'warning',
    title: title,
    text: message,
    showCancelButton: true,
    confirmButtonColor: '#ef4444', // Red color
    cancelButtonColor: '#6b7280', // Gray color
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel'
  });
};

// Loading alert
export const showLoading = (title = 'Loading...') => {
  return Swal.fire({
    title: title,
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });
};

// Close any open alert
export const closeAlert = () => {
  Swal.close();
};

// Custom alert with custom options
export const showCustomAlert = (options) => {
  return Swal.fire({
    confirmButtonColor: '#10b981',
    ...options
  });
}; 