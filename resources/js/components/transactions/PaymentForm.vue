<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Navigation -->
    <Navigation />

    <div class="flex-1 py-6">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
              Payment Form
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Complete your payment to subscribe to our services
            </p>
          </div>
        </div>

        <!-- Payment Form -->
        <div class="mt-6 bg-white shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <form @submit.prevent="processPayment">
              <!-- Billing Interval Selection -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Billing Interval</label>
                <div class="bg-gray-100 rounded-lg p-1 inline-flex">
                  <button
                    type="button"
                    @click="billingInterval = 'monthly'"
                    :class="[
                      'px-4 py-2 text-sm font-medium rounded-md transition-colors',
                      billingInterval === 'monthly'
                        ? 'bg-white text-gray-900 shadow-sm'
                        : 'text-gray-500 hover:text-gray-700'
                    ]"
                  >
                    Monthly
                  </button>
                  <button
                    type="button"
                    @click="billingInterval = 'yearly'"
                    :class="[
                      'px-4 py-2 text-sm font-medium rounded-md transition-colors',
                      billingInterval === 'yearly'
                        ? 'bg-white text-gray-900 shadow-sm'
                        : 'text-gray-500 hover:text-gray-700'
                    ]"
                  >
                    Yearly
                    <span class="ml-1 text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Save 20%</span>
                  </button>
                </div>
              </div>

              <!-- Package Selection -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Package</label>
                <select v-model="form.package_id" required class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md">
                  <option value="">Choose a package</option>
                  <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                    {{ pkg.name }} - ${{ billingInterval === 'monthly' ? pkg.price : pkg.yearly_price }}/{{ billingInterval === 'monthly' ? 'month' : 'year' }}
                  </option>
                </select>
              </div>

              <!-- Amount Display -->
              <div v-if="selectedPackage" class="mb-6 p-4 bg-gray-50 rounded-lg">
                <div class="flex justify-between items-center">
                  <span class="text-sm font-medium text-gray-700">Amount:</span>
                  <span class="text-lg font-bold text-gray-900">
                    ${{ billingInterval === 'monthly' ? selectedPackage.price : selectedPackage.yearly_price }}
                  </span>
                </div>
                <div class="mt-2 text-sm text-gray-500">
                  Package: {{ selectedPackage.name }} ({{ billingInterval === 'monthly' ? 'Monthly' : 'Yearly' }})
                </div>
              </div>

              <!-- Billing Information -->
              <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 mb-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Billing Email *</label>
                  <input 
                    v-model="form.billing_email" 
                    type="email" 
                    required 
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    placeholder="your@email.com"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Billing Name</label>
                  <input 
                    v-model="form.billing_name" 
                    type="text" 
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    placeholder="Your Name"
                  />
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Billing Address</label>
                  <input 
                    v-model="form.billing_address" 
                    type="text" 
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    placeholder="123 Main St"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                  <input 
                    v-model="form.billing_city" 
                    type="text" 
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    placeholder="City"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                  <input 
                    v-model="form.billing_state" 
                    type="text" 
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    placeholder="State"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                  <input 
                    v-model="form.billing_country" 
                    type="text" 
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    placeholder="Country"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                  <input 
                    v-model="form.billing_postal_code" 
                    type="text" 
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    placeholder="12345"
                  />
                </div>
              </div>

              <!-- Stripe Card Element -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Card Information</label>
                <div id="card-element" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"></div>
                <div id="card-errors" class="text-red-600 text-sm mt-2" role="alert"></div>
              </div>

              <!-- Submit Button -->
              <div class="flex justify-end">
                <button 
                  type="submit" 
                  :disabled="loading || !selectedPackage || !stripeLoaded"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ loading ? 'Processing Payment...' : `Pay $${selectedPackage ? (billingInterval === 'monthly' ? selectedPackage.price : selectedPackage.yearly_price) : '$0.00'}` }}
                </button>
              </div>
            </form>
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
import axios from 'axios'
import { showSuccess, showError } from '../../utils/sweetalert.js'

export default {
  name: 'PaymentForm',
  components: {
    Navigation,
    SimpleFooter
  },
  data() {
    return {
      loading: false,
      stripeLoaded: false,
      stripe: null,
      cardElement: null,
      packages: [],
      form: {
        package_id: '',
        billing_email: '',
        billing_name: '',
        billing_address: '',
        billing_city: '',
        billing_state: '',
        billing_country: '',
        billing_postal_code: ''
      },
      billingInterval: 'yearly', // Default to yearly
      stripePublishableKey: null // New property to store the key
    }
  },
  computed: {
    selectedPackage() {
      return this.packages.find(pkg => pkg.id == this.form.package_id)
    }
  },
  async mounted() {
    try {
      await this.loadPackages()
      await this.loadStripeKey() // Load the key FIRST
      await this.loadStripe()     // Then initialize Stripe with the key
      
      // Pre-fill billing email with user's email
      const user = JSON.parse(localStorage.getItem('user') || '{}')
      if (user.email) {
        this.form.billing_email = user.email
        this.form.billing_name = user.name || ''
      }
    } catch (error) {
      console.error('Failed to initialize payment form:', error.message)
      showError('Payment Form Error', `Failed to initialize payment form: ${error.message}`)
    }
    
    // Pre-select package from URL parameter
    const urlParams = new URLSearchParams(window.location.search)
    const packageId = urlParams.get('package_id')
    if (packageId) {
      this.form.package_id = packageId
    }
  },
  methods: {
    async loadPackages() {
      try {
        const response = await axios.get('/api/subscriptions/packages')
        this.packages = response.data.data || []
      } catch (error) {
        showError('Error', 'Failed to load subscription packages')
      }
    },

    async loadStripe() {
      try {
        console.log('Loading Stripe...')
        
        // Load Stripe.js
        if (typeof Stripe === 'undefined') {
          console.log('Stripe not found, loading script...')
          await this.loadStripeScript()
        }

        // Check if publishable key is available
        const publishableKey = this.getStripeKey()
        if (!publishableKey) {
          throw new Error('Stripe publishable key not found. Please check your environment configuration.')
        }

        console.log('Initializing Stripe with key:', publishableKey.substring(0, 20) + '...')
        
        // Initialize Stripe
        this.stripe = Stripe(publishableKey)
        
        // Create card element
        const elements = this.stripe.elements()
        this.cardElement = elements.create('card', {
          style: {
            base: {
              fontSize: '16px',
              color: '#424770',
              '::placeholder': {
                color: '#aab7c4',
              },
            },
            invalid: {
              color: '#9e2146',
            },
          },
        })

        // Mount card element
        this.cardElement.mount('#card-element')

        // Handle card errors
        this.cardElement.on('change', ({error}) => {
          const displayError = document.getElementById('card-errors')
          if (error) {
            displayError.textContent = error.message
          } else {
            displayError.textContent = ''
          }
        })

        this.stripeLoaded = true
        console.log('Stripe loaded successfully')
      } catch (error) {
        console.error('Failed to load Stripe:', error)
        showError('Stripe Error', `Failed to load Stripe: ${error.message}. Please refresh the page and try again.`)
      }
    },

    getStripeKey() {
      // Get the Stripe key from the backend API (dynamic per reseller)
      if (this.stripePublishableKey) {
        return this.stripePublishableKey
      }
      
      return null
    },

    async loadStripeKey() {
      try {
        console.log('Loading Stripe configuration from backend...')
        // Fetch Stripe configuration from backend (reseller-specific)
        const response = await axios.get('/api/stripe/config')
        
        console.log('Stripe config response:', response.data)
        
        if (response.data.success && response.data.publishable_key) {
          this.stripePublishableKey = response.data.publishable_key
          console.log('✅ Stripe key loaded successfully from backend')
        } else {
          throw new Error('Invalid response: No publishable key found in response')
        }
      } catch (error) {
        console.error('❌ Failed to load Stripe key from backend:', error.message)
        this.stripePublishableKey = null
        throw new Error(`Failed to load Stripe configuration: ${error.message}`)
      }
    },

    debugStripeKeySources() {
      console.log('=== Stripe Key Debug Info ===')
      console.log('Backend API key:', this.stripePublishableKey ? 'Available' : 'Not available')
      console.log('Backend key value:', this.stripePublishableKey ? this.stripePublishableKey.substring(0, 20) + '...' : 'N/A')
      console.log('=============================')
    },

    // Method to test Stripe key loading (can be called from browser console)
    testStripeKey() {
      console.log('=== Testing Stripe Key Loading ===')
      const key = this.getStripeKey()
      if (key) {
        console.log('✅ Stripe key loaded successfully:', key.substring(0, 20) + '...')
        return true
      } else {
        console.log('❌ No Stripe key available')
        this.debugStripeKeySources()
        return false
      }
    },

    async loadStripeScript() {
      return new Promise((resolve, reject) => {
        console.log('Loading Stripe script...')
        
        // Check if script already exists
        const existingScript = document.querySelector('script[src="https://js.stripe.com/v3/"]')
        if (existingScript) {
          console.log('Stripe script already exists')
          resolve()
          return
        }

        const script = document.createElement('script')
        script.src = 'https://js.stripe.com/v3/'
        script.onload = () => {
          console.log('Stripe script loaded successfully')
          resolve()
        }
        script.onerror = () => {
          console.error('Failed to load Stripe script')
          reject(new Error('Failed to load Stripe script from CDN'))
        }
        document.head.appendChild(script)
      })
    },

    async processPayment() {
      if (!this.selectedPackage) {
        showError('Error', 'Please select a package')
        return
      }

      if (!this.stripeLoaded) {
        showError('Error', 'Stripe is not loaded yet. Please wait and try again.')
        return
      }

      try {
        this.loading = true

        // Create payment method
        const { paymentMethod, error } = await this.stripe.createPaymentMethod({
          type: 'card',
          card: this.cardElement,
          billing_details: {
            email: this.form.billing_email,
            name: this.form.billing_name,
            address: {
              line1: this.form.billing_address,
              city: this.form.billing_city,
              state: this.form.billing_state,
              country: this.form.billing_country,
              postal_code: this.form.billing_postal_code,
            }
          }
        })

        if (error) {
          showError('Payment Error', error.message)
          return
        }

        // Create Stripe subscription with payment method
        const subscriptionData = {
          package_id: this.selectedPackage.id,
          payment_method_id: paymentMethod.id,
          billing_interval: this.billingInterval
        }

        const response = await axios.post('/api/stripe/subscription', subscriptionData)
        
        if (response.data.success) {
          await showSuccess('Payment Successful', 'Your subscription has been activated successfully! You can now create voice assistants.')
          this.$router.push('/dashboard')
        } else {
          // Handle specific PaymentMethod errors
          let errorMessage = response.data.message || 'Failed to create subscription'
          
          if (errorMessage.includes('payment method is invalid') || errorMessage.includes('expired')) {
            errorMessage = 'Your payment method is invalid or has expired. Please try again with a different card.'
            // Clear the card element to force user to re-enter
            this.cardElement.clear()
          } else if (errorMessage.includes('card was declined')) {
            errorMessage = 'Your card was declined. Please try a different payment method.'
            this.cardElement.clear()
          } else if (errorMessage.includes('insufficient funds')) {
            errorMessage = 'Insufficient funds. Please try a different payment method.'
            this.cardElement.clear()
          }
          
          showError('Payment Error', errorMessage)
        }
      } catch (error) {
        showError('Payment Error', error.response?.data?.message || 'Failed to process payment')
      } finally {
        this.loading = false
      }
    }
  }
}
</script> 