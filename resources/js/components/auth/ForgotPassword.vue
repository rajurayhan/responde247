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
          Forgot your password?
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Enter your email address and we'll send you a link to reset your password.
        </p>
      </div>
      
      <form class="mt-8 space-y-6" @submit.prevent="handleForgotPassword" v-if="!emailSent">
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email address</label>
          <input
            id="email"
            v-model="form.email"
            name="email"
            type="email"
            autocomplete="email"
            required
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200"
            placeholder="Enter your email"
            :class="{ 'border-red-500 focus:ring-red-500': errors.email }"
          />
          <p v-if="errors.email" class="mt-2 text-sm text-red-600">{{ errors.email }}</p>
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
            {{ loading ? 'Sending...' : 'Send reset link' }}
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

      <div v-if="emailSent" class="mt-8 space-y-6">
        <div class="bg-green-50 border border-green-200 rounded-md p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-green-800">Check your email</h3>
              <div class="mt-2 text-sm text-green-700">
                <p>We've sent a password reset link to <strong>{{ form.email }}</strong></p>
                <p class="mt-1">Please check your email and click the link to reset your password.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="text-center space-y-4">
          <p class="text-sm text-gray-600">
            Didn't receive the email? Check your spam folder or
            <button @click="resendEmail" :disabled="resendLoading" class="font-medium text-primary-600 hover:text-primary-500 transition-colors duration-200">
              {{ resendLoading ? 'Sending...' : 'try again' }}
            </button>
          </p>
          <router-link to="/login" class="font-medium text-primary-600 hover:text-primary-500 transition-colors duration-200">
            Back to login
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
  name: 'ForgotPassword',
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
        email: ''
      },
      errors: {},
      loading: false,
      resendLoading: false,
      error: '',
      emailSent: false
    }
  },
  created() {
    // Set document title using branding data
    updateDocumentTitle('Forgot Password')
  },
  methods: {
    async handleForgotPassword() {
      this.loading = true;
      this.errors = {};
      this.error = '';

      try {
        const response = await axios.post('/api/forgot-password', this.form);
        
        this.emailSent = true;
        await showSuccess('Email Sent', 'Password reset link has been sent to your email address.');
      } catch (error) {
        if (error.response && error.response.data.errors) {
          this.errors = error.response.data.errors;
          await showError('Validation Error', 'Please check your input and try again.');
        } else if (error.response && error.response.data.message) {
          this.error = error.response.data.message;
          await showError('Request Failed', error.response.data.message);
        } else {
          this.error = 'An error occurred. Please try again.';
          await showError('Error', 'An error occurred. Please try again.');
        }
      } finally {
        this.loading = false;
      }
    },
    
    async resendEmail() {
      this.resendLoading = true;
      try {
        await axios.post('/api/forgot-password', this.form);
        await showSuccess('Email Sent', 'Password reset link has been resent to your email address.');
      } catch (error) {
        await showError('Error', 'Failed to resend email. Please try again.');
      } finally {
        this.resendLoading = false;
      }
    }
  }
}
</script> 