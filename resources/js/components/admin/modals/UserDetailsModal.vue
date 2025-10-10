<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="close"></div>

      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="w-full">
              <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                  User Details
                </h3>
                <button @click="close" class="text-gray-400 hover:text-gray-600">
                  <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <div v-if="loading" class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
              </div>

              <div v-else-if="userDetails" class="space-y-6">
                <!-- User Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                  <h4 class="text-lg font-medium text-gray-900 mb-4">User Information</h4>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Name</label>
                      <p class="mt-1 text-sm text-gray-900">{{ userDetails.user.name }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Email</label>
                      <p class="mt-1 text-sm text-gray-900">{{ userDetails.user.email }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Role</label>
                      <span class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            :class="getRoleBadgeClass(userDetails.user.role)">
                        {{ userDetails.user.role.replace('_', ' ').toUpperCase() }}
                      </span>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Status</label>
                      <span class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            :class="getStatusBadgeClass(userDetails.user.status, userDetails.user.deleted_at)">
                        {{ userDetails.user.deleted_at ? 'DELETED' : userDetails.user.status.toUpperCase() }}
                      </span>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Phone</label>
                      <p class="mt-1 text-sm text-gray-900">{{ userDetails.user.phone || 'Not provided' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Company</label>
                      <p class="mt-1 text-sm text-gray-900">{{ userDetails.user.company || 'Not provided' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Reseller</label>
                      <p class="mt-1 text-sm text-gray-900">{{ userDetails.user.reseller?.org_name || 'No Reseller' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Created At</label>
                      <p class="mt-1 text-sm text-gray-900">{{ formatDate(userDetails.user.created_at) }}</p>
                    </div>
                    <div v-if="userDetails.user.bio">
                      <label class="block text-sm font-medium text-gray-700">Bio</label>
                      <p class="mt-1 text-sm text-gray-900">{{ userDetails.user.bio }}</p>
                    </div>
                  </div>
                </div>

                <!-- Statistics -->
                <div class="bg-gray-50 rounded-lg p-4">
                  <h4 class="text-lg font-medium text-gray-900 mb-4">Statistics</h4>
                  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="text-center">
                      <div class="text-2xl font-bold text-blue-600">{{ userDetails.statistics.total_assistants }}</div>
                      <div class="text-sm text-gray-500">Assistants</div>
                    </div>
                    <div class="text-center">
                      <div class="text-2xl font-bold text-green-600">{{ userDetails.statistics.total_subscriptions }}</div>
                      <div class="text-sm text-gray-500">Subscriptions</div>
                    </div>
                    <div class="text-center">
                      <div class="text-2xl font-bold text-purple-600">{{ userDetails.statistics.total_transactions }}</div>
                      <div class="text-sm text-gray-500">Transactions</div>
                    </div>
                    <div class="text-center">
                      <div class="text-2xl font-bold text-yellow-600">${{ userDetails.statistics.total_spent }}</div>
                      <div class="text-sm text-gray-500">Total Spent</div>
                    </div>
                  </div>
                </div>

                <!-- Subscriptions -->
                <div v-if="userDetails.user.subscriptions?.length > 0" class="bg-gray-50 rounded-lg p-4">
                  <h4 class="text-lg font-medium text-gray-900 mb-4">Subscriptions</h4>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                      <thead class="bg-gray-100">
                        <tr>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        </tr>
                      </thead>
                      <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="subscription in userDetails.user.subscriptions" :key="subscription.id">
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ subscription.package?.name || 'Unknown Package' }}
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                  :class="getSubscriptionStatusBadgeClass(subscription.status)">
                              {{ subscription.status.toUpperCase() }}
                            </span>
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div v-if="subscription.current_period_start && subscription.current_period_end">
                              {{ formatDate(subscription.current_period_start) }} - {{ formatDate(subscription.current_period_end) }}
                            </div>
                            <div v-else class="text-gray-400">No period set</div>
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ formatDate(subscription.created_at) }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Assistants -->
                <div v-if="userDetails.user.assistants?.length > 0" class="bg-gray-50 rounded-lg p-4">
                  <h4 class="text-lg font-medium text-gray-900 mb-4">Assistants</h4>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                      <thead class="bg-gray-100">
                        <tr>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        </tr>
                      </thead>
                      <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="assistant in userDetails.user.assistants" :key="assistant.id">
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ assistant.name }}
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ assistant.phone_number || 'No phone' }}
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                  :class="getAssistantTypeBadgeClass(assistant.type)">
                              {{ assistant.type.toUpperCase() }}
                            </span>
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ formatDate(assistant.created_at) }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Recent Transactions -->
                <div v-if="userDetails.user.transactions?.length > 0" class="bg-gray-50 rounded-lg p-4">
                  <h4 class="text-lg font-medium text-gray-900 mb-4">Recent Transactions</h4>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                      <thead class="bg-gray-100">
                        <tr>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                      </thead>
                      <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="transaction in userDetails.user.transactions.slice(0, 5)" :key="transaction.id">
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${{ transaction.amount }}
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                  :class="getTransactionStatusBadgeClass(transaction.status)">
                              {{ transaction.status.toUpperCase() }}
                            </span>
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ formatDate(transaction.created_at) }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            type="button"
            @click="close"
            class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch } from 'vue'

export default {
  name: 'UserDetailsModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    user: {
      type: Object,
      default: null
    }
  },
  emits: ['close'],
  setup(props, { emit }) {
    const loading = ref(false)
    const userDetails = ref(null)

    // Load user details when modal opens
    watch(() => props.show, async (newShow) => {
      if (newShow && props.user) {
        await loadUserDetails()
      }
    })

    const loadUserDetails = async () => {
      if (!props.user) return
      
      loading.value = true
      try {
        const response = await fetch(`/api/super-admin/users/${props.user.id}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        })

        const data = await response.json()

        if (data.success) {
          userDetails.value = data.data
        } else {
          console.error('Failed to load user details:', data.message)
        }
      } catch (error) {
        console.error('Error loading user details:', error)
      } finally {
        loading.value = false
      }
    }

    const close = () => {
      emit('close')
    }

    // Utility functions
    const getRoleBadgeClass = (role) => {
      const classes = {
        'admin': 'bg-red-100 text-red-800',
        'content_admin': 'bg-purple-100 text-purple-800',
        'reseller_admin': 'bg-blue-100 text-blue-800',
        'user': 'bg-gray-100 text-gray-800'
      }
      return classes[role] || 'bg-gray-100 text-gray-800'
    }

    const getStatusBadgeClass = (status, isDeleted) => {
      if (isDeleted) return 'bg-red-100 text-red-800'
      
      const classes = {
        'active': 'bg-green-100 text-green-800',
        'inactive': 'bg-yellow-100 text-yellow-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const getSubscriptionStatusBadgeClass = (status) => {
      const classes = {
        'active': 'bg-green-100 text-green-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'cancelled': 'bg-red-100 text-red-800',
        'expired': 'bg-gray-100 text-gray-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const getAssistantTypeBadgeClass = (type) => {
      const classes = {
        'production': 'bg-green-100 text-green-800',
        'test': 'bg-blue-100 text-blue-800',
        'demo': 'bg-purple-100 text-purple-800'
      }
      return classes[type] || 'bg-gray-100 text-gray-800'
    }

    const getTransactionStatusBadgeClass = (status) => {
      const classes = {
        'completed': 'bg-green-100 text-green-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'failed': 'bg-red-100 text-red-800',
        'refunded': 'bg-gray-100 text-gray-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString()
    }

    return {
      loading,
      userDetails,
      loadUserDetails,
      close,
      getRoleBadgeClass,
      getStatusBadgeClass,
      getSubscriptionStatusBadgeClass,
      getAssistantTypeBadgeClass,
      getTransactionStatusBadgeClass,
      formatDate
    }
  }
}
</script>
