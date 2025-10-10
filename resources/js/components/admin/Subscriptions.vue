<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Navigation -->
    <Navigation />

    <div class="flex-1 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
              Subscription History
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Manage user subscriptions and view subscription history
            </p>
          </div>
          <div class="mt-4 md:mt-0 md:ml-4">
            <button
              @click="createSubscription"
              class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Create Subscription
            </button>
          </div>
        </div>

        <!-- Filters -->
        <div class="mt-6 bg-white shadow rounded-lg p-6">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select v-model="filters.status" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="cancelled">Cancelled</option>
                <option value="expired">Expired</option>
                <option value="trial">Trial</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Package</label>
              <select v-model="filters.package_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                <option value="">All Packages</option>
                <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
              <select v-model="filters.date_range" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                <option value="">All Time</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="quarter">This Quarter</option>
                <option value="year">This Year</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
              <input v-model="filters.search" type="text" placeholder="Search by user name or email" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
            </div>
          </div>
          
          <div class="mt-4 flex justify-end">
            <button @click="applyFilters" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
              Apply Filters
            </button>
          </div>
        </div>

        <!-- Statistics -->
        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Subscriptions</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.total || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Active Subscriptions</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.active || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Pending Payments</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.pending || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Cancelled</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.cancelled || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="mt-8 flex justify-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
        </div>

        <!-- Subscriptions Table -->
        <div v-else class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
          <!-- Mobile Card View (hidden on larger screens) -->
          <div class="block md:hidden">
            <div v-for="subscription in subscriptions" :key="subscription.id" class="border-b border-gray-200 last:border-b-0">
              <div class="p-4 cursor-pointer hover:bg-gray-50 transition-colors duration-150" @click="viewSubscription(subscription)">
                <!-- Header with User and Status -->
                <div class="flex items-start justify-between mb-3">
                  <div class="flex items-center space-x-3 min-w-0 flex-1">
                    <div class="flex-shrink-0">
                      <div v-if="subscription.user.profile_picture" class="h-10 w-10 rounded-full overflow-hidden ring-1 ring-gray-200">
                        <img :src="subscription.user.profile_picture" :alt="subscription.user.name" class="h-full w-full object-cover">
                      </div>
                      <div v-else class="h-10 w-10 rounded-full bg-green-600 flex items-center justify-center ring-1 ring-gray-200">
                        <span class="text-white font-medium text-sm">{{ getUserInitials(subscription.user.name) }}</span>
                      </div>
                    </div>
                    <div class="min-w-0 flex-1">
                      <h3 class="text-sm font-medium text-gray-900 truncate">
                        {{ subscription.user.name }}
                      </h3>
                      <p class="text-xs text-gray-500 truncate">{{ subscription.user.email }}</p>
                    </div>
                  </div>
                  <span :class="getStatusBadgeClass(subscription.status)" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium">
                    {{ subscription.status }}
                  </span>
                </div>

                <!-- Details -->
                <div class="space-y-2 mb-3">
                  <div class="text-xs text-gray-600">
                    <span class="font-medium">Package:</span> {{ subscription.package.name }} - ${{ subscription.package.price }}/month
                  </div>
                  <div class="text-xs text-gray-600">
                    <span class="font-medium">Period:</span> {{ formatDate(subscription.current_period_start) }} to {{ formatDate(subscription.current_period_end) }}
                  </div>
                  <div class="text-xs text-gray-500">
                    <span class="font-medium">Created:</span> {{ formatDate(subscription.created_at) }}
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between">
                  <button 
                    @click.stop="viewSubscription(subscription)" 
                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100 rounded-md transition-colors"
                  >
                    View Details
                  </button>
                  
                  <div class="flex items-center space-x-1">
                    <button 
                      v-if="subscription.status !== 'active'" 
                      @click.stop="deleteSubscription(subscription)" 
                      class="p-1 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-md"
                      title="Delete subscription"
                    >
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Desktop Table View (hidden on mobile) -->
          <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    User
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Package
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Period
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Created
                  </th>
                  <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr 
                  v-for="subscription in subscriptions" 
                  :key="subscription.id" 
                  @click="viewSubscription(subscription)"
                  class="hover:bg-gray-50 transition-colors duration-150 cursor-pointer"
                >
                  <!-- User -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10">
                        <div v-if="subscription.user.profile_picture" class="h-10 w-10 rounded-full overflow-hidden ring-1 ring-gray-200">
                          <img :src="subscription.user.profile_picture" :alt="subscription.user.name" class="h-full w-full object-cover">
                        </div>
                        <div v-else class="h-10 w-10 rounded-full bg-green-600 flex items-center justify-center ring-1 ring-gray-200">
                          <span class="text-white font-medium text-sm">{{ getUserInitials(subscription.user.name) }}</span>
                        </div>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">{{ subscription.user.name }}</div>
                        <div class="text-sm text-gray-500">{{ subscription.user.email }}</div>
                      </div>
                    </div>
                  </td>

                  <!-- Package -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ subscription.package.name }}</div>
                    <div class="text-sm text-gray-500">${{ subscription.package.price }}/month</div>
                  </td>

                  <!-- Status -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getStatusBadgeClass(subscription.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                      {{ subscription.status }}
                    </span>
                  </td>

                  <!-- Period -->
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div>{{ formatDate(subscription.current_period_start) }}</div>
                    <div class="text-gray-500">to {{ formatDate(subscription.current_period_end) }}</div>
                  </td>

                  <!-- Created -->
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(subscription.created_at) }}
                  </td>

                  <!-- Actions -->
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex items-center justify-end space-x-2">
                      <!-- View Button -->
                      <button 
                        @click.stop="viewSubscription(subscription)" 
                        class="text-green-600 hover:text-green-900 p-1 rounded-md hover:bg-green-50 transition-colors duration-200"
                        title="View details"
                      >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                      </button>

                      <!-- Edit Button -->
                      <button 
                        @click.stop="editSubscription(subscription)" 
                        class="text-blue-600 hover:text-blue-900 p-1 rounded-md hover:bg-blue-50 transition-colors duration-200"
                        title="Edit subscription"
                      >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>

                      <!-- Delete Button (only for non-active subscriptions) -->
                      <button 
                        v-if="subscription.status !== 'active'" 
                        @click.stop="deleteSubscription(subscription)" 
                        class="text-red-600 hover:text-red-900 p-1 rounded-md hover:bg-red-50 transition-colors duration-200"
                        title="Delete subscription"
                      >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="subscriptions.length > 0" class="mt-6 flex items-center justify-between">
          <div class="flex-1 flex justify-between sm:hidden">
            <button @click="previousPage" :disabled="currentPage === 1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
              Previous
            </button>
            <button @click="nextPage" :disabled="currentPage >= totalPages" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
              Next
            </button>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700">
                Showing <span class="font-medium">{{ (currentPage - 1) * perPage + 1 }}</span> to <span class="font-medium">{{ Math.min(currentPage * perPage, total) }}</span> of <span class="font-medium">{{ total }}</span> results
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                <button @click="previousPage" :disabled="currentPage === 1" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                  Previous
                </button>
                <button @click="nextPage" :disabled="currentPage >= totalPages" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                  Next
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Subscription Details Modal -->
  <div v-if="showDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900">Subscription Details</h3>
          <button @click="showDetailsModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <div v-if="selectedSubscription" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">User</label>
              <p class="text-sm text-gray-900">{{ selectedSubscription.user.name }}</p>
              <p class="text-xs text-gray-500">{{ selectedSubscription.user.email }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Package</label>
              <p class="text-sm text-gray-900">{{ selectedSubscription.package.name }}</p>
              <p class="text-xs text-gray-500">${{ selectedSubscription.package.price }}/month</p>
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Status</label>
              <span :class="getStatusBadgeClass(selectedSubscription.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                {{ selectedSubscription.status }}
              </span>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Stripe ID</label>
              <p class="text-sm text-gray-900">{{ selectedSubscription.stripe_subscription_id || 'N/A' }}</p>
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Period Start</label>
              <p class="text-sm text-gray-900">{{ formatDate(selectedSubscription.current_period_start) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Period End</label>
              <p class="text-sm text-gray-900">{{ formatDate(selectedSubscription.current_period_end) }}</p>
            </div>
          </div>
          
          <div v-if="selectedSubscription.trial_ends_at">
            <label class="block text-sm font-medium text-gray-700">Trial Ends</label>
            <p class="text-sm text-gray-900">{{ formatDate(selectedSubscription.trial_ends_at) }}</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700">Created</label>
            <p class="text-sm text-gray-900">{{ formatDate(selectedSubscription.created_at) }}</p>
          </div>
          
          <div v-if="selectedSubscription.updated_at !== selectedSubscription.created_at">
            <label class="block text-sm font-medium text-gray-700">Last Updated</label>
            <p class="text-sm text-gray-900">{{ formatDate(selectedSubscription.updated_at) }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Footer -->
  <SimpleFooter />

  <!-- Admin Subscription Form Modal -->
  <AdminSubscriptionFormModal
    :show="showFormModal"
    :subscription="editingSubscription"
    @close="showFormModal = false"
    @saved="onSubscriptionSaved"
  />
</template>

<script>
import Navigation from '../shared/Navigation.vue'
import SimpleFooter from '../shared/SimpleFooter.vue'
import AdminSubscriptionFormModal from './AdminSubscriptionFormModal.vue'
import Swal from 'sweetalert2'

export default {
  name: 'Subscriptions',
  components: {
    Navigation,
    SimpleFooter,
    AdminSubscriptionFormModal
  },
  data() {
    return {
      subscriptions: [],
      packages: [],
      loading: true,
      currentPage: 1,
      perPage: 10,
      total: 0,
      stats: {},
      filters: {
        status: '',
        package_id: '',
        date_range: '',
        search: ''
      },
      showDetailsModal: false,
      selectedSubscription: null,
      showFormModal: false,
      editingSubscription: null
    }
  },
  computed: {
    totalPages() {
      return Math.ceil(this.total / this.perPage)
    }
  },
  async mounted() {
    await this.loadPackages()
    await this.loadSubscriptions()
  },
  methods: {
    async loadPackages() {
      try {
        const response = await fetch('/api/admin/subscriptions/packages', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          this.packages = data.data
        }
      } catch (error) {
        console.error('Error loading packages:', error)
      }
    },

    async loadSubscriptions() {
      try {
        this.loading = true
        const params = new URLSearchParams({
          page: this.currentPage,
          per_page: this.perPage,
          ...this.filters
        })

        const response = await fetch(`/api/admin/subscriptions?${params}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          this.subscriptions = data.data
          this.total = data.meta?.total || 0
          this.stats = data.stats || {}
        } else {
          console.error('Failed to load subscriptions')
        }
      } catch (error) {
        console.error('Error loading subscriptions:', error)
      } finally {
        this.loading = false
      }
    },

    applyFilters() {
      this.currentPage = 1
      this.loadSubscriptions()
    },

    previousPage() {
      if (this.currentPage > 1) {
        this.currentPage--
        this.loadSubscriptions()
      }
    },

    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++
        this.loadSubscriptions()
      }
    },

    getUserInitials(name) {
      return name ? name.split(' ').map(n => n[0]).join('').toUpperCase() : 'U'
    },

    getStatusBadgeClass(status) {
      const classes = {
        active: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        cancelled: 'bg-red-100 text-red-800',
        expired: 'bg-gray-100 text-gray-800',
        trial: 'bg-blue-100 text-blue-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    },

    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString()
    },

    viewSubscription(subscription) {
      this.selectedSubscription = subscription
      this.showDetailsModal = true
    },

    createSubscription() {
      this.editingSubscription = null
      this.showFormModal = true
    },

    editSubscription(subscription) {
      this.editingSubscription = subscription
      this.showFormModal = true
    },

    onSubscriptionSaved() {
      this.loadSubscriptions()
    },

    async deleteSubscription(subscription) {
      const result = await Swal.fire({
        title: 'Delete Subscription?',
        text: `Are you sure you want to permanently delete this subscription for "${subscription.user.name}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
      })

      if (result.isConfirmed) {
        try {
          const response = await fetch(`/api/admin/subscriptions/${subscription.id}`, {
            method: 'DELETE',
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`,
              'Accept': 'application/json',
            }
          })

          const data = await response.json()
          if (data.success) {
            // Show success message using SweetAlert2
            await Swal.fire({
              title: 'Success!',
              text: 'Subscription deleted successfully',
              icon: 'success',
              timer: 2000,
              showConfirmButton: false
            })
            // Remove the subscription from the list
            const index = this.subscriptions.findIndex(s => s.id === subscription.id)
            if (index !== -1) {
              this.subscriptions.splice(index, 1)
            }
            // Update stats
            this.stats.total = (this.stats.total || 0) - 1
            if (subscription.status === 'active') {
              this.stats.active = (this.stats.active || 0) - 1
            }
          } else {
            throw new Error(data.message || 'Failed to delete subscription')
          }
        } catch (error) {
          console.error('Error deleting subscription:', error)
          // Show error message using SweetAlert2
          await Swal.fire({
            title: 'Error!',
            text: error.message || 'Error deleting subscription',
            icon: 'error',
            confirmButtonText: 'OK'
          })
        }
      }
    }
  }
}
</script> 