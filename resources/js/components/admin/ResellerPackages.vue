<template>
  <SuperAdminLayout title="Reseller Packages" subtitle="Manage subscription packages for resellers">
    <div class="flex-1 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="md:flex md:items-center md:justify-between">
            <div class="flex-1 min-w-0">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                    </svg>
                  </div>
                </div>
                <div>
                  <h1 class="text-3xl font-bold text-gray-900">
                    Reseller Packages
                  </h1>
                  <p class="mt-1 text-sm text-gray-600">
                    Manage subscription packages for resellers
                  </p>
                </div>
              </div>
            </div>
            <div class="mt-6 md:mt-0 md:ml-4">
              <button 
                @click="createPackage" 
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl shadow-sm text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transform transition-all duration-150 hover:scale-105"
              >
                <svg class="-ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add Package
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
              placeholder="Search packages by name or description..."
              class="block w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl shadow-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-sm"
            />
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
        </div>

        <!-- Packages Results -->
        <div v-else>
          <!-- Empty State -->
          <div v-if="packages.data && packages.data.length === 0" class="text-center py-16">
            <div class="mx-auto h-24 w-24 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center mb-6">
              <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No packages yet</h3>
            <p class="text-gray-500 mb-8 max-w-sm mx-auto">Get started by creating your first reseller package to manage subscription plans.</p>
            <button @click="createPackage" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transform transition-all duration-150 hover:scale-105">
              <svg class="-ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Create First Package
            </button>
          </div>

          <!-- Packages Grid -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div 
              v-for="packageItem in packages.data" 
              :key="packageItem.id" 
              class="group relative bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg hover:border-gray-300 transition-all duration-300 transform hover:-translate-y-1"
            >
              <!-- Status Indicator Bar -->
              <div 
                :class="[
                  'absolute top-0 left-0 right-0 h-1 rounded-t-xl',
                  packageItem.is_active 
                    ? 'bg-gradient-to-r from-green-400 to-emerald-500' 
                    : 'bg-gradient-to-r from-red-400 to-rose-500'
                ]"
              ></div>

              <!-- Popular Badge -->
              <div v-if="packageItem.is_popular" class="absolute top-4 right-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 ring-1 ring-yellow-600/20">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  Popular
                </span>
              </div>

              <!-- Card Content -->
              <div class="p-6">
                <!-- Header with Name and Status -->
                <div class="flex items-start justify-between mb-4">
                  <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-semibold text-gray-900 truncate group-hover:text-gray-700 transition-colors">
                      {{ packageItem.name }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 line-clamp-2">
                      {{ packageItem.description }}
                    </p>
                  </div>
                  
                  <!-- Status Badge -->
                  <span :class="[
                    'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset ml-2',
                    packageItem.is_active 
                      ? 'bg-green-50 text-green-700 ring-green-600/20' 
                      : 'bg-red-50 text-red-700 ring-red-600/20'
                  ]">
                    <svg 
                      :class="[
                        'w-1.5 h-1.5 mr-1',
                        packageItem.is_active ? 'text-green-400' : 'text-red-400'
                      ]" 
                      fill="currentColor" 
                      viewBox="0 0 8 8"
                    >
                      <circle cx="4" cy="4" r="3" />
                    </svg>
                    {{ packageItem.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>

                <!-- Pricing -->
                <div class="mb-6">
                  <div class="flex items-baseline">
                    <span class="text-3xl font-bold text-gray-900">${{ parseFloat(packageItem.price).toFixed(2) }}</span>
                    <span class="text-sm text-gray-500 ml-1">/month</span>
                  </div>
                  <div v-if="packageItem.yearly_price" class="text-sm text-gray-600 mt-1">
                    ${{ parseFloat(packageItem.yearly_price).toFixed(2) }}/year ({{ Math.round((1 - packageItem.yearly_price / (packageItem.price * 12)) * 100) }}% off)
                  </div>
                </div>

                <!-- Features -->
                <div class="space-y-3 mb-6">
                  <div class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ packageItem.voice_agents_limit === -1 ? 'Unlimited' : packageItem.voice_agents_limit }} Voice Agents</span>
                  </div>
                  
                  <div class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ packageItem.monthly_minutes_limit === -1 ? 'Unlimited' : packageItem.monthly_minutes_limit.toLocaleString() }} Minutes/Month</span>
                  </div>
                  
                  <div v-if="packageItem.extra_per_minute_charge > 0" class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                    </svg>
                    <span>${{ parseFloat(packageItem.extra_per_minute_charge).toFixed(4) }}/min overage</span>
                  </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                  <button 
                    @click="togglePackageStatus(packageItem)" 
                    :class="[
                      'inline-flex items-center px-3 py-2 text-xs font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2',
                      packageItem.is_active 
                        ? 'text-red-700 bg-red-50 hover:bg-red-100 focus:ring-red-500 ring-1 ring-red-200' 
                        : 'text-green-700 bg-green-50 hover:bg-green-100 focus:ring-green-500 ring-1 ring-green-200'
                    ]"
                    :title="packageItem.is_active ? 'Deactivate package' : 'Activate package'"
                  >
                    <svg v-if="packageItem.is_active" class="w-3 h-3 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg v-else class="w-3 h-3 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ packageItem.is_active ? 'Deactivate' : 'Activate' }}
                  </button>
                  
                  <div class="flex items-center space-x-2">
                    <button 
                      @click="editPackage(packageItem)" 
                      class="inline-flex items-center p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                      title="Edit package"
                    >
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    
                    <button 
                      @click="deletePackage(packageItem)" 
                      class="inline-flex items-center p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                      title="Delete package"
                    >
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
          <div v-if="packages.last_page > 1" class="mt-8 bg-white rounded-xl border border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <p class="text-sm text-gray-600">
                  Showing <span class="font-semibold text-gray-900">{{ packages.from }}</span> to <span class="font-semibold text-gray-900">{{ packages.to }}</span> of <span class="font-semibold text-gray-900">{{ packages.total }}</span> packages
                </p>
              </div>
              
              <div class="flex items-center space-x-1">
                <button 
                  @click="goToPage(packages.current_page - 1)" 
                  :disabled="packages.current_page <= 1" 
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
                      page === packages.current_page 
                        ? 'bg-purple-100 text-purple-700 border border-purple-300' 
                        : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                    ]"
                  >
                    {{ page }}
                  </button>
                </div>
                
                <button 
                  @click="goToPage(packages.current_page + 1)" 
                  :disabled="packages.current_page >= packages.last_page" 
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

    <!-- Package Form Modal -->
    <PackageFormModal
      :show="showModal"
      :package="selectedPackage"
      @close="closeModal"
      @saved="handlePackageSaved"
    />
  </SuperAdminLayout>
</template>

<script>
import { ref, onMounted, computed, getCurrentInstance } from 'vue'
import SuperAdminLayout from '../layouts/SuperAdminLayout.vue'
import PackageFormModal from './PackageFormModal.vue'
import Swal from 'sweetalert2'

export default {
  name: 'ResellerPackages',
  components: {
    SuperAdminLayout,
    PackageFormModal
  },
  setup() {
    const { proxy } = getCurrentInstance()
    const loading = ref(true)
    const packages = ref({})
    const searchQuery = ref('')
    const showModal = ref(false)
    const selectedPackage = ref(null)
    const searchTimeout = ref(null)

    const fetchPackages = async (page = 1, search = '') => {
      loading.value = true
      try {
        let url = `/api/super-admin/reseller-packages?page=${page}`
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
          throw new Error('Failed to fetch packages')
        }

        const data = await response.json()
        if (data.success) {
          packages.value = data.data
        } else {
          throw new Error(data.message || 'Failed to fetch packages')
        }
      } catch (error) {
        console.error('Error fetching packages:', error)
        if (proxy.$toast && proxy.$toast.error) {
          proxy.$toast.error('Error fetching packages')
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
        fetchPackages(1, searchQuery.value)
      }, 300)
    }

    const createPackage = () => {
      selectedPackage.value = null
      showModal.value = true
    }

    const editPackage = (packageItem) => {
      selectedPackage.value = { ...packageItem }
      showModal.value = true
    }

    const togglePackageStatus = async (packageItem) => {
      const newStatus = !packageItem.is_active
      const action = packageItem.is_active ? 'deactivate' : 'activate'
      
      const result = await Swal.fire({
        title: `${action.charAt(0).toUpperCase() + action.slice(1)} Package?`,
        text: `Are you sure you want to ${action} "${packageItem.name}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: packageItem.is_active ? '#ef4444' : '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Yes, ${action} it!`
      })

      if (result.isConfirmed) {
        try {
          const response = await fetch(`/api/super-admin/reseller-packages/${packageItem.id}/toggle-status`, {
            method: 'PATCH',
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`,
              'Accept': 'application/json',
            }
          })

          const data = await response.json()
          if (data.success) {
            if (proxy.$toast && proxy.$toast.success) {
              proxy.$toast.success(`Package ${action}d successfully`)
            }
            // Update the package in the list
            const index = packages.value.data.findIndex(p => p.id === packageItem.id)
            if (index !== -1) {
              packages.value.data[index] = data.data
            }
          } else {
            throw new Error(data.message || `Failed to ${action} package`)
          }
        } catch (error) {
          console.error(`Error ${action}ing package:`, error)
          if (proxy.$toast && proxy.$toast.error) {
            proxy.$toast.error(`Error ${action}ing package`)
          }
        }
      }
    }

    const deletePackage = async (packageItem) => {
      const result = await Swal.fire({
        title: 'Delete Package?',
        text: `Are you sure you want to delete "${packageItem.name}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!'
      })

      if (result.isConfirmed) {
        try {
          const response = await fetch(`/api/super-admin/reseller-packages/${packageItem.id}`, {
            method: 'DELETE',
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`,
              'Accept': 'application/json',
            }
          })

          const data = await response.json()
          if (data.success) {
            if (proxy.$toast && proxy.$toast.success) {
              proxy.$toast.success('Package deleted successfully')
            }
            fetchPackages(packages.value.current_page || 1, searchQuery.value)
          } else {
            throw new Error(data.message || 'Failed to delete package')
          }
        } catch (error) {
          console.error('Error deleting package:', error)
          if (proxy.$toast && proxy.$toast.error) {
            proxy.$toast.error('Error deleting package')
          }
        }
      }
    }

    const closeModal = () => {
      showModal.value = false
      selectedPackage.value = null
    }

    const handlePackageSaved = () => {
      closeModal()
      fetchPackages(packages.value.current_page || 1, searchQuery.value)
    }

    const goToPage = (page) => {
      if (page >= 1 && page <= packages.value.last_page) {
        fetchPackages(page, searchQuery.value)
      }
    }

    const visiblePages = computed(() => {
      const current = packages.value.current_page || 1
      const last = packages.value.last_page || 1
      const pages = []
      
      let start = Math.max(1, current - 2)
      let end = Math.min(last, current + 2)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    })

    onMounted(() => {
      fetchPackages()
    })

    return {
      loading,
      packages,
      searchQuery,
      showModal,
      selectedPackage,
      fetchPackages,
      performSearch,
      createPackage,
      editPackage,
      togglePackageStatus,
      deletePackage,
      closeModal,
      handlePackageSaved,
      goToPage,
      visiblePages
    }
  }
}
</script>