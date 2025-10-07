<template>
  <div v-if="show" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="closeModal">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 xl:w-2/5 shadow-lg rounded-md bg-white" @click.stop>
      <div class="mt-3">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b">
          <h3 class="text-lg font-medium text-gray-900">
            {{ isEditing ? 'Edit Reseller' : 'Add New Reseller' }}
          </h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Modal Body -->
        <form @submit.prevent="saveReseller" class="mt-6 space-y-6">
          <!-- Organization Name -->
          <div>
            <label for="org_name" class="block text-sm font-medium text-gray-700">
              Organization Name *
            </label>
            <input
              id="org_name"
              v-model="form.org_name"
              type="text"
              required
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
              :class="{ 'border-red-300': errors.org_name }"
              placeholder="Enter organization name"
            />
            <p v-if="errors.org_name" class="mt-1 text-sm text-red-600">{{ errors.org_name[0] }}</p>
          </div>

          <!-- Company Email -->
          <div>
            <label for="company_email" class="block text-sm font-medium text-gray-700">
              Company Email
            </label>
            <input
              id="company_email"
              v-model="form.company_email"
              type="email"
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
              :class="{ 'border-red-300': errors.company_email }"
              placeholder="contact@company.com"
            />
            <p v-if="errors.company_email" class="mt-1 text-sm text-red-600">{{ errors.company_email[0] }}</p>
          </div>

          <!-- Domain -->
          <div>
            <label for="domain" class="block text-sm font-medium text-gray-700">
              Domain *
            </label>
            <input
              id="domain"
              v-model="form.domain"
              type="text"
              required
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
              :class="{ 'border-red-300': errors.domain }"
              placeholder="company.com"
            />
            <p v-if="errors.domain" class="mt-1 text-sm text-red-600">{{ errors.domain[0] }}</p>
          </div>

          <!-- Logo Upload -->
          <div>
            <label for="logo_file" class="block text-sm font-medium text-gray-700">
              Logo{{ !isEditing ? ' *' : '' }}
            </label>
            <div class="mt-1 flex items-center space-x-4">
              <input
                id="logo_file"
                type="file"
                ref="logoFile"
                @change="handleLogoUpload"
                accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100"
              />
            </div>
            <div v-if="errors.logo_file" class="mt-1 text-sm text-red-600">{{ errors.logo_file[0] }}</div>
            <div v-if="uploadErrors.logo" class="mt-1 text-sm text-red-600">{{ uploadErrors.logo }}</div>
            <div v-if="selectedLogoFile && !uploadErrors.logo" class="mt-2 text-sm text-green-600">
              <strong>Ready to upload:</strong> {{ selectedLogoFile.name }} ({{ formatFileSize(selectedLogoFile.size) }})
            </div>
            <!-- Logo Preview -->
            <div v-if="logoPreviewUrl || form.logo_address" class="mt-3">
              <div class="text-sm text-gray-700 mb-2">Logo Preview:</div>
              <div class="h-16 w-16 rounded-lg overflow-hidden border border-gray-200">
                <img 
                  :src="logoPreviewUrl || form.logo_address" 
                  :alt="form.org_name" 
                  class="h-16 w-16 object-cover"
                  @error="handleImageError"
                />
              </div>
            </div>
          </div>

          <!-- Status -->
          <div>
            <label for="status" class="block text-sm font-medium text-gray-700">
              Status *
            </label>
            <select
              id="status"
              v-model="form.status"
              required
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
              :class="{ 'border-red-300': errors.status }"
            >
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
            <p v-if="errors.status" class="mt-1 text-sm text-red-600">{{ errors.status[0] }}</p>
          </div>

          <!-- Admin User Section (only when creating new reseller) -->
          <div v-if="!isEditing" class="border-t border-gray-200 pt-6">
            <h4 class="text-md font-medium text-gray-900 mb-4">
              Default Admin User *
              <span class="text-sm font-normal text-gray-500 ml-2">(Required information for the reseller)</span>
            </h4>
            
            <!-- Admin Name -->
            <div class="mb-4">
              <label for="admin_name" class="block text-sm font-medium text-gray-700">
                Admin Name *
              </label>
              <input
                id="admin_name"
                v-model="form.admin_name"
                type="text"
                required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                :class="{ 'border-red-300': errors.admin_name }"
                placeholder="Enter full name"
              />
              <p v-if="errors.admin_name" class="mt-1 text-sm text-red-600">{{ errors.admin_name[0] }}</p>
            </div>

            <!-- Admin Email -->
            <div class="mb-4">
              <label for="admin_email" class="block text-sm font-medium text-gray-700">
                Admin Email *
              </label>
              <input
                id="admin_email"
                v-model="form.admin_email"
                type="email"
                required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                :class="{ 'border-red-300': errors.admin_email }"
                placeholder="admin@company.com"
              />
              <p v-if="errors.admin_email" class="mt-1 text-sm text-red-600">{{ errors.admin_email[0] }}</p>
            </div>

            <!-- Admin Phone -->
            <div class="mb-4">
              <label for="admin_phone" class="block text-sm font-medium text-gray-700">
                Admin Phone *
              </label>
              <input
                id="admin_phone"
                v-model="form.admin_phone"
                type="tel"
                required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                :class="{ 'border-red-300': errors.admin_phone }"
                placeholder="+1 (555) 123-4567"
              />
              <p v-if="errors.admin_phone" class="mt-1 text-sm text-red-600">{{ errors.admin_phone[0] }}</p>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
              <div class="flex">
                <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.834-1.964-.834-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <div class="ml-3">
                  <p class="text-sm text-blue-700">
                    <strong>⚠️ Required:</strong> Admin user information is mandatory for creating a new reseller. 
                    This admin user will have full access to manage the reseller's settings and users.
                  </p>
                  <p class="text-sm text-blue-700 mt-2">
                    <strong>📧 Welcome Email:</strong> The admin user will receive a welcome email with their login credentials 
                    and temporary password. They will be prompted to change the password on first login for security.
                  </p>
                </div>
              </div>
            </div>
          </div>


          <!-- Form Actions -->
          <div class="flex justify-end space-x-3 pt-6 border-t">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="saving" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Saving...
              </span>
              <span v-else>
                {{ isEditing ? 'Update Reseller' : 'Create Reseller' }}
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch, getCurrentInstance } from 'vue'
import axios from 'axios'

export default {
  name: 'ResellerFormModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    reseller: {
      type: Object,
      default: null
    }
  },
  emits: ['close', 'saved'],
  setup(props, { emit }) {
    // Get the current instance to access $toast
    const { proxy } = getCurrentInstance()
    const saving = ref(false)
    const errors = ref({})
    const uploadErrors = ref({})
    const selectedLogoFile = ref(null)
    const logoPreviewUrl = ref(null)
    const form = ref({
      org_name: '',
      company_email: '',
      domain: '',
      logo_address: '',
      status: 'active',
      // Admin user fields (only for new resellers)
      admin_name: '',
      admin_email: '',
      admin_phone: ''
    })

    const isEditing = computed(() => props.reseller && props.reseller.id)

    // Reset form when modal opens/closes or reseller changes
    watch([() => props.show, () => props.reseller], () => {
      if (props.show) {
        resetForm()
        errors.value = {}
        uploadErrors.value = {}
        selectedLogoFile.value = null
        logoPreviewUrl.value = null
      }
    })

    const resetForm = () => {
      if (props.reseller) {
        // Editing existing reseller - don't include admin user fields
        form.value = {
          org_name: props.reseller.org_name || '',
          company_email: props.reseller.company_email || '',
          domain: props.reseller.domain || '',
          logo_address: props.reseller.logo_address || '',
          status: props.reseller.status || 'active',
          admin_name: '',
          admin_email: '',
          admin_phone: ''
        }
      } else {
        // Creating new reseller - include admin user fields
        form.value = {
          org_name: '',
          company_email: '',
          domain: '',
          logo_address: '',
          status: 'active',
          admin_name: '',
          admin_email: '',
          admin_phone: ''
        }
      }
    }

    const closeModal = () => {
      emit('close')
    }

    const handleImageError = () => {
      console.log('Logo image failed to load')
    }

    // File upload validation
    const validateImageFile = (file, type) => {
      if (!file) return null

      // Check file type
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']
      if (!allowedTypes.includes(file.type)) {
        return `${type === 'logo' ? 'Logo' : 'Image'} must be a JPEG, PNG, JPG, GIF, or WebP file`
      }

      // Check file size
      const maxSize = 2 * 1024 * 1024 // 2MB
      if (file.size > maxSize) {
        return `${type === 'logo' ? 'Logo' : 'Image'} file size must not exceed 2MB`
      }

      return null
    }

    // Logo file upload handler
    const handleLogoUpload = (event) => {
      const file = event.target.files[0]
      if (file) {
        // Clear previous error
        uploadErrors.value.logo = null
        
        // Validate file
        const error = validateImageFile(file, 'logo')
        if (error) {
          uploadErrors.value.logo = error
          event.target.value = ''
          return
        }
        
        // Store the file for upload
        selectedLogoFile.value = file
        // Show preview
        logoPreviewUrl.value = URL.createObjectURL(file)
      } else {
        selectedLogoFile.value = null
        logoPreviewUrl.value = null
      }
    }

    // Format file size helper
    const formatFileSize = (bytes) => {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    }

    const saveReseller = async () => {
      saving.value = true
      errors.value = {}
      uploadErrors.value = {}

      try {
        // Debug: Check form values before submission
        console.log('=== Form Submission Debug ===')
        console.log('Current form values:', JSON.stringify(form.value, null, 2))
        console.log('Form org_name specifically:', form.value.org_name)
        
        // Check if required fields are actually filled
        if (!form.value.org_name || form.value.org_name.trim() === '') {
          console.error('org_name is missing or empty')
          errors.value.org_name = ['Organization name is required']
          saving.value = false
          return
        }

        const url = isEditing.value 
          ? `/api/admin/resellers/${props.reseller.id}`
          : '/api/admin/resellers'
        
        const method = isEditing.value ? 'PUT' : 'POST'

        // Prepare form data with file upload
        const formData = new FormData()
        
        // Add basic form fields with robust validation
        const orgName = form.value.org_name?.toString().trim()
        console.log('Processing org_name:', orgName)
        
        formData.append('org_name', orgName || '')
        formData.append('company_email', (form.value.company_email || '').toString().trim())
        formData.append('domain', (form.value.domain || '').toString().trim())
        formData.append('status', (form.value.status || 'active').toString())
        
        // Only include admin fields when creating new reseller
        if (!isEditing.value) {
          formData.append('admin_name', (form.value.admin_name || '').toString().trim())
          formData.append('admin_email', (form.value.admin_email || '').toString().trim())
          formData.append('admin_phone', (form.value.admin_phone || '').toString().trim())
        }

        // Add logo file if selected
        if (selectedLogoFile.value) {
          formData.append('logo_file', selectedLogoFile.value)
        }

        // Debug: Log FormData contents
        console.log('=== FormData Assembly Complete ===')
        console.log('form.value:', form.value)
        console.log('FormData entries:')
        for (let [key, value] of formData.entries()) {
          console.log(`${key}:`, value, `(${typeof value})`)
        }
        console.log('org_name in FormData:', formData.get('org_name'))
        console.log('=== End FormData Debug ===')

        // Use axios instead of fetch for multipart form data
        const requestConfig = {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'multipart/form-data',
            'Accept': 'application/json',
          }
        }

        let response
        if (isEditing.value) {
          // For updates with file uploads, use POST with _method override
          formData.append('_method', 'PUT')
          response = await axios.post(url, formData, requestConfig)
        } else {
          response = await axios.post(url, formData, requestConfig)
        }

        const data = response.data

        if (data.success) {
          if (proxy.$toast && proxy.$toast.success) {
            let message = isEditing.value ? 'Reseller updated successfully' : 'Reseller created successfully'
            if (!isEditing.value && data.data.admin_user) {
              message += `. Admin user created and welcome email sent to: ${data.data.admin_user.email}`
            }
            proxy.$toast.success(message)
          }
          emit('saved', data.data)
        } else {
          if (data.errors) {
            errors.value = data.errors
          } else {
            throw new Error(data.message || 'Failed to save reseller')
          }
        }
      } catch (error) {
        console.error('Error saving reseller:', error)
        
        // Handle axios error response
        if (error.response && error.response.data) {
          if (error.response.data.errors) {
            errors.value = error.response.data.errors
          } else {
            console.error('Backend error details:', error.response.data)
          }
        }
        
        if (proxy.$toast && proxy.$toast.error) {
          proxy.$toast.error(error.response?.data?.message || 'Error saving reseller')
        }
      } finally {
        saving.value = false
      }
    }

    return {
      saving,
      errors,
      uploadErrors,
      form,
      isEditing,
      selectedLogoFile,
      logoPreviewUrl,
      closeModal,
      handleImageError,
      handleLogoUpload,
      formatFileSize,
      saveReseller
    }
  }
}
</script>
