<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <div class="flex justify-center mb-6">
          <div class="h-16 w-16 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </div>
        
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Payment Successful!
        </h2>
        
        <p class="mt-2 text-center text-sm text-gray-600">
          Your reseller account has been activated successfully.
        </p>
        
        <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
          <div class="flex">
            <svg class="h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-green-800">Account Activated</h3>
              <div class="mt-2 text-sm text-green-700">
                <ul class="list-disc list-inside space-y-1">
                  <li>Reseller account created and activated</li>
                  <li>Subscription is now active</li>
                  <li>Welcome email sent with login credentials</li>
                  <li>You can now start managing your reseller business</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        
        <div class="mt-6">
          <button
            @click="goToLogin"
            :disabled="loading"
            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="loading">Loading...</span>
            <span v-else-if="resellerDomain">Sign In to {{ resellerDomain }}</span>
            <span v-else>Sign In to Your Account</span>
          </button>
        </div>
        
        <div class="mt-6 text-center">
          <p class="text-xs text-gray-500">
            Need help? Contact our support team at 
            <a href="mailto:support@sulus.ai" class="text-blue-600 hover:text-blue-500">
              support@sulus.ai
            </a>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { updateDocumentTitle } from '../../utils/systemSettings'

import axios from 'axios'

export default {
  name: 'ResellerRegistrationSuccess',
  data() {
    return {
      sessionId: null,
      resellerDomain: null,
      loading: true
    }
  },
  async created() {
    updateDocumentTitle('Registration Successful')
    
    // Get session ID from URL parameters
    const urlParams = new URLSearchParams(window.location.search)
    this.sessionId = urlParams.get('session_id')
    
    // Log successful registration
    console.log('Reseller registration completed successfully', {
      session_id: this.sessionId,
      timestamp: new Date().toISOString()
    })
    
    // Fetch reseller domain from session ID
    if (this.sessionId) {
      await this.fetchResellerDomain()
    } else {
      this.loading = false
    }
  },
  methods: {
    async fetchResellerDomain() {
      try {
        const response = await axios.post('/api/get-reseller-domain-by-session', {
          session_id: this.sessionId
        })
        
        if (response.data.success && response.data.domain) {
          this.resellerDomain = response.data.domain
          console.log('Reseller domain retrieved:', this.resellerDomain)
        } else {
          console.warn('No reseller domain found for session:', this.sessionId)
        }
      } catch (error) {
        console.error('Error fetching reseller domain:', error)
      } finally {
        this.loading = false
      }
    },
    
    goToLogin() {
      if (this.resellerDomain) {
        // Redirect to reseller subdomain login
        window.location.href = `https://${this.resellerDomain}/login`
      } else {
        // Fallback to main login
        this.$router.push('/login')
      }
    },
    
  }
}
</script>
