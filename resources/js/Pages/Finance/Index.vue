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
          <div class="kpi-value" :class="profitLoss.net_profit >= 0 ? 'positive' : 'negative'">{{ formatCurrency(profitLoss.net_profit) }}</div>
          <div class="kpi-sub">{{ profitLoss.profit_margin }}% margin</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Cash Flow</div>
          <div class="kpi-value" :class="cashFlow.net_cash_flow >= 0 ? 'positive' : 'negative'">{{ formatCurrency(cashFlow.net_cash_flow) }}</div>
          <div class="kpi-sub">Net cash position</div>
        </div>
      </div>

      <!-- Budget vs Actual -->
      <div class="card">
        <div class="card-header">
          <h3>Budget vs Actual</h3>
        </div>
        <div class="budget-grid">
          <div class="budget-item">
            <div class="budget-label">Revenue Target</div>
            <div class="budget-value">{{ formatCurrency(budgetVsActual.revenue_target) }}</div>
            <div class="budget-actual">{{ formatCurrency(budgetVsActual.revenue_actual) }}</div>
            <div class="budget-variance" :class="budgetVsActual.revenue_variance >= 0 ? 'positive' : 'negative'">
              {{ budgetVsActual.revenue_variance >= 0 ? '+' : '' }}{{ formatCurrency(budgetVsActual.revenue_variance) }}
            </div>
          </div>
          <div class="budget-item">
            <div class="budget-label">Expense Budget</div>
            <div class="budget-value">{{ formatCurrency(budgetVsActual.expense_budget) }}</div>
            <div class="budget-actual">{{ formatCurrency(budgetVsActual.expense_actual) }}</div>
            <div class="budget-variance" :class="budgetVsActual.expense_variance <= 0 ? 'positive' : 'negative'">
              {{ budgetVsActual.expense_variance <= 0 ? '+' : '' }}{{ formatCurrency(budgetVsActual.expense_variance) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Transactions -->
      <div class="card">
        <div class="card-header">
          <h3>Recent Payments</h3>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Control #</th>
              <th>Client</th>
              <th>Amount</th>
              <th>Method</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="payment in payments" :key="payment.id">
              <td>{{ formatDate(payment.paid_at) }}</td>
              <td>{{ payment.control_number }}</td>
              <td>{{ payment.client?.name || 'Unknown' }}</td>
              <td>{{ formatCurrency(payment.amount) }}</td>
              <td>{{ payment.payment_method }}</td>
            </tr>
            <tr v-if="payments.length === 0">
              <td colspan="5" style="text-align: center; color: #4a6357;">No payments found</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Recent Expenses -->
      <div class="card">
        <div class="card-header">
          <h3>Recent Expenses</h3>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Category</th>
              <th>Description</th>
              <th>Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="expense in expenses" :key="expense.id">
              <td>{{ formatDate(expense.expense_date) }}</td>
              <td>{{ expense.category?.name || 'N/A' }}</td>
              <td>{{ expense.description }}</td>
              <td>{{ formatCurrency(expense.amount) }}</td>
            </tr>
            <tr v-if="expenses.length === 0">
              <td colspan="4" style="text-align: center; color: #4a6357;">No expenses found</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  invoices: { type: Array, default: () => [] },
  payments: { type: Array, default: () => [] },
  expenses: { type: Array, default: () => [] },
  bankDeposits: { type: Array, default: () => [] },
  monthlyStats: { type: Object, default: () => ({}) },
  profitLoss: { type: Object, default: () => ({ revenue: 0, expenses: 0, net_profit: 0, profit_margin: 0 }) },
  budgetVsActual: { type: Object, default: () => ({ revenue_target: 0, revenue_actual: 0, revenue_variance: 0, expense_budget: 0, expense_actual: 0, expense_variance: 0 }) },
  cashFlow: { type: Object, default: () => ({ cash_in: 0, cash_out: 0, net_cash_flow: 0 }) },
  selectedMonth: { type: Number, default: new Date().getMonth() + 1 },
  selectedYear: { type: Number, default: new Date().getFullYear() },
})

const selectedMonth = ref(props.selectedMonth)
const selectedYear = ref(props.selectedYear)

const months = [
  { value: 1, label: 'January' },
  { value: 2, label: 'February' },
  { value: 3, label: 'March' },
  { value: 4, label: 'April' },
  { value: 5, label: 'May' },
  { value: 6, label: 'June' },
  { value: 7, label: 'July' },
  { value: 8, label: 'August' },
  { value: 9, label: 'September' },
  { value: 10, label: 'October' },
  { value: 11, label: 'November' },
  { value: 12, label: 'December' },
]

const currentYear = new Date().getFullYear()
const years = [currentYear - 2, currentYear - 1, currentYear, currentYear + 1]

const loadData = () => {
  router.get('/finance', { month: selectedMonth.value, year: selectedYear.value }, { preserveState: false })
}

const exportReport = () => {
  window.location.href = `/finance/export?month=${selectedMonth.value}&year=${selectedYear.value}&format=csv`
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('sw-TZ', {
    style: 'currency',
    currency: 'TZS',
    minimumFractionDigits: 0,
  }).format(value)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-TZ', { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<style scoped>
.finance-container {
  padding: 20px;
}

.header {
  margin-bottom: 24px;
}

.header h1 {
  font-size: 24px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 4px;
}

.header p {
  color: #4a6357;
  font-size: 14px;
}

.period-selector {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
}

.period-select {
  padding: 8px 12px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 13px;
  color: #4a6357;
  background: white;
}

.export-btn, .budget-btn {
  padding: 8px 16px;
  background: #4caf76;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
  transition: background 0.15s;
}

.export-btn:hover, .budget-btn:hover {
  background: #2d7a50;
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.kpi-card {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.kpi-label {
  font-size: 12px;
  color: #4a6357;
  margin-bottom: 8px;
}

.kpi-value {
  font-size: 24px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 4px;
}

.kpi-value.positive {
  color: #2d7a50;
}

.kpi-value.negative {
  color: #c0392b;
}

.kpi-sub {
  font-size: 11px;
  color: #7a9489;
}

.card {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 24px;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.card-header h3 {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
}

.budget-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.budget-item {
  padding: 16px;
  background: #f8faf9;
  border-radius: 8px;
}

.budget-label {
  font-size: 12px;
  color: #4a6357;
  margin-bottom: 4px;
}

.budget-value {
  font-size: 18px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 4px;
}

.budget-actual {
  font-size: 14px;
  color: #4a6357;
  margin-bottom: 4px;
}

.budget-variance {
  font-size: 12px;
  font-weight: 500;
}

.budget-variance.positive {
  color: #2d7a50;
}

.budget-variance.negative {
  color: #c0392b;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  text-align: left;
  padding: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #4a6357;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.data-table td {
  padding: 12px;
  font-size: 13px;
  color: #1a2e24;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.data-table tr:last-child td {
  border-bottom: none;
}

@media (max-width: 1024px) {
  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .budget-grid {
    grid-template-columns: 1fr;
  }
}
</style>
