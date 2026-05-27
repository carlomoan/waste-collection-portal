<template>
  <AppLayout title="Dashboard">

    <AlertBanner type="warning">
      <strong>{{ stats.clients_unpaid }} clients</strong> have unpaid balances past the grace period
      — penalty fees applicable.
      <Link :href="route('debts.index')" class="alert-link">Review now →</Link>
    </AlertBanner>

    <!-- Period tabs -->
    <div class="tab-bar">
      <button
        v-for="tab in tabs" :key="tab.key"
        class="tab" :class="{ 'tab--active': activeTab === tab.key }"
        @click="activeTab = tab.key"
      >{{ tab.label }}</button>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
      <StatCard
        label="Total Collected"
        :value="formatTZS(stats.total_collected)"
        sub="TZS · May 1–15"
        accent="green"
      />
      <StatCard
        label="Transactions"
        :value="stats.total_transactions"
        sub="+8 vs last period"
        trend="up"
        accent="green"
      />
      <StatCard
        label="Outstanding Debt"
        :value="formatTZS(stats.total_outstanding)"
        sub="TZS unpaid balance"
        accent="amber"
      />
      <StatCard
        label="Penalty Due"
        :value="formatTZS(stats.total_penalties)"
        :sub="`${stats.clients_unpaid} defaulting clients`"
        trend="down"
        accent="red"
      />
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
      <Link :href="route('reports.monthly')" class="qa-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="1.8" stroke="currentColor" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21
               18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
        </svg>
        Export Monthly Report
      </Link>
      <Link :href="route('debts.index')" class="qa-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="1.8" stroke="currentColor" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0
               2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697
               16.126ZM12 15.75h.007v.008H12v-.008Z"/>
        </svg>
        Flag Non-Payers
      </Link>
      <Link :href="route('reports.collector')" class="qa-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="1.8" stroke="currentColor" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5
               7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
        </svg>
        Collector Report
      </Link>
      <Link :href="route('transactions.index')" class="qa-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="1.8" stroke="currentColor" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424
               48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75
               0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012
               0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25
               6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125
               1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
        </svg>
        All Transactions
      </Link>
    </div>

    <!-- Charts Row -->
    <div class="row-2">
      <!-- Trend chart -->
      <div class="card">
        <div class="card-head">
          <span class="card-title">Collection Trend — May 2026</span>
          <Link :href="route('reports.monthly')" class="see-all">View full report →</Link>
        </div>
        <div class="chart-wrap">
          <Bar :data="trendChartData" :options="trendChartOptions" />
        </div>
      </div>

      <!-- Band chart -->
      <div class="card">
        <div class="card-head">
          <span class="card-title">Collection by Amount Band</span>
        </div>
        <div class="band-legend">
          <span v-for="b in bandLegend" :key="b.label" class="legend-item">
            <span class="legend-dot" :style="{ background: b.color }" />
            {{ b.label }}
          </span>
        </div>
        <div class="chart-wrap chart-wrap--sm">
          <Doughnut :data="bandChartData" :options="bandChartOptions" />
        </div>
      </div>
    </div>

    <!-- Transactions + Collectors -->
    <div class="row-2">
      <!-- Recent transactions -->
      <div class="card">
        <div class="card-head">
          <span class="card-title">Recent Transactions</span>
          <Link :href="route('transactions.index')" class="see-all">See all {{ stats.total_transactions }} →</Link>
        </div>
        <TransactionRow
          v-for="tx in recentTransactions"
          :key="tx.control_number"
          v-bind="tx"
        />
      </div>

      <!-- Collector performance -->
      <div class="card">
        <div class="card-head">
          <span class="card-title">Collector Performance</span>
          <Link :href="route('reports.collector')" class="see-all">Full report →</Link>
        </div>
        <CollectorProgressRow
          v-for="c in collectors"
          :key="c.name"
          v-bind="c"
        />
        <div class="target-note">
          <strong>Target:</strong> 1,200,000 TZS/collector · Period ends May 31
        </div>
      </div>
    </div>

    <!-- Schedule row -->
    <div class="section-title">This Week's Schedule</div>
    <div class="row-3">
      <ScheduleCard
        v-for="sched in weekSchedule"
        :key="sched.dayLabel"
        v-bind="sched"
      />
    </div>

  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Bar, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale, BarElement,
  ArcElement, Tooltip, Legend,
} from 'chart.js'

import AppLayout           from '@/Layouts/AppLayout.vue'
import StatCard            from '@/Components/StatCard.vue'
import AlertBanner         from '@/Components/AlertBanner.vue'
import TransactionRow      from '@/Components/TransactionRow.vue'
import CollectorProgressRow from '@/Components/CollectorProgressRow.vue'
import ScheduleCard        from '@/Components/ScheduleCard.vue'

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Tooltip, Legend)

// Props from Inertia (Laravel controller passes these)
const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      total_collected: 1005000, total_transactions: 119,
      total_outstanding: 342000, total_penalties: 48000,
      clients_unpaid: 12, collection_rate: 74.6,
    }),
  },
  recentTransactions: {
    type: Array,
    default: () => [
      { payerName: 'ANOLD KED MON', controlNumber: '5260000007045', amount: 3000,  status: 'paid',      paidAt: '2026-05-15T16:28:00' },
      { payerName: 'KIZENGA',       controlNumber: '5260000007046', amount: 6000,  status: 'paid',      paidAt: '2026-05-15T16:40:00' },
      { payerName: 'SHEMEJI',       controlNumber: '5260000007044', amount: 6000,  status: 'paid',      paidAt: '2026-05-15T15:59:00' },
      { payerName: 'ELIANLS',       controlNumber: '5260000007049', amount: 6000,  status: 'paid',      paidAt: '2026-05-15T17:32:00' },
      { payerName: 'JASHUA',        controlNumber: '5260000007048', amount: 6000,  status: 'partial',   paidAt: '2026-05-15T16:58:00' },
      { payerName: null,            controlNumber: '5260000007050', amount: 3000,  status: 'unmatched', paidAt: '2026-05-15T17:37:00' },
    ],
  },
  collectors: {
    type: Array,
    default: () => [
      { name: 'Sarah Shechambo', collected: 1005000, transactions: 119, zone: 'Zone A–F' },
      { name: 'John Mwangi',     collected: 630000,  transactions: 84,  zone: 'Zone G–J' },
      { name: 'Fatuma Makame',   collected: 290000,  transactions: 41,  zone: 'Zone K–L' },
    ],
  },
  weekSchedule: {
    type: Array,
    default: () => [
      { dayLabel: 'Monday · Week 21',    zoneName: 'Zone A — Kariakoo', zoneColor: '#4caf76', staffName: 'Sarah Shechambo', clientCount: 47 },
      { dayLabel: 'Tuesday · Week 21',   zoneName: 'Zone B — Ilala',    zoneColor: '#f5c842', staffName: 'John Mwangi',     clientCount: 32 },
      { dayLabel: 'Wednesday · Week 21', zoneName: 'Zone C — Temeke',   zoneColor: '#c0392b', staffName: 'Fatuma Makame',   clientCount: 29 },
    ],
  },
})

// Tabs
const tabs = [
  { key: 'monthly', label: 'Monthly' },
  { key: 'weekly',  label: 'Weekly' },
  { key: 'yearly',  label: 'Yearly' },
]
const activeTab = ref('monthly')

// Formatting
const formatTZS = (v) =>
  new Intl.NumberFormat('sw-TZ', {
    style: 'currency', currency: 'TZS', minimumFractionDigits: 0,
  }).format(v)

// Chart data
const trendChartData = {
  labels: ['May 1','May 3','May 5','May 7','May 9','May 11','May 13','May 15'],
  datasets: [{
    label: 'Collected (TZS)',
    data: [42000, 78000, 55000, 120000, 95000, 185000, 165000, 265000],
    backgroundColor: '#4caf76',
    borderRadius: 4,
    borderSkipped: false,
  }],
}
const trendChartOptions = {
  responsive: true, maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: { callbacks: { label: ctx => 'TZS ' + ctx.parsed.y.toLocaleString() } },
  },
  scales: {
    x: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#7a9489', font: { size: 10 } }, border: { display: false } },
    y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#7a9489', font: { size: 10 }, callback: v => v >= 1000 ? (v/1000)+'k' : v }, border: { display: false } },
  },
}

const bandChartData = {
  labels: ['3,000 TZS', '6,000 TZS', 'High value (>10k)'],
  datasets: [{
    data: [45, 38, 17],
    backgroundColor: ['#2d7a50', '#4caf76', '#f5c842'],
    borderWidth: 0,
    hoverOffset: 4,
  }],
}
const bandChartOptions = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ctx.label + ': ' + ctx.parsed + '%' } } },
  cutout: '65%',
}

const bandLegend = [
  { label: '3,000',      color: '#2d7a50' },
  { label: '6,000',      color: '#4caf76' },
  { label: 'High value', color: '#f5c842' },
]
</script>

<style scoped>
.alert-link { color: #2d7a50; text-decoration: underline; cursor: pointer; }

.tab-bar {
  display: flex; gap: 2px; background: #f0faf3;
  border: 1px solid rgba(0,0,0,0.08); border-radius: 8px;
  padding: 3px; width: fit-content; margin-bottom: 16px;
}
.tab {
  padding: 5px 14px; border-radius: 6px; font-size: 12px;
  cursor: pointer; color: #4a6357; background: none; border: none;
  transition: all 0.15s;
}
.tab--active { background: #ffffff; color: #1a2e24; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }

.kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 16px; }

.quick-actions { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
.qa-btn {
  background: #ffffff; border: 1px solid rgba(0,0,0,0.08); border-radius: 8px;
  padding: 8px 12px; font-size: 11px; color: #4a6357; cursor: pointer;
  display: flex; align-items: center; gap: 6px; text-decoration: none;
  transition: all 0.15s;
}
.qa-btn:hover { border-color: #4caf76; color: #2d7a50; }

.row-2 { display: grid; grid-template-columns: 1.4fr 1fr; gap: 12px; margin-bottom: 16px; }
.row-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px; }

.card {
  background: #ffffff; border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px; padding: 16px;
}
.card-head {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;
}
.card-title { font-size: 13px; font-weight: 600; color: #1a2e24; }
.see-all { font-size: 11px; color: #4caf76; text-decoration: none; }
.see-all:hover { text-decoration: underline; }

.chart-wrap     { height: 180px; position: relative; }
.chart-wrap--sm { height: 150px; }

.band-legend {
  display: flex; gap: 10px; margin-bottom: 8px; flex-wrap: wrap;
}
.legend-item {
  display: flex; align-items: center; gap: 4px; font-size: 10px; color: #4a6357;
}
.legend-dot { width: 8px; height: 8px; border-radius: 2px; }

.target-note {
  background: #f0faf3; border: 1px solid #a8ddb8;
  border-radius: 8px; padding: 10px 12px;
  font-size: 11px; color: #2d7a50; margin-top: 12px;
}

.section-title { font-size: 13px; font-weight: 600; color: #1a2e24; margin-bottom: 10px; }

@media (max-width: 1024px) {
  .kpi-grid { grid-template-columns: repeat(2, 1fr); }
  .row-2    { grid-template-columns: 1fr; }
  .row-3    { grid-template-columns: 1fr; }
}
</style>
