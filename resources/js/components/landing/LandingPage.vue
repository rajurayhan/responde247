<template>
  <div class="min-h-screen bg-white flex flex-col">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <div class="flex items-center"> 
            <div v-if="branding.logoUrl" class="h-8 w-auto">
              <img :src="branding.logoUrl" :alt="branding.appName" class="h-8 w-auto object-contain" @error="handleLogoError" @load="handleLogoLoad">
            </div>
            <div v-else class="h-8 w-8 bg-gradient-to-r from-primary-600 to-blue-600 rounded-lg flex items-center justify-center">
              <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
              </svg>
            </div>
            <div class="ml-2">
              <h1 class="text-xl font-bold text-gray-900">{{ branding.appName }}</h1>
            </div>
          </div>
          <nav class="hidden md:flex space-x-8">
            <a href="#features" class="text-gray-500 hover:text-gray-900">Features</a>
            <a v-if="featureFlags.showPricing" href="#pricing" class="text-gray-500 hover:text-gray-900">Pricing</a>
            <a v-if="featureFlags.showContactForm" href="#contact" class="text-gray-500 hover:text-gray-900">Contact</a>
            <router-link v-if="!isAuthenticated" to="/login" class="text-gray-500 hover:text-gray-900">Login</router-link>
            <router-link v-else to="/dashboard" class="text-gray-500 hover:text-gray-900">Dashboard</router-link>
          </nav>
          <div class="md:hidden">
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-500 hover:text-gray-900">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Mobile menu -->
    <div v-if="mobileMenuOpen" class="md:hidden">
      <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-b border-gray-200">
        <a href="#features" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Features</a>
        <a v-if="featureFlags.showPricing" href="#pricing" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Pricing</a>
        <a v-if="featureFlags.showContactForm" href="#contact" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Contact</a>
        <router-link v-if="!isAuthenticated" to="/login" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Login</router-link>
        <router-link v-else to="/dashboard" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Dashboard</router-link>
      </div>
    </div>

    <div class="flex-1">
      <!-- Hero Section -->
      <div class="relative bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto">
          <div class="relative z-10 pb-8 sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
            <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
              <div class="sm:text-center lg:text-left">
                <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl"> 
                  <span class="block xl:inline">{{ branding.slogan }} </span>
                  <span class="block text-primary-600 xl:inline"> {{ branding.appName }} answers 24x7!</span> 
                </h1>
                <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                  {{ branding.description }}
                </p>
                <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                  <div class="rounded-md shadow">
                    <router-link v-if="!isAuthenticated" to="/register" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 md:py-4 md:text-lg md:px-10">
                      Get Started
                    </router-link>
                    <router-link v-else to="/dashboard" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 md:py-4 md:text-lg md:px-10">
                      Dashboard
                    </router-link>
                  </div>
                  <div v-if="featureFlags.showDemoRequest" class="mt-3 sm:mt-0 sm:ml-3">
                    <router-link to="/demo-request" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-primary-700 bg-primary-100 hover:bg-primary-200 md:py-4 md:text-lg md:px-10">
                      Request Demo
                    </router-link>
                  </div>
                </div>
              </div>
            </main>
          </div>
        </div>
        <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2">
          <div v-if="branding.bannerUrl" class="h-56 w-full sm:h-72 md:h-96 lg:w-full lg:h-full rounded-lg shadow-xl overflow-hidden">
            <img :src="branding.bannerUrl" :alt="branding.appName" class="w-full h-full object-cover" @error="handleBannerError" @load="handleBannerLoad">
          </div>
          <div v-else class="h-56 w-full sm:h-72 md:h-96 lg:w-full lg:h-full bg-gradient-to-r from-primary-400 to-blue-500 rounded-lg shadow-xl"></div>
        </div>
      </div>

      <!-- Features Section -->
      <div id="features" class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="lg:text-center">
            <h2 class="text-base text-primary-600 font-semibold tracking-wide uppercase">Features</h2>
            <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
              Everything you need for {{ branding.appName }}
            </p>
            <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
              Our platform provides all the tools you need to create, deploy, and manage intelligent voice agents.
            </p>
          </div>

          <div class="mt-10">
            <div class="space-y-10 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-10">
              <div
                v-for="feature in features"
                :key="feature.id"
                class="relative"
              >
                <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-primary-500 text-white">
                  <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="feature.icon" />
                  </svg>
                </div>
                <p class="ml-16 text-lg leading-6 font-medium text-gray-900">{{ feature.title }}</p>
                <p class="mt-2 ml-16 text-base text-gray-500">
                  {{ feature.description }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pricing Section -->
      <div v-if="featureFlags.showPricing" id="pricing" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
              Simple, transparent pricing
            </h2>
            <p class="mt-4 text-xl text-gray-600">
              Choose the plan that's right for your business
            </p>
          </div>

          <!-- Billing Interval Toggle -->
          <div class="mt-8 flex justify-center">
            <div class="bg-gray-100 rounded-lg p-1 inline-flex">
              <button
                @click="billingInterval = 'monthly'"
                :class="[
                  'px-4 py-2 text-sm font-medium rounded-md transition-colors',
                  billingInterval === 'monthly'
                    ? 'bg-white text-gray-900 shadow-sm'
                    : 'text-gray-500 hover:text-gray-700'
                ]"
              >
                Monthly
              </button>
              <button
                @click="billingInterval = 'yearly'"
                :class="[
                  'px-4 py-2 text-sm font-medium rounded-md transition-colors',
                  billingInterval === 'yearly'
                    ? 'bg-white text-gray-900 shadow-sm'
                    : 'text-gray-500 hover:text-gray-700'
                ]"
              >
                Yearly
                <span class="ml-1 text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Save 20%</span>
              </button>
            </div>
          </div>

          <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Dynamic Package Cards -->
            <div
              v-for="(pkg, index) in packages"
              :key="pkg.id"
              :class="[
                'bg-white rounded-lg shadow-lg border p-8',
                pkg.is_popular ? 'border-2 border-primary-500 relative' : 'border-gray-200'
              ]"
            >
              <div v-if="pkg.is_popular" class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                <span class="bg-primary-600 text-white px-3 py-1 rounded-full text-sm font-medium">Most Popular</span>
              </div>
              <div class="text-center">
                <h3 class="text-2xl font-bold text-gray-900">{{ pkg.name }}</h3>
                <div class="mt-4">
                  <span class="text-4xl font-extrabold text-gray-900">
                    ${{ billingInterval === 'monthly' ? pkg.price : pkg.yearly_price }}
                  </span>
                  <span class="text-gray-500">
                    /{{ billingInterval === 'monthly' ? 'month' : 'year' }}
                  </span>
                </div>
                <p class="mt-4 text-gray-600">{{ pkg.description }}</p>
              </div>
              <ul class="mt-8 space-y-4">
                <li v-for="feature in pkg.features" :key="feature" class="flex items-center">
                  <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                  <span class="ml-3 text-gray-600">{{ feature }}</span>
                </li>
              </ul>
              <div class="mt-8">
                <router-link
                  :to="isAuthenticated ? '/subscription' : '/register'"
                  class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                  Get Started
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Contact Us Section -->
      <div v-if="featureFlags.showContactForm" id="contact" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
              Get in Touch
            </h2>
            <p class="mt-4 text-xl text-gray-600">
              Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.
            </p>
          </div>

          <div class="mt-12 grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- Contact Information -->
            <div class="bg-gray-50 rounded-lg p-8">
              <h3 class="text-lg font-medium text-gray-900 mb-6">Contact Information</h3>
              <div class="space-y-6">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">Phone</p>
                    <a :href="`tel:${branding.companyPhone}`" class="text-lg text-primary-600 hover:text-primary-700 font-medium">{{ branding.companyPhone }}</a>
                  </div>
                </div>
                
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">Email</p>
                    <a :href="`mailto:${branding.supportEmail}`" class="text-lg text-primary-600 hover:text-primary-700 font-medium">{{ branding.supportEmail }}</a>
                  </div>
                </div>
                
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">Business Hours</p>
                    <p class="text-lg text-gray-700">Monday - Friday: 9:00 AM - 6:00 PM EST</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Contact Form -->
            <div class="bg-white border border-gray-200 rounded-lg p-8">
              <h3 class="text-lg font-medium text-gray-900 mb-6">Send us a Message</h3>
              
              <!-- Success Message -->
              <div v-if="contactFormSuccess" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-md">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ contactFormSuccess }}</p>
                  </div>
                </div>
              </div>
              
              <!-- Error Message -->
              <div v-if="contactFormError" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ contactFormError }}</p>
                  </div>
                </div>
              </div>
              
              <form @submit.prevent="submitContactForm" class="space-y-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                  <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                    <input
                      type="text"
                      id="first_name"
                      v-model="contactForm.first_name"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                    <input
                      type="text"
                      id="last_name"
                      v-model="contactForm.last_name"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    />
                  </div>
                </div>
                
                <div>
                  <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                  <input
                    type="email"
                    id="email"
                    v-model="contactForm.email"
                    required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  />
                </div>
                
                <div>
                  <label for="phone" class="block text-sm font-medium text-gray-700">Phone (Optional)</label>
                  <input
                    type="tel"
                    id="phone"
                    v-model="contactForm.phone"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  />
                </div>
                
                <div>
                  <label for="subject" class="block text-sm font-medium text-gray-700">Subject</label>
                  <select
                    id="subject"
                    v-model="contactForm.subject"
                    required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  >
                    <option value="">Select a subject</option>
                    <option value="general">General Inquiry</option>
                    <option value="sales">Sales Question</option>
                    <option value="support">Technical Support</option>
                    <option value="demo">Demo Request</option>
                    <option value="partnership">Partnership</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                
                <div>
                  <label for="message" class="block text-sm font-medium text-gray-700">Message</label>
                  <textarea
                    id="message"
                    v-model="contactForm.message"
                    rows="4"
                    required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    placeholder="Tell us how we can help you..."
                  ></textarea>
                </div>
                
                <div>
                  <button
                    type="submit"
                    :disabled="contactFormSubmitting"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <svg v-if="contactFormSubmitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ contactFormSubmitting ? 'Sending...' : 'Send Message' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- CTA Section -->
      <div class="bg-primary-700">
        <div class="max-w-2xl mx-auto text-center py-16 px-4 sm:py-20 sm:px-6 lg:px-8">
          <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
            <span class="block">Ready to get started?</span>
            <span class="block">Transform your business today.</span>
          </h2>
          <p class="mt-4 text-lg leading-6 text-primary-200">
            Join thousands of businesses already using our {{ branding.appName }} platform.
          </p>
          <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
            <router-link v-if="!isAuthenticated" to="/register" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-primary-600 bg-white hover:bg-primary-50">
              Sign up now
            </router-link>
            <router-link v-else to="/dashboard" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-primary-600 bg-white hover:bg-primary-50">
              Dashboard
            </router-link>
            <router-link v-if="featureFlags.showDemoRequest" to="/demo-request" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3 border border-white text-base font-medium rounded-md text-white hover:bg-primary-600">
              Request Demo
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <Footer />
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { updateDocumentTitle } from '../../utils/systemSettings.js'
import { useResellerData } from '../../composables/useResellerData.js'
import Footer from '../shared/Footer.vue'

export default {
  name: 'LandingPage',
  components: {
    Footer
  },
  setup() {
    const packages = ref([])
    const features = ref([])
    const mobileMenuOpen = ref(false)
    const billingInterval = ref('monthly') // Default to monthly
    const contactFormSubmitting = ref(false)
    const contactFormSuccess = ref('')
    const contactFormError = ref('')
    const contactForm = ref({
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      subject: '',
      message: ''
    })

    // Get reseller data - available immediately
    const { branding, features: featureFlags, isLoaded } = useResellerData()

    // Check if user is authenticated
    const isAuthenticated = computed(() => {
      return localStorage.getItem('token') !== null
    })

    const loadPackages = async () => {
      try {
        const response = await axios.get('/api/subscriptions/packages')
        packages.value = response.data.data
      } catch (error) {
        // Handle error silently
      }
    }

    const loadFeatures = async () => {
      try {
        const response = await axios.get('/api/features')
        features.value = response.data.data
      } catch (error) {
        // Handle error silently
      }
    }


    const submitContactForm = async () => {
      contactFormSubmitting.value = true
      contactFormSuccess.value = ''
      contactFormError.value = ''
      try {
        const response = await axios.post('/api/contact', contactForm.value)
        
        if (response.data.success) {
          // Reset form
          contactForm.value = {
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            subject: '',
            message: ''
          }
          
          // Show success message
          contactFormSuccess.value = response.data.message
        } else {
          contactFormError.value = response.data.message || 'There was an error sending your message. Please try again.'
        }
        
      } catch (error) {
        console.error('Error submitting contact form:', error)
        if (error.response && error.response.data && error.response.data.message) {
          contactFormError.value = error.response.data.message
        } else {
          contactFormError.value = 'There was an error sending your message. Please try again or contact us directly.'
        }
      } finally {
        contactFormSubmitting.value = false
      }
    }

    const handleLogoError = (event) => {
      console.error('Logo failed to load:', branding.value.logoUrl)
      console.error('Error event:', event)
    }

    const handleLogoLoad = () => {
      console.log('Logo loaded successfully:', branding.value.logoUrl)
    }

    const handleBannerError = (event) => {
      console.error('Banner failed to load:', branding.value.bannerUrl)
      console.error('Error event:', event)
    }

    const handleBannerLoad = () => {
      console.log('Banner loaded successfully:', branding.value.bannerUrl)
    }

    onMounted(() => {
      loadPackages()
      loadFeatures()
      
      // Set document title using reseller data (available immediately)
      updateDocumentTitle(`${branding.value.appName} - ${branding.value.slogan}`)
      
      // Close mobile menu when clicking outside
      document.addEventListener('click', (e) => {
        const mobileMenuButton = document.querySelector('[aria-expanded="false"]')
        if (mobileMenuButton && !mobileMenuButton.contains(e.target) && !e.target.closest('.mobile-menu')) {
          mobileMenuOpen.value = false
        }
      })
    })

    return {
      packages,
      features,
      isAuthenticated,
      branding,
      featureFlags,
      isLoaded,
      mobileMenuOpen,
      billingInterval,
      contactForm,
      contactFormSubmitting,
      contactFormSuccess,
      contactFormError,
      submitContactForm,
      handleLogoError,
      handleLogoLoad,
      handleBannerError,
      handleBannerLoad
    }
  }
}
</script> 