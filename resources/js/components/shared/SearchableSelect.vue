<template>
  <div class="searchable-select relative">
    <!-- Search Input -->
    <div class="relative">
      <input
        ref="searchInput"
        v-model="searchQuery"
        type="text"
        :placeholder="placeholder"
        :disabled="disabled"
        :class="[
          'w-full px-3 py-2 pr-10 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
          disabled ? 'bg-gray-100 cursor-not-allowed' : '',
          error ? 'border-red-300' : 'border-gray-300'
        ]"
        @focus="showDropdown = true"
        @blur="handleBlur"
        @keydown="handleKeydown"
      />
      <div class="absolute inset-y-0 right-0 flex items-center pr-3">
        <svg 
          v-if="!showDropdown" 
          class="h-5 w-5 text-gray-400" 
          fill="none" 
          viewBox="0 0 24 24" 
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <svg 
          v-else 
          class="h-5 w-5 text-gray-400 cursor-pointer" 
          fill="none" 
          viewBox="0 0 24 24" 
          stroke="currentColor"
          @click="showDropdown = false"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </div>
    </div>

    <!-- Dropdown -->
    <div 
      v-if="showDropdown" 
      class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
    >
      <!-- Loading State -->
      <div v-if="loading" class="px-3 py-2 text-sm text-gray-500">
        Loading...
      </div>
      
      <!-- No Results -->
      <div v-else-if="filteredOptions.length === 0" class="px-3 py-2 text-sm text-gray-500">
        {{ searchQuery ? 'No results found' : 'No options available' }}
      </div>
      
      <!-- Options -->
      <div v-else>
        <div
          v-for="(option, index) in filteredOptions"
          :key="getOptionValue(option)"
          :class="[
            'px-3 py-2 cursor-pointer text-sm hover:bg-gray-100',
            selectedIndex === index ? 'bg-blue-50 text-blue-700' : 'text-gray-900'
          ]"
          @click="selectOption(option)"
          @mouseenter="selectedIndex = index"
        >
          <slot :option="option" :search-query="searchQuery">
            {{ getOptionLabel(option) }}
          </slot>
        </div>
      </div>
    </div>

    <!-- Selected Value Display -->
    <div 
      v-if="selectedOption && !showDropdown" 
      class="mt-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm"
    >
      <div class="font-medium text-gray-900">{{ getOptionLabel(selectedOption) }}</div>
      <div v-if="getOptionDescription" class="text-gray-500 text-xs mt-1">
        {{ getOptionDescription(selectedOption) }}
      </div>
    </div>

    <!-- Error Message -->
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
  </div>
</template>

<script>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'

export default {
  name: 'SearchableSelect',
  props: {
    modelValue: {
      type: [String, Number, Object],
      default: null
    },
    options: {
      type: Array,
      default: () => []
    },
    placeholder: {
      type: String,
      default: 'Search...'
    },
    disabled: {
      type: Boolean,
      default: false
    },
    loading: {
      type: Boolean,
      default: false
    },
    error: {
      type: String,
      default: null
    },
    valueKey: {
      type: String,
      default: 'value'
    },
    labelKey: {
      type: String,
      default: 'label'
    },
    descriptionKey: {
      type: String,
      default: null
    },
    searchKeys: {
      type: Array,
      default: () => ['label']
    }
  },
  emits: ['update:modelValue', 'change', 'search'],
  setup(props, { emit }) {
    const searchInput = ref(null)
    const showDropdown = ref(false)
    const searchQuery = ref('')
    const selectedIndex = ref(-1)

    // Find selected option
    const selectedOption = computed(() => {
      if (!props.modelValue) return null
      return props.options.find(option => getOptionValue(option) === props.modelValue)
    })

    // Filter options based on search query
    const filteredOptions = computed(() => {
      if (!searchQuery.value) {
        return props.options
      }

      const query = searchQuery.value.toLowerCase()
      return props.options.filter(option => {
        return props.searchKeys.some(key => {
          const value = getNestedValue(option, key)
          return value && value.toString().toLowerCase().includes(query)
        })
      })
    })

    // Helper functions
    const getOptionValue = (option) => {
      if (typeof option === 'object') {
        return getNestedValue(option, props.valueKey)
      }
      return option
    }

    const getOptionLabel = (option) => {
      if (typeof option === 'object') {
        return getNestedValue(option, props.labelKey)
      }
      return option
    }

    const getOptionDescription = (option) => {
      if (props.descriptionKey && typeof option === 'object') {
        return getNestedValue(option, props.descriptionKey)
      }
      return null
    }

    const getNestedValue = (obj, path) => {
      return path.split('.').reduce((current, key) => current?.[key], obj)
    }

    // Event handlers
    const selectOption = (option) => {
      const value = getOptionValue(option)
      emit('update:modelValue', value)
      emit('change', { option, value })
      showDropdown.value = false
      searchQuery.value = ''
      selectedIndex.value = -1
    }

    const handleBlur = () => {
      // Delay hiding dropdown to allow click events
      setTimeout(() => {
        showDropdown.value = false
        selectedIndex.value = -1
      }, 150)
    }

    const handleKeydown = (event) => {
      if (!showDropdown.value) return

      switch (event.key) {
        case 'ArrowDown':
          event.preventDefault()
          selectedIndex.value = Math.min(selectedIndex.value + 1, filteredOptions.value.length - 1)
          break
        case 'ArrowUp':
          event.preventDefault()
          selectedIndex.value = Math.max(selectedIndex.value - 1, -1)
          break
        case 'Enter':
          event.preventDefault()
          if (selectedIndex.value >= 0 && filteredOptions.value[selectedIndex.value]) {
            selectOption(filteredOptions.value[selectedIndex.value])
          }
          break
        case 'Escape':
          showDropdown.value = false
          selectedIndex.value = -1
          break
      }
    }

    // Watch for search query changes
    watch(searchQuery, (newQuery) => {
      emit('search', newQuery)
      selectedIndex.value = -1
    })

    // Watch for model value changes
    watch(() => props.modelValue, (newValue) => {
      if (newValue && selectedOption.value) {
        searchQuery.value = ''
      }
    })

    // Click outside handler
    const handleClickOutside = (event) => {
      if (!event.target.closest('.searchable-select')) {
        showDropdown.value = false
        selectedIndex.value = -1
      }
    }

    onMounted(() => {
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      searchInput,
      showDropdown,
      searchQuery,
      selectedIndex,
      selectedOption,
      filteredOptions,
      getOptionValue,
      getOptionLabel,
      getOptionDescription,
      selectOption,
      handleBlur,
      handleKeydown
    }
  }
}
</script>

<style scoped>
.searchable-select {
  position: relative;
}

/* Custom scrollbar for dropdown */
.max-h-60::-webkit-scrollbar {
  width: 6px;
}

.max-h-60::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.max-h-60::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.max-h-60::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
