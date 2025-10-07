<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl w-full space-y-8">
      <div>
        <div class="flex justify-center mb-6">
          <router-link to="/" class="flex items-center hover:opacity-80 transition-opacity duration-200">
            <div class="flex-shrink-0 flex items-center justify-center">
              <div class="flex items-center">
                <div v-if="branding.logoUrl" class="h-8 w-auto">
                  <img :src="branding.logoUrl" :alt="branding.appName" class="h-full w-auto">
                </div>
                <div v-else class="h-8 w-8 bg-green-600 rounded-lg flex items-center justify-center">
                  <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                  </svg>
                </div>
                <div class="ml-2">
                  <h1 class="text-xl font-bold text-gray-900">{{ branding.appName }}</h1>
                </div>
              </div>
            </div>
          </router-link>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Become a Reseller Partner
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Join our reseller program and offer voice AI solutions to your clients
        </p>
        <p class="mt-1 text-center text-sm text-gray-500">
          Already have an account?
          <router-link to="/login" class="font-medium text-primary-600 hover:text-primary-500">
            Sign in here
          </router-link>
        </p>
      </div>

      <!-- Progress Steps -->
      <div class="flex items-center justify-center mb-8">
        <div class="flex items-center space-x-4">
          <div class="flex items-center">
            <div :class="currentStep >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600'" 
                 class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium">
              1
            </div>
            <span class="ml-2 text-sm font-medium text-gray-700">Company Info</span>
          </div>
          <div class="w-8 h-0.5 bg-gray-300"></div>
          <div class="flex items-center">
            <div :class="currentStep >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600'" 
                 class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium">
              2
            </div>
            <span class="ml-2 text-sm font-medium text-gray-700">Hosting</span>
          </div>
          <div class="w-8 h-0.5 bg-gray-300"></div>
          <div class="flex items-center">
            <div :class="currentStep >= 3 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600'" 
                 class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium">
              3
            </div>
            <span class="ml-2 text-sm font-medium text-gray-700">Admin Details</span>
          </div>
          <div class="w-8 h-0.5 bg-gray-300"></div>
          <div class="flex items-center">
            <div :class="currentStep >= 4 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600'" 
                 class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium">
              4
            </div>
            <span class="ml-2 text-sm font-medium text-gray-700">Choose Package</span>
          </div>
        </div>
      </div>

      <form class="mt-8 space-y-6" @submit.prevent="handleSubmit">
        <!-- Step 1: Company Information -->
        <div v-if="currentStep === 1" class="space-y-6">
          <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Company Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="org_name" class="block text-sm font-medium text-gray-700 mb-2">
                  Organization Name <span class="text-red-500">*</span>
                </label>
                <input
                  id="org_name"
                  v-model="form.org_name"
                  name="org_name"
                  type="text"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200"
                  placeholder="Enter your organization name"
                  :class="{ 'border-red-500 focus:ring-red-500': errors.org_name }"
                />
                <p v-if="errors.org_name" class="mt-2 text-sm text-red-600">{{ errors.org_name }}</p>
              </div>


              <div>
                <label for="company_email" class="block text-sm font-medium text-gray-700 mb-2">
                  Company Email
                </label>
                <input
                  id="company_email"
                  v-model="form.company_email"
                  name="company_email"
                  type="email"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200"
                  placeholder="contact@yourcompany.com"
                  :class="{ 'border-red-500 focus:ring-red-500': errors.company_email }"
                />
                <p v-if="errors.company_email" class="mt-2 text-sm text-red-600">{{ errors.company_email }}</p>
              </div>

              <div>
                <label for="logo_file" class="block text-sm font-medium text-gray-700 mb-2">
                  Company Logo
                </label>
                
                <!-- Logo Preview -->
                <div v-if="logoPreview" class="mb-4 p-4 border border-gray-200 rounded-lg bg-gray-50">
                  <p class="text-sm text-gray-600 mb-2">Preview:</p>
                  <img :src="logoPreview" alt="Logo preview" class="max-h-20 max-w-32 object-contain">
                </div>
                
                <input
                  id="logo_file"
                  ref="logoFile"
                  name="logo_file"
                  type="file"
                  accept="image/*"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200"
                  @change="handleLogoChange"
                />
                <p class="mt-1 text-xs text-gray-500">PNG, JPG, GIF up to 2MB (max 800x400px)</p>
                <p v-if="errors.logo_file" class="mt-2 text-sm text-red-600">{{ errors.logo_file }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 2: Hosting Preference -->
        <div v-if="currentStep === 2" class="space-y-6">
          <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Choose Your Subdomain</h3>
            <p class="text-sm text-gray-600 mb-6">Your reseller platform will be hosted on a subdomain of responde247.com</p>
            
            <!-- Subdomain Display Card -->
            <div class="p-6 border-2 border-blue-500 bg-blue-50 rounded-lg mb-6">
              <div class="text-center">
                <div class="flex items-center justify-center mb-4">
                  <svg class="h-12 w-12 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 mb-2">Subdomain Hosting</h4>
                <p class="text-sm text-gray-600 mb-4">
                  Use a subdomain on responde247.com (e.g., yourcompany.responde247.com)
                </p>
                <div class="space-y-2 text-sm text-gray-700">
                  <div class="flex items-center justify-center">
                    <svg class="h-4 w-4 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Quick setup
                  </div>
                  <div class="flex items-center justify-center">
                    <svg class="h-4 w-4 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    No domain configuration needed
                  </div>
                  <div class="flex items-center justify-center">
                    <svg class="h-4 w-4 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Ready to use immediately
                  </div>
                </div>
              </div>
            </div>

            <!-- Subdomain Input -->
            <div>
              <label for="subdomain_name" class="block text-sm font-medium text-gray-700 mb-2">
                Subdomain Name <span class="text-red-500">*</span>
              </label>
              <div class="flex">
                <input
                  id="subdomain_name"
                  v-model="form.subdomain_name"
                  name="subdomain_name"
                  type="text"
                  required
                  class="flex-1 px-4 py-3 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200"
                  placeholder="yourcompany"
                  :class="{ 'border-red-500 focus:ring-red-500': errors.subdomain_name }"
                />
                <span class="px-4 py-3 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg text-gray-700">
                  .responde247.com
                </span>
              </div>
              <p class="mt-1 text-xs text-gray-500">Only letters, numbers, and hyphens allowed</p>
              <p v-if="errors.subdomain_name" class="mt-2 text-sm text-red-600">{{ errors.subdomain_name }}</p>
            </div>
          </div>
        </div>

        <!-- Step 3: Admin User Details -->
        <div v-if="currentStep === 3" class="space-y-6">
          <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Admin User Details</h3>
            <p class="text-sm text-gray-600 mb-6">This will be the primary administrator account for your reseller organization.</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="admin_name" class="block text-sm font-medium text-gray-700 mb-2">
                  Full Name <span class="text-red-500">*</span>
                </label>
                <input
                  id="admin_name"
                  v-model="form.admin_name"
                  name="admin_name"
                  type="text"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200"
                  placeholder="Enter your full name"
                  :class="{ 'border-red-500 focus:ring-red-500': errors.admin_name }"
                />
                <p v-if="errors.admin_name" class="mt-2 text-sm text-red-600">{{ errors.admin_name }}</p>
              </div>

              <div>
                <label for="admin_email" class="block text-sm font-medium text-gray-700 mb-2">
                  Email Address <span class="text-red-500">*</span>
                </label>
                <input
                  id="admin_email"
                  v-model="form.admin_email"
                  name="admin_email"
                  type="email"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200"
                  placeholder="Enter your email"
                  :class="{ 'border-red-500 focus:ring-red-500': errors.admin_email }"
                />
                <p v-if="errors.admin_email" class="mt-2 text-sm text-red-600">{{ errors.admin_email }}</p>
              </div>

              <div>
                <label for="admin_phone" class="block text-sm font-medium text-gray-700 mb-2">
                  Phone Number <span class="text-red-500">*</span>
                </label>
                <input
                  id="admin_phone"
                  v-model="form.admin_phone"
                  name="admin_phone"
                  type="tel"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200"
                  placeholder="Enter your phone number"
                  :class="{ 'border-red-500 focus:ring-red-500': errors.admin_phone }"
                />
                <p v-if="errors.admin_phone" class="mt-2 text-sm text-red-600">{{ errors.admin_phone }}</p>
              </div>

              <!-- Password fields removed - temporary password will be sent via email -->
            </div>
            
            <!-- Information about credentials -->
            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <div class="flex items-start">
                <svg class="h-5 w-5 text-blue-600 mt-0.5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                  <h4 class="text-sm font-medium text-blue-900 mb-1">Account Credentials</h4>
                  <p class="text-sm text-blue-700">
                    Your admin account credentials will be automatically generated and sent to your email address after successful registration and payment. You'll receive a temporary password that you can change on first login.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 4: Package Selection -->
        <div v-if="currentStep === 4" class="space-y-6">
          <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Choose Your Reseller Package</h3>
            <p class="text-sm text-gray-600 mb-6">Select the package that best fits your business needs. You can upgrade or downgrade later.</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <div 
                v-for="pkg in packages" 
                :key="pkg.id"
                :class="[
                  'relative p-6 border-2 rounded-lg cursor-pointer transition-all duration-200',
                  selectedPackage === pkg.id 
                    ? 'border-blue-500 bg-blue-50' 
                    : 'border-gray-200 hover:border-gray-300'
                ]"
                @click="selectPackage(pkg.id)"
              >
                <div v-if="pkg.is_popular" class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                  <span class="bg-blue-600 text-white px-3 py-1 text-xs font-medium rounded-full">
                    Most Popular
                  </span>
                </div>
                
                <div class="text-center">
                  <h4 class="text-lg font-semibold text-gray-900 mb-2">{{ pkg.name }}</h4>
                  <div class="mb-4">
                    <span class="text-3xl font-bold text-gray-900">${{ pkg.price }}</span>
                    <span class="text-gray-600">/month</span>
                  </div>
                  <p class="text-sm text-gray-600 mb-4">{{ pkg.description }}</p>
                  
                  <div class="space-y-2 mb-6">
                    <div class="flex items-center text-sm text-gray-700">
                      <svg class="h-4 w-4 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                      {{ pkg.formatted_voice_agents_limit }} Voice Agents
                    </div>
                    <div class="flex items-center text-sm text-gray-700">
                      <svg class="h-4 w-4 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                      {{ pkg.formatted_minutes_limit }} Minutes/Month
                    </div>
                    <div class="flex items-center text-sm text-gray-700">
                      <svg class="h-4 w-4 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                      {{ pkg.support_level }} Support
                    </div>
                  </div>
                  
                  <div v-if="pkg.yearly_price" class="text-xs text-gray-500 mb-2">
                    Yearly: ${{ pkg.yearly_price }} (Save {{ getYearlySavingsPercentage(pkg) }}%)
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Package Selection Error -->
            <p v-if="errors.selectedPackage" class="mt-4 text-sm text-red-600 text-center">{{ errors.selectedPackage }}</p>
            
            <div class="mt-6">
              <label class="flex items-center">
                <input
                  v-model="form.billing_interval"
                  type="radio"
                  value="monthly"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                />
                <span class="ml-2 text-sm text-gray-700">Monthly Billing</span>
              </label>
              <label class="flex items-center mt-2">
                <input
                  v-model="form.billing_interval"
                  type="radio"
                  value="yearly"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                />
                <span class="ml-2 text-sm text-gray-700">Yearly Billing (Save up to 17%)</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Terms and Conditions -->
        <div class="flex items-center">
          <input
            id="terms"
            v-model="form.terms"
            name="terms"
            type="checkbox"
            required
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
          />
          <label for="terms" class="ml-2 block text-sm text-gray-700">
            I agree to the
            <a href="#" class="text-blue-600 hover:text-blue-500">Terms of Service</a>
            and
            <a href="#" class="text-blue-600 hover:text-blue-500">Privacy Policy</a>
          </label>
        </div>
        <p v-if="errors.terms" class="text-sm text-red-600">{{ errors.terms }}</p>

        <!-- Navigation Buttons -->
        <div class="flex justify-between">
          <button
            v-if="currentStep > 1"
            type="button"
            @click="previousStep"
            class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200"
          >
            Previous
          </button>
          <div v-else></div>
          
          <button
            v-if="currentStep < 4"
            type="button"
            @click="nextStep"
            :disabled="loading"
            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
          >
            Next
          </button>
          <button
            v-else
            type="submit"
            :disabled="loading || !selectedPackage"
            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
          >
            <span v-if="loading">Creating Account...</span>
            <span v-else>Create Reseller Account</span>
          </button>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
          <div class="flex">
            <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">Error</h3>
              <p class="mt-1 text-sm text-red-700">{{ error }}</p>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { showSuccess, showError } from '../../utils/sweetalert'
import { updateDocumentTitle } from '../../utils/systemSettings'
import { useResellerData } from '../../composables/useResellerData'

export default {
  name: 'RegisterReseller',
  data() {
    return {
      currentStep: 1,
      loading: false,
      error: '',
      packages: [],
      selectedPackage: null,
      branding: {},
      isLoaded: false,
      form: {
        // Company Information
        org_name: '',
        company_email: '',
        logo_file: null,
        
        // Subdomain
        subdomain_name: '',
        
        // Admin User Details
        admin_name: '',
        admin_email: '',
        admin_phone: '',
        
        // Package Selection
        billing_interval: 'monthly',
        
        // Terms
        terms: false
      },
      errors: {},
      logoPreview: null
    }
  },
  created() {
    // Initialize reseller data
    const { branding, isLoaded } = useResellerData()
    this.branding = branding
    this.isLoaded = isLoaded
    
    // Set document title using branding data
    updateDocumentTitle('Become a Reseller')
    this.loadPackages()
  },
  methods: {
    async loadPackages() {
      try {
        const response = await axios.get('/api/reseller-packages')
        this.packages = response.data.data || []
      } catch (error) {
        console.error('Error loading packages:', error)
        showError('Error', 'Failed to load reseller packages')
      }
    },
    
    async checkDomainAvailability() {
      try {
        this.loading = true
        
        // Only check subdomain - send just the subdomain name
        const response = await axios.post('/api/check-domain-availability', {
          subdomain: this.form.subdomain_name
        })
        
        if (response.data.available) {
          this.currentStep++
        } else {
          this.errors.subdomain_name = 'This subdomain is already taken by another reseller'
        }
      } catch (error) {
        console.error('Error checking domain availability:', error)
        const errorMessage = error.response?.data?.message || 'Failed to check domain availability. Please try again.'
        this.errors.subdomain_name = errorMessage
      } finally {
        this.loading = false
      }
    },
    
    async checkEmailAvailability() {
      try {
        this.loading = true
        const response = await axios.post('/api/check-email-availability', {
          email: this.form.admin_email
        })
        
        if (response.data.available) {
          this.currentStep++
        } else {
          this.errors.admin_email = 'This email is already registered by another user'
        }
      } catch (error) {
        console.error('Error checking email availability:', error)
        if (error.response && error.response.data.message) {
          this.errors.admin_email = error.response.data.message
        } else {
          this.errors.admin_email = 'Failed to check email availability. Please try again.'
        }
      } finally {
        this.loading = false
      }
    },
    
    async nextStep() {
      if (this.validateCurrentStep()) {
        // If moving from step 2, check domain availability
        if (this.currentStep === 2) {
          await this.checkDomainAvailability()
        } else if (this.currentStep === 3) {
          // If moving from step 3, check email availability
          await this.checkEmailAvailability()
        } else {
          this.currentStep++
        }
      }
    },
    
    previousStep() {
      this.currentStep--
    },
    
    selectPackage(packageId) {
      this.selectedPackage = packageId
    },
    
    validateCurrentStep() {
      this.errors = {}
      let isValid = true
      
      if (this.currentStep === 1) {
        if (!this.form.org_name.trim()) {
          this.errors.org_name = 'Organization name is required'
          isValid = false
        }
      } else if (this.currentStep === 2) {
        // Subdomain validation
        if (!this.form.subdomain_name.trim()) {
          this.errors.subdomain_name = 'Subdomain name is required'
          isValid = false
        } else if (!this.isValidSubdomain(this.form.subdomain_name)) {
          this.errors.subdomain_name = 'Subdomain name can only contain letters, numbers, and hyphens'
          isValid = false
        }
      } else if (this.currentStep === 3) {
        if (!this.form.admin_name.trim()) {
          this.errors.admin_name = 'Admin name is required'
          isValid = false
        }
        if (!this.form.admin_email.trim()) {
          this.errors.admin_email = 'Admin email is required'
          isValid = false
        } else if (!this.isValidEmail(this.form.admin_email)) {
          this.errors.admin_email = 'Please enter a valid email address'
          isValid = false
        }
        if (!this.form.admin_phone.trim()) {
          this.errors.admin_phone = 'Admin phone is required'
          isValid = false
        }
        // Password validation removed - temporary password will be sent via email
      } else if (this.currentStep === 4) {
        // Package selection validation
        if (!this.selectedPackage) {
          this.errors.selectedPackage = 'Please select a package'
          isValid = false
        }
      }
      
      return isValid
    },
    
    isValidEmail(email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      return emailRegex.test(email)
    },
    
    isValidDomain(domain) {
      const domainRegex = /^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/
      return domainRegex.test(domain)
    },
    
    isValidSubdomain(subdomain) {
      const subdomainRegex = /^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/
      return subdomainRegex.test(subdomain)
    },
    
    getYearlySavingsPercentage(pkg) {
      if (!pkg.yearly_price || !pkg.price) {
        return 0
      }
      
      const monthlyYearlyCost = pkg.price * 12
      const yearlyCost = pkg.yearly_price
      
      if (monthlyYearlyCost <= 0) {
        return 0
      }
      
      return Math.round(((monthlyYearlyCost - yearlyCost) / monthlyYearlyCost) * 100)
    },
    
    handleLogoChange(event) {
      console.log('Logo file change event:', event)
      const file = event.target.files[0]
      console.log('Selected file:', file)
      
      if (file) {
        console.log('File details:', {
          name: file.name,
          size: file.size,
          type: file.type,
          lastModified: file.lastModified
        })
        
        this.form.logo_file = file
        
        // Create preview
        const reader = new FileReader()
        reader.onload = (e) => {
          this.logoPreview = e.target.result
          console.log('Logo preview created')
        }
        reader.readAsDataURL(file)
      } else {
        this.logoPreview = null
        console.log('No file selected')
      }
    },
    
    async handleSubmit() {
      if (!this.validateCurrentStep()) {
        return
      }
      
      if (!this.selectedPackage) {
        showError('Error', 'Please select a reseller package')
        return
      }
      
      if (!this.form.terms) {
        this.errors.terms = 'You must agree to the terms and conditions'
        return
      }
      
      this.loading = true
      this.error = ''
      this.errors = {}
      
      try {
        const formData = new FormData()
        
        // Add all form data
        Object.keys(this.form).forEach(key => {
          if (key === 'logo_file' && this.form[key]) {
            console.log('Adding logo file to FormData:', this.form[key])
            formData.append('logo_file', this.form[key])
          } else if (key !== 'logo_file' && this.form[key] !== null) {
            formData.append(key, this.form[key])
          }
        })
        
        // Debug: Log FormData contents
        console.log('FormData contents:')
        for (let [key, value] of formData.entries()) {
          console.log(key, value)
        }
        
        // Add selected package
        formData.append('reseller_package_id', this.selectedPackage)
        
        const response = await axios.post('/api/register-reseller', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })
        
        // Check if checkout URL is provided
        if (response.data.checkout_url) {
          // Redirect to Stripe checkout
          window.location.href = response.data.checkout_url
        } else {
          // Show success message and redirect to login
          await showSuccess('Registration Successful!', 'Your reseller account has been created successfully. Please check your email for login credentials.')
          this.$router.push('/login')
        }
        
      } catch (error) {
        console.error('Registration error:', error)
        
        if (error.response && error.response.data.errors) {
          this.errors = error.response.data.errors
        } else {
          this.error = error.response?.data?.message || 'An error occurred during registration. Please try again.'
        }
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
