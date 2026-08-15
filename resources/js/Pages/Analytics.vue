<template>
  <AppLayout title="Analytics">
    <div class="analytics-container">
      <div class="header">
        <h1>Analytics</h1>
        <p>Detailed analysis and reports</p>
      </div>

      <!-- Period Selector -->
      <div class="period-selector">
        <button
          v-for="period in periods"
          :key="period.key"
          class="period-btn"
          :class="{ 'period-btn--active': activePeriod === period.key }"
          @click="changePeriod(period.key)"
        >{{ period.label }}</button>
        <button class="export-btn" @click="exportAnalytics">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export
        </button>
      </div>

      <!-- Comparison Selector -->
      <div class="comparison-section">
        <label class="comparison-label">Compare with:</label>
        <select v-model="comparePeriod" class="comparison-select" @change="applyComparison">
          <option value="">No comparison</option>
          <option value="previous">Previous period</option>
          <option value="last_year">Same period last year</option>
        </select>
      </div>

      <!-- KPI Cards -->
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-label">Total Revenue</div>
          <div class="kpi-value">{{ formatCurrency(metrics.totalRevenue || 0) }}</div>
          <div class="kpi-change" :class="(metrics.revenueChange || 0) >= 0 ? 'kpi-change--positive' : 'kpi-change--negative'">
            {{ (metrics.revenueChange || 0) >= 0 ? '+' : '' }}{{ (metrics.revenueChange || 0).toFixed(1) }}% vs last period
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Collection Rate</div>
          <div class="kpi-value">{{ (metrics.collectionRate || 0).toFixed(1) }}%</div>
          <div class="kpi-change" :class="(metrics.collectionRateChange || 0) >= 0 ? 'kpi-change--positive' : 'kpi-change--negative'">
            {{ (metrics.collectionRateChange || 0) >= 0 ? '+' : '' }}{{ (metrics.collectionRateChange || 0).toFixed(1) }}% vs last period
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Active Clients</div>
          <div class="kpi-value">{{ metrics.activeClients || 0 }}</div>
          <div class="kpi-change kpi-change--neutral">+{{ metrics.newClients || 0 }} new clients</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Outstanding Debt</div>
          <div class="kpi-value">{{ formatCurrency(metrics.outstandingDebt || 0) }}</div>
          <div class="kpi-change" :class="(metrics.debtChange || 0) >= 0 ? 'kpi-change--negative' : 'kpi-change--positive'">
            {{ (metrics.debtChange || 0) >= 0 ? '+' : '' }}{{ (metrics.debtChange || 0).toFixed(1) }}% vs last period
          </div>
        </div>
      </div>

      <!-- Revenue Trend Chart -->
      <div class="charts-section">
        <div class="chart-card">
          <div class="chart-header"><h3>Revenue Trend (Last 12 Months)</h3></div>
          <div class="chart-container">
            <BarChart v-if="revenueTrend.length" :data="trendChartData" :options="trendChartOptions" />
            <div v-else class="chart-placeholder"><p>No revenue data available</p></div>
          </div>
        </div>
        <div class="chart-card">
          <div class="chart-header"><h3>Collection by Zone</h3></div>
          <div class="chart-container">
            <DoughnutChart v-if="collectionByZone.length" :data="zoneChartData" :options="zoneChartOptions" />
            <div v-else class="chart-placeholder"><p>No zone data available</p></div>
          </div>
        </div>
      </div>

      <!-- Top Collectors Table -->
      <div class="data-table-section">
        <div class="table-header">
          <h3>Top Performing Collectors</h3>
          <button class="export-btn" @click="router.visit('/reports')">View Full Report</button>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Collector</th>
              <th>Zone</th>
              <th>Amount Collected</th>
              <th>Share</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="collector in topCollectors" :key="collector.name">
              <td>{{ collector.name }}</td>
              <td>{{ collector.zone || '—' }}</td>
              <td>{{ formatCurrency(collector.collections || 0) }}</td>
              <td>{{ totalRevenue > 0 ? ((collector.collections / totalRevenue) * 100).toFixed(1) : 0 }}%</td>
            </tr>
            <tr v-if="topCollectors.length === 0">
              <td colspan="4" style="text-align:center; color:#4a6357;">No collector data available</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BarChart from '@/Components/BarChart.vue'
import DoughnutChart from '@/Components/DoughnutChart.vue'

const props = defineProps({
  metrics:          { type: Object, default: () => ({}) },
  compareMetrics:   { type: Object, default: null },
  revenueTrend:     { type: Array,  default: () => [] },
  collectionByZone: { type: Array,  default: () => [] },
  topCollectors:    { type: Array,  default: () => [] },
  paymentMethods:   { type: Array,  default: () => [] },
  retention:        { type: Object, default: () => ({}) },
  period:           { type: Object, default: () => ({ month: 1, year: 2024 }) },
  comparePeriod:    { type: Object, default: null },
})

const activePeriod = ref('monthly')
const comparePeriod = ref('')

const periods = [
  { key: 'daily',     label: 'Daily' },
  { key: 'weekly',    label: 'Weekly' },
  { key: 'monthly',   label: 'Monthly' },
  { key: 'quarterly', label: 'Quarterly' },
  { key: 'yearly',    label: 'Yearly' },
]

const totalRevenue = computed(() => props.metrics.totalRevenue || 0)

const trendChartData = computed(() => ({
  labels: props.revenueTrend.map(t => t.month),
  datasets: [{
    label: 'Revenue',
    data: props.revenueTrend.map(t => t.revenue),
    backgroundColor: 'rgba(16, 185, 129, 0.5)',
    borderColor: '#10b981',
    borderWidth: 2,
    borderRadius: 4,
  }],
}))

const trendChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { color: '#6b7280' } },
    y: { grid: { color: '#e5e7eb' }, ticks: { color: '#6b7280', callback: v => 'TZS ' + v.toLocaleString() } },
  },
}))

const zoneChartData = computed(() => ({
  labels: props.collectionByZone.map(z => z.zone),
  datasets: [{
    data: props.collectionByZone.map(z => z.amount),
    backgroundColor: ['#10b981', '#f59e0b', '#3b82f6', '#8b5cf6', '#ef4444', '#06b6d4'],
    borderWidth: 1,
  }],
}))

const zoneChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
}))

function changePeriod(key) {
  activePeriod.value = key
  router.get('/analytics', { period: key, month: props.period.month, year: props.period.year }, { preserveState: true })
}

function applyComparison() {
  if (!comparePeriod.value) return
  router.get('/analytics', {
    compare: comparePeriod.value,
    month: props.period.month,
    year: props.period.year,
  }, { preserveState: true })
}

function exportAnalytics() {
  window.location.href = `/analytics/export?month=${props.period.month}&year=${props.period.year}`
}

function formatCurrency(value) {
  return new Intl.NumberFormat('sw-TZ', { style: 'currency', currency: 'TZS', minimumFractionDigits: 0 }).format(value || 0)
}
</script>

<style scoped>
.analytics-container { padding: 20px; }
.header { margin-bottom: 24px; }
.header h1 { font-size: 24px; font-weight: 600; color: #1a2e24; margin-bottom: 4px; }
.header p { color: #4a6357; font-size: 14px; }
.period-selector { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
.period-btn { padding: 8px 16px; border: 1px solid rgba(0,0,0,0.08); border-radius: 8px; background: white; color: #4a6357; font-size: 13px; cursor: pointer; transition: all 0.15s; }
.period-btn:hover { border-color: #4caf76; color: #2d7a50; }
.period-btn--active { background: #4caf76; color: white; border-color: #4caf76; }
.export-btn { margin-left: auto; padding: 8px 16px; background: #4caf76; color: white; border: none; border-radius: 8px; font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 6px; }
.export-btn:hover { background: #2d7a50; }
.comparison-section { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding: 12px 16px; background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 8px; }
.comparison-label { font-size: 12px; font-weight: 500; color: #4a6357; }
.comparison-select { padding: 6px 12px; border: 1px solid rgba(0,0,0,0.08); border-radius: 6px; font-size: 12px; color: #4a6357; background: white; }
.kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.kpi-card { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 20px; }
.kpi-label { font-size: 12px; color: #4a6357; margin-bottom: 8px; }
.kpi-value { font-size: 24px; font-weight: 600; color: #1a2e24; margin-bottom: 8px; }
.kpi-change { font-size: 11px; }
.kpi-change--positive { color: #2d7a50; }
.kpi-change--negative { color: #c0392b; }
.kpi-change--neutral { color: #4a6357; }
.charts-section { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 24px; }
.chart-card { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 20px; }
.chart-header { margin-bottom: 16px; }
.chart-header h3 { font-size: 14px; font-weight: 600; color: #1a2e24; }
.chart-container { height: 250px; position: relative; }
.chart-placeholder { height: 200px; display: flex; align-items: center; justify-content: center; background: #f0faf3; border-radius: 8px; color: #4a6357; font-size: 13px; }
.data-table-section { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 20px; }
.table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.table-header h3 { font-size: 14px; font-weight: 600; color: #1a2e24; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { text-align: left; padding: 12px; font-size: 12px; font-weight: 600; color: #4a6357; border-bottom: 1px solid rgba(0,0,0,0.08); }
.data-table td { padding: 12px; font-size: 13px; color: #1a2e24; border-bottom: 1px solid rgba(0,0,0,0.04); }
.data-table tr:last-child td { border-bottom: none; }
@media (max-width: 1024px) {
  .kpi-grid { grid-template-columns: repeat(2, 1fr); }
  .charts-section { grid-template-columns: 1fr; }
}
</style>
