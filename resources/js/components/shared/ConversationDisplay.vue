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
        <div v-for="message in normalizedConversation" :key="message.id" 
             :class="['flex', message.isCaller ? 'justify-end' : 'justify-start']">
          <div class="max-w-xs lg:max-w-md">
            <!-- Speaker Label -->
            <div :class="['flex items-center mb-1', message.isCaller ? 'justify-end' : 'justify-start']">
              <span :class="['text-xs font-medium', message.isCaller ? 'text-green-600' : 'text-blue-600']">
                {{ message.speaker }}
              </span>
              <span v-if="formatMessageInfo(message)" class="text-xs text-gray-500 ml-2">
                {{ formatMessageInfo(message) }}
              </span>
            </div>
            
            <!-- Message Bubble -->
            <div class="relative">
              <div :class="['px-4 py-2 rounded-lg', message.isCaller ? 'bg-green-100 text-green-900' : 'bg-blue-100 text-blue-900']">
                <p class="text-sm whitespace-pre-wrap">{{ message.message }}</p>
              </div>
              
              <!-- Tail -->
              <div :class="['absolute top-2', message.isCaller ? 'right-0 -mr-1' : 'left-0 -ml-1']">
                <div :class="['w-2 h-2 transform rotate-45', message.isCaller ? 'bg-green-100' : 'bg-blue-100']"></div>
              </div>
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
    },
    assistantName: {
      type: String,
      default: 'Assistant'
    }
  },
  computed: {
    maxHeightClass() {
      return this.maxHeight === 'none' ? '' : `overflow-y-auto ${this.maxHeight}`
    },
    normalizedConversation() {
      if (!this.conversationData?.conversation) return []
      
      return this.conversationData.conversation.map(message => {
        let normalizedSpeaker = message.speaker
        
        // Normalize speaker names
        if (message.speaker === 'Customer') {
          normalizedSpeaker = 'Caller'
        } else if (message.speaker === 'Assistant') {
          normalizedSpeaker = this.assistantName
        }
        
        return {
          ...message,
          speaker: normalizedSpeaker,
          isCaller: normalizedSpeaker === 'Caller'
        }
      })
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
