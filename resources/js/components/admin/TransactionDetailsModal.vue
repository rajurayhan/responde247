<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-medium text-gray-900">
              Transaction Details
            </h3>
            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Transaction Details -->
          <div v-if="transaction" class="space-y-6">
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Transaction ID</label>
                <p class="text-sm text-gray-900 font-mono">{{ transaction.transaction_id }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <span :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ring-1 ring-inset',
                  getStatusClasses(transaction.status)
                ]">
                  {{ transaction.status }}
                </span>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                <p class="text-sm text-gray-900">${{ parseFloat(transaction.amount).toFixed(2) }} {{ transaction.currency?.toUpperCase() || 'USD' }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <p class="text-sm text-gray-900">{{ transaction.type }}</p>
              </div>
            </div>

            <!-- Reseller Info -->
            <div class="border-t pt-6">
              <h4 class="text-md font-medium text-gray-900 mb-4">Reseller Information</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Organization</label>
                  <p class="text-sm text-gray-900">{{ transaction.reseller?.org_name || 'N/A' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Billing Email</label>
                  <p class="text-sm text-gray-900">{{ transaction.billing_email }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Billing Name</label>
                  <p class="text-sm text-gray-900">{{ transaction.billing_name || 'N/A' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                  <p class="text-sm text-gray-900">{{ transaction.payment_method || 'N/A' }}</p>
                </div>
              </div>
            </div>

            <!-- Billing Address -->
            <div v-if="transaction.billing_address" class="border-t pt-6">
              <h4 class="text-md font-medium text-gray-900 mb-4">Billing Address</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                  <p class="text-sm text-gray-900">{{ transaction.billing_address }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                  <p class="text-sm text-gray-900">{{ transaction.billing_city || 'N/A' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                  <p class="text-sm text-gray-900">{{ transaction.billing_state || 'N/A' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                  <p class="text-sm text-gray-900">{{ transaction.billing_country || 'N/A' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                  <p class="text-sm text-gray-900">{{ transaction.billing_postal_code || 'N/A' }}</p>
                </div>
              </div>
            </div>

            <!-- Timestamps -->
            <div class="border-t pt-6">
              <h4 class="text-md font-medium text-gray-900 mb-4">Timestamps</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Created</label>
                  <p class="text-sm text-gray-900">{{ formatDate(transaction.created_at) }}</p>
                </div>
                <div v-if="transaction.processed_at">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Processed</label>
                  <p class="text-sm text-gray-900">{{ formatDate(transaction.processed_at) }}</p>
                </div>
                <div v-if="transaction.failed_at">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Failed</label>
                  <p class="text-sm text-gray-900">{{ formatDate(transaction.failed_at) }}</p>
                </div>
              </div>
            </div>

            <!-- Description -->
            <div v-if="transaction.description" class="border-t pt-6">
              <h4 class="text-md font-medium text-gray-900 mb-4">Description</h4>
              <p class="text-sm text-gray-900">{{ transaction.description }}</p>
            </div>

            <!-- Metadata -->
            <div v-if="transaction.metadata" class="border-t pt-6">
              <h4 class="text-md font-medium text-gray-900 mb-4">Additional Data</h4>
              <pre class="text-xs text-gray-600 bg-gray-50 p-3 rounded-lg overflow-auto">{{ JSON.stringify(transaction.metadata, null, 2) }}</pre>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            @click="$emit('close')"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'TransactionDetailsModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    transaction: {
      type: Object,
      default: null
    }
  },
  methods: {
    getStatusClasses(status) {
      const classes = {
        'completed': 'bg-green-50 text-green-700 ring-green-600/20',
        'pending': 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
        'failed': 'bg-red-50 text-red-700 ring-red-600/20',
        'refunded': 'bg-purple-50 text-purple-700 ring-purple-600/20',
        'cancelled': 'bg-gray-50 text-gray-700 ring-gray-600/20'
      }
      return classes[status] || 'bg-gray-50 text-gray-700 ring-gray-600/20'
    },
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
  }
}
</script>

