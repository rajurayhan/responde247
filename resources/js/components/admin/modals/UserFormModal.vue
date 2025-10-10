<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="close"></div>

      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form @submit.prevent="saveUser">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="w-full">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                  {{ isEditing ? 'Edit User' : 'Create User' }}
                </h3>

                <div class="space-y-4">
                  <!-- Name -->
                  <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input
                      type="text"
                      id="name"
                      v-model="form.name"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                  </div>

                  <!-- Email -->
                  <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input
                      type="email"
                      id="email"
                      v-model="form.email"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                  </div>

                  <!-- Password -->
                  <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                      Password {{ isEditing ? '(leave blank to keep current)' : '' }}
                    </label>
                    <input
                      type="password"
                      id="password"
                      v-model="form.password"
                      :required="!isEditing"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                  </div>

                  <!-- Role -->
                  <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                    <select
                      id="role"
                      v-model="form.role"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                      <option value="user">User</option>
                      <option value="admin">Admin</option>
                      <option value="content_admin">Content Admin</option>
                      <option value="reseller_admin">Reseller Admin</option>
                    </select>
                  </div>

                  <!-- Reseller -->
                  <div>
                    <label for="reseller_id" class="block text-sm font-medium text-gray-700">Reseller</label>
                    <select
                      id="reseller_id"
                      v-model="form.reseller_id"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                      <option value="">No Reseller</option>
                      <option v-for="reseller in resellers" :key="reseller.id" :value="reseller.id">
                        {{ reseller.org_name }}
                      </option>
                    </select>
                  </div>

                  <!-- Status -->
                  <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select
                      id="status"
                      v-model="form.status"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                      <option value="active">Active</option>
                      <option value="inactive">Inactive</option>
                    </select>
                  </div>

                  <!-- Phone -->
                  <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                    <input
                      type="tel"
                      id="phone"
                      v-model="form.phone"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                  </div>

                  <!-- Company -->
                  <div>
                    <label for="company" class="block text-sm font-medium text-gray-700">Company</label>
                    <input
                      type="text"
                      id="company"
                      v-model="form.company"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                  </div>

                  <!-- Bio -->
                  <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700">Bio</label>
                    <textarea
                      id="bio"
                      v-model="form.bio"
                      rows="3"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    ></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="submit"
              :disabled="saving"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="saving" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
              {{ saving ? 'Saving...' : (isEditing ? 'Update User' : 'Create User') }}
            </button>
            <button
              type="button"
              @click="close"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
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
import { ref, computed, watch } from 'vue'

export default {
  name: 'UserFormModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    user: {
      type: Object,
      default: null
    },
    resellers: {
      type: Array,
      default: () => []
    }
  },
  emits: ['close', 'saved'],
  setup(props, { emit }) {
    const saving = ref(false)
    
    const form = ref({
      name: '',
      email: '',
      password: '',
      role: 'user',
      reseller_id: '',
      status: 'active',
      phone: '',
      company: '',
      bio: ''
    })

    const isEditing = computed(() => !!props.user)

    // Watch for user prop changes to populate form
    watch(() => props.user, (newUser) => {
      if (newUser) {
        form.value = {
          name: newUser.name || '',
          email: newUser.email || '',
          password: '',
          role: newUser.role || 'user',
          reseller_id: newUser.reseller?.id || '',
          status: newUser.status || 'active',
          phone: newUser.phone || '',
          company: newUser.company || '',
          bio: newUser.bio || ''
        }
      } else {
        // Reset form for new user
        form.value = {
          name: '',
          email: '',
          password: '',
          role: 'user',
          reseller_id: '',
          status: 'active',
          phone: '',
          company: '',
          bio: ''
        }
      }
    }, { immediate: true })

    const saveUser = async () => {
      saving.value = true
      
      try {
        const url = isEditing.value 
          ? `/api/super-admin/users/${props.user.id}`
          : '/api/super-admin/users'
        
        const method = isEditing.value ? 'PUT' : 'POST'
        
        const payload = { ...form.value }
        
        // Remove password if empty for updates
        if (isEditing.value && !payload.password) {
          delete payload.password
        }

        const response = await fetch(url, {
          method,
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(payload)
        })

        const data = await response.json()

        if (data.success) {
          emit('saved')
        } else {
          alert('Failed to save user: ' + (data.message || 'Unknown error'))
        }
      } catch (error) {
        console.error('Error saving user:', error)
        alert('Error saving user')
      } finally {
        saving.value = false
      }
    }

    const close = () => {
      emit('close')
    }

    return {
      form,
      saving,
      isEditing,
      saveUser,
      close
    }
  }
}
</script>
