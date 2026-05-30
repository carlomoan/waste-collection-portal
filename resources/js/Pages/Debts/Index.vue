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
              <Link :href="route('clients.show', debt.client_id)" class="action-link">Client</Link>
              <button class="action-link" @click="recordPartial(debt)">Pay</button>
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

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout   from '@/Layouts/AppLayout.vue'
import AlertBanner from '@/Components/AlertBanner.vue'
import StatCard    from '@/Components/StatCard.vue'
import Modal       from '@/Components/Modal.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  debts:  { type: Array,  default: () => [] },
  totals: { type: Object, default: () => ({ outstanding: 0, penalties: 0, avg_debt: 0 }) },
})

const search       = ref('')
const filterStatus = ref('')
const filterMonth  = ref('')
const currentPage  = ref(1)
const perPage      = 20
const showPenaltyModal = ref(false)

const penaltyForm = useForm({
  penalty_percentage: 10,
  apply_to_all: true,
  notes: ''
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
