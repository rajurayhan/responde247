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
              All Voice Assistants
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Manage all voice assistants in the system
            </p>
          </div>
          <div class="mt-4 flex md:mt-0 md:ml-4">
            <button 
              @click="createAssistant" 
              :disabled="subscriptionInfo && !subscriptionInfo.hasSubscription && !isSuperAdmin"
              class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              <span v-if="subscriptionInfo && !subscriptionInfo.hasSubscription && !isSuperAdmin">Subscribe to Create</span>
              <span v-else>Create Assistant</span>
            </button>
          </div>
        </div>

        <!-- No Subscription Banner for Reseller Admins -->
        <div v-if="subscriptionInfo && !subscriptionInfo.hasSubscription && isResellerAdmin" class="mt-6 bg-red-50 border border-red-200 rounded-md p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">
                No Active Subscription
              </h3>
              <div class="mt-2 text-sm text-red-700">
                <p>Your reseller does not have an active subscription. You need an active subscription to create and manage voice assistants.</p>
              </div>
              <div class="mt-4">
                <router-link :to="isResellerAdmin ? '/reseller-billing' : '/subscription'" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-red-800 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                  Subscribe Now
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- Search and Sort Controls -->
        <div class="mt-6 flex flex-col space-y-4">
          <!-- Search Controls -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Assistant Name Search -->
            <div class="sm:col-span-1">
              <label for="search" class="sr-only">Search assistants</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
                <input
                  id="search"
                  v-model="searchQuery"
                  @input="debounceSearch"
                  type="text"
                  placeholder="Search assistants by name..."
                  class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-green-500 focus:border-green-500 sm:text-sm"
                />
                <div v-if="searchQuery" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Active
                  </span>
                </div>
              </div>
            </div>

            <!-- User Search -->
            <div class="sm:col-span-1">
              <label for="userSearch" class="sr-only">Search by owner</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
                <input
                  id="userSearch"
                  v-model="userSearchQuery"
                  @input="debounceUserSearch"
                  type="text"
                  placeholder="Search by owner name or email..."
                  class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-green-500 focus:border-green-500 sm:text-sm"
                />
                <div v-if="userSearchQuery" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Active
                  </span>
                </div>
              </div>
            </div>

            <!-- Phone Number Search -->
            <div class="sm:col-span-1">
              <label for="phoneSearch" class="sr-only">Search by phone number</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                </div>
                <input
                  id="phoneSearch"
                  v-model="phoneSearchQuery"
                  @input="debouncePhoneSearch"
                  type="text"
                  placeholder="Search by phone number..."
                  class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-green-500 focus:border-green-500 sm:text-sm"
                />
                <div v-if="phoneSearchQuery" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Active
                  </span>
                </div>
              </div>
            </div>

            <!-- Type Filter -->
            <div class="sm:col-span-1">
              <label for="typeFilter" class="sr-only">Filter by type</label>
              <div class="relative">
                <select
                  id="typeFilter"
                  v-model="typeFilter"
                  @change="onTypeFilterChange"
                  class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md"
                >
                  <option value="">All Types</option>
                  <option value="demo">Demo</option>
                  <option value="production">Production</option>
                </select>
                <div v-if="typeFilter" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Active
                  </span>
                </div>
              </div>
            </div>

            <!-- Clear Filters Button -->
            <div v-if="activeFiltersCount > 0" class="sm:col-span-1">
              <button
                @click="clearAllFilters"
                class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              >
                <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Clear Filters
                <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                  {{ activeFiltersCount }}
                </span>
              </button>
            </div>
          </div>

          <!-- View Toggle and Sort -->
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
            <!-- View Toggle -->
            <div class="flex items-center bg-gray-100 rounded-lg p-1">
              <button
                @click="setViewMode('grid')"
                :class="[
                  'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                  viewMode === 'grid'
                    ? 'bg-white text-gray-900 shadow-sm'
                    : 'text-gray-600 hover:text-gray-900'
                ]"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
              </button>
              <button
                @click="setViewMode('list')"
                :class="[
                  'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                  viewMode === 'list'
                    ? 'bg-white text-gray-900 shadow-sm'
                    : 'text-gray-600 hover:text-gray-900'
                ]"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
              </button>
            </div>

            <!-- Sort -->
            <div class="flex items-center space-x-4">
              <label for="sort" class="text-sm font-medium text-gray-700">Sort by:</label>
              <select
                id="sort"
                v-model="sortBy"
                @change="loadAssistants"
                class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md"
              >
                <option value="name">Name</option>
                <option value="created_at">Created Date</option>
              </select>
              <button
                @click="toggleSortOrder"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              >
                <svg v-if="sortOrder === 'asc'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                </svg>
                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="mt-8 flex justify-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
        </div>

        <!-- Filter Summary -->
        <div v-else-if="activeFiltersCount > 0" class="mt-6 bg-blue-50 border border-blue-200 rounded-md p-4">
          <div class="flex items-center">
            <svg class="flex-shrink-0 h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z" />
            </svg>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-blue-800">Active Filters</h3>
              <div class="mt-2 text-sm text-blue-700">
                <div class="flex flex-wrap gap-2">
                  <span v-if="searchQuery" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    Name: {{ searchQuery }}
                  </span>
                  <span v-if="userSearchQuery" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    Owner: {{ userSearchQuery }}
                  </span>
                  <span v-if="phoneSearchQuery" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    Phone: {{ phoneSearchQuery }}
                  </span>
                  <span v-if="typeFilter" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    Type: {{ typeFilter === 'demo' ? 'Demo' : 'Production' }}
                  </span>
                </div>
              </div>
            </div>
            <div class="ml-auto">
              <button
                @click="clearAllFilters"
                class="inline-flex items-center px-3 py-2 border border-blue-300 shadow-sm text-sm leading-4 font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Clear All
              </button>
            </div>
          </div>
        </div>

        <!-- Assistants Section -->
        <div v-if="!loading" class="mt-8">
          <!-- Empty State -->
          <div v-if="assistants.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No assistants found</h3>
            <p class="mt-1 text-sm text-gray-500">
              {{ activeFiltersCount > 0 ? 'No assistants match your current filters.' : 'No assistants have been created yet.' }}
            </p>
            <div class="mt-6">
              <button 
                @click="createAssistant" 
                :disabled="subscriptionInfo && !subscriptionInfo.hasSubscription && !isSuperAdmin"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span v-if="subscriptionInfo && !subscriptionInfo.hasSubscription && !isSuperAdmin">Subscribe to Create</span>
                <span v-else>Create Assistant</span>
              </button>
            </div>
          </div>

          <!-- Assistants List -->
          <div v-else>
            <!-- Grid View -->
            <div v-if="viewMode === 'grid'" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
              <div v-for="assistant in paginatedAssistants" :key="assistant.vapi_assistant_id" class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                  <div class="flex items-center">
                    <div class="flex-shrink-0">
                      <div class="h-10 w-10 rounded-full bg-green-500 flex items-center justify-center">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                      </div>
                    </div>
                    <div class="ml-4 flex-1">
                      <h3 class="text-lg font-medium text-gray-900">{{ assistant.name }}</h3>
                      <p class="text-sm text-gray-500">Assistant ID: {{ assistant.vapi_assistant_id }}</p>
                    </div>
                  </div>
                  
                  <div class="mt-4">
                    <div class="flex items-center justify-between text-sm">
                      <span class="text-gray-500">Owner:</span>
                      <span class="font-medium text-gray-900">{{ assistant.user?.name || 'Unknown' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm mt-1">
                      <span class="text-gray-500">Created:</span>
                      <span class="font-medium text-gray-900">{{ formatDate(assistant.created_at) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm mt-1">
                      <span class="text-gray-500">Created By:</span>
                      <span class="font-medium text-gray-900">{{ assistant.creator?.name || 'Unknown' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm mt-1">
                      <span class="text-gray-500">Type:</span>
                      <span 
                        :class="[
                          'px-2 py-1 rounded-full text-xs font-medium',
                          assistant.type === 'demo' 
                            ? 'bg-blue-100 text-blue-800' 
                            : 'bg-green-100 text-green-800'
                        ]"
                      >
                        {{ assistant.type === 'demo' ? 'Demo' : 'Production' }}
                      </span>
                    </div>
                    <div class="flex items-center justify-between text-sm mt-1">
                      <span class="text-gray-500">Phone:</span>
                      <span class="font-medium text-gray-900">{{ assistant.phone_number || 'Not set' }}</span>
                    </div>
                  </div>

                  <div class="mt-6 flex space-x-3">
                    <button @click="editAssistant(assistant)" class="flex-1 bg-green-50 border border-green-300 text-green-700 hover:bg-green-100 px-3 py-2 rounded-md text-sm font-medium">
                      Edit
                    </button>
                    <button @click="viewStats(assistant)" class="flex-1 bg-blue-50 border border-blue-300 text-blue-700 hover:bg-blue-100 px-3 py-2 rounded-md text-sm font-medium">
                      Stats
                    </button>
                    <button 
                      @click="deleteAssistant(assistant.vapi_assistant_id)" 
                      :disabled="deletingAssistant === assistant.vapi_assistant_id"
                      class="flex-1 bg-red-50 border border-red-300 text-red-700 hover:bg-red-100 px-3 py-2 rounded-md text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {{ deletingAssistant === assistant.vapi_assistant_id ? 'Deleting...' : 'Delete' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- List View -->
            <div v-else class="bg-white shadow overflow-hidden sm:rounded-md">
              <ul class="divide-y divide-gray-200">
                <li v-for="assistant in paginatedAssistants" :key="assistant.vapi_assistant_id">
                  <div class="px-4 py-4 sm:px-6">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center">
                        <div class="flex-shrink-0">
                          <div class="h-10 w-10 rounded-full bg-green-500 flex items-center justify-center">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                          </div>
                        </div>
                        <div class="ml-4">
                          <div class="flex items-center">
                            <h3 class="text-lg font-medium text-gray-900">{{ assistant.name }}</h3>
                            <span 
                              :class="[
                                'ml-2 px-2 py-1 rounded-full text-xs font-medium',
                                assistant.type === 'demo' 
                                  ? 'bg-blue-100 text-blue-800' 
                                  : 'bg-green-100 text-green-800'
                              ]"
                            >
                              {{ assistant.type === 'demo' ? 'Demo' : 'Production' }}
                            </span>
                          </div>
                          <p class="text-sm text-gray-500">ID: {{ assistant.vapi_assistant_id }}</p>
                        </div>
                      </div>
                      <div class="flex items-center space-x-3">
                        <button @click="editAssistant(assistant)" class="bg-green-50 border border-green-300 text-green-700 hover:bg-green-100 px-3 py-2 rounded-md text-sm font-medium">
                          Edit
                        </button>
                        <button @click="viewStats(assistant)" class="bg-blue-50 border border-blue-300 text-blue-700 hover:bg-blue-100 px-3 py-2 rounded-md text-sm font-medium">
                          Stats
                        </button>
                        <button 
                          @click="deleteAssistant(assistant.vapi_assistant_id)" 
                          :disabled="deletingAssistant === assistant.vapi_assistant_id"
                          class="bg-red-50 border border-red-300 text-red-700 hover:bg-red-100 px-3 py-2 rounded-md text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          {{ deletingAssistant === assistant.vapi_assistant_id ? 'Deleting...' : 'Delete' }}
                        </button>
                      </div>
                    </div>
                    <div class="mt-4 sm:flex sm:justify-between">
                      <div class="sm:flex">
                        <div class="flex items-center text-sm text-gray-500 sm:mr-6">
                          <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                          </svg>
                          {{ assistant.user?.name || 'Unknown' }}
                        </div>
                        <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0 sm:mr-6">
                          <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                          </svg>
                          {{ formatDate(assistant.created_at) }}
                        </div>
                        <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0 sm:mr-6">
                          <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                          </svg>
                          {{ assistant.creator?.name || 'Unknown' }}
                        </div>
                      </div>
                      <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                        <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        {{ assistant.phone_number || 'Not set' }}
                      </div>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="assistants.length > 0" class="mt-8 flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
              <button 
                @click="previousPage" 
                :disabled="currentPage === 1"
                :class="[
                  'relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md',
                  currentPage === 1 
                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                    : 'bg-white text-gray-700 hover:bg-gray-50'
                ]"
              >
                Previous
              </button>
              <button 
                @click="nextPage" 
                :disabled="currentPage >= totalPages"
                :class="[
                  'ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md',
                  currentPage >= totalPages 
                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                    : 'bg-white text-gray-700 hover:bg-gray-50'
                ]"
              >
                Next
              </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-gray-700">
                  Showing 
                  <span class="font-medium">{{ pagination.from }}</span>
                  to 
                  <span class="font-medium">{{ pagination.to }}</span>
                  of 
                  <span class="font-medium">{{ pagination.total }}</span>
                  results
                </p>
              </div>
              <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                  <button 
                    @click="previousPage" 
                    :disabled="currentPage === 1"
                    :class="[
                      'relative inline-flex items-center px-2 py-2 rounded-l-md border text-sm font-medium',
                      currentPage === 1 
                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                        : 'bg-white text-gray-500 hover:bg-gray-50'
                    ]"
                  >
                    <span class="sr-only">Previous</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  
                  <template v-for="page in visiblePages" :key="page">
                    <button 
                      v-if="page !== '...'" 
                      @click="goToPage(page)"
                      :class="[
                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                        page === currentPage 
                          ? 'z-10 bg-green-50 border-green-500 text-green-600' 
                          : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                      ]"
                    >
                      {{ page }}
                    </button>
                    <span v-else class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                      ...
                    </span>
                  </template>
                  
                  <button 
                    @click="nextPage" 
                    :disabled="currentPage >= totalPages"
                    :class="[
                      'relative inline-flex items-center px-2 py-2 rounded-r-md border text-sm font-medium',
                      currentPage >= totalPages 
                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                        : 'bg-white text-gray-500 hover:bg-gray-50'
                    ]"
                  >
                    <span class="sr-only">Next</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Modal -->
    <div v-if="showStatsModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
          <div>
            <div class="mt-3 text-center sm:mt-5">
              <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                Statistics for {{ selectedAssistant?.name }}
              </h3>
              <div class="mt-2">
                <div v-if="stats" class="space-y-4">
                  <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Total Calls:</span>
                    <span class="text-sm text-gray-900">{{ stats.totalCalls || 0 }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Successful Calls:</span>
                    <span class="text-sm text-gray-900">{{ stats.successfulCalls || 0 }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Average Duration:</span>
                    <span class="text-sm text-gray-900">{{ stats.averageDuration || 0 }}s</span>
                  </div>
                </div>
                <div v-else class="text-sm text-gray-500">
                  No statistics available for this assistant.
                </div>
              </div>
            </div>
          </div>
          <div class="mt-5 sm:mt-6">
            <button @click="closeStatsModal" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:text-sm">
              Close
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <SimpleFooter />
  </div>
</template>

<script>
import Navigation from '../shared/Navigation.vue'
import SimpleFooter from '../shared/SimpleFooter.vue'
import { showDeleteConfirm, showSuccess, showError } from '../../utils/sweetalert.js'
import axios from 'axios'

export default {
  name: 'AdminAssistants',
  components: {
    Navigation,
    SimpleFooter
  },
  data() {
    return {
      loading: true,
      assistants: [],
      currentPage: 1,
      itemsPerPage: 9,
      showStatsModal: false,
      selectedAssistant: null,
      stats: null,
      searchQuery: '',
      userSearchQuery: '',
      phoneSearchQuery: '',
      sortBy: 'name',
      sortOrder: 'asc',
      searchTimeout: null,
      userSearchTimeout: null,
      phoneSearchTimeout: null,
      deletingAssistant: null, // Added for loading state
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 9,
        total: 0,
        from: 0,
        to: 0
      },
      viewMode: 'grid', // 'grid' or 'list'
      typeFilter: '', // 'demo' or 'production'
      subscriptionInfo: null // Subscription info for reseller admins
    }
  },
  computed: {
    totalPages() {
      return this.pagination.last_page;
    },
    isSuperAdmin() {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      return user.role === 'admin' || user.role === 'content_admin';
    },
    isResellerAdmin() {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      return user.role === 'reseller_admin';
    },
    startIndex() {
      return this.pagination.from - 1;
    },
    endIndex() {
      return this.pagination.to;
    },
    paginatedAssistants() {
      return this.assistants;
    },
    visiblePages() {
      const pages = [];
      const totalPages = this.totalPages;
      const current = this.currentPage;
      
      if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) {
          pages.push(i);
        }
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) {
            pages.push(i);
          }
          pages.push('...');
          pages.push(totalPages);
        } else if (current >= totalPages - 3) {
          pages.push(1);
          pages.push('...');
          for (let i = totalPages - 4; i <= totalPages; i++) {
            pages.push(i);
          }
        } else {
          pages.push(1);
          pages.push('...');
          for (let i = current - 1; i <= current + 1; i++) {
            pages.push(i);
          }
          pages.push('...');
          pages.push(totalPages);
        }
      }
      
      return pages;
    },
    
    activeFiltersCount() {
      let count = 0;
      if (this.searchQuery) count++;
      if (this.userSearchQuery) count++;
      if (this.phoneSearchQuery) count++;
      if (this.typeFilter) count++;
      return count;
    }
  },
  async mounted() {
    await this.loadAssistants();
    await this.loadSubscriptionInfo(); // Load subscription info for reseller admins
  },
  methods: {
    async loadAssistants() {
      try {
        this.loading = true;
        const params = {
          sort_by: this.sortBy,
          sort_order: this.sortOrder,
          search: this.searchQuery,
          user_search: this.userSearchQuery,
          phone_search: this.phoneSearchQuery,
          type: this.typeFilter,
          page: this.currentPage,
          per_page: this.itemsPerPage
        };

        // Remove empty parameters to avoid sending empty strings
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null || params[key] === undefined) {
            delete params[key];
          }
        });

        const response = await axios.get('/api/admin/assistants', { params });
        
        this.assistants = response.data.data || [];
        this.pagination = response.data.pagination || {
          current_page: 1,
          last_page: 1,
          per_page: 9,
          total: 0,
          from: 0,
          to: 0
        };

      } catch (error) {
        if (error.response && error.response.status === 401) {
          this.$router.push('/login');
        }
      } finally {
        this.loading = false;
      }
    },
    
    async loadSubscriptionInfo() {
      try {
        // Don't load subscription info for super admin users
        if (this.isSuperAdmin) {
          this.subscriptionInfo = null;
          return;
        }
        
        const response = await axios.get('/api/subscriptions/usage');
        const usage = response.data.data;
        
        if (usage && usage.package && usage.subscription) {
          // Check if subscription is actually active (not pending)
          const isActiveSubscription = usage.subscription.status === 'active';
          
          this.subscriptionInfo = {
            hasSubscription: isActiveSubscription,
            plan: usage.package.name || 'No Plan',
            used: usage.assistants.used || 0,
            limit: usage.assistants.limit || 0,
            subscriptionStatus: usage.subscription.status
          };
        } else {
          this.subscriptionInfo = {
            hasSubscription: false,
            plan: 'No Plan',
            used: 0,
            limit: 0,
            subscriptionStatus: 'none'
          };
        }
      } catch (error) {
        console.error('Error loading subscription info:', error);
        this.subscriptionInfo = {
          hasSubscription: false,
          plan: 'No Plan',
          used: 0,
          limit: 0,
          subscriptionStatus: 'none'
        };
      }
    },
    
    createAssistant() {
      this.$router.push('/assistants/create');
    },
    
    editAssistant(assistant) {
      this.$router.push(`/assistants/${assistant.vapi_assistant_id}/edit`);
    },
    
    async viewStats(assistant) {
      this.selectedAssistant = assistant;
      this.showStatsModal = true;
      
      try {
        const response = await axios.get(`/api/assistants/${assistant.vapi_assistant_id}`);
        this.stats = response.data.data.vapi_data?.stats || null;
      } catch (error) {
        this.stats = null;
      }
    },
    
    async deleteAssistant(assistantId) {
      const result = await showDeleteConfirm(
        'Delete Assistant',
        'Are you sure you want to delete this assistant? This action cannot be undone.'
      );
      
      if (!result.isConfirmed) {
        return;
      }
      
      try {
        this.deletingAssistant = assistantId; // Set loading state
        const response = await axios.delete(`/api/assistants/${assistantId}`);
        
        if (response.status === 200) {
            const result = response.data;
            await this.loadAssistants();
        } else {
          const errorData = response.data;
          await showError('Delete Failed', errorData.message || 'Failed to delete assistant. Please try again.');
        }
      } catch (error) {
        await showError('Error', 'Failed to delete assistant: ' + error.message);
      } finally {
        this.deletingAssistant = null; // Reset loading state
      }
    },
    
    closeStatsModal() {
      this.showStatsModal = false;
      this.selectedAssistant = null;
      this.stats = null;
    },
    
    formatDate(date) {
      return new Date(date).toLocaleDateString();
    },
    
    previousPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
        this.loadAssistants();
      }
    },
    
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
        this.loadAssistants();
      }
    },
    
    goToPage(page) {
      this.currentPage = page;
      this.loadAssistants();
    },

    debounceSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.currentPage = 1; // Reset to first page on new search
        this.loadAssistants();
      }, 500);
    },

    debounceUserSearch() {
      clearTimeout(this.userSearchTimeout);
      this.userSearchTimeout = setTimeout(() => {
        this.currentPage = 1; // Reset to first page on new search
        this.loadAssistants();
      }, 500);
    },

    debouncePhoneSearch() {
      clearTimeout(this.phoneSearchTimeout);
      this.phoneSearchTimeout = setTimeout(() => {
        this.currentPage = 1; // Reset to first page when phone search changes
        this.loadAssistants();
      }, 500);
    },

    toggleSortOrder() {
      this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
      this.currentPage = 1; // Reset to first page on sort change
      this.loadAssistants();
    },

    setViewMode(mode) {
      this.viewMode = mode;
    },

    onTypeFilterChange() {
      this.currentPage = 1; // Reset to first page when type filter changes
      this.loadAssistants();
    },

    clearAllFilters() {
      this.searchQuery = '';
      this.userSearchQuery = '';
      this.phoneSearchQuery = '';
      this.typeFilter = '';
      this.currentPage = 1; // Reset to first page when all filters are cleared
      this.loadAssistants();
    }
  }
}
</script> 