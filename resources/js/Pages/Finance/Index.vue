<template>
  <AppLayout title="Finance">
    <div class="finance-container">
      <div class="header">
        <h1>Finance</h1>
        <p>Financial overview and reports</p>
      </div>

      <!-- Month/Year Selector -->
      <div class="period-selector">
        <select v-model="selectedMonth" @change="loadData" class="period-select">
          <option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option>
        </select>
        <select v-model="selectedYear" @change="loadData" class="period-select">
          <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
        </select>
        <button class="export-btn" @click="exportReport">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export Report
        </button>
        <Link href="/finance/budget" class="budget-btn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
          </svg>
          Manage Budgets
        </Link>
      </div>

      <!-- KPI Cards -->
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-label">Revenue</div>
          <div class="kpi-value">{{ formatCurrency(profitLoss.revenue) }}</div>
          <div class="kpi-sub">Total collected</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Expenses</div>
          <div class="kpi-value">{{ formatCurrency(profitLoss.expenses) }}</div>
          <div class="kpi-sub">Total spent</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Net Profit</div>
          <div class="kpi-value" :class="(profitLoss.net_profit || 0) >= 0 ? 'positive' : 'negative'">{{ formatCurrency(profitLoss.net_profit) }}</div>
          <div class="kpi-sub">{{ profitLoss.profit_margin || 0 }}% margin</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Cash Flow</div>
          <div class="kpi-value" :class="(cashFlow.net_cash_flow || 0) >= 0 ? 'positive' : 'negative'">{{ formatCurrency(cashFlow.net_cash_flow) }}</div>
          <div class="kpi-sub">Net cash movement</div>
        </div>
      </div>

      <!-- Charts -->
      <div class="charts-section">
        <div class="chart-card">
          <div class="chart-header">
            <h3>Revenue Trend</h3>
            <div class="chart-legend"><span class="legend-item"><span class="legend-color" style="background:#10b981"></span> Revenue</span></div>
          </div>
          <div class="chart-container">
            <BarChart :data="revenueChartData" :options="revenueChartOptions" />
          </div>
        </div>
        <div class="chart-card">
          <div class="chart-header">
            <h3>Expense Breakdown</h3>
            <div class="chart-legend"><span class="legend-item"><span class="legend-color" style="background:#ef4444"></span> Expenses</span></div>
          </div>
          <div class="chart-container">
            <DoughnutChart :data="expenseChartData" :options="expenseChartOptions" />
          </div>
        </div>
      </div>

      <!-- Recent Transactions -->
      <div class="transactions-section">
        <div class="section-header">
          <h3>Recent Transactions</h3>
          <Link href="/transactions" class="view-all">View all →</Link>
        </div>
        <div class="transactions-table">
          <div class="table-header">
            <div class="col control-number">Control #</div>
            <div class="col payer-name">Payer Name</div>
            <div class="col amount">Amount</div>
            <div class="col method">Method</div>
            <div class="col date">Date</div>
            <div class="col status">Status</div>
          </div>
          <div class="table-body">
            <div v-for="transaction in recentTransactions" :key="transaction.id" class="table-row">
              <div class="col control-number">{{ transaction.control_number }}</div>
              <div class="col payer-name">{{ transaction.payerName }}</div>
              <div class="col amount">{{ formatCurrency(transaction.amount) }}</div>
              <div class="col method">{{ transaction.paymentMethod }}</div>
              <div class="col date">{{ formatDate(transaction.paidAt) }}</div>
              <div class="col status">
                <span class="status-badge" :class="`status-badge--${transaction.status}`">{{ transaction.status }}</span>
              </div>
            </div>
            <div v-if="!recentTransactions.length" class="empty-row">No transactions this month.</div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BarChart from '@/Components/BarChart.vue'
import DoughnutChart from '@/Components/DoughnutChart.vue'

const props = defineProps({
  profitLoss:    { type: Object, default: () => ({}) },
  cashFlow:      { type: Object, default: () => ({}) },
  selectedMonth: { type: [String, Number], default: new Date().getMonth() + 1 },
  selectedYear:  { type: [String, Number], default: new Date().getFullYear() },
  payments:      { type: Array, default: () => [] },
  expenses:      { type: Array, default: () => [] },
})

const selectedMonth = ref(props.selectedMonth)
const selectedYear  = ref(props.selectedYear)

const months = ref([
  { value: 1, label: 'January' }, { value: 2, label: 'February' }, { value: 3, label: 'March' },
  { value: 4, label: 'April' }, { value: 5, label: 'May' }, { value: 6, label: 'June' },
  { value: 7, label: 'July' }, { value: 8, label: 'August' }, { value: 9, label: 'September' },
  { value: 10, label: 'October' }, { value: 11, label: 'November' }, { value: 12, label: 'December' },
])
const years = ref(Array.from({ length: 10 }, (_, i) => new Date().getFullYear() - i))

const recentTransactions = computed(() =>
  props.payments.slice(0, 10).map(p => ({
    id: p.id,
    control_number: p.control_number,
    payerName: p.client?.name || p.payer_name || 'Unknown',
    amount: p.amount,
    paymentMethod: p.payment_method,
    paidAt: p.paid_at,
    status: p.status || 'paid',
  }))
)

const revenueChartData = computed(() => {
  const dailyRevenue = {}
  props.payments.filter(p => p.status === 'paid').forEach(p => {
    const day = new Date(p.paid_at).getDate()
    dailyRevenue[day] = (dailyRevenue[day] || 0) + parseFloat(p.amount)
  })
  const labels = Object.keys(dailyRevenue).sort((a, b) => a - b)
  return {
    labels: labels.map(d => `Day ${d}`),
    datasets: [{ label: 'Revenue', data: labels.map(d => dailyRevenue[d]), backgroundColor: 'rgba(16,185,129,0.5)', borderColor: '#10b981', borderWidth: 2 }],
  }
})

const expenseChartData = computed(() => {
  const categoryTotals = {}
  props.expenses.forEach(e => {
    const cat = e.category?.name || 'Uncategorized'
    categoryTotals[cat] = (categoryTotals[cat] || 0) + parseFloat(e.amount)
  })
  return {
    labels: Object.keys(categoryTotals),
    datasets: [{ data: Object.values(categoryTotals), backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#8b5cf6', '#10b981'], borderWidth: 1 }],
  }
})

const revenueChartOptions = ref({ responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } })
const expenseChartOptions = ref({ responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } })

const loadData = () => {
  router.get('/finance', { month: selectedMonth.value, year: selectedYear.value }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const exportReport = () => {
  window.location.href = `/finance/export?month=${selectedMonth.value}&year=${selectedYear.value}`
}

const formatCurrency = (amount) => {
  if (!amount) return 'TZS 0.00'
  return 'TZS ' + amount.toLocaleString('en-TZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<style scoped>
.finance-container { max-width: 1400px; margin: 0 auto; padding: 32px; }
.header { margin-bottom: 32px; }
.header h1 { font-size: 32px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
.header p { font-size: 16px; color: #64748b; }
.period-selector { display: flex; gap: 16px; margin-bottom: 32px; flex-wrap: wrap; }
.period-select { padding: 10px 16px; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 14px; color: #475569; cursor: pointer; }
.period-select:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); }
.export-btn, .budget-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s; }
.export-btn:hover, .budget-btn:hover { background: linear-gradient(135deg, #059669 0%, #047857 100%); transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 32px; }
.kpi-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.2s; }
.kpi-card:hover { box-shadow: 0 4px 6px rgba(0,0,0,0.1); transform: translateY(-2px); }
.kpi-label { font-size: 14px; color: #64748b; margin-bottom: 8px; font-weight: 600; }
.kpi-value { font-size: 28px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
.kpi-sub { font-size: 12px; color: #94a3b8; }
.kpi-value.positive { color: #10b981; }
.kpi-value.negative { color: #ef4444; }
.charts-section { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 32px; }
.chart-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.chart-header h3 { font-size: 18px; font-weight: 600; color: #1e293b; }
.chart-legend { display: flex; gap: 16px; }
.legend-item { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #64748b; }
.legend-color { width: 12px; height: 12px; border-radius: 2px; }
.chart-container { height: 300px; position: relative; }
.transactions-section { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.section-header h3 { font-size: 18px; font-weight: 600; color: #1e293b; }
.view-all { font-size: 14px; color: #10b981; text-decoration: none; font-weight: 600; }
.view-all:hover { text-decoration: underline; }
.transactions-table { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
.table-header { display: grid; grid-template-columns: 120px 1fr 100px 100px 120px 100px; gap: 16px; padding: 16px; background: #f8fafc; font-size: 14px; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0; }
.table-body { max-height: 400px; overflow-y: auto; }
.table-row { display: grid; grid-template-columns: 120px 1fr 100px 100px 120px 100px; gap: 16px; padding: 16px; border-bottom: 1px solid #f1f5f9; transition: all 0.2s; }
.table-row:hover { background: #f8fafc; }
.col { display: flex; align-items: center; font-size: 14px; color: #1e293b; }
.col.control-number { font-family: monospace; font-weight: 600; color: #10b981; }
.col.payer-name { font-weight: 500; }
.col.amount { font-weight: 600; }
.col.status { justify-content: center; }
.status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
.status-badge--paid { background: #dcfce7; color: #166534; }
.status-badge--pending { background: #fef3c7; color: #92400e; }
.status-badge--failed { background: #fee2e2; color: #991b1b; }
.status-badge--refunded { background: #e0f2fe; color: #0369a1; }
.empty-row { text-align: center; padding: 24px; color: #94a3b8; }
@media (max-width: 1024px) { .charts-section { grid-template-columns: 1fr; } .table-header, .table-row { grid-template-columns: 100px 1fr 80px 80px 100px 80px; gap: 12px; padding: 12px; } }
@media (max-width: 768px) { .period-selector { flex-direction: column; } .period-select { width: 100%; } .export-btn, .budget-btn { justify-content: center; } .kpi-grid { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); } .table-header, .table-row { grid-template-columns: 80px 1fr 70px 70px 80px 70px; gap: 8px; padding: 12px; font-size: 12px; } .col.control-number { font-size: 11px; } }
</style>
