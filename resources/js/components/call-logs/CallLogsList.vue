<template>
  <div>
    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Search -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <div class="relative">
            <input
              v-model="localFilters.search"
              type="text"
              placeholder="Search call ID, summary..."
              class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
              @input="debounceSearch"
            />
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
              <svg v-if="searchLoading" class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
          </div>
        </div>

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
              {{ assistant.name }}
            </option>
          </select>
        </div>

        <!-- Status Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="localFilters.status"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
            @change="applyFilters"
          >
            <option value="">All Statuses</option>
            <option value="initiated">Initiated</option>
            <option value="ringing">Ringing</option>
            <option value="in-progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="failed">Failed</option>
            <option value="busy">Busy</option>
            <option value="no-answer">No Answer</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <!-- Direction Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Direction</label>
          <select
            v-model="localFilters.direction"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
            @change="applyFilters"
          >
            <option value="">All Directions</option>
            <option value="inbound">Inbound</option>
            <option value="outbound">Outbound</option>
          </select>
        </div>
      </div>

      <!-- Date Range -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
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

      <!-- Clear Filters -->
      <div class="mt-4 flex justify-between items-center">
        <div class="text-sm text-gray-500">
          {{ pagination ? `Total: ${pagination.total} calls` : '' }}
        </div>
        <button
          @click="clearFilters"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md"
        >
          Clear Filters
        </button>
      </div>
    </div>

    <!-- Call Logs Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Call History</h3>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="p-6 text-center">
        <div class="inline-flex items-center">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Loading call logs...
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="callLogs.length === 0" class="p-6 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No call logs found</h3>
        <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or check back later.</p>
      </div>

      <!-- Call Logs Table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Call ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assistant</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone Numbers</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
              <th v-if="isAdmin" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr 
              v-for="callLog in callLogs" 
              :key="callLog.id" 
              class="hover:bg-gray-50 cursor-pointer transition-colors duration-150"
              @click="viewCallDetails(callLog)"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ callLog.call_id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ getAssistantName(callLog.assistant_id) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <div>
                  <div class="text-xs text-gray-400">Assistant: {{ callLog.phone_number || 'N/A' }}</div>
                  <div class="text-xs text-gray-400">Caller: {{ callLog.caller_number || 'N/A' }}</div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(callLog.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                  {{ callLog.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDuration(callLog.duration) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(callLog.start_time) }}
              </td>
              <td v-if="isAdmin" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatCost(callLog.cost, callLog.currency) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button
                  @click.stop="viewCallDetails(callLog)"
                  class="text-green-600 hover:text-green-900"
                >
                  View Details
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination && pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              :class="[
                'px-3 py-1 text-sm rounded-md',
                pagination.current_page === 1
                  ? 'text-gray-400 cursor-not-allowed'
                  : 'text-gray-700 hover:bg-gray-100'
              ]"
            >
              Previous
            </button>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              :class="[
                'px-3 py-1 text-sm rounded-md',
                pagination.current_page === pagination.last_page
                  ? 'text-gray-400 cursor-not-allowed'
                  : 'text-gray-700 hover:bg-gray-100'
              ]"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CallLogsList',
  props: {
    assistants: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    },
    callLogs: {
      type: Array,
      default: () => []
    },
    pagination: {
      type: Object,
      default: null
    },
    filters: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      localFilters: {
        page: 1,
        status: '',
        direction: '',
        assistant_id: '',
        start_date: '',
        end_date: '',
        search: ''
      },

      searchTimeout: null,
      searchLoading: false,
      isAdmin: false
    }
  },
  watch: {
    filters: {
      handler(newFilters) {
        this.localFilters = { ...newFilters }
      },
      immediate: true
    }
  },
  mounted() {
    this.checkAdminStatus()
  },
  methods: {
    getAssistantName(assistantId) {
      const assistant = this.assistants.find(a => a.id === assistantId)
      return assistant ? assistant.name : 'Unknown Assistant'
    },

    getStatusBadgeClass(status) {
      const classes = {
        'initiated': 'bg-blue-100 text-blue-800',
        'ringing': 'bg-yellow-100 text-yellow-800',
        'in-progress': 'bg-green-100 text-green-800',
        'completed': 'bg-green-100 text-green-800',
        'failed': 'bg-red-100 text-red-800',
        'busy': 'bg-gray-100 text-gray-800',
        'no-answer': 'bg-gray-100 text-gray-800',
        'cancelled': 'bg-gray-100 text-gray-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    },

    formatDuration(seconds) {
      if (!seconds) return 'N/A'
      const minutes = Math.floor(seconds / 60)
      const remainingSeconds = seconds % 60
      return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString()
    },

    formatCost(cost, currency = 'USD') {
      if (!cost) return 'N/A'
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
      }).format(cost)
    },

    debounceSearch() {
      clearTimeout(this.searchTimeout)
      this.searchLoading = true
      
      this.searchTimeout = setTimeout(() => {
        this.searchLoading = false
        this.applyFilters()
      }, 500)
    },

    applyFilters() {
      this.$emit('update-filters', this.localFilters)
    },

    clearFilters() {
      this.localFilters = {
        page: 1,
        status: '',
        direction: '',
        assistant_id: '',
        start_date: '',
        end_date: '',
        search: ''
      }
      this.applyFilters()
    },

    changePage(page) {
      this.localFilters.page = page
      this.applyFilters()
    },

    viewCallDetails(callLog) {
      this.$router.push(`/call-logs/${callLog.call_id}`)
    },

    checkAdminStatus() {
      const user = JSON.parse(localStorage.getItem('user') || '{}')
      this.isAdmin = user.role === 'admin' || user.role === 'reseller_admin'
    }
  }
}
</script> 