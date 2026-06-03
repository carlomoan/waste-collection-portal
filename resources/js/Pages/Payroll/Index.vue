<template>
  <AppLayout title="Payroll">
    <div class="payroll-container">
      <div class="header">
        <h1>Payroll</h1>
        <p>Manage staff payroll and payments</p>
      </div>

      <!-- Month Selector -->
      <div class="month-selector">
        <button class="month-btn" @click="previousMonth">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
          </svg>
        </button>
        <div class="current-month">{{ currentMonthYear }}</div>
        <button class="month-btn" @click="nextMonth">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
          </svg>
        </button>
      </div>

      <!-- Summary Cards -->
      <div class="summary-grid">
        <div class="summary-card">
          <div class="summary-label">Total Payroll</div>
          <div class="summary-value">{{ formatCurrency(totalPayroll) }}</div>
          <div class="summary-change summary-change--neutral">For {{ currentMonthYear }}</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Paid</div>
          <div class="summary-value">{{ formatCurrency(paidAmount) }}</div>
          <div class="summary-change summary-change--positive">{{ paidCount }} staff members paid</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Pending</div>
          <div class="summary-value">{{ formatCurrency(pendingAmount) }}</div>
          <div class="summary-change summary-change--neutral">{{ pendingCount }} staff pending payment</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Total Staff</div>
          <div class="summary-value">{{ staff.length }}</div>
          <div class="summary-change summary-change--positive">Active collectors</div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="actions-bar">
        <Link :href="route('payroll.generate')" class="action-btn action-btn--primary">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          Generate Payroll
        </Link>
        <button class="action-btn" @click="exportPayslips">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export Payslips
        </button>
        <button class="action-btn" @click="exportBankFile">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export Bank File
        </button>
        <button class="action-btn" @click="processPayments">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
          </svg>
          Process Payments
        </button>
        <button class="action-btn" @click="showAdvanceModal = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          Request Advance
        </button>
      </div>

      <!-- Staff Payroll List -->
      <div class="payroll-section">
        <div class="section-header">
          <h3>Staff Payroll - {{ currentMonthYear }}</h3>
          <div class="filter-actions">
            <select class="filter-select">
              <option>All Staff</option>
              <option>Collectors</option>
              <option>Drivers</option>
              <option>Admin</option>
            </select>
            <button class="view-all-btn">View All</button>
          </div>
        </div>
        <table class="payroll-table">
          <thead>
            <tr>
              <th>Staff</th>
              <th>Role</th>
              <th>Base Salary</th>
              <th>Overtime</th>
              <th>Deductions</th>
              <th>Net Pay</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="payment in salaryPayments" :key="payment.id">
              <td>
                <div class="staff-info">
                  <div class="staff-name">{{ payment.staff_name }}</div>
                  <div class="staff-id">STF-{{ payment.id }}</div>
                </div>
              </td>
              <td>Collector</td>
              <td>{{ formatCurrency(payment.base_salary) }}</td>
              <td>{{ formatCurrency(payment.commissions) }}</td>
              <td>{{ formatCurrency(payment.deductions) }}</td>
              <td>{{ formatCurrency(payment.net_salary) }}</td>
              <td><span class="status-badge" :class="`status-badge--${payment.status}`">{{ payment.status }}</span></td>
              <td class="td-actions">
                <button class="table-action" @click="downloadPayslip(payment)">PDF</button>
                <button class="table-action" @click="emailPayslip(payment)">Email</button>
                <button class="table-action">View</button>
              </td>
            </tr>
            <tr v-if="salaryPayments.length === 0">
              <td colspan="8" style="text-align: center; color: #4a6357;">No payroll data found</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Payroll Summary -->
      <div class="summary-section">
        <h3>Payroll Summary</h3>
        <div class="summary-details">
          <div class="summary-row">
            <span class="summary-detail-label">Total Base Salary</span>
            <span class="summary-detail-value">{{ formatCurrency(payrollSummary.total_base_salary || 0) }}</span>
          </div>
          <div class="summary-row">
            <span class="summary-detail-label">Total Overtime</span>
            <span class="summary-detail-value">{{ formatCurrency(payrollSummary.total_commissions || 0) }}</span>
          </div>
          <div class="summary-row">
            <span class="summary-detail-label">Total Deductions</span>
            <span class="summary-detail-value">{{ formatCurrency(payrollSummary.total_deductions || 0) }}</span>
          </div>
          <div class="summary-row summary-row--total">
            <span class="summary-detail-label">Net Payroll Total</span>
            <span class="summary-detail-value">{{ formatCurrency(payrollSummary.total_net || 0) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Salary Advance Modal -->
    <Modal :show="showAdvanceModal" title="Request Salary Advance" @close="showAdvanceModal = false">
      <form @submit.prevent="submitAdvance">
        <div class="form-group">
          <label>Staff Member</label>
          <select v-model="advanceForm.staff_id" class="form-input" required>
            <option value="">Select staff</option>
            <option v-for="s in staff" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>Amount (TZS)</label>
          <input type="number" v-model="advanceForm.amount" class="form-input" required :max="maxAdvanceAmount" />
          <small class="form-hint">Maximum: {{ formatCurrency(maxAdvanceAmount) }}</small>
        </div>
        <div class="form-group">
          <label>Reason</label>
          <textarea v-model="advanceForm.reason" class="form-input" rows="3" required></textarea>
        </div>
      </form>
      <template #footer>
        <button class="btn-secondary" @click="showAdvanceModal = false">Cancel</button>
        <button class="btn-primary" @click="submitAdvance" :disabled="advanceForm.processing">
          {{ advanceForm.processing ? 'Submitting...' : 'Submit Request' }}
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
  staff: {
    type: Array,
    default: () => []
  },
  salaryPayments: {
    type: Array,
    default: () => []
  },
  month: {
    type: Number,
    default: null
  },
  year: {
    type: Number,
    default: null
  },
  payrollSummary: {
    type: Object,
    default: () => ({})
  }
})

const showAdvanceModal = ref(false)

const advanceForm = useForm({
  staff_id: '',
  amount: '',
  reason: ''
})

const maxAdvanceAmount = computed(() => {
  const selectedStaff = props.staff.find(s => s.id === advanceForm.staff_id)
  return selectedStaff ? (selectedStaff.base_salary * 0.5) : 0
})

const currentDate = ref(new Date(props.year || new Date().getFullYear(), (props.month || new Date().getMonth() + 1) - 1))

const currentMonthYear = computed(() => {
  return currentDate.value.toLocaleDateString('en-TZ', {
    year: 'numeric',
    month: 'long',
  })
})

const previousMonth = () => {
  currentDate.value = new Date(currentDate.value.setMonth(currentDate.value.getMonth() - 1))
}

const nextMonth = () => {
  currentDate.value = new Date(currentDate.value.setMonth(currentDate.value.getMonth() + 1))
}

const totalPayroll = computed(() => {
  return props.salaryPayments.reduce((sum, payment) => sum + (payment.net_salary || 0), 0)
})

const paidAmount = computed(() => {
  return props.salaryPayments
    .filter(p => p.status === 'paid')
    .reduce((sum, payment) => sum + (payment.net_salary || 0), 0)
})

const pendingAmount = computed(() => {
  return props.salaryPayments
    .filter(p => p.status === 'pending')
    .reduce((sum, payment) => sum + (payment.net_salary || 0), 0)
})

const paidCount = computed(() => {
  return props.salaryPayments.filter(p => p.status === 'paid').length
})

const pendingCount = computed(() => {
  return props.salaryPayments.filter(p => p.status === 'pending').length
})

const formatCurrency = (value) => {
  return new Intl.NumberFormat('sw-TZ', {
    style: 'currency',
    currency: 'TZS',
    minimumFractionDigits: 0,
  }).format(value)
}

const exportPayslips = () => {
  const month = currentDate.value.getMonth() + 1
  const year = currentDate.value.getFullYear()
  window.location.href = `/payroll/export?month=${month}&year=${year}&format=csv`
}

const processPayments = () => {
  const month = currentDate.value.getMonth() + 1
  const year = currentDate.value.getFullYear()
  router.post('/payroll/process', { month, year })
}

const exportBankFile = () => {
  const month = currentDate.value.getMonth() + 1
  const year = currentDate.value.getFullYear()
  window.location.href = `/payroll/export-bank?month=${month}&year=${year}`
}

const downloadPayslip = (payment) => {
  window.location.href = `/payroll/${payment.id}/payslip`
}

const emailPayslip = (payment) => {
  router.post(`/payroll/${payment.id}/email-payslip`, {}, {
    onSuccess: () => {
      alert('Payslip sent successfully')
    }
  })
}

const submitAdvance = () => {
  advanceForm.post('/payroll/advance-request', {
    onSuccess: () => {
      showAdvanceModal.value = false
      advanceForm.reset()
    }
  })
}
</script>

<style scoped>
.payroll-container {
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

.month-selector {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 12px 20px;
  width: fit-content;
}

.month-btn {
  padding: 8px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  background: white;
  color: #4a6357;
  cursor: pointer;
  transition: all 0.15s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.month-btn:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.current-month {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
  min-width: 150px;
  text-align: center;
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

.payroll-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 24px;
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

.filter-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 12px;
  color: #4a6357;
  background: white;
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

.payroll-table {
  width: 100%;
  border-collapse: collapse;
}

.payroll-table th {
  text-align: left;
  padding: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #4a6357;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.payroll-table td {
  padding: 12px;
  font-size: 13px;
  color: #1a2e24;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.payroll-table tr:last-child td {
  border-bottom: none;
}

.staff-info {
  display: flex;
  flex-direction: column;
}

.staff-name {
  font-weight: 500;
  color: #1a2e24;
}

.staff-id {
  font-size: 11px;
  color: #4a6357;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge--paid {
  background: #e8f5e9;
  color: #2d7a50;
}

.status-badge--pending {
  background: #fff3e0;
  color: #e65100;
}

.status-badge--processing {
  background: #e3f2fd;
  color: #1565c0;
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

.summary-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.summary-section h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 16px;
}

.summary-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.summary-row--total {
  border-top: 2px solid rgba(0,0,0,0.08);
  border-bottom: none;
  padding-top: 16px;
  margin-top: 8px;
}

.summary-detail-label {
  font-size: 13px;
  color: #4a6357;
}

.summary-detail-value {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
}

.summary-row--total .summary-detail-value {
  font-size: 18px;
  color: #2d7a50;
}

.td-actions {
  display: flex;
  gap: 8px;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  font-size: 12px;
  font-weight: 500;
  color: #4a6357;
  margin-bottom: 6px;
}

.form-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 13px;
  color: #1a2e24;
}

.form-input:focus {
  outline: none;
  border-color: #4caf76;
}

.form-hint {
  display: block;
  font-size: 11px;
  color: #7a9489;
  margin-top: 4px;
}

.btn-secondary {
  padding: 8px 16px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 12px;
  color: #4a6357;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-secondary:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.btn-primary {
  padding: 8px 16px;
  background: #4caf76;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-primary:hover {
  background: #2d7a50;
}

@media (max-width: 1024px) {
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
