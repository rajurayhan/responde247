<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-medium text-gray-900">
              Overage Report
            </h3>
            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-600"></div>
          </div>

          <!-- Overage Report Content -->
          <div v-else-if="report" class="space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
              <div class="bg-gradient-to-br from-red-50 to-rose-50 rounded-xl p-6 border border-red-200">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-lg bg-red-100 flex items-center justify-center">
                      <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-red-600">Total Overage</p>
                    <p class="text-2xl font-bold text-red-900">${{ parseFloat(report.total_overage_charges || 0).toFixed(2) }}</p>
                  </div>
                </div>
              </div>

              <div class="bg-gradient-to-br from-orange-50 to-amber-50 rounded-xl p-6 border border-orange-200">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-lg bg-orange-100 flex items-center justify-center">
                      <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-orange-600">Affected Resellers</p>
                    <p class="text-2xl font-bold text-orange-900">{{ report.affected_resellers || 0 }}</p>
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
                    <p class="text-sm font-medium text-yellow-600">Overage Minutes</p>
                    <p class="text-2xl font-bold text-yellow-900">{{ report.total_overage_minutes || 0 }}</p>
                  </div>
                </div>
              </div>

              <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl p-6 border border-purple-200">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-lg bg-purple-100 flex items-center justify-center">
                      <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-purple-600">Average Overage</p>
                    <p class="text-2xl font-bold text-purple-900">${{ parseFloat(report.average_overage || 0).toFixed(2) }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Overage by Reseller -->
            <div v-if="report.reseller_overages && report.reseller_overages.length > 0" class="bg-white border border-gray-200 rounded-xl p-6">
              <h4 class="text-lg font-medium text-gray-900 mb-4">Overage by Reseller</h4>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reseller</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Minutes Used</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Limit</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overage</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Charges</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="item in report.reseller_overages" :key="item.subscription_id" class="hover:bg-gray-50">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ item.reseller_name }}</div>
                        <div class="text-sm text-gray-500">{{ item.reseller_email }}</div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ item.package_name }}</div>
                        <div class="text-sm text-gray-500">${{ parseFloat(item.package_price).toFixed(2) }}/month</div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-medium text-gray-900">{{ item.minutes_used }}</span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900">{{ item.minutes_limit === -1 ? 'Unlimited' : item.minutes_limit }}</span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-medium text-red-600">{{ item.overage_minutes }}</span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-bold text-red-600">${{ parseFloat(item.overage_charges).toFixed(2) }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Monthly Overage Trend -->
            <div v-if="report.monthly_overages && report.monthly_overages.length > 0" class="bg-white border border-gray-200 rounded-xl p-6">
              <h4 class="text-lg font-medium text-gray-900 mb-4">Monthly Overage Trend</h4>
              <div class="space-y-4">
                <div v-for="month in report.monthly_overages" :key="month.month" class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <span class="text-sm font-medium text-gray-900">{{ month.month }}</span>
                  </div>
                  <div class="flex items-center space-x-4">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                      <div 
                        class="bg-red-500 h-2 rounded-full" 
                        :style="{ width: `${(month.overage_charges / Math.max(...report.monthly_overages.map(m => m.overage_charges))) * 100}%` }"
                      ></div>
                    </div>
                    <span class="text-sm font-medium text-gray-900 w-20 text-right">${{ parseFloat(month.overage_charges).toFixed(2) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Top Overage Packages -->
            <div v-if="report.package_overages && report.package_overages.length > 0" class="bg-white border border-gray-200 rounded-xl p-6">
              <h4 class="text-lg font-medium text-gray-900 mb-4">Packages with Most Overage</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="packageItem in report.package_overages" :key="packageItem.package_id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                      <span class="text-sm font-bold text-red-600">#{{ packageItem.rank }}</span>
                    </div>
                    <div>
                      <div class="text-sm font-medium text-gray-900">{{ packageItem.package_name }}</div>
                      <div class="text-xs text-gray-500">{{ packageItem.overage_count }} overages</div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-lg font-bold text-gray-900">${{ parseFloat(packageItem.total_overage).toFixed(2) }}</div>
                    <div class="text-sm text-gray-500">{{ packageItem.total_minutes }} min over</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Recommendations -->
            <div v-if="report.recommendations && report.recommendations.length > 0" class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
              <h4 class="text-lg font-medium text-gray-900 mb-4">Recommendations</h4>
              <div class="space-y-3">
                <div v-for="(recommendation, index) in report.recommendations" :key="index" class="flex items-start space-x-3">
                  <div class="flex-shrink-0">
                    <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center">
                      <svg class="w-3 h-3 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                      </svg>
                    </div>
                  </div>
                  <p class="text-sm text-gray-700">{{ recommendation }}</p>
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
            <h3 class="text-lg font-medium text-gray-900 mb-2">Error loading overage report</h3>
            <p class="text-gray-500">Unable to load overage report data.</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            @click="$emit('close')"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-orange-600 text-base font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 sm:ml-3 sm:w-auto sm:text-sm"
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
  name: 'OverageReportModal',
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
    const report = ref(null)

    const fetchOverageReport = async () => {
      loading.value = true
      try {
        const response = await fetch('/api/super-admin/reseller-subscriptions/usage/overage-report', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
          }
        })

        if (!response.ok) {
          throw new Error('Failed to fetch overage report')
        }

        const data = await response.json()
        if (data.success) {
          report.value = data.data
        } else {
          throw new Error(data.message || 'Failed to fetch overage report')
        }
      } catch (error) {
        console.error('Error fetching overage report:', error)
        if (proxy.$toast && proxy.$toast.error) {
          proxy.$toast.error('Error fetching overage report')
        }
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      if (report.value === null) {
        fetchOverageReport()
      }
    })

    return {
      loading,
      report
    }
  }
}
</script>
