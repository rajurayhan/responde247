<template>
  <div :class="[
    'flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white px-6',
    mobile ? 'pb-4' : 'pb-4 lg:fixed lg:inset-y-0 lg:z-50 lg:w-64'
  ]">
    <!-- Logo -->
    <div class="flex h-16 shrink-0 items-center">
      <div class="flex items-center space-x-3">
        <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-purple-600 to-indigo-700 flex items-center justify-center shadow-lg">
          <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
          </svg>
        </div>
        <div>
          <h1 class="text-lg font-bold text-gray-900">Super Admin</h1>
          <p class="text-xs text-gray-600">System Control</p>
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex flex-1 flex-col">
      <ul role="list" class="flex flex-1 flex-col gap-y-7">
        <li>
          <ul role="list" class="-mx-2 space-y-1">
            <!-- Dashboard -->
            <li>
              <router-link
                to="/super-admin"
                :class="[
                  $route.path === '/super-admin'
                    ? 'bg-purple-50 text-purple-700 border-r-2 border-purple-600'
                    : 'text-gray-700 hover:text-purple-700 hover:bg-purple-50',
                  'group flex gap-x-3 rounded-md p-3 text-sm leading-6 font-medium transition-colors'
                ]"
              >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
                Dashboard
              </router-link>
            </li>

            <!-- Reseller Management -->
            <li>
              <div class="text-xs font-semibold leading-6 text-gray-400 uppercase tracking-wider">
                Reseller Management
              </div>
              <ul role="list" class="-mx-2 mt-2 space-y-1">
                <li>
                  <router-link
                    to="/super-admin/resellers"
                    :class="[
                      $route.path.startsWith('/super-admin/resellers') && !$route.path.includes('packages') && !$route.path.includes('subscriptions')
                        ? 'bg-purple-50 text-purple-700 border-r-2 border-purple-600'
                        : 'text-gray-700 hover:text-purple-700 hover:bg-purple-50',
                      'group flex gap-x-3 rounded-md p-3 text-sm leading-6 font-medium transition-colors'
                    ]"
                  >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2m-2 0H7m2 0H5m2 0h2m2 0h2M9 7h6m-6 4h6m-6 4h6" />
                    </svg>
                    Resellers
                    <span v-if="resellersCount" class="ml-auto w-5 h-5 rounded-full bg-purple-100 text-purple-700 text-xs flex items-center justify-center font-medium">
                      {{ resellersCount }}
                    </span>
                  </router-link>
                </li>
                <li>
                  <router-link
                    to="/super-admin/reseller-packages"
                    :class="[
                      $route.path.startsWith('/super-admin/reseller-packages')
                        ? 'bg-purple-50 text-purple-700 border-r-2 border-purple-600'
                        : 'text-gray-700 hover:text-purple-700 hover:bg-purple-50',
                      'group flex gap-x-3 rounded-md p-3 text-sm leading-6 font-medium transition-colors'
                    ]"
                  >
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                    </svg>
                    Packages
                  </router-link>
                </li>
        <li>
          <router-link
            to="/super-admin/reseller-subscriptions"
            :class="[
              $route.path.startsWith('/super-admin/reseller-subscriptions')
                ? 'bg-purple-50 text-purple-700 border-r-2 border-purple-600'
                : 'text-gray-700 hover:text-purple-700 hover:bg-purple-50',
              'group flex gap-x-3 rounded-md p-3 text-sm leading-6 font-medium transition-colors'
            ]"
          >
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
            </svg>
            Subscriptions
          </router-link>
        </li>
        <li>
          <router-link
            to="/super-admin/reseller-transactions"
            :class="[
              $route.path.startsWith('/super-admin/reseller-transactions')
                ? 'bg-purple-50 text-purple-700 border-r-2 border-purple-600'
                : 'text-gray-700 hover:text-purple-700 hover:bg-purple-50',
              'group flex gap-x-3 rounded-md p-3 text-sm leading-6 font-medium transition-colors'
            ]"
          >
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
            </svg>
            Transactions
          </router-link>
        </li>
              </ul>
            </li>

            <!-- Usage Reports -->
            <li>
              <router-link
                to="/super-admin/usage-reports"
                :class="[
                  $route.path.startsWith('/super-admin/usage-reports')
                    ? 'bg-purple-50 text-purple-700 border-r-2 border-purple-600'
                    : 'text-gray-700 hover:text-purple-700 hover:bg-purple-50',
                  'group flex gap-x-3 rounded-md p-3 text-sm leading-6 font-medium transition-colors'
                ]"
              >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Usage Reports
              </router-link>
            </li>

          </ul>
        </li>


        <!-- Quick Actions -->
        <li class="mt-auto">
          <div class="text-xs font-semibold leading-6 text-gray-400 uppercase tracking-wider mb-2">
            Quick Actions
          </div>
          
          <!-- Back to Admin -->
          <router-link
            to="/admin"
            class="group flex gap-x-3 rounded-md p-3 text-sm leading-6 font-medium text-gray-700 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
          >
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
            </svg>
            Back to Admin
          </router-link>

          <!-- System Status -->
          <div class="group flex gap-x-3 rounded-md p-3 text-sm leading-6 font-medium">
            <div class="flex items-center space-x-2">
              <div class="h-2 w-2 rounded-full bg-green-500"></div>
              <span class="text-gray-600">System Online</span>
            </div>
          </div>
        </li>
      </ul>
    </nav>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'

export default {
  name: 'SuperAdminNavigation',
  props: {
    mobile: {
      type: Boolean,
      default: false
    }
  },
  setup() {
    const resellersCount = ref(0)

    // Load reseller count for badge
    const loadClientCount = async () => {
      try {
        const response = await fetch('/api/admin/resellers', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
          }
        })
        const data = await response.json()
        if (data.success && data.data.total) {
          resellersCount.value = data.data.total
        }
      } catch (error) {
        console.error('Error loading reseller count:', error)
      }
    }

    onMounted(() => {
      loadClientCount()
    })

    return {
      resellersCount
    }
  }
}
</script>
