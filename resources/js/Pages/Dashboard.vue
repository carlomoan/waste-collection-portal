<template>
  <AppLayout title="Dashboard">

    <!-- Alert banner — only shows when there are unpaid clients -->
    <AlertBanner v-if="stats.clients_unpaid > 0" type="warning">
      <strong>{{ stats.clients_unpaid }} client{{ stats.clients_unpaid > 1 ? 's' : '' }}</strong>
      have unpaid balances past the grace period — penalty fees applicable.
      <Link href="/debts?status=active" class="alert-link">Review now →</Link>
    </AlertBanner>

    <!-- ── Period tabs + period label ─────────────────────────────────── -->
    <div class="top-row">
      <div class="tab-bar">
        <button
          v-for="tab in tabs" :key="tab.key"
          class="tab" :class="{ 'tab--active': activePeriod === tab.key }"
          :disabled="loading"
          @click="switchPeriod(tab.key)"
        >
          <span v-if="loading && activePeriod === tab.key" class="tab-spinner" />
          {{ tab.label }}
        </button>
      </div>
      <span class="period-label">{{ periodLabel }}</span>
    </div>

    <!-- ── KPI Cards ──────────────────────────────────────────────────── -->
    <div class="kpi-grid">
      <StatCard
        label="Total Collected"
        :value="formatTZS(stats.total_collected)"
        :sub="changeLabel(stats.collected_change, 'vs previous period')"
        :trend="stats.collected_change >= 0 ? 'up' : 'down'"
        accent="green"
      />
      <StatCard
        label="Transactions"
        :value="stats.total_transactions.toLocaleString()"
        :sub="changeLabel(stats.tx_change, 'vs previous period')"
        :trend="stats.tx_change >= 0 ? 'up' : 'down'"
        accent="green"
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
        :sub="`${stats.clients_unpaid} defaulting clients`"
        :trend="stats.clients_unpaid > 0 ? 'down' : null"
        accent="red"
      />
    </div>

    <!-- ── Quick Actions ──────────────────────────────────────────────── -->
    <div class="quick-actions">
      <a :href="exportUrl" class="qa-btn qa-btn--primary" title="Download monthly report as CSV">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="1.8" stroke="currentColor" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21
               18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
        </svg>
        Export Monthly Report
      </a>
      <button class="qa-btn" @click="exportDashboard" title="Export full dashboard as PDF">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="1.8" stroke="currentColor" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21
               18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
        </svg>
        Export Dashboard
      </button>

      <Link href="/debts?status=active" class="qa-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="1.8" stroke="currentColor" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71
               c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5
               -3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
        </svg>
        Flag Non-Payers
        <span v-if="stats.clients_unpaid" class="qa-badge">{{ stats.clients_unpaid }}</span>
      </Link>

      <Link href="/reports/collector" class="qa-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="1.8" stroke="currentColor" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501
               20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75
               c-2.676 0-5.216-.584-7.499-1.632Z"/>
        </svg>
        Collector Report
      </Link>

      <Link href="/transactions" class="qa-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="1.8" stroke="currentColor" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0
               2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424
               0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75
               .75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8
               0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15
               1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973
               8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25
               c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125
               -1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
        </svg>
        All Transactions
        <span class="qa-count">{{ stats.total_transactions }}</span>
      </Link>

      <Link href="/transactions/import" class="qa-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="1.8" stroke="currentColor" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21
               18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
        </svg>
        Import PDF / Excel
      </Link>
      <button class="qa-btn" @click="showAlertsModal = true">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="1.8" stroke="currentColor" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
        </svg>
        Configure Alerts
      </button>
    </div>

    <!-- ── Charts row ─────────────────────────────────────────────────── -->
    <div class="row-2">
      <!-- Trend bar chart -->
      <div class="card" :class="{ 'card--loading': loading }">
        <div class="card-head">
          <span class="card-title">
            Collection Trend —
            <span class="period-inline">{{ periodLabel }}</span>
          </span>
          <Link href="/reports/monthly" class="see-all">Full report →</Link>
        </div>
        <div class="chart-wrap">
          <Bar v-if="trendReady" :data="trendChartData" :options="trendChartOptions" />
          <div v-else class="chart-placeholder">
            <span v-if="loading">Loading chart…</span>
            <span v-else>No data for this period</span>
          </div>
        </div>
      </div>

      <!-- Amount band doughnut -->
      <div class="card" :class="{ 'card--loading': loading }">
        <div class="card-head">
          <span class="card-title">Collection by Amount Band</span>
        </div>
        <div class="band-legend">
          <span v-for="b in bandChartData.labels" :key="b" class="legend-item">
            <span class="legend-dot"
                  :style="{ background: bandColors[bandChartData.labels.indexOf(b)] }" />
            {{ b }}
          </span>
        </div>
        <div class="chart-wrap chart-wrap--sm">
          <Doughnut v-if="bandReady" :data="doughnutData" :options="doughnutOptions" />
          <div v-else class="chart-placeholder">
            <span v-if="loading">Loading…</span>
            <span v-else>No data</span>
          </div>
        </div>
        <!-- Centre total label -->
        <div class="band-total">
          <div class="bt-val">{{ stats.total_transactions }}</div>
          <div class="bt-label">transactions</div>
        </div>
      </div>
    </div>

    <!-- ── Recent transactions + Collector performance ────────────────── -->
    <div class="row-2">

      <!-- Recent transactions -->
      <div class="card">
        <div class="card-head">
          <span class="card-title">Recent Transactions</span>
          <Link href="/transactions" class="see-all">
            See all {{ stats.total_transactions }} →
          </Link>
        </div>
        <div v-if="recentTransactions.length">
          <TransactionRow
            v-for="tx in recentTransactions"
            :key="tx.controlNumber"
            :payer-name="tx.payerName"
            :control-number="tx.controlNumber"
            :amount="tx.amount"
            :status="tx.status"
            :paid-at="tx.paidAt"
          />
        </div>
        <div v-else class="empty-state">
          No transactions in this period.
          <Link href="/transactions/import" class="link">Import from PDF →</Link>
        </div>
      </div>

      <!-- Collector performance -->
      <div class="card">
        <div class="card-head">
          <span class="card-title">Collector Performance</span>
          <Link href="/reports/collector" class="see-all">Full report →</Link>
        </div>
        <div v-if="collectors.length">
          <CollectorProgressRow
            v-for="c in collectors"
            :key="c.name"
            :name="c.name"
            :collected="c.collected"
            :target="c.target"
            :transactions="c.transactions"
            :zone="c.zone"
          />
          <div class="target-note">
            <strong>Target:</strong>
            {{ formatTZS(totals.monthly_target) }} total ·
            {{ formatTZS(1200000) }} per collector
          </div>
        </div>
        <div v-else class="empty-state">No collector data available.</div>
      </div>
    </div>

    <!-- ── This week's schedule ───────────────────────────────────────── -->
    <div v-if="weekSchedule.length" class="section-block">
      <div class="section-head">
        <span class="section-title">This Week's Collection Schedule</span>
        <Link href="/schedule" class="see-all">Manage schedules →</Link>
      </div>
      <div class="row-3">
        <ScheduleCard
          v-for="sched in weekSchedule"
          :key="sched.dayLabel"
          v-bind="sched"
        />
      </div>
    </div>

    <!-- ── Bottom stats: active clients, collection rate ─────────────── -->
    <div class="bottom-stats">
      <div class="bs-item">
        <span class="bs-label">Active Clients</span>
        <span class="bs-val">{{ totals.active_clients.toLocaleString() }}</span>
        <span class="bs-sub">of {{ totals.total_clients }} registered</span>
      </div>
      <div class="bs-item">
        <span class="bs-label">Collection Rate</span>
        <span class="bs-val" :class="rateColor">{{ stats.collection_rate }}%</span>
        <div class="rate-bar">
          <div class="rate-fill" :style="{ width: Math.min(stats.collection_rate, 100) + '%' }"
               :class="rateColor" />
        </div>
      </div>
      <div class="bs-item">
        <span class="bs-label">Monthly Target</span>
        <span class="bs-val">{{ formatTZS(totals.monthly_target) }}</span>
        <span class="bs-sub">{{ collectors.length }} active collectors</span>
      </div>
    </div>

    <!-- Alerts Configuration Modal -->
    <Modal :show="showAlertsModal" @close="showAlertsModal = false" title="Configure Alerts">
      <div class="alerts-form">
        <div class="toggle-group">
          <div class="toggle-info">
            <div class="toggle-label">Unpaid Clients Alert</div>
            <div class="toggle-description">Alert when clients have unpaid balances past grace period</div>
          </div>
          <label class="toggle-switch">
            <input type="checkbox" v-model="alerts.unpaidClients">
            <span class="toggle-slider"></span>
          </label>
        </div>
        <div class="toggle-group">
          <div class="toggle-info">
            <div class="toggle-label">Low Collection Rate Alert</div>
            <div class="toggle-description">Alert when collection rate falls below threshold</div>
          </div>
          <label class="toggle-switch">
            <input type="checkbox" v-model="alerts.lowCollectionRate">
            <span class="toggle-slider"></span>
          </label>
        </div>
        <div class="form-group">
          <label>Collection Rate Threshold (%)</label>
          <input type="number" v-model="alerts.collectionThreshold" class="form-input" min="0" max="100">
        </div>
        <div class="toggle-group">
          <div class="toggle-info">
            <div class="toggle-label">Email Notifications</div>
            <div class="toggle-description">Receive alerts via email</div>
          </div>
          <label class="toggle-switch">
            <input type="checkbox" v-model="alerts.emailNotifications">
            <span class="toggle-slider"></span>
          </label>
        </div>
      </div>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showAlertsModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="saveAlerts">Save Alerts</button>
      </template>
    </Modal>

  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Bar, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale, BarElement,
  ArcElement, Tooltip, Legend,
} from 'chart.js'

import AppLayout            from '@/Layouts/AppLayout.vue'
import StatCard             from '@/Components/StatCard.vue'
import AlertBanner          from '@/Components/AlertBanner.vue'
import TransactionRow       from '@/Components/TransactionRow.vue'
import CollectorProgressRow from '@/Components/CollectorProgressRow.vue'
import ScheduleCard         from '@/Components/ScheduleCard.vue'
import Modal                from '@/Components/Modal.vue'

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Tooltip, Legend)

// ─── Props from Inertia controller ───────────────────────────────────────────
const props = defineProps({
  period:      { type: String, default: 'monthly' },
  periodLabel: { type: String, default: '' },

  stats: {
    type: Object,
    default: () => ({
      total_collected: 0, total_transactions: 0,
      total_outstanding: 0, total_penalties: 0,
      clients_unpaid: 0, collection_rate: 0,
      collected_change: 0, tx_change: 0,
    }),
  },

  chartData: {
    type: Object,
    default: () => ({ labels: [], amounts: [], counts: [] }),
  },

  bandData: {
    type: Array,
    default: () => [],
  },

  recentTransactions: { type: Array, default: () => [] },
  collectors:         { type: Array, default: () => [] },
  weekSchedule:       { type: Array, default: () => [] },

  totals: {
    type: Object,
    default: () => ({ active_clients: 0, total_clients: 0, monthly_target: 0 }),
  },
})

// ─── Period switching ─────────────────────────────────────────────────────────
const activePeriod = ref(props.period)
const loading      = ref(false)

const tabs = [
  { key: 'monthly', label: 'Monthly' },
  { key: 'weekly',  label: 'Weekly'  },
  { key: 'yearly',  label: 'Yearly'  },
]

function switchPeriod(period) {
  if (period === activePeriod.value) return
  activePeriod.value = period
  loading.value      = true

  router.get('/dashboard', { period }, {
    preserveScroll: true,
    preserveState:  false,
    onFinish: () => { loading.value = false },
  })
}

// ─── Export URL ───────────────────────────────────────────────────────────────
const exportUrl = computed(() => {
  const now = new Date()
  return `/dashboard/export-monthly?month=${now.getMonth() + 1}&year=${now.getFullYear()}`
})

// ─── Charts ───────────────────────────────────────────────────────────────────
const trendReady = computed(() =>
  (props.chartData?.amounts ?? []).some(v => v > 0)
)

const bandReady = computed(() =>
  (props.bandData ?? []).some(b => b.count > 0)
)

const trendChartData = computed(() => ({
  labels: props.chartData?.labels ?? [],
  datasets: [{
    label:           'Collected (TZS)',
    data:            props.chartData?.amounts ?? [],
    backgroundColor: '#4caf76',
    borderRadius:    4,
    borderSkipped:   false,
  }],
}))

const trendChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: ctx => 'TZS ' + ctx.parsed.y.toLocaleString(),
      },
    },
  },
  scales: {
    x: {
      grid:   { color: 'rgba(0,0,0,0.04)' },
      ticks:  { color: '#7a9489', font: { size: 10 }, maxTicksLimit: 12 },
      border: { display: false },
    },
    y: {
      grid:   { color: 'rgba(0,0,0,0.04)' },
      ticks:  {
        color: '#7a9489', font: { size: 10 },
        callback: v => v >= 1000000 ? (v / 1000000).toFixed(1) + 'M'
                     : v >= 1000   ? (v / 1000) + 'k'
                     : v,
      },
      border: { display: false },
    },
  },
}

const bandColors = ['#1a4d32', '#2d7a50', '#4caf76', '#f5c842', '#e67e22']

const bandChartData = computed(() => ({
  labels: (props.bandData ?? []).map(b => b.label),
  counts: (props.bandData ?? []).map(b => b.count),
}))

const doughnutData = computed(() => ({
  labels: bandChartData.value.labels,
  datasets: [{
    data:            bandChartData.value.counts,
    backgroundColor: bandColors,
    borderWidth:     0,
    hoverOffset:     4,
  }],
}))

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '65%',
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: ctx => {
          const band = props.bandData?.[ctx.dataIndex]
          return `${ctx.label}: ${ctx.parsed} transactions (${band?.percent ?? 0}%)`
        },
      },
    },
  },
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
const formatTZS = v =>
  new Intl.NumberFormat('sw-TZ', { minimumFractionDigits: 0 }).format(v ?? 0)

// ─── Alerts Configuration ───────────────────────────────────────────────────────
const showAlertsModal = ref(false)
const alerts = ref({
  unpaidClients: true,
  lowCollectionRate: true,
  collectionThreshold: 70,
  emailNotifications: true,
})

const exportDashboard = () => {
  window.print()
}

const saveAlerts = () => {
  router.post('/dashboard/alerts', alerts.value, {
    onSuccess: () => {
      showAlertsModal.value = false
    }
  })
}

const changeLabel = (pct, suffix) => {
  if (pct === null || pct === undefined) return suffix
  const sign = pct >= 0 ? '+' : ''
  return `${sign}${pct}% ${suffix}`
}

const rateColor = computed(() => {
  const r = props.stats.collection_rate ?? 0
  if (r >= 80) return 'green'
  if (r >= 50) return 'amber'
  return 'red'
})
</script>

<style scoped>
/* Top row: tabs + label */
.top-row {
  display: flex; align-items: center; gap: 14px; margin-bottom: 16px; flex-wrap: wrap;
}
.tab-bar {
  display: flex; gap: 2px; background: #f0faf3;
  border: 1px solid rgba(0,0,0,0.08); border-radius: 8px; padding: 3px;
}
.tab {
  padding: 5px 16px; border-radius: 6px; font-size: 12px;
  cursor: pointer; color: #4a6357; background: none; border: none;
  transition: all 0.15s; display: flex; align-items: center; gap: 5px;
}
.tab:disabled { cursor: wait; opacity: 0.7; }
.tab--active  { background: #ffffff; color: #1a2e24; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
.tab-spinner  {
  width: 10px; height: 10px; border: 2px solid rgba(0,0,0,0.15);
  border-top-color: #4caf76; border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.period-label { font-size: 12px; color: #7a9489; }

/* KPI grid */
.kpi-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 10px; margin-bottom: 16px; }

/* Quick actions */
.quick-actions { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
.qa-btn {
  background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 8px;
  padding: 8px 12px; font-size: 11px; color: #4a6357; cursor: pointer;
  display: flex; align-items: center; gap: 6px; text-decoration: none;
  transition: all 0.15s; white-space: nowrap;
}
.qa-btn:hover { border-color: #4caf76; color: #2d7a50; }
.qa-btn--primary { background: #f0faf3; border-color: #a8ddb8; color: #2d7a50; font-weight: 500; }
.qa-btn--primary:hover { background: #2d7a50; color: #fff; }
.qa-badge {
  background: #c0392b; color: #fff; font-size: 9px;
  padding: 1px 5px; border-radius: 8px; font-weight: 600;
}
.qa-count {
  background: #f0faf3; color: #2d7a50; font-size: 9px;
  padding: 1px 5px; border-radius: 8px;
}

/* Layout rows */
.row-2 { display: grid; grid-template-columns: 1.4fr 1fr; gap: 12px; margin-bottom: 16px; }
.row-3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; }

/* Cards */
.card {
  background: #fff; border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px; padding: 16px; position: relative;
}
.card--loading { opacity: 0.6; pointer-events: none; }
.card-head {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;
}
.card-title { font-size: 13px; font-weight: 600; color: #1a2e24; }
.period-inline { color: #4caf76; }
.see-all { font-size: 11px; color: #4caf76; text-decoration: none; }
.see-all:hover { text-decoration: underline; }

/* Charts */
.chart-wrap     { height: 180px; position: relative; }
.chart-wrap--sm { height: 150px; }
.chart-placeholder {
  height: 100%; display: flex; align-items: center; justify-content: center;
  font-size: 12px; color: #7a9489; background: #f8faf9; border-radius: 6px;
}

/* Band legend */
.band-legend { display: flex; gap: 8px; margin-bottom: 6px; flex-wrap: wrap; }
.legend-item { display: flex; align-items: center; gap: 4px; font-size: 10px; color: #4a6357; }
.legend-dot  { width: 8px; height: 8px; border-radius: 2px; flex-shrink: 0; }

/* Band total (overlaid centre text) */
.band-total {
  text-align: center; margin-top: -120px; position: relative;
  pointer-events: none;
}
.bt-val   { font-size: 18px; font-weight: 600; color: #1a2e24; }
.bt-label { font-size: 10px; color: #7a9489; }

/* Target note */
.target-note {
  background: #f0faf3; border: 1px solid #a8ddb8;
  border-radius: 8px; padding: 10px 12px;
  font-size: 11px; color: #2d7a50; margin-top: 12px;
}

/* Section block */
.section-block { margin-bottom: 16px; }
.section-head  { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.section-title { font-size: 13px; font-weight: 600; color: #1a2e24; }

/* Empty state */
.empty-state { font-size: 12px; color: #7a9489; padding: 20px 0; text-align: center; }
.link { color: #4caf76; text-decoration: underline; }

/* Alert link */
.alert-link { color: #2d7a50; text-decoration: underline; cursor: pointer; margin-left: 4px; }

/* Bottom stats */
.bottom-stats {
  display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; margin-bottom: 16px;
}
.bs-item {
  background: #fff; border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px; padding: 14px 16px;
}
.bs-label { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; color: #7a9489; margin-bottom: 4px; }
.bs-val   { display: block; font-size: 20px; font-weight: 600; color: #1a2e24; margin-bottom: 4px; }
.bs-sub   { font-size: 10px; color: #7a9489; }
.bs-val.green { color: #2d7a50; }
.bs-val.amber { color: #b88a00; }
.bs-val.red   { color: #c0392b; }
.rate-bar {
  height: 4px; background: rgba(0,0,0,0.08); border-radius: 2px;
  overflow: hidden; margin-top: 6px;
}
.rate-fill { height: 100%; border-radius: 2px; transition: width 0.5s ease; }
.rate-fill.green { background: #4caf76; }
.rate-fill.amber { background: #f5c842; }
.rate-fill.red   { background: #c0392b; }

/* Alerts Modal */
.alerts-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.toggle-group {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.toggle-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.toggle-label {
  font-size: 14px;
  font-weight: 500;
  color: #1a2e24;
}

.toggle-description {
  font-size: 12px;
  color: #4a6357;
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 24px;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #d1d5db;
  transition: 0.3s;
  border-radius: 24px;
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
}

.toggle-switch input:checked + .toggle-slider {
  background-color: #4caf76;
}

.toggle-switch input:checked + .toggle-slider:before {
  transform: translateX(24px);
}

.modal-btn {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 13px;
  cursor: pointer;
  border: none;
}

.modal-btn--cancel {
  background: #f5f5f5;
  color: #4a6357;
}

.modal-btn--primary {
  background: #4caf76;
  color: white;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  font-weight: 500;
  color: #1a2e24;
}

.form-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid rgba(0,0,0,0.12);
  border-radius: 6px;
  font-size: 13px;
  color: #1a2e24;
  background: white;
}

.form-input:focus {
  outline: none;
  border-color: #4caf76;
}

/* Responsive */
@media (max-width: 1200px) {
  .kpi-grid     { grid-template-columns: repeat(2,1fr); }
  .row-2        { grid-template-columns: 1fr; }
  .row-3        { grid-template-columns: repeat(2,1fr); }
  .bottom-stats { grid-template-columns: 1fr; }
  .band-total   { display: none; }
}
@media (max-width: 640px) {
  .row-3 { grid-template-columns: 1fr; }
  .quick-actions .qa-btn span.qa-count,
  .quick-actions .qa-btn span.qa-badge { display: none; }
}
</style>
