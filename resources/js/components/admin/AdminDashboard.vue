<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Navigation -->
    <Navigation />

    <div class="flex-1 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
          <p class="mt-2 text-sm text-gray-600">System-wide analytics and management</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                    <dd class="text-2xl font-bold text-gray-900">{{ stats.overview?.total_users || 0 }}</dd>
                    <dd class="text-sm text-gray-500">Active: {{ stats.overview?.active_users || 0 }}</dd>
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
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
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
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                    <dd class="text-2xl font-bold text-gray-900">${{ stats.overview?.total_revenue || 0 }}</dd>
                    <dd class="text-sm text-gray-500">This month: ${{ stats.overview?.revenue_this_month || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Performing Assistants -->
        <div class="bg-white shadow rounded-lg mb-8">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Top Performing Assistants</h3>
          </div>
          <div class="p-6">
            <div class="space-y-4">
              <div v-for="assistant in stats.top_assistants" :key="assistant.id" class="border rounded-lg p-4">
                <div class="flex justify-between items-center mb-2">
                  <h4 class="font-medium text-gray-900">{{ assistant.name }}</h4>
                  <span class="text-sm text-gray-500">{{ assistant.type }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <div class="text-sm text-gray-600">
                    {{ assistant.total_calls }} calls
                  </div>
                  <div class="text-sm text-gray-600">
                    {{ assistant.user_name }}
                  </div>
                </div>
                <div class="mt-2 text-xs text-gray-500">
                  {{ assistant.phone_number }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Custom Subscription Manager -->
        <CustomSubscriptionManager />
      </div>
    </div>

    <!-- Footer -->
    <SimpleFooter />
  </div>
</template>

<script>
import Navigation from '../shared/Navigation.vue'
import SimpleFooter from '../shared/SimpleFooter.vue'
import CustomSubscriptionManager from './CustomSubscriptionManager.vue'
import { updateDocumentTitle } from '../../utils/systemSettings.js'

export default {
  name: 'AdminDashboard',
  components: {
    Navigation,
    SimpleFooter,
    CustomSubscriptionManager
  },
  data() {
    return {
      stats: {
        overview: {},
        top_assistants: []
      }
    }
  },
  async mounted() {
    await this.loadStats();
    await updateDocumentTitle('Admin Dashboard');
  },
  methods: {
    async loadStats() {
      try {
        const response = await fetch('/api/admin/dashboard/stats', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        });
        
        if (response.ok) {
          const data = await response.json();
          this.stats = data.data;
        }
      } catch (error) {
        console.error('Error loading stats:', error);
      }
    }
  }
}
</script> 