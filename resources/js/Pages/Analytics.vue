<template>
  <AppLayout title="Analytics">
    <div class="analytics-container">
      <div class="header">
        <div>
          <h1>Analytics</h1>
          <p>Detailed analysis and reports — {{ monthLabel }}</p>
        </div>
        <button class="export-btn" @click="exportAnalytics">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export CSV
        </button>
      </div>

      <!-- Month/Year Selector + Comparison -->
      <div class="controls-bar">
        <div class="control-group">
          <label class="comparison-label">Month</label>
          <select v-model.number="selectedMonth" class="comparison-select" @change="changeMonthYear">
            <option v-for="m in 12" :key="m" :value="m">{{ monthName(m) }}</option>
          </select>
        </div>
        <div class="control-group">
          <label class="comparison-label">Year</label>
          <select v-model.number="selectedYear" class="comparison-select" @change="changeMonthYear">
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <div class="control-group">
          <label class="comparison-label">Compare with:</label>
          <select v-model="comparePeriod" class="comparison-select" @change="applyComparison">
            <option value="">Previous month (default)</option>
            <option value="previous_year">Same month last year</option>
          </select>
        </div>
      </div>

      <!-- KPI Cards -->
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-label">Total Revenue</div>
          <div class="kpi-value">{{ formatCurrency(metrics.totalRevenue || 0) }}</div>
          <div class="kpi-change" :class="(metrics.revenueChange || 0) >= 0 ? 'kpi-change--positive' : 'kpi-change--negative'">
            {{ (metrics.revenueChange || 0) >= 0 ? '↑' : '↓' }} {{ Math.abs(metrics.revenueChange || 0).toFixed(1) }}% vs last period
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
          <div class="kpi-value">{{ (metrics.activeClients || 0).toLocaleString() }}</div>
          <div class="kpi-change kpi-change--neutral">+{{ metrics.newClients || 0 }} new this month</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Outstanding Debt</div>
          <div class="kpi-value red-text">{{ formatCurrency(metrics.outstandingDebt || 0) }}</div>
          <div class="kpi-change" :class="(metrics.debtChange || 0) <= 0 ? 'kpi-change--positive' : 'kpi-change--negative'">
            {{ (metrics.debtChange || 0) >= 0 ? '↑' : '↓' }} {{ Math.abs(metrics.debtChange || 0).toFixed(1) }}% vs last period
          </div>
        </div>
      </div>

      <!-- Revenue Type Breakdown -->
      <div class="revenue-type-section">
        <div class="section-head-row">
          <h3>Revenue by Type</h3>
          <span class="hint">TZS 200 payments are classified as Ushuru wa Mnada Soko la Kikundi, not household waste fees</span>
        </div>
        <div class="revenue-type-grid">
          <div class="revenue-type-card rt-household">
            <div class="rt-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
              </svg>
            </div>
            <div class="rt-info">
              <div class="rt-label">Household Waste Fees</div>
              <div class="rt-value green-text">{{ formatCurrency(metrics.householdWasteRevenue || 0) }}</div>
              <div class="rt-sub">{{ (metrics.householdWasteCount || 0).toLocaleString() }} payments</div>
            </div>
            <div class="rt-share">
              {{ revenueShare(metrics.householdWasteRevenue) }}%
            </div>
          </div>
          <div class="revenue-type-card rt-market">
            <div class="rt-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .414.336.75.75.75z"/>
              </svg>
            </div>
            <div class="rt-info">
              <div class="rt-label">Ushuru wa Mnada Soko la Kikundi</div>
              <div class="rt-value amber-text">{{ formatCurrency(metrics.marketLevyRevenue || 0) }}</div>
              <div class="rt-sub">{{ (metrics.marketLevyCount || 0).toLocaleString() }} payments · TZS 200 each</div>
            </div>
            <div class="rt-share">
              {{ revenueShare(metrics.marketLevyRevenue) }}%
            </div>
          </div>
        </div>
        <!-- Share bar -->
        <div class="share-bar">
          <div class="share-fill share-fill--household" :style="{ width: revenueShare(metrics.householdWasteRevenue) + '%' }"></div>
          <div class="share-fill share-fill--market" :style="{ width: revenueShare(metrics.marketLevyRevenue) + '%' }"></div>
        </div>
      </div>

      <!-- Charts -->
      <div class="charts-section">
        <div class="chart-card">
          <div class="chart-header"><h3>Revenue Trend (Last 12 Months)</h3></div>
          <div class="chart-container chart-container--tall">
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

      <!-- Payment Methods -->
      <div v-if="paymentMethods.length" class="chart-card methods-section">
        <div class="chart-header"><h3>Payment Methods</h3></div>
        <div class="methods-grid">
          <div v-for="pm in paymentMethods" :key="pm.method" class="method-item">
            <span class="method-name">{{ prettyMethod(pm.method) }}</span>
            <span class="method-total">{{ formatCurrency(pm.total) }}</span>
            <span class="method-count">{{ pm.count }} txns</span>
          </div>
        </div>
      </div>

      <!-- Top Collectors Table -->
      <div class="data-table-section">
        <div class="table-header">
          <h3>Top Performing Collectors</h3>
          <button class="export-btn export-btn--ghost" @click="router.visit('/reports/collector')">View Full Report</button>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Collector</th>
              <th>Zone</th>
              <th>Transactions</th>
              <th>Amount Collected</th>
              <th>Share</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(collector, i) in topCollectors" :key="collector.name">
              <td class="rank-cell" :class="`rank-${i + 1}`">{{ i + 1 }}</td>
              <td class="name-cell">{{ collector.name }}</td>
              <td>{{ collector.zone || '—' }}</td>
              <td>{{ (collector.transactions || 0).toLocaleString() }}</td>
              <td class="amount-cell">{{ formatCurrency(collector.collections || 0) }}</td>
              <td>
                <div class="share-cell">
                  <div class="mini-bar"><div class="mini-fill" :style="{ width: shareOf(collector.collections) + '%' }"></div></div>
                  <span>{{ shareOf(collector.collections).toFixed(1) }}%</span>
                </div>
              </td>
            </tr>
            <tr v-if="topCollectors.length === 0">
              <td colspan="6" style="text-align:center; color:#4a6357;">No collector data available for this month</td>
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
  period:           { type: Object, default: () => ({ month: new Date().getMonth() + 1, year: new Date().getFullYear() }) },
  comparePeriod:    { type: Object, default: null },
})

const selectedMonth = ref(props.period.month)
const selectedYear = ref(props.period.year)
const comparePeriod = ref('')

const years = Array.from({ length: 6 }, (_, i) => new Date().getFullYear() - i)

const monthLabel = computed(() =>
  `${monthName(selectedMonth.value)} ${selectedYear.value}`
)

const totalRevenue = computed(() => props.metrics.totalRevenue || 0)

function revenueShare(amount) {
  const total = totalRevenue.value
  if (!total || total <= 0) return 0
  return Math.round(((amount || 0) / total) * 100)
}

function shareOf(collections) {
  if (!totalRevenue.value) return 0
  return ((collections || 0) / totalRevenue.value) * 100
}

function monthName(m) {
  return new Date(2000, m - 1, 1).toLocaleString('en-US', { month: 'long' })
}

function prettyMethod(method) {
  const names = {
    cash: 'Cash',
    mobile_money: 'Mobile Money',
    bank: 'Bank Transfer',
  }
  return names[method] || (method ? method.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '—')
}

// Stacked bar: household waste vs market levy per month
const trendChartData = computed(() => ({
  labels: props.revenueTrend.map(t => t.month),
  datasets: [
    {
      label: 'Household Waste Fees',
      data: props.revenueTrend.map(t => t.household_waste ?? t.revenue),
      backgroundColor: 'rgba(16, 185, 129, 0.75)',
      borderColor: '#10b981',
      borderWidth: 1,
      borderRadius: 3,
    },
    {
      label: 'Ushuru wa Mnada (Market Levy)',
      data: props.revenueTrend.map(t => t.market_levy ?? 0),
      backgroundColor: 'rgba(245, 158, 11, 0.75)',
      borderColor: '#f59e0b',
      borderWidth: 1,
      borderRadius: 3,
    },
  ],
}))

const trendChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom', labels: { padding: 14, usePointStyle: true, pointStyle: 'circle', font: { size: 11 } } },
    tooltip: {
      callbacks: {
        label: ctx => ` ${ctx.dataset.label}: TZS ${ctx.parsed.y.toLocaleString()}`,
      },
    },
  },
  scales: {
    x: { stacked: true, grid: { display: false }, ticks: { color: '#6b7280', font: { size: 10 } } },
    y: {
      stacked: true,
      grid: { color: '#eef2f0' },
      ticks: { color: '#6b7280', callback: v => v >= 1000000 ? (v / 1000000) + 'M' : v >= 1000 ? (v / 1000) + 'K' : v },
    },
  },
}))

const zoneChartData = computed(() => ({
  labels: props.collectionByZone.map(z => z.zone),
  datasets: [{
    data: props.collectionByZone.map(z => z.amount),
    backgroundColor: ['#10b981', '#f59e0b', '#3b82f6', '#8b5cf6', '#ef4444', '#06b6d4', '#84cc16', '#f97316'],
    borderWidth: 1,
  }],
}))

const zoneChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, pointStyle: 'circle', font: { size: 10 } } } },
}))

function changeMonthYear() {
  router.get('/analytics', {
    month: selectedMonth.value,
    year: selectedYear.value,
    ...(comparePeriod.value ? { compare: comparePeriod.value } : {}),
  }, { preserveState: true })
}

function applyComparison() {
  router.get('/analytics', {
    month: selectedMonth.value,
    year: selectedYear.value,
    ...(comparePeriod.value ? { compare: comparePeriod.value } : {}),
  }, { preserveState: true })
}

function exportAnalytics() {
  window.location.href = `/analytics/export?month=${selectedMonth.value}&year=${selectedYear.value}`
}

function formatCurrency(value) {
  return 'TZS ' + Number(value || 0).toLocaleString('en-TZ', { maximumFractionDigits: 0 })
}
</script>

<style scoped>
.analytics-container { padding: 20px; max-width: 1400px; margin: 0 auto; }

.header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; gap: 12px; flex-wrap: wrap; }
.header h1 { font-size: 24px; font-weight: 700; color: #1a2e24; margin-bottom: 4px; }
.header p { color: #4a6357; font-size: 14px; margin: 0; }
.export-btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; background: #4caf76; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.15s; }
.export-btn:hover { background: #2d7a50; transform: translateY(-1px); }
.export-btn--ghost { background: white; color: #2d7a50; border: 1px solid rgba(0,0,0,0.1); }
.export-btn--ghost:hover { background: #f0faf3; border-color: #a8ddb8; }

.controls-bar { display: flex; gap: 16px; align-items: end; margin-bottom: 20px; padding: 14px 16px; background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; flex-wrap: wrap; }
.control-group { display: flex; flex-direction: column; gap: 4px; }
.comparison-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #7a9489; }
.comparison-select { padding: 7px 12px; border: 1px solid rgba(0,0,0,0.12); border-radius: 6px; font-size: 13px; color: #1a2e24; background: white; min-width: 150px; }
.comparison-select:focus { outline: none; border-color: #4caf76; box-shadow: 0 0 0 2px rgba(76,175,118,0.15); }

.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px; }
.kpi-card { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 18px 20px; transition: all 0.15s; }
.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.06); }
.kpi-label { font-size: 11px; color: #7a9489; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; font-weight: 600; }
.kpi-value { font-size: 22px; font-weight: 700; color: #1a2e24; margin-bottom: 6px; }
.kpi-value.red-text { color: #c0392b; }
.kpi-change { font-size: 11px; font-weight: 500; }
.kpi-change--positive { color: #2d7a50; }
.kpi-change--negative { color: #c0392b; }
.kpi-change--neutral { color: #4a6357; }

/* Revenue type breakdown */
.revenue-type-section { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 20px; margin-bottom: 20px; }
.section-head-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 14px; flex-wrap: wrap; gap: 8px; }
.section-head-row h3 { font-size: 14px; font-weight: 600; color: #1a2e24; margin: 0; }
.hint { font-size: 11px; color: #b45309; }
.revenue-type-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
.revenue-type-card { display: flex; align-items: center; gap: 14px; padding: 16px; border-radius: 10px; }
.rt-household { background: #f0faf3; border: 1px solid #a8ddb8; }
.rt-market { background: #fef9ec; border: 1px solid #f5deb0; }
.rt-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.rt-household .rt-icon { background: #dcf2e3; color: #2d7a50; }
.rt-market .rt-icon { background: #fbeed0; color: #b45309; }
.rt-info { flex: 1; min-width: 0; }
.rt-label { font-size: 12px; font-weight: 600; color: #4a6357; margin-bottom: 3px; }
.rt-value { font-size: 19px; font-weight: 700; }
.green-text { color: #2d7a50; }
.amber-text { color: #b45309; }
.red-text { color: #c0392b; }
.rt-sub { font-size: 11px; color: #7a9489; margin-top: 2px; }
.rt-share { font-size: 17px; font-weight: 700; color: #1a2e24; }
.share-bar { height: 8px; background: #eef2f0; border-radius: 4px; overflow: hidden; display: flex; }
.share-fill { height: 100%; transition: width 0.5s ease; }
.share-fill--household { background: linear-gradient(90deg, #4caf76, #2d7a50); }
.share-fill--market { background: linear-gradient(90deg, #f5c842, #d4a520); }

.charts-section { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 20px; }
.chart-card { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 20px; }
.chart-header { margin-bottom: 16px; }
.chart-header h3 { font-size: 14px; font-weight: 600; color: #1a2e24; margin: 0; }
.chart-container { height: 250px; position: relative; }
.chart-container--tall { height: 280px; }
.chart-placeholder { height: 200px; display: flex; align-items: center; justify-content: center; background: #f0faf3; border-radius: 8px; color: #4a6357; font-size: 13px; }

.methods-section { margin-bottom: 20px; }
.methods-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
.method-item { display: flex; flex-direction: column; padding: 12px 14px; background: #f8fbf9; border: 1px solid rgba(0,0,0,0.05); border-radius: 8px; }
.method-name { font-size: 11px; color: #7a9489; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-bottom: 4px; }
.method-total { font-size: 16px; font-weight: 700; color: #1a2e24; }
.method-count { font-size: 11px; color: #7a9489; margin-top: 2px; }

.data-table-section { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 20px; }
.table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.table-header h3 { font-size: 14px; font-weight: 600; color: #1a2e24; margin: 0; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { text-align: left; padding: 10px 12px; font-size: 10px; font-weight: 700; color: #7a9489; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid rgba(0,0,0,0.08); }
.data-table td { padding: 10px 12px; font-size: 13px; color: #1a2e24; border-bottom: 1px solid rgba(0,0,0,0.04); }
.data-table tbody tr:hover { background: #f8fbf9; }
.data-table tr:last-child td { border-bottom: none; }
.rank-cell { font-weight: 700; color: #7a9489; width: 40px; }
.rank-1 { color: #d4a520; }
.rank-2 { color: #8a9ba8; }
.rank-3 { color: #b08d57; }
.name-cell { font-weight: 600; }
.amount-cell { font-weight: 700; font-variant-numeric: tabular-nums; }
.share-cell { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #4a6357; }
.mini-bar { flex: 1; max-width: 90px; height: 6px; background: #eef2f0; border-radius: 3px; overflow: hidden; }
.mini-fill { height: 100%; background: linear-gradient(90deg, #4caf76, #2d7a50); border-radius: 3px; transition: width 0.4s ease; }

@media (max-width: 1024px) {
  .charts-section { grid-template-columns: 1fr; }
  .revenue-type-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
  .controls-bar { flex-direction: column; align-items: stretch; }
}
</style>
