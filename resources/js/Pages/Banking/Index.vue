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
        <button class="action-btn action-btn--primary" @click="showAddDepositModal = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          New Deposit
        </button>
        <button class="action-btn" @click="showAddAccountModal = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          Add Bank Account
        </button>
        <button class="action-btn" @click="showStatementModal = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Upload Statement
        </button>
        <button class="action-btn" @click="showCashPositionModal = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125z"/>
          </svg>
          Cash Position
        </button>
        <button class="action-btn" @click="exportStatement">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export Statement
        </button>
      </div>

      <!-- Bank Accounts -->
      <div class="accounts-section">
        <h3>Bank Accounts</h3>
        <div class="accounts-grid">
          <div v-for="account in bankAccounts" :key="account.id" class="account-card">
            <div class="account-header">
              <div class="account-name">{{ account.bank_name }}</div>
              <div class="account-balance">{{ formatCurrency(account.current_balance || account.balance) }}</div>
            </div>
            <div class="account-details">
              <div class="account-detail">
                <span class="detail-label">Account Number:</span>
                <span class="detail-value">{{ account.account_number }}</span>
              </div>
              <div class="account-detail">
                <span class="detail-label">Account Name:</span>
                <span class="detail-value">{{ account.account_name }}</span>
              </div>
              <div class="account-detail">
                <span class="detail-label">Currency:</span>
                <span class="detail-value">{{ account.currency }}</span>
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
              <td class="td-actions">
                <button v-if="deposit.status === 'pending'" class="table-action table-action--success" @click="reconcileDeposit(deposit)">Confirm</button>
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

    <!-- Add Deposit Modal -->
    <Modal :show="showAddDepositModal" @close="showAddDepositModal = false" title="New Deposit">
      <form @submit.prevent="addDeposit">
        <div class="form-group">
          <label>Bank Account</label>
          <select v-model="addDepositForm.bank_account_id" class="form-input" required>
            <option value="">Select account</option>
            <option v-for="account in bankAccounts" :key="account.id" :value="account.id">{{ account.bank_name }} - {{ account.account_number }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>Amount</label>
          <input type="number" v-model="addDepositForm.amount" class="form-input" min="0" step="0.01" required>
        </div>
        <div class="form-group">
          <label>Deposit Date</label>
          <input type="date" v-model="addDepositForm.deposit_date" class="form-input" required>
        </div>
        <div class="form-group">
          <label>Slip Image (Optional)</label>
          <input type="file" ref="slipImage" class="form-input" accept=".jpg,.png,.pdf" />
        </div>
        <div class="form-group">
          <label>Reference (Optional)</label>
          <input type="text" v-model="addDepositForm.reference" class="form-input">
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showAddDepositModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="addDeposit" :disabled="addDepositForm.processing">
          {{ addDepositForm.processing ? 'Adding...' : 'Add Deposit' }}
        </button>
      </template>
    </Modal>

    <!-- Add Bank Account Modal -->
    <Modal :show="showAddAccountModal" @close="showAddAccountModal = false" title="Add Bank Account">
      <form @submit.prevent="addBankAccount">
        <div class="form-group">
          <label>Bank Name</label>
          <input type="text" v-model="addAccountForm.bank_name" class="form-input" required>
        </div>
        <div class="form-group">
          <label>Account Number</label>
          <input type="text" v-model="addAccountForm.account_number" class="form-input" required>
        </div>
        <div class="form-group">
          <label>Account Name</label>
          <input type="text" v-model="addAccountForm.account_name" class="form-input" required>
        </div>
        <div class="form-group">
          <label>Opening Balance</label>
          <input type="number" v-model="addAccountForm.opening_balance" class="form-input" min="0" step="0.01">
        </div>
        <div class="form-group">
          <label>Currency</label>
          <select v-model="addAccountForm.currency" class="form-input" required>
            <option value="TZS">TZS - Tanzanian Shilling</option>
            <option value="USD">USD - US Dollar</option>
            <option value="EUR">EUR - Euro</option>
          </select>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showAddAccountModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="addBankAccount" :disabled="addAccountForm.processing">
          {{ addAccountForm.processing ? 'Adding...' : 'Add Account' }}
        </button>
      </template>
    </Modal>

    <!-- Upload Statement Modal -->
    <Modal :show="showStatementModal" @close="showStatementModal = false" title="Upload Bank Statement">
      <form @submit.prevent="uploadStatement">
        <div class="form-group">
          <label>Bank Account</label>
          <select v-model="statementForm.bank_account_id" class="form-input" required>
            <option value="">Select account</option>
            <option v-for="account in bankAccounts" :key="account.id" :value="account.id">{{ account.bank_name }} - {{ account.account_number }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>Statement File (CSV or PDF)</label>
          <input type="file" ref="statementFile" class="form-input" accept=".csv,.pdf" required />
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showStatementModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="uploadStatement" :disabled="statementForm.processing">
          {{ statementForm.processing ? 'Uploading...' : 'Upload & Reconcile' }}
        </button>
      </template>
    </Modal>

    <!-- Cash Position Modal -->
    <Modal :show="showCashPositionModal" @close="showCashPositionModal = false" title="Daily Cash Position">
      <div class="cash-position-content">
        <div class="form-group">
          <label>Date</label>
          <input type="date" v-model="cashPositionDate" class="form-input" @change="loadCashPosition" />
        </div>
        <div v-if="cashPositionData" class="cash-position-details">
          <div class="cp-row">
            <span class="cp-label">Cash Received</span>
            <span class="cp-value cp-value--positive">{{ formatCurrency(cashPositionData.cash_received) }}</span>
          </div>
          <div class="cp-row">
            <span class="cp-label">Cash Expenses</span>
            <span class="cp-value cp-value--negative">{{ formatCurrency(cashPositionData.cash_expenses) }}</span>
          </div>
          <div class="cp-row cp-row--total">
            <span class="cp-label">Net Cash</span>
            <span class="cp-value">{{ formatCurrency(cashPositionData.net_cash) }}</span>
          </div>
          <div class="cp-row">
            <span class="cp-label">Amount Banked</span>
            <span class="cp-value">{{ formatCurrency(cashPositionData.amount_banked) }}</span>
          </div>
          <div class="cp-row cp-row--highlight">
            <span class="cp-label">Cash in Hand</span>
            <span class="cp-value cp-value--highlight">{{ formatCurrency(cashPositionData.cash_in_hand) }}</span>
          </div>
        </div>
      </div>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showCashPositionModal = false">Close</button>
        <button class="modal-btn modal-btn--primary" @click="exportCashPosition">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14" style="display:inline;vertical-align:middle;margin-right:4px">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export PDF
        </button>
      </template>
    </Modal>

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
  },
  cashPosition: {
    type: Object,
    default: () => ({})
  }
})

const showAddDepositModal = ref(false)
const showAddAccountModal = ref(false)
const showStatementModal = ref(false)
const showCashPositionModal = ref(false)
const showDeleteModal = ref(false)
const depositToDelete = ref(null)
const cashPositionDate = ref(new Date().toISOString().slice(0, 10))
const cashPositionData = ref(null)

const addDepositForm = useForm({
  bank_account_id: '',
  amount: '',
  deposit_date: '',
  reference: ''
})

const addAccountForm = useForm({
  bank_name: '',
  account_number: '',
  account_name: '',
  opening_balance: 0,
  currency: 'TZS'
})

const statementForm = useForm({
  bank_account_id: ''
})

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

const addDeposit = () => {
  const formData = new FormData()
  formData.append('bank_account_id', addDepositForm.bank_account_id)
  formData.append('amount', addDepositForm.amount)
  formData.append('deposit_date', addDepositForm.deposit_date)
  formData.append('reference', addDepositForm.reference)
  
  const fileInput = document.querySelector('input[type="file"]')
  if (fileInput && fileInput.files[0]) {
    formData.append('slip_image', fileInput.files[0])
  }
  
  router.post('/banking/deposits', formData, {
    onSuccess: () => {
      showAddDepositModal.value = false
      addDepositForm.reset()
    }
  })
}

const addBankAccount = () => {
  addAccountForm.post('/banking/accounts', {
    onSuccess: () => {
      showAddAccountModal.value = false
      addAccountForm.reset()
    }
  })
}

const uploadStatement = () => {
  const formData = new FormData()
  formData.append('bank_account_id', statementForm.bank_account_id)
  
  const fileInput = document.querySelector('#statementFile input[type="file"]')
  if (fileInput && fileInput.files[0]) {
    formData.append('statement_file', fileInput.files[0])
  }
  
  router.post(`/banking/statements/${statementForm.bank_account_id}`, formData, {
    onSuccess: () => {
      showStatementModal.value = false
      statementForm.reset()
    }
  })
}

const reconcileDeposit = (deposit) => {
  router.post(`/banking/deposits/${deposit.id}/reconcile`, {}, {
    onSuccess: () => {
      router.reload()
    }
  })
}

const loadCashPosition = async () => {
  const response = await fetch(`/banking/cash-position?date=${cashPositionDate.value}`)
  cashPositionData.value = await response.json()
}

const exportCashPosition = () => {
  window.location.href = `/banking/cash-position?date=${cashPositionDate.value}&export=pdf`
}

const exportStatement = () => {
  window.location.href = '/banking/export?format=csv'
}

const reconcileAccounts = () => {
  window.location.href = '/banking/reconcile'
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

.table-action--success {
  color: #2d7a50;
}

.table-action--success:hover {
  border-color: #2d7a50;
}

.td-actions {
  display: flex;
  gap: 4px;
}

.cash-position-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.cash-position-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.cp-row {
  display: flex;
  justify-content: space-between;
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
}

.cp-row--total {
  background: #e8f5e9;
}

.cp-row--highlight {
  background: #fff3e0;
  border: 2px solid #e65100;
}

.cp-label {
  font-size: 13px;
  color: #4a6357;
}

.cp-value {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
}

.cp-value--positive {
  color: #2d7a50;
}

.cp-value--negative {
  color: #c62828;
}

.cp-value--highlight {
  color: #e65100;
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

.modal-btn--primary {
  background: #4caf76;
  border: 1px solid #4caf76;
  color: white;
}

.modal-btn--primary:hover {
  background: #2d7a50;
  border-color: #2d7a50;
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
