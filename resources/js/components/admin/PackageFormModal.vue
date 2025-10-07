<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
        <form @submit.prevent="savePackage">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-medium text-gray-900">
                {{ isEditing ? 'Edit Package' : 'Create Package' }}
              </h3>
              <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Form Fields -->
            <div class="space-y-6">
              <!-- Package Name -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Package Name *</label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  :class="{ 'border-red-300': errors.name }"
                  placeholder="Enter package name"
                />
                <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
              </div>

              <!-- Description -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  :class="{ 'border-red-300': errors.description }"
                  placeholder="Enter package description"
                ></textarea>
                <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description[0] }}</p>
              </div>

              <!-- Pricing -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Price ($) *</label>
                  <input
                    v-model="form.price"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    :class="{ 'border-red-300': errors.price }"
                    placeholder="0.00"
                  />
                  <p v-if="errors.price" class="mt-1 text-sm text-red-600">{{ errors.price[0] }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Yearly Price ($)</label>
                  <input
                    v-model="form.yearly_price"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    :class="{ 'border-red-300': errors.yearly_price }"
                    placeholder="0.00"
                  />
                  <p v-if="errors.yearly_price" class="mt-1 text-sm text-red-600">{{ errors.yearly_price[0] }}</p>
                </div>
              </div>

              <!-- Limits -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Voice Agents Limit</label>
                  <input
                    v-model="form.voice_agents_limit"
                    type="number"
                    min="-1"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    :class="{ 'border-red-300': errors.voice_agents_limit }"
                    placeholder="-1 for unlimited"
                  />
                  <p v-if="errors.voice_agents_limit" class="mt-1 text-sm text-red-600">{{ errors.voice_agents_limit[0] }}</p>
                  <p v-else class="text-xs text-gray-500 mt-1">Use -1 for unlimited</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Minutes Limit</label>
                  <input
                    v-model="form.monthly_minutes_limit"
                    type="number"
                    min="-1"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    :class="{ 'border-red-300': errors.monthly_minutes_limit }"
                    placeholder="-1 for unlimited"
                  />
                  <p v-if="errors.monthly_minutes_limit" class="mt-1 text-sm text-red-600">{{ errors.monthly_minutes_limit[0] }}</p>
                  <p v-else class="text-xs text-gray-500 mt-1">Use -1 for unlimited</p>
                </div>
              </div>

              <!-- Overage Charge -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Extra Per Minute Charge ($)</label>
                <input
                  v-model="form.extra_per_minute_charge"
                  type="number"
                  step="0.0001"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  :class="{ 'border-red-300': errors.extra_per_minute_charge }"
                  placeholder="0.0000"
                />
                <p v-if="errors.extra_per_minute_charge" class="mt-1 text-sm text-red-600">{{ errors.extra_per_minute_charge[0] }}</p>
                <p v-else class="text-xs text-gray-500 mt-1">Charge per minute for usage over the limit</p>
              </div>

              <!-- Features -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Features (JSON)</label>
                <textarea
                  v-model="form.features_json"
                  rows="4"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent font-mono text-sm"
                  :class="{ 'border-red-300': errors.features_json }"
                  placeholder='["Feature 1", "Feature 2", "Feature 3"]'
                ></textarea>
                <p v-if="errors.features_json" class="mt-1 text-sm text-red-600">{{ errors.features_json[0] }}</p>
                <p v-else class="text-xs text-gray-500 mt-1">Enter as JSON array of strings</p>
              </div>

              <!-- Support & Analytics -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Support Level</label>
                  <select
                    v-model="form.support_level"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    :class="{ 'border-red-300': errors.support_level }"
                  >
                    <option value="">Select support level</option>
                    <option value="standard">Standard</option>
                    <option value="priority">Priority</option>
                    <option value="enterprise">Enterprise</option>
                  </select>
                  <p v-if="errors.support_level" class="mt-1 text-sm text-red-600">{{ errors.support_level[0] }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Analytics Level</label>
                  <select
                    v-model="form.analytics_level"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    :class="{ 'border-red-300': errors.analytics_level }"
                  >
                    <option value="">Select analytics level</option>
                    <option value="basic">Basic</option>
                    <option value="advanced">Advanced</option>
                    <option value="enterprise">Enterprise</option>
                  </select>
                  <p v-if="errors.analytics_level" class="mt-1 text-sm text-red-600">{{ errors.analytics_level[0] }}</p>
                </div>
              </div>

              <!-- Options -->
              <div class="flex items-center space-x-6">
                <label class="flex items-center">
                  <input
                    v-model="form.is_popular"
                    type="checkbox"
                    class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                  />
                  <span class="ml-2 text-sm text-gray-700">Mark as Popular</span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="form.is_active"
                    type="checkbox"
                    class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                  />
                  <span class="ml-2 text-sm text-gray-700">Active</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="submit"
              :disabled="saving"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg v-if="saving" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ saving ? 'Saving...' : (isEditing ? 'Update Package' : 'Create Package') }}
            </button>
            <button
              type="button"
              @click="$emit('close')"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch, getCurrentInstance } from 'vue'

export default {
  name: 'PackageFormModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    package: {
      type: Object,
      default: null
    }
  },
  emits: ['close', 'saved'],
  setup(props, { emit }) {
    const { proxy } = getCurrentInstance()
    const saving = ref(false)
    const errors = ref({})

    const form = ref({
      name: '',
      description: '',
      price: '',
      yearly_price: '',
      voice_agents_limit: 1,
      monthly_minutes_limit: 0,
      extra_per_minute_charge: '0.0000',
      features_json: '[]',
      support_level: '',
      analytics_level: '',
      is_popular: false,
      is_active: true
    })

    const isEditing = computed(() => props.package !== null)

    // Watch for package changes to populate form
    watch(() => props.package, (newPackage) => {
      // Clear errors when form changes
      errors.value = {}
      
      if (newPackage) {
        form.value = {
          name: newPackage.name || '',
          description: newPackage.description || '',
          price: newPackage.price || '',
          yearly_price: newPackage.yearly_price || '',
          voice_agents_limit: newPackage.voice_agents_limit || 1,
          monthly_minutes_limit: newPackage.monthly_minutes_limit || 0,
          extra_per_minute_charge: newPackage.extra_per_minute_charge || '0.0000',
          features_json: newPackage.features ? JSON.stringify(newPackage.features, null, 2) : '[]',
          support_level: newPackage.support_level || '',
          analytics_level: newPackage.analytics_level || '',
          is_popular: newPackage.is_popular || false,
          is_active: newPackage.is_active !== false
        }
      } else {
        // Reset form for new package
        form.value = {
          name: '',
          description: '',
          price: '',
          yearly_price: '',
          voice_agents_limit: 1,
          monthly_minutes_limit: 0,
          extra_per_minute_charge: '0.0000',
          features_json: '[]',
          support_level: '',
          analytics_level: '',
          is_popular: false,
          is_active: true
        }
      }
    }, { immediate: true })

    const savePackage = async () => {
      saving.value = true
      errors.value = {} // Clear previous errors
      
      try {
        // Parse features JSON
        let features = []
        try {
          features = JSON.parse(form.value.features_json)
        } catch (e) {
          errors.value.features_json = ['Invalid JSON format for features']
          if (proxy.$toast && proxy.$toast.error) {
            proxy.$toast.error('Invalid JSON format for features')
          }
          saving.value = false
          return
        }

        const packageData = {
          ...form.value,
          features: features,
          price: parseFloat(form.value.price),
          yearly_price: form.value.yearly_price ? parseFloat(form.value.yearly_price) : null,
          voice_agents_limit: parseInt(form.value.voice_agents_limit),
          monthly_minutes_limit: parseInt(form.value.monthly_minutes_limit),
          extra_per_minute_charge: parseFloat(form.value.extra_per_minute_charge)
        }

        // Remove the JSON field
        delete packageData.features_json

        const url = isEditing.value 
          ? `/api/super-admin/reseller-packages/${props.package.id}`
          : '/api/super-admin/reseller-packages'
        
        const method = isEditing.value ? 'PUT' : 'POST'

        const response = await fetch(url, {
          method,
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(packageData)
        })

        const data = await response.json()
        
        if (data.success) {
          if (proxy.$toast && proxy.$toast.success) {
            proxy.$toast.success(`Package ${isEditing.value ? 'updated' : 'created'} successfully`)
          }
          emit('saved')
        } else {
          // Handle validation errors
          if (response.status === 422 && data.errors) {
            errors.value = data.errors
            if (proxy.$toast && proxy.$toast.error) {
              proxy.$toast.error('Please fix the validation errors below')
            }
          } else {
            throw new Error(data.message || `Failed to ${isEditing.value ? 'update' : 'create'} package`)
          }
        }
      } catch (error) {
        console.error('Error saving package:', error)
        if (proxy.$toast && proxy.$toast.error) {
          proxy.$toast.error(`Error ${isEditing.value ? 'updating' : 'creating'} package`)
        }
      } finally {
        saving.value = false
      }
    }

    return {
      form,
      saving,
      errors,
      isEditing,
      savePackage
    }
  }
}
</script>
