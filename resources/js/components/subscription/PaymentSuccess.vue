<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Navigation -->
    <Navigation />

    <div class="flex-1 py-12">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Success Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
          <!-- Success Icon -->
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
            <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>

          <!-- Success Message -->
          <h1 class="text-3xl font-bold text-gray-900 mb-4">Payment Successful!</h1>
          <p class="text-lg text-gray-600 mb-8">
            Thank you for your payment. Your subscription has been activated successfully.
          </p>

          <!-- Subscription Details -->
          <div v-if="subscriptionDetails" class="bg-gray-50 rounded-lg p-6 mb-8 text-left">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Subscription Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p class="text-sm font-medium text-gray-500">Package</p>
                <p class="text-base text-gray-900">{{ subscriptionDetails.package_name }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-500">Amount</p>
                <p class="text-base text-gray-900">${{ subscriptionDetails.amount }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-500">Status</p>
                <p class="text-base text-green-600 font-medium">Active</p>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-500">Valid Until</p>
                <p class="text-base text-gray-900">{{ formatDate(subscriptionDetails.valid_until) }}</p>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <router-link
              to="/dashboard"
              class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
              Go to Dashboard
            </router-link>
            
            <router-link
              to="/assistants"
              class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
              Manage Assistants
            </router-link>
          </div>

          <!-- Additional Info -->
          <div class="mt-8 pt-8 border-t border-gray-200">
            <p class="text-sm text-gray-500">
              You will receive a confirmation email shortly. If you have any questions, please contact our support team.
            </p>
          </div>
        </div>

        <!-- Features Preview -->
        <div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">What's Next?</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
              <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-3">
                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
              </div>
              <h4 class="font-medium text-gray-900 mb-2">Create Voice Agents</h4>
              <p class="text-sm text-gray-500">Build and customize your AI voice assistants</p>
            </div>
            
            <div class="text-center">
              <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-3">
                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
              </div>
              <h4 class="font-medium text-gray-900 mb-2">Make Calls</h4>
              <p class="text-sm text-gray-500">Start making calls with your voice agents</p>
            </div>
            
            <div class="text-center">
              <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 mb-3">
                <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
              <h4 class="font-medium text-gray-900 mb-2">View Analytics</h4>
              <p class="text-sm text-gray-500">Track your call performance and insights</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <SimpleFooter />
  </div>
</template>

<script>
import Navigation from '../shared/Navigation.vue'
import SimpleFooter from '../shared/SimpleFooter.vue'
import { updateDocumentTitle } from '../../utils/systemSettings.js'

export default {
  name: 'PaymentSuccess',
  components: {
    Navigation,
    SimpleFooter
  },
  data() {
    return {
      subscriptionDetails: null,
      loading: true
    }
  },
  async mounted() {
    updateDocumentTitle('Payment Successful')
    await this.loadSubscriptionDetails()
  },
  methods: {
    async loadSubscriptionDetails() {
      try {
        // Try to get subscription details from URL parameters or session
        const urlParams = new URLSearchParams(window.location.search)
        const subscriptionId = urlParams.get('subscription_id')
        
        if (subscriptionId) {
          // Load subscription details from API
          const response = await fetch(`/api/subscriptions/${subscriptionId}`, {
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`,
              'Content-Type': 'application/json'
            }
          })
          
          if (response.ok) {
            const data = await response.json()
            this.subscriptionDetails = data.data
          }
        }
      } catch (error) {
        console.error('Error loading subscription details:', error)
      } finally {
        this.loading = false
      }
    },
    
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }
  }
}
</script> 