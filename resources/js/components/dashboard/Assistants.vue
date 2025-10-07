<template>
  <div>
    <!-- Header -->
    <div class="border-b border-gray-200 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex justify-between items-center">
          <div class="flex items-center">
            <div class="h-8 w-8 bg-green-600 rounded-lg flex items-center justify-center mr-3">
              <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
              </svg>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Assistants</h1>
              <p class="text-gray-600">Manage your AI voice agents</p>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <button class="text-gray-500 hover:text-gray-700 px-3 py-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </button>
            <button class="text-gray-500 hover:text-gray-700 px-3 py-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Search and Filter -->
      <div class="mb-6">
        <div class="flex items-center space-x-4">
          <div class="flex-1">
            <div class="relative">
              <input
                type="text"
                placeholder="Search Assistants"
                class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2 text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
              />
              <svg class="absolute right-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Loading assistants...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <div class="text-red-500 mb-4">
          <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        </div>
        <p class="text-gray-600">{{ error }}</p>
        <button @click="loadAssistants" class="mt-4 text-green-600 hover:text-green-700">
          Try again
        </button>
      </div>

      <!-- Empty State -->
      <div v-else-if="assistants.length === 0" class="text-center py-12">
        <div class="text-gray-400 mb-4">
          <svg class="w-24 h-24 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No assistants available</h3>
        <p class="text-gray-600 mb-6">No assistants are available for editing</p>
      </div>

      <!-- Assistants List -->
      <div v-else class="space-y-3">
        <div
          v-for="assistant in assistants"
          :key="assistant.id"
          class="bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors cursor-pointer shadow-sm"
          @click="selectAssistant(assistant)"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center mr-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ assistant.name }}</h3>
                <div class="flex items-center space-x-2 mt-1">
                  <span 
                    :class="[
                      'px-2 py-1 rounded-full text-xs font-medium',
                      assistant.type === 'demo' 
                        ? 'bg-blue-100 text-blue-800' 
                        : 'bg-green-100 text-green-800'
                    ]"
                  >
                    {{ assistant.type === 'demo' ? 'Demo' : 'Production' }}
                  </span>
                  <span v-if="assistant.phone_number" class="text-xs text-gray-500">
                    📞 {{ assistant.phone_number }}
                  </span>
                </div>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <router-link
                :to="`/assistants/${assistant.id}/edit`"
                class="text-gray-500 hover:text-gray-700 p-1"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </router-link>
              <button
                @click.stop="deleteAssistant(assistant.id)"
                class="text-gray-500 hover:text-red-600 p-1"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>


  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'Assistants',
  setup() {
    const router = useRouter()
    const assistants = ref([])
    const loading = ref(true)
    const error = ref(null)
    const activeMenu = ref(null)

    const loadAssistants = async () => {
      try {
        loading.value = true
        error.value = null
        const response = await axios.get('/api/assistants', {
          params: {
            per_page: 100 // Load more for this component since it's a simple list
          }
        })
        assistants.value = response.data.data || []
      } catch (err) {
        error.value = 'Failed to load assistants. Please try again.'
      } finally {
        loading.value = false
      }
    }



    const deleteAssistant = async (assistantId) => {
      if (!confirm('Are you sure you want to delete this assistant?')) return
      
      try {
        await axios.delete(`/api/assistants/${assistantId}`)
        assistants.value = assistants.value.filter(a => a.id !== assistantId)
        activeMenu.value = null
      } catch (err) {
        error.value = 'Failed to delete assistant. Please try again.'
      }
    }



    const selectAssistant = (assistant) => {
      // Navigate to edit page when assistant row is clicked
      router.push(`/assistants/${assistant.id}/edit`)
    }

    const viewStats = (assistantId) => {
      // TODO: Implement stats view
      activeMenu.value = null
    }

    const showAssistantMenu = (assistantId) => {
      activeMenu.value = activeMenu.value === assistantId ? null : assistantId
    }



    // Close menu when clicking outside
    const handleClickOutside = (event) => {
      if (!event.target.closest('.relative')) {
        activeMenu.value = null
      }
    }

    onMounted(() => {
      loadAssistants()
      document.addEventListener('click', handleClickOutside)
    })

    return {
      assistants,
      loading,
      error,
      activeMenu,
      loadAssistants,
      deleteAssistant,
      selectAssistant,
      viewStats,
      showAssistantMenu
    }
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.hover\:bg-gray-750:hover {
  background-color: #374151;
}
</style> 