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
              Features Management
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Manage platform features and capabilities
            </p>
          </div>
          <div class="mt-4 flex md:mt-0 md:ml-4">
            <button @click="showCreateModal = true" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
              <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Add Feature
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="mt-8 flex justify-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
        </div>

        <!-- Features Table -->
        <div v-else class="mt-8 bg-white shadow overflow-hidden sm:rounded-md">
          <ul class="divide-y divide-gray-200">
            <li v-for="feature in features" :key="feature.id" class="px-6 py-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center">
                      <svg v-if="feature.icon" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="feature.icon" />
                      </svg>
                      <svg v-else class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                      </svg>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="flex items-center">
                      <h3 class="text-sm font-medium text-gray-900">{{ feature.title }}</h3>
                      <span v-if="feature.is_active" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Active
                      </span>
                      <span v-else class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        Inactive
                      </span>
                    </div>
                    <p class="text-sm text-gray-500">{{ feature.description }}</p>
                    <p class="text-xs text-gray-400 mt-1">Order: {{ feature.order }}</p>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <button @click="editFeature(feature)" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button @click="deleteFeature(feature.id)" class="text-red-400 hover:text-red-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ showEditModal ? 'Edit Feature' : 'Add New Feature' }}
          </h3>
          <form @submit.prevent="showEditModal ? updateFeature() : createFeature()">
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Title</label>
                <input v-model="form.title" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea v-model="form.description" rows="3" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"></textarea>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Icon (SVG path)</label>
                <input v-model="form.icon" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm" placeholder="M13 10V3L4 14h7v7l9-11h-7z">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Order</label>
                <input v-model="form.order" type="number" min="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
              </div>
              
              <div class="flex items-center">
                <input v-model="form.is_active" type="checkbox" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                <label class="ml-2 block text-sm text-gray-900">Active</label>
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
import { showSuccess, showError, showWarning, showInfo, showDeleteConfirm } from '../../utils/sweetalert.js'

export default {
  name: 'Features',
  components: {
    Navigation,
    SimpleFooter
  },
  data() {
    return {
      features: [],
      loading: true,
      saving: false,
      showCreateModal: false,
      showEditModal: false,
      editingFeature: null,
      form: {
        title: '',
        description: '',
        icon: '',
        order: 0,
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
    await this.loadFeatures()
  },
  methods: {
    async loadFeatures() {
      try {
        this.loading = true
        const response = await fetch('/api/admin/features', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          this.features = data.data
        } else {
          console.error('Failed to load features')
        }
      } catch (error) {
        console.error('Error loading features:', error)
      } finally {
        this.loading = false
      }
    },

    editFeature(feature) {
      this.editingFeature = feature
      this.form = { ...feature }
      this.showEditModal = true
    },

    async createFeature() {
      try {
        this.saving = true
        const response = await fetch('/api/admin/features', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.form)
        })
        
        if (response.ok) {
          this.showToast('Feature created successfully!', 'success')
          await this.loadFeatures()
          this.closeModal()
        } else {
          const error = await response.json()
          this.showToast(error.message || 'Failed to create feature', 'error')
        }
      } catch (error) {
        console.error('Error creating feature:', error)
        this.showToast('Failed to create feature', 'error')
      } finally {
        this.saving = false
      }
    },

    async updateFeature() {
      try {
        this.saving = true
        const response = await fetch(`/api/admin/features/${this.editingFeature.id}`, {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.form)
        })
        
        if (response.ok) {
          this.showToast('Feature updated successfully!', 'success')
          await this.loadFeatures()
          this.closeModal()
        } else {
          const error = await response.json()
          this.showToast(error.message || 'Failed to update feature', 'error')
        }
      } catch (error) {
        console.error('Error updating feature:', error)
        this.showToast('Failed to update feature', 'error')
      } finally {
        this.saving = false
      }
    },

    async deleteFeature(id) {
      const result = await showDeleteConfirm('Delete Feature', 'Are you sure you want to delete this feature? This action cannot be undone.')
      
      if (result.isConfirmed) {
        try {
          const response = await fetch(`/api/admin/features/${id}`, {
            method: 'DELETE',
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`,
              'Content-Type': 'application/json'
            }
          })
          
          if (response.ok) {
            this.showToast('Feature deleted successfully!', 'success')
            await this.loadFeatures()
          } else {
            const error = await response.json()
            this.showToast(error.message || 'Failed to delete feature', 'error')
          }
        } catch (error) {
          console.error('Error deleting feature:', error)
          this.showToast('Failed to delete feature', 'error')
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

    closeModal() {
      this.showCreateModal = false
      this.showEditModal = false
      this.editingFeature = null
      this.form = {
        title: '',
        description: '',
        icon: '',
        order: 0,
        is_active: true
      }
    }
  }
}
</script> 