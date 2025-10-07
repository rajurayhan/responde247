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
              Usage Statistics
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

          <!-- Usage Statistics Content -->
          <div v-else-if="statistics" class="space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
              <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
                      <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-blue-600">Total Minutes</p>
                    <p class="text-2xl font-bold text-blue-900">{{ statistics.total_minutes || 0 }}</p>
                  </div>
                </div>
              </div>

              <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center">
                      <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-green-600">Active Subscriptions</p>
                    <p class="text-2xl font-bold text-green-900">{{ statistics.active_subscriptions || 0 }}</p>
                  </div>
                </div>
              </div>

              <div class="bg-gradient-to-br from-yellow-50 to-amber-50 rounded-xl p-6 border border-yellow-200">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-lg bg-yellow-100 flex items-center justify-center">
                      <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-yellow-600">Overage Charges</p>
                    <p class="text-2xl font-bold text-yellow-900">${{ parseFloat(statistics.total_overage_charges || 0).toFixed(2) }}</p>
                  </div>
                </div>
              </div>

              <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl p-6 border border-purple-200">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-lg bg-purple-100 flex items-center justify-center">
                      <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-purple-600">Average Usage</p>
                    <p class="text-2xl font-bold text-purple-900">{{ statistics.average_usage || 0 }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Usage by Reseller -->
            <div v-if="statistics.reseller_usage && statistics.reseller_usage.length > 0" class="bg-white border border-gray-200 rounded-xl p-6">
              <h4 class="text-lg font-medium text-gray-900 mb-4">Usage by Reseller</h4>
              <div class="space-y-4">
                <div v-for="reseller in statistics.reseller_usage" :key="reseller.reseller_id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                      <span class="text-sm font-bold text-blue-600">{{ reseller.reseller_name.charAt(0) }}</span>
                    </div>
                    <div>
                      <div class="text-sm font-medium text-gray-900">{{ reseller.reseller_name }}</div>
                      <div class="text-xs text-gray-500">{{ reseller.subscription_count }} subscriptions</div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-lg font-bold text-gray-900">{{ reseller.total_minutes }} min</div>
                    <div class="text-sm text-gray-500">${{ parseFloat(reseller.overage_charges).toFixed(2) }} overage</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Monthly Usage Chart -->
            <div v-if="statistics.monthly_usage && statistics.monthly_usage.length > 0" class="bg-white border border-gray-200 rounded-xl p-6">
              <h4 class="text-lg font-medium text-gray-900 mb-4">Monthly Usage Trend</h4>
              <div class="space-y-4">
                <div v-for="month in statistics.monthly_usage" :key="month.month" class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                    <span class="text-sm font-medium text-gray-900">{{ month.month }}</span>
                  </div>
                  <div class="flex items-center space-x-4">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                      <div 
                        class="bg-blue-500 h-2 rounded-full" 
                        :style="{ width: `${(month.minutes / Math.max(...statistics.monthly_usage.map(m => m.minutes))) * 100}%` }"
                      ></div>
                    </div>
                    <span class="text-sm font-medium text-gray-900 w-20 text-right">{{ month.minutes }} min</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Top Packages -->
            <div v-if="statistics.package_usage && statistics.package_usage.length > 0" class="bg-white border border-gray-200 rounded-xl p-6">
              <h4 class="text-lg font-medium text-gray-900 mb-4">Most Used Packages</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="packageItem in statistics.package_usage" :key="packageItem.package_id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                      <span class="text-sm font-bold text-purple-600">#{{ packageItem.rank }}</span>
                    </div>
                    <div>
                      <div class="text-sm font-medium text-gray-900">{{ packageItem.package_name }}</div>
                      <div class="text-xs text-gray-500">{{ packageItem.subscription_count }} subscriptions</div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-lg font-bold text-gray-900">{{ packageItem.total_minutes }} min</div>
                    <div class="text-sm text-gray-500">${{ parseFloat(packageItem.revenue).toFixed(2) }} revenue</div>
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
            <h3 class="text-lg font-medium text-gray-900 mb-2">Error loading statistics</h3>
            <p class="text-gray-500">Unable to load usage statistics data.</p>
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
  name: 'UsageStatisticsModal',
  props: {
    show: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close'],
  setup() {
    const { proxy } = getCurrentInstance()
    const loading = ref(false)
    const statistics = ref(null)

    const fetchUsageStatistics = async () => {
      loading.value = true
      try {
        const response = await fetch('/api/super-admin/reseller-subscriptions/usage/statistics', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
          }
        })

        if (!response.ok) {
          throw new Error('Failed to fetch usage statistics')
        }

        const data = await response.json()
        if (data.success) {
          statistics.value = data.data
        } else {
          throw new Error(data.message || 'Failed to fetch usage statistics')
        }
      } catch (error) {
        console.error('Error fetching usage statistics:', error)
        if (proxy.$toast && proxy.$toast.error) {
          proxy.$toast.error('Error fetching usage statistics')
        }
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      if (statistics.value === null) {
        fetchUsageStatistics()
      }
    })

    return {
      loading,
      statistics
    }
  }
}
</script>
