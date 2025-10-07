<template>
  <footer class="bg-gray-800 mt-auto">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <div class="xl:grid xl:grid-cols-3 xl:gap-8">
        <div class="space-y-6 xl:col-span-1">
          <div class="flex items-center">
            <div v-if="branding.logoUrl" class="h-8 w-auto">
              <img :src="branding.logoUrl" :alt="branding.appName" class="h-full w-auto">
            </div>
            <div v-else class="h-8 w-8 bg-gradient-to-r from-primary-600 to-blue-600 rounded-lg flex items-center justify-center">
              <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
              </svg>
            </div>
            <div class="ml-2">
              <h3 class="text-xl font-bold text-white">{{ branding.appName }}</h3>
            </div>
          </div>
          <p class="text-gray-300 text-sm">
            {{ branding.description }}
          </p>
        </div>
        <div class="mt-8 grid grid-cols-2 gap-8 xl:mt-0 xl:col-span-2">
          <div class="md:grid md:grid-cols-2 md:gap-8">
            <div>
              <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Solutions</h3>
              <ul class="mt-4 space-y-3">
                <li><a href="#" class="text-sm text-gray-300 hover:text-white">Customer Service</a></li>
                <li><a href="#" class="text-sm text-gray-300 hover:text-white">Sales Automation</a></li>
                <li><a href="#" class="text-sm text-gray-300 hover:text-white">Voice Analytics</a></li>
              </ul>
            </div>
            <div class="mt-8 md:mt-0">
              <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Support</h3>
              <ul class="mt-4 space-y-3">
                <li><a href="#" class="text-sm text-gray-300 hover:text-white">Documentation</a></li>
                <li><a href="#" class="text-sm text-gray-300 hover:text-white">API Reference</a></li>
                <li><a href="#" class="text-sm text-gray-300 hover:text-white">Contact Us</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-8 border-t border-gray-700 pt-6">
        <div class="flex flex-col md:flex-row justify-between items-center">
          <p class="text-sm text-gray-400">
            {{ branding.footerText }}
          </p>
          <div class="mt-4 md:mt-0 flex space-x-6">
            <a href="/terms" class="text-sm text-gray-400 hover:text-white">Terms of Service</a>
            <a href="/privacy" class="text-sm text-gray-400 hover:text-white">Privacy Policy</a>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script>
import { useResellerData } from '../../composables/useResellerData.js'

export default {
  name: 'Footer',
  setup() {
    // Get reseller data - available immediately
    const { branding, isLoaded } = useResellerData()
    
    return {
      branding,
      isLoaded
    }
  }
}
</script> 