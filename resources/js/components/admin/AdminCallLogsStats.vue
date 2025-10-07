<template>
  <div>
    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Assistant Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Assistant</label>
          <select
            v-model="localFilters.assistant_id"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
            @change="applyFilters"
          >
            <option value="">All Assistants</option>
            <option
              v-for="assistant in assistants"
              :key="assistant.id"
              :value="assistant.id"
            >
              {{ assistant.name }} ({{ assistant.user?.name || 'Unknown User' }})
            </option>
          </select>
        </div>

        <!-- Date Range -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
          <input
            v-model="localFilters.start_date"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
            @change="applyFilters"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
          <input
            v-model="localFilters.end_date"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
            @change="applyFilters"
          />
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Total Calls -->
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Calls</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.totalCalls || 0 }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <!-- Completed Calls -->
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.completedCalls || 0 }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <!-- Failed Calls -->
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
                <dt class="text-sm font-medium text-gray-500 truncate">Failed</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.failedCalls || 0 }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <!-- Average Duration -->
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Avg Duration</dt>
                <dd class="text-lg font-medium text-gray-900">{{ formatDuration(stats.averageDuration) }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts and Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Status Breakdown -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Status Breakdown</h3>
        </div>
        <div class="p-6">
          <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto"></div>
            <p class="mt-2 text-sm text-gray-500">Loading statistics...</p>
          </div>
          <div v-else-if="stats.statusBreakdown" class="space-y-4">
            <div
              v-for="(count, status) in stats.statusBreakdown"
              :key="status"
              class="flex items-center justify-between"
            >
              <div class="flex items-center">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full mr-3',
                    getStatusClass(status)
                  ]"
                >
                  {{ status }}
                </span>
                <span class="text-sm text-gray-600">{{ count }} calls</span>
              </div>
              <span class="text-sm font-medium text-gray-900">
                {{ ((count / stats.totalCalls) * 100).toFixed(1) }}%
              </span>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            No status data available
          </div>
        </div>
      </div>

      <!-- Direction Breakdown -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Direction Breakdown</h3>
        </div>
        <div class="p-6">
          <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto"></div>
            <p class="mt-2 text-sm text-gray-500">Loading statistics...</p>
          </div>
          <div v-else-if="stats.directionBreakdown" class="space-y-4">
            <div
              v-for="(count, direction) in stats.directionBreakdown"
              :key="direction"
              class="flex items-center justify-between"
            >
              <div class="flex items-center">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full mr-3',
                    direction === 'inbound' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'
                  ]"
                >
                  {{ direction }}
                </span>
                <span class="text-sm text-gray-600">{{ count }} calls</span>
              </div>
              <span class="text-sm font-medium text-gray-900">
                {{ ((count / stats.totalCalls) * 100).toFixed(1) }}%
              </span>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            No direction data available
          </div>
        </div>
      </div>

      <!-- Top Assistants -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Top Performing Assistants</h3>
        </div>
        <div class="p-6">
          <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto"></div>
            <p class="mt-2 text-sm text-gray-500">Loading statistics...</p>
          </div>
          <div v-else-if="stats.topAssistants && stats.topAssistants.length > 0" class="space-y-4">
            <div
              v-for="assistant in stats.topAssistants"
              :key="assistant.id"
              class="flex items-center justify-between"
            >
              <div class="flex items-center">
                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                  <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                </div>
                <div>
                  <div class="text-sm font-medium text-gray-900">{{ assistant.name }}</div>
                  <div class="text-xs text-gray-500">{{ assistant.user?.name || 'Unknown User' }}</div>
                </div>
              </div>
              <div class="text-right">
                <div class="text-sm font-medium text-gray-900">{{ assistant.total_calls }} calls</div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            No assistant data available
          </div>
        </div>
      </div>

      <!-- Cost Analysis -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Cost Analysis</h3>
        </div>
        <div class="p-6">
          <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto"></div>
            <p class="mt-2 text-sm text-gray-500">Loading statistics...</p>
          </div>
          <div v-else-if="stats.costAnalysis" class="space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Total Cost</span>
              <span class="text-sm font-medium text-gray-900">${{ formatCost(stats.costAnalysis.total_cost) }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Average Cost per Call</span>
              <span class="text-sm font-medium text-gray-900">${{ formatCost(stats.costAnalysis.average_cost, 4) }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Highest Cost Call</span>
              <span class="text-sm font-medium text-gray-900">${{ formatCost(stats.costAnalysis.highest_cost) }}</span>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            No cost data available
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdminCallLogsStats',
  props: {
    stats: {
      type: Object,
      default: () => ({})
    },
    assistants: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    },
    filters: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      localFilters: { ...this.filters }
    }
  },
  mounted() {
    console.log('AdminCallLogsStats mounted with stats:', this.stats)
  },
  watch: {
    filters: {
      handler(newFilters) {
        this.localFilters = { ...newFilters }
      },
      deep: true
    },
    stats: {
      handler(newStats) {
        console.log('AdminCallLogsStats stats updated:', newStats)
        if (newStats.costAnalysis) {
          console.log('Cost analysis data:', newStats.costAnalysis)
          console.log('Total cost type:', typeof newStats.costAnalysis.total_cost)
          console.log('Total cost value:', newStats.costAnalysis.total_cost)
        }
      },
      deep: true
    }
  },
  methods: {
    applyFilters() {
      this.$emit('update-filters', this.localFilters)
    },
    formatDuration(seconds) {
      if (!seconds) return '0:00'
      const minutes = Math.floor(seconds / 60)
      const remainingSeconds = Math.floor(seconds % 60)
      return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
    },
    formatCost(cost, decimals = 2) {
      if (!cost || cost === null || cost === undefined) {
        return decimals === 4 ? '0.0000' : '0.00'
      }
      
      // Convert to number if it's a string
      const numCost = typeof cost === 'string' ? parseFloat(cost) : Number(cost)
      
      // Check if it's a valid number
      if (isNaN(numCost)) {
        return decimals === 4 ? '0.0000' : '0.00'
      }
      
      return numCost.toFixed(decimals)
    },
    getStatusClass(status) {
      const classes = {
        'initiated': 'bg-gray-100 text-gray-800',
        'ringing': 'bg-yellow-100 text-yellow-800',
        'in-progress': 'bg-blue-100 text-blue-800',
        'completed': 'bg-green-100 text-green-800',
        'failed': 'bg-red-100 text-red-800',
        'busy': 'bg-orange-100 text-orange-800',
        'no-answer': 'bg-red-100 text-red-800',
        'cancelled': 'bg-gray-100 text-gray-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }
  }
}
</script> 