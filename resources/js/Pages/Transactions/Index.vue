<template>
  <AppLayout title="Transactions">

    <!-- Filters -->
    <div class="filters-bar">
      <input v-model="search" class="search-input" placeholder="Search payer name or control number…" />
      <select v-model="filterStatus" class="filter-select">
        <option value="">All Status</option>
        <option value="paid">Paid</option>
        <option value="partial">Partial</option>
        <option value="unmatched">Unmatched</option>
      </select>
      <select v-model="filterMonth" class="filter-select">
        <option value="">All Months</option>
        <option value="2026-05">May 2026</option>
        <option value="2026-04">Apr 2026</option>
        <option value="2026-03">Mar 2026</option>
      </select>
      <div class="filters-right">
        <span class="result-count">{{ filteredPayments.length }} records</span>
        <button class="export-btn" @click="exportExcel">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke-width="1.8" stroke="currentColor" width="14" height="14">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5
                 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export
        </button>
      </div>
    </div>

    <!-- Summary strip -->
    <div class="summary-strip">
      <div class="strip-item">
        <span class="strip-label">Total</span>
        <span class="strip-val">{{ formatTZS(totalAmount) }}</span>
      </div>
      <div class="strip-item">
        <span class="strip-label">Transactions</span>
        <span class="strip-val">{{ filteredPayments.length }}</span>
      </div>
      <div class="strip-item">
        <span class="strip-label">Paid</span>
        <span class="strip-val green">{{ paidCount }}</span>
      </div>
      <div class="strip-item">
        <span class="strip-label">Partial</span>
        <span class="strip-val amber">{{ partialCount }}</span>
      </div>
      <div class="strip-item">
        <span class="strip-label">Unmatched</span>
        <span class="strip-val red">{{ unmatchedCount }}</span>
      </div>
    </div>

    <!-- Table -->
    <div class="card">
      <table class="tx-table">
        <thead>
          <tr>
            <th>No.</th>
            <th>Control Number</th>
            <th>Payer Name</th>
            <th>Amount (TZS)</th>
            <th>Collector</th>
            <th>Status</th>
            <th>Date & Time</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(tx, i) in paginatedPayments" :key="tx.control_number">
            <td class="td-num">{{ (currentPage - 1) * perPage + i + 1 }}</td>
            <td class="td-ctrl">{{ tx.control_number }}</td>
            <td class="td-name">
              <div class="payer-cell">
                <div class="payer-av">{{ initials(tx.payer_name) }}</div>
                <span>{{ tx.payer_name || '—' }}</span>
              </div>
            </td>
            <td class="td-amount">{{ formatTZS(tx.amount) }}</td>
            <td class="td-collector">{{ tx.collector }}</td>
            <td>
              <span class="status-badge" :class="`status-badge--${tx.status}`">
                {{ tx.status.toUpperCase() }}
              </span>
            </td>
            <td class="td-date">{{ formatDate(tx.paid_at) }}</td>
            <td>
              <Link :href="route('transactions.show', tx.id)" class="view-link">View</Link>
            </td>
          </tr>
          <tr v-if="filteredPayments.length === 0">
            <td colspan="8" class="empty-row">No transactions found.</td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="pagination">
        <button class="page-btn" :disabled="currentPage === 1" @click="currentPage--">← Prev</button>
        <span class="page-info">Page {{ currentPage }} of {{ totalPages }}</span>
        <button class="page-btn" :disabled="currentPage === totalPages" @click="currentPage++">Next →</button>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { route } from 'ziggy-js'

const props = defineProps({
  payments: { type: Array, default: () => [] },
})

const search       = ref('')
const filterStatus = ref('')
const filterMonth  = ref('')
const currentPage  = ref(1)
const perPage      = 20

const filteredPayments = computed(() => {
  return props.payments.filter(tx => {
    const q = search.value.toLowerCase()
    const matchSearch = !q ||
      (tx.payer_name?.toLowerCase().includes(q)) ||
      tx.control_number.includes(q)
    const matchStatus = !filterStatus.value || tx.status === filterStatus.value
    const matchMonth  = !filterMonth.value  || tx.paid_at.startsWith(filterMonth.value)
    return matchSearch && matchStatus && matchMonth
  })
})

const totalPages       = computed(() => Math.max(1, Math.ceil(filteredPayments.value.length / perPage)))
const paginatedPayments = computed(() =>
  filteredPayments.value.slice((currentPage.value - 1) * perPage, currentPage.value * perPage)
)

const totalAmount    = computed(() => filteredPayments.value.reduce((s, t) => s + t.amount, 0))
const paidCount      = computed(() => filteredPayments.value.filter(t => t.status === 'paid').length)
const partialCount   = computed(() => filteredPayments.value.filter(t => t.status === 'partial').length)
const unmatchedCount = computed(() => filteredPayments.value.filter(t => t.status === 'unmatched').length)

const formatTZS  = v => new Intl.NumberFormat('sw-TZ', { minimumFractionDigits: 0 }).format(v)
const formatDate = d => new Date(d).toLocaleString('en-TZ', { month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' })
const initials   = n => n ? n.split(' ').map(w => w[0]).join('').slice(0,2).toUpperCase() : '??'

const exportExcel = () => { window.location.href = route('transactions.export') }
</script>

<style scoped>
.filters-bar {
  display: flex; align-items: center; gap: 10px;
  margin-bottom: 12px; flex-wrap: wrap;
}
.search-input {
  flex: 1; min-width: 200px; padding: 7px 12px;
  border: 1px solid rgba(0,0,0,0.12); border-radius: 7px;
  font-size: 12px; color: #1a2e24; background: #fff;
}
.search-input:focus { outline: none; border-color: #4caf76; }
.filter-select {
  padding: 7px 10px; border: 1px solid rgba(0,0,0,0.12);
  border-radius: 7px; font-size: 12px; color: #1a2e24; background: #fff;
}
.filters-right { margin-left: auto; display: flex; align-items: center; gap: 10px; }
.result-count { font-size: 11px; color: #7a9489; }
.export-btn {
  display: flex; align-items: center; gap: 5px;
  padding: 7px 12px; border: 1px solid rgba(0,0,0,0.12);
  border-radius: 7px; font-size: 11px; color: #4a6357;
  background: #fff; cursor: pointer;
}
.export-btn:hover { border-color: #4caf76; color: #2d7a50; }

.summary-strip {
  display: flex; gap: 0; background: #fff;
  border: 1px solid rgba(0,0,0,0.08); border-radius: 10px;
  overflow: hidden; margin-bottom: 14px;
}
.strip-item {
  flex: 1; padding: 10px 16px; text-align: center;
  border-right: 1px solid rgba(0,0,0,0.06);
}
.strip-item:last-child { border-right: none; }
.strip-label { display: block; font-size: 10px; color: #7a9489; text-transform: uppercase; letter-spacing: 0.6px; }
.strip-val { display: block; font-size: 15px; font-weight: 600; color: #1a2e24; margin-top: 2px; }
.strip-val.green { color: #2d7a50; }
.strip-val.amber { color: #b88a00; }
.strip-val.red   { color: #c0392b; }

.card {
  background: #fff; border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px; overflow: hidden;
}
.tx-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.tx-table th {
  text-align: left; padding: 10px 14px; font-size: 10px;
  text-transform: uppercase; letter-spacing: 0.8px; color: #7a9489;
  background: #f8faf9; border-bottom: 1px solid rgba(0,0,0,0.08);
}
.tx-table td {
  padding: 10px 14px; border-bottom: 1px solid rgba(0,0,0,0.05); color: #1a2e24;
}
.tx-table tbody tr:hover { background: #f8faf9; }
.tx-table tbody tr:last-child td { border-bottom: none; }
.td-num  { color: #7a9489; width: 40px; }
.td-ctrl { font-family: monospace; font-size: 11px; color: #4a6357; }
.td-amount { font-weight: 600; color: #2d7a50; }
.td-date   { color: #7a9489; font-size: 11px; white-space: nowrap; }
.payer-cell { display: flex; align-items: center; gap: 8px; }
.payer-av {
  width: 24px; height: 24px; border-radius: 50%;
  background: #d6f0df; color: #2d7a50; font-size: 8px;
  font-weight: 600; display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.status-badge {
  font-size: 9px; padding: 2px 7px; border-radius: 8px; font-weight: 600;
}
.status-badge--paid      { background: #f0faf3; color: #2d7a50; }
.status-badge--partial   { background: #fdf6e3; color: #b88a00; }
.status-badge--unmatched { background: #fef0f0; color: #c0392b; }
.view-link { font-size: 11px; color: #4caf76; text-decoration: none; }
.view-link:hover { text-decoration: underline; }
.empty-row { text-align: center; color: #7a9489; padding: 32px; }
.pagination {
  display: flex; align-items: center; justify-content: center;
  gap: 12px; padding: 12px; border-top: 1px solid rgba(0,0,0,0.06);
}
.page-btn {
  padding: 5px 12px; border: 1px solid rgba(0,0,0,0.12);
  border-radius: 6px; font-size: 11px; color: #4a6357;
  background: #fff; cursor: pointer;
}
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-info { font-size: 11px; color: #7a9489; }
</style>
