<template>
  <AppLayout title="Dashboard">
    <!-- Alert Banner -->
    <AlertBanner v-if="stats.clients_unpaid > 0" type="warning">
      <strong>{{ stats.clients_unpaid }} client{{ stats.clients_unpaid > 1 ? 's' : '' }}</strong>
      have unpaid balances past the grace period — penalty fees applicable.
      <Link href="/debts?status=active" class="alert-link">Review now →</Link>
    </AlertBanner>

    <!-- Period Tabs -->
    <div class="top-row">
      <TimePeriodSelector
        :tabs="tabs"
        :activePeriod="activePeriod"
        :loading="loading"
        @select="switchPeriod"
      />
      <div class="top-row-right">
        <span class="period-label">{{ periodLabel }}</span>
        <button class="export-btn" @click="exportDashboard" title="Export monthly report (CSV)">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export
        </button>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
      <StatCard
        label="Total Collected"
        :value="formatTZS(stats.total_collected)"
        :trend="(stats.collected_change || 0) >= 0 ? 'up' : 'down'"
        :trendValue="stats.collected_change"
        sub="vs previous period"
        accent="green"
      />
      <StatCard
        label="Transactions"
        :value="(stats.total_transactions || 0).toLocaleString()"
        :trend="(stats.tx_change || 0) >= 0 ? 'up' : 'down'"
        :trendValue="stats.tx_change"
        sub="vs previous period"
        accent="blue"
      />
      <StatCard
        label="Outstanding Debt"
        :value="formatTZS(stats.total_outstanding)"
        sub="Total unpaid balance"
        accent="amber"
      />
      <StatCard
        label="Penalty Due"
        :value="formatTZS(stats.total_penalties)"
        sub="This year"
        accent="red"
      />
    </div>

    <!-- Quick Actions -->
    <QuickActionGrid :actions="quickActions" @action-click="handleQuickAction" />

    <!-- Charts -->
    <div class="charts-section">
      <div class="card">
        <div class="card-head">
          <span class="card-title">Revenue Trend</span>
          <Link href="/reports/monthly" class="see-all">See all →</Link>
        </div>
        <div class="chart-wrap">
          <BarChart v-if="trendReady" :data="trendChartData" :options="trendChartOptions" />
          <div v-else class="chart-placeholder">
            <span v-if="loading">Loading chart data...</span>
            <span v-else>Chart data unavailable</span>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-head">
          <span class="card-title">Payment Distribution</span>
        </div>
        <div class="chart-wrap chart-wrap--sm">
          <DoughnutChart v-if="bandReady" :data="doughnutData" :options="doughnutOptions" />
          <div v-else class="chart-placeholder">
            <span v-if="loading">Loading chart data...</span>
            <span v-else>Chart data unavailable</span>
          </div>
        </div>
        <div class="band-total">
          <div class="bt-val">{{ bandTotal }} transactions</div>
          <div class="bt-label">Total volume</div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="row-2">
      <div class="card">
        <div class="card-head">
          <span class="card-title">Recent Transactions</span>
          <Link href="/transactions" class="see-all">See all →</Link>
        </div>
        <div v-if="recentTransactions.length" class="transaction-list">
          <TransactionRow v-for="tx in recentTransactions" :key="tx.id" :transaction="tx" />
        </div>
        <div v-else class="empty-state">
          <p>No recent transactions</p>
          <Link href="/transactions/import" class="link">Import transactions</Link>
        </div>
      </div>
      <div class="card">
        <div class="card-head">
          <span class="card-title">Collector Performance</span>
          <Link href="/reports/collector" class="see-all">See all →</Link>
        </div>
        <div v-if="collectors.length" class="collector-list">
          <CollectorProgressRow v-for="collector in collectors.slice(0, 5)" :key="collector.id" :collector="collector" />
          <div v-if="collectors.length > 5" class="target-note">
            +{{ collectors.length - 5 }} more · <strong>{{ collectors.length }}</strong> collectors tracked this period
          </div>
        </div>
        <div v-else class="empty-state">
          <p>No collector data available</p>
        </div>
      </div>
    </div>

    <!-- Schedule -->
    <div v-if="weekSchedule.length" class="section-block">
      <div class="section-head">
        <span class="section-title">This Week's Schedule</span>
        <Link href="/schedules" class="see-all">See all →</Link>
      </div>
      <div class="row-3">
        <ScheduleCard v-for="schedule in weekSchedule.slice(0, 3)" :key="schedule.id" :schedule="schedule" />
      </div>
    </div>

    <!-- Bottom Stats -->
    <div class="bottom-stats">
      <div class="bs-item">
        <span class="bs-label">Active Clients</span>
        <span class="bs-val">{{ totals.active_clients }}</span>
        <span class="bs-sub">of {{ totals.total_clients }} total</span>
      </div>
      <div class="bs-item">
        <span class="bs-label">Collection Rate</span>
        <span class="bs-val" :class="rateColor(stats.collection_rate)">{{ stats.collection_rate }}%</span>
        <div class="rate-bar">
          <div class="rate-fill" :style="{ width: Math.min(stats.collection_rate, 100) + '%' }"></div>
        </div>
      </div>
      <div class="bs-item">
        <span class="bs-label">Monthly Target</span>
        <span class="bs-val">{{ formatTZS(totals.monthly_target) }}</span>
        <span class="bs-sub">for all collectors</span>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AlertBanner from '@/Components/AlertBanner.vue'
import StatCard from '@/Components/StatCard.vue'
import TimePeriodSelector from '@/Components/TimePeriodSelector.vue'
import QuickActionGrid from '@/Components/QuickActionGrid.vue'
import TransactionRow from '@/Components/TransactionRow.vue'
import CollectorProgressRow from '@/Components/CollectorProgressRow.vue'
import ScheduleCard from '@/Components/ScheduleCard.vue'
import BarChart from '@/Components/BarChart.vue'
import DoughnutChart from '@/Components/DoughnutChart.vue'

const props = defineProps({
  period:      { type: String, default: 'monthly' },
  periodLabel: { type: String, default: '' },
  stats:       { type: Object, default: () => ({}) },
  chartData:   { type: Object, default: () => ({}) },
  bandData:    { type: Object, default: () => ({}) },
  recentTransactions: { type: Array, default: () => [] },
  collectors:  { type: Array, default: () => [] },
  weekSchedule:{ type: Array, default: () => [] },
  totals:      { type: Object, default: () => ({}) },
})

const emit = defineEmits(['period-changed'])

const activePeriod = ref(props.period)
const loading = ref(false)

const tabs = computed(() => [
  { key: 'weekly',  label: 'This Week' },
  { key: 'monthly', label: 'This Month' },
  { key: 'yearly',  label: 'This Year' },
])

const trendReady = computed(() =>
  props.chartData?.labels?.length > 0 && props.chartData?.amounts?.length > 0
)

const bandReady = computed(() =>
  props.bandData?.labels?.length > 0 && props.bandData?.counts?.length > 0
)

const bandTotal = computed(() =>
  props.bandData?.counts?.reduce((sum, c) => sum + c, 0) || 0
)

const quickActions = computed(() => [
  { id: 'debts',        label: 'Review Debts',       variant: 'primary', icon: 'AlertTriangleIcon', badge: props.stats?.clients_unpaid > 0 ? props.stats.clients_unpaid : null },
  { id: 'collectors',   label: 'Collector Reports',  variant: 'primary', icon: 'UserIcon' },
  { id: 'transactions', label: 'New Transaction',    variant: 'primary', icon: 'PlusIcon' },
  { id: 'import',       label: 'Import Data',        variant: 'primary', icon: 'UploadIcon' },
])

const trendChartData = computed(() => {
  if (!trendReady.value) return { labels: [], datasets: [] }
  return {
    labels: props.chartData.labels,
    datasets: [{
      label: 'Revenue',
      data: props.chartData.amounts,
      backgroundColor: 'rgba(34, 197, 94, 0.5)',
      borderColor: 'rgb(34, 197, 94)',
      borderRadius: 4,
      borderSkipped: false,
    }],
  }
})

const trendChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: { callbacks: { label: ctx => `TZS ${ctx.parsed.y.toLocaleString()}` } },
  },
  scales: {
    x: { grid: { display: false }, ticks: { color: '#6b7280' } },
    y: {
      grid: { color: '#e5e7eb' },
      ticks: { color: '#6b7280', callback: v => 'TZS ' + v.toLocaleString() },
    },
  },
}))

const doughnutData = computed(() => {
  if (!bandReady.value) return { labels: [], datasets: [] }
  const colors = ['rgb(34,197,94)', 'rgb(251,146,60)', 'rgb(239,68,68)', 'rgb(59,130,246)', 'rgb(168,85,247)']
  return {
    labels: props.bandData.labels,
    datasets: [{ data: props.bandData.counts, backgroundColor: colors.slice(0, props.bandData.labels.length), borderWidth: 1 }],
  }
})

const doughnutOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  cutout: '70%',
  plugins: {
    legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true, pointStyle: 'circle' } },
    tooltip: {
      callbacks: {
        label: ctx => {
          const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0)
          return `${ctx.label}: ${Math.round((ctx.parsed / total) * 100)}% (${ctx.parsed} txns)`
        },
      },
    },
  },
}))

function switchPeriod(period) {
  if (loading.value || activePeriod.value === period) return
  loading.value = true
  activePeriod.value = period
  emit('period-changed', period)
  router.get('/dashboard', { period }, { preserveState: true, onFinish: () => { loading.value = false } })
}

function exportDashboard() {
  window.location.href = '/dashboard/export-monthly'
}

function formatTZS(amount) {
  if (!amount) return 'TZS 0.00'
  return 'TZS ' + amount.toLocaleString('en-TZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function changeLabel(change, prefix) {
  if (change === null || change === undefined) return ''
  const arrow = change >= 0 ? '↑' : '↓'
  return `${prefix} ${arrow} ${Math.abs(change).toFixed(1)}%`
}

// Kept for potential reuse in custom labels
// eslint-disable-next-line unused-imports/no-unused-vars

function rateColor(rate) {
  if (rate >= 90) return 'green'
  if (rate >= 80) return 'amber'
  return 'red'
}

function handleQuickAction(action) {
  const routes = {
    debts: '/debts?status=active',
    collectors: '/reports/collector',
    transactions: '/transactions/create',
    import: '/transactions/import',
  }
  if (routes[action.id]) router.visit(routes[action.id])
}

watch(() => props.period, (newPeriod) => {
  if (newPeriod && newPeriod !== activePeriod.value) activePeriod.value = newPeriod
})
</script>

<style scoped>
.top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; gap: 1rem; flex-wrap: wrap; }
.top-row-right { display: flex; align-items: center; gap: 1rem; }
.period-label { font-size: 0.875rem; color: rgb(107,114,128); font-weight: 500; }
.export-btn {
  display: inline-flex; align-items: center; gap: 0.375rem;
  padding: 0.5rem 0.875rem; background: white; border: 1px solid rgb(229,231,235);
  border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 600; color: #2d7a50;
  cursor: pointer; transition: all 0.15s;
}
.export-btn:hover { background: #f0faf3; border-color: #a8ddb8; }
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
.charts-section { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
.card { background: white; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem; }
.card-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
.card-title { font-size: 1.125rem; font-weight: 600; color: rgb(31,41,55); }
.see-all { font-size: 0.875rem; color: rgb(59,130,246); text-decoration: none; font-weight: 500; }
.see-all:hover { text-decoration: underline; }
.chart-wrap { position: relative; height: 200px; }
.chart-wrap--sm { height: 150px; }
.chart-placeholder { display: flex; align-items: center; justify-content: center; height: 100%; color: rgb(107,114,128); font-size: 0.875rem; }
.band-total { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgb(229,231,235); text-align: center; }
.bt-val { font-size: 1.5rem; font-weight: 700; color: rgb(31,41,55); }
.bt-label { font-size: 0.75rem; color: rgb(107,114,128); margin-top: 0.25rem; }
.target-note { font-size: 0.875rem; color: rgb(107,114,128); margin-top: 0.5rem; text-align: center; }
.transaction-list { display: flex; flex-direction: column; gap: 0.75rem; }
.empty-state { text-align: center; padding: 2rem; color: rgb(107,114,128); }
.empty-state .link { color: rgb(59,130,246); text-decoration: none; font-weight: 500; }
.section-block { margin-bottom: 2rem; }
.section-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.section-title { font-size: 1.125rem; font-weight: 600; color: rgb(31,41,55); }
.row-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
.row-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
.bottom-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
.bs-item { background: rgb(249,250,251); border-radius: 0.5rem; padding: 1rem; text-align: center; }
.bs-label { font-size: 0.75rem; color: rgb(107,114,128); display: block; margin-bottom: 0.25rem; }
.bs-val { font-size: 1.25rem; font-weight: 600; color: rgb(31,41,55); display: block; margin-bottom: 0.25rem; }
.bs-val.green { color: rgb(34,197,94); }
.bs-val.amber { color: rgb(251,146,60); }
.bs-val.red { color: rgb(239,68,68); }
.bs-sub { font-size: 0.75rem; color: rgb(107,114,128); }
.rate-bar { background: rgb(229,231,235); height: 0.375rem; border-radius: 0.1875rem; margin-top: 0.5rem; overflow: hidden; }
.rate-fill { background: rgb(34,197,94); height: 100%; border-radius: 0.1875rem; transition: width 1s ease; }
.alert-link { color: #92400e; font-weight: 600; text-decoration: underline; margin-left: 4px; }
@media (max-width: 1200px) {
  .kpi-grid { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
  .charts-section, .row-2 { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
  .row-3, .bottom-stats { grid-template-columns: 1fr; }
}
</style>
