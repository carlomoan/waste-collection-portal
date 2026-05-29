<template>
  <AppLayout title="Banking">
    <div class="banking-container">
      <div class="header">
        <h1>Banking</h1>
        <p>Manage bank deposits and transactions</p>
      </div>

      <!-- Summary Cards -->
      <div class="summary-grid">
        <div class="summary-card">
          <div class="summary-label">Total Deposits (This Month)</div>
          <div class="summary-value">{{ formatCurrency(recentDeposits.reduce((sum, d) => sum + (d.amount || 0), 0)) }}</div>
          <div class="summary-change summary-change--positive">{{ recentDeposits.length }} deposits this month</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Pending Deposits</div>
          <div class="summary-value">{{ formatCurrency(recentDeposits.filter(d => d.status === 'pending').reduce((sum, d) => sum + (d.amount || 0), 0)) }}</div>
          <div class="summary-change summary-change--neutral">{{ recentDeposits.filter(d => d.status === 'pending').length }} deposits awaiting confirmation</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Bank Accounts</div>
          <div class="summary-value">{{ bankAccounts.length }} Active</div>
          <div class="summary-change summary-change--neutral">{{ bankAccounts.map(a => a.bank_name).join(', ') }}</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Last Deposit</div>
          <div class="summary-value">{{ formatDate(recentDeposits[0]?.date) }}</div>
          <div class="summary-change summary-change--positive">{{ formatCurrency(recentDeposits[0]?.amount || 0) }}</div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="actions-bar">
        <button class="action-btn action-btn--primary" @click="navigateToNewDeposit">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          New Deposit
        </button>
        <button class="action-btn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export Statement
        </button>
        <button class="action-btn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
          </svg>
          Reconcile
        </button>
      </div>

      <!-- Bank Accounts -->
      <div class="accounts-section">
        <h3>Bank Accounts</h3>
        <div class="accounts-grid">
          <div v-for="account in bankAccounts" :key="account.id" class="account-card">
            <div class="account-header">
              <div class="account-name">{{ account.bank_name }}</div>
              <div class="account-balance">{{ formatCurrency(account.balance) }}</div>
            </div>
            <div class="account-details">
              <div class="account-detail">
                <span class="detail-label">Account Number:</span>
                <span class="detail-value">{{ account.account_number }}</span>
              </div>
              <div class="account-detail">
                <span class="detail-label">Status:</span>
                <span class="detail-value status--active">{{ account.status }}</span>
              </div>
            </div>
          </div>
          <div v-if="bankAccounts.length === 0" style="grid-column: 1/-1; text-align: center; color: #4a6357; padding: 40px;">
            No bank accounts found
          </div>
        </div>
      </div>

      <!-- Recent Deposits -->
      <div class="deposits-section">
        <div class="section-header">
          <h3>Recent Deposits</h3>
          <button class="view-all-btn">View All</button>
        </div>
        <table class="deposits-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Bank</th>
              <th>Amount</th>
              <th>Reference</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="deposit in recentDeposits" :key="deposit.id">
              <td>{{ formatDate(deposit.date) }}</td>
              <td>{{ deposit.bank_account }}</td>
              <td>{{ formatCurrency(deposit.amount) }}</td>
              <td>{{ deposit.reference }}</td>
              <td><span class="status-badge" :class="`status-badge--${deposit.status}`">{{ deposit.status }}</span></td>
              <td>
                <button class="table-action" @click="confirmDelete(deposit)">Delete</button>
              </td>
            </tr>
            <tr v-if="recentDeposits.length === 0">
              <td colspan="6" style="text-align: center; color: #4a6357;">No deposits found</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Modal :show="showDeleteModal" @close="showDeleteModal = false" title="Delete Deposit">
      <p>Are you sure you want to delete this deposit? This action cannot be undone.</p>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showDeleteModal = false">Cancel</button>
        <button class="modal-btn modal-btn--danger" @click="deleteDeposit" :disabled="deleteForm.processing">
          {{ deleteForm.processing ? 'Deleting...' : 'Delete' }}
        </button>
      </template>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  bankAccounts: {
    type: Array,
    default: () => []
  },
  recentDeposits: {
    type: Array,
    default: () => []
  }
})

const showAddModal = ref(false)
const showDeleteModal = ref(false)
const depositToDelete = ref(null)

const deleteForm = useForm({})

const formatCurrency = (value) => {
  return new Intl.NumberFormat('sw-TZ', {
    style: 'currency',
    currency: 'TZS',
    minimumFractionDigits: 0,
  }).format(value)
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-TZ', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const confirmDelete = (deposit) => {
  depositToDelete.value = deposit
  showDeleteModal.value = true
}

const deleteDeposit = () => {
  if (depositToDelete.value) {
    deleteForm.delete(`/banking/${depositToDelete.value.id}`, {
      onSuccess: () => {
        showDeleteModal.value = false
        depositToDelete.value = null
      }
    })
  }
}

const navigateToNewDeposit = () => {
  router.visit('/banking/create')
}
</script>

<style scoped>
.banking-container {
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

.accounts-section {
  margin-bottom: 24px;
}

.accounts-section h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 16px;
}

.accounts-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.account-card {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.account-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.account-name {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
}

.account-balance {
  font-size: 18px;
  font-weight: 600;
  color: #2d7a50;
}

.account-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.account-detail {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
}

.detail-label {
  color: #4a6357;
}

.detail-value {
  color: #1a2e24;
  font-weight: 500;
}

.status--active {
  color: #2d7a50;
}

.deposits-section {
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

.deposits-table {
  width: 100%;
  border-collapse: collapse;
}

.deposits-table th {
  text-align: left;
  padding: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #4a6357;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.deposits-table td {
  padding: 12px;
  font-size: 13px;
  color: #1a2e24;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.deposits-table tr:last-child td {
  border-bottom: none;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge--confirmed {
  background: #e8f5e9;
  color: #2d7a50;
}

.status-badge--pending {
  background: #fff3e0;
  color: #e65100;
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

.modal-btn {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.15s;
}

.modal-btn--cancel {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  color: #4a6357;
}

.modal-btn--cancel:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.modal-btn--danger {
  background: #c0392b;
  border: 1px solid #c0392b;
  color: white;
}

.modal-btn--danger:hover {
  background: #a93226;
  border-color: #a93226;
}

.modal-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 1024px) {
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .accounts-grid {
    grid-template-columns: 1fr;
  }
}
</style>
