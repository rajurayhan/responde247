<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="$emit('close')">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white" @click.stop>
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900">Call Details</h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600"
        >
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Call Information -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
          <h4 class="text-sm font-medium text-gray-500 mb-2">Call Information</h4>
          <dl class="space-y-2">
            <div>
              <dt class="text-xs text-gray-500">Call ID</dt>
              <dd class="text-sm font-medium text-gray-900">{{ callLog.call_id }}</dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500">Status</dt>
              <dd>
                <span :class="getStatusBadgeClass(callLog.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                  {{ callLog.status }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500">Direction</dt>
              <dd class="text-sm text-gray-900">{{ callLog.direction }}</dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500">Duration</dt>
              <dd class="text-sm text-gray-900">{{ formatDuration(callLog.duration) }}</dd>
            </div>
          </dl>
        </div>

        <div>
          <h4 class="text-sm font-medium text-gray-500 mb-2">Timing</h4>
          <dl class="space-y-2">
            <div>
              <dt class="text-xs text-gray-500">Start Time</dt>
              <dd class="text-sm text-gray-900">{{ formatDateTime(callLog.start_time) }}</dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500">End Time</dt>
              <dd class="text-sm text-gray-900">{{ formatDateTime(callLog.end_time) }}</dd>
            </div>
            <div>
              <dt class="text-xs text-gray-500">Cost</dt>
              <dd class="text-sm text-gray-900">{{ formatCost(callLog.cost, callLog.currency) }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Phone Numbers -->
      <div class="mb-6">
        <h4 class="text-sm font-medium text-gray-500 mb-2">Phone Numbers</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <dt class="text-xs text-gray-500">Assistant Number</dt>
            <dd class="text-sm text-gray-900">{{ callLog.phone_number || 'N/A' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500">Caller Number</dt>
            <dd class="text-sm text-gray-900">{{ callLog.caller_number || 'N/A' }}</dd>
          </div>
        </div>
      </div>

      <!-- Summary -->
      <div v-if="callLog.summary" class="mb-6">
        <h4 class="text-sm font-medium text-gray-500 mb-2">Call Summary</h4>
        <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">{{ callLog.summary }}</p>
      </div>

      <!-- Transcript -->
      <div v-if="callLog.transcript" class="mb-6">
        <h4 class="text-sm font-medium text-gray-500 mb-2">Transcript</h4>
        <div class="bg-gray-50 p-4 rounded-md max-h-64 overflow-y-auto">
          <div v-if="isJsonTranscript" class="space-y-3">
            <div
              v-for="(message, index) in parsedTranscript"
              :key="index"
              :class="[
                'p-2 rounded-md',
                message.role === 'user' ? 'bg-blue-100 ml-4' : 'bg-green-100 mr-4'
              ]"
            >
              <div class="text-xs font-medium text-gray-600 mb-1">
                {{ message.role === 'user' ? 'User' : 'Assistant' }}
              </div>
              <div class="text-sm text-gray-900">{{ message.content }}</div>
            </div>
          </div>
          <div v-else class="text-sm text-gray-900 whitespace-pre-wrap">
            {{ callLog.transcript }}
          </div>
        </div>
      </div>

      <!-- Metadata -->
      <div v-if="callLog.metadata" class="mb-6">
        <h4 class="text-sm font-medium text-gray-500 mb-2">Metadata</h4>
        <div class="bg-gray-50 p-4 rounded-md">
          <pre class="text-xs text-gray-700 overflow-x-auto">{{ JSON.stringify(callLog.metadata, null, 2) }}</pre>
        </div>
      </div>

      <!-- Webhook Data -->
      <div v-if="callLog.webhook_data" class="mb-6">
        <h4 class="text-sm font-medium text-gray-500 mb-2">Webhook Data</h4>
        <div class="bg-gray-50 p-4 rounded-md">
          <pre class="text-xs text-gray-700 overflow-x-auto">{{ JSON.stringify(callLog.webhook_data, null, 2) }}</pre>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex justify-end mt-6">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CallLogDetails',
  props: {
    callLog: {
      type: Object,
      required: true
    }
  },
  computed: {
    isJsonTranscript() {
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
  methods: {
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
    }
  }
}
</script> 