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
            <div v-if="errors.length > 0" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                  <div class="mt-2 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                      <li v-for="error in errors" :key="error">{{ error }}</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <!-- Form Fields -->
            <div class="space-y-6">
              <!-- Reseller Selection -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reseller *</label>
                <select
                  v-model="form.reseller_id"
                  required
                  :class="[
                    'w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    fieldErrors.reseller_id ? 'border-red-300' : 'border-gray-300'
                  ]"
                >
                  <option value="">Select a reseller</option>
                  <option v-for="reseller in resellers" :key="reseller.id" :value="reseller.id">
                    {{ reseller.org_name }}
                  </option>
                </select>
                <p v-if="fieldErrors.reseller_id" class="mt-1 text-sm text-red-600">{{ fieldErrors.reseller_id }}</p>
              </div>

              <!-- Package Selection -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Package *</label>
                <select
                  v-model="form.reseller_package_id"
                  required
                  :class="[
                    'w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    fieldErrors.reseller_package_id ? 'border-red-300' : 'border-gray-300'
                  ]"
                >
                  <option value="">Select a package</option>
                  <option v-for="packageItem in packages" :key="packageItem.id" :value="packageItem.id">
                    {{ packageItem.name }} - ${{ parseFloat(packageItem.price).toFixed(2) }}/month
                    <span v-if="packageItem.yearly_price"> (Yearly: ${{ parseFloat(packageItem.yearly_price).toFixed(2) }})</span>
                  </option>
                </select>
                <p v-if="fieldErrors.reseller_package_id" class="mt-1 text-sm text-red-600">{{ fieldErrors.reseller_package_id }}</p>
              </div>

              <!-- Billing Interval -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Billing Interval *</label>
                <select
                  v-model="form.billing_interval"
                  required
                  :class="[
                    'w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    fieldErrors.billing_interval ? 'border-red-300' : 'border-gray-300'
                  ]"
                >
                  <option value="monthly">Monthly</option>
                  <option value="yearly">Yearly</option>
                </select>
                <p v-if="fieldErrors.billing_interval" class="mt-1 text-sm text-red-600">{{ fieldErrors.billing_interval }}</p>
                <div v-if="form.billing_interval === 'yearly' && selectedPackage" class="mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                  <div class="flex items-center">
                    <svg class="h-5 w-5 text-green-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                    </svg>
                    <span class="text-sm text-green-800">
                      <strong>Save {{ yearlySavings }}%</strong> with yearly billing
                      <span class="block text-xs text-green-600">
                        Monthly: ${{ (selectedPackage.price * 12).toFixed(2) }} | Yearly: ${{ parseFloat(selectedPackage.yearly_price || selectedPackage.price * 10).toFixed(2) }}
                      </span>
                    </span>
                  </div>
                </div>
              </div>

              <!-- Status -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                <select
                  v-model="form.status"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="active">Active</option>
                  <option value="cancelled">Cancelled</option>
                  <option value="expired">Expired</option>
                  <option value="trial">Trial</option>
                  <option value="pending">Pending</option>
                </select>
              </div>

              <!-- Custom Amount -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Custom Amount ($)</label>
                <input
                  v-model="form.custom_amount"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Leave empty to use package price"
                />
                <p class="text-xs text-gray-500 mt-1">Override the package price with a custom amount</p>
              </div>

              <!-- Trial Period -->
              <div>
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
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

              <!-- Stripe Integration -->
              <div class="border-t pt-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Stripe Integration</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stripe Subscription ID</label>
                    <input
                      v-model="form.stripe_subscription_id"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="sub_xxxxx"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stripe Customer ID</label>
                    <input
                      v-model="form.stripe_customer_id"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="cus_xxxxx"
                    />
                  </div>
                </div>
              </div>

              <!-- Payment Link -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
              <div>
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
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg v-if="saving" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ saving ? 'Saving...' : (isEditing ? 'Update Subscription' : 'Create Subscription') }}
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

export default {
  name: 'SubscriptionFormModal',
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
    const resellers = ref([])
    const packages = ref([])
    const errors = ref([])
    const fieldErrors = ref({})

    const form = ref({
      reseller_id: '',
      reseller_package_id: '',
      billing_interval: 'monthly',
      status: 'active',
      custom_amount: '',
      trial_ends_at: '',
      current_period_start: '',
      current_period_end: '',
      stripe_subscription_id: '',
      stripe_customer_id: '',
      payment_link_id: '',
      payment_link_url: '',
      stripe_checkout_session_id: '',
      checkout_session_url: '',
      metadata_json: '{}'
    })

    const isEditing = computed(() => props.subscription !== null)

    // Computed properties for package and savings
    const selectedPackage = computed(() => {
      if (!form.value.reseller_package_id) return null
      return packages.value.find(pkg => pkg.id == form.value.reseller_package_id)
    })

    const yearlySavings = computed(() => {
      if (!selectedPackage.value || !selectedPackage.value.yearly_price) return 0
      const monthlyYearlyCost = selectedPackage.value.price * 12
      const yearlyCost = selectedPackage.value.yearly_price
      return Math.round(((monthlyYearlyCost - yearlyCost) / monthlyYearlyCost) * 100)
    })

    // Load resellers and packages
    const loadResellers = async () => {
      try {
        const response = await fetch('/api/admin/resellers', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
          }
        })
        const data = await response.json()
        if (data.success) {
          resellers.value = data.data.data || []
        }
      } catch (error) {
        console.error('Error loading resellers:', error)
      }
    }

    const loadPackages = async () => {
      try {
        const response = await fetch('/api/super-admin/reseller-packages', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
          }
        })
        const data = await response.json()
        if (data.success) {
          packages.value = data.data.data || []
        }
      } catch (error) {
        console.error('Error loading packages:', error)
      }
    }

    // Watch for subscription changes to populate form
    watch(() => props.subscription, (newSubscription) => {
      if (newSubscription) {
        form.value = {
          reseller_id: newSubscription.reseller_id || '',
          reseller_package_id: newSubscription.reseller_package_id || '',
          billing_interval: newSubscription.billing_interval || 'monthly',
          status: newSubscription.status || 'active',
          custom_amount: newSubscription.custom_amount || '',
          trial_ends_at: newSubscription.trial_ends_at ? new Date(newSubscription.trial_ends_at).toISOString().slice(0, 16) : '',
          current_period_start: newSubscription.current_period_start ? new Date(newSubscription.current_period_start).toISOString().slice(0, 16) : '',
          current_period_end: newSubscription.current_period_end ? new Date(newSubscription.current_period_end).toISOString().slice(0, 16) : '',
          stripe_subscription_id: newSubscription.stripe_subscription_id || '',
          stripe_customer_id: newSubscription.stripe_customer_id || '',
          payment_link_id: newSubscription.payment_link_id || '',
          payment_link_url: newSubscription.payment_link_url || '',
          stripe_checkout_session_id: newSubscription.stripe_checkout_session_id || '',
          checkout_session_url: newSubscription.checkout_session_url || '',
          metadata_json: newSubscription.metadata ? JSON.stringify(newSubscription.metadata, null, 2) : '{}'
        }
      } else {
        // Reset form for new subscription
        form.value = {
          reseller_id: '',
          reseller_package_id: '',
          billing_interval: 'monthly',
          status: 'active',
          custom_amount: '',
          trial_ends_at: '',
          current_period_start: '',
          current_period_end: '',
          stripe_subscription_id: '',
          stripe_customer_id: '',
          payment_link_id: '',
          payment_link_url: '',
          stripe_checkout_session_id: '',
          checkout_session_url: '',
          metadata_json: '{}'
        }
      }
    }, { immediate: true })

    const saveSubscription = async () => {
      saving.value = true
      // Clear previous errors
      errors.value = []
      fieldErrors.value = {}
      
      try {
        // Parse metadata JSON
        let metadata = {}
        try {
          metadata = JSON.parse(form.value.metadata_json)
        } catch (e) {
          errors.value = ['Invalid JSON format for metadata']
          return
        }

        const subscriptionData = {
          ...form.value,
          metadata: metadata,
          custom_amount: form.value.custom_amount ? parseFloat(form.value.custom_amount) : null,
          trial_ends_at: form.value.trial_ends_at ? new Date(form.value.trial_ends_at).toISOString() : null,
          current_period_start: form.value.current_period_start ? new Date(form.value.current_period_start).toISOString() : null,
          current_period_end: form.value.current_period_end ? new Date(form.value.current_period_end).toISOString() : null
        }

        // Remove the JSON field
        delete subscriptionData.metadata_json

        const url = isEditing.value 
          ? `/api/super-admin/reseller-subscriptions/${props.subscription.id}`
          : '/api/super-admin/reseller-subscriptions'
        
        const method = isEditing.value ? 'PUT' : 'POST'

        const response = await fetch(url, {
          method,
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(subscriptionData)
        })

        const data = await response.json()
        if (data.success) {
          if (proxy.$toast && proxy.$toast.success) {
            proxy.$toast.success(`Subscription ${isEditing.value ? 'updated' : 'created'} successfully`)
          }
          emit('saved')
        } else {
          // Handle validation errors
          if (response.status === 422 && data.errors) {
            console.error('Validation errors:', data.errors)
            // Set field-specific errors
            fieldErrors.value = {}
            errors.value = []
            
            Object.entries(data.errors).forEach(([field, messages]) => {
              const errorMessage = Array.isArray(messages) ? messages.join(', ') : messages
              fieldErrors.value[field] = errorMessage
              errors.value.push(`${field}: ${errorMessage}`)
            })
          } else {
            errors.value = [data.message || `Failed to ${isEditing.value ? 'update' : 'create'} subscription`]
          }
        }
      } catch (error) {
        console.error('Error saving subscription:', error)
        if (error.response && error.response.status === 422 && error.response.data.errors) {
          // Handle validation errors from catch block
          fieldErrors.value = {}
          errors.value = []
          
          Object.entries(error.response.data.errors).forEach(([field, messages]) => {
            const errorMessage = Array.isArray(messages) ? messages.join(', ') : messages
            fieldErrors.value[field] = errorMessage
            errors.value.push(`${field}: ${errorMessage}`)
          })
        } else {
          errors.value = [error.message || `Error ${isEditing.value ? 'updating' : 'creating'} subscription`]
        }
      } finally {
        saving.value = false
      }
    }

    // Copy to clipboard function
    const copyToClipboard = async (text, label) => {
      try {
        await navigator.clipboard.writeText(text)
        if (proxy.$toast && proxy.$toast.success) {
          proxy.$toast.success('Copied')
        } else {
          // Fallback for environments without toast
          alert('Copied')
        }
      } catch (err) {
        console.error('Failed to copy to clipboard:', err)
        if (proxy.$toast && proxy.$toast.error) {
          proxy.$toast.error('Failed to copy')
        } else {
          alert('Failed to copy')
        }
      }
    }

    onMounted(() => {
      loadResellers()
      loadPackages()
    })

    return {
      form,
      saving,
      isEditing,
      resellers,
      packages,
      errors,
      fieldErrors,
      selectedPackage,
      yearlySavings,
      saveSubscription,
      copyToClipboard
    }
  }
}
</script>
