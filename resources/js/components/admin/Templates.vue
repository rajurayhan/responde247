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
              Assistant Templates
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Manage default templates for assistant creation and updates
            </p>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto"></div>
          <p class="mt-4 text-gray-600">Loading templates...</p>
        </div>

        <!-- Templates Form -->
        <div v-else class="mt-8 space-y-8">
          <!-- System Prompt Template -->
          <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">System Prompt Template</h3>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Template Content</label>
              <div class="flex justify-between items-center mb-2">
                <span class="text-sm text-gray-600">Use {{company_name}}, {{company_industry}}, and {{company_services}} as template variables</span>
                <button
                  @click="saveTemplate('assistant_system_prompt_template', systemPromptTemplate)"
                  :disabled="saving"
                  class="text-sm bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg flex items-center"
                >
                  <svg v-if="saving" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ saving ? 'Saving...' : 'Save Template' }}
                </button>
              </div>
              <textarea
                v-model="systemPromptTemplate"
                rows="20"
                placeholder="Enter the system prompt template..."
                class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
              ></textarea>
              <p class="text-xs text-gray-500 mt-1">This template will be used for demo assistants. Template variables will be automatically replaced with company information.</p>
            </div>
          </div>

          <!-- First Message Template -->
          <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">First Message Template</h3>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Template Content</label>
              <div class="flex justify-between items-center mb-2">
                <span class="text-sm text-gray-600">Use {{company_name}}, {{company_industry}}, and {{company_services}} as template variables</span>
                <button
                  @click="saveTemplate('assistant_first_message_template', firstMessageTemplate)"
                  :disabled="saving"
                  class="text-sm bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg flex items-center"
                >
                  <svg v-if="saving" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ saving ? 'Saving...' : 'Save Template' }}
                </button>
              </div>
              <textarea
                v-model="firstMessageTemplate"
                rows="4"
                maxlength="1000"
                placeholder="Enter the first message template..."
                class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
              ></textarea>
              <p class="text-xs text-gray-500 mt-1">Maximum 1000 characters. This template will be used for demo assistants.</p>
            </div>
          </div>

          <!-- End Call Message Template -->
          <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">End Call Message Template</h3>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Template Content</label>
              <div class="flex justify-between items-center mb-2">
                <span class="text-sm text-gray-600">Use {{company_name}}, {{company_industry}}, and {{company_services}} as template variables</span>
                <button
                  @click="saveTemplate('assistant_end_call_message_template', endCallMessageTemplate)"
                  :disabled="saving"
                  class="text-sm bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg flex items-center"
                >
                  <svg v-if="saving" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ saving ? 'Saving...' : 'Save Template' }}
                </button>
              </div>
              <textarea
                v-model="endCallMessageTemplate"
                rows="4"
                maxlength="1000"
                placeholder="Enter the end call message template..."
                class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
              ></textarea>
              <p class="text-xs text-gray-500 mt-1">Maximum 1000 characters. This template will be used for demo assistants.</p>
            </div>
          </div>

          <!-- Template Preview -->
          <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Template Preview</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                <input
                  v-model="previewData.company_name"
                  type="text"
                  placeholder="Your Company Name"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Industry</label>
                <input
                  v-model="previewData.industry"
                  type="text"
                  placeholder="e.g., Technology, Healthcare"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Services/Products</label>
                <input
                  v-model="previewData.services_products"
                  type="text"
                  placeholder="e.g., Software Development"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                />
              </div>
            </div>
            
            <div class="mt-6 space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">System Prompt Preview</label>
                <div class="bg-gray-50 border border-gray-200 rounded-md p-4 text-sm text-gray-700 max-h-40 overflow-y-auto">
                  <pre class="whitespace-pre-wrap">{{ processedSystemPrompt }}</pre>
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">First Message Preview</label>
                <div class="bg-gray-50 border border-gray-200 rounded-md p-4 text-sm text-gray-700">
                  <pre class="whitespace-pre-wrap">{{ processedFirstMessage }}</pre>
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">End Call Message Preview</label>
                <div class="bg-gray-50 border border-gray-200 rounded-md p-4 text-sm text-gray-700">
                  <pre class="whitespace-pre-wrap">{{ processedEndCallMessage }}</pre>
                </div>
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
import { ref, onMounted, computed } from 'vue'
import Navigation from '../shared/Navigation.vue'
import SimpleFooter from '../shared/SimpleFooter.vue'
import axios from 'axios'
import { showError, showSuccess } from '../../utils/sweetalert.js'

export default {
  name: 'Templates',
  components: {
    Navigation,
    SimpleFooter
  },
  setup() {
    const loading = ref(false)
    const saving = ref(false)
    
    const systemPromptTemplate = ref('')
    const firstMessageTemplate = ref('')
    const endCallMessageTemplate = ref('')
    
    const previewData = ref({
      company_name: 'Acme Corporation',
      industry: 'Technology',
      services_products: 'Software Development and Consulting'
    })

    // Computed properties for template preview
    const processedSystemPrompt = computed(() => {
      let prompt = systemPromptTemplate.value || ''
      
      // Replace template variables with preview data
      prompt = prompt.replace(/\{\{company_name\}\}/g, previewData.value.company_name || '{{company_name}}')
      prompt = prompt.replace(/\{\{company_industry\}\}/g, previewData.value.industry || '{{company_industry}}')
      prompt = prompt.replace(/\{\{company_services\}\}/g, previewData.value.services_products || '{{company_services}}')
      
      return prompt
    })

    const processedFirstMessage = computed(() => {
      let message = firstMessageTemplate.value || ''
      
      // Replace template variables with preview data
      message = message.replace(/\{\{company_name\}\}/g, previewData.value.company_name || '{{company_name}}')
      message = message.replace(/\{\{company_industry\}\}/g, previewData.value.industry || '{{company_industry}}')
      message = message.replace(/\{\{company_services\}\}/g, previewData.value.services_products || '{{company_services}}')
      
      return message
    })

    const processedEndCallMessage = computed(() => {
      let message = endCallMessageTemplate.value || ''
      
      // Replace template variables with preview data
      message = message.replace(/\{\{company_name\}\}/g, previewData.value.company_name || '{{company_name}}')
      message = message.replace(/\{\{company_industry\}\}/g, previewData.value.industry || '{{company_industry}}')
      message = message.replace(/\{\{company_services\}\}/g, previewData.value.services_products || '{{company_services}}')
      
      return message
    })

    const loadTemplates = async () => {
      try {
        loading.value = true
        const response = await axios.get('/api/assistant-templates')
        
        if (response.data.success) {
          const templates = response.data.data
          systemPromptTemplate.value = templates.system_prompt || ''
          firstMessageTemplate.value = templates.first_message || ''
          endCallMessageTemplate.value = templates.end_call_message || ''
        }
      } catch (error) {
        console.error('Error loading templates:', error)
        await showError('Error', 'Failed to load templates')
      } finally {
        loading.value = false
      }
    }

    const saveTemplate = async (key, value) => {
      try {
        saving.value = true
        const response = await axios.put(`/api/admin/settings/${key}`, {
          value: value
        })
        
        if (response.data.success) {
          await showSuccess('Success', 'Template saved successfully')
        }
      } catch (error) {
        console.error('Error saving template:', error)
        await showError('Error', 'Failed to save template')
      } finally {
        saving.value = false
      }
    }

    onMounted(() => {
      loadTemplates()
    })

    return {
      loading,
      saving,
      systemPromptTemplate,
      firstMessageTemplate,
      endCallMessageTemplate,
      previewData,
      processedSystemPrompt,
      processedFirstMessage,
      processedEndCallMessage,
      saveTemplate
    }
  }
}
</script> 