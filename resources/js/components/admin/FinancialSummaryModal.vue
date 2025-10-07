<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-medium text-gray-900">
              Financial Summary
            </h3>
            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          </div>

          <!-- Financial Summary Content -->
          <div v-else-if="summary" class="space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
              <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center">
                      <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-green-600">Total Revenue</p>
                    <p class="text-2xl font-bold text-green-900">${{ parseFloat(summary.total_revenue || 0).toFixed(2) }}</p>
                  </div>
                </div>
              </div>

              <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
                      <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-blue-600">Completed</p>
                    <p class="text-2xl font-bold text-blue-900">{{ summary.completed_transactions || 0 }}</p>
                  </div>
                </div>
              </div>

              <div class="bg-gradient-to-br from-yellow-50 to-amber-50 rounded-xl p-6 border border-yellow-200">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-lg bg-yellow-100 flex items-center justify-center">
                      <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-yellow-600">Pending</p>
                    <p class="text-2xl font-bold text-yellow-900">{{ summary.pending_transactions || 0 }}</p>
                  </div>
                </div>
              </div>

              <div class="bg-gradient-to-br from-red-50 to-rose-50 rounded-xl p-6 border border-red-200">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-lg bg-red-100 flex items-center justify-center">
                      <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-red-600">Refunded</p>
                    <p class="text-2xl font-bold text-red-900">${{ parseFloat(summary.total_refunds || 0).toFixed(2) }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Revenue by Month Chart -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
              <h4 class="text-lg font-medium text-gray-900 mb-4">Revenue by Month</h4>
              <div v-if="summary.monthly_revenue && summary.monthly_revenue.length > 0" class="space-y-4">
                <div v-for="month in summary.monthly_revenue" :key="month.month" class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                    <span class="text-sm font-medium text-gray-900">{{ month.month }}</span>
                  </div>
                  <div class="flex items-center space-x-4">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                      <div 
                        class="bg-blue-500 h-2 rounded-full" 
                        :style="{ width: `${(month.revenue / Math.max(...summary.monthly_revenue.map(m => m.revenue))) * 100}%` }"
                      ></div>
                    </div>
                    <span class="text-sm font-medium text-gray-900 w-20 text-right">${{ parseFloat(month.revenue).toFixed(2) }}</span>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8 text-gray-500">
                No revenue data available
              </div>
            </div>

            <!-- Transaction Status Breakdown -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
              <h4 class="text-lg font-medium text-gray-900 mb-4">Transaction Status Breakdown</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="status in summary.status_breakdown" :key="status.status" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                  <div class="flex items-center space-x-3">
                    <div :class="[
                      'w-3 h-3 rounded-full',
                      getStatusColor(status.status)
                    ]"></div>
                    <span class="text-sm font-medium text-gray-900 capitalize">{{ status.status }}</span>
                  </div>
                  <div class="text-right">
                    <div class="text-lg font-bold text-gray-900">{{ status.count }}</div>
                    <div class="text-sm text-gray-500">${{ parseFloat(status.amount).toFixed(2) }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Top Resellers -->
            <div v-if="summary.top_resellers && summary.top_resellers.length > 0" class="bg-white border border-gray-200 rounded-xl p-6">
              <h4 class="text-lg font-medium text-gray-900 mb-4">Top Resellers by Revenue</h4>
              <div class="space-y-4">
                <div v-for="(reseller, index) in summary.top_resellers" :key="reseller.reseller_id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                      <span class="text-sm font-bold text-blue-600">#{{ index + 1 }}</span>
                    </div>
                    <div>
                      <div class="text-sm font-medium text-gray-900">{{ reseller.reseller_name }}</div>
                      <div class="text-xs text-gray-500">{{ reseller.transaction_count }} transactions</div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-lg font-bold text-gray-900">${{ parseFloat(reseller.total_revenue).toFixed(2) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Error State -->
          <div v-else class="text-center py-8">
            <div class="mx-auto h-12 w-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
              <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Error loading summary</h3>
            <p class="text-gray-500">Unable to load financial summary data.</p>
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
import { ref, onMounted, getCurrentInstance } from 'vue'

export default {
  name: 'FinancialSummaryModal',
  props: {
    show: {
      type: Boolean,
      default: false
    }
  },
  setup() {
    const { proxy } = getCurrentInstance()
    const loading = ref(false)
    const summary = ref(null)

    const fetchFinancialSummary = async () => {
      loading.value = true
      try {
        const response = await fetch('/api/super-admin/reseller-transactions/financial/summary', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
          }
        })

        if (!response.ok) {
          throw new Error('Failed to fetch financial summary')
        }

        const data = await response.json()
        if (data.success) {
          summary.value = data.data
        } else {
          throw new Error(data.message || 'Failed to fetch financial summary')
        }
      } catch (error) {
        console.error('Error fetching financial summary:', error)
        if (proxy.$toast && proxy.$toast.error) {
          proxy.$toast.error('Error fetching financial summary')
        }
      } finally {
        loading.value = false
      }
    }

    const getStatusColor = (status) => {
      const colors = {
        'completed': 'bg-green-500',
        'pending': 'bg-yellow-500',
        'failed': 'bg-red-500',
        'refunded': 'bg-purple-500',
        'cancelled': 'bg-gray-500'
      }
      return colors[status] || 'bg-gray-500'
    }

    onMounted(() => {
      if (summary.value === null) {
        fetchFinancialSummary()
      }
    })

    return {
      loading,
      summary,
      getStatusColor
    }
  }
}
</script>

