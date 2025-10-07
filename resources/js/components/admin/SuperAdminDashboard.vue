<template>
  <SuperAdminLayout title="Super Admin Dashboard" subtitle="System overview and management">
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
      <!-- Total Resellers -->
      <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-blue-600">
              <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2m-2 0H7m2 0H5m2 0h2m2 0h2M9 7h6m-6 4h6m-6 4h6" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <div class="flex items-baseline">
              <p class="text-2xl font-semibold text-gray-900">{{ stats.totalResellers }}</p>
              <p class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                <svg class="h-4 w-4 flex-shrink-0 self-center" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
                <span class="sr-only">Increased by</span>
                {{ stats.resellersGrowth }}%
              </p>
            </div>
            <p class="text-sm font-medium text-gray-500">Total Resellers</p>
          </div>
        </div>
        <div class="absolute -bottom-2 -right-2 h-16 w-16 rounded-full bg-blue-500/10"></div>
      </div>

      <!-- Active Resellers -->
      <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-green-600">
              <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <div class="flex items-baseline">
              <p class="text-2xl font-semibold text-gray-900">{{ stats.activeResellers }}</p>
              <p class="ml-2 text-sm font-medium text-gray-500">
                ({{ Math.round((stats.activeResellers / stats.totalResellers) * 100) }}%)
              </p>
            </div>
            <p class="text-sm font-medium text-gray-500">Active Resellers</p>
          </div>
        </div>
        <div class="absolute -bottom-2 -right-2 h-16 w-16 rounded-full bg-green-500/10"></div>
      </div>

      <!-- Total Users -->
      <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-purple-600">
              <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <div class="flex items-baseline">
              <p class="text-2xl font-semibold text-gray-900">{{ stats.totalUsers }}</p>
              <p class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                <svg class="h-4 w-4 flex-shrink-0 self-center" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
                <span class="sr-only">Increased by</span>
                {{ stats.usersGrowth }}%
              </p>
            </div>
            <p class="text-sm font-medium text-gray-500">Total Users</p>
          </div>
        </div>
        <div class="absolute -bottom-2 -right-2 h-16 w-16 rounded-full bg-purple-500/10"></div>
      </div>

      <!-- System Health -->
      <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600">
              <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <div class="flex items-baseline">
              <p class="text-2xl font-semibold text-gray-900">{{ stats.systemHealth }}%</p>
              <div class="ml-2 flex items-center">
                <div class="h-2 w-2 rounded-full bg-green-500"></div>
                <span class="ml-1 text-sm font-medium text-green-600">Healthy</span>
              </div>
            </div>
            <p class="text-sm font-medium text-gray-500">System Health</p>
          </div>
        </div>
        <div class="absolute -bottom-2 -right-2 h-16 w-16 rounded-full bg-emerald-500/10"></div>
      </div>
    </div>

    <!-- Reseller Usage Overview -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-8">
      <!-- Usage Widget -->
      <div>
        <UsageWidget />
      </div>
      
      <!-- Usage History Chart -->
      <div>
        <UsageHistoryChart />
      </div>
    </div>

    <!-- Recent Activity & Quick Actions -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
      <!-- Recent Activity -->
      <div class="lg:col-span-2">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
            <button class="text-sm font-medium text-purple-600 hover:text-purple-700">View all</button>
          </div>
          <div class="flow-root">
            <ul class="-mb-8">
              <li v-for="(activity, index) in recentActivity" :key="activity.id">
                <div class="relative pb-8" :class="{ 'pb-0': index === recentActivity.length - 1 }">
                  <span v-if="index !== recentActivity.length - 1" class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200"></span>
                  <div class="relative flex space-x-3">
                    <div>
                      <span :class="[
                        'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white',
                        activity.type === 'reseller' ? 'bg-blue-500' :
                        activity.type === 'user' ? 'bg-purple-500' :
                        activity.type === 'system' ? 'bg-green-500' : 'bg-gray-500'
                      ]">
                        <component :is="getActivityIcon(activity.type)" class="h-4 w-4 text-white" />
                      </span>
                    </div>
                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                      <div>
                        <p class="text-sm text-gray-900">
                          {{ activity.description }}
                        </p>
                        <p class="text-xs text-gray-500">{{ activity.user }}</p>
                      </div>
                      <div class="whitespace-nowrap text-right text-sm text-gray-500">
                        {{ formatTime(activity.timestamp) }}
                      </div>
                    </div>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="space-y-6">
        <!-- System Status -->
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">System Status</h3>
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Database</span>
              <div class="flex items-center space-x-2">
                <div class="h-2 w-2 rounded-full bg-green-500"></div>
                <span class="text-sm font-medium text-green-600">Online</span>
              </div>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">API Services</span>
              <div class="flex items-center space-x-2">
                <div class="h-2 w-2 rounded-full bg-green-500"></div>
                <span class="text-sm font-medium text-green-600">Online</span>
              </div>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Storage</span>
              <div class="flex items-center space-x-2">
                <div class="h-2 w-2 rounded-full bg-yellow-500"></div>
                <span class="text-sm font-medium text-yellow-600">Warning</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
          <div class="space-y-3">
            <router-link
              to="/super-admin/resellers"
              class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:border-purple-300 hover:bg-purple-50 transition-colors group"
            >
              <div class="flex items-center space-x-3">
                <div class="h-8 w-8 rounded-lg bg-blue-100 flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                  <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2m-2 0H7m2 0H5m2 0h2m2 0h2M9 7h6m-6 4h6m-6 4h6" />
                  </svg>
                </div>
                <span class="text-sm font-medium text-gray-900">Manage Resellers</span>
              </div>
              <svg class="h-4 w-4 text-gray-400 group-hover:text-purple-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7" />
              </svg>
            </router-link>

            <router-link
              to="/super-admin/analytics"
              class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:border-purple-300 hover:bg-purple-50 transition-colors group"
            >
              <div class="flex items-center space-x-3">
                <div class="h-8 w-8 rounded-lg bg-green-100 flex items-center justify-center group-hover:bg-green-200 transition-colors">
                  <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                  </svg>
                </div>
                <span class="text-sm font-medium text-gray-900">View Analytics</span>
              </div>
              <svg class="h-4 w-4 text-gray-400 group-hover:text-purple-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7" />
              </svg>
            </router-link>

            <router-link
              to="/super-admin/usage-reports"
              class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:border-purple-300 hover:bg-purple-50 transition-colors group"
            >
              <div class="flex items-center space-x-3">
                <div class="h-8 w-8 rounded-lg bg-blue-100 flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                  <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                  </svg>
                </div>
                <span class="text-sm font-medium text-gray-900">Usage Reports</span>
              </div>
              <svg class="h-4 w-4 text-gray-400 group-hover:text-purple-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7" />
              </svg>
            </router-link>

            <router-link
              to="/super-admin/system-settings"
              class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:border-purple-300 hover:bg-purple-50 transition-colors group"
            >
              <div class="flex items-center space-x-3">
                <div class="h-8 w-8 rounded-lg bg-purple-100 flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                  <svg class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </div>
                <span class="text-sm font-medium text-gray-900">System Settings</span>
              </div>
              <svg class="h-4 w-4 text-gray-400 group-hover:text-purple-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7" />
              </svg>
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </SuperAdminLayout>
</template>

<script>
import { ref, onMounted, h } from 'vue'
import SuperAdminLayout from '../layouts/SuperAdminLayout.vue'
import UsageWidget from '../reseller/UsageWidget.vue';
import UsageHistoryChart from '../reseller/UsageHistoryChart.vue';


export default {
  name: 'SuperAdminDashboard',
  components: {
    SuperAdminLayout,
    UsageWidget,
    UsageHistoryChart
  },
  setup() {
    const stats = ref({
      totalResellers: 0,
      activeResellers: 0,
      totalUsers: 0,
      systemHealth: 98,
      resellersGrowth: 12,
      usersGrowth: 8
    })

    const recentActivity = ref([
      {
        id: 1,
        type: 'reseller',
        description: 'New reseller "Acme Corp" created',
        user: 'Super Admin',
        timestamp: new Date(Date.now() - 5 * 60 * 1000)
      },
      {
        id: 2,
        type: 'user',
        description: 'User permissions updated for john@example.com',
        user: 'Super Admin',
        timestamp: new Date(Date.now() - 15 * 60 * 1000)
      },
      {
        id: 3,
        type: 'system',
        description: 'System backup completed successfully',
        user: 'System',
        timestamp: new Date(Date.now() - 30 * 60 * 1000)
      },
      {
        id: 4,
        type: 'reseller',
        description: 'Reseller "TechStart Inc" status changed to inactive',
        user: 'Super Admin',
        timestamp: new Date(Date.now() - 45 * 60 * 1000)
      }
    ])

    const loadStats = async () => {
      try {
        // Load reseller stats
        const resellerResponse = await fetch('/api/admin/resellers', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
          }
        })
        const resellerData = await resellerResponse.json()
        if (resellerData.success) {
          stats.value.totalResellers = resellerData.data.total || 0
          // Count active resellers (assuming we can filter by status)
          const activeCount = resellerData.data.data?.filter(reseller => reseller.status === 'active').length || 0
          stats.value.activeResellers = activeCount
        }

        // Load user stats (you might need to create an endpoint for this)
        // For now, using mock data
        stats.value.totalUsers = 156
      } catch (error) {
        console.error('Error loading stats:', error)
      }
    }

    const getActivityIcon = (type) => {
      const icons = {
        reseller: () => h('svg', {
          fill: 'none',
          viewBox: '0 0 24 24',
          stroke: 'currentColor'
        }, [
          h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '1.5',
            d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2m-2 0H7m2 0H5m2 0h2m2 0h2M9 7h6m-6 4h6m-6 4h6'
          })
        ]),
        user: () => h('svg', {
          fill: 'none',
          viewBox: '0 0 24 24',
          stroke: 'currentColor'
        }, [
          h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '1.5',
            d: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'
          })
        ]),
        system: () => h('svg', {
          fill: 'none',
          viewBox: '0 0 24 24',
          stroke: 'currentColor'
        }, [
          h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '1.5',
            d: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'
          })
        ])
      }
      return icons[type] || icons.system
    }

    const formatTime = (timestamp) => {
      const now = new Date()
      const diff = now - timestamp
      const minutes = Math.floor(diff / 60000)
      const hours = Math.floor(diff / 3600000)
      const days = Math.floor(diff / 86400000)

      if (minutes < 60) {
        return `${minutes}m ago`
      } else if (hours < 24) {
        return `${hours}h ago`
      } else {
        return `${days}d ago`
      }
    }

    onMounted(() => {
      loadStats()
    })

    return {
      stats,
      recentActivity,
      getActivityIcon,
      formatTime
    }
  }
}
</script>
