<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Navigation -->
    <Navigation />

    <div class="flex-1 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900">Admin Call Logs</h1>
          <p class="mt-2 text-sm text-gray-600">
            Monitor and analyze all voice assistant call activity across the platform
          </p>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-8">
          <nav class="-mb-px flex space-x-8">
            <button
              @click="activeTab = 'history'"
              :class="[
                'py-2 px-1 border-b-2 font-medium text-sm',
                activeTab === 'history'
                  ? 'border-green-500 text-green-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              Call History
            </button>
            <button
              @click="activeTab = 'analytics'"
              :class="[
                'py-2 px-1 border-b-2 font-medium text-sm',
                activeTab === 'analytics'
                  ? 'border-green-500 text-green-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              Analytics
            </button>
          </nav>
        </div>

        <!-- Tab Content -->
        <div v-if="activeTab === 'history'">
          <AdminCallLogsList 
            :assistants="assistants"
            :loading="loading"
            :call-logs="callLogs"
            :pagination="pagination"
            :filters="filters"
            @update-filters="updateFilters"
            @load-call-logs="loadCallLogs"
          />
        </div>

        <div v-else-if="activeTab === 'analytics'">
          <AdminCallLogsStats 
            :stats="stats"
            :assistants="assistants"
            :loading="statsLoading"
            @update-filters="updateStatsFilters"
            @load-stats="loadStats"
          />
        </div>
      </div>
    </div>
    
    <!-- Footer -->
    <SimpleFooter />
  </div>
</template>

<script>
import Navigation from '../shared/Navigation.vue'
import SimpleFooter from '../shared/SimpleFooter.vue'
import AdminCallLogsList from './AdminCallLogsList.vue'
import AdminCallLogsStats from './AdminCallLogsStats.vue'
import axios from 'axios'

export default {
  name: 'AdminCallLogs',
  components: {
    Navigation,
    AdminCallLogsList,
    AdminCallLogsStats,
    SimpleFooter
  },
  data() {
    return {
      activeTab: 'history',
      loading: false,
      statsLoading: false,
      assistants: [],
      callLogs: [],
      stats: {},
      pagination: null,
      filters: {
        search: '',
        assistant_id: '',
        status: '',
        direction: '',
        start_date: '',
        end_date: '',
        phone_number: ''
      }
    }
  },
  methods: {
    async loadAssistants() {
      try {
        const response = await axios.get('/api/admin/assistants', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        this.assistants = response.data.data || []
      } catch (error) {
        // Handle error silently
      }
    },
    async loadCallLogs(page = 1) {
      this.loading = true
      try {
        const params = {
          page,
          ...this.filters
        }
        
        const response = await axios.get('/api/admin/call-logs', {
          params,
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        
        this.callLogs = response.data.data || []
        this.pagination = response.data.meta || null
      } catch (error) {
        if (error.response && error.response.status === 401) {
          this.$router.push('/login')
        }
      } finally {
        this.loading = false
      }
    },
    async loadStats() {
      this.statsLoading = true
      try {
        const params = {
          ...this.filters
        }
        
        console.log('Loading admin stats with params:', params)
        const response = await axios.get('/api/admin/call-logs/stats', {
          params,
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        
        console.log('Admin stats response:', response.data)
        this.stats = response.data.data || {}
      } catch (error) {
        console.error('Error loading admin stats:', error)
        if (error.response && error.response.status === 401) {
          this.$router.push('/login')
        }
      } finally {
        this.statsLoading = false
      }
    },
    updateFilters(newFilters) {
      this.filters = { ...this.filters, ...newFilters }
      this.loadCallLogs()
    },
    updateStatsFilters(newFilters) {
      this.filters = { ...this.filters, ...newFilters }
      this.loadStats()
    }
  },
  mounted() {
    this.loadAssistants()
    this.loadCallLogs()
    this.loadStats()
  }
}
</script> 