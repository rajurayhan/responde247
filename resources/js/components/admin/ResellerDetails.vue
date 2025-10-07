<template>
  <SuperAdminLayout title="Reseller Details" subtitle="View detailed information about this reseller">
    <div class="flex-1 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="text-center py-12">
          <div class="mx-auto h-24 w-24 rounded-full bg-red-100 flex items-center justify-center mb-6">
            <svg class="h-12 w-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Error Loading Reseller</h3>
          <p class="text-gray-500 mb-8">{{ error }}</p>
          <button @click="loadResellerDetails" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            Try Again
          </button>
        </div>

        <!-- Reseller Details -->
        <div v-else-if="resellerData" class="space-y-8">
          <!-- Header -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                  <div class="h-16 w-16 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2m-2 0H7m2 0H5m2 0h2m2 0h2M9 7h6m-6 4h6m-6 4h6" />
                    </svg>
                  </div>
                </div>
                <div>
                  <h1 class="text-3xl font-bold text-gray-900">{{ resellerData.reseller.org_name }}</h1>
                  <p class="text-gray-600">{{ resellerData.reseller.company_email }}</p>
                  <div class="flex items-center mt-2">
                    <span 
                      :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        resellerData.reseller.status === 'active' 
                          ? 'bg-green-100 text-green-800' 
                          : 'bg-red-100 text-red-800'
                      ]"
                    >
                      {{ resellerData.reseller.status }}
                    </span>
                    <span v-if="resellerData.reseller.domain" class="ml-3 text-sm text-gray-500">
                      {{ resellerData.reseller.domain }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="flex space-x-3">
                <button 
                  @click="$router.push('/super-admin/resellers')"
                  class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                >
                  <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                  </svg>
                  Back to Resellers
                </button>
              </div>
            </div>
          </div>

          <!-- Statistics Overview -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center">
                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Total Users</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ resellerData.statistics.total_users }}</p>
                  <p class="text-sm text-green-600">{{ resellerData.statistics.active_users }} active</p>
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
                  <p class="text-sm font-medium text-gray-500">Total Assistants</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ resellerData.statistics.total_assistants }}</p>
                  <p class="text-sm text-green-600">{{ resellerData.statistics.active_assistants }} active</p>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="h-12 w-12 rounded-lg bg-green-100 flex items-center justify-center">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Total Calls</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ resellerData.statistics.usage_stats.total_calls.toLocaleString() }}</p>
                  <p class="text-sm text-blue-600">{{ resellerData.statistics.usage_stats.this_month_calls }} this month</p>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="h-12 w-12 rounded-lg bg-orange-100 flex items-center justify-center">
                    <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Total Minutes</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ resellerData.statistics.usage_stats.total_minutes.toLocaleString() }}</p>
                  <p class="text-sm text-orange-600">{{ resellerData.statistics.usage_stats.this_month_minutes }} this month</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Billing Information -->
          <div v-if="resellerData.billing_info" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Billing Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div>
                <p class="text-sm font-medium text-gray-500">Subscription Status</p>
                <p class="text-lg font-semibold text-gray-900">{{ resellerData.billing_info.subscription_status }}</p>
              </div>
              <div v-if="resellerData.billing_info.package_name">
                <p class="text-sm font-medium text-gray-500">Package</p>
                <p class="text-lg font-semibold text-gray-900">{{ resellerData.billing_info.package_name }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                <p class="text-lg font-semibold text-gray-900">${{ resellerData.billing_info.total_revenue.toLocaleString() }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-500">This Month Revenue</p>
                <p class="text-lg font-semibold text-gray-900">${{ resellerData.billing_info.this_month_revenue.toLocaleString() }}</p>
              </div>
              <div v-if="resellerData.billing_info.current_billing_period_minutes">
                <p class="text-sm font-medium text-gray-500">Current Period Usage</p>
                <p class="text-lg font-semibold text-gray-900">{{ resellerData.billing_info.current_billing_period_minutes }} minutes</p>
              </div>
            </div>
          </div>

          <!-- Reseller Billing Period Usage Block -->
          <div v-if="resellerData.reseller_billing_usage.has_active_subscription" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h3 class="text-lg font-semibold text-gray-900">Current Billing Period Usage</h3>
                <p class="text-sm text-gray-600 mt-1">
                  Period: {{ formatDate(resellerData.reseller_billing_usage.billing_period.start) }} - {{ formatDate(resellerData.reseller_billing_usage.billing_period.end) }}
                </p>
              </div>
              <div class="flex items-center space-x-2">
                <div class="h-2 w-2 rounded-full bg-green-500"></div>
                <span class="text-sm text-gray-600">Active Period</span>
              </div>
            </div>

            <!-- Usage Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
              <!-- Total Calls -->
              <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-blue-600">Total Calls</p>
                    <p class="text-2xl font-bold text-blue-900">{{ resellerData.reseller_billing_usage.usage_stats.total_calls.toLocaleString() }}</p>
                  </div>
                  <div class="h-12 w-12 rounded-lg bg-blue-200 flex items-center justify-center">
                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Total Minutes -->
              <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-green-600">Total Minutes</p>
                    <p class="text-2xl font-bold text-green-900">{{ resellerData.reseller_billing_usage.usage_stats.total_minutes.toLocaleString() }}</p>
                    <div v-if="resellerData.reseller_billing_usage.limits.monthly_minutes_limit > 0" class="mt-1">
                      <div class="flex items-center justify-between text-xs">
                        <span class="text-green-700">Usage</span>
                        <span class="text-green-700">{{ resellerData.reseller_billing_usage.usage_percentages.minutes_used_percent }}%</span>
                      </div>
                      <div class="w-full bg-green-200 rounded-full h-1.5 mt-1">
                        <div 
                          class="bg-green-600 h-1.5 rounded-full transition-all duration-300"
                          :style="{ width: Math.min(resellerData.reseller_billing_usage.usage_percentages.minutes_used_percent, 100) + '%' }"
                        ></div>
                      </div>
                    </div>
                  </div>
                  <div class="h-12 w-12 rounded-lg bg-green-200 flex items-center justify-center">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Total Cost -->
              <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-purple-600">Total Cost</p>
                    <p class="text-2xl font-bold text-purple-900">${{ resellerData.reseller_billing_usage.usage_stats.total_cost.toLocaleString() }}</p>
                  </div>
                  <div class="h-12 w-12 rounded-lg bg-purple-200 flex items-center justify-center">
                    <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Active Assistants -->
              <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-orange-600">Active Assistants</p>
                    <p class="text-2xl font-bold text-orange-900">{{ resellerData.reseller_billing_usage.usage_stats.unique_assistants }}</p>
                    <div v-if="resellerData.reseller_billing_usage.limits.voice_agents_limit > 0" class="mt-1">
                      <div class="flex items-center justify-between text-xs">
                        <span class="text-orange-700">Usage</span>
                        <span class="text-orange-700">{{ resellerData.reseller_billing_usage.usage_percentages.assistants_used_percent }}%</span>
                      </div>
                      <div class="w-full bg-orange-200 rounded-full h-1.5 mt-1">
                        <div 
                          class="bg-orange-600 h-1.5 rounded-full transition-all duration-300"
                          :style="{ width: Math.min(resellerData.reseller_billing_usage.usage_percentages.assistants_used_percent, 100) + '%' }"
                        ></div>
                      </div>
                    </div>
                  </div>
                  <div class="h-12 w-12 rounded-lg bg-orange-200 flex items-center justify-center">
                    <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <!-- Package Limits Summary -->
            <div v-if="resellerData.reseller_billing_usage.limits.monthly_minutes_limit > 0 || resellerData.reseller_billing_usage.limits.voice_agents_limit > 0" class="bg-gray-50 rounded-lg p-4">
              <h4 class="text-sm font-semibold text-gray-900 mb-3">Package Limits</h4>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div v-if="resellerData.reseller_billing_usage.limits.monthly_minutes_limit > 0" class="text-center">
                  <p class="text-sm text-gray-600">Monthly Minutes</p>
                  <p class="text-lg font-semibold text-gray-900">
                    {{ resellerData.reseller_billing_usage.usage_stats.total_minutes.toLocaleString() }} / {{ resellerData.reseller_billing_usage.limits.monthly_minutes_limit.toLocaleString() }}
                  </p>
                </div>
                <div v-if="resellerData.reseller_billing_usage.limits.voice_agents_limit > 0" class="text-center">
                  <p class="text-sm text-gray-600">Voice Agents</p>
                  <p class="text-lg font-semibold text-gray-900">
                    {{ resellerData.reseller_billing_usage.usage_stats.unique_assistants }} / {{ resellerData.reseller_billing_usage.limits.voice_agents_limit }}
                  </p>
                </div>
                <div v-if="resellerData.reseller_billing_usage.limits.users_limit > 0" class="text-center">
                  <p class="text-sm text-gray-600">Users</p>
                  <p class="text-lg font-semibold text-gray-900">
                    {{ resellerData.reseller_billing_usage.usage_stats.unique_users }} / {{ resellerData.reseller_billing_usage.limits.users_limit }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Assistants Block -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-semibold text-gray-900">Assistants</h3>
              <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-500">{{ resellerData.assistants.pagination.total }} total</span>
                <select 
                  v-model="assistantsPerPage" 
                  @change="changeAssistantsPerPage(assistantsPerPage)"
                  class="text-sm border border-gray-300 rounded-lg px-3 py-1 focus:outline-none focus:ring-2 focus:ring-green-500"
                >
                  <option value="5">5 per page</option>
                  <option value="10">10 per page</option>
                  <option value="25">25 per page</option>
                  <option value="50">50 per page</option>
                </select>
              </div>
            </div>
            
            <div v-if="resellerData.assistants.data.length === 0" class="text-center py-8">
              <div class="mx-auto h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
              </div>
              <p class="text-gray-500">No assistants found for this reseller</p>
            </div>

            <div v-else>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assistant</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calls</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Minutes</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="assistant in resellerData.assistants.data" :key="assistant.id">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div>
                          <div class="text-sm font-medium text-gray-900">{{ assistant.name }}</div>
                          <div class="text-sm text-gray-500">{{ assistant.phone_number }}</div>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ assistant.user_name }}</div>
                        <div class="text-sm text-gray-500">{{ assistant.user_email }}</div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span 
                          :class="[
                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                            assistant.type === 'production' 
                              ? 'bg-green-100 text-green-800' 
                              : 'bg-blue-100 text-blue-800'
                          ]"
                        >
                          {{ assistant.type }}
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ assistant.total_calls.toLocaleString() }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ assistant.total_minutes.toLocaleString() }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ formatDate(assistant.created_at) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Assistants Pagination -->
              <div v-if="resellerData.assistants.pagination.last_page > 1" class="mt-6 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <p class="text-sm text-gray-600">
                    Showing <span class="font-semibold text-gray-900">{{ resellerData.assistants.pagination.from }}</span> to 
                    <span class="font-semibold text-gray-900">{{ resellerData.assistants.pagination.to }}</span> of 
                    <span class="font-semibold text-gray-900">{{ resellerData.assistants.pagination.total }}</span> assistants
                  </p>
                </div>
                
                <div class="flex items-center space-x-1">
                  <button 
                    @click="loadAssistantsPage(resellerData.assistants.pagination.current_page - 1)"
                    :disabled="resellerData.assistants.pagination.current_page <= 1"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Previous
                  </button>
                  
                  <button 
                    v-for="page in getVisiblePages(resellerData.assistants.pagination.current_page, resellerData.assistants.pagination.last_page)"
                    :key="page"
                    @click="loadAssistantsPage(page)"
                    :class="[
                      'px-3 py-2 text-sm font-medium rounded-lg',
                      page === resellerData.assistants.pagination.current_page
                        ? 'bg-green-600 text-white'
                        : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'
                    ]"
                  >
                    {{ page }}
                  </button>
                  
                  <button 
                    @click="loadAssistantsPage(resellerData.assistants.pagination.current_page + 1)"
                    :disabled="resellerData.assistants.pagination.current_page >= resellerData.assistants.pagination.last_page"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Next
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Users Block -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" data-section="users">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-semibold text-gray-900">Users</h3>
              <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-500">{{ resellerData.users.pagination.total }} total</span>
                <select 
                  v-model="usersPerPage" 
                  @change="changeUsersPerPage(usersPerPage)"
                  class="text-sm border border-gray-300 rounded-lg px-3 py-1 focus:outline-none focus:ring-2 focus:ring-green-500"
                >
                  <option value="5">5 per page</option>
                  <option value="10">10 per page</option>
                  <option value="25">25 per page</option>
                  <option value="50">50 per page</option>
                </select>
              </div>
            </div>
            
            <div v-if="resellerData.users.data.length === 0" class="text-center py-8">
              <div class="mx-auto h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                </svg>
              </div>
              <p class="text-gray-500">No users found for this reseller</p>
            </div>

            <div v-else>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assistants</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Calls</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Minutes</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Billing Period Usage</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="user in resellerData.users.data" :key="user.id">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div>
                          <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                          <div class="text-sm text-gray-500">{{ user.email }}</div>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span 
                          :class="[
                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                            user.role === 'reseller_admin' 
                              ? 'bg-purple-100 text-purple-800' 
                              : 'bg-gray-100 text-gray-800'
                          ]"
                        >
                          {{ user.role }}
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ user.total_assistants }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ user.total_calls.toLocaleString() }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ user.total_minutes.toLocaleString() }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div v-if="user.billing_period_usage" class="text-sm">
                          <div class="flex items-center space-x-2">
                            <div class="text-gray-900 font-medium">{{ user.billing_period_usage.total_calls.toLocaleString() }} calls</div>
                          </div>
                          <div class="text-gray-600 text-xs mt-1">
                            {{ user.billing_period_usage.total_minutes.toLocaleString() }} min • ${{ user.billing_period_usage.total_cost.toLocaleString() }}
                          </div>
                        </div>
                        <div v-else class="text-sm text-gray-500">
                          No usage data
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span 
                          :class="[
                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                            user.status === 'active' 
                              ? 'bg-green-100 text-green-800' 
                              : 'bg-red-100 text-red-800'
                          ]"
                        >
                          {{ user.status }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Users Pagination -->
              <div v-if="resellerData.users.pagination.last_page > 1" class="mt-6 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <p class="text-sm text-gray-600">
                    Showing <span class="font-semibold text-gray-900">{{ resellerData.users.pagination.from }}</span> to 
                    <span class="font-semibold text-gray-900">{{ resellerData.users.pagination.to }}</span> of 
                    <span class="font-semibold text-gray-900">{{ resellerData.users.pagination.total }}</span> users
                  </p>
                </div>
                
                <div class="flex items-center space-x-1">
                  <button 
                    @click="loadUsersPage(resellerData.users.pagination.current_page - 1)"
                    :disabled="resellerData.users.pagination.current_page <= 1"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Previous
                  </button>
                  
                  <button 
                    v-for="page in getVisiblePages(resellerData.users.pagination.current_page, resellerData.users.pagination.last_page)"
                    :key="page"
                    @click="loadUsersPage(page)"
                    :class="[
                      'px-3 py-2 text-sm font-medium rounded-lg',
                      page === resellerData.users.pagination.current_page
                        ? 'bg-green-600 text-white'
                        : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'
                    ]"
                  >
                    {{ page }}
                  </button>
                  
                  <button 
                    @click="loadUsersPage(resellerData.users.pagination.current_page + 1)"
                    :disabled="resellerData.users.pagination.current_page >= resellerData.users.pagination.last_page"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Next
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Activity -->
          <div v-if="resellerData.recent_activity" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Recent Activity</h3>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
              <!-- Recent Calls -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Recent Calls</h4>
                <div v-if="resellerData.recent_activity.recent_calls.length === 0" class="text-sm text-gray-500">
                  No recent calls
                </div>
                <div v-else class="space-y-2">
                  <div v-for="call in resellerData.recent_activity.recent_calls.slice(0, 5)" :key="call.id" class="text-sm">
                    <div class="flex justify-between">
                      <span class="text-gray-900">{{ call.assistant_name }}</span>
                      <span class="text-gray-500">{{ formatDate(call.start_time) }}</span>
                    </div>
                    <div class="text-xs text-gray-500">{{ call.user_name }} • {{ call.duration }}s</div>
                  </div>
                </div>
              </div>

              <!-- Recent Users -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Recent Users</h4>
                <div v-if="resellerData.recent_activity.recent_users.length === 0" class="text-sm text-gray-500">
                  No recent users
                </div>
                <div v-else class="space-y-2">
                  <div v-for="user in resellerData.recent_activity.recent_users" :key="user.id" class="text-sm">
                    <div class="flex justify-between">
                      <span class="text-gray-900">{{ user.name }}</span>
                      <span class="text-gray-500">{{ formatDate(user.created_at) }}</span>
                    </div>
                    <div class="text-xs text-gray-500">{{ user.email }} • {{ user.role }}</div>
                  </div>
                </div>
              </div>

              <!-- Recent Assistants -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Recent Assistants</h4>
                <div v-if="resellerData.recent_activity.recent_assistants.length === 0" class="text-sm text-gray-500">
                  No recent assistants
                </div>
                <div v-else class="space-y-2">
                  <div v-for="assistant in resellerData.recent_activity.recent_assistants" :key="assistant.id" class="text-sm">
                    <div class="flex justify-between">
                      <span class="text-gray-900">{{ assistant.name }}</span>
                      <span class="text-gray-500">{{ formatDate(assistant.created_at) }}</span>
                    </div>
                    <div class="text-xs text-gray-500">{{ assistant.user_name }} • {{ assistant.type }}</div>
                  </div>
                </div>
              </div>
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
  name: 'ResellerDetails',
  components: {
    SuperAdminLayout
  },
  data() {
    return {
      loading: true,
      error: null,
      resellerData: null,
      assistantsPage: 1,
      usersPage: 1,
      assistantsPerPage: 10,
      usersPerPage: 10
    }
  },
  computed: {
    resellerId() {
      return this.$route.params.reseller_id
    }
  },
  mounted() {
    this.loadResellerDetails()
  },
  methods: {
    async loadResellerDetails() {
      this.loading = true
      this.error = null
      
      try {
        const params = new URLSearchParams({
          assistants_per_page: this.assistantsPerPage,
          users_per_page: this.usersPerPage,
          assistants_page: this.assistantsPage,
          users_page: this.usersPage
        })
        
        const response = await axios.get(`/api/super-admin/resellers/${this.resellerId}?${params}`)
        this.resellerData = response.data.data
      } catch (error) {
        console.error('Error loading reseller details:', error)
        this.error = error.response?.data?.message || 'Failed to load reseller details'
      } finally {
        this.loading = false
      }
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString()
    },
    async loadAssistantsPage(page) {
      this.assistantsPage = page
      await this.loadResellerDetails()
      // Scroll to assistants section smoothly
      this.$nextTick(() => {
        const assistantsSection = document.querySelector('[data-section="assistants"]')
        if (assistantsSection) {
          assistantsSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
        }
      })
    },
    async loadUsersPage(page) {
      this.usersPage = page
      await this.loadResellerDetails()
      // Scroll to users section smoothly
      this.$nextTick(() => {
        const usersSection = document.querySelector('[data-section="users"]')
        if (usersSection) {
          usersSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
        }
      })
    },
    async changeAssistantsPerPage(perPage) {
      this.assistantsPerPage = perPage
      this.assistantsPage = 1
      await this.loadResellerDetails()
      // Scroll to assistants section smoothly
      this.$nextTick(() => {
        const assistantsSection = document.querySelector('[data-section="assistants"]')
        if (assistantsSection) {
          assistantsSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
        }
      })
    },
    async changeUsersPerPage(perPage) {
      this.usersPerPage = perPage
      this.usersPage = 1
      await this.loadResellerDetails()
      // Scroll to users section smoothly
      this.$nextTick(() => {
        const usersSection = document.querySelector('[data-section="users"]')
        if (usersSection) {
          usersSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
        }
      })
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
