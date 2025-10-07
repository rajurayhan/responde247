<template>
  <SuperAdminLayout title="Usage Reports" subtitle="Comprehensive usage analytics and reporting">
    <div class="flex-1 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="md:flex md:items-center md:justify-between">
            <div class="flex-1 min-w-0">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                  </div>
                </div>
                <div>
                  <h1 class="text-3xl font-bold text-gray-900">
                    Usage Reports
                  </h1>
                  <p class="mt-1 text-sm text-gray-600">
                    Comprehensive usage analytics and reporting
                  </p>
                </div>
              </div>
            </div>
            <div class="mt-6 md:mt-0 md:ml-4">
              <button 
                @click="exportReport"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl shadow-sm text-white bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transform transition-all duration-150 hover:scale-105"
              >
                <svg class="-ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export Report
              </button>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Filters</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Reseller Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Reseller</label>
              <select 
                v-model="filters.reseller_id" 
                @change="applyFilters"
                class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">All Resellers</option>
                <option v-for="reseller in filterOptions.resellers" :key="reseller.value" :value="reseller.value">
                  {{ reseller.label }}
                </option>
              </select>
            </div>

            <!-- User Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
              <select 
                v-model="filters.user_id" 
                @change="applyFilters"
                class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">All Users</option>
                <option v-for="user in filteredUsers" :key="user.value" :value="user.value">
                  {{ user.label }}
                </option>
              </select>
            </div>

            <!-- Assistant Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Assistant</label>
              <select 
                v-model="filters.assistant_id" 
                @change="applyFilters"
                class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">All Assistants</option>
                <option v-for="assistant in filteredAssistants" :key="assistant.value" :value="assistant.value">
                  {{ assistant.label }}
                </option>
              </select>
            </div>

            <!-- Date Range -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <input 
                    v-model="filters.start_date" 
                    @change="applyFilters"
                    type="date" 
                    class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                  <label class="text-xs text-gray-500 mt-1 block">Start Date</label>
                </div>
                <div>
                  <input 
                    v-model="filters.end_date" 
                    @change="applyFilters"
                    type="date" 
                    class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                  <label class="text-xs text-gray-500 mt-1 block">End Date</label>
                </div>
              </div>
            </div>
          </div>

          <!-- Clear Filters -->
          <div class="mt-4 flex justify-end">
            <button 
              @click="clearFilters"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              Clear Filters
            </button>
          </div>
        </div>

        <!-- Summary Statistics -->
        <div v-if="summaryStats" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center">
                  <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total Calls</p>
                <p class="text-2xl font-semibold text-gray-900">{{ summaryStats.total_calls.toLocaleString() }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="h-12 w-12 rounded-lg bg-green-100 flex items-center justify-center">
                  <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total Minutes</p>
                <p class="text-2xl font-semibold text-gray-900">{{ summaryStats.total_minutes.toLocaleString() }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="h-12 w-12 rounded-lg bg-purple-100 flex items-center justify-center">
                  <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Unique Assistants</p>
                <p class="text-2xl font-semibold text-gray-900">{{ summaryStats.unique_assistants }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="h-12 w-12 rounded-lg bg-orange-100 flex items-center justify-center">
                  <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total Cost</p>
                <p class="text-2xl font-semibold text-gray-900">${{ summaryStats.total_cost.toLocaleString() }}</p>
              </div>
            </div>
          </div>
        </div>


        <!-- Billing Period Usage Block -->
        <div v-if="billingPeriodUsage" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h3 class="text-lg font-semibold text-gray-900">
                {{ billingPeriodUsage.type === 'reseller' ? 'Reseller' : 'User' }} Billing Period Usage
              </h3>
              <p class="text-sm text-gray-600 mt-1">
                Current billing period: {{ formatDate(billingPeriodUsage.billing_period.start) }} - {{ formatDate(billingPeriodUsage.billing_period.end) }}
                <span v-if="billingPeriodUsage.billing_period.is_fallback" class="ml-2 text-xs text-orange-600 bg-orange-100 px-2 py-1 rounded-full">
                  Fallback Period
                </span>
              </p>
            </div>
            <div class="flex items-center space-x-2">
              <span 
                :class="[
                  'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                  billingPeriodUsage.entity.subscription_status === 'active'
                    ? 'bg-green-100 text-green-800'
                    : 'bg-red-100 text-red-800'
                ]"
              >
                {{ billingPeriodUsage.entity.subscription_status }}
              </span>
            </div>
          </div>

          <!-- Entity Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-gray-50 rounded-lg p-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ billingPeriodUsage.type === 'reseller' ? 'Reseller' : 'User' }} Information
              </h4>
              <div class="space-y-1 text-sm text-gray-600">
                <div><span class="font-medium">Name:</span> {{ billingPeriodUsage.entity.name }}</div>
                <div><span class="font-medium">Email:</span> {{ billingPeriodUsage.entity.email }}</div>
                <div v-if="billingPeriodUsage.type === 'user'">
                  <span class="font-medium">Role:</span> {{ billingPeriodUsage.entity.role }}
                </div>
                <div v-if="billingPeriodUsage.type === 'user' && billingPeriodUsage.entity.reseller_name">
                  <span class="font-medium">Reseller:</span> {{ billingPeriodUsage.entity.reseller_name }}
                </div>
                <div><span class="font-medium">Package:</span> {{ billingPeriodUsage.entity.package_name }}</div>
              </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">Billing Period Details</h4>
              <div class="space-y-1 text-sm text-gray-600">
                <div><span class="font-medium">Start:</span> {{ formatDate(billingPeriodUsage.billing_period.start) }}</div>
                <div><span class="font-medium">End:</span> {{ formatDate(billingPeriodUsage.billing_period.end) }}</div>
                <div><span class="font-medium">Type:</span> {{ billingPeriodUsage.billing_period.interval_type }}</div>
                <div v-if="billingPeriodUsage.billing_period.subscription_day">
                  <span class="font-medium">Billing Day:</span> {{ billingPeriodUsage.billing_period.subscription_day }}
                </div>
              </div>
            </div>
          </div>

          <!-- Usage Statistics -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 rounded-lg p-4">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="h-8 w-8 rounded-lg bg-blue-100 flex items-center justify-center">
                    <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-gray-500">Total Calls</p>
                  <p class="text-lg font-semibold text-gray-900">{{ billingPeriodUsage.usage.total_calls.toLocaleString() }}</p>
                </div>
              </div>
            </div>

            <div class="bg-green-50 rounded-lg p-4">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="h-8 w-8 rounded-lg bg-green-100 flex items-center justify-center">
                    <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-gray-500">Total Minutes</p>
                  <p class="text-lg font-semibold text-gray-900">{{ billingPeriodUsage.usage.total_minutes.toLocaleString() }}</p>
                </div>
              </div>
            </div>

            <div class="bg-purple-50 rounded-lg p-4">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="h-8 w-8 rounded-lg bg-purple-100 flex items-center justify-center">
                    <svg class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-gray-500">Unique Assistants</p>
                  <p class="text-lg font-semibold text-gray-900">{{ billingPeriodUsage.usage.unique_assistants }}</p>
                </div>
              </div>
            </div>

            <div class="bg-orange-50 rounded-lg p-4">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="h-8 w-8 rounded-lg bg-orange-100 flex items-center justify-center">
                    <svg class="h-4 w-4 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                    </svg>
                  </div>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-gray-500">Total Cost</p>
                  <p class="text-lg font-semibold text-gray-900">${{ billingPeriodUsage.usage.total_cost.toLocaleString() }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Package Limits & Overage -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-gray-50 rounded-lg p-4">
              <h4 class="text-sm font-medium text-gray-900 mb-3">Package Limits</h4>
              <div class="space-y-2">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Monthly Minutes Limit:</span>
                  <span class="text-sm font-medium text-gray-900">
                    {{ billingPeriodUsage.package_limits.monthly_minutes_limit.toLocaleString() }}
                  </span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Extra Per Minute:</span>
                  <span class="text-sm font-medium text-gray-900">
                    ${{ billingPeriodUsage.package_limits.extra_per_minute_charge }}
                  </span>
                </div>
                <div class="mt-3">
                  <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">Usage Progress</span>
                    <span class="text-gray-900">
                      {{ billingPeriodUsage.usage.total_minutes.toLocaleString() }} / {{ billingPeriodUsage.package_limits.monthly_minutes_limit.toLocaleString() }}
                    </span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div 
                      :class="[
                        'h-2 rounded-full transition-all duration-300',
                        (billingPeriodUsage.usage.total_minutes / billingPeriodUsage.package_limits.monthly_minutes_limit) > 1
                          ? 'bg-red-500'
                          : (billingPeriodUsage.usage.total_minutes / billingPeriodUsage.package_limits.monthly_minutes_limit) > 0.8
                          ? 'bg-yellow-500'
                          : 'bg-green-500'
                      ]"
                      :style="{ width: Math.min((billingPeriodUsage.usage.total_minutes / billingPeriodUsage.package_limits.monthly_minutes_limit) * 100, 100) + '%' }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
              <h4 class="text-sm font-medium text-gray-900 mb-3">Overage Charges</h4>
              <div class="space-y-2">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Overage Minutes:</span>
                  <span class="text-sm font-medium text-gray-900">
                    {{ billingPeriodUsage.overage.overage_minutes.toLocaleString() }}
                  </span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Overage Cost:</span>
                  <span 
                    :class="[
                      'text-sm font-medium',
                      billingPeriodUsage.overage.overage_cost > 0 ? 'text-red-600' : 'text-gray-900'
                    ]"
                  >
                    ${{ billingPeriodUsage.overage.overage_cost.toLocaleString() }}
                  </span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Average Duration:</span>
                  <span class="text-sm font-medium text-gray-900">
                    {{ billingPeriodUsage.usage.avg_duration }}s
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>

        <!-- Usage Data Table -->
        <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Assistant Usage Details</h3>
            <div class="flex items-center space-x-4">
              <span class="text-sm text-gray-500">{{ usageData.pagination.total }} total records</span>
              <select 
                v-model="perPage" 
                @change="changePerPage"
                class="text-sm border border-gray-300 rounded-lg px-3 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="10">10 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
                <option value="100">100 per page</option>
              </select>
            </div>
          </div>

          <div v-if="usageData.data.length === 0" class="text-center py-8">
            <div class="mx-auto h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
              <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <p class="text-gray-500">No usage data found for the selected filters</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assistant</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reseller</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calls</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Minutes</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Duration</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">First Call</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Call</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in usageData.data" :key="`${item.assistant_id}-${item.user_id}`">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div class="text-sm font-medium text-gray-900">{{ item.assistant_name }}</div>
                      <div class="text-sm text-gray-500">{{ item.assistant_phone }}</div>
                      <span 
                        :class="[
                          'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mt-1',
                          item.assistant_type === 'production' 
                            ? 'bg-green-100 text-green-800' 
                            : 'bg-blue-100 text-blue-800'
                        ]"
                      >
                        {{ item.assistant_type }}
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div class="text-sm font-medium text-gray-900">{{ item.user_name }}</div>
                      <div class="text-sm text-gray-500">{{ item.user_email }}</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ item.reseller_name }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ item.total_calls.toLocaleString() }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ item.total_minutes.toLocaleString() }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ item.avg_duration }}s
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${{ item.total_cost.toLocaleString() }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(item.first_call) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(item.last_call) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="usageData.pagination.last_page > 1" class="mt-6 flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <p class="text-sm text-gray-600">
                Showing <span class="font-semibold text-gray-900">{{ usageData.pagination.from }}</span> to 
                <span class="font-semibold text-gray-900">{{ usageData.pagination.to }}</span> of 
                <span class="font-semibold text-gray-900">{{ usageData.pagination.total }}</span> records
              </p>
            </div>
            
            <div class="flex items-center space-x-1">
              <button 
                @click="loadPage(usageData.pagination.current_page - 1)"
                :disabled="usageData.pagination.current_page <= 1"
                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Previous
              </button>
              
              <button 
                v-for="page in getVisiblePages(usageData.pagination.current_page, usageData.pagination.last_page)"
                :key="page"
                @click="loadPage(page)"
                :class="[
                  'px-3 py-2 text-sm font-medium rounded-lg',
                  page === usageData.pagination.current_page
                    ? 'bg-blue-600 text-white'
                    : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'
                ]"
              >
                {{ page }}
              </button>
              
              <button 
                @click="loadPage(usageData.pagination.current_page + 1)"
                :disabled="usageData.pagination.current_page >= usageData.pagination.last_page"
                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </SuperAdminLayout>
</template>

<script>
import SuperAdminLayout from '../layouts/SuperAdminLayout.vue'
import axios from 'axios'

export default {
  name: 'UsageReport',
  components: {
    SuperAdminLayout
  },
  data() {
    return {
      loading: true,
      usageData: { data: [], pagination: {} },
      filterOptions: { resellers: [], users: [], assistants: [] },
      summaryStats: null,
      billingPeriodUsage: null,
      filters: {
        reseller_id: '',
        user_id: '',
        assistant_id: '',
        start_date: '',
        end_date: ''
      },
      currentPage: 1,
      perPage: 25
    }
  },
  computed: {
    filteredUsers() {
      if (!this.filters.reseller_id) {
        return this.filterOptions.users
      }
      return this.filterOptions.users.filter(user => 
        user.reseller_name === this.getResellerName(this.filters.reseller_id)
      )
    },
    filteredAssistants() {
      if (!this.filters.reseller_id && !this.filters.user_id) {
        return this.filterOptions.assistants
      }
      return this.filterOptions.assistants.filter(assistant => {
        const matchesReseller = !this.filters.reseller_id || 
          assistant.reseller_name === this.getResellerName(this.filters.reseller_id)
        const matchesUser = !this.filters.user_id || 
          assistant.user_name === this.getUserName(this.filters.user_id)
        return matchesReseller && matchesUser
      })
    }
  },
  mounted() {
    this.loadUsageReport()
  },
  methods: {
    async loadUsageReport() {
      this.loading = true
      
      try {
        const params = new URLSearchParams({
          page: this.currentPage,
          per_page: this.perPage,
          ...this.filters
        })
        
        const response = await axios.get(`/api/super-admin/usage-reports?${params}`)
        this.usageData = response.data.data.usage_data
        this.filterOptions = response.data.data.filter_options
        this.summaryStats = response.data.data.summary_stats
        this.billingPeriodUsage = response.data.data.billing_period_usage
        
      } catch (error) {
        console.error('Error loading usage report:', error)
      } finally {
        this.loading = false
      }
    },
    applyFilters() {
      this.currentPage = 1
      this.loadUsageReport()
    },
    clearFilters() {
      this.filters = {
        reseller_id: '',
        user_id: '',
        assistant_id: '',
        start_date: '',
        end_date: ''
      }
      this.currentPage = 1
      this.loadUsageReport()
    },
    loadPage(page) {
      this.currentPage = page
      this.loadUsageReport()
    },
    changePerPage() {
      this.currentPage = 1
      this.loadUsageReport()
    },
    exportReport() {
      // Implement export functionality
      console.log('Export report functionality')
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString()
    },
    getResellerName(resellerId) {
      const reseller = this.filterOptions.resellers.find(r => r.value === resellerId)
      return reseller ? reseller.label : ''
    },
    getUserName(userId) {
      const user = this.filterOptions.users.find(u => u.value === userId)
      return user ? user.label.split(' (')[0] : ''
    },
    getVisiblePages(currentPage, lastPage) {
      const pages = []
      const start = Math.max(1, currentPage - 2)
      const end = Math.min(lastPage, currentPage + 2)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    }
  }
}
</script>
