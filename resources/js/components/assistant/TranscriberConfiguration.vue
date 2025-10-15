<template>
  <div class="space-y-6">
    <!-- Transcriber Provider Selection -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Transcriber Provider *</label>
      <select
        v-model="transcriberConfig.provider"
        @change="onProviderChange"
        data-field="transcriber_provider"
        :class="[
          'w-full px-3 py-2 border rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500',
          fieldErrors.provider 
            ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
            : 'border-gray-300 focus:border-green-500 bg-white'
        ]"
      >
        <option value="11labs">ElevenLabs</option>
        <option value="deepgram">Deepgram</option>
        <option value="openai" disabled>OpenAI (Upcoming)</option>
        <option value="google" disabled>Google (Upcoming)</option>
        <option value="azure" disabled>Azure (Upcoming)</option>
        <option value="custom" disabled>Custom (Upcoming)</option>
      </select>
      <p v-if="fieldErrors.provider" class="text-xs text-red-600 mt-1">{{ fieldErrors.provider }}</p>
      <p v-else class="text-xs text-gray-500 mt-1">Select the speech-to-text provider for your assistant</p>
    </div>

    <!-- Language Selection -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Language *</label>
      <div class="relative">
        <input
          v-model="languageSearchQuery"
          type="text"
          placeholder="Search languages..."
          class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 mb-2"
        />
        <div class="absolute right-2 top-2 text-gray-400">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </div>
      </div>
      <select
        v-model="transcriberConfig.language"
        :class="[
          'w-full px-3 py-2 border rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 max-h-60 overflow-y-auto',
          fieldErrors.language 
            ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
            : 'border-gray-300 focus:border-green-500 bg-white'
        ]"
      >
        <option v-for="lang in getFilteredLanguages()" :key="lang.code" :value="lang.code">
          {{ lang.name }}
        </option>
      </select>
      <p v-if="fieldErrors.language" class="text-xs text-red-600 mt-1">{{ fieldErrors.language }}</p>
      <p v-else class="text-xs text-gray-500 mt-1">Select the primary language for speech recognition. Choose "Multi (Auto-detect)" for automatic language detection across multiple languages.</p>
    </div>

    <div v-if="getAvailableModels().length > 0">
        <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
        <select
          v-model="transcriberConfig.model"
          data-field="transcriber_model"
          :class="[
            'w-full px-3 py-2 border rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500',
            fieldErrors.model 
              ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
              : 'border-gray-300 focus:border-green-500 bg-white'
          ]"
        >
          <option v-for="model in getAvailableModels()" :key="model.value" :value="model.value">
            {{ model.label }}
          </option>
        </select>
        <p v-if="fieldErrors.model" class="text-xs text-red-600 mt-1">{{ fieldErrors.model }}</p>
      </div>
    <!-- Basic Settings -->
    <div class="space-y-4">
      <h3 class="text-lg font-medium text-gray-900">Basic Settings</h3>
      
      <!-- Confidence Threshold -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Confidence Threshold</label>
        <input
          v-model.number="transcriberConfig.confidenceThreshold"
          type="range"
          min="0"
          max="1"
          step="0.1"
          class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
        />
        <div class="flex justify-between text-xs text-gray-500 mt-1">
          <span>0.0 (Low)</span>
          <span>{{ transcriberConfig.confidenceThreshold }}</span>
          <span>1.0 (High)</span>
        </div>
        <p class="text-xs text-gray-500 mt-1">Minimum confidence level for transcript acceptance</p>
      </div>

      <!-- Format Turns -->
      <div>
        <label class="flex items-center">
          <input
            v-model="transcriberConfig.formatTurns"
            type="checkbox"
            class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
          />
          <span class="ml-2 text-sm text-gray-700">Format Turns</span>
        </label>
        <p class="text-xs text-gray-500 mt-1">Format transcript turns with speaker labels</p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch } from 'vue'

export default {
  name: 'TranscriberConfiguration',
  props: {
    transcriber: {
      type: Object,
      default: () => ({})
    },
    fieldErrors: {
      type: Object,
      default: () => ({})
    }
  },
  emits: ['update:transcriber'],
  setup(props, { emit }) {
    const isUpdatingFromProps = ref(false)
    const languageSearchQuery = ref('')
    const transcriberConfig = ref({
      provider: 'deepgram',
      language: 'en',
      model: 'nova-2',
      confidenceThreshold: 0.4,
      formatTurns: true
    })

    // Language options - comprehensive list for multi-language support
    const languages = [
      { code: 'multi', name: 'Multi (Auto-detect)' },
      { code: 'en', name: 'English' },
      { code: 'en-US', name: 'English (US)' },
      { code: 'en-GB', name: 'English (UK)' },
      { code: 'en-AU', name: 'English (Australia)' },
      { code: 'en-CA', name: 'English (Canada)' },
      { code: 'en-IN', name: 'English (India)' },
      { code: 'es', name: 'Spanish' },
      { code: 'es-ES', name: 'Spanish (Spain)' },
      { code: 'es-MX', name: 'Spanish (Mexico)' },
      { code: 'es-AR', name: 'Spanish (Argentina)' },
      { code: 'es-CO', name: 'Spanish (Colombia)' },
      { code: 'es-PE', name: 'Spanish (Peru)' },
      { code: 'fr', name: 'French' },
      { code: 'fr-FR', name: 'French (France)' },
      { code: 'fr-CA', name: 'French (Canada)' },
      { code: 'de', name: 'German' },
      { code: 'de-DE', name: 'German (Germany)' },
      { code: 'de-AT', name: 'German (Austria)' },
      { code: 'de-CH', name: 'German (Switzerland)' },
      { code: 'it', name: 'Italian' },
      { code: 'it-IT', name: 'Italian (Italy)' },
      { code: 'pt', name: 'Portuguese' },
      { code: 'pt-BR', name: 'Portuguese (Brazil)' },
      { code: 'pt-PT', name: 'Portuguese (Portugal)' },
      { code: 'ru', name: 'Russian' },
      { code: 'ru-RU', name: 'Russian (Russia)' },
      { code: 'ja', name: 'Japanese' },
      { code: 'ja-JP', name: 'Japanese (Japan)' },
      { code: 'ko', name: 'Korean' },
      { code: 'ko-KR', name: 'Korean (South Korea)' },
      { code: 'zh', name: 'Chinese' },
      { code: 'zh-CN', name: 'Chinese (Simplified)' },
      { code: 'zh-TW', name: 'Chinese (Traditional)' },
      { code: 'ar', name: 'Arabic' },
      { code: 'ar-SA', name: 'Arabic (Saudi Arabia)' },
      { code: 'ar-EG', name: 'Arabic (Egypt)' },
      { code: 'ar-AE', name: 'Arabic (UAE)' },
      { code: 'hi', name: 'Hindi' },
      { code: 'hi-IN', name: 'Hindi (India)' },
      { code: 'nl', name: 'Dutch' },
      { code: 'nl-NL', name: 'Dutch (Netherlands)' },
      { code: 'nl-BE', name: 'Dutch (Belgium)' },
      { code: 'sv', name: 'Swedish' },
      { code: 'sv-SE', name: 'Swedish (Sweden)' },
      { code: 'no', name: 'Norwegian' },
      { code: 'no-NO', name: 'Norwegian (Norway)' },
      { code: 'da', name: 'Danish' },
      { code: 'da-DK', name: 'Danish (Denmark)' },
      { code: 'fi', name: 'Finnish' },
      { code: 'fi-FI', name: 'Finnish (Finland)' },
      { code: 'pl', name: 'Polish' },
      { code: 'pl-PL', name: 'Polish (Poland)' },
      { code: 'tr', name: 'Turkish' },
      { code: 'tr-TR', name: 'Turkish (Turkey)' },
      { code: 'he', name: 'Hebrew' },
      { code: 'he-IL', name: 'Hebrew (Israel)' },
      { code: 'th', name: 'Thai' },
      { code: 'th-TH', name: 'Thai (Thailand)' },
      { code: 'vi', name: 'Vietnamese' },
      { code: 'vi-VN', name: 'Vietnamese (Vietnam)' },
      { code: 'id', name: 'Indonesian' },
      { code: 'id-ID', name: 'Indonesian (Indonesia)' },
      { code: 'ms', name: 'Malay' },
      { code: 'ms-MY', name: 'Malay (Malaysia)' },
      { code: 'tl', name: 'Filipino' },
      { code: 'tl-PH', name: 'Filipino (Philippines)' }
    ]

      // Model options for each provider
      const modelOptions = {
      '11labs': [
        { value: 'scribe_v1', label: 'Scribe' },
      ],
      deepgram: [
        { value: 'nova-2', label: 'Nova 2' },
        { value: 'nova-3', label: 'Nova 3' },
        { value: 'nova-3-general', label: 'Nova 3 General' },
        { value: 'nova-3-medical', label: 'Nova 3 Medical' },
        { value: 'nova-2-general', label: 'Nova 2 General' },
        { value: 'nova-2-meeting', label: 'Nova 2 Meeting' },
        { value: 'nova-2-phonecall', label: 'Nova 2 Phonecall' },
        { value: 'nova-2-finance', label: 'Nova 2 Finance' },
        { value: 'nova-2-conversationalai', label: 'Nova 2 Conversationalai' },
        { value: 'nova-2-voicemail', label: 'Nova 2 Voicemail' },
        { value: 'nova-2-video', label: 'Nova 2 Video' },
      ],
    }

    // Get available models for current provider
    const getAvailableModels = () => {
      const models = modelOptions[transcriberConfig.value.provider] || [];
      return models;
    }

    // Get filtered languages based on search query
    const getFilteredLanguages = () => {
      const sortedLanguages = languages.sort((a, b) => a.name.localeCompare(b.name))
      if (!languageSearchQuery.value.trim()) {
        return sortedLanguages
      }
      return sortedLanguages.filter(lang => 
        lang.name.toLowerCase().includes(languageSearchQuery.value.toLowerCase())
      )
    }

    // Provider change handler
    const onProviderChange = () => {
      // Reset to default values when provider changes
      transcriberConfig.value.language = 'en'
      transcriberConfig.value.confidenceThreshold = 0.4
      transcriberConfig.value.formatTurns = true
      
      // Set default model based on provider
      if (transcriberConfig.value.provider === 'deepgram') {
        transcriberConfig.value.model = 'nova-2'
      } else if (transcriberConfig.value.provider === '11labs') {
        transcriberConfig.value.model = 'scribe_v1'
      } else {
        transcriberConfig.value.model = ''
      }
    }

    // Watch for changes and emit updates (but not when updating from props)
    watch(transcriberConfig, (newConfig) => {
      // Only emit if this change didn't come from props
      if (!isUpdatingFromProps.value) {
        emit('update:transcriber', newConfig)
      }
    }, { deep: true })

    // Initialize with props and watch for prop changes
    const initializeTranscriberConfig = () => {
      if (props.transcriber && Object.keys(props.transcriber).length > 0) {
        transcriberConfig.value = { ...transcriberConfig.value, ...props.transcriber }
      } else {
        // Use default Deepgram configuration when no transcriber value exists
        transcriberConfig.value = {
          provider: 'deepgram',
          language: 'en',
          model: 'nova-2',
          confidenceThreshold: 0.4,
          formatTurns: true,
          endOfTurnConfidenceThreshold: 0.7,
          minEndOfTurnSilenceWhenConfident: 160,
          wordFinalizationMaxWaitTime: 160,
          maxTurnSilence: 400,
          realtimeUrl: '',
          wordBoost: [],
          endUtteranceSilenceThreshold: 1.1,
          disablePartialTranscripts: false,
          fallbackPlan: null
        }
      }
    }

    // Initialize immediately
    initializeTranscriberConfig()

    // Watch for prop changes to update transcriberConfig
    watch(() => props.transcriber, (newTranscriber) => {
      console.log('TranscriberConfiguration: Received transcriber props:', newTranscriber)
      if (newTranscriber && Object.keys(newTranscriber).length > 0) {
        console.log('TranscriberConfiguration: Updating transcriberConfig with:', newTranscriber)
        isUpdatingFromProps.value = true
        transcriberConfig.value = { ...transcriberConfig.value, ...newTranscriber }
        console.log('TranscriberConfiguration: Updated transcriberConfig:', transcriberConfig.value)
        // Reset flag after a tick to allow the emit watcher to work again
        setTimeout(() => {
          isUpdatingFromProps.value = false
        }, 0)
      }
    }, { deep: true, immediate: true })

    return {
      transcriberConfig,
      languageSearchQuery,
      languages,
      getAvailableModels,
      getFilteredLanguages,
      onProviderChange
    }
  }
}
</script>

<style scoped>
.slider::-webkit-slider-thumb {
  appearance: none;
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #10b981;
  cursor: pointer;
}

.slider::-moz-range-thumb {
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #10b981;
  cursor: pointer;
  border: none;
}
</style>