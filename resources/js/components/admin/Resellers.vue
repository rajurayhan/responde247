<template>
  <SuperAdminLayout title="Reseller Management" subtitle="Manage resellers and their status">
    <!-- Replace all client references with reseller -->
    <!-- Add New Reseller Button -->
    <div class="flex-1 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="md:flex md:items-center md:justify-between">
            <div class="flex-1 min-w-0">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2m-2 0H7m2 0H5m2 0h2m2 0h2M9 7h6m-6 4h6m-6 4h6" />
                    </svg>
                  </div>
                </div>
                <div>
                  <h1 class="text-3xl font-bold text-gray-900">
                    Reseller Management
                  </h1>
                  <p class="mt-1 text-sm text-gray-600">
                    Manage resellers and their status
                  </p>
                </div>
              </div>
            </div>
            <div class="mt-6 md:mt-0 md:ml-4 flex items-center space-x-3">
              <button
                @click="toggleDeletedFilter"
                :class="[
                  'inline-flex items-center px-4 py-2 border rounded-md shadow-sm text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2',
                  includeDeleted 
                    ? 'border-red-300 text-red-700 bg-red-50 hover:bg-red-100 focus:ring-red-500' 
                    : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus:ring-green-500'
                ]"
              >
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                {{ includeDeleted ? 'Hide Deleted' : 'Show Deleted' }}
              </button>
              
              <button 
                @click="createReseller" 
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl shadow-sm text-white bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transform transition-all duration-150 hover:scale-105"
              >
                <svg class="-ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add New Reseller
              </button>
            </div>
          </div>
        </div>

        <!-- Search -->
        <div class="mt-8 mb-8">
          <div class="relative max-w-md">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchQuery"
              @input="performSearch"
              type="text"
              placeholder="Search resellers by name, domain, or email..."
              class="block w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 text-sm"
            />
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
        </div>

        <!-- Resellers Results -->
        <div v-else>

          <!-- Resellers Grid -->
          <div v-if="resellers.data && resellers.data.length === 0" class="text-center py-16">
            <div class="mx-auto h-24 w-24 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center mb-6">
              <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2m-2 0H7m2 0H5m2 0h2m2 0h2M9 7h6m-6 4h6m-6 4h6" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No resellers yet</h3>
            <p class="text-gray-500 mb-8 max-w-sm mx-auto">Get started by adding your first reseller organization to manage their information and status.</p>
            <button @click="createReseller" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transform transition-all duration-150 hover:scale-105">
              <svg class="-ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Create First Reseller
            </button>
          </div>

          <!-- Resellers Table -->
          <div v-else class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
            <!-- Mobile Card View (hidden on larger screens) -->
            <div class="block md:hidden">
              <div v-for="reseller in resellers.data" :key="reseller.id" class="border-b border-gray-200 last:border-b-0">
                <div 
                  class="p-4 cursor-pointer hover:bg-gray-50 transition-colors duration-150" 
                  :class="{ 'bg-red-50': reseller.is_deleted }"
                  @click="viewResellerDetails(reseller)"
                >
                  <!-- Header with Logo and Status -->
                  <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                      <div class="flex-shrink-0">
                        <div v-if="reseller.logo_address" class="h-10 w-10 rounded-lg overflow-hidden ring-1 ring-gray-200">
                          <img :src="reseller.logo_address" :alt="reseller.org_name" class="h-10 w-10 object-cover">
                        </div>
                        <div v-else class="h-10 w-10 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center ring-1 ring-gray-200">
                          <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2m-2 0H7m2 0H5m2 0h2m2 0h2M9 7h6m-6 4h6m-6 4h6" />
                          </svg>
                        </div>
                      </div>
                      <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-medium text-gray-900 truncate">
                          {{ reseller.org_name }}
                        </h3>
                      </div>
                    </div>
                    <span :class="[
                      'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                      reseller.is_deleted
                        ? 'bg-red-100 text-red-800'
                        : reseller.status === 'active' 
                          ? 'bg-green-100 text-green-800' 
                          : 'bg-red-100 text-red-800'
                    ]">
                      {{ reseller.is_deleted ? 'DELETED' : reseller.status }}
                    </span>
                  </div>

                  <!-- Details -->
                  <div class="space-y-2 mb-3">
                    <div v-if="reseller.company_email" class="text-xs text-gray-600">
                      <span class="font-medium">Email:</span> {{ reseller.company_email }}
                    </div>
                    <div v-if="reseller.domain" class="text-xs text-gray-600">
                      <span class="font-medium">Domain:</span> {{ reseller.domain }}
                    </div>
                    <div class="text-xs text-gray-500">
                      <span class="font-medium">Created:</span> {{ formatDate(reseller.created_at) }}
                    </div>
                  </div>

                  <!-- Actions -->
                  <div class="flex items-center justify-between">
                    <button 
                      v-if="!reseller.is_deleted"
                      @click.stop="toggleResellerStatus(reseller)" 
                      :class="[
                        'inline-flex items-center px-2 py-1 text-xs font-medium rounded-md transition-colors',
                        reseller.status === 'active' 
                          ? 'text-red-700 bg-red-50 hover:bg-red-100' 
                          : 'text-green-700 bg-green-50 hover:bg-green-100'
                      ]"
                    >
                      {{ reseller.status === 'active' ? 'Deactivate' : 'Activate' }}
                    </button>
                    
                    <div class="flex items-center space-x-1">
                      <button 
                        @click.stop="viewResellerDetails(reseller)" 
                        class="p-1 text-green-600 hover:text-green-700 hover:bg-green-50 rounded-md"
                        title="View details"
                      >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                      </button>
                      <button 
                        v-if="!reseller.is_deleted"
                        @click.stop="editReseller(reseller)" 
                        class="p-1 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-md"
                        title="Edit reseller"
                      >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      <button 
                        v-if="!reseller.is_deleted"
                        @click.stop="deleteReseller(reseller)" 
                        class="p-1 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-md"
                        title="Delete reseller"
                      >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                      <button 
                        v-if="reseller.is_deleted"
                        @click.stop="restoreReseller(reseller)" 
                        class="p-1 text-green-600 hover:text-green-700 hover:bg-green-50 rounded-md"
                        title="Restore reseller"
                      >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Desktop Table View (hidden on mobile) -->
            <div class="hidden md:block overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Organization
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Contact
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Domain
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Created
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr 
                    v-for="reseller in resellers.data" 
                    :key="reseller.id" 
                    @click="viewResellerDetails(reseller)"
                    class="hover:bg-gray-50 transition-colors duration-150 cursor-pointer"
                    :class="{ 'bg-red-50': reseller.is_deleted }"
                  >
                    <!-- Organization -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                          <div v-if="reseller.logo_address" class="h-10 w-10 rounded-lg overflow-hidden ring-1 ring-gray-200">
                            <img :src="reseller.logo_address" :alt="reseller.org_name" class="h-10 w-10 object-cover">
                          </div>
                          <div v-else class="h-10 w-10 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center ring-1 ring-gray-200">
                            <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2m-2 0H7m2 0H5m2 0h2m2 0h2M9 7h6m-6 4h6m-6 4h6" />
                            </svg>
                          </div>
                        </div>
                        <div class="ml-4">
                          <div class="text-sm font-medium text-gray-900">
                            {{ reseller.org_name }}
                          </div>
                        </div>
                      </div>
                    </td>

                    <!-- Contact -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">
                        {{ reseller.company_email || 'N/A' }}
                      </div>
                    </td>

                    <!-- Domain -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">
                        {{ reseller.domain || 'N/A' }}
                      </div>
                    </td>

                    <!-- Status -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        reseller.is_deleted
                          ? 'bg-red-100 text-red-800'
                          : reseller.status === 'active' 
                            ? 'bg-green-100 text-green-800' 
                            : 'bg-red-100 text-red-800'
                      ]">
                        <svg 
                          :class="[
                            'w-1.5 h-1.5 mr-1',
                            reseller.is_deleted 
                              ? 'text-red-400'
                              : reseller.status === 'active' ? 'text-green-400' : 'text-red-400'
                          ]" 
                          fill="currentColor" 
                          viewBox="0 0 8 8"
                        >
                          <circle cx="4" cy="4" r="3" />
                        </svg>
                        {{ reseller.is_deleted ? 'DELETED' : reseller.status }}
                      </span>
                    </td>

                    <!-- Created -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatDate(reseller.created_at) }}
                    </td>

                    <!-- Actions -->
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div class="flex items-center justify-end space-x-2">
                        <!-- View Button -->
                        <button 
                          @click.stop="viewResellerDetails(reseller)" 
                          class="text-green-600 hover:text-green-900 p-1 rounded-md hover:bg-green-50 transition-colors duration-200"
                          title="View details"
                        >
                          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                        </button>

                        <!-- Edit Button -->
                        <button 
                          v-if="!reseller.is_deleted"
                          @click.stop="editReseller(reseller)" 
                          class="text-blue-600 hover:text-blue-900 p-1 rounded-md hover:bg-blue-50 transition-colors duration-200"
                          title="Edit reseller"
                        >
                          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </button>

                        <!-- Toggle Status Button -->
                        <button 
                          v-if="!reseller.is_deleted"
                          @click.stop="toggleResellerStatus(reseller)" 
                          :class="[
                            'p-1 rounded-md transition-colors duration-200',
                            reseller.status === 'active' 
                              ? 'text-red-600 hover:text-red-900 hover:bg-red-50' 
                              : 'text-green-600 hover:text-green-900 hover:bg-green-50'
                          ]"
                          :title="reseller.status === 'active' ? 'Deactivate reseller' : 'Activate reseller'"
                        >
                          <svg v-if="reseller.status === 'active'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                          <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                        </button>

                        <!-- Delete Button -->
                        <button 
                          v-if="!reseller.is_deleted"
                          @click.stop="deleteReseller(reseller)" 
                          class="text-red-600 hover:text-red-900 p-1 rounded-md hover:bg-red-50 transition-colors duration-200"
                          title="Delete reseller"
                        >
                          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                          </svg>
                        </button>

                        <!-- Restore Button -->
                        <button 
                          v-if="reseller.is_deleted"
                          @click.stop="restoreReseller(reseller)" 
                          class="text-green-600 hover:text-green-900 p-1 rounded-md hover:bg-green-50 transition-colors duration-200"
                          title="Restore reseller"
                        >
                          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="resellers.last_page > 1" class="mt-8 bg-white rounded-xl border border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <p class="text-sm text-gray-600">
                  Showing <span class="font-semibold text-gray-900">{{ resellers.from }}</span> to <span class="font-semibold text-gray-900">{{ resellers.to }}</span> of <span class="font-semibold text-gray-900">{{ resellers.total }}</span> resellers
                </p>
              </div>
              
              <div class="flex items-center space-x-1">
                <button 
                  @click="goToPage(resellers.current_page - 1)" 
                  :disabled="resellers.current_page <= 1" 
                  class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7" />
                  </svg>
                  Previous
                </button>
                
                <div class="hidden sm:flex items-center space-x-1 mx-2">
                  <button 
                    v-for="page in visiblePages" 
                    :key="page" 
                    @click="goToPage(page)" 
                    :class="[
                      'inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                      page === resellers.current_page 
                        ? 'bg-green-100 text-green-700 border border-green-300' 
                        : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                    ]"
                  >
                    {{ page }}
                  </button>
                </div>
                
                <button 
                  @click="goToPage(resellers.current_page + 1)" 
                  :disabled="resellers.current_page >= resellers.last_page" 
                  class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  Next
                  <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reseller Form Modal -->
    <ResellerFormModal
      :show="showModal"
      :reseller="selectedReseller"
      @close="closeModal"
      @saved="handleResellerSaved"
    />
  </SuperAdminLayout>
</template>

<script>
import { ref, onMounted, computed, getCurrentInstance } from 'vue'
import Navigation from '../shared/Navigation.vue'
import Swal from 'sweetalert2'
import ResellerFormModal from './ResellerFormModal.vue'
import SuperAdminLayout from '../layouts/SuperAdminLayout.vue'

export default {
  name: 'Resellers',
  components: {
    Navigation,
    SuperAdminLayout,
    ResellerFormModal
  },
  setup() {
    // Get the current instance to access $toast
    const { proxy } = getCurrentInstance()
    const loading = ref(true)
    const resellers = ref({
      data: [],
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
      from: null,
      to: null
    })
    const searchQuery = ref('')
    const showModal = ref(false)
    const selectedReseller = ref(null)
    const searchTimeout = ref(null)
    const includeDeleted = ref(false)

    const fetchResellers = async (page = 1, search = '') => {
      loading.value = true
      try {
        let url = `/api/super-admin/resellers?page=${page}&include_deleted=${includeDeleted.value ? '1' : '0'}`
        if (search) {
          url += `&search=${encodeURIComponent(search)}`
        }

        const response = await fetch(url, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
          }
        })

        if (!response.ok) {
          throw new Error('Failed to fetch resellers')
        }

        const data = await response.json()
        if (data.success) {
          resellers.value = {
            data: data.data,
            current_page: data.meta.current_page,
            last_page: data.meta.last_page,
            per_page: data.meta.per_page,
            total: data.meta.total,
            from: data.meta.from,
            to: data.meta.to
          }
        } else {
          throw new Error(data.message || 'Failed to fetch resellers')
        }
      } catch (error) {
        console.error('Error fetching resellers:', error)
        if (proxy.$toast && proxy.$toast.error) {
          proxy.$toast.error('Error fetching resellers')
        }
      } finally {
        loading.value = false
      }
    }

    const performSearch = () => {
      if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
      }
      searchTimeout.value = setTimeout(() => {
        fetchResellers(1, searchQuery.value)
      }, 300)
    }

    const toggleDeletedFilter = () => {
      includeDeleted.value = !includeDeleted.value
      fetchResellers(1, searchQuery.value)
    }

    const createReseller = () => {
      selectedReseller.value = null
      showModal.value = true
    }

    const editReseller = (reseller) => {
      selectedReseller.value = { ...reseller }
      showModal.value = true
    }

    const viewResellerDetails = (reseller) => {
      // Navigate to reseller details page
      window.location.href = `/super-admin/resellers/${reseller.id}`
    }

    const toggleResellerStatus = async (reseller) => {
      const newStatus = reseller.status === 'active' ? 'inactive' : 'active'
      const action = reseller.status === 'active' ? 'deactivate' : 'activate'
      
      const result = await Swal.fire({
        title: `${action.charAt(0).toUpperCase() + action.slice(1)} Reseller?`,
        text: `Are you sure you want to ${action} "${reseller.org_name}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: reseller.status === 'active' ? '#ef4444' : '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Yes, ${action} it!`
      })

      if (result.isConfirmed) {
        try {
          const response = await fetch(`/api/admin/resellers/${reseller.id}/toggle-status`, {
            method: 'PUT',
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`,
              'Accept': 'application/json',
            }
          })

          const data = await response.json()
          if (data.success) {
            if (proxy.$toast && proxy.$toast.success) {
              proxy.$toast.success(`Reseller ${action}d successfully`)
            }
            // Update the reseller in the list
            const index = resellers.value.data.findIndex(r => r.id === reseller.id)
            if (index !== -1) {
              resellers.value.data[index] = data.data
            }
          } else {
            throw new Error(data.message || `Failed to ${action} reseller`)
          }
        } catch (error) {
          console.error(`Error ${action}ing reseller:`, error)
          if (proxy.$toast && proxy.$toast.error) {
            proxy.$toast.error(`Error ${action}ing reseller`)
          }
        }
      }
    }

    const deleteReseller = async (reseller) => {
      const result = await Swal.fire({
        title: 'Delete Reseller?',
        text: `Are you sure you want to delete "${reseller.org_name}"? This will soft delete the reseller and can be restored later.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
      })

      if (result.isConfirmed) {
        try {
          const response = await fetch(`/api/super-admin/resellers/${reseller.id}`, {
            method: 'DELETE',
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`,
              'Accept': 'application/json',
            }
          })

          const data = await response.json()
          if (data.success) {
            // Show success message using SweetAlert2
            await Swal.fire({
              title: 'Success!',
              text: 'Reseller deleted successfully',
              icon: 'success',
              timer: 2000,
              showConfirmButton: false
            })
            // Remove the reseller from the list
            const index = resellers.value.data.findIndex(r => r.id === reseller.id)
            if (index !== -1) {
              resellers.value.data.splice(index, 1)
            }
          } else {
            throw new Error(data.message || 'Failed to delete reseller')
          }
        } catch (error) {
          console.error('Error deleting reseller:', error)
          // Show error message using SweetAlert2
          await Swal.fire({
            title: 'Error!',
            text: error.message || 'Error deleting reseller',
            icon: 'error',
            confirmButtonText: 'OK'
          })
        }
      }
    }

    const restoreReseller = async (reseller) => {
      const result = await Swal.fire({
        title: 'Restore Reseller',
        text: `Are you sure you want to restore "${reseller.org_name}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, restore reseller',
        cancelButtonText: 'Cancel'
      })

      if (!result.isConfirmed) {
        return
      }

      try {
        const response = await fetch(`/api/super-admin/resellers/${reseller.id}/restore`, {
          method: 'PATCH',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        })

        const data = await response.json()

        if (data.success) {
          await Swal.fire({
            title: 'Success!',
            text: 'Reseller restored successfully',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
          })
          fetchResellers()
        } else {
          await Swal.fire({
            title: 'Error',
            text: data.message || 'Failed to restore reseller',
            icon: 'error'
          })
        }
      } catch (error) {
        console.error('Error restoring reseller:', error)
        await Swal.fire({
          title: 'Error',
          text: 'Error restoring reseller',
          icon: 'error'
        })
      }
    }

    const closeModal = () => {
      showModal.value = false
      selectedReseller.value = null
    }

    const handleResellerSaved = () => {
      closeModal()
      fetchResellers(resellers.value.current_page || 1, searchQuery.value)
    }

    const goToPage = (page) => {
      if (page >= 1 && page <= resellers.value.last_page) {
        fetchResellers(page, searchQuery.value)
      }
    }

    const visiblePages = computed(() => {
      const current = resellers.value.current_page || 1
      const last = resellers.value.last_page || 1
      const pages = []
      
      let start = Math.max(1, current - 2)
      let end = Math.min(last, current + 2)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    })

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }

    onMounted(() => {
      fetchResellers()
    })

    return {
      loading,
      resellers,
      searchQuery,
      showModal,
      selectedReseller,
      includeDeleted,
      fetchResellers,
      performSearch,
      toggleDeletedFilter,
      createReseller,
      editReseller,
      viewResellerDetails,
      toggleResellerStatus,
      deleteReseller,
      restoreReseller,
      closeModal,
      handleResellerSaved,
      goToPage,
      visiblePages,
      formatDate
    }
  }
}
</script>
