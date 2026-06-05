<template>
  <AppLayout title="Budget Management">
    <div class="budget-container">
      <div class="header">
        <h1>Budget Management</h1>
        <p>Set and track monthly budgets</p>
      </div>

      <!-- Year Selector -->
      <div class="period-selector">
        <select v-model="selectedYear" @change="loadData" class="period-select">
          <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
        </select>
        <button class="add-btn" @click="showAddModal = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          Add Budget
        </button>
      </div>

      <!-- Budget Summary -->
      <div class="summary-cards">
        <div class="summary-card">
          <div class="summary-label">Total Revenue Target</div>
          <div class="summary-value">{{ formatCurrency(totalRevenueTarget) }}</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Total Expense Budget</div>
          <div class="summary-value">{{ formatCurrency(totalExpenseBudget) }}</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Budgeted Net Profit</div>
          <div class="summary-value" :class="budgetedNetProfit >= 0 ? 'positive' : 'negative'">{{ formatCurrency(budgetedNetProfit) }}</div>
        </div>
      </div>

      <!-- Monthly Budgets -->
      <div class="card">
        <div class="card-header">
          <h3>Monthly Budgets - {{ selectedYear }}</h3>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Month</th>
              <th>Revenue Target</th>
              <th>Expense Budget</th>
              <th>Net Budget</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="budget in budgets" :key="`${budget.year}-${budget.month}`">
              <td>{{ getMonthName(budget.month) }}</td>
              <td>{{ formatCurrency(budget.revenue_target) }}</td>
              <td>{{ formatCurrency(budget.expense_budget) }}</td>
              <td :class="(budget.revenue_target - budget.expense_budget) >= 0 ? 'positive' : 'negative'">
                {{ formatCurrency(budget.revenue_target - budget.expense_budget) }}
              </td>
              <td class="actions">
                <button class="action-btn" @click="editBudget(budget)">Edit</button>
              </td>
            </tr>
            <tr v-if="budgets.length === 0">
              <td colspan="5" style="text-align: center; color: #4a6357;">No budgets set for this year</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add/Edit Budget Modal -->
    <Modal :show="showAddModal" @close="closeModal" :title="editingBudget ? 'Edit Budget' : 'Add Budget'">
      <form @submit.prevent="saveBudget">
        <div class="form-group">
          <label>Month</label>
          <select v-model="form.month" class="form-input" required>
            <option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>Year</label>
          <select v-model="form.year" class="form-input" required>
            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>Revenue Target (TZS)</label>
          <input type="number" v-model="form.revenue_target" class="form-input" min="0" step="1000">
        </div>
        <div class="form-group">
          <label>Expense Budget (TZS)</label>
          <input type="number" v-model="form.expense_budget" class="form-input" min="0" step="1000">
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="closeModal">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="saveBudget" :disabled="form.processing">
          {{ form.processing ? 'Saving...' : 'Save Budget' }}
        </button>
      </template>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  budgets: { type: Array, default: () => [] },
  year: { type: Number, default: new Date().getFullYear() },
})

const selectedYear = ref(props.year)
const showAddModal = ref(false)
const editingBudget = ref(null)

const form = useForm({
  month: new Date().getMonth() + 1,
  year: selectedYear.value,
  revenue_target: '',
  expense_budget: '',
})

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
const years = [currentYear - 1, currentYear, currentYear + 1]

const totalRevenueTarget = computed(() => props.budgets.reduce((sum, b) => sum + (b.revenue_target || 0), 0))
const totalExpenseBudget = computed(() => props.budgets.reduce((sum, b) => sum + (b.expense_budget || 0), 0))
const budgetedNetProfit = computed(() => totalRevenueTarget.value - totalExpenseBudget.value)

const loadData = () => {
  router.get('/finance/budget', { year: selectedYear.value }, { preserveState: false })
}

const editBudget = (budget) => {
  editingBudget.value = budget
  form.month = budget.month
  form.year = budget.year
  form.revenue_target = budget.revenue_target
  form.expense_budget = budget.expense_budget
  showAddModal.value = true
}

const saveBudget = () => {
  form.post('/finance/budget/store', {
    onSuccess: () => {
      closeModal()
    }
  })
}

const closeModal = () => {
  showAddModal.value = false
  editingBudget.value = null
  form.reset()
  form.year = selectedYear.value
  form.month = new Date().getMonth() + 1
}

const getMonthName = (monthNum) => {
  return months.find(m => m.value === monthNum)?.label || 'Unknown'
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('sw-TZ', {
    style: 'currency',
    currency: 'TZS',
    minimumFractionDigits: 0,
  }).format(value || 0)
}
</script>

<style scoped>
.budget-container {
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

.add-btn {
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
  transition: background 0.15s;
}

.add-btn:hover {
  background: #2d7a50;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.summary-card {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.summary-label {
  font-size: 12px;
  color: #4a6357;
  margin-bottom: 8px;
}

.summary-value {
  font-size: 24px;
  font-weight: 600;
  color: #1a2e24;
}

.summary-value.positive {
  color: #2d7a50;
}

.summary-value.negative {
  color: #c0392b;
}

.card {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
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

.data-table td.positive {
  color: #2d7a50;
  font-weight: 500;
}

.data-table td.negative {
  color: #c0392b;
  font-weight: 500;
}

.data-table tr:last-child td {
  border-bottom: none;
}

.actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  padding: 6px 12px;
  background: #f0faf3;
  color: #2d7a50;
  border: 1px solid #a8ddb8;
  border-radius: 6px;
  font-size: 11px;
  cursor: pointer;
  transition: background 0.15s;
}

.action-btn:hover {
  background: #2d7a50;
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

@media (max-width: 1024px) {
  .summary-cards {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
