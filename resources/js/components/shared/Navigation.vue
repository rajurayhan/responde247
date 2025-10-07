<template>
  <div>
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <router-link to="/" class="flex items-center hover:opacity-80 transition-opacity">
                <div v-if="branding.logoUrl" class="h-8 w-auto">
                  <img :src="branding.logoUrl" :alt="branding.appName" class="h-full w-auto">
                </div>
                <div v-else class="h-8 w-8 bg-gradient-to-r from-primary-600 to-blue-600 rounded-lg flex items-center justify-center">
                  <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                  </svg>
                </div>
                <div class="ml-2">
                  <h1 class="text-xl font-bold text-gray-900">{{ branding.appName }}</h1>
                </div>
              </router-link>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <!-- User Navigation -->
              <template v-if="!isAdmin">
                <router-link 
                  to="/dashboard" 
                  :class="[
                    'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                    $route.path === '/dashboard' 
                      ? 'border-green-500 text-green-600' 
                      : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                  ]"
                >
                  Dashboard
                </router-link>
                <router-link 
                  to="/assistants" 
                  :class="[
                    'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                    $route.path === '/assistants' 
                      ? 'border-green-500 text-green-600' 
                      : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                  ]"
                >
                  Assistants
                </router-link>
                <router-link 
                  to="/call-logs" 
                  :class="[
                    'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                    $route.path === '/call-logs' 
                      ? 'border-green-500 text-green-600' 
                      : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                  ]"
                >
                  Call Logs
                </router-link>
                
                <!-- User Subscription Dropdown -->
                <div class="relative inline-flex items-center">
                  <button 
                    @click="subscriptionMenuOpen = !subscriptionMenuOpen"
                    :class="[
                      'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                      $route.path === '/subscription' || $route.path === '/transactions'
                        ? 'border-green-500 text-green-600' 
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                    ]"
                  >
                    Subscription
                    <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                  <div v-if="subscriptionMenuOpen" class="absolute right-0 top-full mt-1 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none border border-gray-200 z-50">
                    <router-link :to="isResellerAdmin ? '/reseller-billing' : '/subscription'" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Subscriptions</router-link>
                    <router-link to="/transactions" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Transactions</router-link>
                  </div>
                </div>
                
                <router-link 
                  to="/demo-request" 
                  :class="[
                    'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                    $route.path === '/demo-request' 
                      ? 'border-green-500 text-green-600' 
                      : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                  ]"
                >
                  Demo Requests
                </router-link>
                
                <!-- System Settings for Reseller Admins -->
                <div v-if="isResellerAdmin" class="relative inline-flex items-center">
                  <button 
                    @click="resellerConfigMenuOpen = !resellerConfigMenuOpen"
                    :class="[
                      'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                      $route.path.startsWith('/admin/system-settings') || $route.path.startsWith('/admin/stripe-configuration')
                        ? 'border-green-500 text-green-600' 
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                    ]"
                  >
                    Settings
                    <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                  <div v-if="resellerConfigMenuOpen" class="absolute right-0 top-full mt-1 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none border border-gray-200 z-50">
                    <router-link to="/admin/system-settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">System Settings</router-link>
                    <router-link to="/admin/stripe-configuration" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Stripe Configuration</router-link>
                  </div>
                </div>
              </template>
              
              <!-- Admin Navigation -->
              <template v-if="isAdmin">
                <router-link 
                  to="/admin" 
                  :class="[
                    'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                    $route.path === '/admin' 
                      ? 'border-green-500 text-green-600' 
                      : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                  ]"
                >
                  Dashboard
                </router-link>
                <router-link 
                  to="/admin/assistants" 
                  :class="[
                    'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                    $route.path.startsWith('/admin/assistants') 
                      ? 'border-green-500 text-green-600' 
                      : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                  ]"
                >
                  Assistants
                </router-link>
                <router-link 
                  to="/admin/call-logs" 
                  :class="[
                    'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                    $route.path.startsWith('/admin/call-logs') 
                      ? 'border-green-500 text-green-600' 
                      : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                  ]"
                >
                  Call Logs
                </router-link>
                
                <!-- Admin Subscription Dropdown -->
                <div class="relative inline-flex items-center">
                  <button 
                    @click="subscriptionMenuOpen = !subscriptionMenuOpen"
                    :class="[
                      'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                      $route.path.startsWith('/admin/subscriptions') || $route.path.startsWith('/admin/transactions')
                        ? 'border-green-500 text-green-600' 
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                    ]"
                  >
                    Subscription
                    <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                  <div v-if="subscriptionMenuOpen" class="absolute right-0 top-full mt-1 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none border border-gray-200 z-50">
                    <router-link to="/admin/subscriptions" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Subscriptions</router-link>
                    <router-link to="/admin/transactions" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Transactions</router-link>
                  </div>
                </div>
                
                <!-- Admin Config Dropdown -->
                <div class="relative inline-flex items-center">
                  <button 
                    @click="configMenuOpen = !configMenuOpen"
                    :class="[
                      'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                      $route.path.startsWith('/admin/features') || $route.path.startsWith('/admin/templates') || $route.path.startsWith('/admin/packages') || $route.path.startsWith('/admin/users') || $route.path.startsWith('/admin/resellers') || $route.path.startsWith('/admin/system-settings') || $route.path.startsWith('/admin/contacts') || $route.path.startsWith('/admin/stripe-configuration')
                        ? 'border-green-500 text-green-600' 
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                    ]"
                  >
                    Config
                    <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                  <div v-if="configMenuOpen" class="absolute right-0 top-full mt-1 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none border border-gray-200 z-50">
                    <router-link to="/admin/usage-overview" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Usage Overview</router-link>
                    <router-link to="/admin/features" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Features</router-link>
                    <router-link to="/admin/templates" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Templates</router-link>
                    <router-link to="/admin/packages" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Packages</router-link>
                    <router-link to="/admin/users" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Users</router-link>
                    <router-link to="/admin/system-settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">System Settings</router-link>
                    <router-link to="/admin/stripe-configuration" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Stripe Configuration</router-link>
                    <router-link to="/admin/contacts" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Contacts</router-link>
                  </div>
                </div>
                
                <router-link 
                  to="/admin/demo-requests" 
                  :class="[
                    'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                    $route.path === '/admin/demo-requests' 
                      ? 'border-green-500 text-green-600' 
                      : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                  ]"
                >
                  Demo Requests
                </router-link>
              </template>
            </div>
          </div>
          <div class="flex items-center">
            <!-- Mobile menu button -->
            <div class="md:hidden">
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
            </div>
            
            <!-- Desktop user menu -->
            <div class="hidden md:block relative">
                <button @click="showUserMenu = !showUserMenu" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                  <div class="h-8 w-8 rounded-full bg-primary-600 flex items-center justify-center">
                    <span class="text-white text-sm font-medium">{{ userInitials }}</span>
                  </div>
                  <svg class="ml-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
                <div v-if="showUserMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                  <div class="px-4 py-2 text-sm text-gray-700 border-b border-gray-200">
                    <p class="font-medium">{{ user.name }}</p>
                    <p class="text-xs text-gray-500">{{ user.email }}</p>
                  </div>
                  <router-link to="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</router-link>
                  <router-link 
                    v-if="isResellerAdmin" 
                    to="/reseller-billing" 
                    class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors"
                  >
                    <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Billing and Subscription
                  </router-link>
                  <router-link 
                    v-if="isSuperAdmin" 
                    to="/super-admin" 
                    class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700 transition-colors"
                  >
                    <svg class="w-4 h-4 mr-2 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Super Admin
                  </router-link>
                  <button @click="logout" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</button>
                </div>
              </div>
          </div>
        </div>
      </div>
    </nav>
    
    <!-- Mobile menu -->
    <div v-if="mobileMenuOpen" class="md:hidden">
      <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-b border-gray-200">
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
            to="/call-logs"
            class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
            @click="mobileMenuOpen = false"
          >
            Call Logs
          </router-link>
          
          <router-link
            v-if="!isAdmin"
            to="/subscription"
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
          
          <router-link
            v-if="!isAdmin"
            to="/demo-request"
            class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
            @click="mobileMenuOpen = false"
          >
            Request Demo
          </router-link>
          
          <!-- System Settings for Reseller Admins (Mobile) -->
          <template v-if="isResellerAdmin">
            <div class="border-t border-gray-200 pt-4 mt-4">
              <h3 class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Settings
              </h3>
              
              <router-link
                to="/admin/system-settings"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                System Settings
              </router-link>
              
              <router-link
                to="/admin/stripe-configuration"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                Stripe Configuration
              </router-link>
            </div>
          </template>
          
          <!-- Admin Navigation -->
          <template v-if="isAdmin">
            <div class="border-t border-gray-200 pt-4 mt-4">
              <h3 class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Admin Panel
              </h3>
              
              <router-link
                to="/admin"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                Dashboard
              </router-link>
              
              <router-link
                to="/admin/assistants"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                Assistants
              </router-link>
              
              <router-link
                to="/admin/call-logs"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                Call Logs
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
                to="/admin/features"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                Features
              </router-link>
              
              <router-link
                to="/admin/templates"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                Templates
              </router-link>
              
              <router-link
                to="/admin/packages"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                Packages
              </router-link>
              
              <router-link
                to="/admin/usage-overview"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                Usage Overview
              </router-link>
              
              <router-link
                to="/admin/users"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                Users
              </router-link>
              
              <router-link
                to="/admin/resellers"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                Resellers
              </router-link>
              
              <router-link
                to="/admin/system-settings"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                System Settings
              </router-link>
              
              <router-link
                to="/admin/stripe-configuration"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                Stripe Configuration
              </router-link>
              
              <router-link
                to="/admin/contacts"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                Contacts
              </router-link>
              
              <router-link
                to="/admin/demo-requests"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
                @click="mobileMenuOpen = false"
              >
                Demo Requests
              </router-link>
            </div>
          </template>
          
          <!-- User menu items for mobile -->
          <div class="border-t border-gray-200 pt-4 mt-4">
            <h3 class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Account
            </h3>
            <router-link
              to="/profile"
              class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
              @click="mobileMenuOpen = false"
            >
              Profile
            </router-link>
            <router-link
              v-if="isResellerAdmin"
              to="/reseller-billing"
              class="flex items-center px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-700 hover:bg-blue-50 transition-colors"
              @click="mobileMenuOpen = false"
            >
              <svg class="w-5 h-5 mr-3 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Billing and Subscription
            </router-link>
            <router-link
              v-if="isSuperAdmin"
              to="/super-admin"
              class="flex items-center px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-purple-700 hover:bg-purple-50 transition-colors"
              @click="mobileMenuOpen = false"
            >
              <svg class="w-5 h-5 mr-3 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Super Admin
            </router-link>
            <button
              @click="logout"
              class="block w-full text-left px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
            >
              Sign out
            </button>
          </div>
        </template>
        
        <!-- Non-authenticated users -->
        <template v-else>
          <router-link
            to="/login"
            class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
            @click="mobileMenuOpen = false"
          >
            Login
          </router-link>
          <router-link
            to="/register"
            class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50"
            @click="mobileMenuOpen = false"
          >
            Register
          </router-link>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
import { useResellerData } from '../../composables/useResellerData.js'

export default {
  name: 'Navigation',
  data() {
    return {
      showUserMenu: false,
      subscriptionMenuOpen: false,
      configMenuOpen: false,
      resellerConfigMenuOpen: false,
      mobileMenuOpen: false,
      user: JSON.parse(localStorage.getItem('user') || '{}')
    }
  },
  setup() {
    // Get reseller data - available immediately
    const { branding, isLoaded } = useResellerData()
    
    return {
      branding,
      isLoaded
    }
  },
  computed: {
    userInitials() {
      return this.user.name ? this.user.name.split(' ').map(n => n[0]).join('').toUpperCase() : 'U';
    },
    isAdmin() {
      return this.user.role === 'admin' || this.user.role === 'reseller_admin';
    },
    isResellerAdmin() {
      return this.user.role === 'reseller_admin';
    },
    isSuperAdmin() {
      return this.user.role === 'admin';
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
        
        // Redirect to login
        this.$router.push('/login');
      } catch (error) {
        // Even if API call fails, clear local storage and redirect
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        this.$router.push('/login');
      }
    }
  },
  mounted() {
    // Close menus when clicking outside
    document.addEventListener('click', (e) => {
      if (!this.$el.contains(e.target)) {
        this.showUserMenu = false;
        this.subscriptionMenuOpen = false;
        this.configMenuOpen = false;
      }
    });
  }
}
</script> 