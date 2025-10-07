<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Navigation -->
    <Navigation />

    <div class="flex-1 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
          <p class="mt-2 text-sm text-gray-600">Monitor your voice agents and call analytics</p>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-8">
          <div class="text-lg text-gray-600">Loading dashboard...</div>
        </div>

        <!-- Error State -->
        <div v-if="error" class="text-center py-8">
          <div class="text-lg text-red-600">Error: {{ error }}</div>
        </div>


        <!-- Stats Cards -->
        <div v-if="!loading && !error" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Active Voice Agents</dt>
                    <dd class="text-2xl font-bold text-gray-900">{{ stats.overview?.total_assistants || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Calls</dt>
                    <dd class="text-2xl font-bold text-gray-900">{{ stats.overview?.total_calls || 0 }}</dd>
                    <dd class="text-sm text-gray-500">Today: {{ stats.overview?.calls_today || 0 }}</dd>
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
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Minutes</dt>
                    <dd class="text-2xl font-bold text-gray-900">{{ stats.overview?.total_minutes || 0 }}</dd>
                    <dd class="text-sm text-gray-500">This month: {{ stats.overview?.this_month_minutes || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Avg Duration</dt>
                    <dd class="text-2xl font-bold text-gray-900">{{ stats.overview?.avg_duration || 0 }}s</dd>
                    <dd class="text-sm text-gray-500">Per call average</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Latest Calls -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Latest Calls (Last 10)</h3>
          </div>
          <div class="p-6">
            <div class="space-y-4">
              <router-link 
                v-for="call in stats.recent_calls" 
                :key="call.id" 
                :to="`/call-logs/${call.call_id}`"
                class="block border rounded-lg p-4 hover:bg-gray-50 transition-colors cursor-pointer"
              >
                <div class="flex justify-between items-start">
                  <div class="flex-1">
                    <div class="flex items-center space-x-2">
                      <h4 class="font-medium text-gray-900">{{ call.phone_number }}</h4>
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getStatusClass(call.status)">
                        {{ call.status }}
                      </span>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">{{ call.assistant_name }}</p>
                    <div class="mt-2 text-xs text-gray-500">
                      <span>{{ formatDuration(call.duration) }}</span>
                    </div>
                  </div>
                  <div class="text-right text-xs text-gray-500">
                    {{ formatTime(call.start_time) }}
                  </div>
                </div>
              </router-link>
              <div v-if="stats.recent_calls && stats.recent_calls.length === 0" class="text-center py-8 text-gray-500">
                No recent calls found
              </div>
            </div>
          </div>
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
import { updateDocumentTitle } from '../../utils/systemSettings.js'

export default {
  name: 'Dashboard',
  components: {
    Navigation,
    SimpleFooter
  },
  data() {
    return {
      stats: {
        overview: {},
        recent_calls: []
      },
      loading: true,
      error: null
    }
  },
  async mounted() {
    console.log('Dashboard mounted, loading stats...');
    try {
      await this.loadStats();
      this.loading = false;
    } catch (error) {
      console.error('Error in mounted:', error);
      this.error = error.message;
      this.loading = false;
    }
    updateDocumentTitle('Dashboard');
  },
  methods: {
    async loadStats() {
      try {
        const token = localStorage.getItem('token');
        if (!token) {
          console.warn('No authentication token found');
          this.stats = { overview: {}, recent_calls: [] };
          return;
        }

        console.log('Loading stats with token:', token.substring(0, 10) + '...');
        const response = await fetch('/api/dashboard/stats', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        });
        
        console.log('API response status:', response.status);
        if (response.ok) {
          const data = await response.json();
          console.log('API data received:', data);
          this.stats = data.data || { overview: {}, recent_calls: [] };
          console.log('Stats set to:', this.stats);
        } else {
          console.error('API response not ok:', response.status, response.statusText);
          this.stats = { overview: {}, recent_calls: [] };
        }
      } catch (error) {
        console.error('Error loading stats:', error);
        this.stats = { overview: {}, recent_calls: [] };
      }
    },
    
    formatTime(time) {
      return new Date(time).toLocaleString();
    },
    
    formatDuration(seconds) {
      if (!seconds) return '0s';
      const minutes = Math.floor(seconds / 60);
      const remainingSeconds = seconds % 60;
      return minutes > 0 ? `${minutes}m ${remainingSeconds}s` : `${remainingSeconds}s`;
    },
    
    getStatusClass(status) {
      const classes = {
        completed: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
        'in-progress': 'bg-yellow-100 text-yellow-800'
      };
      return classes[status] || 'bg-gray-100 text-gray-800';
    }
  }
}
</script> 