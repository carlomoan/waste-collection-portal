<template>
  <AppLayout title="Debts & Penalties">

    <AlertBanner type="danger">
      <strong>{{ overdueCount }} invoices</strong> are past the grace period.
      Run the penalty engine to apply fees automatically.
      <button class="inline-btn" @click="applyPenalties">Apply Penalties Now →</button>
    </AlertBanner>

    <!-- KPIs -->
    <div class="kpi-grid">
      <StatCard label="Total Outstanding" :value="formatTZS(totals.outstanding)" accent="red" />
      <StatCard label="Penalty Fees Due"  :value="formatTZS(totals.penalties)"   accent="amber" />
      <StatCard label="Overdue Clients"   :value="overdueCount"                  accent="red" />
      <StatCard label="Avg Debt / Client" :value="formatTZS(totals.avg_debt)"    accent="blue" />
    </div>

    <!-- Aging Summary -->
    <div class="aging-section">
      <h3>Aging Summary</h3>
      <div class="aging-grid">
        <div class="aging-card aging-card--0-30">
          <div class="aging-label">0-30 Days</div>
          <div class="aging-value">{{ formatTZS(agingSummary['0-30'] || 0) }}</div>
        </div>
        <div class="aging-card aging-card--31-60">
          <div class="aging-label">31-60 Days</div>
          <div class="aging-value">{{ formatTZS(agingSummary['31-60'] || 0) }}</div>
        </div>
        <div class="aging-card aging-card--61-90">
          <div class="aging-label">61-90 Days</div>
          <div class="aging-value">{{ formatTZS(agingSummary['61-90'] || 0) }}</div>
        </div>
        <div class="aging-card aging-card--90-plus">
          <div class="aging-label">90+ Days</div>
          <div class="aging-value">{{ formatTZS(agingSummary['90+'] || 0) }}</div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <input v-model="search" class="search-input" placeholder="Search client name or number…" />
      <select v-model="filterStatus" class="filter-select">
        <option value="">All Status</option>
        <option value="active">Active Debt</option>
        <option value="penalized">Penalized</option>
        <option value="partially_paid">Partially Paid</option>
      </select>
      <select v-model="filterMonth" class="filter-select">
        <option value="">All Months</option>
        <option value="2026-05">May 2026</option>
        <option value="2026-04">Apr 2026</option>
        <option value="2026-03">Mar 2026</option>
      </select>
      <div style="margin-left:auto">
        <button class="export-btn" @click="exportDebts">Export PDF</button>
      </div>
    </div>

    <!-- Debts Table -->
    <div class="card">
      <table class="debts-table">
        <thead>
          <tr>
            <th>Client</th>
            <th>Zone</th>
            <th>Invoice</th>
            <th>Original Due</th>
            <th>Paid</th>
            <th>Outstanding</th>
            <th>Penalty</th>
            <th>Grace End</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="debt in paginatedDebts" :key="debt.id"
              :class="{ 'row--penalized': debt.status === 'penalized' }">
            <td>
              <div class="client-cell">
                <div class="client-av">{{ initials(debt.client_name) }}</div>
                <div>
                  <div class="client-name">{{ debt.client_name }}</div>
                  <div class="client-num">{{ debt.client_number }}</div>
                </div>
              </div>
            </td>
            <td class="td-zone">{{ debt.zone_name }}</td>
            <td class="td-mono">{{ debt.invoice_number }}</td>
            <td class="td-amount">{{ formatTZS(debt.original_amount) }}</td>
            <td class="td-paid">{{ formatTZS(debt.paid_amount) }}</td>
            <td class="td-outstanding">{{ formatTZS(debt.outstanding) }}</td>
            <td>
              <span v-if="debt.penalty_amount > 0" class="penalty-amount">
                {{ formatTZS(debt.penalty_amount) }}
              </span>
              <span v-else class="no-penalty">—</span>
            </td>
            <td class="td-date" :class="{ 'date--overdue': isOverdue(debt.grace_end) }">
              {{ formatDate(debt.grace_end) }}
            </td>
            <td>
              <span class="status-badge" :class="`status--${debt.status}`">
                {{ statusLabel(debt.status) }}
              </span>
            </td>
            <td class="td-actions">
              <a :href="`/clients?search=${encodeURIComponent(debt.client_name ?? '')}`" class="action-link">Client</a>
              <button class="action-link" @click="showPaymentPlanModal(debt)">Plan</button>
              <button class="action-link" @click="sendReminder(debt)">Remind</button>
              <button class="action-link action-link--danger" @click="showWriteOffModal(debt)">Write Off</button>
            </td>
          </tr>
          <tr v-if="filteredDebts.length === 0">
            <td colspan="10" class="empty-row">No debts found for the selected filters.</td>
          </tr>
        </tbody>
      </table>

      <div class="pagination">
        <button class="page-btn" :disabled="currentPage === 1" @click="currentPage--">← Prev</button>
        <span class="page-info">Page {{ currentPage }} of {{ totalPages }}</span>
        <button class="page-btn" :disabled="currentPage === totalPages" @click="currentPage++">Next →</button>
      </div>
    </div>

    <!-- Apply Penalties Modal -->
    <Modal :show="showPenaltyModal" @close="showPenaltyModal = false" title="Apply Penalties">
      <form @submit.prevent="submitPenalties">
        <div class="form-group">
          <label>Penalty Percentage (%)</label>
          <input type="number" v-model="penaltyForm.penalty_percentage" class="form-input" min="0" max="100" required>
        </div>
        <div class="form-group">
          <label>
            <input type="checkbox" v-model="penaltyForm.apply_to_all"> Apply to all overdue invoices
          </label>
        </div>
        <div class="form-group">
          <label>Notes</label>
          <textarea v-model="penaltyForm.notes" class="form-input" rows="3" placeholder="Optional notes..."></textarea>
        </div>
        <p class="penalty-warning">This will apply penalty fees to {{ overdueCount }} overdue invoices.</p>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showPenaltyModal = false">Cancel</button>
        <button class="modal-btn modal-btn--danger" @click="submitPenalties" :disabled="penaltyForm.processing">
          {{ penaltyForm.processing ? 'Applying...' : 'Apply Penalties' }}
        </button>
      </template>
    </Modal>

    <!-- Payment Plan Modal -->
    <Modal :show="showPaymentPlanModalFlag" @close="showPaymentPlanModalFlag = false" title="Create Payment Plan">
      <form @submit.prevent="submitPaymentPlan">
        <div class="form-group">
          <label>Total Debt Amount (TZS)</label>
          <input type="number" v-model="paymentPlanForm.total_debt" class="form-input" min="0" step="0.01" required>
        </div>
        <div class="form-group">
          <label>Number of Installments</label>
          <input type="number" v-model="paymentPlanForm.installments" class="form-input" min="1" max="24" required>
        </div>
        <div class="form-group">
          <label>Start Date</label>
          <input type="date" v-model="paymentPlanForm.start_date" class="form-input" required>
        </div>
        <div class="plan-summary">
          <div class="plan-summary-item">
            <span class="ps-label">Installment Amount:</span>
            <span class="ps-value">{{ formatTZS(paymentPlanForm.total_debt / paymentPlanForm.installments || 0) }}</span>
          </div>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showPaymentPlanModalFlag = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="submitPaymentPlan" :disabled="paymentPlanForm.processing">
          {{ paymentPlanForm.processing ? 'Creating...' : 'Create Plan' }}
        </button>
      </template>
    </Modal>

    <!-- Write Off Modal -->
    <Modal :show="showWriteOffModalFlag" @close="showWriteOffModalFlag = false" title="Write Off Debt">
      <form @submit.prevent="submitWriteOff">
        <div class="form-group">
          <label>Reason for Write-off</label>
          <textarea v-model="writeOffForm.reason" class="form-input" rows="4" required placeholder="Explain why this debt is being written off..."></textarea>
        </div>
        <p class="write-off-warning">This will permanently write off the debt. This action cannot be undone.</p>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showWriteOffModalFlag = false">Cancel</button>
        <button class="modal-btn modal-btn--danger" @click="submitWriteOff" :disabled="writeOffForm.processing">
          {{ writeOffForm.processing ? 'Writing Off...' : 'Write Off' }}
        </button>
      </template>
    </Modal>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout   from '@/Layouts/AppLayout.vue'
import AlertBanner from '@/Components/AlertBanner.vue'
import StatCard    from '@/Components/StatCard.vue'
import Modal       from '@/Components/Modal.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  debts:  { type: Array,  default: () => [] },
  totals: { type: Object, default: () => ({ outstanding: 0, penalties: 0, avg_debt: 0 }) },
  agingSummary: { type: Object, default: () => ({ '0-30': 0, '31-60': 0, '61-90': 0, '90+': 0 }) },
})

const search       = ref('')
const filterStatus = ref('')
const filterMonth  = ref('')
const currentPage  = ref(1)
const perPage      = 20
const showPenaltyModal = ref(false)
const showPaymentPlanModalFlag = ref(false)
const showWriteOffModalFlag = ref(false)
const selectedDebt = ref(null)

const penaltyForm = useForm({
  penalty_percentage: 10,
  apply_to_all: true,
  notes: ''
})

const paymentPlanForm = useForm({
  total_debt: 0,
  installments: 1,
  start_date: ''
})

const writeOffForm = useForm({
  reason: ''
})

const filteredDebts = computed(() => props.debts.filter(d => {
  const q = search.value.toLowerCase()
  return (!q || d.client_name.toLowerCase().includes(q) || d.client_number.includes(q))
    && (!filterStatus.value || d.status === filterStatus.value)
    && (!filterMonth.value  || d.invoice_month === filterMonth.value)
}))

const overdueCount  = computed(() => props.debts.filter(d => ['active','penalized'].includes(d.status)).length)
const totalPages    = computed(() => Math.max(1, Math.ceil(filteredDebts.value.length / perPage)))
const paginatedDebts = computed(() =>
  filteredDebts.value.slice((currentPage.value - 1) * perPage, currentPage.value * perPage)
)

const formatTZS  = v => new Intl.NumberFormat('sw-TZ', { minimumFractionDigits: 0 }).format(v)
const formatDate = d => new Date(d).toLocaleDateString('en-TZ', { month: 'short', day: 'numeric', year: 'numeric' })
const isOverdue  = d => new Date(d) < new Date()
const initials   = n => n.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
const statusLabel = s => ({ active: 'Active', penalized: 'Penalized', partially_paid: 'Partial', settled: 'Settled' }[s] ?? s)

const applyPenalties = () => {
  showPenaltyModal.value = true
}

const submitPenalties = () => {
  penaltyForm.post(route('debts.apply-penalties'), {
    onSuccess: () => {
      showPenaltyModal.value = false
      penaltyForm.reset()
    }
  })
}

const exportDebts    = () => window.location.href = route('debts.export')
const recordPartial  = (debt) => router.visit(route('payments.create', { invoice_id: debt.invoice_id }))

const showPaymentPlanModal = (debt) => {
  selectedDebt.value = debt
  paymentPlanForm.total_debt = debt.outstanding
  paymentPlanForm.start_date = new Date().toISOString().slice(0, 10)
  showPaymentPlanModalFlag.value = true
}

const submitPaymentPlan = () => {
  paymentPlanForm.post(`/clients/${selectedDebt.value.client_id}/payment-plan`, {
    onSuccess: () => {
      showPaymentPlanModalFlag.value = false
      paymentPlanForm.reset()
      selectedDebt.value = null
    }
  })
}

const sendReminder = (debt) => {
  if (confirm(`Send payment reminder to ${debt.client_name}?`)) {
    router.post(`/debts/remind/${debt.invoice_id}`, {}, {
      onSuccess: () => {
        router.reload()
      }
    })
  }
}

const showWriteOffModal = (debt) => {
  selectedDebt.value = debt
  showWriteOffModalFlag.value = true
}

const submitWriteOff = () => {
  writeOffForm.post(`/debts/write-off/${selectedDebt.value.invoice_id}`, {
    onSuccess: () => {
      showWriteOffModalFlag.value = false
      writeOffForm.reset()
      selectedDebt.value = null
    }
  })
}
</script>

<style scoped>
.inline-btn {
  background: none; border: none; color: #7a1a1a; text-decoration: underline;
  cursor: pointer; font-size: 12px; padding: 0; margin-left: 6px;
}
.kpi-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 10px; margin-bottom: 14px; }
.filters-bar { display: flex; gap: 10px; margin-bottom: 12px; flex-wrap: wrap; }
.search-input {
  flex: 1; min-width: 200px; padding: 7px 12px;
  border: 1px solid rgba(0,0,0,0.12); border-radius: 7px; font-size: 12px; background: #fff;
}
.search-input:focus { outline: none; border-color: #4caf76; }
.filter-select { padding: 7px 10px; border: 1px solid rgba(0,0,0,0.12); border-radius: 7px; font-size: 12px; background: #fff; }
.export-btn { padding: 7px 12px; border: 1px solid rgba(0,0,0,0.12); border-radius: 7px; font-size: 11px; color: #4a6357; background: #fff; cursor: pointer; }

/* Form Styles */
.form-group { margin-bottom: 16px; }
.form-group label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 500; color: #1a2e24; }
.form-input {
  width: 100%; padding: 8px 12px; border: 1px solid rgba(0,0,0,0.12);
  border-radius: 6px; font-size: 13px; color: #1a2e24; background: white;
}
.form-input:focus { outline: none; border-color: #4caf76; }
.modal-btn {
  padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500;
  cursor: pointer; border: none;
}
.modal-btn--cancel { background: #f5f5f5; color: #4a6357; }
.modal-btn--primary { background: #4caf76; color: white; }
.modal-btn--danger { background: #c0392b; color: white; }
.penalty-warning { color: #c0392b; font-size: 12px; margin-top: 12px; }
.write-off-warning { color: #c0392b; font-size: 12px; margin-top: 12px; font-weight: 500; }

.aging-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 16px;
  margin-bottom: 14px;
}
.aging-section h3 {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 12px;
}
.aging-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}
.aging-card {
  padding: 12px;
  border-radius: 8px;
  text-align: center;
}
.aging-card--0-30 { background: #f0faf3; }
.aging-card--31-60 { background: #fff7ed; }
.aging-card--61-90 { background: #fef9c3; }
.aging-card--90-plus { background: #fef0f0; }
.aging-label {
  font-size: 11px;
  color: #4a6357;
  margin-bottom: 4px;
}
.aging-value {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
}
.plan-summary {
  margin-top: 12px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
}
.plan-summary-item {
  display: flex;
  justify-content: space-between;
}
.ps-label { font-size: 12px; color: #4a6357; }
.ps-value { font-size: 13px; font-weight: 600; color: #1a2e24; }
.action-link--danger { color: #c0392b; }

.card { background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; overflow: hidden; }
.debts-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.debts-table th {
  text-align: left; padding: 10px 12px; font-size: 10px;
  text-transform: uppercase; letter-spacing: 0.8px; color: #7a9489;
  background: #f8faf9; border-bottom: 1px solid rgba(0,0,0,0.08);
}
.debts-table td { padding: 9px 12px; border-bottom: 1px solid rgba(0,0,0,0.05); }
.debts-table tbody tr:hover { background: #f8faf9; }
.row--penalized { background: #fffaf0; }

.client-cell  { display: flex; align-items: center; gap: 8px; }
.client-av    { width: 26px; height: 26px; border-radius: 50%; background: #fef0f0; color: #c0392b; font-size: 8px; font-weight: 600; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.client-name  { font-size: 12px; font-weight: 500; color: #1a2e24; }
.client-num   { font-size: 10px; color: #7a9489; }
.td-zone      { font-size: 11px; color: #4a6357; }
.td-mono      { font-family: monospace; font-size: 11px; color: #4a6357; }
.td-amount    { font-weight: 500; }
.td-paid      { color: #2d7a50; font-weight: 500; }
.td-outstanding { color: #c0392b; font-weight: 600; }
.penalty-amount { color: #b88a00; font-weight: 600; font-size: 11px; }
.no-penalty   { color: #7a9489; }
.td-date      { font-size: 11px; color: #4a6357; }
.date--overdue { color: #c0392b; font-weight: 600; }
.td-actions   { display: flex; gap: 8px; }
.action-link  { font-size: 11px; color: #4caf76; text-decoration: none; background: none; border: none; cursor: pointer; padding: 0; }
.action-link:hover { text-decoration: underline; }
.status-badge { font-size: 9px; padding: 2px 7px; border-radius: 8px; font-weight: 600; }
.status--active        { background: #fef0f0; color: #c0392b; }
.status--penalized     { background: #fdf6e3; color: #b88a00; }
.status--partially_paid { background: #f0f4ff; color: #3b5cb8; }
.status--settled       { background: #f0faf3; color: #2d7a50; }
.empty-row { text-align: center; color: #7a9489; padding: 32px; }
.pagination { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 12px; border-top: 1px solid rgba(0,0,0,0.06); }
.page-btn { padding: 5px 12px; border: 1px solid rgba(0,0,0,0.12); border-radius: 6px; font-size: 11px; color: #4a6357; background: #fff; cursor: pointer; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-info { font-size: 11px; color: #7a9489; }
</style>
