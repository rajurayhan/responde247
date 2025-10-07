<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Navigation -->
    <Navigation />

    <div class="flex-1 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
              Debug Information
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Debug information for troubleshooting
            </p>
          </div>
        </div>

        <!-- Debug Info -->
        <div class="mt-8">
          <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">User Information</h3>
            </div>
            <div class="px-6 py-4">
              <pre class="bg-gray-100 p-4 rounded-md text-sm overflow-auto">{{ debugInfo }}</pre>
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
import { ref, onMounted } from 'vue'
import Navigation from '../shared/Navigation.vue'
import SimpleFooter from '../shared/SimpleFooter.vue'

export default {
  name: 'DebugInfo',
  components: {
    Navigation,
    SimpleFooter
  },
  setup() {
    const debugInfo = ref('')

    const gatherDebugInfo = () => {
      const user = JSON.parse(localStorage.getItem('user') || '{}')
      const token = localStorage.getItem('token')
      
      const info = {
        user: user,
        hasToken: !!token,
        tokenLength: token ? token.length : 0,
        isAdmin: user.role === 'admin',
        isResellerAdmin: user.role === 'reseller_admin',
        isAuthenticated: !!token,
        currentRoute: window.location.pathname,
        timestamp: new Date().toISOString()
      }
      
      debugInfo.value = JSON.stringify(info, null, 2)
    }

    onMounted(() => {
      gatherDebugInfo()
    })

    return {
      debugInfo
    }
  }
}
</script>
