<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Simple Navigation for Demo Request Page -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <router-link to="/" class="flex items-center hover:opacity-80 transition-opacity">
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
              </router-link>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <template v-if="!isAuthenticated">
              <router-link to="/login" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                Login
              </router-link>
              <router-link to="/register" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                Sign Up
              </router-link>
            </template>
            <template v-else>
              <router-link to="/dashboard" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                Dashboard
              </router-link>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <div class="flex-1 py-12">
      <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-gray-900 mb-4">Request a Demo</h1>
          <p class="text-lg text-gray-600">
            Experience the power of our {{ branding.appName }} platform firsthand.
            Our team will schedule a personalized demo tailored to your business needs.
          </p>
        </div>

        <!-- Loading State -->
        <div v-if="checking" class="bg-white shadow-lg rounded-lg p-8 text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto"></div>
          <p class="mt-4 text-gray-600">Checking your demo request status...</p>
        </div>

        <!-- Not Authenticated -->
        <div v-else-if="!isAuthenticated" class="bg-white shadow-lg rounded-lg p-8 text-center">
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-4">
            <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-gray-900 mb-4">Login Required</h2>
          <p class="text-gray-600 mb-6">
            You need to be logged in to request a demo. Please sign in or create an account to continue.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <router-link to="/login" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
              Sign In
            </router-link>
            <router-link to="/register" class="inline-flex items-center px-4 py-2 border border-green-600 text-sm font-medium rounded-md text-green-600 bg-white hover:bg-green-50">
              Create Account
            </router-link>
          </div>
        </div>

        <!-- Already Requested Demo -->
        <div v-else-if="hasRequestedDemo" class="bg-white shadow-lg rounded-lg p-8">
          <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
              <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Demo Request Already Submitted</h2>
            <p class="text-gray-600 mb-6">
              Thank you for your interest! We have already received your demo request and our team will contact you within 24 hours.
            </p>
            <div class="bg-gray-50 rounded-lg p-6 text-left">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Your Request Details:</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                  <span class="font-medium text-gray-700">Name:</span>
                  <span class="ml-2 text-gray-600">{{ existingRequest.name }}</span>
                </div>
                <div>
                  <span class="font-medium text-gray-700">Email:</span>
                  <span class="ml-2 text-gray-600">{{ existingRequest.email }}</span>
                </div>
                <div>
                  <span class="font-medium text-gray-700">Company:</span>
                  <span class="ml-2 text-gray-600">{{ existingRequest.company_name }}</span>
                </div>
                <div>
                  <span class="font-medium text-gray-700">Status:</span>
                  <span class="ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full" :class="existingRequest.status_badge_class">
                    {{ existingRequest.status_display_name }}
                  </span>
                </div>
                <div class="md:col-span-2">
                  <span class="font-medium text-gray-700">Submitted:</span>
                  <span class="ml-2 text-gray-600">{{ formatDate(existingRequest.created_at) }}</span>
                </div>
              </div>
            </div>
            <div class="mt-6">
              <router-link to="/" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                Return to Home
              </router-link>
            </div>
          </div>
        </div>

        <!-- Demo Request Form -->
        <div v-else class="bg-white shadow-lg rounded-lg p-8">
          <form @submit.prevent="submitDemoRequest">
            <div class="space-y-6">
              <!-- Personal Information -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Personal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                      Full Name *
                    </label>
                    <input
                      id="name"
                      v-model="form.name"
                      type="text"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                      placeholder="Enter your full name"
                    />
                  </div>
                  <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                      Email Address *
                    </label>
                    <input
                      id="email"
                      v-model="form.email"
                      type="email"
                      required
                      readonly
                      class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500 cursor-not-allowed"
                      placeholder="Enter your email address"
                    />
                  </div>
                </div>
                <div class="mt-4">
                  <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                    Phone Number *
                  </label>
                  <input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    placeholder="Enter your phone number"
                  />
                </div>
              </div>

              <!-- Company Information -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Company Information</h3>
                <div class="space-y-4">
                  <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">
                      Company Name *
                    </label>
                    <input
                      id="company_name"
                      v-model="form.company_name"
                      type="text"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                      placeholder="Enter your company name"
                    />
                  </div>
                  <div>
                    <label for="industry" class="block text-sm font-medium text-gray-700 mb-2">
                      Industry *
                    </label>
                    <select
                      id="industry"
                      v-model="form.industry"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    >
                      <option value="">Select your industry</option>
                      <option value="Healthcare">Healthcare</option>
                      <option value="Finance">Finance</option>
                      <option value="E-commerce">E-commerce</option>
                      <option value="Real Estate">Real Estate</option>
                      <option value="Education">Education</option>
                      <option value="Technology">Technology</option>
                      <option value="Manufacturing">Manufacturing</option>
                      <option value="Retail">Retail</option>
                      <option value="Hospitality">Hospitality</option>
                      <option value="Legal">Legal</option>
                      <option value="Marketing">Marketing</option>
                      <option value="Consulting">Consulting</option>
                      <option value="Non-profit">Non-profit</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                  <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                      Country *
                    </label>
                    <select
                      id="country"
                      v-model="form.country"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    >
                      <option value="">Select your country</option>
                      <option value="United States">United States</option>
                      <option value="United Kingdom">United Kingdom</option>
                      <option value="Canada">Canada</option>
                      <option value="Australia">Australia</option>
                      <option value="New Zealand">New Zealand</option>
                      <option value="Ireland">Ireland</option>
                      <option value="Germany">Germany</option>
                      <option value="France">France</option>
                      <option value="Spain">Spain</option>
                      <option value="Italy">Italy</option>
                      <option value="Netherlands">Netherlands</option>
                      <option value="Belgium">Belgium</option>
                      <option value="Switzerland">Switzerland</option>
                      <option value="Austria">Austria</option>
                      <option value="Sweden">Sweden</option>
                      <option value="Norway">Norway</option>
                      <option value="Denmark">Denmark</option>
                      <option value="Finland">Finland</option>
                      <option value="Poland">Poland</option>
                      <option value="Czech Republic">Czech Republic</option>
                      <option value="Hungary">Hungary</option>
                      <option value="Slovakia">Slovakia</option>
                      <option value="Slovenia">Slovenia</option>
                      <option value="Croatia">Croatia</option>
                      <option value="Serbia">Serbia</option>
                      <option value="Bulgaria">Bulgaria</option>
                      <option value="Romania">Romania</option>
                      <option value="Greece">Greece</option>
                      <option value="Portugal">Portugal</option>
                      <option value="Japan">Japan</option>
                      <option value="South Korea">South Korea</option>
                      <option value="China">China</option>
                      <option value="India">India</option>
                      <option value="Singapore">Singapore</option>
                      <option value="Malaysia">Malaysia</option>
                      <option value="Thailand">Thailand</option>
                      <option value="Vietnam">Vietnam</option>
                      <option value="Philippines">Philippines</option>
                      <option value="Indonesia">Indonesia</option>
                      <option value="Brazil">Brazil</option>
                      <option value="Argentina">Argentina</option>
                      <option value="Chile">Chile</option>
                      <option value="Colombia">Colombia</option>
                      <option value="Peru">Peru</option>
                      <option value="Mexico">Mexico</option>
                      <option value="South Africa">South Africa</option>
                      <option value="Nigeria">Nigeria</option>
                      <option value="Kenya">Kenya</option>
                      <option value="Egypt">Egypt</option>
                      <option value="Morocco">Morocco</option>
                      <option value="Tunisia">Tunisia</option>
                      <option value="Algeria">Algeria</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                  <div>
                    <label for="services" class="block text-sm font-medium text-gray-700 mb-2">
                      Services/Products *
                    </label>
                    <textarea
                      id="services"
                      v-model="form.services"
                      required
                      rows="4"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                      placeholder="Describe your services or products"
                    ></textarea>
                    <p class="text-xs text-gray-500 mt-1">
                      Tell us about your business and how you think {{ settings.company_name || 'sulus.ai' }} could help
                    </p>
                  </div>
                </div>
              </div>

              <!-- Submit Button -->
              <div class="pt-4">
                <button
                  type="submit"
                  :disabled="loading"
                  class="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-medium py-3 px-4 rounded-md transition-colors duration-200"
                >
                  <span v-if="loading" class="flex items-center justify-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Submitting...
                  </span>
                  <span v-else>Request Demo</span>
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- Benefits Section -->
        <div class="mt-12 bg-white shadow-lg rounded-lg p-8">
          <h3 class="text-lg font-medium text-gray-900 mb-6">What to Expect</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
                  <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <h4 class="text-sm font-medium text-gray-900">Quick Response</h4>
                <p class="text-sm text-gray-600 mt-1">We'll contact you within 24 hours to schedule your demo</p>
              </div>
            </div>
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
                  <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <h4 class="text-sm font-medium text-gray-900">Personalized Demo</h4>
                <p class="text-sm text-gray-600 mt-1">Tailored to your specific business needs and use cases</p>
              </div>
            </div>
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
                  <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <h4 class="text-sm font-medium text-gray-900">Live Demo</h4>
                <p class="text-sm text-gray-600 mt-1">See our {{ settings.company_name || 'sulus.ai' }} platform in action with real examples</p>
              </div>
            </div>
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
                  <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <h4 class="text-sm font-medium text-gray-900">Q&A Session</h4>
                <p class="text-sm text-gray-600 mt-1">Get all your questions answered by our experts</p>
              </div>
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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { showSuccess, showError } from '../../utils/sweetalert.js'
import axios from 'axios'
import { updateDocumentTitle } from '../../utils/systemSettings.js'
import { useResellerData } from '../../composables/useResellerData.js'
import SimpleFooter from '../shared/SimpleFooter.vue'

export default {
  name: 'DemoRequestForm',
  components: {
    SimpleFooter
  },
  setup() {
    const router = useRouter()
    const loading = ref(false)
    const checking = ref(true)
    const hasRequestedDemo = ref(false)
    const existingRequest = ref(null)
    // Get reseller data - available immediately
    const { branding, isLoaded } = useResellerData()
    
    // Check if user is authenticated
    const isAuthenticated = computed(() => {
      return localStorage.getItem('token') !== null
    })

    const loadSettings = async () => {
      try {
        const response = await axios.get('/api/public-settings')
        settings.value = response.data.data
      } catch (error) {
        // Set default values if API fails
        settings.value = {
          site_title: 'sulus.ai',
          logo_url: '/logo.png'
        }
      }
    }
    
    const form = ref({
      name: '',
      email: '',
      phone: '',
      company_name: '',
      industry: '',
      country: '',
      services: ''
    })

    // Check if user has already requested a demo
    const checkExistingDemoRequest = async () => {
      try {
        checking.value = true
        
        // Get user email from localStorage if authenticated
        const user = JSON.parse(localStorage.getItem('user') || '{}')
        const email = user.email || ''
        
        // Pre-fill form with user data if authenticated
        if (user.email) {
          form.value.email = user.email
          form.value.name = user.name || ''
        } else {
          // If no user email, redirect to login or show message
          checking.value = false
          return
        }

        if (!email) {
          // If no email available, show message
          checking.value = false
          return
        }

        const response = await fetch('/api/demo-request/check', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ email })
        })

        const data = await response.json()

        if (data.success) {
          if (data.data.has_requested) {
            hasRequestedDemo.value = true
            existingRequest.value = data.data.request
          }
        }
      } catch (error) {
        // If there's an error, show the form anyway
      } finally {
        checking.value = false
      }
    }

    const submitDemoRequest = async () => {
      try {
        loading.value = true
        
        const response = await fetch('/api/demo-request', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify(form.value)
        })

        const data = await response.json()

        if (data.success) {
          await showSuccess('Demo Request Submitted!', data.message)
          // Check again to show the updated status
          await checkExistingDemoRequest()
        } else {
          await showError('Error', data.message || 'Failed to submit demo request')
        }
      } catch (error) {
        await showError('Error', 'Failed to submit demo request. Please try again.')
      } finally {
        loading.value = false
      }
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    onMounted(() => {
      checkExistingDemoRequest()
      updateDocumentTitle('Request a Demo')
    })

    return {
      form,
      loading,
      checking,
      hasRequestedDemo,
      existingRequest,
      isAuthenticated,
      submitDemoRequest,
      formatDate,
      branding,
      isLoaded
    }
  }
}
</script> 