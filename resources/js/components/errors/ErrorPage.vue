<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="text-center">
        <!-- Logo -->
        <div class="flex justify-center">
          <div v-if="branding.logoUrl" class="h-12 w-auto">
            <img :src="branding.logoUrl" :alt="branding.appName" class="h-full w-auto">
          </div>
          <div v-else class="h-12 w-12 bg-gradient-to-r from-primary-600 to-blue-600 rounded-lg flex items-center justify-center">
            <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
            </svg>
          </div>
        </div>
        
        <!-- Error Icon -->
        <div class="mt-8 flex justify-center">
          <div class="relative">
            <div class="h-32 w-32 bg-gradient-to-r from-red-100 to-pink-100 rounded-full flex items-center justify-center">
              <svg class="h-16 w-16 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
            </div>
            <div class="absolute -top-2 -right-2 h-8 w-8 bg-red-500 rounded-full flex items-center justify-center">
              <span class="text-white text-sm font-bold">{{ errorCode }}</span>
            </div>
          </div>
        </div>
        
        <h1 class="mt-8 text-3xl font-bold text-gray-900 sm:text-4xl">
          {{ errorTitle }}
        </h1>
        <p class="mt-4 text-lg text-gray-600">
          {{ errorMessage }}
        </p>
        <p class="mt-2 text-sm text-gray-500">
          {{ errorDescription }}
        </p>
      </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <div class="space-y-6">
          <!-- Quick Actions -->
          <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 gap-3">
              <button 
                @click="goBack"
                class="flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              >
                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Go Back
              </button>
              
              <router-link 
                to="/" 
                class="flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              >
                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Go to Homepage
              </router-link>
              
              <router-link 
                v-if="!isAuthenticated"
                to="/login" 
                class="flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              >
                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Sign In
              </router-link>
              
              <router-link 
                v-if="isAuthenticated"
                to="/dashboard" 
                class="flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              >
                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Go to Dashboard
              </router-link>
            </div>
          </div>

          <!-- Help Section -->
          <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Need Help?</h3>
            <p class="text-sm text-gray-600 mb-4">
              If this error persists, please contact our support team.
            </p>
            <div class="flex space-x-3">
              <a 
                :href="`mailto:${branding.supportEmail}`" 
                class="flex-1 flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              >
                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Contact Support
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useResellerData } from '../../composables/useResellerData.js'

export default {
  name: 'ErrorPage',
  props: {
    errorCode: {
      type: String,
      default: '500'
    },
    errorTitle: {
      type: String,
      default: 'Server Error'
    },
    errorMessage: {
      type: String,
      default: 'Something went wrong on our end.'
    },
    errorDescription: {
      type: String,
      default: 'We\'re working to fix the problem. Please try again later.'
    }
  },
  setup() {
    // Get reseller data - available immediately
    const { branding, isLoaded } = useResellerData()
    
    return {
      branding,
      isLoaded
    }
  },
  computed: {
    isAuthenticated() {
      return !!localStorage.getItem('token')
    }
  },
  methods: {
    goBack() {
      this.$router.go(-1)
    }
  },
  created() {
    // Update document title using branding data
    document.title = `${this.errorCode} - ${this.errorTitle} | ${this.branding.appName}`
  }
}
</script> 