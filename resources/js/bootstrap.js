import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Add CSRF token to all requests (except API requests)
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Add authentication token to requests if available
window.axios.interceptors.request.use(function (config) {
    const token = localStorage.getItem('token');
    if (token) {
        // For Laravel Sanctum, we need to include the token in the Authorization header
        config.headers.Authorization = `Bearer ${token}`;
        // Also set the Accept header for JSON responses
        config.headers.Accept = 'application/json';
        
        // Remove CSRF token for API requests
        if (config.url && config.url.startsWith('/api/')) {
            delete config.headers['X-CSRF-TOKEN'];
        }
    }
    return config;
}, function (error) {
    return Promise.reject(error);
});

// Handle 401 responses
window.axios.interceptors.response.use(function (response) {
    return response;
}, function (error) {
    if (error.response && error.response.status === 401) {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '/login';
    }
    return Promise.reject(error);
}); 