<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow p-6">
      <h2 class="text-2xl font-bold mb-4">Stripe Debug</h2>
      
      <div class="mb-4">
        <h3 class="text-lg font-semibold mb-2">Environment Check</h3>
        <div class="space-y-2">
          <div class="flex justify-between">
            <span>Stripe Key Available:</span>
            <span :class="stripeKeyAvailable ? 'text-green-600' : 'text-red-600'">
              {{ stripeKeyAvailable ? '✅ Yes' : '❌ No' }}
            </span>
          </div>
          <div class="flex justify-between">
            <span>Stripe Key Length:</span>
            <span>{{ stripeKeyLength }}</span>
          </div>
          <div class="flex justify-between">
            <span>Stripe Key Prefix:</span>
            <span>{{ stripeKeyPrefix }}</span>
          </div>
        </div>
      </div>

      <div class="mb-4">
        <h3 class="text-lg font-semibold mb-2">Browser Check</h3>
        <div class="space-y-2">
          <div class="flex justify-between">
            <span>Stripe Object:</span>
            <span :class="stripeObjectAvailable ? 'text-green-600' : 'text-red-600'">
              {{ stripeObjectAvailable ? '✅ Available' : '❌ Not Available' }}
            </span>
          </div>
          <div class="flex justify-between">
            <span>Script Loaded:</span>
            <span :class="scriptLoaded ? 'text-green-600' : 'text-red-600'">
              {{ scriptLoaded ? '✅ Yes' : '❌ No' }}
            </span>
          </div>
        </div>
      </div>

      <button 
        @click="testStripe"
        :disabled="loading"
        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50 mb-4"
      >
        {{ loading ? 'Testing...' : 'Test Stripe Initialization' }}
      </button>

      <div v-if="testResult" class="mt-4 p-3 bg-gray-100 rounded">
        <h4 class="font-semibold mb-2">Test Result:</h4>
        <pre class="text-sm">{{ JSON.stringify(testResult, null, 2) }}</pre>
      </div>

      <div v-if="consoleLogs.length > 0" class="mt-4 p-3 bg-gray-100 rounded">
        <h4 class="font-semibold mb-2">Console Logs:</h4>
        <div class="text-sm space-y-1">
          <div v-for="(log, index) in consoleLogs" :key="index" class="font-mono">
            {{ log }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StripeDebug',
  data() {
    return {
      loading: false,
      testResult: null,
      consoleLogs: [],
      stripeConfig: null
    }
  },
  computed: {
    stripeKeyAvailable() {
      return !!this.stripeConfig?.publishable_key
    },
    stripeKeyLength() {
      const key = this.stripeConfig?.publishable_key
      return key ? key.length : 0
    },
    stripeKeyPrefix() {
      const key = this.stripeConfig?.publishable_key
      return key ? key.substring(0, 20) + '...' : 'N/A'
    },
    stripeObjectAvailable() {
      return typeof Stripe !== 'undefined'
    },
    scriptLoaded() {
      return !!document.querySelector('script[src="https://js.stripe.com/v3/"]')
    }
  },
  async mounted() {
    this.captureConsoleLogs()
    // Load Stripe configuration on component mount
    try {
      const response = await axios.get('/api/stripe/config')
      this.stripeConfig = response.data
    } catch (error) {
      console.error('Failed to load Stripe configuration:', error)
    }
  },
  methods: {
    captureConsoleLogs() {
      const originalLog = console.log
      const originalError = console.error
      
      console.log = (...args) => {
        this.consoleLogs.push(`LOG: ${args.join(' ')}`)
        originalLog.apply(console, args)
      }
      
      console.error = (...args) => {
        this.consoleLogs.push(`ERROR: ${args.join(' ')}`)
        originalError.apply(console, args)
      }
    },

    async testStripe() {
      try {
        this.loading = true
        this.testResult = null

        // Load Stripe configuration from backend first
        const configResponse = await axios.get('/api/stripe/config')
        this.stripeConfig = configResponse.data

        // Test 1: Check API configuration
        const configCheck = {
          keyAvailable: !!this.stripeConfig?.publishable_key,
          keyLength: this.stripeConfig?.publishable_key?.length || 0,
          keyPrefix: this.stripeConfig?.publishable_key?.substring(0, 20) || 'N/A',
          testMode: this.stripeConfig?.test_mode,
          currency: this.stripeConfig?.currency
        }

        // Test 2: Load Stripe script
        if (typeof Stripe === 'undefined') {
          await this.loadStripeScript()
        }

        // Test 3: Initialize Stripe
        let stripe = null
        try {
          stripe = Stripe(this.stripeConfig.publishable_key)
        } catch (error) {
          throw new Error(`Stripe initialization failed: ${error.message}`)
        }

        // Test 4: Create elements
        const elements = stripe.elements()
        const cardElement = elements.create('card')

        this.testResult = {
          success: true,
          configuration: configCheck,
          stripeLoaded: !!stripe,
          elementsCreated: !!cardElement,
          message: 'All tests passed! Stripe is working correctly.'
        }

      } catch (error) {
        this.testResult = {
          success: false,
          error: error.message,
          configuration: {
            keyAvailable: !!this.stripeConfig?.publishable_key,
            keyLength: this.stripeConfig?.publishable_key?.length || 0,
            keyPrefix: this.stripeConfig?.publishable_key?.substring(0, 20) || 'N/A'
          }
        }
      } finally {
        this.loading = false
      }
    },

    async loadStripeScript() {
      return new Promise((resolve, reject) => {
        const script = document.createElement('script')
        script.src = 'https://js.stripe.com/v3/'
        script.onload = () => resolve()
        script.onerror = () => reject(new Error('Failed to load Stripe script'))
        document.head.appendChild(script)
      })
    }
  }
}
</script> 