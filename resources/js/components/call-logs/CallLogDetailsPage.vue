<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Navigation -->
    <Navigation />

    <div class="flex-1 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-bold text-gray-900">Call Details</h1>
              <p class="mt-2 text-sm text-gray-600">
                Detailed information about call {{ callLog?.call_id }}
              </p>
            </div>
            <div class="flex space-x-3">
              <button
                @click="$router.push('/call-logs')"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md"
              >
                Back to Call Logs
              </button>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-500"></div>
        </div>

        <!-- Call Details -->
        <div v-else-if="callLog" class="bg-white shadow rounded-lg">
          <!-- Call Information -->
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Call Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div>
                <dt class="text-sm font-medium text-gray-500">Call ID</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ callLog.call_id }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Status</dt>
                <dd class="mt-1">
                  <span :class="getStatusBadgeClass(callLog.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                    {{ callLog.status }}
                  </span>
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Direction</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ callLog.direction }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Duration</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ formatDuration(callLog.duration) }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Start Time</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(callLog.start_time) }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">End Time</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(callLog.end_time) }}</dd>
              </div>
              <!-- Cost - Admin Only -->
              <div v-if="isAdmin">
                <dt class="text-sm font-medium text-gray-500">Cost</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ formatCost(callLog.cost, callLog.currency) }}</dd>
              </div>
            </div>
          </div>

          <!-- Phone Numbers -->
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Phone Numbers</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <dt class="text-sm font-medium text-gray-500">Assistant Number</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ callLog.phone_number || 'N/A' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Caller Number</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ callLog.caller_number || 'N/A' }}</dd>
              </div>
            </div>
          </div>

          <!-- Call Recording -->
          <div v-if="callLog.has_recording" class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Call Recording</h3>
            <div class="bg-gray-50 p-4 rounded-md">
              <AudioPlayer 
                :audio-url="callLog.public_audio_url"
                title="Call Recording"
              />
            </div>
          </div>

          <!-- Summary -->
          <div v-if="callLog.summary" class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Call Summary</h3>
            <div class="bg-gray-50 p-4 rounded-md">
              <p class="text-sm text-gray-900">{{ callLog.summary }}</p>
            </div>
          </div>

          <!-- Conversation -->
          <div v-if="conversationData && conversationData.conversation.length > 0" class="p-6 border-b border-gray-200">
            <ConversationDisplay 
              :conversation-data="conversationData"
              title="Call Conversation"
              :show-header="true"
              max-height="max-h-96"
              :show-message-numbers="true"
              :show-timestamps="false"
            />
          </div>

          <!-- Fallback: Raw Transcript -->
          <div v-else-if="callLog.transcript" class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Call Transcript (Raw)</h3>
            <div class="bg-gray-50 p-4 rounded-md max-h-96 overflow-y-auto">
              <div class="space-y-4">
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Call Transcript</span>
                    <span class="text-xs text-gray-500">Raw Format</span>
                  </div>
                  <div class="text-sm text-gray-900 leading-relaxed whitespace-pre-wrap bg-gray-50 p-3 rounded">
                    {{ callLog.transcript }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Metadata - Admin Only -->
          <div v-if="callLog.metadata && isAdmin" class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between cursor-pointer" @click="toggleMetadata">
              <h3 class="text-lg font-medium text-gray-900">Metadata</h3>
              <svg 
                :class="['w-5 h-5 text-gray-500 transition-transform', { 'rotate-180': !metadataCollapsed }]"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </div>
            <div v-show="!metadataCollapsed" class="mt-4 bg-gray-50 p-4 rounded-md">
              <pre class="text-xs text-gray-700 overflow-x-auto">{{ JSON.stringify(callLog.metadata, null, 2) }}</pre>
            </div>
          </div>

          <!-- Webhook Data - Admin Only -->
          <div v-if="callLog.webhook_data && isAdmin" class="p-6">
            <div class="flex items-center justify-between cursor-pointer" @click="toggleWebhookData">
              <h3 class="text-lg font-medium text-gray-900">Webhook Data</h3>
              <svg 
                :class="['w-5 h-5 text-gray-500 transition-transform', { 'rotate-180': !webhookDataCollapsed }]"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </div>
            <div v-show="!webhookDataCollapsed" class="mt-4 bg-gray-50 p-4 rounded-md">
              <pre class="text-xs text-gray-700 overflow-x-auto">{{ JSON.stringify(callLog.webhook_data, null, 2) }}</pre>
            </div>
          </div>
        </div>

        <!-- Error State -->
        <div v-else class="text-center py-12">
          <div class="text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Call not found</h3>
            <p class="mt-1 text-sm text-gray-500">The call you're looking for doesn't exist or you don't have permission to view it.</p>
            <div class="mt-6">
              <button
                @click="$router.push('/call-logs')"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
              >
                Back to Call Logs
              </button>
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
import Navigation from '../shared/Navigation.vue'
import AudioPlayer from '../shared/AudioPlayer.vue'
import SimpleFooter from '../shared/SimpleFooter.vue'
import ConversationDisplay from '../shared/ConversationDisplay.vue'
import axios from 'axios'

export default {
  name: 'CallLogDetailsPage',
  components: {
    Navigation,
    AudioPlayer,
    SimpleFooter,
    ConversationDisplay
  },
  data() {
    return {
      loading: true,
      callLog: null,
      conversationData: null,
      isAdmin: false,
      metadataCollapsed: true,
      webhookDataCollapsed: true
    }
  },
  computed: {
    isJsonTranscript() {
      if (!this.callLog?.transcript) return false
      try {
        JSON.parse(this.callLog.transcript)
        return true
      } catch {
        return false
      }
    },
    parsedTranscript() {
      if (!this.isJsonTranscript) return []
      try {
        return JSON.parse(this.callLog.transcript)
      } catch {
        return []
      }
    }
  },
  async mounted() {
    console.log('CallLogDetailsPage mounted')
    this.checkAdminStatus()
    await this.loadCallLog()
  },
  methods: {
    checkAdminStatus() {
      const user = JSON.parse(localStorage.getItem('user') || '{}')
      this.isAdmin = user.role === 'admin' || user.role === 'reseller_admin'
    },

    async loadCallLog() {
      try {
        this.loading = true
        const callId = this.$route.params.call_id
        console.log('Loading call log for ID:', callId)
        const response = await axios.get(`/api/call-logs/${callId}`)
        console.log('Call log response:', response.data)
        this.callLog = response.data.data
        this.conversationData = response.data.conversation
        console.log('Conversation data:', this.conversationData)
      } catch (error) {
        console.error('Error loading call log:', error)
        this.callLog = null
        this.conversationData = null
      } finally {
        this.loading = false
      }
    },

    getStatusBadgeClass(status) {
      const classes = {
        'initiated': 'bg-blue-100 text-blue-800',
        'ringing': 'bg-yellow-100 text-yellow-800',
        'in-progress': 'bg-green-100 text-green-800',
        'completed': 'bg-green-100 text-green-800',
        'failed': 'bg-red-100 text-red-800',
        'busy': 'bg-gray-100 text-gray-800',
        'no-answer': 'bg-gray-100 text-gray-800',
        'cancelled': 'bg-gray-100 text-gray-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    },

    formatDuration(seconds) {
      if (!seconds) return 'N/A'
      const minutes = Math.floor(seconds / 60)
      const remainingSeconds = seconds % 60
      return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
    },

    formatDateTime(dateString) {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleString()
    },

    formatCost(cost, currency = 'USD') {
      if (!cost) return 'N/A'
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
      }).format(cost)
    },

    formatMessageTime(timestamp) {
      if (!timestamp) return ''
      const date = new Date(timestamp)
      return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      })
    },

    toggleMetadata() {
      console.log('toggleMetadata clicked, current state:', this.metadataCollapsed)
      this.metadataCollapsed = !this.metadataCollapsed
      console.log('toggleMetadata new state:', this.metadataCollapsed)
    },

    toggleWebhookData() {
      console.log('toggleWebhookData clicked, current state:', this.webhookDataCollapsed)
      this.webhookDataCollapsed = !this.webhookDataCollapsed
      console.log('toggleWebhookData new state:', this.webhookDataCollapsed)
    }
  }
}
</script> 