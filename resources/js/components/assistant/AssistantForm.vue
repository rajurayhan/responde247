<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <div class="sticky top-0 z-50 border-b border-gray-200 bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex justify-between items-center">
          <div class="flex items-center">
            <button @click="goBack" class="mr-4 text-gray-500 hover:text-gray-700">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
            </button>
            <div class="h-8 w-8 bg-green-600 rounded-lg flex items-center justify-center mr-3">
              <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
              </svg>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">{{ isCreating ? 'Create Assistant' : 'Edit Assistant' }}</h1>
              <p class="text-gray-600">{{ isCreating ? 'Create a new voice assistant' : 'Update your voice assistant configuration' }}</p>
              <!-- Active Subscription Status (only show when creating and not super admin) -->
              <div v-if="isCreating && subscriptionInfo?.hasSubscription && !isSuperAdmin" class="mt-2 text-sm">
                <span class="text-green-600 font-medium">Active Subscription</span>
                <span class="mx-2 text-gray-300">|</span>
                <span class="text-gray-500">Plan: {{ subscriptionInfo.plan }}</span>
                <span class="mx-2 text-gray-300">|</span>
                <span class="text-gray-500">Assistants: {{ subscriptionInfo.used }}/{{ subscriptionInfo.limit }}</span>
                <span v-if="subscriptionInfo.remaining > 0" class="ml-2 text-green-600">
                  ({{ subscriptionInfo.remaining }} remaining)
                </span>
                <span v-else class="ml-2 text-red-600">
                  (Limit reached)
                </span>
                <router-link v-if="subscriptionInfo.remaining <= 0" :to="isResellerAdmin ? '/reseller-billing' : '/subscription'" class="ml-2 text-blue-600 hover:text-blue-700 underline">
                  Upgrade Plan
                </router-link>
              </div>
              <!-- Pending Subscription Warning (only show when creating and not super admin) -->
              <div v-else-if="isCreating && subscriptionInfo?.subscriptionStatus === 'pending' && !isSuperAdmin" class="mt-2 text-sm">
                <span class="text-yellow-600 font-medium">Payment Processing</span>
                <span class="mx-2 text-gray-300">|</span>
                <span class="text-gray-500">Your subscription is being processed. You'll be able to create assistants once payment is confirmed.</span>
                <router-link :to="isResellerAdmin ? '/reseller-billing' : '/subscription'" class="ml-2 text-blue-600 hover:text-blue-700 underline">
                  Check Payment Status
                </router-link>
              </div>
              <!-- No Subscription Warning (only show when creating and not admin) -->
              <div v-else-if="isCreating && !subscriptionInfo?.hasSubscription && !isAdmin" class="mt-2 text-sm">
                <span class="text-red-600 font-medium">No Active Subscription</span>
                <span class="mx-2 text-gray-300">|</span>
                <span class="text-gray-500">Subscribe to create assistants</span>
                <router-link :to="isResellerAdmin ? '/reseller-billing' : '/subscription'" class="ml-2 text-blue-600 hover:text-blue-700 underline">
                  Subscribe Now
                </router-link>
              </div>
              <!-- Admin Status (only show when creating and admin) -->
              <div v-else-if="isCreating && isAdmin" class="mt-2 text-sm">
                <span class="text-green-600 font-medium">Admin User</span>
                <span class="mx-2 text-gray-300">|</span>
                <span class="text-gray-500">No subscription limits</span>
              </div>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <button
              @click="saveAssistant"
              :disabled="submitting || (isCreating && !isAdmin && !subscriptionInfo?.hasSubscription)"
              class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg flex items-center"
            >
              <svg v-if="submitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>
                {{ 
                  submitting ? (isCreating ? 'Creating...' : 'Updating...') : 
                  (isCreating && !isAdmin && !subscriptionInfo?.hasSubscription) ? 'Active Subscription Required' :
                  (isCreating ? 'Create Assistant' : 'Update Assistant') 
                }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Loading assistant...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <div class="text-red-500 mb-4">
          <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        </div>
        <p class="text-gray-600">{{ error }}</p>
        <button @click="loadAssistant" class="mt-4 text-green-600 hover:text-green-700">
          Try again
        </button>
      </div>

      <!-- Success Message -->
      <div v-if="success" class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-green-800">{{ success }}</p>
          </div>
        </div>
      </div>

      <!-- Form -->
      <div v-else class="space-y-8">
        <!-- Company Information -->
        <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Company Information</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
              <input
                v-model="form.metadata.company_name"
                type="text"
                placeholder="Your Company Name"
                data-field="company_name"
                :class="[
                  'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                  fieldErrors.company_name 
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                    : 'border-gray-300 focus:border-green-500 bg-white'
                ]"
              />
              <p v-if="fieldErrors.company_name" class="text-xs text-red-600 mt-1">{{ fieldErrors.company_name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Industry *</label>
              <input
                v-model="form.metadata.industry"
                type="text"
                placeholder="e.g., Technology, Healthcare, Finance"
                :class="[
                  'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                  fieldErrors.industry 
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                    : 'border-gray-300 focus:border-green-500 bg-white'
                ]"
              />
              <p v-if="fieldErrors.industry" class="text-xs text-red-600 mt-1">{{ fieldErrors.industry }}</p>
            </div>
          </div>
          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Country *</label>
            <select
              v-model="form.metadata.country"
              required
              :class="[
                'w-full px-3 py-2 border rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500',
                fieldErrors.country 
                  ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                  : 'border-gray-300 focus:border-green-500 bg-white'
              ]"
            >
              <option value="United States">United States</option>
              <option value="Canada">Canada</option>
              <option value="Australia">Australia</option>
              <option value="United Kingdom">United Kingdom</option>
            </select>
            <p v-if="fieldErrors.country" class="text-xs text-red-600 mt-1">{{ fieldErrors.country }}</p>
            <p v-else class="text-xs text-gray-500 mt-1">Country for phone number search</p>
          </div>
          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Services/Products *</label>
            <textarea
              v-model="form.metadata.services_products"
              rows="3"
              placeholder="Describe your main services or products"
              :class="[
                'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                fieldErrors.services_products 
                  ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                  : 'border-gray-300 focus:border-green-500 bg-white'
              ]"
            ></textarea>
            <p v-if="fieldErrors.services_products" class="text-xs text-red-600 mt-1">{{ fieldErrors.services_products }}</p>
          </div>
          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">SMS Phone Number</label>
            <input
              v-model="form.metadata.sms_phone_number"
              type="tel"
              placeholder="+1234567890"
              :class="[
                'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                fieldErrors.sms_phone_number 
                  ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                  : 'border-gray-300 focus:border-green-500 bg-white'
              ]"
            />
            <p v-if="fieldErrors.sms_phone_number" class="text-xs text-red-600 mt-1">{{ fieldErrors.sms_phone_number }}</p>
            <p v-else class="text-xs text-gray-500 mt-1">Phone number to receive text messages</p>
          </div>
        </div>

        <!-- Agent Information -->
        <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Agent Information</h2>
          <div class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Agent Name *</label>
              <input
                v-model="form.name"
                type="text"
                required
                maxlength="40"
                placeholder="My Voice Assistant"
                data-field="name"
                :class="[
                  'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                  fieldErrors.name 
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                    : 'border-gray-300 focus:border-green-500 bg-white'
                ]"
              />
              <p v-if="fieldErrors.name" class="text-xs text-red-600 mt-1">{{ fieldErrors.name }}</p>
              <p v-else class="text-xs text-gray-500 mt-1">Maximum 40 characters</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Assistant Type *</label>
              <select
                v-model="form.type"
                required
                :class="[
                  'w-full px-3 py-2 border rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500',
                  'border-gray-300 focus:border-green-500 bg-white'
                ]"
              >
                <option value="demo">Demo (Uses templates from settings)</option>
                <option value="production">Production (Custom configuration)</option>
              </select>
              <p class="text-xs text-gray-500 mt-1">
                <strong>Demo:</strong> Uses pre-configured templates from system settings. Template variables will be automatically replaced with your company information.<br>
                <strong>Production:</strong> Custom configuration that you can fully customize for your specific needs.
              </p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Assistant Phone Number</label>
              
              <!-- Phone Number Purchase Section -->
              <div v-if="isCreating" class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                <div class="flex items-center justify-between mb-3">
                  <h4 class="text-sm font-medium text-blue-900">Purchase A Phone Number</h4>
                  <div class="flex items-center space-x-2">
                    <input
                      v-model="areaCode"
                      type="text"
                      placeholder="Area Code (e.g., 212)"
                      maxlength="3"
                      :disabled="!isAreaCodeSupported"
                      :class="[
                        'text-xs px-2 py-1 border rounded focus:outline-none focus:ring-1 focus:ring-blue-500',
                        isAreaCodeSupported ? 'border-gray-300' : 'border-gray-200 bg-gray-100'
                      ]"
                    />
                    <button
                      @click="loadAvailableNumbers"
                      :disabled="loadingNumbers || !form.metadata.country"
                      class="text-xs bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 disabled:opacity-50"
                    >
                      <span v-if="loadingNumbers">Loading...</span>
                      <span v-else>Get Available Numbers</span>
                    </button>
                  </div>
                </div>
                
                <!-- Country Info -->
                <div v-if="form.metadata.country" class="mb-3 p-2 bg-blue-100 rounded text-xs">
                  <span class="font-medium">Searching in:</span> {{ form.metadata.country }}
                  <span v-if="areaCode && isAreaCodeSupported" class="ml-2">
                    <span class="font-medium">Area Code:</span> {{ areaCode }}
                  </span>
                  <span v-else-if="areaCode && !isAreaCodeSupported" class="ml-2 text-yellow-700">
                    <span class="font-medium">⚠️ Area codes not supported for {{ form.metadata.country }}</span>
                  </span>
                </div>
                
                <!-- Country Warning -->
                <div v-else class="mb-3 p-2 bg-yellow-100 rounded text-xs text-yellow-800">
                  <span class="font-medium">⚠️ Please select a country above to search for available phone numbers.</span>
                </div>
                
                <!-- Area Code Support Info -->
                <div v-if="form.metadata.country && !isAreaCodeSupported" class="mb-3 p-2 bg-yellow-100 rounded text-xs text-yellow-800">
                  <span class="font-medium">ℹ️ Note:</span> Area codes are supported for United States, Canada, Australia, and United Kingdom phone numbers.
                </div>
                
                <!-- Search Reset Message -->
                <div v-if="form.metadata.country && availableNumbers.length === 0 && !loadingNumbers" class="mb-3 p-2 rounded text-xs" :class="searchReset ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800'">
                  <span v-if="searchReset" class="font-medium">🔄 Search Reset:</span>
                  <span v-else class="font-medium">ℹ️ Ready to search:</span>
                  Click "Get Available Numbers" to search for phone numbers in {{ form.metadata.country }}.
                </div>
                
                <!-- Available Numbers List -->
                <div v-if="availableNumbers.length > 0" class="space-y-2">
                  <p class="text-xs text-blue-700 mb-2">Select a phone number to purchase when creating assistant:</p>
                  <div class="space-y-2 max-h-32 overflow-y-auto">
                    <div
                      v-for="number in availableNumbers"
                      :key="number.phone_number"
                      class="flex items-center justify-between p-2 bg-white border border-blue-200 rounded"
                    >
                      <div class="flex items-center space-x-2">
                        <input
                          type="radio"
                          :id="number.phone_number"
                          :value="number.phone_number"
                          v-model="selectedPhoneNumber"
                          name="phoneNumber"
                          class="text-blue-600 focus:ring-blue-500"
                        />
                        <label :for="number.phone_number" class="text-sm font-medium cursor-pointer">
                          {{ number.phone_number }}
                          <span v-if="number.locality" class="text-xs text-gray-500 ml-1">
                            ({{ number.locality }}, {{ number.region }})
                          </span>
                        </label>
                      </div>
                      <span v-if="selectedPhoneNumber === number.phone_number" class="text-xs text-green-600 font-medium">
                        Selected
                      </span>
                    </div>
                  </div>
                </div>
                
                <!-- Manual Phone Number Input -->
                <div class="mt-3">
                  <p class="text-xs text-blue-700 mb-2">Or enter a phone number manually:</p>
                  <input
                    v-model="form.metadata.assistant_phone_number"
                    type="tel"
                    placeholder="+1234567890"
                    :class="[
                      'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                      fieldErrors.assistant_phone_number 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                        : 'border-gray-300 focus:border-green-500 bg-white'
                    ]"
                  />
                </div>
              </div>
              
              <!-- Edit Mode - Simple Input -->
              <div v-else>
                <input
                  v-model="form.metadata.assistant_phone_number"
                  type="tel"
                  placeholder="+1234567890"
                  :class="[
                    'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                    fieldErrors.assistant_phone_number 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                      : 'border-gray-300 focus:border-green-500 bg-white'
                  ]"
                />
              </div>
              
              <p v-if="fieldErrors.assistant_phone_number" class="text-xs text-red-600 mt-1">{{ fieldErrors.assistant_phone_number }}</p>
              <p v-else class="text-xs text-gray-500 mt-1">Optional phone number for this specific assistant</p>
            </div>
          </div>
        </div>

        <!-- AI Configuration Tabs -->
        <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">AI Configuration</h2>
          
          <!-- Tab Navigation -->
          <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
              <button
                @click="activeTab = 'model'"
                :class="[
                  'py-2 px-1 border-b-2 font-medium text-sm',
                  activeTab === 'model'
                    ? 'border-green-500 text-green-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
              >
                Model
              </button>
              <button
                @click="activeTab = 'voice'"
                :class="[
                  'py-2 px-1 border-b-2 font-medium text-sm',
                  activeTab === 'voice'
                    ? 'border-green-500 text-green-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
              >
                Voice
              </button>
              <button
                @click="activeTab = 'transcriber'"
                :class="[
                  'py-2 px-1 border-b-2 font-medium text-sm',
                  activeTab === 'transcriber'
                    ? 'border-green-500 text-green-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
              >
                Transcriber
              </button>
            </nav>
          </div>

          <!-- Tab Content -->
          <div v-if="activeTab === 'model'">
            <ModelConfiguration 
              :model="form.model" 
              :actualSulusData="actualSulusData"
              :templatedData="templatedData"
              :formType="form.type"
              :field-errors="{
                provider: fieldErrors.model_provider,
                model: fieldErrors.model_model,
                temperature: fieldErrors.model_temperature,
                maxTokens: fieldErrors.model_maxTokens,
                topP: fieldErrors.model_topP,
                frequencyPenalty: fieldErrors.model_frequencyPenalty,
                presencePenalty: fieldErrors.model_presencePenalty
              }"
              @update:model="updateModel"
              @replaceWithTemplate="replaceWithTemplate"
              @replaceWithActual="replaceWithActual"
            />
          </div>
          
          <div v-if="activeTab === 'voice'">
            <VoiceConfiguration 
              :voice="form.voice" 
              :field-errors="{
                provider: fieldErrors.voice_provider,
                voiceId: fieldErrors.voice_voiceId,
                model: fieldErrors.voice_model,
                speed: fieldErrors.voice_speed
              }"
              @update:voice="updateVoice"
            />
          </div>
          
          <div v-if="activeTab === 'transcriber'">
            <TranscriberConfiguration 
              :transcriber="form.transcriber" 
              :field-errors="{
                provider: fieldErrors.transcriber_provider,
                model: fieldErrors.transcriber_model,
                language: fieldErrors.transcriber_language
              }"
              @update:transcriber="updateTranscriber"
            />
          </div>
        </div>

        <!-- Analysis Configuration -->
        <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Analysis Configuration</h2>
          
          <AnalysisConfiguration 
            :analysis-plan="form.analysisPlan" 
            :artifact-plan="form.artifactPlan"
            @update:analysis-plan="updateAnalysisPlan"
            @update:artifact-plan="updateArtifactPlan"
          />
        </div>

        <!-- Messages Configuration -->
        <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Messages Configuration</h2>
          
          <!-- Template Variables Reminder -->
          <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <h3 class="text-sm font-medium text-green-900 mb-2">💡 Template Variables Available</h3>
            <p class="text-xs text-green-700 mb-3">
              You can use these variables in your prompts and messages. They will be automatically replaced with your company information:
            </p>
            <div class="flex flex-wrap gap-2">
              <code class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">{{ '{' }}{{ '{' }}company_name{{ '}' }}{{ '}' }}</code>
              <code class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">{{ '{' }}{{ '{' }}company_industry{{ '}' }}{{ '}' }}</code>
              <code class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">{{ '{' }}{{ '{' }}company_services{{ '}' }}{{ '}' }}</code>
            </div>
          </div>
          
          <div class="space-y-6">
            <!-- First Message Mode -->
            <div class="mt-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">First Message Mode *</label>
              <select
                v-model="form.firstMessageMode"
                class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
              >
                <option value="assistant-speaks-first">Assistant Speaks First</option>
                <option value="assistant-speaks-first-with-model-generated-message">Assistant Speaks First with Model Generated Message</option>
                <option value="assistant-waits-for-user">Assistant Waits for User</option>
              </select>
              <p class="text-xs text-gray-500 mt-1">
                Controls when and how the assistant speaks first. 
                <strong>Assistant Speaks First:</strong> Uses the first message you provide. 
                <strong>Model Generated:</strong> Uses AI to generate the first message based on conversation state. 
                <strong>Waits for User:</strong> Assistant waits for user to speak first.
              </p>
            </div>

            <!-- First Message -->
            <div class="mt-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                First Message
                <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <textarea
                  v-model="form.firstMessage"
                  rows="4"
                  :class="[
                    'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 resize-none',
                    fieldErrors.firstMessage 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                      : 'border-gray-300 focus:border-green-500 bg-white'
                  ]"
                  :placeholder="getFirstMessagePlaceholder()"
                ></textarea>
                
                <!-- Template/Actual Data Toggle for Demo Assistants -->
                <div v-if="form.type === 'demo'" class="absolute top-2 right-2 flex space-x-1">
                  <button
                    type="button"
                    @click="replaceWithTemplate('firstMessage')"
                    class="p-1 text-blue-600 hover:text-blue-800 bg-blue-100 hover:bg-blue-200 rounded"
                    title="Replace with templated data"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                  </button>
                  <button
                    type="button"
                    @click="replaceWithActual('firstMessage')"
                    class="p-1 text-green-600 hover:text-green-800 bg-green-100 hover:bg-green-200 rounded"
                    title="Replace with actual Sulus data"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                  </button>
                </div>
              </div>
              <p v-if="fieldErrors.firstMessage" class="text-xs text-red-600 mt-1">
                {{ fieldErrors.firstMessage }}
              </p>
              <p v-else class="text-xs text-gray-500 mt-1">
                <span v-if="form.firstMessageMode === 'assistant-speaks-first'">
                  The first message the assistant will say when a call starts. You can use template variables: <code class="bg-gray-100 px-1 rounded">{{ '{' }}{{ '{' }}company_name{{ '}' }}{{ '}' }}</code>, <code class="bg-gray-100 px-1 rounded">{{ '{' }}{{ '{' }}company_industry{{ '}' }}{{ '}' }}</code>, <code class="bg-gray-100 px-1 rounded">{{ '{' }}{{ '{' }}company_services{{ '}' }}{{ '}' }}</code>
                </span>
                <span v-else-if="form.firstMessageMode === 'assistant-speaks-first-with-model-generated-message'">
                  This message will be used as a fallback or example. The AI will generate the actual first message based on conversation state.
                </span>
                <span v-else>
                  This message will be used when the assistant needs to respond after the user speaks first.
                </span>
              </p>
            </div>

            <!-- First Message Interruptions -->
            <div v-if="form.firstMessageMode === 'assistant-speaks-first' || form.firstMessageMode === 'assistant-speaks-first-with-model-generated-message'" class="mt-4">
              <label class="flex items-center">
                <input
                  v-model="form.firstMessageInterruptionsEnabled"
                  type="checkbox"
                  class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-700">Enable First Message Interruptions</span>
              </label>
              <p class="text-xs text-gray-500 mt-1">Allow users to interrupt the assistant while it's speaking the first message</p>
            </div>

            <!-- End Call Message -->
            <div class="mt-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                End Call Message
                <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <textarea
                  v-model="form.endCallMessage"
                  rows="4"
                  :class="[
                    'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 resize-none',
                    fieldErrors.endCallMessage 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                      : 'border-gray-300 focus:border-green-500 bg-white'
                  ]"
                  placeholder="Enter the message the assistant will say when ending a call..."
                ></textarea>
                
                <!-- Template/Actual Data Toggle for Demo Assistants -->
                <div v-if="form.type === 'demo'" class="absolute top-2 right-2 flex space-x-1">
                  <button
                    type="button"
                    @click="replaceWithTemplate('endCallMessage')"
                    class="p-1 text-blue-600 hover:text-blue-800 bg-blue-100 hover:bg-blue-200 rounded"
                    title="Replace with templated data"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                  </button>
                  <button
                    type="button"
                    @click="replaceWithActual('endCallMessage')"
                    class="p-1 text-green-600 hover:text-green-800 bg-green-100 hover:bg-green-200 rounded"
                    title="Replace with actual Sulus data"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                  </button>
                </div>
              </div>
              <p v-if="fieldErrors.endCallMessage" class="text-xs text-red-600 mt-1">
                {{ fieldErrors.endCallMessage }}
              </p>
              <p v-else class="text-xs text-gray-500 mt-1">
                The message the assistant will say when ending a call. You can use template variables: <code class="bg-gray-100 px-1 rounded">{{ '{' }}{{ '{' }}company_name{{ '}' }}{{ '}' }}</code>, <code class="bg-gray-100 px-1 rounded">{{ '{' }}{{ '{' }}company_industry{{ '}' }}{{ '}' }}</code>, <code class="bg-gray-100 px-1 rounded">{{ '{' }}{{ '{' }}company_services{{ '}' }}{{ '}' }}</code>
              </p>
            </div>
            
            <!-- Message Previews -->
            <div v-if="form.metadata.company_name || form.metadata.industry || form.metadata.services_products" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">First Message Preview</label>
                <div class="bg-gray-50 border border-gray-200 rounded-md p-4 text-sm text-gray-700">
                  <pre class="whitespace-pre-wrap">{{ processedFirstMessage }}</pre>
                </div>
                <p class="text-xs text-gray-500 mt-1">This is how your first message will appear with the company information filled in.</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">End Call Message Preview</label>
                <div class="bg-gray-50 border border-gray-200 rounded-md p-4 text-sm text-gray-700">
                  <pre class="whitespace-pre-wrap">{{ processedEndCallMessage }}</pre>
                </div>
                <p class="text-xs text-gray-500 mt-1">This is how your end call message will appear with the company information filled in.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Webhook Configuration -->
        <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Webhook Configuration</h2>
          
          <!-- Webhook Configuration -->
          <div class="mt-6">
            <div class="flex items-center justify-between mb-2">
              <label class="block text-sm font-medium text-gray-700">Server URL (Webhook)</label>
              <div class="flex items-center space-x-2">
                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                  End-of-Call Report Enabled
                </span>
              </div>
            </div>
            <input
              v-model="form.metadata.webhook_url"
              type="url"
              placeholder="https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents"
              :class="[
                'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
                fieldErrors.webhook_url 
                  ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
                  : 'border-gray-300 focus:border-green-500 bg-white'
              ]"
            />
            <p v-if="fieldErrors.webhook_url" class="text-xs text-red-600 mt-1">{{ fieldErrors.webhook_url }}</p>
            <p v-else class="text-xs text-gray-500 mt-1">
              Server URL to receive webhook events. When provided, end-of-call reports will be automatically enabled and sent to this URL.
            </p>
          </div>
        </div>



        <!-- User Assignment (Admin Only) -->
        <div v-if="isAdmin" class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">User Assignment</h2>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Assign to User *</label>
            <div v-if="loadingUsers" class="flex items-center space-x-2">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-green-600"></div>
              <span class="text-sm text-gray-500">Loading users...</span>
            </div>
            <select
              v-else
              v-model="form.user_id"
              required
              :class="[
                'w-full px-3 py-2 border rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500',
                'border-gray-300 focus:border-green-500 bg-white'
              ]"
            >
              <option value="">Select a user to assign this assistant to</option>
              <option
                v-for="user in users"
                :key="user.id"
                :value="user.id"
              >
                {{ user.name }} ({{ user.email }}) - {{ user.role }}
              </option>
            </select>
            <p v-if="fieldErrors.user_assignment" class="text-xs text-red-600 mt-1">{{ fieldErrors.user_assignment }}</p>
            <p v-else class="text-xs text-gray-500 mt-1">Choose which user will own this assistant</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Footer -->
    <SimpleFooter />
  </div>
</template>

<script>
import { ref, onMounted, computed, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { showError, showSuccess } from '../../utils/sweetalert.js'
import SimpleFooter from '../shared/SimpleFooter.vue'
import ModelConfiguration from './ModelConfiguration.vue'
import VoiceConfiguration from './VoiceConfiguration.vue'
import TranscriberConfiguration from './TranscriberConfiguration.vue'
import AnalysisConfiguration from './AnalysisConfiguration.vue'

export default {
  name: 'AssistantForm',
  components: {
    SimpleFooter,
    ModelConfiguration,
    VoiceConfiguration,
    TranscriberConfiguration,
    AnalysisConfiguration
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const loading = ref(false)
    const submitting = ref(false)
    const error = ref(null)
    const success = ref(null)
    const activeTab = ref('model')
    
    // User assignment for admin
    const users = ref([])
    const loadingUsers = ref(false)
    const currentUser = ref(JSON.parse(localStorage.getItem('user') || '{}'))
    const isAdmin = computed(() => currentUser.value.role === 'admin' || currentUser.value.role === 'reseller_admin')
    const isSuperAdmin = computed(() => currentUser.value.role === 'admin' || currentUser.value.role === 'content_admin')
    const isResellerAdmin = computed(() => currentUser.value.role === 'reseller_admin')
    
    // Subscription info for display
    const subscriptionInfo = ref(null)
    
    // Template data for demo assistants
    const templates = ref({
      system_prompt: '',
      first_message: '',
      end_call_message: ''
    })
    
    // Phone number purchase data
    const availableNumbers = ref([])
    const loadingNumbers = ref(false)
    const purchasingNumber = ref(false)
    const areaCode = ref('')
    const selectedPhoneNumber = ref('')
    const searchReset = ref(false) // Track when search results have been reset
    
    // Check if we're creating a new assistant or editing an existing one
    const isCreating = computed(() => {
      return route.params.id === 'create' || !route.params.id
    })
    
    // Store actual Sulus data separately from form data
    const actualSulusData = ref({
      systemPrompt: '',
      firstMessage: '',
      endCallMessage: ''
    })
    
    // Store templated data separately
    const templatedData = ref({
      systemPrompt: '',
      firstMessage: '',
      endCallMessage: ''
    })
    
    const form = ref({
      name: '',
      model: {
        provider: 'openai',
        model: 'gpt-4o',
        temperature: 1,
        maxTokens: 1000,
        topP: 1,
        frequencyPenalty: 0,
        presencePenalty: 0,
        stop: [],
        messages: [
          {
            role: 'system',
            content: `## COMPANY PROFILE - 
\`\`\`
COMPANY_NAME: {{company_name}}
COMPANY_INDUSTRY: {{company_industry}}
COMPANY_SERVICES: {{company_name}} provides {{company_services}}
\`\`\`

## Core Identity & Mission
You are a professional customer service representative for {{company_name}}, a leading {{company_industry}} company specializing in {{company_name}} provides {{company_services}}. 

You embody the highest standards of customer service that {{company_name}} would provide to their valued resellers.`,
            contentType: 'text'
          }
        ],
        tools: []
      },
      voice: {
        provider: 'vapi',
        voiceId: 'Spencer',
        speed: 1,
        model: ''
      },
      transcriber: {
        provider: 'deepgram',
        language: 'en',
        model: 'nova-2',
        confidenceThreshold: 0.4,
        formatTurns: true,
        endOfTurnConfidenceThreshold: 0.7,
        minEndOfTurnSilenceWhenConfident: 160,
        wordFinalizationMaxWaitTime: 160,
        maxTurnSilence: 400,
        realtimeUrl: '',
        wordBoost: [],
        endUtteranceSilenceThreshold: 1.1,
        disablePartialTranscripts: false,
        fallbackPlan: null
      },
      firstMessage: 'Thank you for calling {{company_name}}, this is Sarah. How may I assist you today?',
      firstMessageMode: 'assistant-speaks-first',
      firstMessageInterruptionsEnabled: false,
      endCallMessage: 'Thank you for calling {{company_name}}. Have a wonderful day!',
      metadata: {
        company_name: '',
        industry: '',
        country: 'United States', // Default to United States
        services_products: '',
        sms_phone_number: '',
        assistant_phone_number: '',
        webhook_url: 'https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents'
      },
      analysisPlan: {
        summary: false,
        summaryPrompt: 'You are an expert note-taker. You will be given a transcript of a call. Summarize the call in 2-3 sentences, if applicable.',
        summaryTimeout: 10,
        minMessages: 2,
        successEvaluation: false,
        successEvaluationPrompt: 'You are an expert call evaluator. You will be given a transcript of a call and the system prompt of the AI participant. Determine if the call was successful based on the objectives inferred from the system prompt.',
        successEvaluationTimeout: 10,
        checklist: [
          { checked: false, text: 'Customer inquiry was addressed' },
          { checked: false, text: 'Appropriate information was provided' },
          { checked: false, text: 'Call ended professionally' }
        ],
        structuredData: false,
        structuredDataPrompt: 'You will be given a transcript of a call and the system prompt of the AI participant. Extract structured data from the call based on the following schema...',
        structuredDataTimeout: 10,
        dataSchema: [
          { 
            name: 'field_1', 
            type: 'string', 
            required: false, 
            isEnum: false, 
            description: '',
            showDescription: false
          }
        ],
        sentiment: false,
        topics: false,
        entities: false,
        actionItems: false,
        followUps: false
      },
      artifactPlan: {
        recording: false,
        transcript: false,
        summary: false,
        analysis: false
      },
      user_id: null, // Will be set based on isAdmin computed value
      type: 'demo' // Default to demo
    })

    // Field-specific error states
    const fieldErrors = ref({
      name: '',
      systemPrompt: '',
      firstMessage: '',
      firstMessageMode: '',
      firstMessageInterruptionsEnabled: '',
      endCallMessage: '',
      company_name: '',
      industry: '',
      services_products: '',
      sms_phone_number: '',
      assistant_phone_number: '',
      webhook_url: '',
      user_assignment: '', // Added for admin user assignment
      country: '', // Added for country field
      transcriber_provider: '',
      transcriber_model: '',
      transcriber_language: '',
      voice_provider: '',
      voice_voiceId: '',
      voice_model: '',
      voice_speed: '',
      model_provider: '',
      model_model: '',
      model_temperature: '',
      model_maxTokens: '',
      model_topP: '',
      model_frequencyPenalty: '',
      model_presencePenalty: ''
    })

    // Computed property to process system prompt with company information
    const processedSystemPrompt = computed(() => {
      let prompt = form.value.model.messages[0].content || ''
      
      // Replace template variables with actual company data
      prompt = prompt.replace(/\{\{company_name\}\}/g, form.value.metadata.company_name || '{{company_name}}')
      prompt = prompt.replace(/\{\{company_industry\}\}/g, form.value.metadata.industry || '{{company_industry}}')
      prompt = prompt.replace(/\{\{company_services\}\}/g, form.value.metadata.services_products || '{{company_services}}')
      
      return prompt
    })

    // Computed property to process first message with company information
    const processedFirstMessage = computed(() => {
      let message = form.value.firstMessage || ''
      
      // Replace template variables with actual company data
      message = message.replace(/\{\{company_name\}\}/g, form.value.metadata.company_name || '{{company_name}}')
      message = message.replace(/\{\{company_industry\}\}/g, form.value.metadata.industry || '{{company_industry}}')
      message = message.replace(/\{\{company_services\}\}/g, form.value.metadata.services_products || '{{company_services}}')
      
      return message
    })

    // Computed property to process end call message with company information
    const processedEndCallMessage = computed(() => {
      let message = form.value.endCallMessage || ''
      
      // Replace template variables with actual company data
      message = message.replace(/\{\{company_name\}\}/g, form.value.metadata.company_name || '{{company_name}}')
      message = message.replace(/\{\{company_industry\}\}/g, form.value.metadata.industry || '{{company_industry}}')
      message = message.replace(/\{\{company_services\}\}/g, form.value.metadata.services_products || '{{company_services}}')
      
      return message
    })

    // Function to replace with templated data
    const replaceWithTemplate = (field) => {
      if (field === 'systemPrompt') {
        form.value.model.messages[0].content = templatedData.value.systemPrompt
      } else if (field === 'firstMessage') {
        form.value.firstMessage = templatedData.value.firstMessage
      } else if (field === 'endCallMessage') {
        form.value.endCallMessage = templatedData.value.endCallMessage
      }
    }

    // Function to replace with actual Sulus data
    const replaceWithActual = (field) => {
      if (field === 'systemPrompt') {
        form.value.model.messages[0].content = actualSulusData.value.systemPrompt
      } else if (field === 'firstMessage') {
        form.value.firstMessage = actualSulusData.value.firstMessage
      } else if (field === 'endCallMessage') {
        form.value.endCallMessage = actualSulusData.value.endCallMessage
      }
    }

    // Function to update templated data when company info changes
    const updateTemplatedData = () => {
      if (templates.value.system_prompt) {
        templatedData.value.systemPrompt = templates.value.system_prompt
          .replace(/\{\{company_name\}\}/g, form.value.metadata.company_name || '{{company_name}}')
          .replace(/\{\{company_industry\}\}/g, form.value.metadata.industry || '{{company_industry}}')
          .replace(/\{\{company_services\}\}/g, form.value.metadata.services_products || '{{company_services}}')
      }
      
      if (templates.value.first_message) {
        templatedData.value.firstMessage = templates.value.first_message
          .replace(/\{\{company_name\}\}/g, form.value.metadata.company_name || '{{company_name}}')
          .replace(/\{\{company_industry\}\}/g, form.value.metadata.industry || '{{company_industry}}')
          .replace(/\{\{company_services\}\}/g, form.value.metadata.services_products || '{{company_services}}')
      }
      
      if (templates.value.end_call_message) {
        templatedData.value.endCallMessage = templates.value.end_call_message
          .replace(/\{\{company_name\}\}/g, form.value.metadata.company_name || '{{company_name}}')
          .replace(/\{\{company_industry\}\}/g, form.value.metadata.industry || '{{company_industry}}')
          .replace(/\{\{company_services\}\}/g, form.value.metadata.services_products || '{{company_services}}')
      }
    }

    // Computed property to check if area code is supported for the selected country
    const isAreaCodeSupported = computed(() => {
      return ['United States', 'Canada', 'Australia', 'United Kingdom'].includes(form.value.metadata.country)
    })

    // Watch for type changes to handle template loading
    watch(() => form.value.type, (newType, oldType) => {
      // Only proceed if type actually changed and we're creating a new assistant
      if (newType === 'demo' && newType !== oldType && isCreating.value && templates.value.system_prompt) {
        // Auto-load templates for demo assistants
        loadDefaultTemplate()
        loadDefaultFirstMessage()
        loadDefaultEndCallMessage()
      }
    })

    // Watch for company information changes to update templated data and auto-populate agent name
    watch([() => form.value.metadata.company_name, () => form.value.metadata.industry, () => form.value.metadata.services_products], ([newCompanyName, newIndustry, newServices], [oldCompanyName, oldIndustry, oldServices]) => {
      // Only update if values actually changed
      if (newCompanyName !== oldCompanyName || newIndustry !== oldIndustry || newServices !== oldServices) {
        // Update templated data first
        updateTemplatedData()
        
        // Auto-populate agent name based on company name changes (only if company name changed)
        if (newCompanyName !== oldCompanyName) {
          if (newCompanyName && newCompanyName.trim()) {
            // Auto-populate agent name based on company name for both create and edit
            form.value.name = `${newCompanyName.trim()} Assistant`
          } else if (!newCompanyName || !newCompanyName.trim()) {
            // Clear agent name if company name is empty
            form.value.name = ''
          }
        }
      }
    }, { deep: true })

    // Watch for country changes to reset available numbers
    watch(() => form.value.metadata.country, (newCountry, oldCountry) => {
      if (newCountry !== oldCountry) {
        // Reset available numbers when country changes
        availableNumbers.value = []
        selectedPhoneNumber.value = ''
        areaCode.value = ''
        searchReset.value = true // Set flag to true

      }
    })

    const loadAssistant = async () => {
      // Check if user is logged in
      const token = localStorage.getItem('token')
      if (!token) {
        error.value = 'Please log in to access this assistant.'
        return
      }
      
      // If creating, don't load existing assistant data
      if (isCreating.value) {
        loading.value = false
        return
      }
      
      try {
        loading.value = true
        error.value = null
        const response = await axios.get(`/api/assistants/${route.params.id}`)
        const assistant = response.data.data
        
        // Store actual Sulus data separately
        actualSulusData.value.systemPrompt = assistant.vapi_data?.model?.messages?.[0]?.content || ''
        actualSulusData.value.firstMessage = assistant.vapi_data?.firstMessage || ''
        actualSulusData.value.endCallMessage = assistant.vapi_data?.endCallMessage || ''
        
        // Map the assistant data to our form structure - always use actual Sulus data
        form.value.name = assistant.name || ''
        form.value.firstMessage = actualSulusData.value.firstMessage
        form.value.endCallMessage = actualSulusData.value.endCallMessage
        
        // Map first message mode and interruptions
        form.value.firstMessageMode = assistant.first_message_mode || 'assistant-speaks-first'
        form.value.firstMessageInterruptionsEnabled = assistant.first_message_interruptions_enabled || false
        
        // Map model configuration
        if (assistant.vapi_data?.model) {
          form.value.model.provider = assistant.vapi_data.model.provider || 'openai'
          form.value.model.model = assistant.vapi_data.model.model || 'gpt-4o'
          form.value.model.temperature = assistant.vapi_data.model.temperature || 1
          form.value.model.maxTokens = assistant.vapi_data.model.maxTokens || 1000
          form.value.model.topP = assistant.vapi_data.model.topP || 1
          form.value.model.frequencyPenalty = assistant.vapi_data.model.frequencyPenalty || 0
          form.value.model.presencePenalty = assistant.vapi_data.model.presencePenalty || 0
          form.value.model.stop = assistant.vapi_data.model.stop || []
          form.value.model.tools = assistant.vapi_data.model.tools || []
          form.value.model.apiVersion = assistant.vapi_data.model.apiVersion || ''
          
          // Handle messages array properly
          if (assistant.vapi_data.model.messages && assistant.vapi_data.model.messages.length > 0) {
            // Update the entire messages array structure
            form.value.model.messages = assistant.vapi_data.model.messages.map(msg => ({
              role: msg.role || 'system',
              content: msg.content || '',
              contentType: msg.contentType || 'text',
              imageUrl: msg.imageUrl || ''
            }))
          }
        }
        
        console.log('AssistantForm: Final form.model after mapping:', form.value.model)
        
                    // Map voice configuration
            if (assistant.vapi_data?.voice) {
              form.value.voice.provider = assistant.vapi_data.voice.provider || 'vapi'
              form.value.voice.voiceId = assistant.vapi_data.voice.voiceId || 'spencer'
              form.value.voice.speed = assistant.vapi_data.voice.speed || 1
              form.value.voice.model = assistant.vapi_data.voice.model || ''
            }
            
            // Map transcriber configuration
            console.log('AssistantForm: VAPI transcriber data:', assistant.vapi_data?.transcriber)
            if (assistant.vapi_data?.transcriber) {
              form.value.transcriber.provider = assistant.vapi_data.transcriber.provider || 'deepgram'
              form.value.transcriber.language = assistant.vapi_data.transcriber.language || 'en'
              form.value.transcriber.model = assistant.vapi_data.transcriber.model || 'nova-2'
              form.value.transcriber.confidenceThreshold = assistant.vapi_data.transcriber.confidenceThreshold || 0.4
              form.value.transcriber.formatTurns = assistant.vapi_data.transcriber.formatTurns !== undefined ? assistant.vapi_data.transcriber.formatTurns : true
              form.value.transcriber.endOfTurnConfidenceThreshold = assistant.vapi_data.transcriber.endOfTurnConfidenceThreshold || 0.7
              form.value.transcriber.minEndOfTurnSilenceWhenConfident = assistant.vapi_data.transcriber.minEndOfTurnSilenceWhenConfident || 160
              form.value.transcriber.wordFinalizationMaxWaitTime = assistant.vapi_data.transcriber.wordFinalizationMaxWaitTime || 160
              form.value.transcriber.maxTurnSilence = assistant.vapi_data.transcriber.maxTurnSilence || 400
              form.value.transcriber.realtimeUrl = assistant.vapi_data.transcriber.realtimeUrl || ''
              form.value.transcriber.wordBoost = assistant.vapi_data.transcriber.wordBoost || []
              form.value.transcriber.endUtteranceSilenceThreshold = assistant.vapi_data.transcriber.endUtteranceSilenceThreshold || 1.1
              form.value.transcriber.disablePartialTranscripts = assistant.vapi_data.transcriber.disablePartialTranscripts || false
              form.value.transcriber.fallbackPlan = assistant.vapi_data.transcriber.fallbackPlan || null
            } else {
              // Use default Deepgram configuration when no transcriber exists
              form.value.transcriber = {
                provider: 'deepgram',
                language: 'en',
                model: 'nova-2',
                confidenceThreshold: 0.4,
                formatTurns: true,
                endOfTurnConfidenceThreshold: 0.7,
                minEndOfTurnSilenceWhenConfident: 160,
                wordFinalizationMaxWaitTime: 160,
                maxTurnSilence: 400,
                realtimeUrl: '',
                wordBoost: [],
                endUtteranceSilenceThreshold: 1.1,
                disablePartialTranscripts: false,
                fallbackPlan: null
              }
            }
            console.log('AssistantForm: Final form.transcriber after mapping:', form.value.transcriber)
            
            // Map analysis plan (check both direct field and metadata location)
            const analysisPlan = assistant.vapi_data?.analysisPlan || assistant.vapi_data?.metadata?.analysisPlan
            if (analysisPlan) {
              form.value.analysisPlan.summary = analysisPlan.summary || false
              form.value.analysisPlan.summaryPrompt = analysisPlan.summaryPrompt || 'You are an expert note-taker. You will be given a transcript of a call. Summarize the call in 2-3 sentences, if applicable.'
              form.value.analysisPlan.summaryTimeout = analysisPlan.summaryTimeout || 10
              form.value.analysisPlan.minMessages = analysisPlan.minMessages || 2
              form.value.analysisPlan.successEvaluation = analysisPlan.successEvaluation || false
              form.value.analysisPlan.successEvaluationPrompt = analysisPlan.successEvaluationPrompt || 'You are an expert call evaluator. You will be given a transcript of a call and the system prompt of the AI participant. Determine if the call was successful based on the objectives inferred from the system prompt.'
              form.value.analysisPlan.successEvaluationTimeout = analysisPlan.successEvaluationTimeout || 10
              form.value.analysisPlan.checklist = analysisPlan.checklist || [
                { checked: false, text: 'Customer inquiry was addressed' },
                { checked: false, text: 'Appropriate information was provided' },
                { checked: false, text: 'Call ended professionally' }
              ]
              form.value.analysisPlan.structuredData = analysisPlan.structuredData || false
              form.value.analysisPlan.structuredDataPrompt = analysisPlan.structuredDataPrompt || 'You will be given a transcript of a call and the system prompt of the AI participant. Extract structured data from the call based on the following schema...'
              form.value.analysisPlan.structuredDataTimeout = analysisPlan.structuredDataTimeout || 10
              form.value.analysisPlan.dataSchema = analysisPlan.dataSchema || [
                { 
                  name: 'field_1', 
                  type: 'string', 
                  required: false, 
                  isEnum: false, 
                  description: '',
                  showDescription: false
                }
              ]
              form.value.analysisPlan.sentiment = analysisPlan.sentiment || false
              form.value.analysisPlan.topics = analysisPlan.topics || false
              form.value.analysisPlan.entities = analysisPlan.entities || false
              form.value.analysisPlan.actionItems = analysisPlan.actionItems || false
              form.value.analysisPlan.followUps = analysisPlan.followUps || false
            }
            
            // Map artifact plan (check both direct field and metadata location)
            const artifactPlan = assistant.vapi_data?.artifactPlan || assistant.vapi_data?.metadata?.artifactPlan
            if (artifactPlan) {
              form.value.artifactPlan.recording = artifactPlan.recording || false
              form.value.artifactPlan.transcript = artifactPlan.transcript || false
              form.value.artifactPlan.summary = artifactPlan.summary || false
              form.value.artifactPlan.analysis = artifactPlan.analysis || false
            }
        
        // Map metadata
        if (assistant.vapi_data?.metadata) {
          form.value.metadata.company_name = assistant.vapi_data.metadata.company_name || ''
          form.value.metadata.industry = assistant.vapi_data.metadata.industry || ''
          form.value.metadata.country = assistant.vapi_data.metadata.country || 'United States'
          form.value.metadata.services_products = assistant.vapi_data.metadata.services_products || ''
          form.value.metadata.sms_phone_number = assistant.vapi_data.metadata.sms_phone_number || ''
          form.value.metadata.assistant_phone_number = assistant.vapi_data.metadata.assistant_phone_number || ''
          form.value.metadata.webhook_url = assistant.vapi_data.metadata.webhook_url || ''
        }
        
        // Map phone_number from database
        if (assistant.phone_number) {
          form.value.metadata.assistant_phone_number = assistant.phone_number
        }
        
        // Map webhook_url from database
        if (assistant.webhook_url) {
          form.value.metadata.webhook_url = assistant.webhook_url
        }
        
        // Map user_id for admin assignment
        if (isAdmin.value) {
          form.value.user_id = assistant.user_id || null
        }
        
        // Map type - check both database and Sulus metadata
        form.value.type = assistant.type || assistant.vapi_data?.metadata?.type || 'demo'
        
        // Load templates and update templated data
        await loadTemplates()
        updateTemplatedData()
        
      } catch (err) {
        
        if (err.response) {
          const { status, data } = err.response
          
          switch (status) {
            case 401:
              error.value = 'Please log in to access this assistant.'
              break
              
            case 403:
              error.value = 'You do not have permission to access this assistant.'
              break
              
            case 404:
              error.value = 'Assistant not found. Please check the URL and try again.'
              break
              
            case 500:
              error.value = 'Server error occurred. Please try again later.'
              break
              
            default:
              if (data.message) {
                error.value = data.message
              } else {
                error.value = `Failed to load assistant (Status: ${status}). Please try again.`
              }
          }
        } else if (err.request) {
          // Network error
          error.value = 'Network error. Please check your internet connection and try again.'
        } else {
          // Other errors
          error.value = 'Failed to load assistant. Please check your connection and try again.'
        }
      } finally {
        loading.value = false
      }
    }

    const loadUsers = async (search = '') => {
      if (!isAdmin.value) return
      
      try {
        loadingUsers.value = true
        const params = new URLSearchParams()
        if (search) {
          params.append('search', search)
        }
        params.append('per_page', '100') // Get more users for assignment dropdown
        
        const response = await axios.get(`/api/admin/users/for-assignment?${params.toString()}`)
        users.value = response.data.data || []
      } catch (error) {
        // Handle error silently
        console.error('Error loading users:', error)
      } finally {
        loadingUsers.value = false
      }
    }

    const loadSubscriptionInfo = async () => {
      if (!isCreating.value) return // Only load for creation
      if (isSuperAdmin.value) return // Don't load for super admin users
      
      try {
        const response = await axios.get('/api/subscriptions/usage')
        const usage = response.data.data
        
        if (usage && usage.package && usage.subscription) {
          // Check if subscription is actually active (not pending)
          const isActiveSubscription = usage.subscription.status === 'active'
          
          subscriptionInfo.value = {
            plan: usage.package.name || 'No Plan',
            used: usage.assistants.used || 0,
            limit: usage.assistants.limit || 0,
            remaining: usage.assistants.remaining || 0,
            hasSubscription: isActiveSubscription,
            subscriptionStatus: usage.subscription.status
          }
        } else {
          // No active subscription
          subscriptionInfo.value = {
            plan: 'No Plan',
            used: 0,
            limit: 0,
            remaining: 0,
            hasSubscription: false,
            subscriptionStatus: 'none'
          }
        }
      } catch (error) {
        // If 404, it means no active subscription
        if (error.response && error.response.status === 404) {
          subscriptionInfo.value = {
            plan: 'No Plan',
            used: 0,
            limit: 0,
            remaining: 0,
            hasSubscription: false,
            subscriptionStatus: 'none'
          }
        } else {
          // Set default values if API fails - assume no subscription
          subscriptionInfo.value = {
            plan: 'Unknown',
            used: 0,
            limit: 0,
            remaining: 0,
            hasSubscription: false,
            subscriptionStatus: 'unknown'
          }
        }
      }
    }

    const saveAssistant = async () => {
      try {
        
        error.value = null
        success.value = null
        
        // Clear all field errors
        Object.keys(fieldErrors.value).forEach(key => {
          fieldErrors.value[key] = ''
        })
        
        // Reseller-side validation
        let hasErrors = false
        
        if (!form.value.name.trim()) {
          fieldErrors.value.name = 'Agent Name is required'
          hasErrors = true
        }
        
        if (!form.value.model.messages[0].content.trim()) {
          fieldErrors.value.systemPrompt = 'System Prompt is required'
          hasErrors = true
        }
        
        if (!form.value.firstMessage.trim()) {
          fieldErrors.value.firstMessage = 'First Message is required'
          hasErrors = true
        }
        
        if (!form.value.metadata.company_name.trim()) {
          fieldErrors.value.company_name = 'Company Name is required'
          hasErrors = true
        }
        
        if (!form.value.metadata.industry.trim()) {
          fieldErrors.value.industry = 'Industry is required'
          hasErrors = true
        }
        
        if (!form.value.metadata.country) {
          fieldErrors.value.country = 'Country is required'
          hasErrors = true
        }
        
        if (!form.value.metadata.services_products.trim()) {
          fieldErrors.value.services_products = 'Services/Products is required'
          hasErrors = true
        }
        
        // Admin user assignment validation
        if (isAdmin.value && !form.value.user_id) {
          fieldErrors.value.user_assignment = 'Please select a user to assign this assistant to'
          hasErrors = true
        }
        
        if (hasErrors) {
          // Focus on the first field with an error
          focusFirstErrorField()
          return
        }
        
        
        // Process the system prompt with company information before saving
        const processedPrompt = processedSystemPrompt.value
        
        // Prepare the data according to Sulus API structure
        const assistantData = {
          name: form.value.name,
          type: form.value.type, // Add type field
          model: {
            ...form.value.model,
            messages: [
              {
                role: 'system',
                content: isCreating.value ? processedSystemPrompt.value : form.value.model.messages[0].content
              }
            ]
          },
          voice: form.value.voice,
          transcriber: form.value.transcriber,
          analysisPlan: form.value.analysisPlan,
          artifactPlan: form.value.artifactPlan,
          firstMessage: isCreating.value ? processedFirstMessage.value : form.value.firstMessage, // Use processed for create, actual for update
          firstMessageMode: form.value.firstMessageMode,
          firstMessageInterruptionsEnabled: form.value.firstMessageInterruptionsEnabled,
          endCallMessage: isCreating.value ? processedEndCallMessage.value : form.value.endCallMessage, // Use processed for create, actual for update
          metadata: {
            ...form.value.metadata,
            user_id: form.value.user_id || currentUser.value.id,
            user_email: currentUser.value.email,
            updated_at: new Date().toISOString()
          },
          user_id: form.value.user_id || currentUser.value.id // Add user_id to main data
        }
        
        // Add selected phone number if chosen
        if (selectedPhoneNumber.value) {
          assistantData.selected_phone_number = selectedPhoneNumber.value
        }
        
        if (isCreating.value) {
          await axios.post('/api/assistants', assistantData)
          success.value = 'Assistant created successfully!'
          // Clear any previous errors
          error.value = null
          // Navigate after a short delay to show success message
          setTimeout(() => {
            if (isAdmin.value) {
              router.push('/admin/assistants')
            } else {
              router.push('/assistants')
            }
          }, 2000)
        } else {
          await axios.put(`/api/assistants/${route.params.id}`, assistantData)
          success.value = 'Assistant updated successfully!'
          // Clear any previous errors
          error.value = null
          // Navigate after a short delay to show success message
          setTimeout(() => {
            if (isAdmin.value) {
              router.push('/admin/assistants')
            } else {
              router.push('/assistants')
            }
          }, 2000)
        }
      } catch (err) {
        
        if (err.response) {
          const { status, data } = err.response
          
          switch (status) {
            case 400:
            case 422:
              // Validation errors - map to specific fields
              if (data.errors) {
                Object.entries(data.errors).forEach(([field, messages]) => {
                  const fieldName = field.replace(/\./g, '_') // Convert metadata.company_name to metadata_company_name
                  const errorMessage = Array.isArray(messages) ? messages.join(', ') : messages
                  
                  // Map server field names to our field error keys
                  const fieldMapping = {
                    'name': 'name',
                    'model.messages.0.content': 'systemPrompt',
                    'firstMessage': 'firstMessage',
                    'endCallMessage': 'endCallMessage',
                    'metadata.company_name': 'company_name',
                    'metadata.industry': 'industry',
                    'metadata.services_products': 'services_products',
                    'metadata.sms_phone_number': 'sms_phone_number',
                    'user_id': 'user_assignment', // Map user_id to user_assignment
                    'transcriber.provider': 'transcriber_provider',
                    'transcriber.model': 'transcriber_model',
                    'transcriber.language': 'transcriber_language',
                    'voice.provider': 'voice_provider',
                    'voice.voiceId': 'voice_voiceId',
                    'voice.model': 'voice_model',
                    'voice.speed': 'voice_speed',
                    'model.provider': 'model_provider',
                    'model.model': 'model_model',
                    'model.temperature': 'model_temperature',
                    'model.maxTokens': 'model_maxTokens',
                    'model.topP': 'model_topP',
                    'model.frequencyPenalty': 'model_frequencyPenalty',
                    'model.presencePenalty': 'model_presencePenalty'
                  }
                  
                  const mappedField = fieldMapping[field] || fieldName
                  console.log(`Mapping field "${field}" to "${mappedField}" with message: "${errorMessage}"`)
                  if (fieldErrors.value.hasOwnProperty(mappedField)) {
                    fieldErrors.value[mappedField] = errorMessage
                    console.log(`Set field error for ${mappedField}:`, fieldErrors.value[mappedField])
                  } else {
                    console.log(`Field ${mappedField} not found in fieldErrors`)
                  }
                })
                console.log('Final fieldErrors:', fieldErrors.value)
                // Focus on the first field with an error
                focusFirstErrorField()
                // Field errors are already displayed inline, no need for SweetAlert
              } else if (data.message) {
                error.value = data.message
              } else {
                error.value = 'Invalid data provided. Please check your inputs and try again.'
              }
              break
              
            case 401:
              error.value = 'You are not authorized to update this assistant. Please log in again.'
              break
              
            case 403:
              if (data.message && data.message.includes('need an active subscription')) {
                error.value = 'You need an active subscription to create assistants. Please subscribe to a plan to get started.'
              } else if (data.message && data.message.includes('assistant limit')) {
                error.value = 'You have reached your assistant limit for your current subscription plan. Please upgrade your plan to create more assistants.'
              } else {
                error.value = 'You do not have permission to update this assistant.'
              }
              break
              
            case 404:
              error.value = 'Assistant not found. Please check the URL and try again.'
              break
              
            case 500:
              error.value = 'Server error occurred. Please try again later.'
              break
              
            default:
              if (data.message) {
                error.value = data.message
              } else {
                error.value = `Failed to update assistant (Status: ${status}). Please try again.`
              }
          }
        } else if (err.request) {
          // Network error
          error.value = 'Network error. Please check your internet connection and try again.'
        } else {
          // Other errors
          error.value = 'An unexpected error occurred. Please try again.'
        }
      } finally {
        submitting.value = false
      }
    }

    const loadTemplates = async () => {
      try {
        const response = await axios.get('/api/assistant-templates')
        if (response.data.success) {
          templates.value = response.data.data
          // Update templated data with current company info
          updateTemplatedData()
        }
      } catch (error) {
        // Handle error silently
      }
    }

    const loadDefaultTemplate = () => {
      // This function is now only used for creating new assistants
      if (isCreating.value && form.value.type === 'demo') {
        form.value.model.messages[0].content = templates.value.system_prompt || `## COMPANY PROFILE - 
\`\`\`
COMPANY_NAME: {{company_name}}
COMPANY_INDUSTRY: {{company_industry}}
COMPANY_SERVICES: {{company_name}} provides {{company_services}}
\`\`\`

## Core Identity & Mission
You are a professional customer service representative for {{company_name}}, a leading {{company_industry}} company specializing in {{company_name}} provides {{company_services}}. 

You embody the highest standards of customer service that {{company_name}} would provide to their valued resellers.`
      }
    }

    const loadDefaultFirstMessage = () => {
      // This function is now only used for creating new assistants
      if (isCreating.value && form.value.type === 'demo') {
        form.value.firstMessage = templates.value.first_message || 'Thank you for calling {{company_name}}, this is Sarah. How may I assist you today?'
      }
    }

    const loadDefaultEndCallMessage = () => {
      // This function is now only used for creating new assistants
      if (isCreating.value && form.value.type === 'demo') {
        form.value.endCallMessage = templates.value.end_call_message || 'Thank you for calling {{company_name}}. Have a wonderful day!'
      }
    }

    // Update methods for configuration components
    const updateModel = (newModel) => {
      form.value.model = { ...form.value.model, ...newModel }
    }

    const updateVoice = (newVoice) => {
      form.value.voice = { ...form.value.voice, ...newVoice }
    }

    const updateTranscriber = (newTranscriber) => {
      form.value.transcriber = { ...form.value.transcriber, ...newTranscriber }
    }

    const updateAnalysisPlan = (newAnalysisPlan) => {
      form.value.analysisPlan = { ...form.value.analysisPlan, ...newAnalysisPlan }
    }

    const updateArtifactPlan = (newArtifactPlan) => {
      form.value.artifactPlan = { ...form.value.artifactPlan, ...newArtifactPlan }
    }

    const getFirstMessagePlaceholder = () => {
      switch (form.value.firstMessageMode) {
        case 'assistant-speaks-first':
          return 'Enter the first message the assistant will say when a call starts...'
        case 'assistant-speaks-first-with-model-generated-message':
          return 'Enter a fallback message or example. The AI will generate the actual first message...'
        case 'assistant-waits-for-user':
          return 'Enter the message the assistant will use when responding to the user...'
        default:
          return 'Enter the first message...'
      }
    }

    const goBack = () => {
      // Try to go back in browser history, fallback to appropriate route
      if (window.history.length > 1) {
        router.go(-1)
      } else {
        // Fallback based on user role
        if (isAdmin.value) {
          router.push('/admin/assistants')
        } else {
          router.push('/assistants')
        }
      }
    }

    // Helper function to focus on the first field with an error
    const focusFirstErrorField = () => {
      // Wait for DOM to update
      nextTick(() => {
        // Define field order for focusing (top to bottom, left to right)
        const fieldOrder = [
          'name',
          'company_name',
          'industry',
          'country',
          'services_products',
          'sms_phone_number',
          'assistant_phone_number',
          'webhook_url',
          'user_assignment',
          'systemPrompt',
          'firstMessage',
          'endCallMessage',
          'model_provider',
          'model_model',
          'voice_provider',
          'voice_voiceId',
          'transcriber_provider',
          'transcriber_model',
          'transcriber_language'
        ]

        // Find the first field with an error
        for (const fieldName of fieldOrder) {
          if (fieldErrors.value[fieldName]) {
            // Switch to the correct tab if needed
            if (fieldName.startsWith('model_')) {
              activeTab.value = 'model'
              console.log('Switched to Model tab for error:', fieldName)
            } else if (fieldName.startsWith('voice_')) {
              activeTab.value = 'voice'
              console.log('Switched to Voice tab for error:', fieldName)
            } else if (fieldName.startsWith('transcriber_')) {
              activeTab.value = 'transcriber'
              console.log('Switched to Transcriber tab for error:', fieldName)
            }

            // Wait for tab switch to complete, then focus
            nextTick(() => {
              // Try to find the input element
              let element = null
              
              // Handle special cases for nested fields
              if (fieldName === 'systemPrompt') {
                element = document.querySelector('textarea[placeholder*="system prompt"], textarea[placeholder*="System prompt"]')
              } else if (fieldName === 'firstMessage') {
                element = document.querySelector('textarea[placeholder*="first message"], textarea[placeholder*="First message"]')
              } else if (fieldName === 'endCallMessage') {
                element = document.querySelector('textarea[placeholder*="end call"], textarea[placeholder*="End call"]')
              } else {
                // Try common selectors for the field
                element = document.querySelector(`input[name="${fieldName}"], select[name="${fieldName}"], textarea[name="${fieldName}"]`) ||
                         document.querySelector(`input[id="${fieldName}"], select[id="${fieldName}"], textarea[id="${fieldName}"]`) ||
                         document.querySelector(`[data-field="${fieldName}"]`)
              }

              if (element) {
                element.focus()
                element.scrollIntoView({ behavior: 'smooth', block: 'center' })
                console.log(`Focused on field with error: ${fieldName}`)
              } else {
                console.log(`Could not find element for field: ${fieldName}`)
              }
            })
            break
          }
        }
      })
    }

    const loadAvailableNumbers = async () => {
      try {
        loadingNumbers.value = true
        searchReset.value = false // Reset the flag when starting a new search
        const params = {}
        if (areaCode.value.trim()) {
          params.area_code = areaCode.value.trim()
        }
        
        // Add country parameter if available
        if (form.value.metadata.country) {
          params.country = form.value.metadata.country
        }
        
        const response = await axios.get('/api/twilio/available-numbers', { params })
        
        if (response.data.success) {
          availableNumbers.value = response.data.data
          selectedPhoneNumber.value = '' // Reset selection
        } else {
          await showError('Error', 'Failed to load available phone numbers')
        }
      } catch (error) {
        await showError('Error', 'Failed to load available phone numbers')
      } finally {
        loadingNumbers.value = false
      }
    }

    onMounted(async () => {
      await loadTemplates() // Load templates first
      await loadAssistant()
      loadUsers()
      loadSubscriptionInfo()
      
      // Set default user for admin when creating
      if (isCreating.value && isAdmin.value && currentUser.value.id) {
        form.value.user_id = currentUser.value.id
      }
    })

    return {
      loading,
      submitting,
      error,
      success,
      form,
      fieldErrors,
      processedSystemPrompt,
      processedFirstMessage,
      processedEndCallMessage,
      isCreating,
      loadAssistant,
      saveAssistant,
      goBack,
      users,
      loadingUsers,
      isAdmin,
      subscriptionInfo,
      templates,
      availableNumbers,
      loadingNumbers,
      purchasingNumber,
      areaCode,
      selectedPhoneNumber,
      loadAvailableNumbers,
      replaceWithTemplate,
      replaceWithActual,
      actualSulusData,
      templatedData,
      isAreaCodeSupported,
      searchReset,
      activeTab,
      updateModel,
      updateVoice,
      updateTranscriber,
      updateAnalysisPlan,
      updateArtifactPlan,
      getFirstMessagePlaceholder,
      focusFirstErrorField
    }
  }
}
</script> 