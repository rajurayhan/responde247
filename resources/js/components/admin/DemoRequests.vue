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
              Demo Requests Management
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Manage and track demo requests from potential customers
            </p>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Requests</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.total }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="h-8 w-8 bg-yellow-100 rounded-full flex items-center justify-center">
                    <svg class="h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.pending }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Contacted</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.contacted }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.completed }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="mt-8 bg-white shadow rounded-lg p-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
              <select v-model="filters.status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="contacted">Contacted</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
              <input v-model="filters.date_from" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
              <input v-model="filters.date_to" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
              <input v-model="filters.search" type="text" placeholder="Name, email, company, country" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
            </div>
          </div>
          <div class="mt-4 flex justify-end space-x-3">
            <button @click="clearFilters" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium">
              Clear Filters
            </button>
            <button @click="loadDemoRequests" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
              Apply Filters
            </button>
          </div>
        </div>

        <!-- Demo Requests Table -->
        <div class="mt-8 bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Demo Requests</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Industry</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="request in demoRequests" :key="request.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div class="text-sm font-medium text-gray-900">{{ request.name }}</div>
                      <div class="text-sm text-gray-500">{{ request.email }}</div>
                      <div class="text-sm text-gray-500">{{ request.phone }}</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ request.company_name }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ request.industry }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ request.country }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="[
                      'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                      request.status_badge_class
                    ]">
                      {{ request.status_display_name }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(request.created_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button @click="viewRequest(request)" class="text-green-600 hover:text-green-900 mr-3">
                      View
                    </button>
                    <button @click="updateStatus(request)" class="text-blue-600 hover:text-blue-900">
                      Update
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="pagination" class="px-6 py-3 border-t border-gray-200">
            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-700">
                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
              </div>
              <div class="flex space-x-2">
                <button 
                  v-if="pagination.prev_page_url"
                  @click="loadDemoRequests(pagination.current_page - 1)"
                  class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50"
                >
                  Previous
                </button>
                <button 
                  v-if="pagination.next_page_url"
                  @click="loadDemoRequests(pagination.current_page + 1)"
                  class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50"
                >
                  Next
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- View Request Modal -->
    <div v-if="showViewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Demo Request Details</h3>
            <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <div v-if="selectedRequest" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <p class="text-sm text-gray-900">{{ selectedRequest.name }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <p class="text-sm text-gray-900">{{ selectedRequest.email }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Phone</label>
                <p class="text-sm text-gray-900">{{ selectedRequest.phone }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Company</label>
                <p class="text-sm text-gray-900">{{ selectedRequest.company_name }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Industry</label>
                <p class="text-sm text-gray-900">{{ selectedRequest.industry }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Country</label>
                <p class="text-sm text-gray-900">{{ selectedRequest.country }}</p>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Services/Products</label>
              <p class="text-sm text-gray-900 mt-1">{{ selectedRequest.services }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Status</label>
              <span :class="[
                'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                selectedRequest.status_badge_class
              ]">
                {{ selectedRequest.status_display_name }}
              </span>
            </div>
            <div v-if="selectedRequest.notes">
              <label class="block text-sm font-medium text-gray-700">Notes</label>
              <p class="text-sm text-gray-900 mt-1">{{ selectedRequest.notes }}</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Created</label>
                <p class="text-sm text-gray-900">{{ formatDate(selectedRequest.created_at) }}</p>
              </div>
              <div v-if="selectedRequest.contacted_at">
                <label class="block text-sm font-medium text-gray-700">Contacted</label>
                <p class="text-sm text-gray-900">{{ formatDate(selectedRequest.contacted_at) }}</p>
              </div>
              <div v-if="selectedRequest.completed_at">
                <label class="block text-sm font-medium text-gray-700">Completed</label>
                <p class="text-sm text-gray-900">{{ formatDate(selectedRequest.completed_at) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Update Status Modal -->
    <div v-if="showUpdateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Update Status</h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
              <select v-model="updateForm.status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                <option value="pending">Pending</option>
                <option value="contacted">Contacted</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
              <textarea v-model="updateForm.notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="Add notes about this demo request..."></textarea>
            </div>
          </div>
          <div class="mt-6 flex justify-end space-x-3">
            <button @click="showUpdateModal = false" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-400">
              Cancel
            </button>
            <button @click="submitStatusUpdate" :disabled="updating" class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md text-sm font-medium">
              {{ updating ? 'Updating...' : 'Update Status' }}
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
import { ref, onMounted } from 'vue'
import axios from 'axios'
import Navigation from '../shared/Navigation.vue'
import SimpleFooter from '../shared/SimpleFooter.vue'
import { showSuccess, showError } from '../../utils/sweetalert.js'

export default {
  name: 'DemoRequests',
  components: {
    Navigation,
    SimpleFooter
  },
  setup() {
    const demoRequests = ref([])
    const stats = ref({
      total: 0,
      pending: 0,
      contacted: 0,
      completed: 0,
      cancelled: 0
    })
    const pagination = ref(null)
    const loading = ref(false)
    const updating = ref(false)
    
    const filters = ref({
      status: '',
      date_from: '',
      date_to: '',
      search: ''
    })

    const showViewModal = ref(false)
    const showUpdateModal = ref(false)
    const selectedRequest = ref(null)
    const updateForm = ref({
      status: '',
      notes: ''
    })

    const loadDemoRequests = async (page = 1) => {
      try {
        loading.value = true
        const token = localStorage.getItem('token')
        const params = {
          page,
          ...filters.value
        }
        
        const response = await axios.get('/api/admin/demo-requests', { 
          params,
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
          }
        })
        
        // Check if response has the expected structure
        if (response.data.success && response.data.data) {
          demoRequests.value = response.data.data.data || []
          pagination.value = response.data.data
        } else {
          demoRequests.value = []
          pagination.value = null
        }
      } catch (error) {
        showError('Failed to load demo requests')
      } finally {
        loading.value = false
      }
    }

    const loadStats = async () => {
      try {
        const token = localStorage.getItem('token')
        
        const response = await axios.get('/api/admin/demo-requests/stats', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
          }
        })
        
        if (response.data.success && response.data.data) {
          stats.value = response.data.data
        } else {
          stats.value = {
            total: 0,
            pending: 0,
            contacted: 0,
            completed: 0,
            cancelled: 0
          }
        }
      } catch (error) {
        stats.value = {
          total: 0,
          pending: 0,
          contacted: 0,
          completed: 0,
          cancelled: 0
        }
      }
    }

    const viewRequest = (request) => {
      selectedRequest.value = request
      showViewModal.value = true
    }

    const updateStatus = (request) => {
      selectedRequest.value = request
      updateForm.value = {
        status: request.status,
        notes: request.notes || ''
      }
      showUpdateModal.value = true
    }

    const submitStatusUpdate = async () => {
      try {
        updating.value = true
        const response = await axios.patch(`/api/admin/demo-requests/${selectedRequest.value.id}/status`, updateForm.value)
        
        showSuccess('Status Updated', 'Demo request status updated successfully')
        
        // Update the request in the list
        const index = demoRequests.value.findIndex(r => r.id === selectedRequest.value.id)
        if (index !== -1) {
          demoRequests.value[index] = response.data.data
        }
        
        showUpdateModal.value = false
        loadStats() // Refresh stats
      } catch (error) {
        showError('Failed to update status')
      } finally {
        updating.value = false
      }
    }

    const clearFilters = () => {
      filters.value = {
        status: '',
        date_from: '',
        date_to: '',
        search: ''
      }
      loadDemoRequests()
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    onMounted(() => {
      loadDemoRequests()
      loadStats()
    })

    return {
      demoRequests,
      stats,
      pagination,
      loading,
      updating,
      filters,
      showViewModal,
      showUpdateModal,
      selectedRequest,
      updateForm,
      loadDemoRequests,
      loadStats,
      viewRequest,
      updateStatus,
      submitStatusUpdate,
      formatDate,
      clearFilters
    }
  }
}
</script> 