<template>
  <div class="flex items-center">
    <div class="flex-shrink-0">
      <div v-if="branding.logoUrl" class="h-8 w-auto">
        <img :src="branding.logoUrl" :alt="branding.appName" class="h-full w-auto">
      </div>
      <div v-else class="h-8 w-8 bg-gradient-to-r from-primary-600 to-blue-600 rounded-lg flex items-center justify-center">
        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
        </svg>
      </div>
    </div>
    <div class="ml-2">
      <h1 class="text-xl font-bold text-gray-900">{{ branding.appName }}</h1>
      <p v-if="showSlogan" class="text-sm text-gray-600">{{ branding.slogan }}</p>
    </div>
  </div>
</template>

<script>
import { useResellerData } from '../../composables/useResellerData.js'

export default {
  name: 'Logo',
  props: {
    showSlogan: {
      type: Boolean,
      default: false
    },
    size: {
      type: String,
      default: 'normal', // normal, small, large
      validator: value => ['small', 'normal', 'large'].includes(value)
    }
  },
  setup(props) {
    const { branding, isLoaded } = useResellerData()
    
    return {
      branding,
      isLoaded
    }
  }
}
</script>

<style scoped>
/* Dynamic sizing based on props */
.h-8 {
  height: v-bind('size === "small" ? "1.5rem" : size === "large" ? "3rem" : "2rem"');
}

.text-xl {
  font-size: v-bind('size === "small" ? "1rem" : size === "large" ? "1.5rem" : "1.25rem"');
}

.text-sm {
  font-size: v-bind('size === "small" ? "0.75rem" : size === "large" ? "1rem" : "0.875rem"');
}
</style> 