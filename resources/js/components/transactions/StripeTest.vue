<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow p-6">
      <h2 class="text-2xl font-bold mb-4">Stripe Payment Test</h2>
      
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Test Card</label>
        <div class="p-3 bg-gray-100 rounded text-sm">
          <p><strong>Card Number:</strong> 4242 4242 4242 4242</p>
          <p><strong>Expiry:</strong> Any future date</p>
          <p><strong>CVC:</strong> Any 3 digits</p>
        </div>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Card Information</label>
        <div id="card-element" class="p-3 border rounded-md"></div>
        <div id="card-errors" class="text-red-600 text-sm mt-2"></div>
      </div>

      <button 
        @click="testPayment"
        :disabled="loading"
        class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 disabled:opacity-50"
      >
        {{ loading ? 'Processing...' : 'Test Payment' }}
      </button>

      <div v-if="result" class="mt-4 p-3 bg-gray-100 rounded">
        <pre>{{ JSON.stringify(result, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StripeTest',
  data() {
    return {
      loading: false,
      stripe: null,
      cardElement: null,
      result: null
    }
  },
  async mounted() {
    await this.loadStripe()
  },
  methods: {
    async loadStripe() {
      try {
        console.log('Loading Stripe...')
        
        // Load Stripe.js
        if (typeof Stripe === 'undefined') {
          console.log('Stripe not found, loading script...')
          await this.loadStripeScript()
        }

        // Load Stripe configuration from backend (reseller-specific)
        const response = await axios.get('/api/stripe/config')
        const publishableKey = response.data.publishable_key
        
        if (!publishableKey) {
          throw new Error('Stripe publishable key not found. Please configure Stripe settings.')
        }

        console.log('Initializing Stripe with key:', publishableKey.substring(0, 20) + '...')
        
        // Initialize Stripe
        this.stripe = Stripe(publishableKey)
        
        // Create card element
        const elements = this.stripe.elements()
        this.cardElement = elements.create('card')
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

        console.log('Stripe loaded successfully')
      } catch (error) {
        console.error('Failed to load Stripe:', error)
        alert(`Failed to load Stripe: ${error.message}`)
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

    async testPayment() {
      if (!this.stripe) {
        alert('Stripe not loaded')
        return
      }

      try {
        this.loading = true

        // Create payment method
        const { paymentMethod, error } = await this.stripe.createPaymentMethod({
          type: 'card',
          card: this.cardElement,
          billing_details: {
            email: 'test@example.com',
            name: 'Test User'
          }
        })

        if (error) {
          this.result = { error: error.message }
          return
        }

        // Test subscription creation
        const response = await fetch('/api/stripe/subscription', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          },
          body: JSON.stringify({
            package_id: 1, // Assuming package ID 1 exists
            payment_method_id: paymentMethod.id
          })
        })

        const data = await response.json()
        this.result = data

      } catch (error) {
        this.result = { error: error.message }
      } finally {
        this.loading = false
      }
    }
  }
}
</script> 