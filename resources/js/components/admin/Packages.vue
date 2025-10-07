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
              Packages Management
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Manage subscription packages and pricing
            </p>
          </div>
          <div class="mt-4 flex md:mt-0 md:ml-4">
            <button @click="showCreateModal = true" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
              <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Add Package
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="mt-8 flex justify-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
        </div>

        <!-- Empty State -->
        <div v-else-if="packages.length === 0" class="mt-8 text-center">
          <div class="max-w-md mx-auto">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No packages found</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by initializing default packages or creating your first package.</p>
            <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
              <button 
                @click="initializeDefaultPackages" 
                :disabled="initializing"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg v-if="initializing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                {{ initializing ? 'Initializing...' : 'Initialize Default Packages' }}
              </button>
              <button 
                @click="showCreateModal = true" 
                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              >
                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Create Custom Package
              </button>
            </div>
          </div>
        </div>

        <!-- Packages Grid -->
        <div v-else class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div v-for="pkg in packages" :key="pkg.id" class="bg-white overflow-hidden shadow rounded-lg relative">
            <div v-if="pkg.is_popular" class="absolute top-0 right-0 bg-green-500 text-white px-3 py-1 text-xs font-medium">
              Popular
            </div>
            <div class="p-6">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">{{ pkg.name }}</h3>
                <span v-if="pkg.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  Active
                </span>
                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                  Inactive
                </span>
              </div>
              
              <div class="text-3xl font-bold text-green-600 mb-2">${{ pkg.price }}/month</div>
              <div v-if="pkg.yearly_price" class="text-lg font-semibold text-blue-600 mb-2">${{ pkg.yearly_price }}/year</div>
              <p class="text-gray-600 mb-4">{{ pkg.description }}</p>
              
              <div class="space-y-2 mb-4">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Voice Agents:</span>
                  <span class="font-medium">{{ pkg.voice_agents_limit < 0 ? 'Unlimited' : pkg.voice_agents_limit }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Monthly Minutes:</span>
                  <span class="font-medium">{{ pkg.monthly_minutes_limit }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Extra Per Minute:</span>
                  <span class="font-medium">${{ pkg.extra_per_minute_charge }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Support:</span>
                  <span class="font-medium capitalize">{{ pkg.support_level }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Analytics:</span>
                  <span class="font-medium capitalize">{{ pkg.analytics_level }}</span>
                </div>
              </div>
              
              <div class="flex items-center justify-between">
                <div class="flex space-x-2">
                  <button @click="editPackage(pkg)" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button @click="deletePackage(pkg.id)" class="text-red-400 hover:text-red-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ showEditModal ? 'Edit Package' : 'Add New Package' }}
          </h3>
          <form @submit.prevent="showEditModal ? updatePackage() : createPackage()">
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input v-model="form.name" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
              </div>
              
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea v-model="form.description" rows="3" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"></textarea>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Price ($)</label>
                <input v-model="form.price" type="number" step="0.01" min="0" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Yearly Price ($)</label>
                <input v-model="form.yearly_price" type="number" step="0.01" min="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Voice Agents Limit (-1 for unlimited)</label>
                <input v-model="form.voice_agents_limit" type="number" min="-1" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Monthly Minutes Limit (-1 for unlimited)</label>
                <input v-model="form.monthly_minutes_limit" type="number" min="-1" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Extra Per Minute Charge ($)</label>
                <input v-model="form.extra_per_minute_charge" type="number" step="0.0001" min="0" max="999.9999" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                <p class="mt-1 text-xs text-gray-500">Charge per minute when monthly limit is exceeded</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Features (comma-separated)</label>
                <textarea v-model="form.features" rows="4" placeholder="Enter features separated by commas" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"></textarea>
                <p class="mt-1 text-xs text-gray-500">Example: 1 Voice Agent, 1,000 minutes/month, Basic Analytics, Email Support</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Support Level</label>
                <select v-model="form.support_level" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                  <option value="email">Email</option>
                  <option value="priority">Priority</option>
                  <option value="dedicated">Dedicated</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Analytics Level</label>
                <select v-model="form.analytics_level" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                  <option value="basic">Basic</option>
                  <option value="advanced">Advanced</option>
                  <option value="custom">Custom</option>
                </select>
              </div>
              
              <div class="flex items-center space-x-4">
                <div class="flex items-center">
                  <input v-model="form.is_active" type="checkbox" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                  <label class="ml-2 block text-sm text-gray-900">Active</label>
                </div>
                <div class="flex items-center">
                  <input v-model="form.is_popular" type="checkbox" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                  <label class="ml-2 block text-sm text-gray-900">Popular</label>
                </div>
              </div>
            </div>
            
            <div class="mt-6 flex justify-end space-x-3">
              <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                Cancel
              </button>
              <button type="submit" :disabled="saving" class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50">
                {{ saving ? 'Saving...' : (showEditModal ? 'Update' : 'Create') }}
              </button>
            </div>
          </form>
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
import { showSuccess, showError, showDeleteConfirm, showWarning, showInfo } from '../../utils/sweetalert.js'

export default {
  name: 'Packages',
  components: {
    Navigation,
    SimpleFooter
  },
  data() {
    return {
      packages: [],
      loading: true,
      saving: false,
      initializing: false,
      showCreateModal: false,
      showEditModal: false,
      editingPackage: null,
      form: {
        name: '',
        description: '',
        price: 0,
        yearly_price: 0,
        voice_agents_limit: 1,
        monthly_minutes_limit: 1000,
        extra_per_minute_charge: 0,
        features: '',
        support_level: 'email',
        analytics_level: 'basic',
        is_popular: false,
        is_active: true
      }
    }
  },
  async mounted() {
    // Ensure toast system is available
    if (!this.$toast) {
      // Wait a bit for the App.vue to initialize the toast
      await this.$nextTick()
      // If still not available, wait a bit more
      if (!this.$toast) {
        setTimeout(() => {
          console.log('Toast system initialized:', !!this.$toast)
        }, 100)
      }
    }
    await this.loadPackages()
  },
  methods: {
    async loadPackages() {
      try {
        this.loading = true
        const response = await fetch('/api/admin/subscriptions/packages', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          this.packages = data.data
        } else {
          console.error('Failed to load packages')
        }
      } catch (error) {
        console.error('Error loading packages:', error)
      } finally {
        this.loading = false
      }
    },

    editPackage(pkg) {
      this.editingPackage = pkg
      this.form = { 
        ...pkg,
        features: Array.isArray(pkg.features) ? pkg.features.join(', ') : pkg.features
      }
      this.showEditModal = true
    },

    async createPackage() {
      try {
        this.saving = true
        const response = await fetch('/api/admin/subscriptions/packages', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.form)
        })
        
        if (response.ok) {
          this.showToast('Package created successfully!', 'success')
          await this.loadPackages()
          this.closeModal()
        } else {
          const error = await response.json()
          this.showToast(error.message || 'Failed to create package', 'error')
        }
      } catch (error) {
        console.error('Error creating package:', error)
        this.showToast('Failed to create package', 'error')
      } finally {
        this.saving = false
      }
    },

    async updatePackage() {
      try {
        this.saving = true
        const response = await fetch(`/api/admin/subscriptions/packages/${this.editingPackage.id}`, {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.form)
        })
        
        if (response.ok) {
          this.showToast('Package updated successfully!', 'success')
          await this.loadPackages()
          this.closeModal()
        } else {
          const error = await response.json()
          this.showToast(error.message || 'Failed to update package', 'error')
        }
      } catch (error) {
        console.error('Error updating package:', error)
        this.showToast('Failed to update package', 'error')
      } finally {
        this.saving = false
      }
    },

    async deletePackage(id) {
      const result = await showDeleteConfirm('Delete Package', 'Are you sure you want to delete this package? This action cannot be undone.')
      
      if (result.isConfirmed) {
        try {
          const response = await fetch(`/api/admin/subscriptions/packages/${id}`, {
            method: 'DELETE',
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`,
              'Content-Type': 'application/json'
            }
          })
          
          if (response.ok) {
            this.showToast('Package deleted successfully!', 'success')
            await this.loadPackages()
          } else {
            const error = await response.json()
            this.showToast(error.message || 'Failed to delete package', 'error')
          }
        } catch (error) {
          console.error('Error deleting package:', error)
          this.showToast('Failed to delete package', 'error')
        }
      }
    },

    showToast(message, type = 'info') {
      if (this.$toast && typeof this.$toast[type] === 'function') {
        this.$toast[type](message)
      } else {
        // Fallback to SweetAlert2 if toast is not available
        if (type === 'error') {
          showError('Error', message)
        } else if (type === 'success') {
          showSuccess('Success', message)
        } else if (type === 'warning') {
          showWarning('Warning', message)
        } else {
          showInfo('Info', message)
        }
      }
    },

    async initializeDefaultPackages() {
      try {
        this.initializing = true
        const response = await fetch('/api/admin/subscriptions/packages/initialize-defaults', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          this.showToast(data.message || 'Default packages initialized successfully!', 'success')
          await this.loadPackages()
        } else {
          const error = await response.json()
          this.showToast(error.message || 'Failed to initialize default packages', 'error')
        }
      } catch (error) {
        console.error('Error initializing default packages:', error)
        this.showToast('Failed to initialize default packages', 'error')
      } finally {
        this.initializing = false
      }
    },

    closeModal() {
      this.showCreateModal = false
      this.showEditModal = false
      this.editingPackage = null
      this.form = {
        name: '',
        description: '',
        price: 0,
        yearly_price: 0,
        voice_agents_limit: 1,
        monthly_minutes_limit: 1000,
        extra_per_minute_charge: 0,
        features: '',
        support_level: 'email',
        analytics_level: 'basic',
        is_popular: false,
        is_active: true
      }
    }
  }
}
</script> 