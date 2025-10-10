<template>
  <SuperAdminLayout title="User Management" subtitle="Manage users across all resellers">
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
      <!-- Total Users -->
      <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-blue-600">
              <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-2xl font-semibold text-gray-900">{{ stats.total_users }}</p>
            <p class="text-sm font-medium text-gray-500">Total Users</p>
          </div>
        </div>
      </div>

      <!-- Active Users -->
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
            <p class="text-2xl font-semibold text-gray-900">{{ stats.active_users }}</p>
            <p class="text-sm font-medium text-gray-500">Active Users</p>
          </div>
        </div>
      </div>

      <!-- Deleted Users -->
      <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-red-500 to-red-600">
              <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-2xl font-semibold text-gray-900">{{ stats.deleted_users }}</p>
            <p class="text-sm font-medium text-gray-500">Deleted Users</p>
          </div>
        </div>
      </div>

      <!-- Users by Reseller -->
      <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-purple-600">
              <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2m-2 0H7m2 0H5m2 0h2m2 0h2M9 7h6m-6 4h6m-6 4h6" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-2xl font-semibold text-gray-900">{{ stats.users_by_reseller?.length || 0 }}</p>
            <p class="text-sm font-medium text-gray-500">Resellers</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Bar -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center space-x-4">
        <button
          @click="createUser"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          Add User
        </button>
        
        <button
          @click="toggleDeletedFilter"
          :class="[
            'inline-flex items-center px-4 py-2 border rounded-md shadow-sm text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2',
            includeDeleted 
              ? 'border-red-300 text-red-700 bg-red-50 hover:bg-red-100 focus:ring-red-500' 
              : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus:ring-blue-500'
          ]"
        >
          <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          {{ includeDeleted ? 'Hide Deleted' : 'Show Deleted' }}
        </button>
      </div>

      <div class="mt-4 sm:mt-0">
        <div class="flex items-center space-x-2">
          <label for="perPage" class="text-sm font-medium text-gray-700">Per Page:</label>
          <select
            id="perPage"
            v-model="pagination.perPage"
            @change="loadUsers"
            class="px-3 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
          >
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white p-4 rounded-lg shadow">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Search -->
        <div class="lg:col-span-2">
          <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input
            id="search"
            v-model="filters.search"
            @input="debouncedSearch"
            type="text"
            placeholder="Search by name, email, or company..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
          />
        </div>
        
        <!-- Reseller Filter -->
        <div>
          <label for="reseller" class="block text-sm font-medium text-gray-700 mb-1">Reseller</label>
          <select
            id="reseller"
            v-model="filters.reseller_id"
            @change="loadUsers"
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
          >
            <option value="">All Resellers</option>
            <option v-for="reseller in filters.resellers" :key="reseller.id" :value="reseller.id">
              {{ reseller.org_name }}
            </option>
          </select>
        </div>
        
        <!-- Role Filter -->
        <div>
          <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
          <select
            id="role"
            v-model="filters.role"
            @change="loadUsers"
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
          >
            <option value="">All Roles</option>
            <option v-for="role in filters.roles" :key="role" :value="role">
              {{ role.replace('_', ' ').toUpperCase() }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Users Table -->
    <div v-else class="mt-8 bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
      <!-- Mobile Card View (hidden on larger screens) -->
      <div class="block md:hidden">
        <div v-for="user in users" :key="user.id" class="border-b border-gray-200 last:border-b-0">
          <div class="p-4 cursor-pointer hover:bg-gray-50 transition-colors duration-150" @click="viewUser(user)">
            <!-- Header with User and Status -->
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-center space-x-3 min-w-0 flex-1">
                <div class="flex-shrink-0">
                  <div class="h-10 w-10 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center ring-1 ring-gray-200">
                    <span class="text-sm font-medium text-gray-700">
                      {{ user.name.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                </div>
                <div class="min-w-0 flex-1">
                  <h3 class="text-sm font-medium text-gray-900 truncate">
                    {{ user.name }}
                  </h3>
                  <p class="text-xs text-gray-500 truncate">{{ user.email }}</p>
                  <p v-if="user.company" class="text-xs text-gray-400 truncate">{{ user.company }}</p>
                </div>
              </div>
              <span :class="getStatusBadgeClass(user.status, user.is_deleted)" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium">
                {{ user.is_deleted ? 'DELETED' : user.status.toUpperCase() }}
              </span>
            </div>

            <!-- Details -->
            <div class="space-y-2 mb-3">
              <div class="text-xs text-gray-600">
                <span class="font-medium">Role:</span> {{ user.role.replace('_', ' ').toUpperCase() }}
              </div>
              <div class="text-xs text-gray-600">
                <span class="font-medium">Reseller:</span> {{ user.reseller?.org_name || 'No Reseller' }}
                <template v-if="user.reseller?.domain">
                  <br><span class="text-gray-500">({{ user.reseller.domain }})</span>
                </template>
              </div>
              <div class="text-xs text-gray-600">
                <span class="font-medium">Subscription:</span> 
                <span :class="user.has_active_subscription ? 'text-green-600' : 'text-gray-400'">
                  <template v-if="user.has_active_subscription">
                    {{ user.subscription_package?.name || 'Active' }}
                    <template v-if="user.subscription_package">
                      - ${{ user.subscription_package.price }}/{{ user.subscription_package.billing_interval || 'monthly' }}
                    </template>
                  </template>
                  <template v-else>No Subscription</template>
                </span>
              </div>
              <div class="text-xs text-gray-500">
                <span class="font-medium">Created:</span> {{ formatDate(user.created_at) }}
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between">
              <button 
                @click.stop="viewUser(user)" 
                class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors"
              >
                View Details
              </button>
              
              <div class="flex items-center space-x-1">
                <button 
                  v-if="!user.is_deleted"
                  @click.stop="editUser(user)" 
                  class="p-1 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded-md"
                  title="Edit user"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button 
                  v-if="!user.is_deleted"
                  @click.stop="deleteUser(user)" 
                  class="p-1 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-md"
                  title="Delete user"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
                <button 
                  v-if="user.is_deleted"
                  @click.stop="restoreUser(user)" 
                  class="p-1 text-green-600 hover:text-green-700 hover:bg-green-50 rounded-md"
                  title="Restore user"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                </button>
                <button 
                  v-if="user.is_deleted"
                  @click.stop="forceDeleteUser(user)" 
                  class="p-1 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-md"
                  title="Force delete user"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
                User
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Reseller
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Role
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Subscription
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
              v-for="user in users" 
              :key="user.id" 
              @click="viewUser(user)"
              :class="{ 'bg-red-50': user.is_deleted }"
              class="hover:bg-gray-50 transition-colors duration-150 cursor-pointer"
            >
              <!-- User -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center ring-1 ring-gray-200">
                      <span class="text-sm font-medium text-gray-700">
                        {{ user.name.charAt(0).toUpperCase() }}
                      </span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                    <div v-if="user.company" class="text-xs text-gray-400">{{ user.company }}</div>
                  </div>
                </div>
              </td>

              <!-- Reseller -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ user.reseller?.org_name || 'No Reseller' }}</div>
                <div v-if="user.reseller?.domain" class="text-sm text-gray-500">{{ user.reseller.domain }}</div>
              </td>

              <!-- Role -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getRoleBadgeClass(user.role)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                  {{ user.role.replace('_', ' ').toUpperCase() }}
                </span>
              </td>

              <!-- Status -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(user.status, user.is_deleted)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                  {{ user.is_deleted ? 'DELETED' : user.status.toUpperCase() }}
                </span>
              </td>

              <!-- Subscription -->
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <div v-if="user.has_active_subscription" class="text-green-600">
                  <div>{{ user.subscription_package?.name || 'Active' }}</div>
                  <div v-if="user.subscription_package" class="text-xs text-gray-500">
                    ${{ user.subscription_package.price }}/{{ user.subscription_package.billing_interval || 'monthly' }}
                  </div>
                </div>
                <div v-else class="text-gray-400">No Subscription</div>
              </td>

              <!-- Created -->
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(user.created_at) }}
              </td>

              <!-- Actions -->
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end space-x-2">
                  <!-- View Button -->
                  <button 
                    @click.stop="viewUser(user)" 
                    class="text-blue-600 hover:text-blue-900 p-1 rounded-md hover:bg-blue-50 transition-colors duration-200"
                    title="View details"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>

                  <!-- Edit Button -->
                  <button 
                    v-if="!user.is_deleted"
                    @click.stop="editUser(user)" 
                    class="text-indigo-600 hover:text-indigo-900 p-1 rounded-md hover:bg-indigo-50 transition-colors duration-200"
                    title="Edit user"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>

                  <!-- Delete Button -->
                  <button 
                    v-if="!user.is_deleted"
                    @click.stop="deleteUser(user)" 
                    class="text-red-600 hover:text-red-900 p-1 rounded-md hover:bg-red-50 transition-colors duration-200"
                    title="Delete user"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>

                  <!-- Restore Button -->
                  <button 
                    v-if="user.is_deleted"
                    @click.stop="restoreUser(user)" 
                    class="text-green-600 hover:text-green-900 p-1 rounded-md hover:bg-green-50 transition-colors duration-200"
                    title="Restore user"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                  </button>

                  <!-- Force Delete Button -->
                  <button 
                    v-if="user.is_deleted"
                    @click.stop="forceDeleteUser(user)" 
                    class="text-red-600 hover:text-red-900 p-1 rounded-md hover:bg-red-50 transition-colors duration-200"
                    title="Force delete user"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <div class="flex-1 flex justify-between sm:hidden">
          <button
            @click="loadUsers(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          <button
            @click="loadUsers(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.last_page"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
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
                @click="loadUsers(pagination.current_page - 1)"
                :disabled="pagination.current_page <= 1"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span class="sr-only">Previous</span>
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </button>
              <button
                v-for="page in getPageNumbers()"
                :key="page"
                @click="loadUsers(page)"
                :class="[
                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                  page === pagination.current_page
                    ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                ]"
              >
                {{ page }}
              </button>
              <button
                @click="loadUsers(pagination.current_page + 1)"
                :disabled="pagination.current_page >= pagination.last_page"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span class="sr-only">Next</span>
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- User Form Modal -->
    <UserFormModal
      :show="showUserModal"
      :user="selectedUser"
      :resellers="filters.resellers"
      @close="closeUserModal"
      @saved="onUserSaved"
    />

    <!-- User Details Modal -->
    <UserDetailsModal
      :show="showDetailsModal"
      :user="selectedUser"
      @close="closeDetailsModal"
    />
  </SuperAdminLayout>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import SuperAdminLayout from '../layouts/SuperAdminLayout.vue'
import UserFormModal from './modals/UserFormModal.vue'
import UserDetailsModal from './modals/UserDetailsModal.vue'
import Swal from 'sweetalert2'

export default {
  name: 'SuperAdminUsers',
  components: {
    SuperAdminLayout,
    UserFormModal,
    UserDetailsModal
  },
  setup() {
    const loading = ref(false)
    const users = ref([])
    const stats = ref({})
    const showUserModal = ref(false)
    const showDetailsModal = ref(false)
    const selectedUser = ref(null)
    const includeDeleted = ref(false)

    const filters = ref({
      search: '',
      reseller_id: '',
      role: '',
      resellers: [],
      roles: ['user', 'admin', 'content_admin', 'reseller_admin']
    })

    const pagination = ref({
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
      from: 0,
      to: 0
    })

    // Simple debounce function
    const debounce = (func, wait) => {
      let timeout
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout)
          func(...args)
        }
        clearTimeout(timeout)
        timeout = setTimeout(later, wait)
      }
    }

    // Debounced search
    const debouncedSearch = debounce(() => {
      pagination.value.current_page = 1
      loadUsers()
    }, 500)

    // Load users
    const loadUsers = async (page = 1) => {
      loading.value = true
      try {
        const params = new URLSearchParams({
          page: page,
          per_page: pagination.value.per_page,
          include_deleted: includeDeleted.value ? '1' : '0',
          ...filters.value
        })

        const response = await fetch(`/api/super-admin/users?${params}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        })

        const data = await response.json()

        if (data.success) {
          users.value = data.data
          pagination.value = data.pagination
          filters.value.resellers = data.filters.resellers
        } else {
          console.error('Failed to load users:', data.message)
        }
      } catch (error) {
        console.error('Error loading users:', error)
      } finally {
        loading.value = false
      }
    }

    // Load statistics
    const loadStats = async () => {
      try {
        const response = await fetch('/api/super-admin/users/statistics', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        })

        const data = await response.json()

        if (data.success) {
          stats.value = data.data
        }
      } catch (error) {
        console.error('Error loading statistics:', error)
      }
    }

    // User actions
    const createUser = () => {
      selectedUser.value = null
      showUserModal.value = true
    }

    const editUser = (user) => {
      selectedUser.value = user
      showUserModal.value = true
    }

    const viewUser = (user) => {
      selectedUser.value = user
      showDetailsModal.value = true
    }

    const deleteUser = async (user) => {
      if (!confirm(`Are you sure you want to delete user "${user.name}"?`)) {
        return
      }

      try {
        const response = await fetch(`/api/super-admin/users/${user.id}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        })

        const data = await response.json()

        if (data.success) {
          await loadUsers()
          await loadStats()
        } else {
          alert('Failed to delete user: ' + data.message)
        }
      } catch (error) {
        console.error('Error deleting user:', error)
        alert('Error deleting user')
      }
    }

    const restoreUser = async (user) => {
      const result = await Swal.fire({
        title: 'Restore User',
        text: `Are you sure you want to restore user "${user.name}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, restore user',
        cancelButtonText: 'Cancel'
      })

      if (!result.isConfirmed) {
        return
      }

      try {
        const response = await fetch(`/api/super-admin/users/${user.id}/restore`, {
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
            text: 'User restored successfully',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
          })
          await loadUsers()
          await loadStats()
        } else {
          await Swal.fire({
            title: 'Error',
            text: data.message || 'Failed to restore user',
            icon: 'error'
          })
        }
      } catch (error) {
        console.error('Error restoring user:', error)
        await Swal.fire({
          title: 'Error',
          text: 'Error restoring user',
          icon: 'error'
        })
      }
    }

    const forceDeleteUser = async (user) => {
      if (!confirm(`Are you sure you want to permanently delete user "${user.name}"? This action cannot be undone.`)) {
        return
      }

      try {
        const response = await fetch(`/api/super-admin/users/${user.id}/force`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        })

        const data = await response.json()

        if (data.success) {
          await loadUsers()
          await loadStats()
        } else {
          alert('Failed to permanently delete user: ' + data.message)
        }
      } catch (error) {
        console.error('Error permanently deleting user:', error)
        alert('Error permanently deleting user')
      }
    }

    const toggleDeletedFilter = () => {
      includeDeleted.value = !includeDeleted.value
      pagination.value.current_page = 1
      loadUsers()
    }

    const closeUserModal = () => {
      showUserModal.value = false
      selectedUser.value = null
    }

    const closeDetailsModal = () => {
      showDetailsModal.value = false
      selectedUser.value = null
    }

    const onUserSaved = () => {
      closeUserModal()
      loadUsers()
      loadStats()
    }

    // Utility functions
    const getRoleBadgeClass = (role) => {
      const classes = {
        'admin': 'bg-red-100 text-red-800',
        'content_admin': 'bg-purple-100 text-purple-800',
        'reseller_admin': 'bg-blue-100 text-blue-800',
        'user': 'bg-gray-100 text-gray-800'
      }
      return classes[role] || 'bg-gray-100 text-gray-800'
    }

    const getStatusBadgeClass = (status, isDeleted) => {
      if (isDeleted) return 'bg-red-100 text-red-800'
      
      const classes = {
        'active': 'bg-green-100 text-green-800',
        'inactive': 'bg-yellow-100 text-yellow-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString()
    }

    const getPageNumbers = () => {
      const pages = []
      const start = Math.max(1, pagination.value.current_page - 2)
      const end = Math.min(pagination.value.last_page, pagination.value.current_page + 2)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    }

    // Watch for filter changes
    watch(() => filters.value.reseller_id, () => {
      pagination.value.current_page = 1
      loadUsers()
    })

    watch(() => filters.value.role, () => {
      pagination.value.current_page = 1
      loadUsers()
    })

    // Initialize
    onMounted(() => {
      loadUsers()
      loadStats()
    })

    return {
      loading,
      users,
      stats,
      filters,
      pagination,
      showUserModal,
      showDetailsModal,
      selectedUser,
      includeDeleted,
      debouncedSearch,
      loadUsers,
      createUser,
      editUser,
      viewUser,
      deleteUser,
      restoreUser,
      forceDeleteUser,
      toggleDeletedFilter,
      closeUserModal,
      closeDetailsModal,
      onUserSaved,
      getRoleBadgeClass,
      getStatusBadgeClass,
      formatDate,
      getPageNumbers
    }
  }
}
</script>
