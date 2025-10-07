<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Simple Navigation for Pricing Page -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <router-link to="/" class="flex items-center hover:opacity-80 transition-opacity">
                <div class="h-8 w-8 bg-green-600 rounded-lg flex items-center justify-center">
                  <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                  </svg>
                </div>
                <div class="ml-2">
                  <h1 class="text-xl font-bold text-gray-900">{{ settings.site_title || 'sulus.ai' }}</h1>
                </div>
              </router-link>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <router-link to="/login" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
              Login
            </router-link>
            <router-link to="/register" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
              Sign Up
            </router-link>
          </div>
        </div>
      </div>
    </nav>

    <div class="py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center">
          <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl">
                         {{ settings.site_title || 'sulus.ai' }} Pricing
          </h1>
          <p class="mt-4 text-xl text-gray-600">
            {{ settings.site_tagline || 'Choose the perfect plan for your business needs' }}
          </p>
        </div>

        <!-- Billing Interval Toggle -->
        <div class="mt-8 flex justify-center">
          <div class="bg-gray-100 rounded-lg p-1 inline-flex">
            <button
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

        <!-- Pricing Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- Dynamic Package Cards -->
          <div
            v-for="(pkg, index) in packages"
            :key="pkg.id"
            :class="[
              'bg-white rounded-lg shadow-lg border border-gray-200 p-6',
              pkg.is_popular ? 'relative' : ''
            ]"
          >
            <div v-if="pkg.is_popular" class="absolute -top-3 left-1/2 transform -translate-x-1/2">
              <span class="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                Most Popular
              </span>
            </div>
            <div class="text-center">
              <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ pkg.name }}</h3>
              <div class="text-4xl font-bold text-green-600 mb-4">
                <span v-if="billingInterval === 'monthly'">${{ pkg.price }}</span>
                <span v-else>${{ pkg.yearly_price }}</span>
                <span class="text-lg text-gray-500">
                  /{{ billingInterval === 'monthly' ? 'month' : 'year' }}
                </span>
              </div>
              <p class="text-gray-600 mb-6">{{ pkg.description }}</p>
              <ul class="text-left space-y-3 mb-8">
                <li
                  v-for="feature in pkg.features"
                  :key="feature"
                  class="flex items-center"
                >
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  {{ feature }}
                </li>
              </ul>
              <router-link 
                v-if="!isPackageDisabled(pkg)"
                :to="isAuthenticated ? `/payment?package_id=${pkg.id}` : '/register'" 
                class="w-full inline-flex justify-center items-center px-6 py-3 rounded-lg font-medium bg-green-600 text-white hover:bg-green-700 transition-colors"
              >
                {{ isAuthenticated ? `Get Started - $${billingInterval === 'monthly' ? pkg.price : pkg.yearly_price}` : 'Sign Up Now' }}
              </router-link>
              <button 
                v-else
                disabled
                class="w-full inline-flex justify-center items-center px-6 py-3 rounded-lg font-medium bg-gray-300 text-gray-500 cursor-not-allowed"
              >
                {{ pkg.price <= (currentSubscription?.package?.price || 0) ? 'Current Plan' : 'Lower Tier' }}
              </button>
              <router-link 
                v-if="!isAuthenticated"
                to="/demo-request"
                class="w-full inline-flex justify-center items-center px-6 py-3 mt-2 rounded-lg font-medium bg-blue-600 text-white hover:bg-blue-700 transition-colors"
              >
                Request Demo
              </router-link>
            </div>
          </div>
        </div>

        <!-- Pricing Comparison -->
        <div class="mt-16">
          <h2 class="text-3xl font-bold text-gray-900 text-center mb-8">Pricing Comparison</h2>
          <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feature</th>
                  <th 
                    v-for="pkg in packages" 
                    :key="pkg.id"
                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    {{ pkg.name }}
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Monthly Minutes</td>
                  <td 
                    v-for="pkg in packages" 
                    :key="pkg.id"
                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"
                  >
                    {{ pkg.monthly_minutes_limit === -1 ? 'Unlimited' : pkg.monthly_minutes_limit.toLocaleString() }}
                  </td>
                </tr>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    Price per {{ billingInterval === 'monthly' ? 'Month' : 'Year' }}
                  </td>
                  <td 
                    v-for="pkg in packages" 
                    :key="pkg.id"
                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"
                  >
                    ${{ billingInterval === 'monthly' ? pkg.price : pkg.yearly_price }}
                  </td>
                </tr>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Voice Agents</td>
                  <td 
                    v-for="pkg in packages" 
                    :key="pkg.id"
                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"
                  >
                    {{ pkg.voice_agents_limit === -1 ? 'Unlimited' : pkg.voice_agents_limit }}
                  </td>
                </tr>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Analytics</td>
                  <td 
                    v-for="pkg in packages" 
                    :key="pkg.id"
                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"
                  >
                    {{ pkg.analytics_level }}
                  </td>
                </tr>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Support</td>
                  <td 
                    v-for="pkg in packages" 
                    :key="pkg.id"
                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"
                  >
                    {{ pkg.support_level }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- FAQ Section -->
        <div class="mt-16">
          <h2 class="text-3xl font-bold text-gray-900 text-center mb-8">Frequently Asked Questions</h2>
          <div class="max-w-3xl mx-auto space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">How does billing work?</h3>
              <p class="text-gray-600">We offer flexible monthly and yearly subscription plans. Monthly plans are billed monthly, while yearly plans offer a 20% discount and are billed annually. You pay a fixed fee based on your chosen plan, with no hidden charges or per-minute fees.</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Can I change plans anytime?</h3>
              <p class="text-gray-600">Yes, you can upgrade or downgrade your plan at any time. Changes take effect at the start of your next billing cycle.</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">What happens if I exceed my monthly minutes?</h3>
              <p class="text-gray-600">For Starter and Professional plans, you'll be notified when you're approaching your limit. Enterprise plans include unlimited minutes.</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Do you offer custom pricing for high-volume usage?</h3>
              <p class="text-gray-600">Yes! Contact our sales team for custom pricing on high-volume usage and enterprise deployments with specific requirements.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { updateDocumentTitle } from '../../utils/systemSettings.js'

export default {
  name: 'Pricing',
  setup() {
    const currentSubscription = ref(null)
    const packages = ref([])
    const isAuthenticated = ref(false)
    const settings = ref({})
    const billingInterval = ref('monthly') // Added for billing interval toggle

    const checkAuthStatus = () => {
      const token = localStorage.getItem('token')
      isAuthenticated.value = !!token
    }

    const loadCurrentSubscription = async () => {
      if (!isAuthenticated.value) {
        currentSubscription.value = null
        return
      }

      try {
        const response = await axios.get('/api/subscriptions/current')
        currentSubscription.value = response.data.data
      } catch (error) {
        // No active subscription
        currentSubscription.value = null
      }
    }

    const loadPackages = async () => {
      try {
        const response = await axios.get('/api/subscriptions/packages')
        packages.value = response.data.data
      } catch (error) {
        // Handle error silently
      }
    }

    const loadSettings = async () => {
      try {
        const response = await axios.get('/api/public-settings')
        settings.value = response.data.data
      } catch (error) {
        // Set default values if API fails
        settings.value = {
          site_title: 'sulus.ai',
          site_tagline: 'Choose the perfect plan for your business needs'
        }
      }
    }

    const isPackageDisabled = (pkg) => {
      // If user is not authenticated, all packages are available
      if (!isAuthenticated.value) return false
      
      const currentPackage = currentSubscription.value?.package;
      if (!currentPackage) return false; // No current subscription, so all packages are available
      
      // Convert prices to numbers for proper comparison
      const pkgPrice = parseFloat(pkg.price);
      const currentPrice = parseFloat(currentPackage.price);
      
      return pkgPrice <= currentPrice;
    }

    onMounted(() => {
      checkAuthStatus()
      loadCurrentSubscription()
      loadPackages()
      loadSettings()
      updateDocumentTitle('Pricing')
    })

    return {
      currentSubscription,
      packages,
      isAuthenticated,
      isPackageDisabled,
      settings,
      billingInterval // Added for billing interval toggle
    }
  }
}
</script> 