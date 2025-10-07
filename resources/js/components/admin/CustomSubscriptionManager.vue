<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Custom Subscription Manager</h2>
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          Create Custom Subscription
        </button>
      </div>
    </div>

    <!-- Content -->
    <div class="p-6">
      <!-- Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 p-4 rounded-lg">
          <div class="text-sm font-medium text-blue-600">Total Custom Subscriptions</div>
          <div class="text-2xl font-bold text-blue-900">{{ stats.total }}</div>
        </div>
        <div class="bg-yellow-50 p-4 rounded-lg">
          <div class="text-sm font-medium text-yellow-600">Pending Payment</div>
          <div class="text-2xl font-bold text-yellow-900">{{ stats.pending }}</div>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
          <div class="text-sm font-medium text-green-600">Active</div>
          <div class="text-2xl font-bold text-green-900">{{ stats.active }}</div>
        </div>
        <div class="bg-red-50 p-4 rounded-lg">
          <div class="text-sm font-medium text-red-600">Expired Links</div>
          <div class="text-2xl font-bold text-red-900">{{ stats.expired }}</div>
        </div>
      </div>

      <!-- Subscriptions Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Link</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="subscription in customSubscriptions" :key="subscription.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="text-sm font-medium text-gray-900">{{ subscription.user.name }}</div>
                  <div class="text-sm text-gray-500 ml-2">{{ subscription.user.email }}</div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ subscription.package.name }}</div>
                <div class="text-sm text-gray-500">{{ subscription.package.description }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${{ subscription.custom_amount }}</div>
                <div class="text-sm text-gray-500">
                  {{ subscription.current_period_start ? formatDate(subscription.current_period_start) : 'N/A' }} - 
                  {{ subscription.current_period_end ? formatDate(subscription.current_period_end) : 'N/A' }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusClass(subscription.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                  {{ subscription.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div v-if="subscription.payment_link_url" class="space-y-2">
                  <a 
                    :href="subscription.payment_link_url" 
                    target="_blank"
                    class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                  >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    Open Link
                  </a>
                  <div class="text-xs text-gray-500">
                    Expires: {{ formatDate(subscription.created_at, 7) }}
                  </div>
                </div>
                <div v-else class="text-sm text-gray-500">No payment link</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(subscription.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                <button
                  v-if="subscription.status === 'pending'"
                  @click="activateSubscription(subscription.id)"
                  class="text-green-600 hover:text-green-900"
                >
                  Activate
                </button>
                                  <button
                    v-if="subscription.status === 'pending' && isPaymentLinkExpired(subscription)"
                    @click="resendPaymentLink(subscription.id)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Resend Link
                  </button>
                <button
                  @click="copyPaymentLink(subscription.payment_link_url)"
                  v-if="subscription.payment_link_url"
                  class="text-gray-600 hover:text-gray-900"
                >
                  Copy Link
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="customSubscriptions.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No custom subscriptions</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by creating a custom subscription for a client.</p>
        <div class="mt-6">
          <button
            @click="showCreateModal = true"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Create Custom Subscription
          </button>
        </div>
      </div>
    </div>

    <!-- Create Custom Subscription Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Create Custom Subscription</h3>
          
          <form @submit.prevent="createCustomSubscription" class="space-y-4">
            <!-- User Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700">User</label>
              <select 
                v-model="form.user_id" 
                required
                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
              >
                <option value="">Select a user</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }} ({{ user.email }})
                </option>
              </select>
            </div>

            <!-- Package Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700">Package</label>
              <select 
                v-model="form.package_id" 
                required
                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
              >
                <option value="">Select a package</option>
                <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                  {{ pkg.name }} - ${{ pkg.price }}/month
                </option>
              </select>
            </div>

            <!-- Custom Amount -->
            <div>
              <label class="block text-sm font-medium text-gray-700">Custom Amount ($)</label>
              <input 
                type="number" 
                v-model="form.custom_amount" 
                required
                min="0.01" 
                step="0.01"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                placeholder="0.00"
              />
            </div>

            <!-- Billing Interval -->
            <div>
              <label class="block text-sm font-medium text-gray-700">Billing Interval</label>
              <select 
                v-model="form.billing_interval" 
                required
                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
              >
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
              </select>
            </div>

            <!-- Duration -->
            <div>
              <label class="block text-sm font-medium text-gray-700">Duration (months)</label>
              <input 
                type="number" 
                v-model="form.duration_months" 
                required
                min="1" 
                max="120"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                placeholder="12"
              />
              <p class="mt-1 text-xs text-gray-500">Maximum 120 months (10 years)</p>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3 pt-4">
              <button
                type="button"
                @click="showCreateModal = false"
                class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="submitting"
                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
              >
                <svg v-if="submitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ submitting ? 'Creating...' : 'Create Subscription' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mt-4">Custom Subscription Created!</h3>
          <div class="mt-4">
            <p class="text-sm text-gray-500 mb-4">Payment link has been generated successfully.</p>
            <div class="bg-gray-50 p-3 rounded-md">
              <p class="text-sm font-medium text-gray-900 mb-2">Payment Link:</p>
              <div class="flex items-center space-x-2">
                <input 
                  :value="successData.payment_link" 
                  readonly
                  class="flex-1 text-sm border-gray-300 rounded-md bg-white"
                />
                <button
                  @click="copyPaymentLink(successData.payment_link)"
                  class="px-3 py-1 text-sm border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                  Copy
                </button>
              </div>
            </div>
            <div class="mt-4 text-sm text-gray-500">
              <p><strong>Amount:</strong> ${{ successData.amount }}</p>
              <p><strong>Expires:</strong> {{ formatDate(new Date(), 7) }}</p>
            </div>
          </div>
          <div class="mt-6">
            <button
              @click="showSuccessModal = false"
              class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CustomSubscriptionManager',
  data() {
    return {
      // State
      customSubscriptions: [],
      users: [],
      packages: [],
      showCreateModal: false,
      showSuccessModal: false,
      submitting: false,
      loading: false,
      
      // Form data
      form: {
        user_id: '',
        package_id: '',
        custom_amount: '',
        billing_interval: 'monthly',
        duration_months: 12
      },
      
      // Success data
      successData: {
        payment_link: '',
        amount: ''
      }
    }
  },
  
  computed: {
    stats() {
      const total = this.customSubscriptions.length
      const pending = this.customSubscriptions.filter(s => s.status === 'pending').length
      const active = this.customSubscriptions.filter(s => s.status === 'active').length
      const expired = this.customSubscriptions.filter(s => s.status === 'pending' && this.isPaymentLinkExpired(s)).length
      
      return { total, pending, active, expired }
    }
  },
  
  async mounted() {
    await Promise.all([
      this.loadCustomSubscriptions(),
      this.loadUsers(),
      this.loadPackages()
    ])
  },
  
  methods: {
    async loadCustomSubscriptions() {
      try {
        this.loading = true
        const response = await fetch('/api/admin/subscriptions/custom', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          this.customSubscriptions = data.data || []
        } else {
          throw new Error('Failed to load custom subscriptions')
        }
      } catch (error) {
        console.error('Error loading custom subscriptions:', error)
        this.showToast('Error loading custom subscriptions', 'error')
      } finally {
        this.loading = false
      }
    },
    
    async loadUsers() {
      try {
        // Get all users for the dropdown (use higher per_page limit)
        const response = await fetch('/api/admin/users?per_page=100', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          this.users = data.data || []
        }
      } catch (error) {
        console.error('Error loading users:', error)
      }
    },
    
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
          this.packages = data.data || []
        }
      } catch (error) {
        console.error('Error loading packages:', error)
      }
    },
    
    async createCustomSubscription() {
      try {
        this.submitting = true
        
        const response = await fetch('/api/admin/subscriptions/custom/create', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.form)
        })
        
        if (response.ok) {
          const data = await response.json()
          this.successData = {
            payment_link: data.data.payment_link,
            amount: data.data.amount
          }
          
          this.showCreateModal = false
          this.showSuccessModal = true
          
          // Reset form
          this.form = {
            user_id: '',
            package_id: '',
            custom_amount: '',
            billing_interval: 'monthly',
            duration_months: 12
          }
          
          // Reload subscriptions
          await this.loadCustomSubscriptions()
          
          this.showToast('Custom subscription created successfully!', 'success')
        } else {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Failed to create custom subscription')
        }
      } catch (error) {
        console.error('Error creating custom subscription:', error)
        this.showToast(error.message, 'error')
      } finally {
        this.submitting = false
      }
    },
    
    async activateSubscription(subscriptionId) {
      try {
        const response = await fetch('/api/admin/subscriptions/custom/activate', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ subscription_id: subscriptionId })
        })
        
        if (response.ok) {
          await this.loadCustomSubscriptions()
          this.showToast('Subscription activated successfully!', 'success')
        } else {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Failed to activate subscription')
        }
      } catch (error) {
        console.error('Error activating subscription:', error)
        this.showToast(error.message, 'error')
      }
    },
    
    async resendPaymentLink(subscriptionId) {
      try {
        const response = await fetch('/api/admin/subscriptions/custom/resend-payment-link', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ subscription_id: subscriptionId })
        })
        
        if (response.ok) {
          const data = await response.json()
          await this.loadCustomSubscriptions()
          this.showToast('Payment link resent successfully!', 'success')
          
          // Show new payment link
          this.successData = {
            payment_link: data.data.payment_link,
            amount: 'Updated'
          }
          this.showSuccessModal = true
        } else {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Failed to resend payment link')
        }
      } catch (error) {
        console.error('Error resending payment link:', error)
        this.showToast(error.message, 'error')
      }
    },
    
    async copyPaymentLink(link) {
      try {
        await navigator.clipboard.writeText(link)
        this.showToast('Payment link copied to clipboard!', 'success')
      } catch (error) {
        console.error('Error copying to clipboard:', error)
        this.showToast('Failed to copy payment link', 'error')
      }
    },
    
    formatDate(date, addDays = 0) {
      if (!date) return 'N/A'
      const d = new Date(date)
      if (addDays > 0) {
        d.setDate(d.getDate() + addDays)
      }
      return d.toLocaleDateString()
    },
    
    getStatusClass(status) {
      switch (status) {
        case 'active':
          return 'bg-green-100 text-green-800'
        case 'pending':
          return 'bg-yellow-100 text-yellow-800'
        case 'cancelled':
          return 'bg-red-100 text-red-800'
        default:
          return 'bg-gray-100 text-gray-800'
      }
    },
    
    isPaymentLinkExpired(subscription) {
      // Payment links typically expire after 7 days
      if (!subscription.created_at) return false
      const created = new Date(subscription.created_at)
      const expired = new Date(created.getTime() + (7 * 24 * 60 * 60 * 1000))
      return new Date() > expired
    },
    
    showToast(message, type = 'info', duration = 3000) {
      if (this.$toast) {
        switch (type) {
          case 'success':
            return this.$toast.success(message, duration)
          case 'error':
            return this.$toast.error(message, duration)
          case 'warning':
            return this.$toast.warning(message, duration)
          case 'info':
          default:
            return this.$toast.info(message, duration)
        }
      } else {
        // Fallback to console if toast is not available
        console.log(`[${type.toUpperCase()}] ${message}`)
      }
    }
  }
}
</script> 