<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Navigation -->
    <Navigation />

    <div class="flex-1 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900">Stripe Configuration</h1>
          <p class="mt-2 text-sm text-gray-600">Configure Stripe payment settings for your reseller account</p>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <span class="ml-2 text-gray-600">Loading configuration...</span>
        </div>

        <!-- Error State -->
        <div v-if="error" class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">Error</h3>
              <div class="mt-2 text-sm text-red-700">{{ error }}</div>
            </div>
          </div>
        </div>

        <!-- Success Message -->
        <div v-if="successMessage" class="bg-green-50 border border-green-200 rounded-md p-4 mb-6">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-green-800">Success</h3>
              <div class="mt-2 text-sm text-green-700">{{ successMessage }}</div>
            </div>
          </div>
        </div>

        <!-- Configuration Form -->
        <div v-if="!loading" class="bg-white rounded-lg shadow-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Stripe Settings</h2>
            <p class="text-sm text-gray-600 mt-1">Configure your Stripe payment processing settings</p>
          </div>

          <div class="p-6">
            <form @submit.prevent="saveConfiguration">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Stripe Publishable Key -->
                <div>
                  <label for="stripe_publishable_key" class="block text-sm font-medium text-gray-700 mb-2">
                    Stripe Publishable Key
                  </label>
                  <input
                    type="text"
                    id="stripe_publishable_key"
                    v-model="form.stripe_publishable_key"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="pk_test_..."
                    required
                  />
                  <p class="text-xs text-gray-500 mt-1">Your Stripe publishable key for frontend integration</p>
                </div>

                <!-- Stripe Secret Key -->
                <div>
                  <label for="stripe_secret_key" class="block text-sm font-medium text-gray-700 mb-2">
                    Stripe Secret Key
                  </label>
                  <div class="relative">
                    <input
                      :type="showSecretKey ? 'text' : 'password'"
                      id="stripe_secret_key"
                      v-model="form.stripe_secret_key"
                      class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="sk_test_..."
                      required
                    />
                    <button
                      type="button"
                      @click="showSecretKey = !showSecretKey"
                      class="absolute inset-y-0 right-0 pr-3 flex items-center"
                    >
                      <svg v-if="showSecretKey" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                      </svg>
                      <svg v-else class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">Your Stripe secret key for backend operations</p>
                </div>

                <!-- Stripe Webhook Secret -->
                <div>
                  <label for="stripe_webhook_secret" class="block text-sm font-medium text-gray-700 mb-2">
                    Stripe Webhook Secret
                  </label>
                  <div class="relative">
                    <input
                      :type="showWebhookSecret ? 'text' : 'password'"
                      id="stripe_webhook_secret"
                      v-model="form.stripe_webhook_secret"
                      class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="whsec_..."
                      required
                    />
                    <button
                      type="button"
                      @click="showWebhookSecret = !showWebhookSecret"
                      class="absolute inset-y-0 right-0 pr-3 flex items-center"
                    >
                      <svg v-if="showWebhookSecret" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                      </svg>
                      <svg v-else class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">Your Stripe webhook endpoint secret for verification</p>
                </div>

                <!-- Test Mode -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Test Mode
                  </label>
                  <div class="flex items-center">
                    <input
                      type="checkbox"
                      id="stripe_test_mode"
                      v-model="form.stripe_test_mode"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />
                    <label for="stripe_test_mode" class="ml-2 block text-sm text-gray-900">
                      Enable test mode (use test keys)
                    </label>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">Enable Stripe test mode for testing payments</p>
                </div>

                <!-- API Version -->
                <div>
                  <label for="stripe_api_version" class="block text-sm font-medium text-gray-700 mb-2">
                    Stripe API Version
                  </label>
                  <select
                    id="stripe_api_version"
                    v-model="form.stripe_api_version"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required
                  >
                    <option value="2024-06-20">2024-06-20</option>
                    <option value="2023-10-16">2023-10-16</option>
                    <option value="2023-08-16">2023-08-16</option>
                    <option value="2023-06-20">2023-06-20</option>
                  </select>
                  <p class="text-xs text-gray-500 mt-1">Stripe API version to use</p>
                </div>

                <!-- Currency -->
                <div>
                  <label for="stripe_currency" class="block text-sm font-medium text-gray-700 mb-2">
                    Default Currency
                  </label>
                  <select
                    id="stripe_currency"
                    v-model="form.stripe_currency"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required
                  >
                    <option value="usd">USD - US Dollar</option>
                    <option value="eur">EUR - Euro</option>
                    <option value="gbp">GBP - British Pound</option>
                    <option value="cad">CAD - Canadian Dollar</option>
                    <option value="aud">AUD - Australian Dollar</option>
                    <option value="jpy">JPY - Japanese Yen</option>
                  </select>
                  <p class="text-xs text-gray-500 mt-1">Default currency for payments</p>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-200">
                <div class="flex items-center space-x-4">
                  <button
                    type="button"
                    @click="testConfiguration"
                    :disabled="testing"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                  >
                    <svg v-if="testing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ testing ? 'Testing...' : 'Test Configuration' }}
                  </button>
                </div>

                <div class="flex items-center space-x-4">
                  <button
                    type="button"
                    @click="loadConfiguration"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  >
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset
                  </button>
                  <button
                    type="submit"
                    :disabled="saving"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                  >
                    <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ saving ? 'Saving...' : 'Save Configuration' }}
                  </button>
                </div>
              </div>
            </form>

            <!-- Test Results -->
            <div v-if="testResult" class="mt-6 p-4 bg-gray-50 rounded-md">
              <h3 class="text-sm font-medium text-gray-900 mb-2">Test Results</h3>
              <div v-if="testResult.success" class="text-sm text-green-700">
                <p v-if="testResult.data.business_name"><strong>Business Name:</strong> {{ testResult.data.business_name }}</p>
                <p><strong>Account ID:</strong> {{ testResult.data.account_id }}</p>
                <p><strong>Email:</strong> {{ testResult.data.email || 'Not provided' }}</p>
                <p><strong>Country:</strong> {{ testResult.data.country }}</p>
                <p><strong>Account Type:</strong> {{ testResult.data.type }}</p>
                <p v-if="testResult.data.business_type"><strong>Business Type:</strong> {{ testResult.data.business_type }}</p>
                <p v-if="testResult.data.business_url"><strong>Business URL:</strong> <a :href="testResult.data.business_url" target="_blank" class="text-blue-600 hover:text-blue-800">{{ testResult.data.business_url }}</a></p>
                <p><strong>Charges Enabled:</strong> {{ testResult.data.charges_enabled ? 'Yes' : 'No' }}</p>
                <p><strong>Payouts Enabled:</strong> {{ testResult.data.payouts_enabled ? 'Yes' : 'No' }}</p>
              </div>
              <div v-else class="text-sm text-red-700">
                <p><strong>Error:</strong> {{ testResult.message }}</p>
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
import Navigation from '../shared/Navigation.vue'
import SimpleFooter from '../shared/SimpleFooter.vue'

export default {
  name: 'StripeConfiguration',
  components: {
    Navigation,
    SimpleFooter
  },
  data() {
    return {
      loading: true,
      saving: false,
      testing: false,
      error: null,
      successMessage: null,
      showSecretKey: false,
      showWebhookSecret: false,
      testResult: null,
      form: {
        stripe_publishable_key: '',
        stripe_secret_key: '',
        stripe_webhook_secret: '',
        stripe_test_mode: true,
        stripe_api_version: '2024-06-20',
        stripe_currency: 'usd',
      }
    }
  },
  mounted() {
    this.loadConfiguration()
  },
  methods: {
    async loadConfiguration() {
      this.loading = true
      this.error = null
      this.successMessage = null
      this.testResult = null

      try {
        const response = await fetch('/api/admin/stripe/config', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        })

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()

        if (data.success) {
          this.form = {
            stripe_publishable_key: data.data.stripe_publishable_key || '',
            stripe_secret_key: data.data.stripe_secret_key || '',
            stripe_webhook_secret: data.data.stripe_webhook_secret || '',
            stripe_test_mode: data.data.stripe_test_mode === 'true',
            stripe_api_version: data.data.stripe_api_version || '2024-06-20',
            stripe_currency: data.data.stripe_currency || 'usd',
          }
        } else {
          this.error = data.message || 'Failed to load configuration'
        }
      } catch (error) {
        this.error = `Failed to load configuration: ${error.message}`
      } finally {
        this.loading = false
      }
    },

    async saveConfiguration() {
      this.saving = true
      this.error = null
      this.successMessage = null

      try {
        const response = await fetch('/api/admin/stripe/config', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(this.form)
        })

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()

        if (data.success) {
          this.successMessage = 'Stripe configuration saved successfully!'
          this.testResult = null
        } else {
          this.error = data.message || 'Failed to save configuration'
        }
      } catch (error) {
        this.error = `Failed to save configuration: ${error.message}`
      } finally {
        this.saving = false
      }
    },

    async testConfiguration() {
      this.testing = true
      this.error = null
      this.testResult = null

      try {
        const response = await fetch('/api/admin/stripe/test', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        })

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()
        this.testResult = data

        if (data.success) {
          this.successMessage = 'Stripe configuration test passed!'
        } else {
          this.error = data.message || 'Stripe configuration test failed'
        }
      } catch (error) {
        this.error = `Failed to test configuration: ${error.message}`
        this.testResult = {
          success: false,
          message: error.message
        }
      } finally {
        this.testing = false
      }
    }
  }
}
</script>
