<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <div class="flex justify-center mb-6">
          <router-link to="/" class="flex items-center hover:opacity-80 transition-opacity duration-200">
            <div class="flex-shrink-0 flex items-center justify-center">
              <div class="flex items-center">
                <div v-if="branding.logoUrl" class="h-8 w-auto">
                  <img :src="branding.logoUrl" :alt="branding.appName" class="h-full w-auto">
                </div>
                <div v-else class="h-8 w-8 bg-green-600 rounded-lg flex items-center justify-center">
                  <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                  </svg>
                </div>
                <div class="ml-2">
                  <h1 class="text-xl font-bold text-gray-900">{{ branding.appName }}</h1>
                </div>
              </div>
            </div>
          </router-link>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Reset your password
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Enter your new password below
        </p>
      </div>
      
      <form class="mt-8 space-y-6" @submit.prevent="handleResetPassword" v-if="!passwordReset">
        <div class="space-y-4">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email address</label>
            <input
              id="email"
              v-model="form.email"
              name="email"
              type="email"
              autocomplete="email"
              required
              readonly
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200 bg-gray-50"
              placeholder="Enter your email"
              :class="{ 'border-red-500 focus:ring-red-500': errors.email }"
            />
            <p v-if="errors.email" class="mt-2 text-sm text-red-600">{{ errors.email }}</p>
          </div>
          
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
            <input
              id="password"
              v-model="form.password"
              name="password"
              type="password"
              autocomplete="new-password"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200"
              placeholder="Enter your new password"
              :class="{ 'border-red-500 focus:ring-red-500': errors.password }"
            />
            <p v-if="errors.password" class="mt-2 text-sm text-red-600">{{ errors.password }}</p>
          </div>
          
          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              name="password_confirmation"
              type="password"
              autocomplete="new-password"
              required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200"
              placeholder="Confirm your new password"
              :class="{ 'border-red-500 focus:ring-red-500': errors.password_confirmation }"
            />
            <p v-if="errors.password_confirmation" class="mt-2 text-sm text-red-600">{{ errors.password_confirmation }}</p>
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="loading"
            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 transition-colors duration-200"
          >
            <span v-if="loading" class="absolute left-0 inset-y-0 flex items-center pl-3">
              <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </span>
            {{ loading ? 'Resetting...' : 'Reset password' }}
          </button>
        </div>

        <div v-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-red-800">{{ error }}</p>
            </div>
          </div>
        </div>

        <div class="text-center">
          <router-link to="/login" class="font-medium text-primary-600 hover:text-primary-500 transition-colors duration-200">
            Back to login
          </router-link>
        </div>
      </form>

      <div v-if="passwordReset" class="mt-8 space-y-6">
        <div class="bg-green-50 border border-green-200 rounded-md p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-green-800">Password reset successful!</h3>
              <div class="mt-2 text-sm text-green-700">
                <p>Your password has been successfully reset. You can now log in with your new password.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="text-center">
          <router-link to="/login" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200">
            Go to login
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { showSuccess, showError } from '../../utils/sweetalert.js'
import { updateDocumentTitle } from '../../utils/systemSettings.js'
import { useResellerData } from '../../composables/useResellerData.js'

export default {
  name: 'ResetPassword',
  setup() {
    // Get reseller data - available immediately
    const { branding, isLoaded } = useResellerData()
    
    return {
      branding,
      isLoaded
    }
  },
  data() {
    return {
      form: {
        email: '',
        password: '',
        password_confirmation: '',
        token: ''
      },
      errors: {},
      loading: false,
      error: '',
      passwordReset: false
    }
  },
  async created() {
    // Set document title using branding data
    updateDocumentTitle('Reset Password')
    
    // Get token and email from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    this.form.token = this.$route.params.token || urlParams.get('token') || '';
    this.form.email = urlParams.get('email') || '';
    
    if (!this.form.token || !this.form.email) {
      this.error = 'Invalid or missing reset token. Please request a new password reset link.';
      await showError('Invalid Token', 'Invalid or missing reset token. Please request a new password reset link.');
    }
  },
  methods: {
    async handleResetPassword() {
      this.loading = true;
      this.errors = {};
      this.error = '';

      // Validate passwords match
      if (this.form.password !== this.form.password_confirmation) {
        this.errors.password_confirmation = 'Passwords do not match';
        this.loading = false;
        return;
      }

      try {
        const response = await axios.post('/api/reset-password', this.form);
        
        this.passwordReset = true;
        await showSuccess('Password Reset', 'Your password has been successfully reset!');
      } catch (error) {
        if (error.response && error.response.data.errors) {
          this.errors = error.response.data.errors;
          await showError('Validation Error', 'Please check your input and try again.');
        } else if (error.response && error.response.data.message) {
          this.error = error.response.data.message;
          await showError('Reset Failed', error.response.data.message);
        } else {
          this.error = 'An error occurred. Please try again.';
          await showError('Error', 'An error occurred. Please try again.');
        }
      } finally {
        this.loading = false;
      }
    }
  }
}
</script> 