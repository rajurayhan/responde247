<template>
  <div class="md:hidden">
    <!-- Mobile menu button -->
    <button
      @click="mobileMenuOpen = !mobileMenuOpen"
      class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-green-500"
      aria-expanded="false"
    >
      <span class="sr-only">Open main menu</span>
      <!-- Icon when menu is closed -->
      <svg
        v-if="!mobileMenuOpen"
        class="block h-6 w-6"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
      <!-- Icon when menu is open -->
      <svg
        v-else
        class="block h-6 w-6"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>

    <!-- Mobile menu -->
    <div
      v-if="mobileMenuOpen"
      class="absolute top-16 left-0 right-0 bg-white shadow-lg border-b border-gray-200 z-50"
    >
      <div class="px-2 pt-2 pb-3 space-y-1">
        <!-- Dashboard Navigation (for authenticated users) -->
        <template v-if="isAuthenticated">
          <router-link
            to="/dashboard"
            class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
            @click="mobileMenuOpen = false"
          >
            Dashboard
          </router-link>
          
          <router-link
            v-if="!isAdmin"
            to="/assistants"
            class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
            @click="mobileMenuOpen = false"
          >
            My Assistants
          </router-link>
          
                      <router-link
              v-if="!isAdmin"
              to="/demo-request"
              class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
              @click="mobileMenuOpen = false"
            >
              Request Demo
            </router-link>
            
            <router-link
              v-if="!isAdmin"
              to="/call-logs"
              class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
              @click="mobileMenuOpen = false"
            >
              Call Logs
            </router-link>
          
          <router-link
            v-if="!isAdmin"
            :to="user.role === 'reseller_admin' ? '/reseller-billing' : '/subscription'"
            class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
            @click="mobileMenuOpen = false"
          >
            Subscription
          </router-link>
          
          <router-link
            v-if="!isAdmin"
            to="/transactions"
            class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
            @click="mobileMenuOpen = false"
          >
            Transactions
          </router-link>
          
          <!-- Admin Navigation -->
          <template v-if="isAdmin">
            <div class="border-t border-gray-200 pt-4 mt-4">
              <h3 class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Admin Panel
              </h3>
              
              <router-link
                to="/admin/users"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                Users
              </router-link>
              
              <router-link
                to="/admin/assistants"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                All Assistants
              </router-link>
              
              <router-link
                to="/admin/demo-requests"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                Demo Requests
              </router-link>
              
              <router-link
                to="/admin/call-logs"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                Call Logs
              </router-link>
              
              <router-link
                to="/admin/system-settings"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                System Settings
              </router-link>
              
              <!-- Admin Config Submenu -->
              <div class="border-t border-gray-200 pt-4 mt-4">
                <h3 class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Configuration
                </h3>
                
                <router-link
                  to="/admin/features"
                  class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                  @click="mobileMenuOpen = false"
                >
                  Features
                </router-link>
                
                <router-link
                  to="/admin/packages"
                  class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                  @click="mobileMenuOpen = false"
                >
                  Packages
                </router-link>
                
                <router-link
                  to="/admin/subscriptions"
                  class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                  @click="mobileMenuOpen = false"
                >
                  Subscriptions
                </router-link>
                
                <router-link
                  to="/admin/transactions"
                  class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                  @click="mobileMenuOpen = false"
                >
                  Transactions
                </router-link>
                
                <router-link
                  to="/admin/templates"
                  class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                  @click="mobileMenuOpen = false"
                >
                  Templates
                </router-link>
              </div>
            </div>
          </template>
          
          <!-- User Profile Section -->
          <div class="border-t border-gray-200 pt-4 mt-4">
            <div class="px-3 py-2">
              <div class="flex items-center">
                <div v-if="user.profile_picture" class="h-8 w-8 rounded-full overflow-hidden">
                  <img :src="user.profile_picture" :alt="user.name" class="h-full w-full object-cover">
                </div>
                <div v-else class="h-8 w-8 rounded-full bg-green-600 flex items-center justify-center">
                  <span class="text-white font-medium text-sm">{{ userInitials }}</span>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                  <p class="text-xs text-gray-500">{{ user.email }}</p>
                </div>
              </div>
            </div>
            
            <router-link
              to="/profile"
              class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
              @click="mobileMenuOpen = false"
            >
              Your Profile
            </router-link>
            
            <router-link
              v-if="!isAdmin"
              to="/pricing"
              class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
              @click="mobileMenuOpen = false"
            >
              Pricing
            </router-link>
            
            <button
              @click="logout"
              class="block w-full text-left px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
            >
              Sign out
            </button>
          </div>
        </template>
        
        <!-- Public Navigation (for non-authenticated users) -->
        <template v-else>
          <a
            href="#features"
            class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
            @click="mobileMenuOpen = false"
          >
            Features
          </a>
          
          <a
            href="#pricing"
            class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
            @click="mobileMenuOpen = false"
          >
            Pricing
          </a>
          
          <a
            href="#contact"
            class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
            @click="mobileMenuOpen = false"
          >
            Contact
          </a>
          
          <div class="border-t border-gray-200 pt-4 mt-4">
            <router-link
              to="/login"
              class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
              @click="mobileMenuOpen = false"
            >
              Login
            </router-link>
            
            <router-link
              to="/register"
              class="block px-3 py-2 rounded-md text-base font-medium text-white bg-green-600 hover:bg-green-700 rounded-md"
              @click="mobileMenuOpen = false"
            >
              Get Started
            </router-link>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'MobileNavigation',
  data() {
    return {
      mobileMenuOpen: false,
      user: JSON.parse(localStorage.getItem('user') || '{}')
    }
  },
  computed: {
    userInitials() {
      return this.user.name ? this.user.name.split(' ').map(n => n[0]).join('').toUpperCase() : 'U';
    },
    isAdmin() {
      return this.user.role === 'admin' || this.user.role === 'reseller_admin';
    },
    isAuthenticated() {
      return !!localStorage.getItem('token');
    }
  },
  methods: {
    async logout() {
      try {
        // Call logout API
        const response = await fetch('/api/logout', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        });
        
        // Clear local storage
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('settings');
        
        // Close mobile menu
        this.mobileMenuOpen = false;
        
        // Redirect to login
        this.$router.push('/login');
      } catch (error) {
        // Even if API call fails, clear local storage and redirect
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('settings');
        this.mobileMenuOpen = false;
        this.$router.push('/login');
      }
    }
  },
  mounted() {
    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
      if (!this.$el.contains(e.target)) {
        this.mobileMenuOpen = false;
      }
    });
    
    // Close mobile menu on route change
    this.$router.afterEach(() => {
      this.mobileMenuOpen = false;
    });
  }
}
</script> 