<template>
  <SuperAdminLayout title="Reseller Transactions" subtitle="Manage reseller payments and billing">
    <div class="flex-1 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="md:flex md:items-center md:justify-between">
            <div class="flex-1 min-w-0">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                    </svg>
                  </div>
                </div>
                <div>
                  <h1 class="text-3xl font-bold text-gray-900">
                    Reseller Transactions
                  </h1>
                  <p class="mt-1 text-sm text-gray-600">
                    Manage reseller payments and billing
                  </p>
                </div>
              </div>
            </div>
            <div class="mt-6 md:mt-0 md:ml-4">
              <button 
                @click="showFinancialSummary = true" 
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl shadow-sm text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transform transition-all duration-150 hover:scale-105"
              >
                <svg class="-ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Financial Summary
              </button>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="mb-8 bg-white rounded-xl border border-gray-200 p-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input
                v-model="filters.search"
                @input="performSearch"
                type="text"
                placeholder="Search transactions..."
                class="block w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg shadow-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
              />
            </div>

            <!-- Status Filter -->
            <select v-model="filters.status" @change="applyFilters" class="block w-full px-3 py-2 border border-gray-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
              <option value="">All Status</option>
              <option value="completed">Completed</option>
              <option value="pending">Pending</option>
              <option value="failed">Failed</option>
              <option value="refunded">Refunded</option>
              <option value="cancelled">Cancelled</option>
            </select>

            <!-- Type Filter -->
            <select v-model="filters.type" @change="applyFilters" class="block w-full px-3 py-2 border border-gray-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
              <option value="">All Types</option>
              <option value="subscription">Subscription</option>
              <option value="upgrade">Upgrade</option>
              <option value="renewal">Renewal</option>
              <option value="refund">Refund</option>
            </select>

            <!-- Date Range -->
            <div class="flex space-x-2">
              <input
                v-model="filters.date_from"
                @change="applyFilters"
                type="date"
                class="block w-full px-3 py-2 border border-gray-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                placeholder="From"
              />
              <input
                v-model="filters.date_to"
                @change="applyFilters"
                type="date"
                class="block w-full px-3 py-2 border border-gray-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                placeholder="To"
              />
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>

        <!-- Transactions Results -->
        <div v-else>
          <!-- Empty State -->
          <div v-if="transactions.data && transactions.data.length === 0" class="text-center py-16">
            <div class="mx-auto h-24 w-24 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center mb-6">
              <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No transactions found</h3>
            <p class="text-gray-500 mb-8 max-w-sm mx-auto">No transactions match your current filters. Try adjusting your search criteria.</p>
          </div>

          <!-- Transactions Table -->
          <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reseller</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr 
                    v-for="transaction in transactions.data" 
                    :key="transaction.id"
                    class="hover:bg-gray-50 transition-colors duration-150"
                  >
                    <!-- Transaction ID -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 h-8 w-8">
                          <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center">
                            <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                          </div>
                        </div>
                        <div class="ml-4">
                          <div class="text-sm font-medium text-gray-900">
                            {{ transaction.transaction_id }}
                          </div>
                          <div class="text-sm text-gray-500">
                            {{ transaction.payment_method || 'N/A' }}
                          </div>
                        </div>
                      </div>
                    </td>

                    <!-- Reseller -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">
                        {{ transaction.reseller?.org_name || 'N/A' }}
                      </div>
                      <div class="text-sm text-gray-500">
                        {{ transaction.billing_email }}
                      </div>
                    </td>

                    <!-- Amount -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">
                        ${{ parseFloat(transaction.amount).toFixed(2) }}
                      </div>
                      <div class="text-sm text-gray-500">
                        {{ transaction.currency?.toUpperCase() || 'USD' }}
                      </div>
                    </td>

                    <!-- Status -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ring-1 ring-inset',
                        getStatusClasses(transaction.status)
                      ]">
                        <svg 
                          :class="[
                            'w-1.5 h-1.5 mr-1',
                            getStatusDotClasses(transaction.status)
                          ]" 
                          fill="currentColor" 
                          viewBox="0 0 8 8"
                        >
                          <circle cx="4" cy="4" r="3" />
                        </svg>
                        {{ transaction.status }}
                      </span>
                    </td>

                    <!-- Type -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        {{ transaction.type }}
                      </span>
                    </td>

                    <!-- Date -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatDate(transaction.created_at) }}
                    </td>

                    <!-- Actions -->
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div class="flex items-center justify-end space-x-2">
                        <button 
                          @click="viewTransaction(transaction)" 
                          class="text-blue-600 hover:text-blue-900 transition-colors"
                          title="View details"
                        >
                          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                        </button>
                        
                        <button 
                          v-if="transaction.status === 'completed' && transaction.type !== 'refund'"
                          @click="processRefund(transaction)" 
                          class="text-red-600 hover:text-red-900 transition-colors"
                          title="Process refund"
                        >
                          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="transactions.last_page > 1" class="bg-white px-6 py-4 border-t border-gray-200">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <p class="text-sm text-gray-600">
                    Showing <span class="font-semibold text-gray-900">{{ transactions.from }}</span> to <span class="font-semibold text-gray-900">{{ transactions.to }}</span> of <span class="font-semibold text-gray-900">{{ transactions.total }}</span> transactions
                  </p>
                </div>
                
                <div class="flex items-center space-x-1">
                  <button 
                    @click="goToPage(transactions.current_page - 1)" 
                    :disabled="transactions.current_page <= 1" 
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7" />
                    </svg>
                    Previous
                  </button>
                  
                  <div class="hidden sm:flex items-center space-x-1 mx-2">
                    <button 
                      v-for="page in visiblePages" 
                      :key="page" 
                      @click="goToPage(page)" 
                      :class="[
                        'inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                        page === transactions.current_page 
                          ? 'bg-blue-100 text-blue-700 border border-blue-300' 
                          : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                      ]"
                    >
                      {{ page }}
                    </button>
                  </div>
                  
                  <button 
                    @click="goToPage(transactions.current_page + 1)" 
                    :disabled="transactions.current_page >= transactions.last_page" 
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    Next
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Transaction Details Modal -->
    <TransactionDetailsModal
      :show="showTransactionModal"
      :transaction="selectedTransaction"
      @close="closeTransactionModal"
    />

    <!-- Financial Summary Modal -->
    <FinancialSummaryModal
      :show="showFinancialSummary"
      @close="showFinancialSummary = false"
    />
  </SuperAdminLayout>
</template>

<script>
import { ref, onMounted, computed, getCurrentInstance } from 'vue'
import SuperAdminLayout from '../layouts/SuperAdminLayout.vue'
import TransactionDetailsModal from './TransactionDetailsModal.vue'
import FinancialSummaryModal from './FinancialSummaryModal.vue'

export default {
  name: 'ResellerTransactions',
  components: {
    SuperAdminLayout,
    TransactionDetailsModal,
    FinancialSummaryModal
  },
  setup() {
    const { proxy } = getCurrentInstance()
    const loading = ref(true)
    const transactions = ref({})
    const showTransactionModal = ref(false)
    const showFinancialSummary = ref(false)
    const selectedTransaction = ref(null)
    const searchTimeout = ref(null)

    const filters = ref({
      search: '',
      status: '',
      type: '',
      date_from: '',
      date_to: ''
    })

    const fetchTransactions = async (page = 1) => {
      loading.value = true
      try {
        const params = new URLSearchParams({
          page: page.toString(),
          ...filters.value
        })

        const response = await fetch(`/api/super-admin/reseller-transactions?${params}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json',
          }
        })

        if (!response.ok) {
          throw new Error('Failed to fetch transactions')
        }

        const data = await response.json()
        if (data.success) {
          transactions.value = data.data
        } else {
          throw new Error(data.message || 'Failed to fetch transactions')
        }
      } catch (error) {
        console.error('Error fetching transactions:', error)
        if (proxy.$toast && proxy.$toast.error) {
          proxy.$toast.error('Error fetching transactions')
        }
      } finally {
        loading.value = false
      }
    }

    const performSearch = () => {
      if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
      }
      searchTimeout.value = setTimeout(() => {
        applyFilters()
      }, 300)
    }

    const applyFilters = () => {
      fetchTransactions(1)
    }

    const viewTransaction = (transaction) => {
      selectedTransaction.value = transaction
      showTransactionModal.value = true
    }

    const closeTransactionModal = () => {
      showTransactionModal.value = false
      selectedTransaction.value = null
    }

    const processRefund = async (transaction) => {
      const result = await Swal.fire({
        title: 'Process Refund?',
        text: `Are you sure you want to refund $${parseFloat(transaction.amount).toFixed(2)} for transaction ${transaction.transaction_id}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, process refund!'
      })

      if (result.isConfirmed) {
        try {
          const response = await fetch(`/api/super-admin/reseller-transactions/${transaction.id}/refund`, {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`,
              'Accept': 'application/json',
              'Content-Type': 'application/json'
            }
          })

          const data = await response.json()
          if (data.success) {
            if (proxy.$toast && proxy.$toast.success) {
              proxy.$toast.success('Refund processed successfully')
            }
            fetchTransactions(transactions.value.current_page || 1)
          } else {
            throw new Error(data.message || 'Failed to process refund')
          }
        } catch (error) {
          console.error('Error processing refund:', error)
          if (proxy.$toast && proxy.$toast.error) {
            proxy.$toast.error('Error processing refund')
          }
        }
      }
    }

    const goToPage = (page) => {
      if (page >= 1 && page <= transactions.value.last_page) {
        fetchTransactions(page)
      }
    }

    const visiblePages = computed(() => {
      const current = transactions.value.current_page || 1
      const last = transactions.value.last_page || 1
      const pages = []
      
      let start = Math.max(1, current - 2)
      let end = Math.min(last, current + 2)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    })

    const getStatusClasses = (status) => {
      const classes = {
        'completed': 'bg-green-50 text-green-700 ring-green-600/20',
        'pending': 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
        'failed': 'bg-red-50 text-red-700 ring-red-600/20',
        'refunded': 'bg-purple-50 text-purple-700 ring-purple-600/20',
        'cancelled': 'bg-gray-50 text-gray-700 ring-gray-600/20'
      }
      return classes[status] || 'bg-gray-50 text-gray-700 ring-gray-600/20'
    }

    const getStatusDotClasses = (status) => {
      const classes = {
        'completed': 'text-green-400',
        'pending': 'text-yellow-400',
        'failed': 'text-red-400',
        'refunded': 'text-purple-400',
        'cancelled': 'text-gray-400'
      }
      return classes[status] || 'text-gray-400'
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    onMounted(() => {
      fetchTransactions()
    })

    return {
      loading,
      transactions,
      showTransactionModal,
      showFinancialSummary,
      selectedTransaction,
      filters,
      fetchTransactions,
      performSearch,
      applyFilters,
      viewTransaction,
      closeTransactionModal,
      processRefund,
      goToPage,
      visiblePages,
      getStatusClasses,
      getStatusDotClasses,
      formatDate
    }
  }
}
</script>

