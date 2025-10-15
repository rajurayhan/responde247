<template>
  <div class="space-y-6">
    <!-- Model Provider Selection -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Model Provider *</label>
      <select
        v-model="modelConfig.provider"
        @change="onProviderChange"
        data-field="model_provider"
        :class="[
          'w-full px-3 py-2 border rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500',
          fieldErrors.provider 
            ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
            : 'border-gray-300 focus:border-green-500 bg-white'
        ]"
      >
        <option value="openai">OpenAI</option>
        <option value="anthropic" disabled>Anthropic (Upcoming)</option>
        <option value="google" disabled>Google (Upcoming)</option>
        <option value="azure" disabled>Azure OpenAI (Upcoming)</option>
        <option value="cohere" disabled>Cohere (Upcoming)</option>
        <option value="mistral" disabled>Mistral (Upcoming)</option>
        <option value="groq" disabled>Groq (Upcoming)</option>
        <option value="deepgram" disabled>Deepgram (Upcoming)</option>
        <option value="perplexity" disabled>Perplexity (Upcoming)</option>
        <option value="openrouter" disabled>OpenRouter (Upcoming)</option>
        <option value="together" disabled>Together AI (Upcoming)</option>
        <option value="replicate" disabled>Replicate (Upcoming)</option>
        <option value="huggingface" disabled>Hugging Face (Upcoming)</option>
        <option value="custom" disabled>Custom (Upcoming)</option>
      </select>
      <p v-if="fieldErrors.provider" class="text-xs text-red-600 mt-1">{{ fieldErrors.provider }}</p>
      <p v-else class="text-xs text-gray-500 mt-1">Select the AI model provider for your assistant</p>
    </div>

    <!-- Provider-Specific Configuration -->
    <div v-if="modelConfig.provider" class="space-y-6">
      <!-- OpenAI Configuration -->
      <div v-if="modelConfig.provider === 'openai'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Model *</label>
          <select
            v-model="modelConfig.model"
            data-field="model_model"
            :class="[
              'w-full px-3 py-2 border rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500',
              fieldErrors.model 
                ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                : 'border-gray-300 focus:border-green-500 bg-white'
            ]"
          >
            <option value="gpt-5-mini">ChatGPT 5 Mini</option>
            <option value="gpt-5-nano">ChatGPT 5 Nano</option>
            <option value="gpt-4.1">ChatGPT 4.1</option>
            <option value="gpt-4.1-mini">ChatGPT 4.1 Mini</option>
            <option value="gpt-4.1-nano">ChatGPT 4.1 Nano</option>
            <option value="gpt-4o-mini">ChatGPT 4o Mini Cluster</option>
            <option value="gpt-4o">ChatGPT 4o Cluster</option>
            <option value="chatgpt-4o-latest">ChatGPT 4o (latest) Cluster</option>
          </select>
          <p v-if="fieldErrors.model" class="text-xs text-red-600 mt-1">{{ fieldErrors.model }}</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          
        </div>
      </div>

      <!-- Anthropic Configuration -->
      <div v-if="modelConfig.provider === 'anthropic'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Model *</label>
          <select
            v-model="modelConfig.model"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          >
            <option value="claude-3-5-sonnet-20241022">Claude 3.5 Sonnet (Latest)</option>
            <option value="claude-3-5-haiku-20241022">Claude 3.5 Haiku</option>
            <option value="claude-3-opus-20240229">Claude 3 Opus</option>
            <option value="claude-3-sonnet-20240229">Claude 3 Sonnet</option>
            <option value="claude-3-haiku-20240307">Claude 3 Haiku</option>
          </select>
        </div>
        

      </div>

      <!-- Google Configuration -->
      <div v-if="modelConfig.provider === 'google'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Model *</label>
          <select
            v-model="modelConfig.model"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          >
            <option value="gemini-1.5-pro">Gemini 1.5 Pro</option>
            <option value="gemini-1.5-flash">Gemini 1.5 Flash</option>
            <option value="gemini-pro">Gemini Pro</option>
          </select>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          
        </div>

      </div>

      <!-- Azure Configuration -->
      <div v-if="modelConfig.provider === 'azure'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Model *</label>
          <select
            v-model="modelConfig.model"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          >
            <option value="gpt-4o">GPT-4o</option>
            <option value="gpt-4-turbo">GPT-4 Turbo</option>
            <option value="gpt-4">GPT-4</option>
            <option value="gpt-35-turbo">GPT-3.5 Turbo</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">API Version</label>
          <input
            v-model="modelConfig.apiVersion"
            type="text"
            placeholder="2024-02-15-preview"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          />
          <p class="text-xs text-gray-500 mt-1">Azure OpenAI API version</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          
        </div>
      </div>

      <!-- Cohere Configuration -->
      <div v-if="modelConfig.provider === 'cohere'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Model *</label>
          <select
            v-model="modelConfig.model"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          >
            <option value="command-r-plus">Command R+</option>
            <option value="command-r">Command R</option>
            <option value="command">Command</option>
            <option value="command-light">Command Light</option>
          </select>
        </div>
        
      </div>

      <!-- Mistral Configuration -->
      <div v-if="modelConfig.provider === 'mistral'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Model *</label>
          <select
            v-model="modelConfig.model"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          >
            <option value="mistral-large-latest">Mistral Large</option>
            <option value="mistral-medium-latest">Mistral Medium</option>
            <option value="mistral-small-latest">Mistral Small</option>
          </select>
        </div>

      </div>

      <!-- Groq Configuration -->
      <div v-if="modelConfig.provider === 'groq'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Model *</label>
          <select
            v-model="modelConfig.model"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          >
            <option value="llama-3.1-70b-versatile">Llama 3.1 70B</option>
            <option value="llama-3.1-8b-instant">Llama 3.1 8B</option>
            <option value="mixtral-8x7b-32768">Mixtral 8x7B</option>
            <option value="gemma-7b-it">Gemma 7B</option>
          </select>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          
        </div>

      </div>

      <!-- Deepgram Configuration -->
      <div v-if="modelConfig.provider === 'deepgram'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Model *</label>
          <select
            v-model="modelConfig.model"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          >
            <option value="nova-2">Nova 2</option>
            <option value="nova">Nova</option>
          </select>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          
        </div>
      </div>

      <!-- Perplexity Configuration -->
      <div v-if="modelConfig.provider === 'perplexity'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Model *</label>
          <select
            v-model="modelConfig.model"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          >
            <option value="llama-3.1-sonar-large-128k-online">Llama 3.1 Sonar Large 128K Online</option>
            <option value="llama-3.1-sonar-small-128k-online">Llama 3.1 Sonar Small 128K Online</option>
            <option value="llama-3.1-sonar-huge-128k-online">Llama 3.1 Sonar Huge 128K Online</option>
          </select>
        </div>
      </div>

      <!-- Custom Configuration -->
      <div v-if="modelConfig.provider === 'custom'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Custom Model Name *</label>
          <input
            v-model="modelConfig.model"
            type="text"
            placeholder="Enter custom model name"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
          />
          <p class="text-xs text-gray-500 mt-1">Enter the exact model name as it appears in your provider</p>
        </div>
      </div>
    </div>

    <!-- System Messages -->
    <div class="border-t border-gray-200 pt-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">System Messages</h3>
      
      <div class="space-y-4">
        <div v-for="(message, index) in modelConfig.messages" :key="index" class="border border-gray-200 rounded-lg p-4">
          <div class="flex items-center justify-between mb-2">
            <label class="text-sm font-medium text-gray-700">Message {{ index + 1 }}</label>
            <button
              v-if="modelConfig.messages.length > 1"
              @click="removeMessage(index)"
              type="button"
              class="text-red-600 hover:text-red-800 text-sm"
            >
              Remove
            </button>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Role</label>
              <select
                v-model="message.role"
                class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500"
              >
                <option value="system">System</option>
                <option value="user">User</option>
                <option value="assistant">Assistant</option>
              </select>
            </div>
            
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Content Type</label>
              <select
                v-model="message.contentType"
                class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500"
              >
                <option value="text">Text</option>
                <option value="image_url">Image URL</option>
                <option value="function_call">Function Call</option>
              </select>
            </div>
            
            <div v-if="message.contentType === 'image_url'">
              <label class="block text-xs font-medium text-gray-600 mb-1">Image URL</label>
              <input
                v-model="message.imageUrl"
                type="url"
                placeholder="https://example.com/image.jpg"
                class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500"
              />
            </div>
          </div>
          
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Content</label>
            <div class="relative">
              <textarea
                v-model="message.content"
                rows="30"
                placeholder="Enter message content..."
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
              ></textarea>
              
              <!-- Template/Actual Data Toggle for System Messages -->
              <div v-if="message.role === 'system'" class="absolute top-2 right-2 flex space-x-1">
                <button
                  type="button"
                  @click="replaceWithTemplate('systemPrompt')"
                  class="p-1 text-blue-600 hover:text-blue-800 bg-blue-100 hover:bg-blue-200 rounded"
                  title="Replace with templated data"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                  </svg>
                </button>
                <button
                  type="button"
                  @click="replaceWithActual('systemPrompt')"
                  class="p-1 text-green-600 hover:text-green-800 bg-green-100 hover:bg-green-200 rounded"
                  title="Replace with actual Sulus data"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <button
          @click="addMessage"
          type="button"
          class="w-full border-2 border-dashed border-gray-300 rounded-lg p-4 text-gray-600 hover:border-green-500 hover:text-green-600 transition-colors"
        >
          + Add Message
        </button>
      </div>
      
      <!-- System Prompt Preview -->
      <div v-if="formType === 'demo' && (actualSulusData?.company_name || templatedData?.company_name)" class="mt-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">System Prompt Preview</label>
        <div class="bg-gray-50 border border-gray-200 rounded-md p-4 text-sm text-gray-700 max-h-40 overflow-y-auto">
          <pre class="whitespace-pre-wrap">{{ processedSystemPrompt }}</pre>
        </div>
        <p class="text-xs text-gray-500 mt-1">This is how your system prompt will appear with the company information filled in.</p>
      </div>
    </div>

    <!-- Tools Configuration -->
    <div class="border-t border-gray-200 pt-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Tools</h3>
      
      <div class="space-y-4">
        <div v-for="(tool, index) in modelConfig.tools" :key="index" class="border border-gray-200 rounded-lg p-4">
          <div class="flex items-center justify-between mb-2">
            <label class="text-sm font-medium text-gray-700">Tool {{ index + 1 }}</label>
            <button
              @click="removeTool(index)"
              type="button"
              class="text-red-600 hover:text-red-800 text-sm"
            >
              Remove
            </button>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Tool Type</label>
              <select
                v-model="tool.type"
                class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500"
              >
                <option value="function">Function</option>
                <option value="web_search">Web Search</option>
                <option value="calculator">Calculator</option>
                <option value="voicemail">Voicemail Detection</option>
                <option value="transfer">Transfer</option>
                <option value="hang">Hang Up</option>
              </select>
            </div>
            
            <div v-if="tool.type === 'function'">
              <label class="block text-xs font-medium text-gray-600 mb-1">Function Name</label>
              <input
                v-model="tool.function.name"
                type="text"
                placeholder="function_name"
                class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500"
              />
            </div>
          </div>
          
          <div v-if="tool.type === 'function'" class="mt-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
            <textarea
              v-model="tool.function.description"
              rows="2"
              placeholder="Function description..."
              class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500"
            ></textarea>
          </div>
        </div>
        
        <button
          @click="addTool"
          type="button"
          class="w-full border-2 border-dashed border-gray-300 rounded-lg p-4 text-gray-600 hover:border-green-500 hover:text-green-600 transition-colors"
        >
          + Add Tool
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue'

export default {
  name: 'ModelConfiguration',
  props: {
    model: {
      type: Object,
      default: () => ({})
    },
    actualSulusData: {
      type: Object,
      default: () => ({})
    },
    templatedData: {
      type: Object,
      default: () => ({})
    },
    formType: {
      type: String,
      default: 'demo'
    },
    fieldErrors: {
      type: Object,
      default: () => ({})
    }
  },
  emits: ['update:model', 'replaceWithTemplate', 'replaceWithActual'],
  setup(props, { emit }) {
    const isUpdatingFromProps = ref(false)
    const modelConfig = ref({
      provider: 'openai',
      model: 'gpt-4o',
      apiVersion: '',
      messages: [
        {
          role: 'system',
          content: '',
          contentType: 'text'
        }
      ],
      tools: []
    })


    const processedSystemPrompt = computed(() => {
      const systemMessage = modelConfig.value.messages.find(msg => msg.role === 'system')
      if (!systemMessage) return ''
      
      let content = systemMessage.content
      if (props.actualSulusData?.company_name) {
        content = content.replace(/\{\{company_name\}\}/g, props.actualSulusData.company_name)
        content = content.replace(/\{\{company_industry\}\}/g, props.actualSulusData.company_industry || '')
        content = content.replace(/\{\{company_services\}\}/g, props.actualSulusData.company_services || '')
      } else if (props.templatedData?.company_name) {
        content = content.replace(/\{\{company_name\}\}/g, props.templatedData.company_name)
        content = content.replace(/\{\{company_industry\}\}/g, props.templatedData.company_industry || '')
        content = content.replace(/\{\{company_services\}\}/g, props.templatedData.company_services || '')
      }
      return content
    })

    const onProviderChange = () => {
      // Reset to default values when provider changes
      switch (modelConfig.value.provider) {
        case 'openai':
          modelConfig.value.model = 'gpt-4o'
          break
        case 'anthropic':
          modelConfig.value.model = 'claude-3-5-sonnet-20241022'
          break
        case 'google':
          modelConfig.value.model = 'gemini-1.5-pro'
          break
        case 'azure':
          modelConfig.value.model = 'gpt-4o'
          modelConfig.value.apiVersion = '2024-02-15-preview'
          break
        case 'cohere':
          modelConfig.value.model = 'command-r-plus'
          break
        case 'mistral':
          modelConfig.value.model = 'mistral-large-latest'
          break
        case 'groq':
          modelConfig.value.model = 'llama-3.1-70b-versatile'
          break
        case 'deepgram':
          modelConfig.value.model = 'nova-2'
          break
        case 'perplexity':
          modelConfig.value.model = 'llama-3.1-sonar-large-128k-online'
          break
        case 'custom':
          modelConfig.value.model = ''
          break
      }
    }

    const addMessage = () => {
      modelConfig.value.messages.push({
        role: 'user',
        content: '',
        contentType: 'text'
      })
    }

    const removeMessage = (index) => {
      modelConfig.value.messages.splice(index, 1)
    }

    const addTool = () => {
      modelConfig.value.tools.push({
        type: 'function',
        function: {
          name: '',
          description: ''
        }
      })
    }

    const removeTool = (index) => {
      modelConfig.value.tools.splice(index, 1)
    }

    const replaceWithTemplate = (field) => {
      emit('replaceWithTemplate', field)
    }

    const replaceWithActual = (field) => {
      emit('replaceWithActual', field)
    }

    // Initialize with props and watch for prop changes
    const initializeModelConfig = () => {
      if (props.model && Object.keys(props.model).length > 0) {
        modelConfig.value = { ...modelConfig.value, ...props.model }
      }
    }

    // Initialize immediately
    initializeModelConfig()

    // Watch for prop changes to update modelConfig
    watch(() => props.model, (newModel) => {
      console.log('ModelConfiguration: Received model props:', newModel)
      if (newModel && Object.keys(newModel).length > 0) {
        console.log('ModelConfiguration: Updating modelConfig with:', newModel)
        isUpdatingFromProps.value = true
        modelConfig.value = { ...modelConfig.value, ...newModel }
        console.log('ModelConfiguration: Updated modelConfig:', modelConfig.value)
        // Reset flag after a tick to allow the emit watcher to work again
        setTimeout(() => {
          isUpdatingFromProps.value = false
        }, 0)
      }
    }, { deep: true, immediate: true })

    // Watch for changes and emit updates (but not when updating from props)
    watch(modelConfig, (newConfig) => {
      // Only emit if this change didn't come from props
      if (!isUpdatingFromProps.value) {
        emit('update:model', newConfig)
      }
    }, { deep: true })

    return {
      modelConfig,
      processedSystemPrompt,
      onProviderChange,
      addMessage,
      removeMessage,
      addTool,
      removeTool,
      replaceWithTemplate,
      replaceWithActual
    }
  }
}
</script>