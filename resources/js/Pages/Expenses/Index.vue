<template>
  <AppLayout title="Expenses">
    <div class="expenses-container">
      <div class="header">
        <h1>Expenses</h1>
        <p>Track and manage expenses</p>
      </div>

      <!-- Summary Cards -->
      <div class="summary-grid">
        <div class="summary-card">
          <div class="summary-label">Total Expenses (This Month)</div>
          <div class="summary-value">{{ formatCurrency(totalExpenses) }}</div>
          <div class="summary-change summary-change--negative">{{ categories.length }} categories</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Pending Approval</div>
          <div class="summary-value">{{ formatCurrency(pendingExpenses) }}</div>
          <div class="summary-change summary-change--neutral">{{ pendingCount }} expenses awaiting approval</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Budget Used</div>
          <div class="summary-value">{{ budgetUsedPercentage }}%</div>
          <div class="summary-change summary-change--neutral">of {{ formatCurrency(totalBudget) }} monthly budget</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Categories</div>
          <div class="summary-value">{{ categories.length }} Active</div>
          <div class="summary-change summary-change--positive">{{ categories.map(c => c.name).join(', ') }}</div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="actions-bar">
        <button class="action-btn action-btn--primary" @click="showAddModal = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          New Expense
        </button>
        <button class="action-btn" @click="showAnalyticsModal = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125z"/>
          </svg>
          Analytics
        </button>
        <button class="action-btn" @click="exportReport">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export Report
        </button>
        <button class="action-btn" @click="showCategoriesModal = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
          </svg>
          Manage Categories
        </button>
      </div>

      <!-- Expense Categories -->
      <div class="categories-section">
        <h3>Expense by Category</h3>
        <div class="categories-grid">
          <div v-for="category in categories" :key="category.id" class="category-card">
            <div class="category-icon category-icon--fuel">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
              </svg>
            </div>
            <div class="category-name">{{ category.name }}</div>
            <div class="category-amount">{{ formatCurrency(category.spent) }}</div>
            <div class="category-bar">
              <div class="category-progress" :style="{ width: ((category.spent / category.budget) * 100) + '%' }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Expenses -->
      <div class="expenses-section">
        <div class="section-header">
          <h3>Recent Expenses</h3>
          <button class="view-all-btn">View All</button>
        </div>
        <table class="expenses-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Description</th>
              <th>Category</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="expense in recentExpenses" :key="expense.id">
              <td>{{ formatDate(expense.date) }}</td>
              <td>{{ expense.description }}</td>
              <td>{{ expense.category }}</td>
              <td>{{ formatCurrency(expense.amount) }}</td>
              <td><span class="status-badge" :class="`status-badge--${expense.status}`">{{ expense.status }}</span></td>
              <td class="td-actions">
                <button class="table-action">View</button>
                <button v-if="expense.status === 'pending'" class="table-action table-action--success" @click="approveExpense(expense)">Approve</button>
                <button v-if="expense.status === 'pending'" class="table-action table-action--danger" @click="rejectExpense(expense)">Reject</button>
              </td>
            </tr>
            <tr v-if="recentExpenses.length === 0">
              <td colspan="6" style="text-align: center; color: #4a6357;">No expenses found</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add Expense Modal -->
    <Modal :show="showAddModal" @close="showAddModal = false" title="New Expense">
      <form @submit.prevent="addExpense">
        <div class="form-group">
          <label>Description</label>
          <input type="text" v-model="addForm.description" class="form-input" required>
        </div>
        <div class="form-group">
          <label>Category</label>
          <select v-model="addForm.category_id" class="form-input" required>
            <option value="">Select category</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>Amount</label>
          <input type="number" v-model="addForm.amount" class="form-input" min="0" step="0.01" required>
        </div>
        <div class="form-group">
          <label>Date</label>
          <input type="date" v-model="addForm.expense_date" class="form-input" required>
        </div>
        <div class="form-group">
          <label>Receipt (Optional)</label>
          <input type="file" ref="receiptFile" class="form-input" accept=".jpg,.png,.pdf" />
        </div>
        <div class="form-group">
          <label>Recurring Expense</label>
          <div class="checkbox-group">
            <input type="checkbox" v-model="addForm.is_recurring" id="is_recurring" />
            <label for="is_recurring">This is a recurring expense</label>
          </div>
        </div>
        <div v-if="addForm.is_recurring" class="form-group">
          <label>Recurrence Pattern</label>
          <select v-model="addForm.recurrence_pattern" class="form-input" required>
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
          </select>
        </div>
        <div class="form-group">
          <label>Notes (Optional)</label>
          <textarea v-model="addForm.notes" class="form-input" rows="3"></textarea>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showAddModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="addExpense" :disabled="addForm.processing">
          {{ addForm.processing ? 'Adding...' : 'Add Expense' }}
        </button>
      </template>
    </Modal>

    <!-- Manage Categories Modal -->
    <Modal :show="showCategoriesModal" @close="showCategoriesModal = false" title="Manage Categories">
      <div class="categories-list">
        <div v-for="cat in categories" :key="cat.id" class="category-item">
          <span>{{ cat.name }}</span>
          <span>{{ formatCurrency(cat.budget) }}</span>
        </div>
      </div>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showCategoriesModal = false">Close</button>
      </template>
    </Modal>

    <!-- Analytics Modal -->
    <Modal :show="showAnalyticsModal" @close="showAnalyticsModal = false" title="Expense Analytics">
      <div class="analytics-content">
        <div class="analytics-section">
          <h4>Monthly Trend ({{ currentYear }})</h4>
          <div class="chart-placeholder">
            <div v-for="month in analyticsData.monthlyTrend" :key="month.month" class="chart-bar">
              <div class="chart-bar-fill" :style="{ height: ((month.total / maxMonthlyTotal) * 100) + '%' }"></div>
              <div class="chart-label">{{ month.month }}</div>
              <div class="chart-value">{{ formatCurrency(month.total) }}</div>
            </div>
          </div>
        </div>
        <div class="analytics-section">
          <h4>Top Categories</h4>
          <div class="top-categories">
            <div v-for="cat in analyticsData.topCategories" :key="cat.expense_category_id" class="top-cat-item">
              <span class="top-cat-name">{{ getCategoryName(cat.expense_category_id) }}</span>
              <span class="top-cat-amount">{{ formatCurrency(cat.total) }}</span>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showAnalyticsModal = false">Close</button>
      </template>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  categories: {
    type: Array,
    default: () => []
  },
  recentExpenses: {
    type: Array,
    default: () => []
  }
})

const showAddModal = ref(false)
const showCategoriesModal = ref(false)
const showAnalyticsModal = ref(false)
const currentYear = new Date().getFullYear()
const analyticsData = ref({ monthlyTrend: [], topCategories: [] })

const addForm = useForm({
  description: '',
  category_id: '',
  amount: '',
  expense_date: '',
  notes: '',
  is_recurring: false,
  recurrence_pattern: 'monthly'
})

const totalExpenses = computed(() => {
  return props.categories.reduce((sum, cat) => sum + (cat.spent || 0), 0)
})

const totalBudget = computed(() => {
  return props.categories.reduce((sum, cat) => sum + (cat.budget || 0), 0)
})

const budgetUsedPercentage = computed(() => {
  if (totalBudget.value === 0) return 0
  return ((totalExpenses.value / totalBudget.value) * 100).toFixed(1)
})

const pendingExpenses = computed(() => {
  return props.recentExpenses.filter(e => e.status === 'pending').reduce((sum, e) => sum + (e.amount || 0), 0)
})

const pendingCount = computed(() => {
  return props.recentExpenses.filter(e => e.status === 'pending').length
})

const formatCurrency = (value) => {
  return new Intl.NumberFormat('sw-TZ', {
    style: 'currency',
    currency: 'TZS',
    minimumFractionDigits: 0,
  }).format(value)
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-TZ', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const addExpense = () => {
  const formData = new FormData()
  formData.append('description', addForm.description)
  formData.append('category_id', addForm.category_id)
  formData.append('amount', addForm.amount)
  formData.append('expense_date', addForm.expense_date)
  formData.append('notes', addForm.notes)
  formData.append('is_recurring', addForm.is_recurring)
  if (addForm.is_recurring) {
    formData.append('recurrence_pattern', addForm.recurrence_pattern)
  }
  
  const fileInput = document.querySelector('input[type="file"]')
  if (fileInput && fileInput.files[0]) {
    formData.append('receipt', fileInput.files[0])
  }
  
  router.post('/expenses', formData, {
    onSuccess: () => {
      showAddModal.value = false
      addForm.reset()
    }
  })
}

const exportReport = () => {
  window.location.href = '/expenses/export?format=csv'
}

const approveExpense = (expense) => {
  if (confirm(`Approve expense: ${expense.description}?`)) {
    router.post(`/expenses/${expense.id}/approve`, {}, {
      onSuccess: () => {
        router.reload()
      }
    })
  }
}

const rejectExpense = (expense) => {
  if (confirm(`Reject expense: ${expense.description}?`)) {
    router.post(`/expenses/${expense.id}/reject`, {}, {
      onSuccess: () => {
        router.reload()
      }
    })
  }
}

const loadAnalytics = async () => {
  const response = await fetch(`/expenses/analytics?year=${currentYear}`)
  analyticsData.value = await response.json()
}

const getCategoryName = (categoryId) => {
  const cat = props.categories.find(c => c.id === categoryId)
  return cat ? cat.name : 'Unknown'
}

const maxMonthlyTotal = computed(() => {
  if (!analyticsData.value.monthlyTrend.length) return 1
  return Math.max(...analyticsData.value.monthlyTrend.map(m => m.total))
})

watch(showAnalyticsModal, (newVal) => {
  if (newVal) {
    loadAnalytics()
  }
})
</script>

<style scoped>
.expenses-container {
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

.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
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
  margin-bottom: 8px;
}

.summary-change {
  font-size: 11px;
}

.summary-change--positive {
  color: #2d7a50;
}

.summary-change--negative {
  color: #c0392b;
}

.summary-change--neutral {
  color: #4a6357;
}

.actions-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
}

.action-btn {
  padding: 10px 20px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 8px;
  background: white;
  color: #4a6357;
  font-size: 13px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.15s;
}

.action-btn:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.action-btn--primary {
  background: #4caf76;
  color: white;
  border-color: #4caf76;
}

.action-btn--primary:hover {
  background: #2d7a50;
  border-color: #2d7a50;
}

.categories-section {
  margin-bottom: 24px;
}

.categories-section h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 16px;
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.category-card {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.category-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12px;
}

.category-icon--fuel {
  background: #ffe0b2;
  color: #e65100;
}

.category-icon--maintenance {
  background: #e1bee7;
  color: #7b1fa2;
}

.category-icon--salaries {
  background: #c8e6c9;
  color: #2d7a50;
}

.category-icon--office {
  background: #bbdefb;
  color: #1565c0;
}

.category-name {
  font-size: 13px;
  font-weight: 500;
  color: #1a2e24;
  margin-bottom: 8px;
}

.category-amount {
  font-size: 18px;
  font-weight: 600;
  color: #2d7a50;
  margin-bottom: 12px;
}

.category-bar {
  width: 100%;
  height: 6px;
  background: #f0faf3;
  border-radius: 3px;
  overflow: hidden;
}

.category-progress {
  height: 100%;
  background: #4caf76;
  border-radius: 3px;
  transition: width 0.3s ease;
}

.expenses-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.section-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1a2e24;
}

.view-all-btn {
  padding: 8px 16px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 12px;
  color: #4a6357;
  cursor: pointer;
  transition: all 0.15s;
}

.view-all-btn:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.expenses-table {
  width: 100%;
  border-collapse: collapse;
}

.expenses-table th {
  text-align: left;
  padding: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #4a6357;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.expenses-table td {
  padding: 12px;
  font-size: 13px;
  color: #1a2e24;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.expenses-table tr:last-child td {
  border-bottom: none;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge--approved {
  background: #e8f5e9;
  color: #2d7a50;
}

.status-badge--pending {
  background: #fff3e0;
  color: #e65100;
}

.status-badge--rejected {
  background: #ffebee;
  color: #c62828;
}

.table-action {
  padding: 4px 12px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 4px;
  font-size: 11px;
  color: #4a6357;
  cursor: pointer;
  transition: all 0.15s;
}

.table-action:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.table-action--success {
  color: #2d7a50;
}

.table-action--success:hover {
  border-color: #2d7a50;
}

.table-action--danger {
  color: #c62828;
}

.table-action--danger:hover {
  border-color: #c62828;
}

.td-actions {
  display: flex;
  gap: 4px;
}

.checkbox-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.checkbox-group input {
  width: auto;
}

.checkbox-group label {
  margin-bottom: 0;
  cursor: pointer;
}

.analytics-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.analytics-section h4 {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 12px;
}

.chart-placeholder {
  display: flex;
  align-items: flex-end;
  gap: 12px;
  height: 150px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 8px;
}

.chart-bar {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
}

.chart-bar-fill {
  width: 100%;
  background: #4caf76;
  border-radius: 4px 4px 0 0;
  transition: height 0.3s ease;
}

.chart-label {
  font-size: 10px;
  color: #4a6357;
  margin-top: 4px;
}

.chart-value {
  font-size: 10px;
  font-weight: 600;
  color: #1a2e24;
  margin-top: 2px;
}

.top-categories {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.top-cat-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 12px;
  background: #f9fafb;
  border-radius: 6px;
}

.top-cat-name {
  font-size: 13px;
  color: #1a2e24;
}

.top-cat-amount {
  font-size: 13px;
  font-weight: 600;
  color: #2d7a50;
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
  font-weight: 500;
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

.modal-btn--primary:hover {
  background: #2d7a50;
}

.categories-list {
  max-height: 300px;
  overflow-y: auto;
}

.category-item {
  display: flex;
  justify-content: space-between;
  padding: 12px;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.category-item:last-child {
  border-bottom: none;
}

@media (max-width: 1024px) {
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .categories-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
