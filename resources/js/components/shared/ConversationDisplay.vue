<template>
  <div class="conversation-display">
    <!-- Conversation Header -->
    <div v-if="showHeader" class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium text-gray-900">{{ title || 'Call Conversation' }}</h3>
      <div v-if="conversationData" class="flex items-center space-x-4 text-sm text-gray-500">
        <span>{{ conversationData.total_messages }} messages</span>
        <span>{{ conversationData.summary.assistant_messages }} from Assistant</span>
        <span>{{ conversationData.summary.customer_messages }} from Customer</span>
      </div>
    </div>
    
    <!-- Conversation Messages -->
    <div v-if="conversationData && conversationData.conversation.length > 0" 
         :class="['bg-gray-50 p-4 rounded-md', maxHeightClass]">
      <div class="space-y-4">
        <div
          v-for="message in conversationData.conversation"
          :key="message.id"
          :class="[
            'flex items-start space-x-3 p-4 rounded-lg shadow-sm transition-colors duration-150',
            message.speaker === 'Customer' ? 'bg-blue-50 border-l-4 border-blue-400 hover:bg-blue-100' : 'bg-green-50 border-l-4 border-green-400 hover:bg-green-100'
          ]"
        >
          <!-- Avatar -->
          <div :class="[
            'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-medium',
            message.speaker === 'Customer' ? 'bg-blue-500' : 'bg-green-500'
          ]">
            {{ message.speaker === 'Customer' ? 'C' : 'A' }}
          </div>
          
          <!-- Message Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between mb-1">
              <span :class="[
                'text-sm font-medium',
                message.speaker === 'Customer' ? 'text-blue-700' : 'text-green-700'
              ]">
                {{ message.speaker }}
              </span>
              <span class="text-xs text-gray-500">
                {{ formatMessageInfo(message) }}
              </span>
            </div>
            <div class="text-sm text-gray-900 leading-relaxed">
              {{ message.message }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-gray-50 p-8 rounded-md text-center">
      <div class="text-gray-500">
        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <p class="text-sm">No conversation data available</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ConversationDisplay',
  props: {
    conversationData: {
      type: Object,
      default: null
    },
    title: {
      type: String,
      default: 'Call Conversation'
    },
    showHeader: {
      type: Boolean,
      default: true
    },
    maxHeight: {
      type: String,
      default: 'max-h-96'
    },
    showMessageNumbers: {
      type: Boolean,
      default: true
    },
    showTimestamps: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    maxHeightClass() {
      return this.maxHeight === 'none' ? '' : `overflow-y-auto ${this.maxHeight}`
    }
  },
  methods: {
    formatMessageInfo(message) {
      if (this.showTimestamps && message.timestamp) {
        return this.formatTimestamp(message.timestamp)
      }
      if (this.showMessageNumbers) {
        return `Message #${message.id}`
      }
      return ''
    },
    
    formatTimestamp(timestamp) {
      if (!timestamp) return ''
      const date = new Date(timestamp)
      return date.toLocaleTimeString()
    }
  }
}
</script>

<style scoped>
.conversation-display {
  @apply w-full;
}

/* Custom scrollbar for conversation */
.conversation-display .overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.conversation-display .overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.conversation-display .overflow-y-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.conversation-display .overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
