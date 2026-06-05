<template>
  <AppLayout title="Transactions">

    <!-- Top actions -->
    <div class="page-actions">
      <button class="btn-primary" @click="showImportModal = true">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
          width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21
               18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
        </svg>
        Import PDF / Excel
      </button>
      <Link href="/transactions/create" class="btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
          width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Add Single
      </Link>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <input v-model="localSearch" class="search-input" placeholder="Search payer name or control number…"
        @keyup.enter="applyFilters" />
      <select v-model="localStatus" class="filter-select">
        <option value="">All Status</option>
        <option value="paid">Paid</option>
        <option value="refunded">Refunded</option>
      </select>
      <select v-model="localMonth" class="filter-select">
        <option value="">All Months</option>
        <option v-for="m in monthOptions" :key="m.value" :value="m.value">{{ m.label }}</option>
      </select>
      <select v-model="localCollector" class="filter-select">
        <option value="">All Collectors</option>
        <option v-for="c in collectors" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <button class="search-btn" @click="applyFilters">Search</button>
      <button v-if="hasActiveFilters" class="clear-btn" @click="clearFilters">✕ Clear</button>
      <div class="filters-right">
        <span class="result-count">{{ payments.total }} records</span>
        <button v-if="selectedPayments.length > 0" class="batch-btn" @click="showReconcileModal = true">
          Reconcile Selected ({{ selectedPayments.length }})
        </button>
        <button v-if="selectedPayments.length > 0" class="batch-btn" @click="exportBatch">
          Export Batch ({{ selectedPayments.length }})
        </button>
        <a :href="exportPdfUrl" class="export-btn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
            stroke="currentColor" width="14" height="14">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
          </svg>
          Export PDF
        </a>
        <a :href="exportCsvUrl" class="export-btn">Export CSV</a>
      </div>
    </div>

    <!-- Summary strip -->
    <div class="summary-strip">
      <div class="strip-item">
        <span class="strip-label">Total Records</span>
        <span class="strip-val">{{ summary.total?.toLocaleString() }}</span>
      </div>
      <div class="strip-item">
        <span class="strip-label">Total Collected</span>
        <span class="strip-val green">{{ formatTZS(summary.total_amount) }}</span>
      </div>
      <div class="strip-item">
        <span class="strip-label">Paid</span>
        <span class="strip-val green">{{ summary.paid?.toLocaleString() }}</span>
      </div>
      <div class="strip-item">
        <span class="strip-label">Unmatched</span>
        <span class="strip-val amber">{{ summary.unmatched }}</span>
      </div>
    </div>

    <!-- Table -->
    <div class="card">
      <table class="tx-table">
        <thead>
          <tr>
            <th><input type="checkbox" v-model="selectAll" @change="toggleSelectAll" /></th>
            <th>No.</th>
            <th>Control Number</th>
            <th>Payer / Client</th>
            <th>Amount (TZS)</th>
            <th>Collector</th>
            <th>Receipt</th>
            <th>Status</th>
            <th>Date & Time</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(tx, i) in payments.data" :key="tx.id">
            <td><input type="checkbox" v-model="selectedPayments" :value="tx.id" /></td>
            <td class="td-num">{{ (payments.current_page - 1) * payments.per_page + i + 1 }}</td>
            <td class="td-mono">{{ tx.control_number }}</td>
            <td>
              <div class="client-cell">
                <div class="payer-av">{{ initials(tx.payer_name) }}</div>
                <div>
                  <div class="payer-name">{{ tx.payer_name || '—' }}</div>
                  <div v-if="tx.client_number" class="client-num">{{ tx.client_number }}</div>
                </div>
              </div>
            </td>
            <td class="td-amount">{{ formatTZS(tx.amount) }}</td>
            <td class="td-collector">{{ tx.collector ?? '—' }}</td>
            <td class="td-mono small">{{ tx.receipt ?? '—' }}</td>
            <td>
              <span class="status-badge" :class="`status--${tx.status}`">
                {{ tx.status?.toUpperCase() }}
              </span>
            </td>
            <td class="td-date">{{ formatDate(tx.paid_at) }}</td>
            <td class="td-actions">
              <button class="action-link" @click="openViewModal(tx)">View</button>
              <button class="action-link" @click="openEditModal(tx)">Edit</button>
              <button v-if="tx.status === 'paid'" class="action-link" @click="openRefundModal(tx)">Refund</button>
              <button class="action-link" @click="sendReceipt(tx)">Email</button>
              <button class="action-link danger-link" @click="openDeleteModal(tx)">Delete</button>
            </td>
          </tr>
          <tr v-if="!payments.data?.length">
            <td colspan="9" class="empty-row">
              No transactions found.
              <button @click="showImportModal = true" class="import-link">Import from PDF →</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="pagination">
        <a v-if="payments.prev_page_url" :href="payments.prev_page_url" class="page-btn">← Prev</a>
        <span v-else class="page-btn page-btn--disabled">← Prev</span>
        <span class="page-info">
          Page {{ payments.current_page }} of {{ payments.last_page }}
          &nbsp;·&nbsp; {{ payments.total }} total
        </span>
        <a v-if="payments.next_page_url" :href="payments.next_page_url" class="page-btn">Next →</a>
        <span v-else class="page-btn page-btn--disabled">Next →</span>
      </div>
    </div>

    <!-- Import Modal -->
    <Modal :show="showImportModal" title="Import Transactions" @close="showImportModal = false">
      <div class="import-modal-content">
        <p class="import-description">Upload POS PDF reports or Excel/CSV files to import transactions.</p>
        <div class="drop-zone" @click="$refs.fileInput.click()">
          <input ref="fileInput" type="file" accept=".pdf,.xlsx,.xls,.csv" class="hidden-input"
            @change="onFileSelected" />
          
          <div v-if="!selectedFile" class="drop-content">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
              stroke="currentColor" width="40" height="40" class="drop-icon">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6 m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <p class="drop-title">Drop file here or click to browse</p>
            <p class="drop-hint">PDF, Excel (.xlsx/.xls), or CSV · Max 10 MB</p>
          </div>

          <div v-else class="file-selected">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
              stroke-width="1.8" stroke="currentColor" width="28" height="28" class="file-icon">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
            </svg>
            <div>
              <p class="file-name">{{ selectedFile.name }}</p>
              <p class="file-size">{{ fileSize }}</p>
            </div>
            <button class="file-remove" @click.stop="clearFile">✕</button>
          </div>
        </div>
        <div class="import-actions">
          <button class="btn-secondary" @click="showImportModal = false">Cancel</button>
          <button class="btn-primary" @click="processImport" :disabled="previewLoading || importing || !selectedFile">
            {{ previewLoading ? 'Parsing...' : (importing ? 'Importing...' : 'Import') }}
          </button>
        </div>
      </div>
    </Modal>

    <!-- Refund Modal -->
    <Modal :show="showRefundModalOpen" title="Refund Payment" @close="showRefundModalOpen = false">
      <form @submit.prevent="processRefund">
        <div class="form-group">
          <label>Payment Amount</label>
          <input type="number" :value="paymentToRefund?.amount" class="form-input" disabled />
        </div>
        <div class="form-group">
          <label>Refund Amount (TZS)</label>
          <input type="number" v-model="refundForm.refund_amount" class="form-input" required :max="paymentToRefund?.amount" />
        </div>
        <div class="form-group">
          <label>Reason</label>
          <textarea v-model="refundForm.reason" class="form-input" rows="3" required></textarea>
        </div>
      </form>
      <template #footer>
        <button class="btn-secondary" @click="showRefundModalOpen = false">Cancel</button>
        <button class="btn-primary" @click="processRefund" :disabled="refundForm.processing">
          {{ refundForm.processing ? 'Processing...' : 'Process Refund' }}
        </button>
      </template>
    </Modal>

    <!-- View Transaction Modal -->
    <Modal :show="showViewModal" title="Transaction Details" @close="showViewModal = false">
      <div v-if="viewingTx" class="detail-grid">
        <div class="detail-row"><span class="detail-label">Receipt #</span><span class="detail-val mono">{{ viewingTx.receipt ?? '—' }}</span></div>
        <div class="detail-row"><span class="detail-label">Control #</span><span class="detail-val mono">{{ viewingTx.control_number }}</span></div>
        <div class="detail-row"><span class="detail-label">Payer</span><span class="detail-val">{{ viewingTx.payer_name }}</span></div>
        <div class="detail-row"><span class="detail-label">Client #</span><span class="detail-val">{{ viewingTx.client_number ?? '—' }}</span></div>
        <div class="detail-row"><span class="detail-label">Amount</span><span class="detail-val amount">{{ formatTZS(viewingTx.amount) }} TZS</span></div>
        <div class="detail-row"><span class="detail-label">Method</span><span class="detail-val">{{ viewingTx.payment_method ?? '—' }}</span></div>
        <div class="detail-row"><span class="detail-label">Collector</span><span class="detail-val">{{ viewingTx.collector ?? '—' }}</span></div>
        <div class="detail-row"><span class="detail-label">Status</span>
          <span class="status-badge" :class="`status--${viewingTx.status}`">{{ viewingTx.status?.toUpperCase() }}</span>
        </div>
        <div class="detail-row"><span class="detail-label">Date</span><span class="detail-val">{{ formatDate(viewingTx.paid_at) }}</span></div>
        <div class="detail-row"><span class="detail-label">POS #</span><span class="detail-val mono">{{ viewingTx.pos_number ?? '—' }}</span></div>
      </div>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showViewModal = false">Close</button>
        <button class="modal-btn modal-btn--primary" @click="openEditModal(viewingTx); showViewModal = false">Edit</button>
      </template>
    </Modal>

    <!-- Edit Transaction Modal -->
    <Modal :show="showEditModal" title="Edit Transaction" @close="showEditModal = false">
      <form v-if="editForm" @submit.prevent="submitEdit">
        <div class="form-group">
          <label>Payer Name</label>
          <input type="text" v-model="editForm.payer_name" class="form-input" />
        </div>
        <div class="form-group">
          <label>Amount (TZS)</label>
          <input type="number" v-model="editForm.amount" class="form-input" required />
        </div>
        <div class="form-group">
          <label>Payment Method</label>
          <select v-model="editForm.payment_method" class="form-input">
            <option value="cash">Cash</option>
            <option value="mobile_money">Mobile Money</option>
            <option value="bank">Bank</option>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select v-model="editForm.status" class="form-input">
            <option value="paid">Paid</option>
            <option value="pending">Pending</option>
            <option value="refunded">Refunded</option>
          </select>
        </div>
        <div class="form-group">
          <label>Paid At</label>
          <input type="datetime-local" v-model="editForm.paid_at" class="form-input" />
        </div>
        <div class="form-group">
          <label>Notes</label>
          <textarea v-model="editForm.notes" class="form-input" rows="2"></textarea>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showEditModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="submitEdit" :disabled="editForm?.processing">
          {{ editForm?.processing ? 'Saving...' : 'Save Changes' }}
        </button>
      </template>
    </Modal>

    <!-- Delete Confirm Modal -->
    <Modal :show="showDeleteModal" title="Delete Transaction" @close="showDeleteModal = false">
      <p class="modal-text">Delete transaction <strong>{{ deletingTx?.control_number }}</strong> for <strong>{{ formatTZS(deletingTx?.amount) }} TZS</strong>? This cannot be undone.</p>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showDeleteModal = false">Cancel</button>
        <button class="modal-btn modal-btn--danger" @click="submitDelete" :disabled="deleteForm?.processing">
          {{ deleteForm?.processing ? 'Deleting...' : 'Delete' }}
        </button>
      </template>
    </Modal>

    <!-- Reconcile Modal -->
    <Modal :show="showReconcileModal" title="Reconcile with Bank Deposit" @close="showReconcileModal = false">
      <form @submit.prevent="processReconcile">
        <div class="form-group">
          <label>Bank Deposit</label>
          <select v-model="reconcileForm.deposit_id" class="form-input" required>
            <option value="">Select deposit</option>
            <option v-for="deposit in bankDeposits" :key="deposit.id" :value="deposit.id">
              {{ deposit.reference }} - {{ formatTZS(deposit.amount) }} ({{ deposit.date }})
            </option>
          </select>
        </div>
        <p class="info-text">{{ selectedPayments.length }} payments selected. Total: {{ formatTZS(selectedTotal) }}</p>
      </form>
      <template #footer>
        <button class="btn-secondary" @click="showReconcileModal = false">Cancel</button>
        <button class="btn-primary" @click="processReconcile" :disabled="reconcileForm.processing">
          {{ reconcileForm.processing ? 'Reconciling...' : 'Reconcile' }}
        </button>
      </template>
    </Modal>

  </AppLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import axios from 'axios'

const showImportModal = ref(false)
const showRefundModalOpen = ref(false)
const showReconcileModal = ref(false)
const showViewModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const viewingTx = ref(null)
const editingTx = ref(null)
const deletingTx = ref(null)
const editForm = ref(null)
const deleteForm = ref(null)
const selectedFile = ref(null)
const previewData = ref(null)
const previewLoading = ref(false)
const importing = ref(false)
const importStep = ref('upload')

const fileSize = computed(() => {
  if (!selectedFile.value) return ''
  const kb = selectedFile.value.size / 1024
  return kb > 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${Math.round(kb)} KB`
})

const onFileSelected = (e) => {
  const file = e.target.files[0]
  if (file) {
    const allowed = [
      'application/pdf',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'text/csv'
    ]
    if (allowed.includes(file.type) || file.name.match(/\.(pdf|xlsx|xls|csv)$/i)) {
      selectedFile.value = file
    } else {
      alert('Unsupported file type. Please upload a PDF, Excel, or CSV file.')
    }
  }
}

const clearFile = () => {
  selectedFile.value = null
  const input = document.querySelector('input[type="file"]')
  if (input) input.value = ''
}

const processImport = async () => {
  if (!selectedFile.value) return
  previewLoading.value = true
  
  const formData = new FormData()
  formData.append('file', selectedFile.value)
  
  try {
    const previewRes = await axios.post('/transactions/preview', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    if (previewRes.data) {
      importing.value = true
      previewLoading.value = false
      
      const confirmRes = await axios.post('/transactions/confirm-import')
      
      if (confirmRes.data) {
        alert(`Successfully imported ${confirmRes.data.imported} payments!`)
        showImportModal.value = false
        selectedFile.value = null
        router.reload()
      } else {
        alert('Import failed. Please try again.')
      }
    }
  } catch (err) {
    const errorMsg = err.response?.data?.message 
      || err.response?.data?.error 
      || 'Failed to import. Please check file format and try again.'
    alert(errorMsg)
  } finally {
    previewLoading.value = false
    importing.value = false
  }
}
const selectedPayments = ref([])
const selectAll = ref(false)
const paymentToRefund = ref(null)
const bankDeposits = ref([])

const previewFile = async () => {
  if (!selectedFile.value) return
  previewLoading.value = true
  const formData = new FormData()
  formData.append('file', selectedFile.value)

  try {
    const response = await fetch('/transactions/preview', {
      method: 'POST',
      body: formData,
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    })
    const result = await response.json()
    if (response.ok) {
      previewData.value = result
      importStep.value = 'preview'
    } else {
      alert(result.error || 'Preview failed.')
    }
  } catch (e) {
    alert('An error occurred during preview.')
  } finally {
    previewLoading.value = false
  }
}

const confirmImport = () => {
  importStep.value = 'importing'
  router.post('/transactions/confirm-import', {}, {
    onSuccess: () => {
      showImportModal.value = false
      selectedFile.value = null
      previewData.value = null
      importStep.value = 'upload'
    },
    onError: () => {
      alert('Import failed.')
      importStep.value = 'preview'
    }
  })
}

const resetImport = () => {
  selectedFile.value = null
  previewData.value = null
  importStep.value = 'upload'
}

const props = defineProps({
  payments: { type: Object, default: () => ({ data: [], total: 0 }) },
  summary: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  collectors: { type: Array, default: () => [] },
  bankDeposits: { type: Array, default: () => [] },
})

bankDeposits.value = props.bankDeposits

// Local filter state (mirrors URL filters)
const localSearch = ref(props.filters.search ?? '')
const localStatus = ref(props.filters.status ?? '')
const localMonth = ref(props.filters.month ?? '')
const localCollector = ref(props.filters.collector_id ?? '')

const hasActiveFilters = computed(() =>
  localSearch.value || localStatus.value || localMonth.value || localCollector.value
)

const exportUrl = computed(() => {
  const params = new URLSearchParams()
  if (localMonth.value) params.append('month', localMonth.value)
  return '/transactions/export?' + params.toString()
})

const exportCsvUrl = computed(() => {
  const params = new URLSearchParams()
  if (localMonth.value) params.append('month', localMonth.value)
  if (localSearch.value) params.append('search', localSearch.value)
  if (localCollector.value) params.append('collector_id', localCollector.value)
  return '/transactions/export?' + params.toString()
})

const selectedTotal = computed(() => {
  return props.payments.data
    .filter(p => selectedPayments.value.includes(p.id))
    .reduce((sum, p) => sum + p.amount, 0)
})

const toggleSelectAll = () => {
  if (selectAll.value) {
    selectedPayments.value = props.payments.data.map(p => p.id)
  } else {
    selectedPayments.value = []
  }
}

const refundForm = useForm({
  refund_amount: '',
  reason: ''
})

const reconcileForm = useForm({
  deposit_id: '',
  payment_ids: []
})

const openViewModal = (tx) => {
  viewingTx.value = tx
  showViewModal.value = true
}

const openEditModal = (tx) => {
  editingTx.value = tx
  editForm.value = useForm({
    payer_name:     tx.payer_name ?? '',
    amount:         tx.amount,
    payment_method: tx.payment_method ?? 'cash',
    status:         tx.status ?? 'paid',
    paid_at:        tx.paid_at ? new Date(tx.paid_at).toISOString().slice(0, 16) : '',
    notes:          tx.notes ?? '',
  })
  showEditModal.value = true
}

const submitEdit = () => {
  editForm.value.patch(`/transactions/${editingTx.value.id}`, {
    onSuccess: () => {
      showEditModal.value = false
      editingTx.value = null
      router.reload()
    }
  })
}

const openDeleteModal = (tx) => {
  deletingTx.value = tx
  deleteForm.value = useForm({})
  showDeleteModal.value = true
}

const submitDelete = () => {
  deleteForm.value.delete(`/transactions/${deletingTx.value.id}`, {
    onSuccess: () => {
      showDeleteModal.value = false
      deletingTx.value = null
      router.reload()
    }
  })
}

const openRefundModal = (payment) => {
  paymentToRefund.value = payment
  refundForm.refund_amount = payment.amount
  refundForm.reason = ''
  showRefundModalOpen.value = true
}

const processRefund = () => {
  if (!paymentToRefund.value) return
  refundForm.post(`/transactions/${paymentToRefund.value.id}/refund`, {
    onSuccess: () => {
      showRefundModalOpen.value = false
      paymentToRefund.value = null
      refundForm.reset()
    }
  })
}

const sendReceipt = (payment) => {
  router.post(`/transactions/${payment.id}/send-receipt`, {}, {
    onSuccess: () => {
      alert('Receipt sent successfully')
    }
  })
}

const processReconcile = () => {
  reconcileForm.payment_ids = selectedPayments.value
  reconcileForm.post('/transactions/reconcile', {
    onSuccess: () => {
      showReconcileModal.value = false
      selectedPayments.value = []
      reconcileForm.reset()
    }
  })
}

const exportBatch = () => {
  const formData = new FormData()
  selectedPayments.value.forEach(id => formData.append('ids[]', id))
  
  fetch('/transactions/export-batch', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    body: formData
  }).then(response => response.blob())
    .then(blob => {
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `payments-batch-${now().format('Ymd_His')}.pdf`
      a.click()
      window.URL.revokeObjectURL(url)
    })
}

const exportPdfUrl = computed(() => {
  const params = new URLSearchParams()
  if (localMonth.value) params.append('month', localMonth.value)
  if (localSearch.value) params.append('search', localSearch.value)
  if (localCollector.value) params.append('collector_id', localCollector.value)
  return '/transactions/export/pdf?' + params.toString()
})

// Month options — last 12 months
const monthOptions = computed(() => {
  const opts = []
  const now = new Date()
  for (let i = 0; i < 12; i++) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
    const value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
    const label = d.toLocaleDateString('en-TZ', { month: 'long', year: 'numeric' })
    opts.push({ value, label })
  }
  return opts
})

function applyFilters() {
  router.get('/transactions', {
    search: localSearch.value || undefined,
    status: localStatus.value || undefined,
    month: localMonth.value || undefined,
    collector_id: localCollector.value || undefined,
  }, { preserveState: true, replace: true })
}

function clearFilters() {
  localSearch.value = ''
  localStatus.value = ''
  localMonth.value = ''
  localCollector.value = ''
  router.get('/transactions', {}, { replace: true })
}

const formatTZS = v => new Intl.NumberFormat('sw-TZ', { minimumFractionDigits: 0 }).format(v ?? 0)
const formatDate = d => d ? new Date(d).toLocaleString('en-TZ', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'
const initials = n => n ? n.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() : '??'
const now = () => new Date()
</script>

<style scoped>
.page-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  margin-bottom: 14px;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  background: #2d7a50;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
}

.btn-primary:hover {
  background: #1a4d32;
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  background: #fff;
  color: #4a6357;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 8px;
  font-size: 12px;
  cursor: pointer;
  text-decoration: none;
}

.btn-secondary:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.filters-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.search-input {
  flex: 1;
  min-width: 200px;
  padding: 7px 12px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 7px;
  font-size: 12px;
  background: #fff;
}

.search-input:focus {
  outline: none;
  border-color: #4caf76;
}

.filter-select {
  padding: 7px 10px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 7px;
  font-size: 12px;
  background: #fff;
}

.search-btn {
  padding: 7px 14px;
  background: #2d7a50;
  color: #fff;
  border: none;
  border-radius: 7px;
  font-size: 12px;
  cursor: pointer;
}

.clear-btn {
  padding: 7px 10px;
  background: #fff;
  color: #c0392b;
  border: 1px solid #f5a5a5;
  border-radius: 7px;
  font-size: 11px;
  cursor: pointer;
}

.filters-right {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 8px;
}

.result-count {
  font-size: 11px;
  color: #7a9489;
}

.export-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 12px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 7px;
  font-size: 11px;
  color: #4a6357;
  background: #fff;
  text-decoration: none;
}

.export-btn:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.summary-strip {
  display: flex;
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 10px;
  overflow: hidden;
  margin-bottom: 14px;
}

.strip-item {
  flex: 1;
  padding: 10px 16px;
  text-align: center;
  border-right: 1px solid rgba(0, 0, 0, 0.06);
}

.strip-item:last-child {
  border-right: none;
}

.strip-label {
  display: block;
  font-size: 10px;
  color: #7a9489;
  text-transform: uppercase;
  letter-spacing: 0.6px;
}

.strip-val {
  display: block;
  font-size: 15px;
  font-weight: 600;
  color: #1a2e24;
  margin-top: 2px;
}

.strip-val.green {
  color: #2d7a50;
}

.strip-val.amber {
  color: #b88a00;
}

.card {
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 10px;
  overflow: hidden;
}

.tx-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}

.tx-table th {
  text-align: left;
  padding: 10px 12px;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: #7a9489;
  background: #f8faf9;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.tx-table td {
  padding: 9px 12px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.tx-table tbody tr:hover {
  background: #f8faf9;
}

.td-num {
  color: #7a9489;
  width: 40px;
}

.td-mono {
  font-family: monospace;
  font-size: 11px;
  color: #4a6357;
}

.td-amount {
  font-weight: 600;
  color: #2d7a50;
}

.td-date {
  font-size: 11px;
  color: #7a9489;
  white-space: nowrap;
}

.td-collector {
  font-size: 11px;
  color: #4a6357;
}

.small {
  font-size: 10px;
}

.client-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.payer-av {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: #d6f0df;
  color: #2d7a50;
  font-size: 8px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.payer-name {
  font-size: 12px;
  font-weight: 500;
  color: #1a2e24;
}

.client-num {
  font-size: 10px;
  color: #7a9489;
}

.status-badge {
  font-size: 9px;
  padding: 2px 7px;
  border-radius: 8px;
  font-weight: 600;
}

.status--paid {
  background: #f0faf3;
  color: #2d7a50;
}

.status--reversed {
  background: #fef0f0;
  color: #c0392b;
}

.view-link {
  font-size: 11px;
  color: #4caf76;
  cursor: pointer;
  background: none;
  border: none;
  padding: 0;
}

.view-link:hover { text-decoration: underline; }

.action-link {
  background: none; border: 1px solid rgba(0,0,0,0.1); border-radius: 4px;
  padding: 3px 8px; font-size: 11px; color: #4a6357; cursor: pointer;
  white-space: nowrap;
}
.action-link:hover { background: #f0f4f1; border-color: #4caf76; color: #2d7a50; }

.td-actions { display: flex; gap: 4px; flex-wrap: wrap; align-items: center; }

.danger-link {
  color: #c0392b !important;
}

.modal-text { font-size: 13px; color: #1a2e24; margin-bottom: 4px; }

.detail-grid { display: flex; flex-direction: column; gap: 10px; }

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6px 0;
  border-bottom: 1px solid rgba(0,0,0,0.05);
  font-size: 13px;
}

.detail-label { color: #7a9489; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; min-width: 90px; }

.detail-val { color: #1a2e24; font-weight: 500; text-align: right; }

.detail-val.mono { font-family: monospace; }

.detail-val.amount { color: #2d7a50; font-weight: 700; }

.modal-btn {
  padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500;
  cursor: pointer; border: none;
}

.modal-btn--cancel  { background: #f5f5f5; color: #4a6357; }
.modal-btn--primary { background: #4caf76; color: white; }
.modal-btn--danger  { background: #c0392b; color: white; }

.form-group { margin-bottom: 14px; }
.form-group label { display: block; margin-bottom: 5px; font-size: 12px; font-weight: 500; color: #1a2e24; }
.form-input {
  width: 100%; padding: 7px 10px; border: 1px solid rgba(0,0,0,0.12);
  border-radius: 6px; font-size: 13px; box-sizing: border-box;
}
.form-input:focus { outline: none; border-color: #4caf76; }

.empty-row {
  text-align: center;
  color: #7a9489;
  padding: 40px;
}

.import-link {
  color: #4caf76;
  text-decoration: underline;
  margin-left: 8px;
  cursor: pointer;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 12px;
  border-top: 1px solid rgba(0, 0, 0, 0.06);
}

.page-btn {
  padding: 5px 12px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 6px;
  font-size: 11px;
  color: #4a6357;
  background: #fff;
  text-decoration: none;
}

.page-btn--disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-info {
  font-size: 11px;
  color: #7a9489;
}

/* Import Modal Styles */
.import-modal-content {
  padding: 0;
}

.import-description {
  margin-bottom: 16px;
  color: #4a6357;
  font-size: 13px;
}

.drop-zone {
  border: 2px dashed #4caf76;
  border-radius: 8px;
  padding: 32px;
  text-align: center;
  background: #f8faf9;
  cursor: pointer;
  transition: all 0.2s;
}

.drop-zone:hover {
  background: #f0faf3;
  border-color: #2d7a50;
}

.hidden-input {
  display: none;
}

.drop-icon {
  color: #4caf76;
  margin-bottom: 12px;
}

.drop-title {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 4px;
}

.drop-hint {
  font-size: 12px;
  color: #7a9489;
}

.import-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.file-selected {
  display: flex;
  align-items: center;
  gap: 12px;
  justify-content: center;
}

.file-icon {
  color: #4caf76;
  flex-shrink: 0;
}

.file-name {
  font-size: 13px;
  font-weight: 500;
  color: #1a2e24;
}

.file-size {
  font-size: 11px;
  color: #7a9489;
}

.file-remove {
  background: none;
  border: none;
  color: #c0392b;
  font-weight: bold;
  font-size: 14px;
  cursor: pointer;
  padding: 4px;
}
</style>
