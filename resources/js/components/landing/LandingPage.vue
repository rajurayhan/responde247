<template>
  <div class="min-h-screen bg-white flex flex-col">
    <!-- Header -->
    <header class="bg-white/95 backdrop-blur-sm shadow-sm sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <div class="flex items-center"> 
            <div v-if="branding.logoUrl" class="h-10 w-auto">
              <img :src="branding.logoUrl" :alt="branding.appName" class="h-10 w-auto object-contain" @error="handleLogoError" @load="handleLogoLoad">
            </div>
            <div v-else class="h-10 w-10 bg-gradient-to-r from-primary-600 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
              <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
              </svg>
            </div>
            <div class="ml-3">
              <h1 class="text-2xl font-bold text-gray-900">{{ branding.appName }}</h1>
            </div>
          </div>
          <nav class="hidden md:flex space-x-8">
            <a href="#features" class="text-gray-600 hover:text-slate-600 transition-colors duration-200 font-medium">{{ t('nav.features') }}</a>
            <a v-if="featureFlags.showPricing" href="#pricing" class="text-gray-600 hover:text-slate-600 transition-colors duration-200 font-medium">{{ t('nav.pricing') }}</a>
            <a href="#testimonials" class="text-gray-600 hover:text-slate-600 transition-colors duration-200 font-medium">{{ t('nav.testimonials') }}</a>
            <a v-if="featureFlags.showContactForm" href="#contact" class="text-gray-600 hover:text-slate-600 transition-colors duration-200 font-medium">{{ t('nav.contact') }}</a>
            <router-link v-if="!isAuthenticated" to="/login" class="text-gray-600 hover:text-slate-600 transition-colors duration-200 font-medium">{{ t('nav.login') }}</router-link>
            <router-link v-else to="/dashboard" class="text-gray-600 hover:text-slate-600 transition-colors duration-200 font-medium">{{ t('nav.dashboard') }}</router-link>
          </nav>
          <div class="hidden md:flex items-center space-x-4">
            <!-- Language Switcher -->
            <LanguageSwitcher />
            <router-link v-if="!isAuthenticated" to="/register" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 shadow-lg hover:shadow-xl">
              {{ t('nav.getStarted') }}
            </router-link>
          </div>
          <div class="md:hidden">
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-600 hover:text-slate-600 transition-colors duration-200">
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
        <!-- Mobile Language Switcher -->
        <div class="px-3 py-2">
          <LanguageSwitcher />
        </div>
        <a href="#features" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-slate-600 hover:bg-gray-50">{{ t('nav.features') }}</a>
        <a v-if="featureFlags.showPricing" href="#pricing" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-slate-600 hover:bg-gray-50">{{ t('nav.pricing') }}</a>
        <a href="#testimonials" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-slate-600 hover:bg-gray-50">{{ t('nav.testimonials') }}</a>
        <a v-if="featureFlags.showContactForm" href="#contact" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-slate-600 hover:bg-gray-50">{{ t('nav.contact') }}</a>
        <router-link v-if="!isAuthenticated" to="/login" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-slate-600 hover:bg-gray-50">{{ t('nav.login') }}</router-link>
        <router-link v-else to="/dashboard" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-slate-600 hover:bg-gray-50">{{ t('nav.dashboard') }}</router-link>
        <router-link v-if="!isAuthenticated" to="/register" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-white bg-primary-600 hover:bg-primary-700">{{ t('nav.getStarted') }}</router-link>
      </div>
    </div>

    <div class="flex-1">
      <!-- Hero Section -->
      <div class="relative bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 overflow-hidden">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0">
          <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-r from-blue-600/20 via-purple-600/20 to-pink-600/20"></div>
          <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl animate-pulse"></div>
          <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl animate-pulse delay-1000"></div>
          <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-pink-500/10 rounded-full blur-3xl animate-pulse delay-2000"></div>
        </div>
        
        <!-- Grid Pattern Overlay -->
        <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
        
        <div class="max-w-7xl mx-auto">
          <div class="relative z-10 pb-4 sm:pb-6 md:pb-8 lg:pb-10 xl:pb-12">
            <main class="mt-4 mx-auto max-w-7xl px-4 sm:mt-6 sm:px-6 md:mt-8 lg:mt-10 lg:px-8 xl:mt-12">
              <div class="text-center">
                <!-- Trust Badge -->
                <div class="mb-6">
                  <div class="inline-flex items-center px-6 py-3 rounded-full bg-white/10 backdrop-blur-sm border border-white/20">
                    <div class="flex items-center space-x-2">
                      <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                      <svg class="h-5 w-5 text-white mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                      <span class="text-sm font-medium text-white">{{ t('hero.trustBadge') }}</span>
                    </div>
                  </div>
                </div>

                <h1 class="text-3xl tracking-tight font-extrabold text-white sm:text-4xl md:text-5xl lg:text-6xl"> 
                  <span class="block xl:inline">{{ t('hero.title') }}</span> 
                  <span class="block text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 xl:inline">&nbsp;{{ branding.appName }} {{ t('hero.subtitle') }}</span> 
                </h1>
                <p class="mt-4 text-lg text-gray-300 sm:text-xl sm:max-w-2xl sm:mx-auto md:mt-6 md:text-xl lg:mx-0 leading-relaxed">
                  {{ t('hero.description') }}
                </p>
                
                <!-- Key Benefits -->
                <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-3 sm:gap-8">
                  <div class="flex items-center group">
                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                      <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    <span class="ml-4 text-lg font-medium text-white group-hover:text-gray-200 transition-colors duration-300">{{ t('hero.benefits.availability') }}</span>
                  </div>
                  <div class="flex items-center group">
                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                      <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    <span class="ml-4 text-lg font-medium text-white group-hover:text-gray-200 transition-colors duration-300">{{ t('hero.benefits.instantSetup') }}</span>
                  </div>
                  <div class="flex items-center group">
                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                      <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    <span class="ml-4 text-lg font-medium text-white group-hover:text-gray-200 transition-colors duration-300">{{ t('hero.benefits.noCoding') }}</span>
                  </div>
                </div>

                <div class="mt-10 sm:mt-12 flex justify-center space-y-4 sm:space-y-0 sm:space-x-6">
                  <div class="group">
                    <router-link v-if="!isAuthenticated" to="/register" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 border border-transparent text-lg font-semibold rounded-xl text-white bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 hover:from-blue-700 hover:via-purple-700 hover:to-pink-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105">
                      <svg class="h-5 w-5 mr-3 group-hover:rotate-12 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                      </svg>
                      {{ t('hero.startFreeTrial') }}
                    </router-link>
                    <router-link v-else to="/dashboard" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 border border-transparent text-lg font-semibold rounded-xl text-white bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 hover:from-blue-700 hover:via-purple-700 hover:to-pink-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105">
                      <svg class="h-5 w-5 mr-3 group-hover:rotate-12 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                      </svg>
                      {{ t('hero.goToDashboard') }}
                    </router-link>
                  </div>
                  <div v-if="featureFlags.showDemoRequest" class="group">
                    <router-link to="/demo-request" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 border-2 border-white/30 text-lg font-semibold rounded-xl text-white hover:bg-white/10 hover:border-white/50 transition-all duration-300 backdrop-blur-sm">
                      <svg class="h-5 w-5 mr-3 group-hover:scale-110 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h8m-5-10V3a2 2 0 00-2-2H6a2 2 0 00-2 2v1m12 0V3a2 2 0 012-2h2a2 2 0 012 2v1m-4 0H8m0 0V3a2 2 0 012-2h2a2 2 0 012 2v1" />
                      </svg>
                      {{ t('hero.requestDemo') }}
                    </router-link>
                  </div>
                </div>

                <!-- Social Proof -->
                <!-- <div class="mt-12">
                  <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Trusted by leading companies</p>
                  
                </div> -->
              </div>
            </main>
          </div>
        </div>
      </div>

      <!-- Statistics Section -->
      <div class="bg-gradient-to-r from-slate-800 to-slate-900 py-12 mt-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
            <div class="text-center group">
              <div class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-400 sm:text-4xl group-hover:scale-110 transition-transform duration-300">10K+</div>
              <div class="mt-2 text-sm font-medium text-gray-300 uppercase tracking-wide">{{ t('stats.activeUsers') }}</div>
            </div>
            <div class="text-center group">
              <div class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400 sm:text-4xl group-hover:scale-110 transition-transform duration-300">99.9%</div>
              <div class="mt-2 text-sm font-medium text-gray-300 uppercase tracking-wide">{{ t('stats.uptime') }}</div>
            </div>
            <div class="text-center group">
              <div class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-green-400 to-emerald-400 sm:text-4xl group-hover:scale-110 transition-transform duration-300">24/7</div>
              <div class="mt-2 text-sm font-medium text-gray-300 uppercase tracking-wide">{{ t('stats.support') }}</div>
            </div>
            <div class="text-center group">
              <div class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-400 sm:text-4xl group-hover:scale-110 transition-transform duration-300">50+</div>
              <div class="mt-2 text-sm font-medium text-gray-300 uppercase tracking-wide">{{ t('stats.countries') }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Features Section -->
      <div id="features" class="py-16 bg-gradient-to-b from-gray-50 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center mb-16">
            <h2 class="text-4xl font-extrabold text-gray-900 sm:text-5xl">
              {{ t('features.title') }}
            </h2>
            <p class="mt-4 text-xl text-gray-600 max-w-3xl mx-auto">
              {{ t('features.subtitle') }}
            </p>
          </div>

          <div class="mt-12">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
              <div
                v-for="feature in features"
                :key="feature.id"
                class="group relative bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 border border-gray-100"
              >
                <div class="absolute inset-0 bg-gradient-to-br from-slate-50 to-gray-50 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative z-10">
                  <div class="w-14 h-14 bg-gradient-to-r from-slate-600 to-slate-700 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                    <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="feature.icon" />
                    </svg>
                  </div>
                  <h3 class="text-xl font-bold text-gray-900 mb-3">{{ feature.title }}</h3>
                  <p class="text-gray-600 leading-relaxed">
                    {{ feature.description }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Additional Feature Highlights -->
          <div class="mt-16">
            <div class="bg-gradient-to-r from-slate-900 via-purple-900 to-slate-900 rounded-3xl p-8 md:p-12 relative overflow-hidden">
              <!-- Background Elements -->
              <div class="absolute inset-0">
                <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-r from-blue-600/10 via-purple-600/10 to-pink-600/10"></div>
                <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-blue-500/5 rounded-full blur-3xl"></div>
                <div class="absolute bottom-1/4 right-1/4 w-64 h-64 bg-purple-500/5 rounded-full blur-3xl"></div>
              </div>
              
              <div class="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                  <h3 class="text-3xl font-bold text-white mb-6">{{ t('features.whyChoose') }} {{ branding.appName }}?</h3>
                  <div class="space-y-6">
                    <div class="flex items-start group">
                      <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-r from-slate-600 to-slate-700 group-hover:scale-110 transition-transform duration-300">
                          <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                          </svg>
                        </div>
                      </div>
                      <div class="ml-4">
                        <h4 class="text-lg font-semibold text-white group-hover:text-gray-200 transition-colors duration-300">{{ t('features.highlights.security.title') }}</h4>
                        <p class="text-gray-300">{{ t('features.highlights.security.description') }}</p>
                      </div>
                    </div>
                    <div class="flex items-start group">
                      <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-r from-slate-600 to-slate-700 group-hover:scale-110 transition-transform duration-300">
                          <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                          </svg>
                        </div>
                      </div>
                      <div class="ml-4">
                        <h4 class="text-lg font-semibold text-white group-hover:text-gray-200 transition-colors duration-300">{{ t('features.highlights.scalable.title') }}</h4>
                        <p class="text-gray-300">{{ t('features.highlights.scalable.description') }}</p>
                      </div>
                    </div>
                    <div class="flex items-start group">
                      <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-r from-slate-600 to-slate-700 group-hover:scale-110 transition-transform duration-300">
                          <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                          </svg>
                        </div>
                      </div>
                      <div class="ml-4">
                        <h4 class="text-lg font-semibold text-white group-hover:text-gray-200 transition-colors duration-300">{{ t('features.highlights.analytics.title') }}</h4>
                        <p class="text-gray-300">{{ t('features.highlights.analytics.description') }}</p>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="relative">
                  <div class="bg-gradient-to-br from-slate-600 via-slate-700 to-slate-800 rounded-2xl p-8 text-white relative overflow-hidden">
                    <!-- Background Elements -->
                    <div class="absolute inset-0">
                      <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-white/10 to-transparent"></div>
                      <div class="absolute top-10 right-10 w-20 h-20 bg-white/10 rounded-full animate-pulse"></div>
                      <div class="absolute bottom-20 left-10 w-16 h-16 bg-white/10 rounded-full animate-pulse delay-1000"></div>
                    </div>
                    
                    <div class="relative z-10 text-center">
                      <svg class="h-16 w-16 mx-auto mb-4 text-white/80" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                      </svg>
                      <h4 class="text-2xl font-bold mb-2">{{ t('features.readyToStart') }}</h4>
                      <p class="text-white/80 mb-6">{{ t('features.readyToStartSubtitle') }}</p>
                      <router-link v-if="!isAuthenticated" to="/register" class="inline-flex items-center px-6 py-3 bg-white text-gray-900 font-semibold rounded-xl hover:bg-gray-50 transition-all duration-300 hover:scale-105 shadow-lg">
                        {{ t('hero.startFreeTrial') }}
                      </router-link>
                      <router-link v-else to="/dashboard" class="inline-flex items-center px-6 py-3 bg-white text-gray-900 font-semibold rounded-xl hover:bg-gray-50 transition-all duration-300 hover:scale-105 shadow-lg">
                        {{ t('hero.goToDashboard') }}
                      </router-link>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Testimonials Section -->
      <div id="testimonials" class="py-12 bg-gradient-to-b from-gray-50 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center">
            <h2 class="text-base text-slate-600 font-semibold tracking-wide uppercase">{{ t('testimonials.title') }}</h2>
            <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
              {{ t('testimonials.subtitle') }}
            </p>
            <p class="mt-4 max-w-2xl text-xl text-gray-600 mx-auto">
              {{ t('testimonials.description') }} {{ branding.appName }}.
            </p>
          </div>

          <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow duration-300">
              <div class="flex items-center mb-4">
                <div class="flex text-yellow-400">
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                </div>
              </div>
              <blockquote class="text-gray-600 mb-6">
                "{{ branding.appName }} {{ t('testimonials.testimonials.0.text') }}"
              </blockquote>
              <div class="flex items-center">
                <div class="h-12 w-12 bg-gradient-to-r from-primary-500 to-blue-500 rounded-full flex items-center justify-center">
                  <span class="text-white font-semibold text-lg">SM</span>
                </div>
                <div class="ml-4">
                  <div class="font-semibold text-gray-900">{{ t('testimonials.testimonials.0.author') }}</div>
                  <div class="text-sm text-gray-500">{{ t('testimonials.testimonials.0.position') }}</div>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300">
              <div class="flex items-center mb-4">
                <div class="flex text-yellow-400">
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                </div>
              </div>
              <blockquote class="text-gray-600 mb-6">
                "{{ t('testimonials.testimonials.1.text') }}"
              </blockquote>
              <div class="flex items-center">
                <div class="h-12 w-12 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center">
                  <span class="text-white font-semibold text-lg">MJ</span>
                </div>
                <div class="ml-4">
                  <div class="font-semibold text-gray-900">{{ t('testimonials.testimonials.1.author') }}</div>
                  <div class="text-sm text-gray-500">{{ t('testimonials.testimonials.1.position') }}</div>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300">
              <div class="flex items-center mb-4">
                <div class="flex text-yellow-400">
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                </div>
              </div>
              <blockquote class="text-gray-600 mb-6">
                "{{ t('testimonials.testimonials.2.text') }}"
              </blockquote>
              <div class="flex items-center">
                <div class="h-12 w-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center">
                  <span class="text-white font-semibold text-lg">AL</span>
                </div>
                <div class="ml-4">
                  <div class="font-semibold text-gray-900">{{ t('testimonials.testimonials.2.author') }}</div>
                  <div class="text-sm text-gray-500">{{ t('testimonials.testimonials.2.position') }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pricing Section -->
      <div v-if="featureFlags.showPricing" id="pricing" class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center">
            <h2 class="text-base text-slate-600 font-semibold tracking-wide uppercase">{{ t('pricing.title') }}</h2>
            <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
              {{ t('pricing.subtitle') }}
            </p>
            <p class="mt-4 max-w-2xl text-xl text-gray-600 mx-auto">
              {{ t('pricing.description') }}
            </p>
          </div>

          <!-- Billing Interval Toggle -->
          <div class="mt-10 flex justify-center">
            <div class="bg-gray-100 rounded-xl p-1 inline-flex shadow-sm">
              <button
                @click="billingInterval = 'monthly'"
                :class="[
                  'px-6 py-3 text-sm font-semibold rounded-lg transition-all duration-300',
                  billingInterval === 'monthly'
                    ? 'bg-white text-gray-900 shadow-md transform scale-105'
                    : 'text-gray-600 hover:text-gray-800'
                ]"
              >
                {{ t('pricing.monthly') }}
              </button>
              <button
                @click="billingInterval = 'yearly'"
                :class="[
                  'px-6 py-3 text-sm font-semibold rounded-lg transition-all duration-300 relative',
                  billingInterval === 'yearly'
                    ? 'bg-white text-gray-900 shadow-md transform scale-105'
                    : 'text-gray-600 hover:text-gray-800'
                ]"
              >
                {{ t('pricing.yearly') }}
                <span class="absolute -top-2 -right-2 text-xs bg-green-500 text-white px-2 py-1 rounded-full font-medium">{{ t('pricing.save20') }}</span>
              </button>
            </div>
          </div>

          <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Dynamic Package Cards -->
            <div
              v-for="(pkg, index) in packages"
              :key="pkg.id"
              :class="[
                'relative bg-white rounded-xl shadow-md border-2 p-6 transition-all duration-300 hover:shadow-lg',
                pkg.is_popular ? 'border-primary-500 ring-1 ring-primary-200' : 'border-gray-200 hover:border-primary-300'
              ]"
            >
              <div v-if="pkg.is_popular" class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                <span class="bg-gradient-to-r from-primary-600 to-primary-700 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-md">
                  {{ t('pricing.mostPopular') }}
                </span>
              </div>
              
              <div class="text-center">
                <h3 class="text-xl font-bold text-gray-900 mb-2">{{ pkg.name }}</h3>
                <div class="mt-4 mb-3">
                  <span class="text-4xl font-extrabold text-gray-900">
                    ${{ billingInterval === 'monthly' ? pkg.price : pkg.yearly_price }}
                  </span>
                  <span class="text-base text-gray-500 ml-1">
                    /{{ billingInterval === 'monthly' ? t('pricing.monthly').toLowerCase() : t('pricing.yearly').toLowerCase() }}
                  </span>
                </div>
                <p class="text-gray-600 mb-6 text-sm">{{ pkg.description }}</p>
              </div>
              
              <ul class="space-y-3 mb-6">
                <li v-for="feature in pkg.features" :key="feature" class="flex items-start">
                  <svg class="h-4 w-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                  <span class="text-gray-700 text-sm">{{ feature }}</span>
                </li>
              </ul>
              
              <div class="mt-auto">
                <router-link
                  :to="isAuthenticated ? '/subscription' : '/register'"
                  :class="[
                    'w-full flex justify-center items-center py-4 px-6 rounded-xl font-semibold text-lg transition-all duration-300 transform hover:scale-105',
                    pkg.is_popular
                      ? 'bg-gradient-to-r from-primary-600 to-primary-700 text-white shadow-lg hover:shadow-xl hover:from-primary-700 hover:to-primary-800'
                      : 'bg-gray-900 text-white hover:bg-gray-800 shadow-md hover:shadow-lg'
                  ]"
                >
                  <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                  {{ t('pricing.getStarted') }}
                </router-link>
              </div>
            </div>
          </div>

          <!-- Pricing FAQ -->
          <div class="mt-12">
            <div class="bg-gradient-to-r from-gray-50 to-primary-50 rounded-3xl p-8 md:p-12">
              <div class="text-center mb-12">
                <h3 class="text-3xl font-bold text-gray-900 mb-4">{{ t('pricing.faq.title') }}</h3>
                <p class="text-xl text-gray-600">{{ t('pricing.faq.subtitle') }}</p>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-white rounded-xl p-6 shadow-sm">
                  <h4 class="text-lg font-semibold text-gray-900 mb-3">{{ t('pricing.faq.questions.0.question') }}</h4>
                  <p class="text-gray-600">{{ t('pricing.faq.questions.0.answer') }}</p>
                </div>
                <div class="bg-white rounded-xl p-6 shadow-sm">
                  <h4 class="text-lg font-semibold text-gray-900 mb-3">{{ t('pricing.faq.questions.1.question') }}</h4>
                  <p class="text-gray-600">{{ t('pricing.faq.questions.1.answer') }}</p>
                </div>
                <div class="bg-white rounded-xl p-6 shadow-sm">
                  <h4 class="text-lg font-semibold text-gray-900 mb-3">{{ t('pricing.faq.questions.2.question') }}</h4>
                  <p class="text-gray-600">{{ t('pricing.faq.questions.2.answer') }}</p>
                </div>
                <div class="bg-white rounded-xl p-6 shadow-sm">
                  <h4 class="text-lg font-semibold text-gray-900 mb-3">{{ t('pricing.faq.questions.3.question') }}</h4>
                  <p class="text-gray-600">{{ t('pricing.faq.questions.3.answer') }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Contact Us Section -->
      <div v-if="featureFlags.showContactForm" id="contact" class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
              {{ t('contact.title') }}
            </h2>
            <p class="mt-4 text-xl text-gray-600">
              {{ t('contact.subtitle') }}
            </p>
          </div>

          <div class="mt-12 grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- Contact Information -->
            <div class="bg-gray-50 rounded-lg p-8">
              <h3 class="text-lg font-medium text-gray-900 mb-6">{{ t('contact.contactInfo') }}</h3>
              <div class="space-y-6">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">{{ t('contact.phone') }}</p>
                    <a :href="`tel:${branding.companyPhone}`" class="text-lg text-slate-600 hover:text-primary-700 font-medium">{{ branding.companyPhone }}</a>
                  </div>
                </div>
                
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">{{ t('contact.email') }}</p>
                    <a :href="`mailto:${branding.supportEmail}`" class="text-lg text-slate-600 hover:text-primary-700 font-medium">{{ branding.supportEmail }}</a>
                  </div>
                </div>
                
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">{{ t('contact.businessHours') }}</p>
                    <p class="text-lg text-gray-700">{{ branding.businessHours }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Contact Form -->
            <div class="bg-white border border-gray-200 rounded-lg p-8">
              <h3 class="text-lg font-medium text-gray-900 mb-6">{{ t('contact.sendMessage') }}</h3>
              
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
                    <label for="first_name" class="block text-sm font-medium text-gray-700">{{ t('contact.firstName') }}</label>
                    <input
                      type="text"
                      id="first_name"
                      v-model="contactForm.first_name"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700">{{ t('contact.lastName') }}</label>
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
                  <label for="email" class="block text-sm font-medium text-gray-700">{{ t('contact.email') }}</label>
                  <input
                    type="email"
                    id="email"
                    v-model="contactForm.email"
                    required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  />
                </div>
                
                <div>
                  <label for="phone" class="block text-sm font-medium text-gray-700">{{ t('contact.phoneOptional') }}</label>
                  <input
                    type="tel"
                    id="phone"
                    v-model="contactForm.phone"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  />
                </div>
                
                <div>
                  <label for="subject" class="block text-sm font-medium text-gray-700">{{ t('contact.subject') }}</label>
                  <select
                    id="subject"
                    v-model="contactForm.subject"
                    required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                  >
                    <option value="">{{ t('contact.selectSubject') }}</option>
                    <option value="general">{{ t('contact.subjects.general') }}</option>
                    <option value="sales">{{ t('contact.subjects.sales') }}</option>
                    <option value="support">{{ t('contact.subjects.support') }}</option>
                    <option value="demo">{{ t('contact.subjects.demo') }}</option>
                    <option value="partnership">{{ t('contact.subjects.partnership') }}</option>
                    <option value="other">{{ t('contact.subjects.other') }}</option>
                  </select>
                </div>
                
                <div>
                  <label for="message" class="block text-sm font-medium text-gray-700">{{ t('contact.message') }}</label>
                  <textarea
                    id="message"
                    v-model="contactForm.message"
                    rows="4"
                    required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                    placeholder="{{ t('contact.messagePlaceholder') }}"
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
                    {{ contactFormSubmitting ? t('contact.sending') : t('contact.sendMessageBtn') }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- CTA Section -->
      <div class="bg-gradient-to-r from-slate-600 via-slate-700 to-slate-800 py-12 relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-white/10 to-transparent"></div>
        
        <div class="relative z-10 max-w-4xl mx-auto text-center py-8 px-4 sm:px-6 lg:px-8">
          <div class="mb-6">
            <div class="inline-flex items-center px-4 py-2 rounded-full bg-white/20 backdrop-blur-sm border border-white/30 mb-4">
              <svg class="h-5 w-5 text-white mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
              <span class="text-sm font-medium text-white">{{ t('cta.joinBusinesses') }} {{ branding.appName }}</span>
            </div>
          </div>
          
          <h2 class="text-3xl font-extrabold text-white sm:text-4xl lg:text-5xl mb-4">
            <span class="block">{{ t('cta.readyToTransform') }}</span>
            <span class="block text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-orange-300">{{ t('cta.yourBusiness') }}</span>
          </h2>
          <p class="mt-4 text-lg leading-7 text-gray-200 max-w-2xl mx-auto">
            {{ t('cta.startTrialDescription') }} {{ branding.appName }} {{ t('cta.canRevolutionize') }}
          </p>
          
          <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center items-center">
            <router-link 
              v-if="!isAuthenticated" 
              to="/register" 
              class="group w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-semibold rounded-lg text-slate-600 bg-white hover:bg-gray-50 transition-all duration-300 shadow-lg hover:shadow-xl"
            >
              <svg class="h-5 w-5 mr-2 group-hover:rotate-12 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              {{ t('hero.startFreeTrial') }}
              <svg class="ml-2 h-4 w-4 group-hover:translate-x-1 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </router-link>
            <router-link 
              v-else 
              to="/dashboard" 
              class="group w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-semibold rounded-lg text-slate-600 bg-white hover:bg-gray-50 transition-all duration-300 shadow-lg hover:shadow-xl"
            >
              <svg class="h-5 w-5 mr-2 group-hover:rotate-12 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              {{ t('hero.goToDashboard') }}
              <svg class="ml-2 h-4 w-4 group-hover:translate-x-1 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </router-link>
            
            <router-link 
              v-if="featureFlags.showDemoRequest" 
              to="/demo-request" 
              class="group w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border-2 border-white text-base font-semibold rounded-lg text-white hover:bg-white hover:text-slate-600 transition-all duration-300"
            >
              <svg class="h-5 w-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h8m-5-10V3a2 2 0 00-2-2H6a2 2 0 00-2 2v1m12 0V3a2 2 0 012-2h2a2 2 0 012 2v1m-4 0H8m0 0V3a2 2 0 012-2h2a2 2 0 012 2v1" />
              </svg>
              {{ t('hero.requestDemo') }}
            </router-link>
          </div>
          
          <!-- Trust Indicators -->
          <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-8 opacity-80">
            <div class="text-center">
              <div class="text-2xl font-bold text-white">99.9%</div>
              <div class="text-sm text-primary-200">{{ t('cta.trustIndicators.uptimeSLA') }}</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-white">24/7</div>
              <div class="text-sm text-primary-200">{{ t('cta.trustIndicators.support247') }}</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-white">SOC 2</div>
              <div class="text-sm text-primary-200">{{ t('cta.trustIndicators.soc2Compliant') }}</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-white">30-day</div>
              <div class="text-sm text-primary-200">{{ t('cta.trustIndicators.moneyBack') }}</div>
            </div>
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
import { useLanguage } from '../../composables/useLanguage.js'
import Footer from '../shared/Footer.vue'
import LanguageSwitcher from '../shared/LanguageSwitcher.vue'

export default {
  name: 'LandingPage',
  components: {
    Footer,
    LanguageSwitcher
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

    // Get language support
    const { t } = useLanguage()

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
      handleBannerLoad,
      t
    }
  }
}
</script> 