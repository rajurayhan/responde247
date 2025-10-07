<template>
  <div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-800">Usage History</h3>
      <select 
        v-model="limit" 
        @change="fetchHistory"
        class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
      >
        <option :value="6">Last 6 months</option>
        <option :value="12">Last 12 months</option>
        <option :value="24">Last 24 months</option>
      </select>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="animate-pulse">
      <div class="h-64 bg-gray-200 rounded mb-4"></div>
      <div class="grid grid-cols-4 gap-4">
        <div v-for="i in 4" :key="i" class="h-16 bg-gray-200 rounded"></div>
      </div>
    </div>

    <!-- Chart -->
    <div v-else-if="history && history.periods.length > 0">
      <!-- Simple Bar Chart -->
      <div class="mb-6">
        <div class="flex items-end justify-between h-64 space-x-2">
          <div 
            v-for="(period, index) in history.periods" 
            :key="index"
            class="flex-1 flex flex-col items-center group relative"
          >
            <!-- Bar -->
            <div class="w-full flex flex-col justify-end" style="height: 240px;">
              <!-- Cost Bar -->
              <div 
                class="w-full bg-blue-500 rounded-t hover:bg-blue-600 transition-colors cursor-pointer"
                :style="{ height: getBarHeight(period.cost, maxCost) }"
                @mouseenter="showTooltip(index, period)"
                @mouseleave="hideTooltip"
              ></div>
              <!-- Overage Bar -->
              <div 
                v-if="period.overage > 0"
                class="w-full bg-red-500 hover:bg-red-600 transition-colors cursor-pointer"
                :style="{ height: getBarHeight(period.overage, maxCost) }"
              ></div>
            </div>
            
            <!-- Label -->
            <div class="mt-2 text-xs text-gray-600 text-center transform -rotate-45 origin-left">
              {{ period.period }}
            </div>

            <!-- Tooltip -->
            <div 
              v-if="tooltip.show && tooltip.index === index"
              class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white text-xs rounded-lg p-3 shadow-lg z-10 whitespace-nowrap"
            >
              <div class="font-semibold mb-1">{{ period.period }}</div>
              <div class="space-y-1">
                <div>Calls: {{ period.calls }}</div>
                <div>Duration: {{ period.duration_minutes }}m</div>
                <div>Cost: ${{ period.cost.toFixed(2) }}</div>
                <div v-if="period.overage > 0" class="text-red-300">
                  Overage: ${{ period.overage.toFixed(2) }}
                </div>
              </div>
              <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                <div class="border-4 border-transparent border-t-gray-900"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Legend -->
        <div class="flex justify-center space-x-4 mt-6 text-sm">
          <div class="flex items-center">
            <div class="w-4 h-4 bg-blue-500 rounded mr-2"></div>
            <span>Usage Cost</span>
          </div>
          <div class="flex items-center">
            <div class="w-4 h-4 bg-red-500 rounded mr-2"></div>
            <span>Overage</span>
          </div>
        </div>
      </div>

      <!-- Totals Summary -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-gray-200">
        <div class="text-center">
          <p class="text-xs text-gray-500 mb-1">Total Calls</p>
          <p class="text-lg font-bold text-gray-800">{{ history.totals.total_calls.toLocaleString() }}</p>
        </div>
        <div class="text-center">
          <p class="text-xs text-gray-500 mb-1">Total Minutes</p>
          <p class="text-lg font-bold text-gray-800">{{ history.totals.total_duration_minutes.toFixed(0) }}</p>
        </div>
        <div class="text-center">
          <p class="text-xs text-gray-500 mb-1">Total Cost</p>
          <p class="text-lg font-bold text-gray-800">${{ history.totals.total_cost.toFixed(2) }}</p>
        </div>
        <div class="text-center">
          <p class="text-xs text-gray-500 mb-1">Total Overage</p>
          <p class="text-lg font-bold text-red-600">${{ history.totals.total_overage.toFixed(2) }}</p>
        </div>
      </div>
    </div>

    <!-- No Data -->
    <div v-else class="text-center py-12 text-gray-500">
      <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
      </svg>
      <p>No usage history available</p>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
      <p class="text-sm text-red-800">{{ error }}</p>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'UsageHistoryChart',
  data() {
    return {
      history: null,
      loading: true,
      error: null,
      limit: 12,
      tooltip: {
        show: false,
        index: null,
      },
    };
  },
  computed: {
    maxCost() {
      if (!this.history || !this.history.periods.length) return 0;
      return Math.max(...this.history.periods.map(p => p.cost + p.overage));
    },
  },
  mounted() {
    this.fetchHistory();
  },
  methods: {
    async fetchHistory() {
      try {
        this.loading = true;
        this.error = null;
        const response = await axios.get('/api/reseller/usage/history', {
          params: { limit: this.limit }
        });
        if (response.data.success) {
          this.history = response.data.data;
          // Reverse to show oldest to newest
          this.history.periods = this.history.periods.reverse();
        } else {
          this.error = response.data.message || 'Failed to fetch usage history';
        }
      } catch (err) {
        this.error = 'Error loading usage history';
        console.error('Error fetching history:', err);
      } finally {
        this.loading = false;
      }
    },
    getBarHeight(value, max) {
      if (!max || !value) return '0px';
      const percentage = (value / max) * 100;
      return `${Math.max(percentage, 2)}%`;
    },
    showTooltip(index, period) {
      this.tooltip = {
        show: true,
        index,
        period,
      };
    },
    hideTooltip() {
      this.tooltip.show = false;
    },
  },
};
</script>

