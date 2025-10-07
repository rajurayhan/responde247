<template>
  <div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800">Current Usage</h3>
      <button 
        @click="refreshUsage" 
        class="text-blue-600 hover:text-blue-800"
        :disabled="loading"
      >
        <svg class="w-5 h-5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading && !usage" class="animate-pulse">
      <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
      <div class="h-20 bg-gray-200 rounded mb-4"></div>
      <div class="h-4 bg-gray-200 rounded w-1/2"></div>
    </div>

    <!-- No Active Period -->
    <div v-else-if="!usage.has_active_period" class="text-center py-8 text-gray-500">
      <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
      </svg>
      <p>{{ usage.message || 'No active billing period' }}</p>
    </div>

    <!-- Usage Data -->
    <div v-else>
      <!-- Usage Bar -->
      <div class="mb-6">
        <div class="flex justify-between mb-2">
          <span class="text-sm text-gray-600">Usage This Period</span>
          <span class="text-sm font-semibold" :class="getUsageColor(usage.usage_percentage)">
            {{ usage.usage_percentage.toFixed(1) }}%
          </span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
          <div 
            class="h-full rounded-full transition-all duration-500"
            :class="getUsageBarColor(usage.usage_percentage)"
            :style="{ width: Math.min(usage.usage_percentage, 100) + '%' }"
          ></div>
        </div>
        <div v-if="!usage.is_unlimited" class="flex justify-between mt-1 text-xs text-gray-500">
          <span>{{ usage.total_calls }} calls</span>
          <span>{{ usage.monthly_minutes_limit }} min limit</span>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="bg-gray-50 rounded-lg p-3">
          <p class="text-xs text-gray-500 mb-1">Total Calls</p>
          <p class="text-2xl font-bold text-gray-800">{{ usage.total_calls }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
          <p class="text-xs text-gray-500 mb-1">Duration</p>
          <p class="text-2xl font-bold text-gray-800">{{ usage.total_duration_minutes }}m</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
          <p class="text-xs text-gray-500 mb-1">Total Cost</p>
          <p class="text-2xl font-bold text-gray-800">{{ usage.formatted_total_cost }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
          <p class="text-xs text-gray-500 mb-1">Days Left</p>
          <p class="text-2xl font-bold text-gray-800">{{ usage.days_remaining }}</p>
        </div>
      </div>

      <!-- Overage Alert -->
      <div v-if="usage.total_overage > 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
        <div class="flex items-start">
          <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
          </svg>
          <div>
            <p class="text-sm font-semibold text-yellow-800">Pending Overage</p>
            <p class="text-xs text-yellow-700 mt-1">
              {{ usage.formatted_overage_cost }} overage
              <span v-if="usage.overage_status === 'pending'"> - Will be billed at $10</span>
            </p>
          </div>
        </div>
      </div>

      <!-- Period Info -->
      <div class="mt-4 pt-4 border-t border-gray-200">
        <div class="flex justify-between text-xs text-gray-500">
          <span>Period: {{ formatDate(usage.period_start) }} - {{ formatDate(usage.period_end) }}</span>
          <router-link to="/usage-history" class="text-blue-600 hover:text-blue-800">
            View History →
          </router-link>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="mt-4 bg-red-50 border border-red-200 rounded-lg p-3">
      <p class="text-sm text-red-800">{{ error }}</p>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'UsageWidget',
  data() {
    return {
      usage: null,
      loading: true,
      error: null,
    };
  },
  mounted() {
    this.fetchUsage();
    // Auto-refresh every 5 minutes
    this.interval = setInterval(() => {
      this.fetchUsage();
    }, 300000);
  },
  beforeUnmount() {
    if (this.interval) {
      clearInterval(this.interval);
    }
  },
  methods: {
    async fetchUsage() {
      try {
        this.loading = true;
        this.error = null;
        const response = await axios.get('/api/reseller/usage/current');
        if (response.data.success) {
          this.usage = response.data.data;
        } else {
          this.error = response.data.message || 'Failed to fetch usage data';
        }
      } catch (err) {
        this.error = 'Error loading usage data';
        console.error('Error fetching usage:', err);
      } finally {
        this.loading = false;
      }
    },
    refreshUsage() {
      this.fetchUsage();
    },
    getUsageColor(percentage) {
      if (percentage >= 100) return 'text-red-600';
      if (percentage >= 90) return 'text-orange-600';
      if (percentage >= 75) return 'text-yellow-600';
      return 'text-green-600';
    },
    getUsageBarColor(percentage) {
      if (percentage >= 100) return 'bg-red-500';
      if (percentage >= 90) return 'bg-orange-500';
      if (percentage >= 75) return 'bg-yellow-500';
      return 'bg-green-500';
    },
    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    },
  },
};
</script>

