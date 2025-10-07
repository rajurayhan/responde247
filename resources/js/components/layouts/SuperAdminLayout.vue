<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-gray-100">
    <!-- Super Admin Navigation -->
    <SuperAdminNavigation />

    <!-- Main Content Area -->
    <div class="lg:pl-64">
      <!-- Top Bar -->
      <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
        <!-- Mobile menu button -->
        <button 
          type="button" 
          class="-m-2.5 p-2.5 text-gray-700 lg:hidden" 
          @click="sidebarOpen = true"
        >
          <span class="sr-only">Open sidebar</span>
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
          </svg>
        </button>

        <!-- Separator -->
        <div class="h-6 w-px bg-gray-900/10 lg:hidden" aria-hidden="true" />

        <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
          <!-- Page Title Area -->
          <div class="flex items-center gap-x-4 lg:gap-x-6">
            <div class="flex items-center space-x-3">
              <div class="flex-shrink-0">
                <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-purple-600 to-indigo-700 flex items-center justify-center">
                  <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </div>
              </div>
              <div>
                <h1 class="text-lg font-semibold text-gray-900">{{ pageTitle }}</h1>
                <p v-if="pageSubtitle" class="text-sm text-gray-600">{{ pageSubtitle }}</p>
              </div>
            </div>
          </div>

          <div class="flex flex-1 justify-end items-center gap-x-4 lg:gap-x-6">
            <!-- Search (if needed) -->
            <div class="relative hidden lg:block">
              <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input 
                type="search" 
                placeholder="Quick search..." 
                class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              />
            </div>

            <!-- Notifications -->
            <button type="button" class="relative rounded-full bg-white p-1 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
              <span class="sr-only">View notifications</span>
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
              </svg>
              <!-- Notification badge -->
              <span class="absolute -top-1 -right-1 h-4 w-4 rounded-full bg-red-500 flex items-center justify-center">
                <span class="text-xs font-medium text-white">3</span>
              </span>
            </button>

            <!-- Profile dropdown -->
            <div class="relative">
              <button 
                type="button" 
                class="relative flex rounded-full bg-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2" 
                @click="profileOpen = !profileOpen"
              >
                <span class="sr-only">Open user menu</span>
                <div class="h-8 w-8 rounded-full bg-gradient-to-br from-purple-600 to-indigo-700 flex items-center justify-center">
                  <span class="text-sm font-medium text-white">SA</span>
                </div>
              </button>

              <!-- Profile dropdown menu -->
              <div v-if="profileOpen" class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                <a href="#" @click="logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Page Content -->
      <main class="py-8">
        <div class="px-4 sm:px-6 lg:px-8">
          <slot></slot>
        </div>
      </main>
    </div>

    <!-- Mobile sidebar overlay -->
    <div v-if="sidebarOpen" class="relative z-50 lg:hidden">
      <div class="fixed inset-0 bg-gray-900/80" @click="sidebarOpen = false"></div>
      <div class="fixed inset-0 flex">
        <div class="relative mr-16 flex w-full max-w-xs flex-1">
          <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
            <button type="button" class="-m-2.5 p-2.5" @click="sidebarOpen = false">
              <span class="sr-only">Close sidebar</span>
              <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <SuperAdminNavigation :mobile="true" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import SuperAdminNavigation from './SuperAdminNavigation.vue'

export default {
  name: 'SuperAdminLayout',
  components: {
    SuperAdminNavigation
  },
  props: {
    title: {
      type: String,
      default: 'Super Admin'
    },
    subtitle: {
      type: String,
      default: null
    }
  },
  setup(props) {
    const route = useRoute()
    const router = useRouter()
    const sidebarOpen = ref(false)
    const profileOpen = ref(false)

    const pageTitle = computed(() => {
      return props.title || getPageTitle(route.name)
    })

    const pageSubtitle = computed(() => {
      return props.subtitle || getPageSubtitle(route.name)
    })

    const getPageTitle = (routeName) => {
      const titles = {
        'super-admin': 'Super Admin Dashboard',
        'super-admin-resellers': 'Reseller Management',
        'super-admin-reseller-details': 'Reseller Details',
        'super-admin-reseller-packages': 'Reseller Packages',
        'super-admin-reseller-subscriptions': 'Reseller Subscriptions',
        'super-admin-reseller-transactions': 'Reseller Transactions',
        'super-admin-usage-reports': 'Usage Reports'
      }
      return titles[routeName] || 'Super Admin'
    }

    const getPageSubtitle = (routeName) => {
      const subtitles = {
        'super-admin': 'System overview and management',
        'super-admin-resellers': 'Manage reseller organizations and their status',
        'super-admin-reseller-details': 'Detailed reseller information and analytics',
        'super-admin-reseller-packages': 'Manage reseller subscription packages',
        'super-admin-reseller-subscriptions': 'Monitor reseller subscription status',
        'super-admin-reseller-transactions': 'Track reseller billing and transactions',
        'super-admin-usage-reports': 'Comprehensive usage analytics and reporting'
      }
      return subtitles[routeName] || null
    }

    const logout = () => {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      router.push('/login')
    }

    // Close dropdowns when clicking outside
    const handleClickOutside = (event) => {
      if (profileOpen.value && !event.target.closest('.relative')) {
        profileOpen.value = false
      }
    }

    return {
      sidebarOpen,
      profileOpen,
      pageTitle,
      pageSubtitle,
      logout,
      handleClickOutside
    }
  }
}
</script>
