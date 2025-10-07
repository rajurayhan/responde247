<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Navigation -->
    <Navigation />

    <div class="flex-1 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
              Reseller Settings
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Manage your reseller's branding and configuration settings
            </p>
          </div>
          <div class="mt-4 flex md:mt-0 md:ml-4">
            <button
              @click="initializeDefaults"
              :disabled="loading"
              class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md text-sm font-medium mr-3"
            >
              {{ loading ? 'Loading...' : 'Initialize Defaults' }}
            </button>
          </div>
        </div>

        <!-- Settings Form -->
        <div class="mt-8">
          <div class="bg-white shadow rounded-lg">
            <form @submit.prevent="saveSettings">
              <!-- General Settings -->
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">General Settings</h3>
              </div>
              <div class="px-6 py-4 space-y-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">App Name</label>
                  <input
                    v-model="settings.app_name"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Enter app name"
                  />
                  <p class="mt-1 text-sm text-gray-500">The name of your application/service (used throughout the interface)</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Site Title</label>
                  <input
                    v-model="settings.site_title"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Enter site title"
                  />
                  <p class="mt-1 text-sm text-gray-500">The main title of your website</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Site Tagline</label>
                  <input
                    v-model="settings.site_tagline"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Enter site tagline"
                  />
                  <p class="mt-1 text-sm text-gray-500">A short description or tagline for your site</p>
                </div>
              </div>

              <!-- Contact Settings -->
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Contact Information</h3>
              </div>
              <div class="px-6 py-4 space-y-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Company Phone</label>
                  <input
                    v-model="settings.company_phone"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Enter company phone number"
                  />
                  <p class="mt-1 text-sm text-gray-500">Primary contact phone number for your company</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Company Address</label>
                  <textarea
                    v-model="settings.company_address"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Enter company address"
                  ></textarea>
                  <p class="mt-1 text-sm text-gray-500">Physical address of your company</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Support Email</label>
                  <input
                    v-model="settings.support_email"
                    type="email"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Enter support email address"
                  />
                  <p class="mt-1 text-sm text-gray-500">Email address for customer support</p>
                </div>
              </div>

              <!-- SEO Settings -->
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">SEO Settings</h3>
              </div>
              <div class="px-6 py-4 space-y-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                  <textarea
                    v-model="settings.meta_description"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Enter meta description for SEO"
                  ></textarea>
                  <p class="mt-1 text-sm text-gray-500">Description for search engines (SEO)</p>
                </div>
              </div>

              <!-- Mail Configuration Settings -->
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Email Configuration</h3>
                <p class="text-sm text-gray-500">Configure SMTP settings for sending emails to your clients</p>
              </div>
              <div class="px-6 py-4 space-y-6">
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <input
                      id="enable_custom_mail"
                      v-model="settings.mail_enabled"
                      type="checkbox"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />
                    <label for="enable_custom_mail" class="ml-2 block text-sm font-medium text-gray-700">
                      Use custom mail configuration
                    </label>
                  </div>
                  <button
                    v-if="settings.mail_enabled"
                    @click="testMailConfig"
                    type="button"
                    :disabled="testingMail"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  >
                    {{ testingMail ? 'Testing...' : 'Test Configuration' }}
                  </button>
                </div>

                <div v-if="settings.mail_enabled" class="space-y-6">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Mail Driver</label>
                      <select
                        v-model="settings.mail_mailer"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      >
                        <option value="smtp">SMTP</option>
                        <option value="sendmail">Sendmail</option>
                        <option value="mailgun">Mailgun</option>
                        <option value="ses">Amazon SES</option>
                        <option value="postmark">Postmark</option>
                      </select>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Host</label>
                      <input
                        v-model="settings.mail_host"
                        type="text"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="smtp.example.com"
                      />
                    </div>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Port</label>
                      <input
                        v-model="settings.mail_port"
                        type="text"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="587"
                      />
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Encryption</label>
                      <select
                        v-model="settings.mail_encryption"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      >
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                        <option value="">None</option>
                      </select>
                    </div>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Username</label>
                      <input
                        v-model="settings.mail_username"
                        type="text"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="username@example.com"
                      />
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Password</label>
                      <input
                        v-model="settings.mail_password"
                        type="password"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="••••••••"
                      />
                    </div>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">From Address</label>
                      <input
                        v-model="settings.mail_from_address"
                        type="email"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="no-reply@example.com"
                      />
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">From Name</label>
                      <input
                        v-model="settings.mail_from_name"
                        type="text"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Company Name"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Branding Settings -->
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Branding</h3>
              </div>
              <div class="px-6 py-4 space-y-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Site Logo</label>
                  <div class="flex items-center space-x-4">
                    <div v-if="settings.logo_url" class="flex-shrink-0">
                      <img src="/api/saas-public/logo.png" alt="Logo" class="h-12 w-auto rounded border">
                    </div>
                    <div class="flex-1">
                      <input
                        ref="logoInput"
                        type="file"
                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                        @change="handleLogoUpload"
                        class="hidden"
                      />
                      <button
                        type="button"
                        @click="$refs.logoInput.click()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        {{ settings.logo_url ? 'Change Logo' : 'Upload Logo' }}
                      </button>
                      <p class="mt-1 text-sm text-gray-500">Recommended: 200x60px, max 800x400px, 2MB</p>
                      <p v-if="uploadErrors.logo" class="mt-1 text-sm text-red-600">{{ uploadErrors.logo }}</p>
                      <div v-if="selectedLogoFile" class="mt-2">
                        <p class="text-sm text-green-600">✓ {{ selectedLogoFile.name }} ready to upload</p>
                      </div>
                    </div>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Homepage Banner</label>
                  <div class="flex items-center space-x-4">
                    <div v-if="settings.homepage_banner" class="flex-shrink-0">
                      <img :src="settings.homepage_banner" alt="Banner" class="h-24 w-auto rounded border">
                    </div>
                    <div class="flex-1">
                      <input
                        ref="bannerInput"
                        type="file"
                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                        @change="handleBannerUpload"
                        class="hidden"
                      />
                      <button
                        type="button"
                        @click="$refs.bannerInput.click()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        {{ settings.homepage_banner ? 'Change Banner' : 'Upload Banner' }}
                      </button>
                      <p class="mt-1 text-sm text-gray-500">Recommended: 1200x400px, max 2000x800px, 5MB</p>
                      <p v-if="uploadErrors.banner" class="mt-1 text-sm text-red-600">{{ uploadErrors.banner }}</p>
                      <div v-if="selectedBannerFile" class="mt-2">
                        <p class="text-sm text-green-600">✓ {{ selectedBannerFile.name }} ready to upload</p>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Primary Color</label>
                    <div class="flex items-center space-x-3">
                      <input
                        v-model="settings.primary_color"
                        type="color"
                        class="h-10 w-16 border border-gray-300 rounded cursor-pointer"
                      />
                      <input
                        v-model="settings.primary_color"
                        type="text"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="#3B82F6"
                      />
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Primary brand color (hex code)</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Secondary Color</label>
                    <div class="flex items-center space-x-3">
                      <input
                        v-model="settings.secondary_color"
                        type="color"
                        class="h-10 w-16 border border-gray-300 rounded cursor-pointer"
                      />
                      <input
                        v-model="settings.secondary_color"
                        type="text"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="#1E40AF"
                      />
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Secondary brand color (hex code)</p>
                  </div>
                </div>
              </div>

              <!-- Save Button -->
              <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <div class="flex justify-end">
                  <button
                    type="submit"
                    :disabled="saving"
                    class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md text-sm font-medium"
                  >
                    {{ saving ? 'Saving...' : 'Save Settings' }}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Footer -->
    <SimpleFooter />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import Navigation from '../shared/Navigation.vue'
import SimpleFooter from '../shared/SimpleFooter.vue'
import { showSuccess, showError } from '../../utils/sweetalert.js'

export default {
  name: 'ResellerSettings',
  components: {
    Navigation,
    SimpleFooter
  },
  setup() {
    const saving = ref(false)
    const loading = ref(false)
    const settings = ref({
      app_name: '',
      site_title: '',
      site_tagline: '',
      meta_description: '',
      logo_url: '',
      homepage_banner: '',
      company_phone: '',
      company_address: '',
      support_email: '',
      primary_color: '#3B82F6',
      secondary_color: '#1E40AF',
      // Mail configuration fields
      mail_enabled: 'false',
      mail_mailer: 'smtp',
      mail_host: '',
      mail_port: '587',
      mail_username: '',
      mail_password: '',
      mail_encryption: 'tls',
      mail_from_address: '',
      mail_from_name: ''
    })
    const selectedLogoFile = ref(null)
    const selectedBannerFile = ref(null)
    const uploadProgress = ref({
      logo: 0,
      banner: 0
    })
    const uploadErrors = ref({
      logo: null,
      banner: null
    })
    const testingMail = ref(false)

    const loadSettings = async () => {
      try {
        loading.value = true
        const response = await axios.get('/api/reseller/settings')
        if (response.data.success) {
          // Flatten the grouped settings
          const allSettings = {}
          Object.values(response.data.data).forEach(group => {
            group.forEach(setting => {
              allSettings[setting.key] = setting.value
            })
          })
          settings.value = { ...settings.value, ...allSettings }
        }
      } catch (error) {
        console.error('Error loading settings:', error)
        showError('Failed to load settings')
      } finally {
        loading.value = false
      }
    }

    const initializeDefaults = async () => {
      try {
        loading.value = true
        const response = await axios.post('/api/reseller/settings/initialize')
        if (response.data.success) {
          showSuccess('Settings Initialized', 'Default settings have been set up successfully')
          await loadSettings()
        }
      } catch (error) {
        console.error('Error initializing settings:', error)
        showError('Failed to initialize default settings')
      } finally {
        loading.value = false
      }
    }

    const saveSettings = async () => {
      try {
        saving.value = true
        
        // Create FormData for file uploads
        const formData = new FormData()
        
        // Add all settings as JSON
        const settingsArray = Object.entries(settings.value).map(([key, value]) => ({
          key,
          value,
          type: getFieldType(key),
          group: getFieldGroup(key),
          label: getFieldLabel(key),
          description: getFieldDescription(key)
        }))
        formData.append('settings', JSON.stringify(settingsArray))
        
        // Add logo file if selected
        if (selectedLogoFile.value) {
          formData.append('logo_file', selectedLogoFile.value)
        }
        
        // Add banner file if selected
        if (selectedBannerFile.value) {
          formData.append('banner_file', selectedBannerFile.value)
        }

        const response = await axios.post('/api/reseller/settings', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })

        if (response.data.success) {
          showSuccess('Settings Saved', 'Reseller settings updated successfully')
          
          // Clear selected files
          selectedLogoFile.value = null
          selectedBannerFile.value = null
          
          // Reload settings to get updated URLs
          await loadSettings()
        }
      } catch (error) {
        console.error('Error saving settings:', error)
        if (error.response?.data?.message) {
          showError('Failed to save settings: ' + error.response.data.message)
        } else {
          showError('Failed to save settings')
        }
      } finally {
        saving.value = false
      }
    }

    const testMailConfig = async () => {
      try {
        testingMail.value = true
        const response = await axios.post('/api/reseller/settings/test-mail')
        
        if (response.data.success) {
          showSuccess('Mail Configuration Valid', 'Your mail server configuration is working correctly.')
        } else {
          showError('Mail Configuration Error', response.data.message)
        }
      } catch (error) {
        console.error('Error testing mail config:', error)
        if (error.response?.data?.message) {
          showError('Mail Configuration Error', error.response.data.message)
        } else {
          showError('Mail Configuration Error', 'Failed to test mail configuration')
        }
      } finally {
        testingMail.value = false
      }
    }

    const validateImageFile = (file, type) => {
      const maxSizes = {
        logo: 2 * 1024 * 1024, // 2MB
        banner: 5 * 1024 * 1024 // 5MB
      }
      
      const maxDimensions = {
        logo: { width: 800, height: 400 },
        banner: { width: 2000, height: 800 }
      }
      
      const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp']
      
      // Check file type
      if (!allowedTypes.includes(file.type)) {
        return `Please select a valid image file (JPEG, PNG, JPG, GIF, or WebP)`
      }
      
      // Check file size
      if (file.size > maxSizes[type]) {
        const maxSizeMB = maxSizes[type] / (1024 * 1024)
        return `File size must not exceed ${maxSizeMB}MB`
      }
      
      return null
    }

    const handleLogoUpload = (event) => {
      const file = event.target.files[0]
      if (file) {
        // Clear previous error
        uploadErrors.value.logo = null
        
        // Validate file
        const error = validateImageFile(file, 'logo')
        if (error) {
          uploadErrors.value.logo = error
          event.target.value = '' // Clear the input
          return
        }
        
        // Store the file for upload
        selectedLogoFile.value = file
        // Show preview
        settings.value.logo_url = URL.createObjectURL(file)
      }
    }

    const handleBannerUpload = (event) => {
      const file = event.target.files[0]
      if (file) {
        // Clear previous error
        uploadErrors.value.banner = null
        
        // Validate file
        const error = validateImageFile(file, 'banner')
        if (error) {
          uploadErrors.value.banner = error
          event.target.value = '' // Clear the input
          return
        }
        
        // Store the file for upload
        selectedBannerFile.value = file
        // Show preview
        settings.value.homepage_banner = URL.createObjectURL(file)
      }
    }

    // Helper functions for field metadata
    const getFieldType = (key) => {
      const typeMap = {
        'primary_color': 'color',
        'secondary_color': 'color',
        'support_email': 'email',
        'meta_description': 'textarea',
        'company_address': 'textarea'
      }
      return typeMap[key] || 'text'
    }

    const getFieldGroup = (key) => {
      const groupMap = {
        'app_name': 'general',
        'site_title': 'general',
        'site_tagline': 'general',
        'company_phone': 'contact',
        'company_address': 'contact',
        'support_email': 'contact',
        'meta_description': 'seo',
        'logo_url': 'branding',
        'homepage_banner': 'branding',
        'primary_color': 'branding',
        'secondary_color': 'branding',
        // Mail configuration fields
        'mail_enabled': 'mail',
        'mail_mailer': 'mail',
        'mail_host': 'mail',
        'mail_port': 'mail',
        'mail_username': 'mail',
        'mail_password': 'mail',
        'mail_encryption': 'mail',
        'mail_from_address': 'mail',
        'mail_from_name': 'mail'
      }
      return groupMap[key] || 'general'
    }

    const getFieldLabel = (key) => {
      const labelMap = {
        'app_name': 'App Name',
        'site_title': 'Site Title',
        'site_tagline': 'Site Tagline',
        'company_phone': 'Company Phone',
        'company_address': 'Company Address',
        'support_email': 'Support Email',
        'meta_description': 'Meta Description',
        'logo_url': 'Logo URL',
        'homepage_banner': 'Homepage Banner',
        'primary_color': 'Primary Color',
        'secondary_color': 'Secondary Color',
        // Mail configuration fields
        'mail_enabled': 'Use Custom Mail Configuration',
        'mail_mailer': 'Mail Driver',
        'mail_host': 'SMTP Host',
        'mail_port': 'SMTP Port',
        'mail_username': 'SMTP Username',
        'mail_password': 'SMTP Password',
        'mail_encryption': 'Encryption',
        'mail_from_address': 'From Address',
        'mail_from_name': 'From Name'
      }
      return labelMap[key] || key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
    }

    const getFieldDescription = (key) => {
      const descMap = {
        'app_name': 'The name of your application/service',
        'site_title': 'The main title of your website',
        'site_tagline': 'A short description or tagline for your site',
        'company_phone': 'Primary contact phone number for your company',
        'company_address': 'Physical address of your company',
        'support_email': 'Email address for customer support',
        'meta_description': 'Description for search engines (SEO)',
        'logo_url': 'Company logo URL',
        'homepage_banner': 'Homepage banner image URL',
        'primary_color': 'Primary brand color (hex code)',
        'secondary_color': 'Secondary brand color (hex code)',
        // Mail configuration fields
        'mail_enabled': 'Enable custom mail server configuration',
        'mail_mailer': 'Mail driver (smtp, sendmail, etc.)',
        'mail_host': 'SMTP server hostname',
        'mail_port': 'SMTP server port',
        'mail_username': 'SMTP authentication username',
        'mail_password': 'SMTP authentication password',
        'mail_encryption': 'Type of encryption (tls, ssl)',
        'mail_from_address': 'Default email address to send from',
        'mail_from_name': 'Default sender name'
      }
      return descMap[key] || ''
    }

    onMounted(() => {
      loadSettings()
    })

    return {
      settings,
      saving,
      loading,
      saveSettings,
      initializeDefaults,
      handleLogoUpload,
      handleBannerUpload,
      selectedLogoFile,
      selectedBannerFile,
      uploadProgress,
      uploadErrors,
      testingMail,
      testMailConfig
    }
  }
}
</script>
