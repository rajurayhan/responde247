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
              Contact Management
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Manage contact form submissions from your website
            </p>
          </div>
        </div>

        <!-- Stats -->
        <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Messages</dt>
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
                  <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">New Messages</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.new }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Read</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.read }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Replied</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.replied }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="mt-8 bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Filters</h3>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status Filter</label>
                <select v-model="filters.status" @change="loadContacts" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                  <option value="">All Statuses</option>
                  <option value="new">New</option>
                  <option value="read">Read</option>
                  <option value="replied">Replied</option>
                  <option value="closed">Closed</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Subject Filter</label>
                <select v-model="filters.subject" @change="loadContacts" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                  <option value="">All Subjects</option>
                  <option value="general">General Inquiry</option>
                  <option value="sales">Sales Question</option>
                  <option value="support">Technical Support</option>
                  <option value="demo">Demo Request</option>
                  <option value="partnership">Partnership</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input
                  v-model="filters.search"
                  @input="debounceSearch"
                  type="text"
                  placeholder="Search by name or email..."
                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Contacts Table -->
        <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-md">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Contact Messages</h3>
          </div>
          
          <div v-if="loading" class="p-6 text-center">
            <div class="inline-flex items-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Loading contacts...
            </div>
          </div>

          <div v-else-if="contacts.length === 0" class="p-6 text-center text-gray-500">
            No contact messages found.
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="contact in contacts" :key="contact.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div class="text-sm font-medium text-gray-900">{{ contact.full_name }}</div>
                      <div class="text-sm text-gray-500">{{ contact.email }}</div>
                      <div v-if="contact.phone" class="text-sm text-gray-500">{{ contact.phone }}</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ contact.subject }}</div>
                    <div class="text-sm text-gray-500 truncate max-w-xs">{{ contact.message }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getStatusClass(contact.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                      {{ getStatusLabel(contact.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(contact.created_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button @click="viewContact(contact)" class="text-green-600 hover:text-green-900 mr-3">View</button>
                    <select @change="updateStatus(contact.id, $event.target.value)" :value="contact.status" class="text-sm border-gray-300 rounded-md">
                      <option value="new">New</option>
                      <option value="read">Read</option>
                      <option value="replied">Replied</option>
                      <option value="closed">Closed</option>
                    </select>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="pagination && pagination.total > pagination.per_page" class="px-6 py-3 border-t border-gray-200">
            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-700">
                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
              </div>
              <div class="flex space-x-2">
                <button
                  @click="loadContacts(pagination.current_page - 1)"
                  :disabled="pagination.current_page <= 1"
                  class="px-3 py-1 text-sm border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Previous
                </button>
                <button
                  @click="loadContacts(pagination.current_page + 1)"
                  :disabled="pagination.current_page >= pagination.last_page"
                  class="px-3 py-1 text-sm border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Next
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Contact Detail Modal -->
    <div v-if="selectedContact" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Contact Details</h3>
            <button @click="selectedContact = null" class="text-gray-400 hover:text-gray-600">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <p class="text-sm text-gray-900">{{ selectedContact.full_name }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <p class="text-sm text-gray-900">{{ selectedContact.email }}</p>
              </div>
            </div>
            
            <div v-if="selectedContact.phone">
              <label class="block text-sm font-medium text-gray-700">Phone</label>
              <p class="text-sm text-gray-900">{{ selectedContact.phone }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Subject</label>
              <p class="text-sm text-gray-900">{{ selectedContact.subject }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Message</label>
              <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ selectedContact.message }}</p>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select v-model="selectedContact.status" @change="updateStatus(selectedContact.id, $event.target.value)" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                  <option value="new">New</option>
                  <option value="read">Read</option>
                  <option value="replied">Replied</option>
                  <option value="closed">Closed</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Date</label>
                <p class="text-sm text-gray-900">{{ formatDate(selectedContact.created_at) }}</p>
              </div>
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
import { ref, onMounted } from 'vue'
import axios from 'axios'
import Navigation from '../shared/Navigation.vue'
import SimpleFooter from '../shared/SimpleFooter.vue'

export default {
  name: 'ContactManagement',
  components: {
    Navigation,
    SimpleFooter
  },
  setup() {
    const contacts = ref([])
    const selectedContact = ref(null)
    const loading = ref(false)
    const stats = ref({
      total: 0,
      new: 0,
      read: 0,
      replied: 0
    })
    const pagination = ref(null)
    const filters = ref({
      status: '',
      subject: '',
      search: ''
    })

    const loadContacts = async (page = 1) => {
      loading.value = true
      try {
        const params = {
          page,
          ...filters.value
        }
        
        const response = await axios.get('/api/admin/contacts', { params })
        contacts.value = response.data.data.data
        pagination.value = response.data.data
        
        // Calculate stats
        const statusCounts = contacts.value.reduce((acc, contact) => {
          acc[contact.status] = (acc[contact.status] || 0) + 1
          return acc
        }, {})
        
        stats.value = {
          total: response.data.data.total,
          new: statusCounts.new || 0,
          read: statusCounts.read || 0,
          replied: statusCounts.replied || 0
        }
      } catch (error) {
        console.error('Error loading contacts:', error)
      } finally {
        loading.value = false
      }
    }

    const updateStatus = async (contactId, status) => {
      try {
        await axios.put(`/api/admin/contacts/${contactId}/status`, { status })
        // Update the contact in the list
        const contact = contacts.value.find(c => c.id === contactId)
        if (contact) {
          contact.status = status
        }
        if (selectedContact.value && selectedContact.value.id === contactId) {
          selectedContact.value.status = status
        }
      } catch (error) {
        console.error('Error updating status:', error)
      }
    }

    const viewContact = (contact) => {
      selectedContact.value = contact
    }

    const getStatusClass = (status) => {
      const classes = {
        new: 'bg-blue-100 text-blue-800',
        read: 'bg-yellow-100 text-yellow-800',
        replied: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800'
      }
      return classes[status] || classes.new
    }

    const getStatusLabel = (status) => {
      const labels = {
        new: 'New',
        read: 'Read',
        replied: 'Replied',
        closed: 'Closed'
      }
      return labels[status] || 'New'
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

    let searchTimeout = null
    const debounceSearch = () => {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        loadContacts()
      }, 500)
    }

    onMounted(() => {
      loadContacts()
    })

    return {
      contacts,
      selectedContact,
      loading,
      stats,
      pagination,
      filters,
      loadContacts,
      updateStatus,
      viewContact,
      getStatusClass,
      getStatusLabel,
      formatDate,
      debounceSearch
    }
  }
}
</script> 