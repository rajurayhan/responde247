<template>
  <SuperAdminLayout title="Reseller Subscriptions" subtitle="Manage reseller subscriptions and usage">
    <div class="flex-1 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="md:flex md:items-center md:justify-between">
            <div class="flex-1 min-w-0">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                    </svg>
                  </div>
                </div>
                <div>
                  <h1 class="text-3xl font-bold text-gray-900">
                    Reseller Subscriptions
                  </h1>
                  <p class="mt-1 text-sm text-gray-600">
                    Manage reseller subscriptions and usage
                  </p>
                </div>
              </div>
            </div>
            <div class="mt-6 md:mt-0 md:ml-4 flex space-x-3">
              <button 
                @click="loadUsageStatistics" 
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              >
                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Usage Stats
              </button>
              <button 
                @click="loadOverageReport" 
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
              >
                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                Overage Report
              </button>
              <button 
                @click="createSubscription" 
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl shadow-sm text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transform transition-all duration-150 hover:scale-105"
              >
                <svg class="-ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Create Subscription
              </button>
            </div>
          </div>
        </div>

        <!-- Search -->
        <div class="mt-8 mb-8">
          <div class="relative max-w-md">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchQuery"
              @input="performSearch"
              type="text"
              placeholder="Search subscriptions by reseller name..."
              class="block w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 text-sm"
            />
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>

        <!-- Subscriptions Results -->
        <div v-else>
          <!-- Empty State -->
          <div v-if="subscriptions.data && subscriptions.data.length === 0" class="text-center py-16">
            <div class="mx-auto h-24 w-24 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center mb-6">
              <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No subscriptions yet</h3>
            <p class="text-gray-500 mb-8 max-w-sm mx-auto">Get started by creating your first reseller subscription to manage billing and usage.</p>
            <button @click="createSubscription" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transform transition-all duration-150 hover:scale-105">
              <svg class="-ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Create First Subscription
            </button>
          </div>

          <!-- Subscriptions Grid -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div 
              v-for="subscription in subscriptions.data" 
              :key="subscription.id" 
              class="group relative bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg hover:border-gray-300 transition-all duration-300 transform hover:-translate-y-1"
            >
              <!-- Status Indicator Bar -->
              <div 
                :class="[
                  'absolute top-0 left-0 right-0 h-1 rounded-t-xl',
                  getStatusColor(subscription.status)
                ]"
              ></div>

              <!-- Card Content -->
              <div class="p-6">
                <!-- Header with Reseller and Status -->
                <div class="flex items-start justify-between mb-4">
                  <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-semibold text-gray-900 truncate group-hover:text-gray-700 transition-colors">
                      {{ subscription.reseller?.org_name || 'Unknown Reseller' }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                      {{ subscription.package?.name || 'Unknown Package' }}
                    </p>
                  </div>
                  
                  <!-- Status Badge -->
                  <span :class="[
                    'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset ml-2',
                    getStatusClasses(subscription.status)
                  ]">
                    <svg 
                      :class="[
                        'w-1.5 h-1.5 mr-1',
                        getStatusDotClasses(subscription.status)
                      ]" 
                      fill="currentColor" 
                      viewBox="0 0 8 8"
                    >
                      <circle cx="4" cy="4" r="3" />
                    </svg>
                    {{ subscription.status }}
                  </span>
                </div>

                <!-- Subscription Details -->
                <div class="space-y-3 mb-6">
                  <div class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                    </svg>
                    <span>
                      ${{ parseFloat(subscription.custom_amount || (subscription.billing_interval === 'yearly' ? subscription.package?.yearly_price : subscription.package?.price) || 0).toFixed(2) }}
                      /{{ subscription.billing_interval === 'yearly' ? 'year' : 'month' }}
                    </span>
                    <span v-if="subscription.billing_interval === 'yearly'" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                      Yearly
                    </span>
                  </div>
                  
                  <div v-if="subscription.current_period_start" class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Period: {{ formatDate(subscription.current_period_start) }} - {{ formatDate(subscription.current_period_end) }}</span>
                  </div>
                  
                  <div v-if="subscription.trial_ends_at" class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Trial ends: {{ formatDate(subscription.trial_ends_at) }}</span>
                  </div>
                  
                  <!-- Payment Link/Checkout Session Status -->
                  <div v-if="subscription.status === 'pending' && (subscription.payment_link_url || subscription.checkout_session_url)" class="flex items-center justify-between text-sm text-amber-600">
                    <div class="flex items-center">
                      <svg class="w-4 h-4 mr-2 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                      </svg>
                      <span>{{ subscription.checkout_session_url ? 'Checkout session sent' : 'Payment link sent' }}</span>
                    </div>
                    <div class="flex space-x-1">
                      <button
                        v-if="subscription.payment_link_url"
                        @click="copyToClipboard(subscription.payment_link_url, 'Payment Link URL')"
                        class="p-1 text-amber-600 hover:text-amber-700 hover:bg-amber-50 rounded transition-colors"
                        title="Copy Payment Link URL"
                      >
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                      </button>
                      <button
                        v-if="subscription.checkout_session_url"
                        @click="copyToClipboard(subscription.checkout_session_url, 'Checkout Session URL')"
                        class="p-1 text-amber-600 hover:text-amber-700 hover:bg-amber-50 rounded transition-colors"
                        title="Copy Checkout Session URL"
                      >
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                      </button>
                    </div>
                  </div>
                  
                  <div v-if="subscription.status === 'pending' && !subscription.payment_link_url && !subscription.checkout_session_url" class="flex items-center text-sm text-red-600">
                    <svg class="w-4 h-4 mr-2 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <span>No payment link</span>
                  </div>

                </div>

                <!-- Usage Information -->
                <div v-if="subscription.usage_stats" class="mb-6 p-4 bg-gray-50 rounded-lg">
                  <h4 class="text-sm font-medium text-gray-900 mb-2">Usage This Period</h4>
                  <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <span class="text-gray-600">Minutes:</span>
                      <span class="font-medium text-gray-900 ml-1">{{ subscription.usage_stats.minutes_used || 0 }}</span>
                    </div>
                    <div>
                      <span class="text-gray-600">Overage:</span>
                      <span class="font-medium text-gray-900 ml-1">${{ parseFloat(subscription.usage_stats.overage_charges || 0).toFixed(2) }}</span>
                    </div>
                  </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                  <div class="flex space-x-2">
                    <button 
                      v-if="subscription.status === 'active'"
                      @click="cancelSubscription(subscription)" 
                      class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 text-red-700 bg-red-50 hover:bg-red-100 focus:ring-red-500 ring-1 ring-red-200"
                      title="Cancel subscription"
                    >
                      <svg class="w-3 h-3 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                      Cancel
                    </button>
                    
                    <button 
                      v-if="subscription.status === 'pending' && (subscription.payment_link_url || subscription.checkout_session_url)"
                      @click="resendPaymentLink(subscription)" 
                      class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 text-blue-700 bg-blue-50 hover:bg-blue-100 focus:ring-blue-500 ring-1 ring-blue-200"
                      :title="subscription.checkout_session_url ? 'Resend checkout session' : 'Resend payment link'"
                    >
                      <svg class="w-3 h-3 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                      </svg>
                      {{ subscription.checkout_session_url ? 'Resend Session' : 'Resend Link' }}
                    </button>
                  </div>
                  
                  <button 
                    v-if="subscription.status === 'cancelled'"
                    @click="reactivateSubscription(subscription)" 
                    class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 text-green-700 bg-green-50 hover:bg-green-100 focus:ring-green-500 ring-1 ring-green-200"
                    title="Reactivate subscription"
                  >
                    <svg class="w-3 h-3 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reactivate
                  </button>
                  
                  <div class="flex items-center space-x-2">
                    <button 
                      @click="viewSubscription(subscription)" 
                      class="inline-flex items-center p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                      title="View details"
                    >
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Hover Overlay -->
              <div class="absolute inset-0 rounded-xl bg-gradient-to-br from-white/0 to-gray-50/0 group-hover:from-white/5 group-hover:to-gray-50/5 pointer-events-none transition-all duration-300"></div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="subscriptions.last_page > 1" class="mt-8 bg-white rounded-xl border border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <p class="text-sm text-gray-600">
                  Showing <span class="font-semibold text-gray-900">{{ subscriptions.from }}</span> to <span class="font-semibold text-gray-900">{{ subscriptions.to }}</span> of <span class="font-semibold text-gray-900">{{ subscriptions.total }}</span> subscriptions
                </p>
              </div>
              
              <div class="flex items-center space-x-1">
                <button 
                  @click="goToPage(subscriptions.current_page - 1)" 
                  :disabled="subscriptions.current_page <= 1" 
                  class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7" />
                  </svg>
                  Previous
                </button>
                
                <div class="hidden sm:flex items-center space-x-1 mx-2">
                  <button 
                    v-for="page in visiblePages" 
                    :key="page" 
                    @click="goToPage(page)" 
                    :class="[
                      'inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                      page === subscriptions.current_page 
                        ? 'bg-blue-100 text-blue-700 border border-blue-300' 
                        : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                    ]"
                  >
                    {{ page }}
                  </button>
                </div>
                
                <button 
                  @click="goToPage(subscriptions.current_page + 1)" 
                  :disabled="subscriptions.current_page >= subscriptions.last_page" 
                  class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  Next
                  <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Subscription Form Modal -->
    <SubscriptionFormModal
      :show="showModal"
      :subscription="selectedSubscription"
      @close="closeModal"
      @saved="handleSubscriptionSaved"
    />

    <!-- Usage Statistics Modal -->
    <UsageStatisticsModal
      :show="showUsageModal"
      @close="showUsageModal = false"
    />

    <!-- Overage Report Modal -->
    <OverageReportModal
      :show="showOverageModal"
      @close="showOverageModal = false"
    />
  </SuperAdminLayout>
</template>

<script>
import { ref, onMounted, computed, getCurrentInstance } from 'vue'
import SuperAdminLayout from '../layouts/SuperAdminLayout.vue'
import SubscriptionFormModal from './SubscriptionFormModal.vue'
import UsageStatisticsModal from './UsageStatisticsModal.vue'
import OverageReportModal from './OverageReportModal.vue'
import Swal from 'sweetalert2'

export default {
  name: 'ResellerSubscriptions',
  components: {
    SuperAdminLayout,
    SubscriptionFormModal,
    UsageStatisticsModal,
    OverageReportModal
  },
  setup() {
    const { proxy } = getCurrentInstance()
    const loading = ref(true)
    const subscriptions = ref({})
    const searchQuery = ref('')
    const showModal = ref(false)
    const showUsageModal = ref(false)
    const showOverageModal = ref(false)
    const selectedSubscription = ref(null)
    const searchTimeout = ref(null)

    const fetchSubscriptions = async (page = 1, search = '') => {
      loading.value = true
      try {
        let url = `/api/super-admin/reseller-subscriptions?page=${page}`
        if (search) {
          url += `&search=${encodeURIComponent(search)}`
        }

        const response = await fetch(url, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
          }
        })

        if (!response.ok) {
          throw new Error('Failed to fetch subscriptions')
        }

        const data = await response.json()
        if (data.success) {
          subscriptions.value = data.data
        } else {
          throw new Error(data.message || 'Failed to fetch subscriptions')
        }
      } catch (error) {
        console.error('Error fetching subscriptions:', error)
        if (proxy.$toast && proxy.$toast.error) {
          proxy.$toast.error('Error fetching subscriptions')
        }
      } finally {
        loading.value = false
      }
    }

    const performSearch = () => {
      if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
      }
      searchTimeout.value = setTimeout(() => {
        fetchSubscriptions(1, searchQuery.value)
      }, 300)
    }

    const createSubscription = () => {
      selectedSubscription.value = null
      showModal.value = true
    }

    const viewSubscription = (subscription) => {
      selectedSubscription.value = { ...subscription }
      showModal.value = true
    }

    const cancelSubscription = async (subscription) => {
      const result = await Swal.fire({
        title: 'Cancel Subscription?',
        text: `Are you sure you want to cancel the subscription for "${subscription.reseller?.org_name}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, cancel it!'
      })

      if (result.isConfirmed) {
        try {
          const response = await fetch(`/api/super-admin/reseller-subscriptions/${subscription.id}/cancel`, {
            method: 'PATCH',
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`,
              'Accept': 'application/json',
            }
          })

          const data = await response.json()
          if (data.success) {
            if (proxy.$toast && proxy.$toast.success) {
              proxy.$toast.success('Subscription cancelled successfully')
            }
            fetchSubscriptions(subscriptions.value.current_page || 1, searchQuery.value)
          } else {
            throw new Error(data.message || 'Failed to cancel subscription')
          }
        } catch (error) {
          console.error('Error cancelling subscription:', error)
          if (proxy.$toast && proxy.$toast.error) {
            proxy.$toast.error('Error cancelling subscription')
          }
        }
      }
    }

    const reactivateSubscription = async (subscription) => {
      const result = await Swal.fire({
        title: 'Reactivate Subscription?',
        text: `Are you sure you want to reactivate the subscription for "${subscription.reseller?.org_name}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, reactivate it!'
      })

      if (result.isConfirmed) {
        try {
          const response = await fetch(`/api/super-admin/reseller-subscriptions/${subscription.id}/reactivate`, {
            method: 'PATCH',
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`,
              'Accept': 'application/json',
            }
          })

          const data = await response.json()
          if (data.success) {
            if (proxy.$toast && proxy.$toast.success) {
              proxy.$toast.success('Subscription reactivated successfully')
            }
            fetchSubscriptions(subscriptions.value.current_page || 1, searchQuery.value)
          } else {
            throw new Error(data.message || 'Failed to reactivate subscription')
          }
        } catch (error) {
          console.error('Error reactivating subscription:', error)
          if (proxy.$toast && proxy.$toast.error) {
            proxy.$toast.error('Error reactivating subscription')
          }
        }
      }
    }

    const resendPaymentLink = async (subscription) => {
      const linkType = subscription.checkout_session_url ? 'checkout session' : 'payment link';
      const result = await Swal.fire({
        title: `Resend ${linkType.charAt(0).toUpperCase() + linkType.slice(1)}?`,
        text: `Are you sure you want to resend the ${linkType} to "${subscription.reseller?.org_name}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, resend it!'
      })

      if (result.isConfirmed) {
        try {
          const response = await fetch(`/api/super-admin/reseller-subscriptions/${subscription.id}/resend-payment-link`, {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`,
              'Accept': 'application/json',
            }
          })

          const data = await response.json()
          if (data.success) {
            if (proxy.$toast && proxy.$toast.success) {
              proxy.$toast.success(`${linkType.charAt(0).toUpperCase() + linkType.slice(1)} resent successfully`)
            }
            fetchSubscriptions(subscriptions.value.current_page || 1, searchQuery.value)
          } else {
            throw new Error(data.message || `Failed to resend ${linkType}`)
          }
        } catch (error) {
          console.error(`Error resending ${linkType}:`, error)
          if (proxy.$toast && proxy.$toast.error) {
            proxy.$toast.error(`Error resending ${linkType}`)
          }
        }
      }
    }

    const loadUsageStatistics = () => {
      showUsageModal.value = true
    }

    const loadOverageReport = () => {
      showOverageModal.value = true
    }

    const closeModal = () => {
      showModal.value = false
      selectedSubscription.value = null
    }

    const handleSubscriptionSaved = () => {
      closeModal()
      fetchSubscriptions(subscriptions.value.current_page || 1, searchQuery.value)
    }

    const goToPage = (page) => {
      if (page >= 1 && page <= subscriptions.value.last_page) {
        fetchSubscriptions(page, searchQuery.value)
      }
    }

    const visiblePages = computed(() => {
      const current = subscriptions.value.current_page || 1
      const last = subscriptions.value.last_page || 1
      const pages = []
      
      let start = Math.max(1, current - 2)
      let end = Math.min(last, current + 2)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    })

    const getStatusColor = (status) => {
      const colors = {
        'active': 'bg-gradient-to-r from-green-400 to-emerald-500',
        'cancelled': 'bg-gradient-to-r from-red-400 to-rose-500',
        'expired': 'bg-gradient-to-r from-gray-400 to-gray-500',
        'trial': 'bg-gradient-to-r from-yellow-400 to-amber-500',
        'pending': 'bg-gradient-to-r from-blue-400 to-indigo-500'
      }
      return colors[status] || 'bg-gradient-to-r from-gray-400 to-gray-500'
    }

    const getStatusClasses = (status) => {
      const classes = {
        'active': 'bg-green-50 text-green-700 ring-green-600/20',
        'cancelled': 'bg-red-50 text-red-700 ring-red-600/20',
        'expired': 'bg-gray-50 text-gray-700 ring-gray-600/20',
        'trial': 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
        'pending': 'bg-blue-50 text-blue-700 ring-blue-600/20'
      }
      return classes[status] || 'bg-gray-50 text-gray-700 ring-gray-600/20'
    }

    const getStatusDotClasses = (status) => {
      const classes = {
        'active': 'text-green-400',
        'cancelled': 'text-red-400',
        'expired': 'text-gray-400',
        'trial': 'text-yellow-400',
        'pending': 'text-blue-400'
      }
      return classes[status] || 'text-gray-400'
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }

    // Copy to clipboard function
    const copyToClipboard = async (text, label) => {
      try {
        await navigator.clipboard.writeText(text)
        if (proxy.$toast && proxy.$toast.success) {
          proxy.$toast.success('Copied')
        } else {
          // Fallback for environments without toast
          alert('Copied')
        }
      } catch (err) {
        console.error('Failed to copy to clipboard:', err)
        if (proxy.$toast && proxy.$toast.error) {
          proxy.$toast.error('Failed to copy')
        } else {
          alert('Failed to copy')
        }
      }
    }

    onMounted(() => {
      fetchSubscriptions()
    })

    return {
      loading,
      subscriptions,
      searchQuery,
      showModal,
      showUsageModal,
      showOverageModal,
      selectedSubscription,
      fetchSubscriptions,
      performSearch,
      createSubscription,
      viewSubscription,
      cancelSubscription,
      reactivateSubscription,
      resendPaymentLink,
      loadUsageStatistics,
      loadOverageReport,
      closeModal,
      handleSubscriptionSaved,
      goToPage,
      visiblePages,
      getStatusColor,
      getStatusClasses,
      getStatusDotClasses,
      formatDate,
      copyToClipboard
    }
  }
}
</script>