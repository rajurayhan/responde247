<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Navigation -->
    <Navigation />

    <div class="flex-1 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900">Billing and Subscription</h1>
          <p class="mt-2 text-sm text-gray-600">Manage your reseller billing and subscription information</p>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
          <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">
              Error loading billing information
            </h3>
            <div class="mt-2 text-sm text-red-700">
              {{ error }}
            </div>
            <div class="mt-4">
              <button 
                @click="loadBillingData"
                class="bg-red-100 px-3 py-2 rounded-md text-sm font-medium text-red-800 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
              >
                Try again
              </button>
            </div>
          </div>
        </div>
        </div>

        <!-- Main Content -->
        <div v-else class="space-y-8">
        <!-- Current Subscription Card -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Current Subscription</h2>
          </div>
          <div class="p-6">
            <div v-if="subscription" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4">
                  <div class="flex items-center">
                    <div class="flex-shrink-0">
                      <svg class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                    <div class="ml-3">
                      <p class="text-sm font-medium text-gray-500">Package</p>
                      <p class="text-lg font-semibold text-gray-900">{{ subscription.package?.name || 'N/A' }}</p>
                    </div>
                  </div>
                </div>
                
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4">
                  <div class="flex items-center">
                    <div class="flex-shrink-0">
                      <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                    <div class="ml-3">
                      <p class="text-sm font-medium text-gray-500">Status</p>
                      <p class="text-lg font-semibold text-gray-900">
                        <span :class="getStatusColor(subscription.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                          {{ subscription.status }}
                        </span>
                      </p>
                    </div>
                  </div>
                </div>
                
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-4">
                  <div class="flex items-center">
                    <div class="flex-shrink-0">
                      <svg class="h-8 w-8 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                    </div>
                    <div class="ml-3">
                      <p class="text-sm font-medium text-gray-500">Next Billing</p>
                      <p class="text-lg font-semibold text-gray-900">
                        {{ subscription.next_billing_date ? formatDate(subscription.next_billing_date) : 'N/A' }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <h3 class="text-sm font-medium text-gray-500 mb-2">Package Details</h3>
                  <div class="space-y-2">
                    <div class="flex justify-between">
                      <span class="text-sm text-gray-600">Voice Agents Limit:</span>
                      <span class="text-sm font-medium text-gray-900">{{ subscription.package?.voice_agents_limit || 'Unlimited' }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-sm text-gray-600">Monthly Minutes:</span>
                      <span class="text-sm font-medium text-gray-900">{{ subscription.package?.monthly_minutes_limit || 'Unlimited' }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-sm text-gray-600">Price:</span>
                      <span class="text-sm font-medium text-gray-900">${{ subscription.package?.price || '0' }}/month</span>
                    </div>
                  </div>
                </div>
                
                <div>
                  <h3 class="text-sm font-medium text-gray-500 mb-2">Current Usage</h3>
                  <div class="space-y-2">
                    <div class="flex justify-between">
                      <span class="text-sm text-gray-600">Active Assistants:</span>
                      <span class="text-sm font-medium text-gray-900">{{ usage?.active_assistants || 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-sm text-gray-600">Minutes Used:</span>
                      <span class="text-sm font-medium text-gray-900">{{ usage?.minutes_used || 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-sm text-gray-600">Calls Made:</span>
                      <span class="text-sm font-medium text-gray-900">{{ usage?.calls_made || 0 }}</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Pending Subscription Actions -->
              <div v-if="subscription.status === 'pending'" class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                  </div>
                  <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-yellow-800">
                      Payment Required
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700">
                      <p>Your subscription is pending payment. Complete the payment to activate your reseller account.</p>
                    </div>
                    <div class="mt-4">
                      <button 
                        @click="retryPayment"
                        :disabled="retryingPayment"
                        :class="[
                          'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white focus:outline-none focus:ring-2 focus:ring-offset-2',
                          retryingPayment 
                            ? 'bg-gray-400 cursor-not-allowed' 
                            : 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500'
                        ]"
                      >
                        <svg v-if="retryingPayment" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        {{ retryingPayment ? 'Processing...' : 'Complete Payment' }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-else class="text-center py-8">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">No active subscription</h3>
              <p class="mt-1 text-sm text-gray-500 mb-6">Choose a reseller package to get started with your voice AI platform.</p>
              
              <!-- Package Selection -->
              <div v-if="packages.length > 0" class="max-w-4xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  <div 
                    v-for="pkg in packages" 
                    :key="pkg.id"
                    :class="[
                      'relative p-6 border-2 rounded-xl cursor-pointer transition-all duration-200',
                      selectedPackage === pkg.id 
                        ? 'border-blue-500 bg-blue-50 shadow-lg' 
                        : 'border-gray-200 hover:border-gray-300 hover:shadow-md'
                    ]"
                    @click="selectPackage(pkg.id)"
                  >
                    <!-- Popular Badge -->
                    <div v-if="pkg.is_popular" class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                      <span class="bg-blue-600 text-white text-xs font-medium px-3 py-1 rounded-full">
                        Most Popular
                      </span>
                    </div>
                    
                    <div class="text-center">
                      <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ pkg.name }}</h3>
                      <p class="text-sm text-gray-600 mb-4">{{ pkg.description }}</p>
                      
                      <div class="mb-4">
                        <span class="text-3xl font-bold text-gray-900">${{ billingInterval === 'yearly' && pkg.yearly_price ? (pkg.yearly_price / 12).toFixed(2) : pkg.price }}</span>
                        <span class="text-gray-600">/month</span>
                        <div v-if="pkg.yearly_price && billingInterval === 'yearly'" class="text-sm text-gray-500 mt-1">
                          Billed annually (${{ pkg.yearly_price }}/year)
                        </div>
                      </div>
                      
                      <!-- Billing Toggle -->
                      <div v-if="pkg.yearly_price" class="mb-4">
                        <div class="flex items-center justify-center">
                          <span :class="billingInterval === 'monthly' ? 'text-gray-900 font-medium' : 'text-gray-500'" class="text-sm mr-3">Monthly</span>
                          <button
                            @click="toggleBillingInterval"
                            :class="[
                              'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                              billingInterval === 'yearly' ? 'bg-blue-600' : 'bg-gray-200'
                            ]"
                          >
                            <span
                              :class="[
                                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                billingInterval === 'yearly' ? 'translate-x-6' : 'translate-x-1'
                              ]"
                            />
                          </button>
                          <span :class="billingInterval === 'yearly' ? 'text-gray-900 font-medium' : 'text-gray-500'" class="text-sm ml-3">Yearly</span>
                        </div>
                        <div v-if="billingInterval === 'yearly'" class="text-center mt-2">
                          <span class="text-sm text-green-600 font-medium">Save {{ getYearlySavingsPercentage(pkg) }}%</span>
                        </div>
                      </div>
                      
                      <!-- Features -->
                      <div class="space-y-2 text-sm text-gray-600 mb-6">
                        <div class="flex items-center justify-center">
                          <svg class="h-4 w-4 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                          {{ pkg.voice_agents_limit || 'Unlimited' }} Voice Agents
                        </div>
                        <div class="flex items-center justify-center">
                          <svg class="h-4 w-4 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                          {{ pkg.monthly_minutes_limit || 'Unlimited' }} Minutes/Month
                        </div>
                        <div class="flex items-center justify-center">
                          <svg class="h-4 w-4 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                          </svg>
                          {{ pkg.users_limit || 'Unlimited' }} Users
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Subscribe Button -->
                <div class="mt-8 text-center">
                  <button 
                    @click="subscribeToPackage"
                    :disabled="!selectedPackage || subscribing"
                    :class="[
                      'px-8 py-3 rounded-lg font-medium transition-colors duration-200',
                      selectedPackage && !subscribing
                        ? 'bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'
                        : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                    ]"
                  >
                    <span v-if="subscribing" class="flex items-center">
                      <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Processing...
                    </span>
                    <span v-else>Subscribe Now</span>
                  </button>
                </div>
              </div>
              
              <!-- Loading Packages -->
              <div v-else-if="loadingPackages" class="flex justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
              </div>
              
              <!-- No Packages Available -->
              <div v-else class="text-center">
                <p class="text-sm text-gray-500">No packages available at the moment.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900">Recent Transactions</h2>
              <button 
                @click="loadTransactions"
                class="text-sm text-blue-600 hover:text-blue-500 font-medium"
              >
                Refresh
              </button>
            </div>
          </div>
          <div class="overflow-hidden">
            <div v-if="transactions.length > 0" class="divide-y divide-gray-200">
              <div 
                v-for="transaction in transactions" 
                :key="transaction.id"
                class="px-6 py-4 hover:bg-gray-50 transition-colors"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                      <div :class="getTransactionIconClass(transaction.status)" class="h-10 w-10 rounded-full flex items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                    </div>
                    <div>
                      <p class="text-sm font-medium text-gray-900">{{ transaction.description || 'Subscription Payment' }}</p>
                      <p class="text-sm text-gray-500">{{ formatDate(transaction.created_at) }}</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-sm font-medium text-gray-900">${{ transaction.amount }}</p>
                    <span :class="getTransactionStatusClass(transaction.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                      {{ transaction.status }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-else class="px-6 py-8 text-center">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">No transactions found</h3>
              <p class="mt-1 text-sm text-gray-500">Your transaction history will appear here.</p>
            </div>
          </div>
        </div>

        <!-- Usage Statistics -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Usage Statistics</h2>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ usage?.total_calls || 0 }}</div>
                <div class="text-sm text-gray-500">Total Calls</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ usage?.total_minutes || 0 }}</div>
                <div class="text-sm text-gray-500">Total Minutes</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-purple-600">{{ usage?.total_cost || 0 }}</div>
                <div class="text-sm text-gray-500">Total Cost ($)</div>
              </div>
            </div>
          </div>
        </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import Navigation from '../shared/Navigation.vue'

export default {
  name: 'ResellerBilling',
  components: {
    Navigation
  },
  setup() {
    const loading = ref(true)
    const error = ref(null)
    const subscription = ref(null)
    const transactions = ref([])
    const usage = ref(null)
    
    // Package selection properties
    const packages = ref([])
    const loadingPackages = ref(false)
    const selectedPackage = ref(null)
    const billingInterval = ref('monthly')
    const subscribing = ref(false)
    
    // Payment retry properties
    const retryingPayment = ref(false)

    const loadBillingData = async () => {
      try {
        loading.value = true
        error.value = null

        const token = localStorage.getItem('token')
        if (!token) {
          throw new Error('No authentication token found')
        }

        // Load subscription data
        const subscriptionResponse = await fetch('/api/reseller/subscription', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (subscriptionResponse.ok) {
          const subscriptionData = await subscriptionResponse.json()
          subscription.value = subscriptionData.data
        }

        // Load transactions
        const transactionsResponse = await fetch('/api/reseller/transactions', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (transactionsResponse.ok) {
          const transactionsData = await transactionsResponse.json()
          transactions.value = transactionsData.data || []
        }

        // Load usage data
        const usageResponse = await fetch('/api/reseller/usage', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (usageResponse.ok) {
          const usageData = await usageResponse.json()
          usage.value = usageData.data
        }

      } catch (err) {
        error.value = err.message || 'Failed to load billing data'
        console.error('Error loading billing data:', err)
      } finally {
        loading.value = false
      }
    }

    const loadTransactions = async () => {
      try {
        const token = localStorage.getItem('token')
        const response = await fetch('/api/reseller/transactions', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          transactions.value = data.data || []
        }
      } catch (err) {
        console.error('Error loading transactions:', err)
      }
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }

    const getStatusColor = (status) => {
      switch (status?.toLowerCase()) {
        case 'active':
          return 'bg-green-100 text-green-800'
        case 'pending':
          return 'bg-yellow-100 text-yellow-800'
        case 'canceled':
          return 'bg-red-100 text-red-800'
        case 'past_due':
          return 'bg-yellow-100 text-yellow-800'
        case 'trialing':
          return 'bg-blue-100 text-blue-800'
        default:
          return 'bg-gray-100 text-gray-800'
      }
    }

    const getTransactionStatusClass = (status) => {
      switch (status?.toLowerCase()) {
        case 'completed':
        case 'succeeded':
          return 'bg-green-100 text-green-800'
        case 'pending':
          return 'bg-yellow-100 text-yellow-800'
        case 'failed':
        case 'canceled':
          return 'bg-red-100 text-red-800'
        default:
          return 'bg-gray-100 text-gray-800'
      }
    }

    const getTransactionIconClass = (status) => {
      switch (status?.toLowerCase()) {
        case 'completed':
        case 'succeeded':
          return 'bg-green-100 text-green-600'
        case 'pending':
          return 'bg-yellow-100 text-yellow-600'
        case 'failed':
        case 'canceled':
          return 'bg-red-100 text-red-600'
        default:
          return 'bg-gray-100 text-gray-600'
      }
    }

    const loadPackages = async () => {
      try {
        loadingPackages.value = true
        const token = localStorage.getItem('token')
        const response = await fetch('/api/reseller-packages', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          packages.value = data.data || []
        }
      } catch (err) {
        console.error('Error loading packages:', err)
      } finally {
        loadingPackages.value = false
      }
    }

    const selectPackage = (packageId) => {
      selectedPackage.value = packageId
    }


    const getYearlySavingsPercentage = (pkg) => {
      if (!pkg.yearly_price || !pkg.price) {
        return 0
      }
      const monthlyTotal = pkg.price * 12
      const savings = monthlyTotal - pkg.yearly_price
      return Math.round((savings / monthlyTotal) * 100)
    }

    const toggleBillingInterval = () => {
      billingInterval.value = billingInterval.value === 'monthly' ? 'yearly' : 'monthly'
    }

    const subscribeToPackage = async () => {
      if (!selectedPackage.value) return

      try {
        subscribing.value = true
        const token = localStorage.getItem('token')
        
        // Use the billing interval directly
        const interval = billingInterval.value

        const response = await fetch('/api/reseller/subscribe', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            reseller_package_id: selectedPackage.value,
            billing_interval: interval
          })
        })

        if (response.ok) {
          const data = await response.json()
          if (data.checkout_url) {
            // Redirect to Stripe checkout
            window.location.href = data.checkout_url
          } else {
            // Handle success case
            await loadBillingData()
          }
        } else {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Failed to create subscription')
        }
      } catch (err) {
        console.error('Error subscribing to package:', err)
        error.value = err.message || 'Failed to subscribe to package'
      } finally {
        subscribing.value = false
      }
    }

    const retryPayment = async () => {
      try {
        retryingPayment.value = true
        const token = localStorage.getItem('token')

        const response = await fetch('/api/reseller/retry-payment', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          if (data.checkout_url) {
            // Redirect to Stripe checkout
            window.location.href = data.checkout_url
          } else {
            throw new Error('No checkout URL received')
          }
        } else {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Failed to create retry payment')
        }
      } catch (err) {
        console.error('Error retrying payment:', err)
        error.value = err.message || 'Failed to retry payment'
      } finally {
        retryingPayment.value = false
      }
    }

    onMounted(() => {
      loadBillingData()
      // Load packages if no subscription
      if (!subscription.value) {
        loadPackages()
      }
    })

    return {
      loading,
      error,
      subscription,
      transactions,
      usage,
      packages,
      loadingPackages,
      selectedPackage,
      billingInterval,
      subscribing,
      retryingPayment,
      loadBillingData,
      loadTransactions,
      loadPackages,
      selectPackage,
      getYearlySavingsPercentage,
      toggleBillingInterval,
      subscribeToPackage,
      retryPayment,
      formatDate,
      getStatusColor,
      getTransactionStatusClass,
      getTransactionIconClass
    }
  }
}
</script>
