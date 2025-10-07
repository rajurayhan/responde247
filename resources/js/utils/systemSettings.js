import axios from 'axios'

let cachedSettings = null
let settingsPromise = null

/**
 * Get system settings with caching
 */
export const getSystemSettings = async () => {
  if (cachedSettings) {
    return cachedSettings
  }

  if (settingsPromise) {
    return settingsPromise
  }

  settingsPromise = axios.get('/api/public-settings')
    .then(response => {
      if (response.data.success) {
        cachedSettings = response.data.data
        return cachedSettings
      }
      return getDefaultSettings()
    })
    .catch(error => {
      console.error('Error loading system settings:', error)
      return getDefaultSettings()
    })

  return settingsPromise
}

/**
 * Get default settings if API fails
 */
const getDefaultSettings = () => {
  return {
    site_title: 'sulus.ai',
    site_tagline: 'Never Miss a call Again sulus.ai answers 24x7!',
    meta_description: 'Transform your business with cutting-edge voice AI technology',
    logo_url: '/logo.png',
    homepage_banner: null,
    company_phone: '(682) 582 8396',
    company_email: 'support@sulus.ai',
    company_name: 'sulus.ai'
  }
}

/**
 * Clear cached settings (useful after updates)
 */
export const clearSettingsCache = () => {
  cachedSettings = null
  settingsPromise = null
}

/**
 * Get a specific setting value
 */
export const getSetting = async (key, defaultValue = '') => {
  const settings = await getSystemSettings()
  return settings[key] || defaultValue
}

/**
 * Update document title with reseller branding data
 * Uses the global RESELLER_DATA injected by the server for instant access
 */
export const updateDocumentTitle = (pageTitle = '') => {
  try {
    // Get reseller data from global window object (injected by server)
    const resellerData = window.RESELLER_DATA || {}
    const appName = resellerData.app_name || 'AI Phone System'
    
    if (pageTitle) {
      // If pageTitle already contains the app name, use it as is
      if (pageTitle.includes(appName)) {
        document.title = pageTitle
      } else {
        // Otherwise, append app name to page title
        document.title = `${pageTitle} - ${appName}`
      }
    } else {
      document.title = appName
    }
  } catch (error) {
    console.error('Error updating document title:', error)
    // Fallback to default title
    if (pageTitle) {
      document.title = pageTitle
    } else {
      document.title = 'AI Phone System'
    }
  }
}

/**
 * Legacy async version for backward compatibility
 * @deprecated Use the synchronous version above for better performance
 */
export const updateDocumentTitleLegacy = async (pageTitle = '') => {
  try {
    const settings = await getSystemSettings()
    const siteTitle = settings.site_title || 'sulus.ai'
    
    if (pageTitle) {
      // If pageTitle already contains the site title (like "sulus.ai - Tagline"), use it as is
      if (pageTitle.includes(siteTitle)) {
        document.title = pageTitle
      } else {
        // Otherwise, append site title to page title
        document.title = `${pageTitle} - ${siteTitle}`
      }
    } else {
      document.title = siteTitle
    }
  } catch (error) {
    console.error('Error updating document title:', error)
    // Fallback to default title
    if (pageTitle) {
      document.title = pageTitle
    } else {
      document.title = 'sulus.ai'
    }
  }
} 