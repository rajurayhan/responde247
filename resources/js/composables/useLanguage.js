import { ref, computed } from 'vue'
import en from '../lang/en.js'
import es from '../lang/es.js'
import fr from '../lang/fr.js'

// Available languages
const languages = {
  en: { name: 'English', flag: '🇺🇸', translations: en },
  es: { name: 'Español', flag: '🇪🇸', translations: es },
  fr: { name: 'Français', flag: '🇫🇷', translations: fr }
}

// Current language state
const currentLanguage = ref('es')

// Initialize language from localStorage or browser preference
const initializeLanguage = () => {
  const savedLanguage = localStorage.getItem('preferred-language')
  if (savedLanguage && languages[savedLanguage]) {
    currentLanguage.value = savedLanguage
  } else {
    // Detect browser language
    const browserLang = navigator.language.split('-')[0]
    if (languages[browserLang]) {
      currentLanguage.value = browserLang
    }
  }
}

// Translation function
const t = (key, fallback = '') => {
  const keys = key.split('.')
  let translation = languages[currentLanguage.value].translations
  
  for (const k of keys) {
    if (translation && translation[k]) {
      translation = translation[k]
    } else {
      return fallback || key
    }
  }
  
  return translation || fallback || key
}

// Set language
const setLanguage = (langCode) => {
  if (languages[langCode]) {
    currentLanguage.value = langCode
    localStorage.setItem('preferred-language', langCode)
    
    // Update document language attribute
    document.documentElement.lang = langCode
    
    // Dispatch custom event for other components to listen
    window.dispatchEvent(new CustomEvent('language-changed', { 
      detail: { language: langCode } 
    }))
  }
}

// Get current language info
const getCurrentLanguage = computed(() => {
  return {
    code: currentLanguage.value,
    name: languages[currentLanguage.value].name,
    flag: languages[currentLanguage.value].flag
  }
})

// Get all available languages
const getAvailableLanguages = computed(() => {
  return Object.entries(languages).map(([code, lang]) => ({
    code,
    name: lang.name,
    flag: lang.flag
  }))
})

// Initialize on import
initializeLanguage()

export function useLanguage() {
  return {
    // State
    currentLanguage: computed(() => currentLanguage.value),
    currentLanguageInfo: getCurrentLanguage,
    availableLanguages: getAvailableLanguages,
    
    // Methods
    t,
    setLanguage,
    
    // Direct access to translations for complex cases
    translations: computed(() => languages[currentLanguage.value].translations)
  }
}
