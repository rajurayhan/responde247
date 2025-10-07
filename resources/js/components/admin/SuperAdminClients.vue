<template>
  <SuperAdminLayout title="Reseller Management" subtitle="Manage reseller organizations and their status">
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
      <div class="flex items-center space-x-4">
        <!-- Add Reseller Button -->
        <button 
          @click="createReseller"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl shadow-sm text-white bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transform transition-all duration-150 hover:scale-105"
        >
          <svg class="-ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          Add New Reseller
        </button>
      </div>

      <!-- Stats Summary -->
      <div class="flex items-center space-x-6 text-sm text-gray-600">
        <div class="flex items-center space-x-2">
          <div class="h-3 w-3 rounded-full bg-green-500"></div>
          <span>{{ stats.active }} Active</span>
        </div>
        <div class="flex items-center space-x-2">
          <div class="h-3 w-3 rounded-full bg-red-500"></div>
          <span>{{ stats.inactive }} Inactive</span>
        </div>
        <div class="text-gray-500">
          Total: {{ stats.total }}
        </div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <!-- Search -->
        <div class="flex-1 max-w-md">
          <div class="relative">
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
              class="block w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-sm"
            />
          </div>
        </div>

        <!-- Filters -->
        <div class="flex items-center space-x-4">
          <!-- Status Filter -->
          <div class="flex items-center space-x-2">
            <label class="text-sm font-medium text-gray-700">Status:</label>
            <select
              v-model="filters.status"
              @change="applyFilters"
              class="block w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            >
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

          <!-- Clear Filters -->
          <button
            v-if="hasActiveFilters"
            @click="clearFilters"
            class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors"
          >
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Clear
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
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
        <button @click="createReseller" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transform transition-all duration-150 hover:scale-105">
          <svg class="-ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          Create First Reseller
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="reseller in resellers.data" 
          :key="reseller.id" 
          class="group relative bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg hover:border-gray-300 transition-all duration-300 transform hover:-translate-y-1"
        >
          <!-- Status Indicator Bar -->
          <div 
            :class="[
              'absolute top-0 left-0 right-0 h-1 rounded-t-xl',
              reseller.status === 'active' 
                ? 'bg-gradient-to-r from-green-400 to-emerald-500' 
                : 'bg-gradient-to-r from-red-400 to-rose-500'
            ]"
          ></div>

          <!-- Card Content -->
          <div class="p-6">
            <!-- Header with Logo and Status -->
            <div class="flex items-start justify-between mb-4 gap-3">
              <div class="flex items-center space-x-3 min-w-0 flex-1">
                <div class="flex-shrink-0">
                  <div v-if="reseller.logo_address" class="h-12 w-12 rounded-xl overflow-hidden ring-2 ring-gray-100 shadow-sm">
                    <img :src="reseller.logo_address" :alt="reseller.org_name" class="h-12 w-12 object-cover">
                  </div>
                  <div v-else class="h-12 w-12 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center ring-2 ring-gray-100 shadow-sm">
                    <svg class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2m-2 0H7m2 0H5m2 0h2m2 0h2M9 7h6m-6 4h6m-6 4h6" />
                    </svg>
                  </div>
                </div>
                <div class="min-w-0 flex-1">
                  <h3 class="text-lg font-semibold text-gray-900 truncate group-hover:text-gray-700 transition-colors">
                    {{ reseller.org_name }}
                  </h3>
                </div>
              </div>
              
              <!-- Status Badge -->
              <span :class="[
                'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset flex-shrink-0',
                reseller.status === 'active' 
                  ? 'bg-green-50 text-green-700 ring-green-600/20' 
                  : 'bg-red-50 text-red-700 ring-red-600/20'
              ]">
                <svg 
                  :class="[
                    'w-1.5 h-1.5 mr-1',
                    reseller.status === 'active' ? 'text-green-400' : 'text-red-400'
                  ]" 
                  fill="currentColor" 
                  viewBox="0 0 8 8"
                >
                  <circle cx="4" cy="4" r="3" />
                </svg>
                {{ reseller.status }}
              </span>
            </div>

            <!-- Reseller Details -->
            <div class="space-y-3 mb-6">
              <div v-if="reseller.company_email" class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span class="truncate">{{ reseller.company_email }}</span>
              </div>
              
              <div v-if="reseller.domain" class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9" />
                </svg>
                <span class="truncate">{{ reseller.domain }}</span>
              </div>
              
              <div class="flex items-center text-sm text-gray-500">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Created {{ formatDate(reseller.created_at) }}</span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
              <button 
                @click="toggleResellerStatus(reseller)" 
                :class="[
                  'inline-flex items-center px-3 py-2 text-xs font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2',
                  reseller.status === 'active' 
                    ? 'text-red-700 bg-red-50 hover:bg-red-100 focus:ring-red-500 ring-1 ring-red-200' 
                    : 'text-green-700 bg-green-50 hover:bg-green-100 focus:ring-green-500 ring-1 ring-green-200'
                ]"
                :title="reseller.status === 'active' ? 'Deactivate reseller' : 'Activate reseller'"
              >
                <svg v-if="reseller.status === 'active'" class="w-3 h-3 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else class="w-3 h-3 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ reseller.status === 'active' ? 'Deactivate' : 'Activate' }}
              </button>
              
              <div class="flex items-center space-x-2">
                <button 
                  @click="editReseller(reseller)" 
                  class="inline-flex items-center p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  title="Edit reseller"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Hover Overlay -->
          <div class="absolute inset-0 rounded-xl bg-gradient-to-br from-white/0 to-gray-50/0 group-hover:from-white/5 group-hover:to-gray-50/5 pointer-events-none transition-all duration-300"></div>
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
                    ? 'bg-purple-100 text-purple-700 border border-purple-300' 
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
import SuperAdminLayout from '../layouts/SuperAdminLayout.vue'
import ResellerFormModal from './ResellerFormModal.vue'
import Swal from 'sweetalert2'

export default {
  name: 'SuperAdminResellers',
  components: {
    SuperAdminLayout,
    ResellerFormModal
  },
  setup() {
    // Get the current instance to access $toast
    const { proxy } = getCurrentInstance()
    const loading = ref(true)
    const resellers = ref({})
    const searchQuery = ref('')
    const showModal = ref(false)
    const selectedReseller = ref(null)
    const searchTimeout = ref(null)
    const filters = ref({
      status: ''
    })
    const stats = ref({
      total: 0,
      active: 0,
      inactive: 0
    })

    const fetchResellers = async (page = 1, search = '', statusFilter = '') => {
      loading.value = true
      try {
        let url = `/api/admin/resellers?page=${page}`
        
        // Add search parameter
        if (search) {
          url += `&search=${encodeURIComponent(search)}`
        }
        
        // Add status filter
        if (statusFilter) {
          url += `&status=${statusFilter}`
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
          resellers.value = data.data
          updateStats(data.data.data || [])
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

    const updateStats = (resellersData) => {
      stats.value.total = resellersData.length
      stats.value.active = resellersData.filter(reseller => reseller.status === 'active').length
      stats.value.inactive = resellersData.filter(reseller => reseller.status === 'inactive').length
    }

    const performSearch = () => {
      if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
      }
      searchTimeout.value = setTimeout(() => {
        fetchResellers(1, searchQuery.value, filters.value.status)
      }, 300)
    }

    const applyFilters = () => {
      fetchResellers(1, searchQuery.value, filters.value.status)
    }

    const clearFilters = () => {
      filters.value.status = ''
      searchQuery.value = ''
      fetchResellers(1, '', '')
    }

    const hasActiveFilters = computed(() => {
      return filters.value.status !== '' || searchQuery.value !== ''
    })

    const createReseller = () => {
      selectedReseller.value = null
      showModal.value = true
    }

    const editReseller = (reseller) => {
      selectedReseller.value = { ...reseller }
      showModal.value = true
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
            const index = resellers.value.data.findIndex(c => c.id === reseller.id)
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


    const closeModal = () => {
      showModal.value = false
      selectedReseller.value = null
    }

    const handleResellerSaved = () => {
      closeModal()
      fetchResellers(resellers.value.current_page || 1, searchQuery.value, filters.value.status)
    }

    const goToPage = (page) => {
      if (page >= 1 && page <= resellers.value.last_page) {
        fetchResellers(page, searchQuery.value, filters.value.status)
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
      filters,
      stats,
      hasActiveFilters,
      fetchResellers,
      performSearch,
      applyFilters,
      clearFilters,
      createReseller,
      editReseller,
      toggleResellerStatus,
      closeModal,
      handleResellerSaved,
      goToPage,
      visiblePages,
      formatDate
    }
  }
}
</script>
