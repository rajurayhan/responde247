<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
        <form @submit.prevent="saveSubscription">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-medium text-gray-900">
                {{ isEditing ? 'Edit Subscription' : 'Create Subscription' }}
              </h3>
              <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Error Messages -->
            <div v-if="errors.length > 0" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-red-800">Please correct the following errors:</h3>
                  <div class="mt-2 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                      <li v-for="error in errors" :key="error">{{ error }}</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <!-- Stripe Integration Section (Only for new subscriptions) -->
            <div v-if="!isEditing" class="mb-6">
              <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h4 class="text-sm font-medium text-blue-900 mb-3">Subscription Type</h4>
                <p class="text-sm text-blue-700 mb-4">Choose between creating a new subscription or syncing an existing one from Stripe</p>
                
                <!-- Toggle Switch -->
                <div class="flex items-center space-x-4 mb-4">
                  <span :class="['text-sm font-medium', !isExistingSubscription ? 'text-blue-900' : 'text-gray-500']">New</span>
                  <button
                    type="button"
                    @click="toggleSubscriptionType"
                    :class="[
                      'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                      isExistingSubscription ? 'bg-blue-600' : 'bg-gray-200'
                    ]"
                  >
                    <span
                      :class="[
                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                        isExistingSubscription ? 'translate-x-5' : 'translate-x-0'
                      ]"
                    />
                  </button>
                  <span :class="['text-sm font-medium', isExistingSubscription ? 'text-blue-900' : 'text-gray-500']">Existing</span>
                </div>

                <!-- Existing Stripe Subscription Section -->
                <div v-if="isExistingSubscription" class="mb-6">
                  <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h5 class="text-sm font-medium text-blue-900 mb-3">Sync Existing Stripe Subscription</h5>
                    
                    <!-- User Selection -->
                    <div class="mb-4">
                      <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                      <SearchableSelect
                        v-model="form.user_id"
                        :options="users"
                        :loading="false"
                        placeholder="Search users..."
                        value-key="id"
                        label-key="name"
                        description-key="email"
                        :search-keys="['name', 'email']"
                      >
                        <template #default="{ option }">
                          <div class="flex justify-between items-center">
                            <div>
                              <div class="font-medium">{{ option.name }}</div>
                              <div class="text-xs text-gray-500">{{ option.email }}</div>
                            </div>
                            <div class="text-xs text-gray-400">
                              ID: {{ option.id }}
                            </div>
                          </div>
                        </template>
                      </SearchableSelect>
                      <p v-if="fieldErrors.user_id" class="mt-1 text-sm text-red-600">{{ fieldErrors.user_id }}</p>
                    </div>

                    <!-- Package Selection -->
                    <div class="mb-4">
                      <label class="block text-sm font-medium text-gray-700 mb-1">Package</label>
                      <SearchableSelect
                        v-model="form.package_id"
                        :options="packages"
                        :loading="false"
                        placeholder="Search packages..."
                        value-key="id"
                        label-key="name"
                        description-key="description"
                        :search-keys="['name', 'description']"
                      >
                        <template #default="{ option }">
                          <div class="flex justify-between items-center">
                            <div>
                              <div class="font-medium">{{ option.name }}</div>
                              <div class="text-xs text-gray-500">{{ option.description }}</div>
                            </div>
                            <div class="text-xs text-gray-400">
                              ${{ option.price }}/mo
                            </div>
                          </div>
                        </template>
                      </SearchableSelect>
                      <p v-if="fieldErrors.package_id" class="mt-1 text-sm text-red-600">{{ fieldErrors.package_id }}</p>
                    </div>

                    <!-- Stripe Customer Selection -->
                    <div class="mb-4">
                      <label class="block text-sm font-medium text-gray-700 mb-1">Stripe Customer</label>
                      <SearchableSelect
                        v-model="form.stripe_customer_id"
                        :options="stripeCustomers"
                        :loading="false"
                        placeholder="Search customers..."
                        value-key="id"
                        label-key="display_name"
                        description-key="email"
                        :search-keys="['name', 'email', 'id']"
                        @change="onCustomerChange"
                      >
                        <template #default="{ option }">
                          <div class="flex justify-between items-center">
                            <div>
                              <div class="font-medium">{{ option.name || option.email }}</div>
                              <div class="text-xs text-gray-500">{{ option.id }}</div>
                            </div>
                            <div v-if="option.email" class="text-xs text-gray-400">
                              {{ option.email }}
                            </div>
                          </div>
                        </template>
                      </SearchableSelect>
                      <p v-if="fieldErrors.stripe_customer_id" class="mt-1 text-sm text-red-600">{{ fieldErrors.stripe_customer_id }}</p>
                    </div>

                    <!-- Stripe Subscription Selection -->
                    <div class="mb-4">
                      <label class="block text-sm font-medium text-gray-700 mb-1">Stripe Subscription</label>
                      <SearchableSelect
                        v-model="form.stripe_subscription_id"
                        :options="stripeSubscriptions"
                        :loading="loadingSubscriptions"
                        :disabled="!form.stripe_customer_id"
                        placeholder="Search subscriptions..."
                        value-key="id"
                        label-key="id"
                        description-key="status"
                        :search-keys="['id', 'status']"
                        @change="onSubscriptionChange"
                      >
                        <template #default="{ option }">
                          <div class="flex justify-between items-center">
                            <div>
                              <div class="font-medium">{{ option.id }}</div>
                              <div class="text-xs text-gray-500">
                                {{ formatDate(option.current_period_start) }} to {{ formatDate(option.current_period_end) }}
                              </div>
                            </div>
                            <div class="text-xs">
                              <span :class="[
                                'px-2 py-1 rounded-full text-xs font-medium',
                                option.status === 'active' ? 'bg-green-100 text-green-800' :
                                option.status === 'canceled' ? 'bg-red-100 text-red-800' :
                                option.status === 'past_due' ? 'bg-yellow-100 text-yellow-800' :
                                'bg-gray-100 text-gray-800'
                              ]">
                                {{ option.status }}
                              </span>
                            </div>
                          </div>
                        </template>
                      </SearchableSelect>
                      <p v-if="fieldErrors.stripe_subscription_id" class="mt-1 text-sm text-red-600">{{ fieldErrors.stripe_subscription_id }}</p>
                    </div>

                    <!-- Auto-sync notification -->
                    <div v-if="form.stripe_customer_id && form.stripe_subscription_id" class="p-3 bg-green-50 border border-green-200 rounded-md">
                      <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm text-green-700">
                          This will sync the existing Stripe subscription and all associated transactions.
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- New Subscription Section -->
                <div v-else class="mb-6">
                  <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                    <h5 class="text-sm font-medium text-green-900 mb-3">Create New Subscription</h5>
                    <p class="text-sm text-green-700">
                      This will create a new subscription and generate a payment link for the user.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Read-only Stripe info for editing -->
            <div v-else-if="isEditing && subscription.stripe_customer_id" class="mb-6">
              <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Stripe Integration</h4>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Stripe Customer ID</label>
                    <p class="mt-1 text-sm text-gray-900 font-mono">{{ subscription.stripe_customer_id }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Stripe Subscription ID</label>
                    <p class="mt-1 text-sm text-gray-900 font-mono">{{ subscription.stripe_subscription_id }}</p>
                  </div>
                </div>
                <p class="mt-2 text-xs text-gray-500">Stripe integration details cannot be modified when editing.</p>
              </div>
            </div>

            <!-- Form Fields (only show for new subscriptions or when not syncing from Stripe) -->
            <div v-if="!isExistingSubscription || isEditing">
              <!-- User Selection -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                <SearchableSelect
                  v-model="form.user_id"
                  :options="users"
                  :loading="false"
                  placeholder="Search users..."
                  value-key="id"
                  label-key="name"
                  description-key="email"
                  :search-keys="['name', 'email']"
                >
                  <template #default="{ option }">
                    <div class="flex justify-between items-center">
                      <div>
                        <div class="font-medium">{{ option.name }}</div>
                        <div class="text-xs text-gray-500">{{ option.email }}</div>
                      </div>
                      <div class="text-xs text-gray-400">
                        ID: {{ option.id }}
                      </div>
                    </div>
                  </template>
                </SearchableSelect>
                <p v-if="fieldErrors.user_id" class="mt-1 text-sm text-red-600">{{ fieldErrors.user_id }}</p>
              </div>

              <!-- Package Selection -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Package</label>
                <SearchableSelect
                  v-model="form.package_id"
                  :options="packages"
                  :loading="false"
                  placeholder="Search packages..."
                  value-key="id"
                  label-key="name"
                  description-key="description"
                  :search-keys="['name', 'description']"
                >
                  <template #default="{ option }">
                    <div class="flex justify-between items-center">
                      <div>
                        <div class="font-medium">{{ option.name }}</div>
                        <div class="text-xs text-gray-500">{{ option.description }}</div>
                      </div>
                      <div class="text-xs text-gray-400">
                        ${{ option.price }}/mo
                      </div>
                    </div>
                  </template>
                </SearchableSelect>
                <p v-if="fieldErrors.package_id" class="mt-1 text-sm text-red-600">{{ fieldErrors.package_id }}</p>
              </div>

              <!-- Custom Amount -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Custom Amount ($)</label>
                <input
                  v-model="form.custom_amount"
                  type="number"
                  step="0.01"
                  min="0.01"
                  :class="[
                    'w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    fieldErrors.custom_amount ? 'border-red-300' : 'border-gray-300'
                  ]"
                  placeholder="Enter custom amount"
                />
                <p v-if="fieldErrors.custom_amount" class="mt-1 text-sm text-red-600">{{ fieldErrors.custom_amount }}</p>
              </div>

              <!-- Billing Interval -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Billing Interval</label>
                <select
                  v-model="form.billing_interval"
                  :class="[
                    'w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    fieldErrors.billing_interval ? 'border-red-300' : 'border-gray-300'
                  ]"
                >
                  <option value="monthly">Monthly</option>
                  <option value="yearly">Yearly</option>
                </select>
                <p v-if="fieldErrors.billing_interval" class="mt-1 text-sm text-red-600">{{ fieldErrors.billing_interval }}</p>
              </div>

              <!-- Duration -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Duration (months)</label>
                <input
                  v-model="form.duration_months"
                  type="number"
                  min="1"
                  max="120"
                  :class="[
                    'w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    fieldErrors.duration_months ? 'border-red-300' : 'border-gray-300'
                  ]"
                  placeholder="Enter duration in months"
                />
                <p v-if="fieldErrors.duration_months" class="mt-1 text-sm text-red-600">{{ fieldErrors.duration_months }}</p>
              </div>

              <!-- Status -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select
                  v-model="form.status"
                  :class="[
                    'w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    fieldErrors.status ? 'border-red-300' : 'border-gray-300'
                  ]"
                >
                  <option value="active">Active</option>
                  <option value="cancelled">Cancelled</option>
                  <option value="expired">Expired</option>
                  <option value="trial">Trial</option>
                  <option value="pending">Pending</option>
                </select>
                <p v-if="fieldErrors.status" class="mt-1 text-sm text-red-600">{{ fieldErrors.status }}</p>
              </div>

              <!-- Trial Period -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Trial Ends At</label>
                <input
                  v-model="form.trial_ends_at"
                  type="datetime-local"
                  :class="[
                    'w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    fieldErrors.trial_ends_at ? 'border-red-300' : 'border-gray-300'
                  ]"
                />
                <p v-if="fieldErrors.trial_ends_at" class="mt-1 text-sm text-red-600">{{ fieldErrors.trial_ends_at }}</p>
                <p class="text-xs text-gray-500 mt-1">Leave empty for no trial period</p>
              </div>

              <!-- Period Dates -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Current Period Start</label>
                  <input
                    v-model="form.current_period_start"
                    type="datetime-local"
                    :class="[
                      'w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                      fieldErrors.current_period_start ? 'border-red-300' : 'border-gray-300'
                    ]"
                  />
                  <p v-if="fieldErrors.current_period_start" class="mt-1 text-sm text-red-600">{{ fieldErrors.current_period_start }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Current Period End</label>
                  <input
                    v-model="form.current_period_end"
                    type="datetime-local"
                    :class="[
                      'w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                      fieldErrors.current_period_end ? 'border-red-300' : 'border-gray-300'
                    ]"
                  />
                  <p v-if="fieldErrors.current_period_end" class="mt-1 text-sm text-red-600">{{ fieldErrors.current_period_end }}</p>
                </div>
              </div>

              <!-- Payment Link -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Payment Link ID</label>
                  <input
                    v-model="form.payment_link_id"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="plink_xxxxx"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Payment Link URL</label>
                  <div class="flex">
                    <input
                      v-model="form.payment_link_url"
                      type="url"
                      class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="https://checkout.stripe.com/..."
                    />
                    <button
                      v-if="form.payment_link_url"
                      @click="copyToClipboard(form.payment_link_url, 'Payment Link URL')"
                      type="button"
                      class="px-3 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                      title="Copy Payment Link URL"
                    >
                      <svg class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Stripe Checkout Session -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Stripe Checkout Session ID</label>
                  <input
                    v-model="form.stripe_checkout_session_id"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="cs_xxxxx"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Checkout Session URL</label>
                  <div class="flex">
                    <input
                      v-model="form.checkout_session_url"
                      type="url"
                      class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="https://checkout.stripe.com/c/pay/..."
                    />
                    <button
                      v-if="form.checkout_session_url"
                      @click="copyToClipboard(form.checkout_session_url, 'Checkout Session URL')"
                      type="button"
                      class="px-3 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                      title="Copy Checkout Session URL"
                    >
                      <svg class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Metadata -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Metadata (JSON)</label>
                <textarea
                  v-model="form.metadata_json"
                  rows="4"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                  placeholder='{"key": "value"}'
                ></textarea>
                <p class="text-xs text-gray-500 mt-1">Enter as JSON object</p>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="submit"
              :disabled="saving"
              :class="[
                'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm',
                saving ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'
              ]"
            >
              <span v-if="saving" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ isExistingSubscription ? 'Syncing...' : 'Creating...' }}
              </span>
              <span v-else>
                {{ isExistingSubscription ? 'Sync from Stripe' : (isEditing ? 'Update Subscription' : 'Create Subscription') }}
              </span>
            </button>
            <button
              type="button"
              @click="$emit('close')"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch, onMounted, getCurrentInstance } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import SearchableSelect from '../shared/SearchableSelect.vue'

export default {
  name: 'AdminSubscriptionFormModal',
  components: {
    SearchableSelect
  },
  props: {
    show: {
      type: Boolean,
      default: false
    },
    subscription: {
      type: Object,
      default: null
    }
  },
  emits: ['close', 'saved'],
  setup(props, { emit }) {
    const { proxy } = getCurrentInstance()
    
    const saving = ref(false)
    const loadingSubscriptions = ref(false)
    const isExistingSubscription = ref(false)
    const stripeCustomers = ref([])
    const stripeSubscriptions = ref([])
    const users = ref([])
    const packages = ref([])
    const errors = ref([])
    const fieldErrors = ref({})

    const form = ref({
      user_id: '',
      package_id: '',
      custom_amount: '',
      billing_interval: 'monthly',
      duration_months: 1,
      status: 'pending',
      trial_ends_at: '',
      current_period_start: '',
      current_period_end: '',
      payment_link_id: '',
      payment_link_url: '',
      stripe_checkout_session_id: '',
      checkout_session_url: '',
      metadata_json: '{}',
      stripe_customer_id: '',
      stripe_subscription_id: ''
    })

    const isEditing = computed(() => props.subscription !== null)

    // Watch for show prop changes
    watch(() => props.show, (newShow) => {
      if (newShow) {
        resetForm()
        if (props.subscription) {
          populateForm(props.subscription)
        }
      }
    })

    // Reset form
    const resetForm = () => {
      form.value = {
        user_id: '',
        package_id: '',
        custom_amount: '',
        billing_interval: 'monthly',
        duration_months: 1,
        status: 'pending',
        trial_ends_at: '',
        current_period_start: '',
        current_period_end: '',
        payment_link_id: '',
        payment_link_url: '',
        stripe_checkout_session_id: '',
        checkout_session_url: '',
        metadata_json: '{}',
        stripe_customer_id: '',
        stripe_subscription_id: ''
      }
      errors.value = []
      fieldErrors.value = {}
      isExistingSubscription.value = false
      stripeSubscriptions.value = []
    }

    // Populate form for editing
    const populateForm = (subscription) => {
      form.value = {
        user_id: subscription.user_id || '',
        package_id: subscription.subscription_package_id || '',
        custom_amount: subscription.custom_amount || '',
        billing_interval: subscription.billing_interval || 'monthly',
        duration_months: subscription.duration_months || 1,
        status: subscription.status || 'pending',
        trial_ends_at: subscription.trial_ends_at ? formatDateForInput(subscription.trial_ends_at) : '',
        current_period_start: subscription.current_period_start ? formatDateForInput(subscription.current_period_start) : '',
        current_period_end: subscription.current_period_end ? formatDateForInput(subscription.current_period_end) : '',
        payment_link_id: subscription.payment_link_id || '',
        payment_link_url: subscription.payment_link_url || '',
        stripe_checkout_session_id: subscription.stripe_checkout_session_id || '',
        checkout_session_url: subscription.checkout_session_url || '',
        metadata_json: subscription.metadata_json || '{}',
        stripe_customer_id: subscription.stripe_customer_id || '',
        stripe_subscription_id: subscription.stripe_subscription_id || ''
      }
    }

    // Load users
    const loadUsers = async () => {
      try {
        const response = await axios.get('/api/admin/users', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        if (response.data.success) {
          users.value = response.data.data
        }
      } catch (error) {
        console.error('Error loading users:', error)
      }
    }

    // Load packages
    const loadPackages = async () => {
      try {
        const response = await axios.get('/api/admin/subscriptions/packages', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        if (response.data.success) {
          packages.value = response.data.data
        }
      } catch (error) {
        console.error('Error loading packages:', error)
      }
    }

    // Load Stripe customers
    const loadStripeCustomers = async () => {
      try {
        const response = await axios.get('/api/admin/subscriptions/custom/stripe-customers', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        if (response.data.success) {
          stripeCustomers.value = response.data.data
        }
      } catch (error) {
        console.error('Error loading Stripe customers:', error)
      }
    }

    // Load customer subscriptions
    const loadCustomerSubscriptions = async (customerId) => {
      if (!customerId) return
      
      loadingSubscriptions.value = true
      try {
        const response = await axios.get('/api/admin/subscriptions/custom/customer-subscriptions', {
          params: { customer_id: customerId },
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        if (response.data.success) {
          stripeSubscriptions.value = response.data.data
        }
      } catch (error) {
        console.error('Error loading customer subscriptions:', error)
        stripeSubscriptions.value = []
      } finally {
        loadingSubscriptions.value = false
      }
    }

    // Handle customer selection change
    const onCustomerChange = (event) => {
      form.value.stripe_subscription_id = ''
      loadCustomerSubscriptions(event.value)
    }

    // Handle subscription selection change
    const onSubscriptionChange = (event) => {
      if (event.value) {
        const selectedSubscription = stripeSubscriptions.value.find(sub => sub.id === event.value)
        if (selectedSubscription) {
          // Auto-populate current period dates from subscription
          form.value.current_period_start = formatDateForInput(selectedSubscription.current_period_start)
          form.value.current_period_end = formatDateForInput(selectedSubscription.current_period_end)
          
          // Auto-populate trial end date if available
          if (selectedSubscription.trial_end) {
            form.value.trial_ends_at = formatDateForInput(selectedSubscription.trial_end)
          }
          
          // Auto-populate status
          form.value.status = mapStripeStatusToFormStatus(selectedSubscription.status)
        }
      }
    }

    // Toggle subscription type
    const toggleSubscriptionType = () => {
      isExistingSubscription.value = !isExistingSubscription.value
      
      // Reset Stripe fields when switching back to "New"
      if (!isExistingSubscription.value) {
        form.value.stripe_customer_id = ''
        form.value.stripe_subscription_id = ''
        stripeSubscriptions.value = []
      }
    }

    // Format date for input fields (YYYY-MM-DDTHH:MM)
    const formatDateForInput = (dateString) => {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toISOString().slice(0, 16)
    }

    // Copy to clipboard function
    const copyToClipboard = async (text, label) => {
      try {
        await navigator.clipboard.writeText(text)
        Swal.fire({
          icon: 'success',
          title: 'Copied!',
          text: `${label} copied to clipboard`,
          timer: 1500,
          showConfirmButton: false
        })
      } catch (err) {
        console.error('Failed to copy: ', err)
        Swal.fire({
          icon: 'error',
          title: 'Copy Failed',
          text: 'Failed to copy to clipboard',
          timer: 2000,
          showConfirmButton: false
        })
      }
    }

    // Map Stripe status to form status
    const mapStripeStatusToFormStatus = (stripeStatus) => {
      const statusMap = {
        'active': 'active',
        'canceled': 'cancelled',
        'incomplete': 'pending',
        'incomplete_expired': 'expired',
        'past_due': 'past_due',
        'trialing': 'trial',
        'unpaid': 'expired'
      }
      return statusMap[stripeStatus] || 'pending'
    }

    // Format date
    const formatDate = (timestamp) => {
      if (!timestamp) return 'N/A'
      return new Date(timestamp * 1000).toLocaleDateString()
    }

    // Save subscription
    const saveSubscription = async () => {
      saving.value = true
      errors.value = []
      fieldErrors.value = {}

      try {
        let url, data

        if (isExistingSubscription.value && !isEditing.value) {
          // Sync from Stripe
          url = '/api/admin/subscriptions/custom/sync-from-stripe'
          data = {
            user_id: form.value.user_id,
            package_id: form.value.package_id,
            stripe_customer_id: form.value.stripe_customer_id,
            stripe_subscription_id: form.value.stripe_subscription_id
          }
        } else {
          // Create new subscription
          url = '/api/admin/subscriptions/custom/create'
          data = {
            user_id: form.value.user_id,
            package_id: form.value.package_id,
            custom_amount: parseFloat(form.value.custom_amount),
            billing_interval: form.value.billing_interval,
            duration_months: parseInt(form.value.duration_months)
          }
        }

        const response = await axios.post(url, data, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        })

        if (response.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: response.data.message || 'Subscription created successfully',
            timer: 2000,
            showConfirmButton: false
          })
          
          emit('saved')
          emit('close')
        } else {
          errors.value = [response.data.message || 'An error occurred']
        }
      } catch (error) {
        console.error('Error saving subscription:', error)
        
        if (error.response?.data?.errors) {
          fieldErrors.value = error.response.data.errors
        } else if (error.response?.data?.message) {
          errors.value = [error.response.data.message]
        } else {
          errors.value = ['An unexpected error occurred']
        }
      } finally {
        saving.value = false
      }
    }

    onMounted(() => {
      loadUsers()
      loadPackages()
      loadStripeCustomers()
    })

    return {
      form,
      saving,
      loadingSubscriptions,
      isExistingSubscription,
      stripeCustomers,
      stripeSubscriptions,
      users,
      packages,
      isEditing,
      errors,
      fieldErrors,
      saveSubscription,
      onCustomerChange,
      onSubscriptionChange,
      toggleSubscriptionType,
      loadCustomerSubscriptions,
      formatDate,
      resetForm,
      populateForm,
      copyToClipboard
    }
  }
}
</script>
