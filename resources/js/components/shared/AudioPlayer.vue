<template>
  <div class="audio-player bg-white rounded-lg shadow-sm border border-gray-200 p-4">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <div>
          <h4 class="text-sm font-medium text-gray-900">Call Recording</h4>
          <p class="text-xs text-gray-500">{{ formatDuration(currentTime) }} / {{ formatDuration(duration) }}</p>
        </div>
      </div>
      
      <!-- Download Button -->
      <a 
        :href="audioUrl" 
        download 
        class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
      >
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Download
      </a>
    </div>

    <!-- Audio Controls -->
    <div class="space-y-3">
      <!-- Progress Bar -->
      <div class="relative">
        <div class="bg-gray-200 rounded-full h-2">
          <div 
            class="bg-green-500 h-2 rounded-full transition-all duration-100"
            :style="{ width: progressPercentage + '%' }"
          ></div>
        </div>
        <input
          ref="progressSlider"
          type="range"
          min="0"
          :max="duration"
          :value="currentTime"
          @input="seekTo"
          class="absolute inset-0 w-full h-2 opacity-0 cursor-pointer"
        />
      </div>

      <!-- Control Buttons -->
      <div class="flex items-center justify-center space-x-4">
        <!-- Rewind 10s -->
        <button
          @click="rewind"
          class="p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 rounded-full"
          :disabled="!isLoaded"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/>
          </svg>
        </button>

        <!-- Play/Pause -->
        <button
          @click="togglePlay"
          class="p-3 bg-green-500 text-white rounded-full hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!isLoaded"
        >
          <svg v-if="!isPlaying" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
            <path d="M8 5v14l11-7z"/>
          </svg>
          <svg v-else class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
            <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
          </svg>
        </button>

        <!-- Forward 10s -->
        <button
          @click="forward"
          class="p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 rounded-full"
          :disabled="!isLoaded"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.934 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.334-4z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.934 12.8a1 1 0 000-1.6L14.6 7.2A1 1 0 0013 8v8a1 1 0 001.6.8l5.334-4z"/>
          </svg>
        </button>
      </div>

      <!-- Volume Control -->
      <div class="flex items-center space-x-3">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
        </svg>
        <input
          type="range"
          min="0"
          max="1"
          step="0.1"
          :value="volume"
          @input="setVolume"
          class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
        />
        <span class="text-xs text-gray-500 w-8 text-right">{{ Math.round(volume * 100) }}%</span>
      </div>
    </div>

    <!-- Hidden Audio Element -->
    <audio
      ref="audioElement"
      :src="audioUrl"
      @loadedmetadata="onLoadedMetadata"
      @timeupdate="onTimeUpdate"
      @play="onPlay"
      @pause="onPause"
      @ended="onEnded"
      @error="onError"
      @loadstart="onLoadStart"
      @canplay="onCanPlay"
      preload="metadata"
      crossorigin="anonymous"
    ></audio>
  </div>
</template>

<script>
export default {
  name: 'AudioPlayer',
  props: {
    audioUrl: {
      type: String,
      required: true
    },
    title: {
      type: String,
      default: 'Audio Player'
    }
  },
  data() {
    return {
      isPlaying: false,
      isLoaded: false,
      currentTime: 0,
      duration: 0,
      volume: 0.7,
      error: null
    }
  },
  computed: {
    progressPercentage() {
      if (this.duration === 0) return 0
      return (this.currentTime / this.duration) * 100
    }
  },
  mounted() {
    console.log('AudioPlayer mounted with URL:', this.audioUrl)
    this.$nextTick(() => {
      this.setVolume()
    })
  },
  methods: {
    togglePlay() {
      console.log('Toggle play clicked, isLoaded:', this.isLoaded, 'isPlaying:', this.isPlaying)
      if (!this.isLoaded) {
        console.log('Audio not loaded yet')
        return
      }
      
      if (this.isPlaying) {
        console.log('Pausing audio')
        this.$refs.audioElement.pause()
      } else {
        console.log('Playing audio')
        this.$refs.audioElement.play().catch(error => {
          console.error('Error playing audio:', error)
        })
      }
    },

    seekTo(event) {
      if (!this.isLoaded) return
      const newTime = parseFloat(event.target.value)
      this.$refs.audioElement.currentTime = newTime
    },

    rewind() {
      if (!this.isLoaded) return
      const newTime = Math.max(0, this.currentTime - 10)
      this.$refs.audioElement.currentTime = newTime
    },

    forward() {
      if (!this.isLoaded) return
      const newTime = Math.min(this.duration, this.currentTime + 10)
      this.$refs.audioElement.currentTime = newTime
    },

    setVolume(event) {
      if (event && event.target) {
        const newVolume = parseFloat(event.target.value)
        this.volume = newVolume
        if (this.$refs.audioElement) {
          this.$refs.audioElement.volume = newVolume
        }
      } else {
        // Direct volume setting (for mounted hook)
        if (this.$refs.audioElement) {
          this.$refs.audioElement.volume = this.volume
        }
      }
    },

    formatDuration(seconds) {
      if (!seconds || isNaN(seconds)) return '0:00'
      const minutes = Math.floor(seconds / 60)
      const remainingSeconds = Math.floor(seconds % 60)
      return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
    },

    onLoadedMetadata() {
      console.log('Audio metadata loaded, duration:', this.$refs.audioElement.duration)
      this.isLoaded = true
      this.duration = this.$refs.audioElement.duration
    },

    onTimeUpdate() {
      this.currentTime = this.$refs.audioElement.currentTime
    },

    onPlay() {
      console.log('Audio started playing')
      this.isPlaying = true
    },

    onPause() {
      console.log('Audio paused')
      this.isPlaying = false
    },

    onEnded() {
      console.log('Audio ended')
      this.isPlaying = false
    },

    onLoadStart() {
      console.log('Audio load started')
    },

    onCanPlay() {
      console.log('Audio can play')
    },

    onError(event) {
      this.error = 'Error loading audio file'
      console.error('Audio error:', event)
    }
  }
}
</script>

<style scoped>
.slider {
  -webkit-appearance: none;
  appearance: none;
}

.slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #10b981;
  cursor: pointer;
}

.slider::-moz-range-thumb {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #10b981;
  cursor: pointer;
  border: none;
}

.slider::-webkit-slider-track {
  background: #e5e7eb;
  border-radius: 8px;
  height: 8px;
}

.slider::-moz-range-track {
  background: #e5e7eb;
  border-radius: 8px;
  height: 8px;
}
</style> 