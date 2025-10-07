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
              {{ isContentAdmin ? 'User Management' : 'My Profile' }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              {{ isContentAdmin ? 'Manage users and their permissions' : 'View your profile information' }}
            </p>
          </div>
          <div v-if="canCreateUsers" class="mt-4 flex md:mt-0 md:ml-4">
            <button @click="createUser" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
              <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Add User
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="mt-8 flex justify-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
        </div>

        <!-- Users Table -->
        <div v-else class="mt-8">
          <!-- Filters and Search -->
          <div class="mb-6 bg-white p-4 rounded-lg shadow">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
              <!-- Search -->
              <div class="lg:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input
                  id="search"
                  v-model="filters.search"
                  @input="debouncedSearch"
                  type="text"
                  placeholder="Search by name, email, or company..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 text-sm"
                />
              </div>
              
              <!-- Status Filter (Admin only) -->
              <div v-if="isContentAdmin">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select
                  id="status"
                  v-model="filters.status"
                  @change="loadUsers"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 text-sm"
                >
                  <option value="">All Status</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
              
              <!-- Role Filter (Admin only) -->
              <div v-if="isContentAdmin">
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select
                  id="role"
                  v-model="filters.role"
                  @change="loadUsers"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 text-sm"
                >
                  <option value="">All Roles</option>
                  <option value="user">User</option>
                  <option value="admin">Admin</option>
                  <option value="reseller_admin">Reseller Admin</option>
                </select>
              </div>
              
              <!-- Per Page -->
              <div>
                <label for="perPage" class="block text-sm font-medium text-gray-700 mb-1">Per Page</label>
                <select
                  id="perPage"
                  v-model="pagination.perPage"
                  @change="loadUsers"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 text-sm"
                >
                  <option value="10">10</option>
                  <option value="15">15</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                </select>
              </div>
            </div>
            
            <!-- Sort Controls -->
            <div class="mt-4 flex items-center space-x-4">
              <div class="flex items-center space-x-2">
                <label for="sortBy" class="text-sm font-medium text-gray-700">Sort by:</label>
                <select
                  id="sortBy"
                  v-model="filters.sortBy"
                  @change="loadUsers"
                  class="px-3 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 text-sm"
                >
                  <option value="name">Name</option>
                  <option value="email">Email</option>
                  <option value="role">Role</option>
                  <option value="status">Status</option>
                  <option value="created_at">Created Date</option>
                </select>
              </div>
              
              <div class="flex items-center space-x-2">
                <label for="sortOrder" class="text-sm font-medium text-gray-700">Order:</label>
                <select
                  id="sortOrder"
                  v-model="filters.sortOrder"
                  @change="loadUsers"
                  class="px-3 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 text-sm"
                >
                  <option value="asc">Ascending</option>
                  <option value="desc">Descending</option>
                </select>
              </div>
              
              <button
                @click="resetFilters"
                class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded-md hover:bg-gray-50"
              >
                Reset Filters
              </button>
            </div>
          </div>

          <!-- Users List -->
          <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <!-- Table Header -->
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                  <span class="text-sm font-medium text-gray-900">Users</span>
                  <span class="text-sm text-gray-500">
                    Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} results
                  </span>
                </div>
                <div class="text-sm text-gray-500">
                  Page {{ pagination.currentPage || 1 }} of {{ pagination.lastPage || 1 }}
                </div>
              </div>
            </div>
            
            <ul class="divide-y divide-gray-200">
              <li v-for="user in users" :key="user.id" class="px-6 py-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                      <div v-if="user.profile_picture" class="h-10 w-10 rounded-full overflow-hidden">
                        <img :src="user.profile_picture" :alt="user.name" class="h-full w-full object-cover">
                      </div>
                      <div v-else class="h-10 w-10 rounded-full bg-green-600 flex items-center justify-center">
                        <span class="text-white font-medium">{{ getUserInitials(user.name) }}</span>
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                      <div class="text-sm text-gray-500">{{ user.email }}</div>
                    </div>
                  </div>
                  <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                      <span :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        user.role === 'admin' ? 'bg-purple-100 text-purple-800' : 
                        user.role === 'reseller_admin' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
                      ]">
                        {{ user.role }}
                      </span>
                      <span :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                      ]">
                        {{ user.status }}
                      </span>
                    </div>
                    <div v-if="canPerformActionOnUser(user)" class="flex items-center space-x-2">
                      <button v-if="canEditUsers && canPerformActionOnUser(user)" @click="editUser(user)" class="text-green-600 hover:text-green-900 text-sm font-medium">
                        Edit
                      </button>
                      <button v-if="canToggleUserStatus && canPerformActionOnUser(user)" @click="toggleUserStatus(user)" :class="user.status === 'active' ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'" class="text-sm font-medium">
                        {{ user.status === 'active' ? 'Suspend' : 'Activate' }}
                      </button>
                    </div>
                  </div>
                </div>
              </li>
            </ul>
          </div>

          <!-- Pagination Controls -->
          <div v-if="pagination.lastPage > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="flex-1 flex justify-between sm:hidden">
              <button
                @click="goToPage(pagination.currentPage - 1)"
                :disabled="pagination.currentPage <= 1"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Previous
              </button>
              <button
                @click="goToPage(pagination.currentPage + 1)"
                :disabled="pagination.currentPage >= pagination.lastPage"
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Next
              </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-gray-700">
                  Showing <span class="font-medium">{{ pagination.from || 0 }}</span> to <span class="font-medium">{{ pagination.to || 0 }}</span> of <span class="font-medium">{{ pagination.total || 0 }}</span> results
                </p>
              </div>
              <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                  <button
                    @click="goToPage(pagination.currentPage - 1)"
                    :disabled="pagination.currentPage <= 1"
                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <span class="sr-only">Previous</span>
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  
                  <template v-for="page in visiblePages" :key="page">
                    <button
                      v-if="page !== '...'"
                      @click="goToPage(page)"
                      :class="[
                        page === pagination.currentPage
                          ? 'z-10 bg-green-50 border-green-500 text-green-600'
                          : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                      ]"
                    >
                      {{ page }}
                    </button>
                    <span v-else class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                      ...
                    </span>
                  </template>
                  
                  <button
                    @click="goToPage(pagination.currentPage + 1)"
                    :disabled="pagination.currentPage >= pagination.lastPage"
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
          
          <!-- Empty State -->
          <div v-if="users.length === 0 && !loading" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
            <p class="mt-1 text-sm text-gray-500">No users match your search criteria.</p>
          </div>
        </div>

        <!-- Create User Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Create New User</h3>
                <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600">
                  <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              
              <form @submit.prevent="submitCreateUser">
                <div class="space-y-4">
                  <div>
                    <label for="create-name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input
                      id="create-name"
                      v-model="createForm.name"
                      type="text"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    />
                  </div>
                  
                  <div>
                    <label for="create-email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input
                      id="create-email"
                      v-model="createForm.email"
                      type="email"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    />
                  </div>
                  
                  <div>
                    <label for="create-password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input
                      id="create-password"
                      v-model="createForm.password"
                      type="password"
                      required
                      minlength="8"
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    />
                  </div>
                  
                  <div>
                    <label for="create-role" class="block text-sm font-medium text-gray-700">Role</label>
                    <select
                      id="create-role"
                      v-model="createForm.role"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    >
                      <option value="user">User</option>
                      <option value="admin">Admin</option>
                    </select>
                  </div>
                  
                  <div>
                    <label for="create-status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select
                      id="create-status"
                      v-model="createForm.status"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    >
                      <option value="active">Active</option>
                      <option value="inactive">Inactive</option>
                    </select>
                  </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                  <button
                    type="button"
                    @click="closeCreateModal"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="createLoading"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                  >
                    {{ createLoading ? 'Creating...' : 'Create User' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Edit User Modal -->
        <div v-if="showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Edit User</h3>
                <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600">
                  <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              
              <form @submit.prevent="submitEditUser">
                <div class="space-y-4">
                  <div>
                    <label for="edit-name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input
                      id="edit-name"
                      v-model="editForm.name"
                      type="text"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    />
                  </div>
                  
                  <div>
                    <label for="edit-email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input
                      id="edit-email"
                      v-model="editForm.email"
                      type="email"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    />
                  </div>
                  
                  <div>
                    <label for="edit-role" class="block text-sm font-medium text-gray-700">Role</label>
                    <select
                      id="edit-role"
                      v-model="editForm.role"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    >
                      <option value="user">User</option>
                      <option value="admin">Admin</option>
                    </select>
                  </div>
                  
                  <div>
                    <label for="edit-status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select
                      id="edit-status"
                      v-model="editForm.status"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    >
                      <option value="active">Active</option>
                      <option value="inactive">Inactive</option>
                    </select>
                  </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                  <button
                    type="button"
                    @click="closeEditModal"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="editLoading"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                  >
                    {{ editLoading ? 'Updating...' : 'Update User' }}
                  </button>
                </div>
              </form>
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
import Navigation from '../shared/Navigation.vue'
import SimpleFooter from '../shared/SimpleFooter.vue'
import { showError, showConfirm } from '../../utils/sweetalert.js'

export default {
  name: 'Users',
  components: {
    Navigation,
    SimpleFooter
  },
  data() {
    return {
      loading: true,
      users: [],
      currentUser: JSON.parse(localStorage.getItem('user') || '{}'),
      pagination: {
        currentPage: 1,
        lastPage: 1,
        perPage: 15,
        total: 0,
        from: 0,
        to: 0,
        hasMorePages: false
      },
      filters: {
        search: '',
        status: '',
        role: '',
        sortBy: 'name',
        sortOrder: 'asc'
      },
      searchTimeout: null,
      showCreateModal: false,
      showEditModal: false,
      createLoading: false,
      editLoading: false,
      createForm: {
        name: '',
        email: '',
        password: '',
        role: 'user',
        status: 'active'
      },
      editForm: {
        id: null,
        name: '',
        email: '',
        role: 'user',
        status: 'active'
      }
    }
  },
  computed: {
    // Check if current user is a content admin
    isContentAdmin() {
      return this.currentUser.role === 'admin' || this.currentUser.role === 'reseller_admin';
    },
    
    // Check if current user can create users
    canCreateUsers() {
      return this.isContentAdmin;
    },
    
    // Check if current user can edit users
    canEditUsers() {
      return this.isContentAdmin;
    },
    
    // Check if current user can delete users
    canDeleteUsers() {
      return this.isContentAdmin;
    },
    
    // Check if current user can toggle user status
    canToggleUserStatus() {
      return this.isContentAdmin;
    },
    
    visiblePages() {
      const current = this.pagination.currentPage;
      const last = this.pagination.lastPage;
      const pages = [];
      
      if (last <= 7) {
        // Show all pages if 7 or fewer
        for (let i = 1; i <= last; i++) {
          pages.push(i);
        }
      } else {
        // Show first page
        pages.push(1);
        
        if (current > 4) {
          pages.push('...');
        }
        
        // Show pages around current page
        const start = Math.max(2, current - 1);
        const end = Math.min(last - 1, current + 1);
        
        for (let i = start; i <= end; i++) {
          if (!pages.includes(i)) {
            pages.push(i);
          }
        }
        
        if (current < last - 3) {
          pages.push('...');
        }
        
        // Show last page
        if (last > 1) {
          pages.push(last);
        }
      }
      
      return pages;
    }
  },
  async mounted() {
    await this.loadUsers();
  },
  
  watch: {
    'filters.search'(newVal, oldVal) {
      if (newVal !== oldVal) {
        this.debouncedSearch();
      }
    }
  },
  methods: {
    async loadUsers() {
      try {
        this.loading = true;
        
        // Build query parameters
        const params = new URLSearchParams({
          page: this.pagination.currentPage,
          per_page: this.pagination.perPage,
          sort_by: this.filters.sortBy,
          sort_order: this.filters.sortOrder
        });
        
        if (this.filters.search) {
          params.append('search', this.filters.search);
        }
        if (this.filters.status) {
          params.append('status', this.filters.status);
        }
        if (this.filters.role) {
          params.append('role', this.filters.role);
        }
        
        const response = await fetch(`/api/admin/users?${params.toString()}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        });
        
        if (response.ok) {
          const data = await response.json();
          this.users = data.data || [];
          this.pagination = {
            currentPage: data.pagination.current_page,
            lastPage: data.pagination.last_page,
            perPage: data.pagination.per_page,
            total: data.pagination.total,
            from: data.pagination.from,
            to: data.pagination.to,
            hasMorePages: data.pagination.has_more_pages
          };
        } else {
          if (response.status === 403) {
            await showError('Access Denied', 'You do not have permission to view users.');
            this.$router.push('/dashboard');
          } else {
            console.error('Failed to load users');
            await showError('Error', 'Failed to load users. Please try again.');
          }
        }
      } catch (error) {
        console.error('Error loading users:', error);
        if (error.response && error.response.status === 403) {
          await showError('Access Denied', 'You do not have permission to view users.');
          this.$router.push('/dashboard');
        } else {
          await showError('Error', 'An error occurred while loading users.');
        }
      } finally {
        this.loading = false;
      }
    },
    
    debouncedSearch() {
      // Clear existing timeout
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout);
      }
      
      // Set new timeout
      this.searchTimeout = setTimeout(() => {
        this.pagination.currentPage = 1; // Reset to first page when searching
        this.loadUsers();
      }, 500); // 500ms delay
    },
    
    goToPage(page) {
      if (page >= 1 && page <= this.pagination.lastPage && page !== this.pagination.currentPage) {
        this.pagination.currentPage = page;
        this.loadUsers();
      }
    },
    
    resetFilters() {
      this.filters = {
        search: '',
        status: '',
        role: '',
        sortBy: 'name',
        sortOrder: 'asc'
      };
      this.pagination.currentPage = 1;
      this.loadUsers();
    },
    
    // Check if current user can perform action on target user
    canPerformActionOnUser(targetUser) {
      // Content admins can perform actions on any user within their reseller
      if (this.isContentAdmin) {
        return true;
      }
      
      // Regular users can only perform actions on themselves
      return targetUser.id === this.currentUser.id;
    },
    
    getUserInitials(name) {
      return name ? name.split(' ').map(n => n[0]).join('').toUpperCase() : 'U';
    },
    
    createUser() {
      this.showCreateModal = true;
      this.createForm = {
        name: '',
        email: '',
        password: '',
        role: 'user',
        status: 'active'
      };
      this.createLoading = false;
    },
    
    closeCreateModal() {
      this.showCreateModal = false;
    },

    async submitCreateUser() {
      if (!this.createForm.name || !this.createForm.email || !this.createForm.password) {
        await showError('Validation Error', 'Name, Email, and Password are required.');
        return;
      }

      try {
        this.createLoading = true;
        const response = await fetch('/api/admin/users', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.createForm)
        });

        if (response.ok) {
          const data = await response.json();
          this.closeCreateModal();
          // Reload users to get updated list with pagination
          await this.loadUsers();
        } else {
          const errorData = await response.json();
          await showError('Create Failed', errorData.message || 'Failed to create user. Please try again.');
        }
      } catch (error) {
        console.error('Error submitting create user:', error);
        await showError('Error', 'An error occurred while creating the user.');
      } finally {
        this.createLoading = false;
      }
    },
    
    editUser(user) {
      this.editForm = { ...user };
      this.showEditModal = true;
      this.editLoading = false;
    },

    closeEditModal() {
      this.showEditModal = false;
    },

    async submitEditUser() {
      if (!this.editForm.name || !this.editForm.email) {
        await showError('Validation Error', 'Name and Email are required for editing.');
        return;
      }

      try {
        this.editLoading = true;
        const response = await fetch(`/api/admin/users/${this.editForm.id}`, {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.editForm)
        });

        if (response.ok) {
          const data = await response.json();
          this.closeEditModal();
          // Reload users to get updated list with pagination
          await this.loadUsers();
        } else {
          const errorData = await response.json();
          await showError('Update Failed', errorData.message || 'Failed to update user. Please try again.');
        }
      } catch (error) {
        console.error('Error submitting edit user:', error);
        await showError('Error', 'An error occurred while updating the user.');
      } finally {
        this.editLoading = false;
      }
    },
    
    async toggleUserStatus(user) {
      const action = user.status === 'active' ? 'suspend' : 'activate';
      const result = await showConfirm(
        `Confirm ${action.charAt(0).toUpperCase() + action.slice(1)}`,
        `Are you sure you want to ${action} this user?`
      );
      
      if (!result.isConfirmed) {
        return;
      }
      
      try {
        const response = await fetch(`/api/admin/users/${user.id}/toggle-status`, {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        });
        
        if (response.ok) {
          await this.loadUsers();
        } else {
          const errorData = await response.json();
          await showError('Status Update Failed', errorData.message || 'Failed to update user status.');
        }
      } catch (error) {
        console.error('Error toggling user status:', error);
        await showError('Error', 'Failed to update user status: ' + error.message);
      }
    }
  }
}
</script> 