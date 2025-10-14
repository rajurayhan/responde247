<template>
  <div class="relative language-switcher">
    <!-- Language Switcher Button -->
    <button
      @click="toggleDropdown"
      class="flex items-center space-x-2 px-3 py-2 rounded-lg bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/20 transition-all duration-200 text-white"
    >
      <span class="text-lg">{{ currentLanguageInfo.flag }}</span>
      <span class="text-sm font-medium hidden sm:block">{{ currentLanguageInfo.name }}</span>
      <svg 
        class="h-4 w-4 transition-transform duration-200"
        :class="{ 'rotate-180': isOpen }"
        fill="none" 
        viewBox="0 0 24 24" 
        stroke="currentColor"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Language Dropdown -->
    <div
      v-if="isOpen"
      class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-200 py-2 z-50"
    >
      <div class="px-3 py-2 border-b border-gray-100">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Select Language</p>
      </div>
      
      <div class="py-1">
        <button
          v-for="lang in availableLanguages"
          :key="lang.code"
          @click="selectLanguage(lang.code)"
          :class="[
            'w-full flex items-center space-x-3 px-3 py-2 text-left hover:bg-gray-50 transition-colors duration-150',
            currentLanguage === lang.code ? 'bg-primary-50 text-primary-700' : 'text-gray-700'
          ]"
        >
          <span class="text-lg">{{ lang.flag }}</span>
          <span class="text-sm font-medium">{{ lang.name }}</span>
          <svg 
            v-if="currentLanguage === lang.code"
            class="h-4 w-4 text-primary-600 ml-auto"
            fill="currentColor" 
            viewBox="0 0 20 20"
          >
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Backdrop to close dropdown -->
    <div
      v-if="isOpen"
      @click="closeDropdown"
      class="fixed inset-0 z-40"
    ></div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'
import { useLanguage } from '../../composables/useLanguage.js'

export default {
  name: 'LanguageSwitcher',
  setup() {
    const isOpen = ref(false)
    const { currentLanguage, currentLanguageInfo, availableLanguages, setLanguage } = useLanguage()

    const selectLanguage = (langCode) => {
      console.log('Selecting language:', langCode)
      setLanguage(langCode)
      isOpen.value = false
    }

    const toggleDropdown = () => {
      console.log('Toggling dropdown, current state:', isOpen.value)
      isOpen.value = !isOpen.value
    }

    const closeDropdown = () => {
      console.log('Closing dropdown')
      isOpen.value = false
    }

    // Close dropdown when clicking outside
    const handleClickOutside = (event) => {
      if (!event.target.closest('.language-switcher')) {
        isOpen.value = false
      }
    }

    onMounted(() => {
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      isOpen,
      currentLanguage,
      currentLanguageInfo,
      availableLanguages,
      selectLanguage,
      toggleDropdown,
      closeDropdown
    }
  }
}
</script>

<style scoped>
.language-switcher {
  position: relative;
}
</style>
