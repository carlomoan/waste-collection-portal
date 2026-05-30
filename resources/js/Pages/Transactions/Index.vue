<template>
  <AppLayout title="Transactions">

    <!-- Top actions -->
    <div class="page-actions">
      <button class="btn-primary" @click="showImportModal = true">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="1.8" stroke="currentColor" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21
               18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
        </svg>
        Import PDF / Excel
      </button>
      <Link href="/transactions/create" class="btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="2" stroke="currentColor" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Add Single
      </Link>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <input v-model="localSearch" class="search-input"
             placeholder="Search payer name or control number…"
             @keyup.enter="applyFilters" />
      <select v-model="localStatus" class="filter-select">
        <option value="">All Status</option>
        <option value="paid">Paid</option>
        <option value="reversed">Reversed</option>
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
        <a :href="exportUrl" class="export-btn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke-width="1.8" stroke="currentColor" width="14" height="14">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21
                 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export CSV
        </a>
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
            <th>No.</th>
            <th>Control Number</th>
            <th>Payer / Client</th>
            <th>Amount (TZS)</th>
            <th>Collector</th>
            <th>Receipt</th>
            <th>Status</th>
            <th>Date & Time</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(tx, i) in payments.data" :key="tx.id">
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
            <td>
              <Link :href="`/transactions/${tx.id}`" class="view-link">View</Link>
            </td>
          </tr>
          <tr v-if="!payments.data?.length">
            <td colspan="9" class="empty-row">
              No transactions found.
              <Link href="/transactions/import" class="import-link">Import from PDF →</Link>
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
          <input ref="fileInput" type="file" accept=".pdf,.xlsx,.xls,.csv" class="hidden-input" @change="onFileSelected" />
          <div class="drop-content">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="40" height="40" class="drop-icon">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6 m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
            </svg>
            <p class="drop-title">Drop file here or click to browse</p>
            <p class="drop-hint">PDF, Excel (.xlsx/.xls), or CSV · Max 10 MB</p>
          </div>
        </div>
        <div class="import-actions">
          <button class="btn-secondary" @click="showImportModal = false">Cancel</button>
          <button class="btn-primary" @click="processImport">Import</button>
        </div>
      </div>
    </Modal>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const showImportModal = ref(false)
const selectedFile = ref(null)

const onFileSelected = (event) => {
  const file = event.target.files[0]
  if (file) {
    selectedFile.value = file
  }
}

const processImport = () => {
  if (selectedFile.value) {
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    router.post('/transactions/import', formData, {
      onSuccess: () => {
        showImportModal.value = false
        selectedFile.value = null
      }
    })
  }
}

const props = defineProps({
  payments:   { type: Object, default: () => ({ data: [], total: 0 }) },
  summary:    { type: Object, default: () => ({}) },
  filters:    { type: Object, default: () => ({}) },
  collectors: { type: Array,  default: () => [] },
})

// Local filter state (mirrors URL filters)
const localSearch    = ref(props.filters.search    ?? '')
const localStatus    = ref(props.filters.status    ?? '')
const localMonth     = ref(props.filters.month     ?? '')
const localCollector = ref(props.filters.collector_id ?? '')

const hasActiveFilters = computed(() =>
  localSearch.value || localStatus.value || localMonth.value || localCollector.value
)

const exportUrl = computed(() => {
  const params = new URLSearchParams()
  if (localMonth.value) params.append('month', localMonth.value)
  return '/transactions/export?' + params.toString()
})

// Month options — last 12 months
const monthOptions = computed(() => {
  const opts = []
  const now  = new Date()
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
    search:       localSearch.value    || undefined,
    status:       localStatus.value    || undefined,
    month:        localMonth.value     || undefined,
    collector_id: localCollector.value || undefined,
  }, { preserveState: true, replace: true })
}

function clearFilters() {
  localSearch.value = ''
  localStatus.value = ''
  localMonth.value  = ''
  localCollector.value = ''
  router.get('/transactions', {}, { replace: true })
}

const formatTZS  = v => new Intl.NumberFormat('sw-TZ', { minimumFractionDigits: 0 }).format(v ?? 0)
const formatDate = d => d ? new Date(d).toLocaleString('en-TZ', { month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' }) : '—'
const initials   = n => n ? n.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() : '??'
</script>

<style scoped>
.page-actions { display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 14px; }
.btn-primary {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 14px; background: #2d7a50; color: #fff;
  border: none; border-radius: 8px; font-size: 12px; font-weight: 500;
  cursor: pointer; text-decoration: none;
}
.btn-primary:hover { background: #1a4d32; }
.btn-secondary {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 14px; background: #fff; color: #4a6357;
  border: 1px solid rgba(0,0,0,0.12); border-radius: 8px;
  font-size: 12px; cursor: pointer; text-decoration: none;
}
.btn-secondary:hover { border-color: #4caf76; color: #2d7a50; }

.filters-bar { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; }
.search-input {
  flex: 1; min-width: 200px; padding: 7px 12px;
  border: 1px solid rgba(0,0,0,0.12); border-radius: 7px;
  font-size: 12px; background: #fff;
}
.search-input:focus { outline: none; border-color: #4caf76; }
.filter-select { padding: 7px 10px; border: 1px solid rgba(0,0,0,0.12); border-radius: 7px; font-size: 12px; background: #fff; }
.search-btn {
  padding: 7px 14px; background: #2d7a50; color: #fff;
  border: none; border-radius: 7px; font-size: 12px; cursor: pointer;
}
.clear-btn {
  padding: 7px 10px; background: #fff; color: #c0392b;
  border: 1px solid #f5a5a5; border-radius: 7px; font-size: 11px; cursor: pointer;
}
.filters-right { margin-left: auto; display: flex; align-items: center; gap: 8px; }
.result-count { font-size: 11px; color: #7a9489; }
.export-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 7px 12px; border: 1px solid rgba(0,0,0,0.12);
  border-radius: 7px; font-size: 11px; color: #4a6357;
  background: #fff; text-decoration: none;
}
.export-btn:hover { border-color: #4caf76; color: #2d7a50; }

.summary-strip {
  display: flex; background: #fff; border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px; overflow: hidden; margin-bottom: 14px;
}
.strip-item { flex: 1; padding: 10px 16px; text-align: center; border-right: 1px solid rgba(0,0,0,0.06); }
.strip-item:last-child { border-right: none; }
.strip-label { display: block; font-size: 10px; color: #7a9489; text-transform: uppercase; letter-spacing: 0.6px; }
.strip-val { display: block; font-size: 15px; font-weight: 600; color: #1a2e24; margin-top: 2px; }
.strip-val.green { color: #2d7a50; }
.strip-val.amber { color: #b88a00; }

.card { background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; overflow: hidden; }
.tx-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.tx-table th {
  text-align: left; padding: 10px 12px; font-size: 10px;
  text-transform: uppercase; letter-spacing: 0.8px; color: #7a9489;
  background: #f8faf9; border-bottom: 1px solid rgba(0,0,0,0.08);
}
.tx-table td { padding: 9px 12px; border-bottom: 1px solid rgba(0,0,0,0.05); }
.tx-table tbody tr:hover { background: #f8faf9; }
.td-num       { color: #7a9489; width: 40px; }
.td-mono      { font-family: monospace; font-size: 11px; color: #4a6357; }
.td-amount    { font-weight: 600; color: #2d7a50; }
.td-date      { font-size: 11px; color: #7a9489; white-space: nowrap; }
.td-collector { font-size: 11px; color: #4a6357; }
.small        { font-size: 10px; }
.client-cell  { display: flex; align-items: center; gap: 8px; }
.payer-av {
  width: 26px; height: 26px; border-radius: 50%; background: #d6f0df;
  color: #2d7a50; font-size: 8px; font-weight: 600;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.payer-name   { font-size: 12px; font-weight: 500; color: #1a2e24; }
.client-num   { font-size: 10px; color: #7a9489; }
.status-badge { font-size: 9px; padding: 2px 7px; border-radius: 8px; font-weight: 600; }
.status--paid     { background: #f0faf3; color: #2d7a50; }
.status--reversed { background: #fef0f0; color: #c0392b; }
.view-link    { font-size: 11px; color: #4caf76; text-decoration: none; }
.view-link:hover { text-decoration: underline; }
.empty-row { text-align: center; color: #7a9489; padding: 40px; }
.import-link { color: #4caf76; text-decoration: underline; margin-left: 8px; cursor: pointer; }
.pagination { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 12px; border-top: 1px solid rgba(0,0,0,0.06); }
.page-btn { padding: 5px 12px; border: 1px solid rgba(0,0,0,0.12); border-radius: 6px; font-size: 11px; color: #4a6357; background: #fff; text-decoration: none; }
.page-btn--disabled { opacity: 0.4; cursor: not-allowed; }
.page-info { font-size: 11px; color: #7a9489; }

/* Import Modal Styles */
.import-modal-content { padding: 0; }
.import-description { margin-bottom: 16px; color: #4a6357; font-size: 13px; }
.drop-zone {
  border: 2px dashed #4caf76; border-radius: 8px; padding: 32px;
  text-align: center; background: #f8faf9; cursor: pointer;
  transition: all 0.2s;
}
.drop-zone:hover { background: #f0faf3; border-color: #2d7a50; }
.hidden-input { display: none; }
.drop-icon { color: #4caf76; margin-bottom: 12px; }
.drop-title { font-size: 14px; font-weight: 600; color: #1a2e24; margin-bottom: 4px; }
.drop-hint { font-size: 12px; color: #7a9489; }
.import-actions {
  display: flex; gap: 12px; justify-content: flex-end;
  margin-top: 20px; padding-top: 16px; border-top: 1px solid #e5e7eb;
}
</style>
