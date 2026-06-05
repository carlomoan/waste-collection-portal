<template>
  <AppLayout title="Invoices">
    <div class="invoices-container">
      <div class="header">
        <h1>Invoices</h1>
        <div class="header-actions">
          <button class="action-btn action-btn--primary" @click="showGenerateModal = true">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Generate Invoices
          </button>
          <button class="action-btn" @click="applyPenalties">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
            </svg>
            Apply Penalties
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters">
        <select v-model="filters.status" @change="applyFilters" class="filter-select">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="paid">Paid</option>
          <option value="overdue">Overdue</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <select v-model="filters.client_id" @change="applyFilters" class="filter-select">
          <option value="">All Clients</option>
          <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
        </select>
        <button v-if="hasFilters" class="clear-filters-btn" @click="clearFilters">Clear Filters</button>
      </div>

      <!-- Summary Cards -->
      <div class="summary-cards">
        <div class="summary-card">
          <div class="summary-label">Total Invoices</div>
          <div class="summary-value">{{ invoices.total || 0 }}</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Total Outstanding</div>
          <div class="summary-value">{{ formatCurrency(totalOutstanding) }}</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Paid This Month</div>
          <div class="summary-value">{{ formatCurrency(paidThisMonth) }}</div>
        </div>
      </div>

      <!-- Invoices Table -->
      <div class="card">
        <div class="card-header">
          <h3>All Invoices</h3>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Invoice #</th>
              <th>Client</th>
              <th>Amount</th>
              <th>Due Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="invoice in invoices.data" :key="invoice.id">
              <td>{{ invoice.invoice_number }}</td>
              <td>{{ invoice.client?.name || 'Unknown' }}</td>
              <td>{{ formatCurrency(invoice.amount) }}</td>
              <td>{{ formatDate(invoice.due_date) }}</td>
              <td><span class="status-badge" :class="getStatusClass(invoice.status)">{{ invoice.status }}</span></td>
              <td class="actions">
                <Link :href="`/invoices/${invoice.id}`" class="action-link">View</Link>
                <button class="action-btn" @click="downloadInvoice(invoice)" title="Download PDF">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                  </svg>
                </button>
              </td>
            </tr>
            <tr v-if="invoices.data.length === 0">
              <td colspan="6" style="text-align: center; color: #4a6357;">No invoices found</td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="invoices.last_page > 1" class="pagination">
          <button :disabled="invoices.current_page === 1" @click="goToPage(invoices.current_page - 1)" class="pagination-btn">
            Previous
          </button>
          <span class="pagination-info">Page {{ invoices.current_page }} of {{ invoices.last_page }}</span>
          <button :disabled="invoices.current_page === invoices.last_page" @click="goToPage(invoices.current_page + 1)" class="pagination-btn">
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Generate Invoices Modal -->
    <Modal :show="showGenerateModal" @close="showGenerateModal = false" title="Generate Monthly Invoices">
      <form @submit.prevent="generateInvoices">
        <div class="form-group">
          <label>Month</label>
          <select v-model="generateForm.month" class="form-input" required>
            <option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>Year</label>
          <select v-model="generateForm.year" class="form-input" required>
            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
          </select>
        </div>
        <p class="modal-info">This will generate invoices for all active clients for the selected month.</p>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showGenerateModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="generateInvoices" :disabled="generateForm.processing">
          {{ generateForm.processing ? 'Generating...' : 'Generate' }}
        </button>
      </template>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  invoices: { type: Object, default: () => ({ data: [], total: 0, last_page: 1, current_page: 1 }) },
  clients: { type: Array, default: () => [] },
})

const showGenerateModal = ref(false)

const filters = ref({
  status: '',
  client_id: '',
})

const generateForm = useForm({
  month: new Date().getMonth() + 1,
  year: new Date().getFullYear(),
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

const hasFilters = computed(() => filters.value.status || filters.value.client_id)

const totalOutstanding = computed(() => {
  return props.invoices.data
    .filter(i => i.status === 'pending' || i.status === 'overdue')
    .reduce((sum, i) => sum + (i.amount - (i.amount_paid || 0)), 0)
})

const paidThisMonth = computed(() => {
  const now = new Date()
  return props.invoices.data
    .filter(i => {
      const paidDate = new Date(i.paid_at)
      return i.status === 'paid' && paidDate.getMonth() === now.getMonth() && paidDate.getFullYear() === now.getFullYear()
    })
    .reduce((sum, i) => sum + i.amount, 0)
})

const applyFilters = () => {
  router.get('/invoices', filters.value, { preserveState: false })
}

const clearFilters = () => {
  filters.value = { status: '', client_id: '' }
  applyFilters()
}

const goToPage = (page) => {
  router.get('/invoices', { ...filters.value, page }, { preserveScroll: true })
}

const generateInvoices = () => {
  generateForm.post('/invoices/generate', {
    onSuccess: () => {
      showGenerateModal.value = false
    }
  })
}

const applyPenalties = () => {
  if (confirm('Are you sure you want to apply penalties to all overdue invoices?')) {
    router.post('/invoices/apply-penalties', {}, {
      onSuccess: () => {
        alert('Penalties applied successfully')
      }
    })
  }
}

const downloadInvoice = (invoice) => {
  window.location.href = `/invoices/${invoice.id}/download`
}

const getStatusClass = (status) => {
  const classes = {
    paid: 'status-badge--success',
    pending: 'status-badge--warning',
    overdue: 'status-badge--danger',
    cancelled: 'status-badge--neutral',
  }
  return classes[status] || 'status-badge--neutral'
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('sw-TZ', {
    style: 'currency',
    currency: 'TZS',
    minimumFractionDigits: 0,
  }).format(value || 0)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-TZ', { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<style scoped>
.invoices-container {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.header h1 {
  font-size: 24px;
  font-weight: 600;
  color: #1a2e24;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  padding: 8px 16px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  background: white;
  color: #4a6357;
  transition: background 0.15s;
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

.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 13px;
  color: #4a6357;
  background: white;
}

.clear-filters-btn {
  padding: 8px 16px;
  background: #f5f5f5;
  color: #4a6357;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.15s;
}

.clear-filters-btn:hover {
  background: #e5e5e5;
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

.card {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.card-header {
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid rgba(0,0,0,0.08);
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

.data-table tr:last-child td {
  border-bottom: none;
}

.actions {
  display: flex;
  gap: 8px;
}

.action-link {
  color: #4caf76;
  text-decoration: none;
  font-size: 12px;
  font-weight: 500;
}

.action-link:hover {
  text-decoration: underline;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge--success {
  background: #e8f5e9;
  color: #2d7a50;
}

.status-badge--warning {
  background: #fef3c7;
  color: #92400e;
}

.status-badge--danger {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge--neutral {
  background: #f5f5f5;
  color: #4a6357;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 20px;
}

.pagination-btn {
  padding: 8px 16px;
  background: white;
  color: #4a6357;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.15s;
}

.pagination-btn:hover:not(:disabled) {
  background: #f0faf3;
  border-color: #4caf76;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  font-size: 13px;
  color: #4a6357;
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

.modal-info {
  color: #4a6357;
  font-size: 12px;
  margin-top: 12px;
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

@media (max-width: 768px) {
  .summary-cards {
    grid-template-columns: 1fr;
  }
  .header-actions {
    flex-direction: column;
  }
}
</style>
