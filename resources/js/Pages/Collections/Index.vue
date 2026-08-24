<template>
  <AppLayout title="Collections">
    <div class="collections-container">
      <div class="header">
        <div>
          <h1>Collections</h1>
          <p>Filter collections by zone, collector, month or date range</p>
        </div>
        <button class="btn-export" @click="exportCsv">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export CSV
        </button>
      </div>

      <!-- Filters -->
      <div class="filters-card">
        <div class="filters-grid">
          <div class="form-group">
            <label>Zone</label>
            <select v-model="filters.zone_id" class="form-input" @change="applyFilters">
              <option value="">All zones</option>
              <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Collector</label>
            <select v-model="filters.collector_id" class="form-input" @change="applyFilters">
              <option value="">All collectors</option>
              <option v-for="c in collectors" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Month</label>
            <select v-model="filters.month" class="form-input" @change="applyFilters">
              <option value="">Any month</option>
              <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Year</label>
            <select v-model="filters.year" class="form-input" @change="applyFilters">
              <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>From</label>
            <input type="date" v-model="filters.date_from" class="form-input" @change="applyFilters" />
          </div>
          <div class="form-group">
            <label>To</label>
            <input type="date" v-model="filters.date_to" class="form-input" @change="applyFilters" />
          </div>
          <div class="form-group form-group--actions">
            <button class="btn-clear" @click="clearFilters">Clear Filters</button>
          </div>
        </div>
      </div>

      <!-- Summary -->
      <div class="summary-grid">
        <div class="summary-card">
          <span class="sc-label">Total Collected</span>
          <span class="sc-value green">{{ formatTZS(summary.total_amount) }}</span>
        </div>
        <div class="summary-card">
          <span class="sc-label">Transactions</span>
          <span class="sc-value">{{ (summary.total_count || 0).toLocaleString() }}</span>
        </div>
        <div class="summary-card">
          <span class="sc-label">Unique Clients</span>
          <span class="sc-value">{{ (summary.unique_clients || 0).toLocaleString() }}</span>
        </div>
      </div>

      <!-- Breakdowns -->
      <div class="breakdown-grid">
        <div class="card">
          <h3>By Zone</h3>
          <div v-if="byZone.length" class="bar-list">
            <div v-for="row in byZone" :key="row.label" class="bar-row">
              <span class="bar-label">{{ row.label }}</span>
              <div class="bar-track">
                <div class="bar-fill" :style="{ width: barWidth(row.total, byZone) }"></div>
              </div>
              <span class="bar-value">{{ formatTZS(row.total) }}</span>
              <span class="bar-count">{{ row.count }} txns</span>
            </div>
          </div>
          <p v-else class="empty">No data for the selected filters</p>
        </div>

        <div class="card">
          <h3>By Collector</h3>
          <div v-if="byCollector.length" class="bar-list">
            <div v-for="row in byCollector" :key="row.label" class="bar-row">
              <span class="bar-label">{{ row.label }}</span>
              <div class="bar-track">
                <div class="bar-fill bar-fill--blue" :style="{ width: barWidth(row.total, byCollector) }"></div>
              </div>
              <span class="bar-value">{{ formatTZS(row.total) }}</span>
              <span class="bar-count">{{ row.count }} txns</span>
            </div>
          </div>
          <p v-else class="empty">No data for the selected filters</p>
        </div>
      </div>

      <!-- Transactions table -->
      <div class="card">
        <div class="table-head">
          <h3>Transactions</h3>
          <span class="count-badge">{{ transactions.total }} total</span>
        </div>
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th>Control #</th>
                <th>Payer</th>
                <th>Zone</th>
                <th>Collector</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="tx in transactions.data" :key="tx.id">
                <td class="mono">{{ tx.control_number }}</td>
                <td>{{ tx.payer_name }}</td>
                <td>{{ tx.zone }}</td>
                <td>{{ tx.collector }}</td>
                <td class="amount">{{ formatTZS(tx.amount) }}</td>
                <td><span class="method-badge">{{ tx.method }}</span></td>
                <td>{{ formatDate(tx.paid_at) }}</td>
              </tr>
              <tr v-if="!transactions.data?.length">
                <td colspan="7" class="empty-row">No transactions match the selected filters</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="transactions.last_page > 1" class="pagination">
          <button class="page-btn" :disabled="transactions.current_page === 1" @click="goToPage(transactions.current_page - 1)">← Prev</button>
          <span class="page-info">Page {{ transactions.current_page }} of {{ transactions.last_page }}</span>
          <button class="page-btn" :disabled="transactions.current_page === transactions.last_page" @click="goToPage(transactions.current_page + 1)">Next →</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  transactions: { type: Object, default: () => ({ data: [], current_page: 1, last_page: 1, total: 0 }) },
  summary:      { type: Object, default: () => ({}) },
  byZone:       { type: Array,  default: () => [] },
  byCollector:  { type: Array,  default: () => [] },
  zones:        { type: Array,  default: () => [] },
  collectors:   { type: Array,  default: () => [] },
  filters:      { type: Object, default: () => ({}) },
})

const filters = reactive({
  zone_id:      props.filters.zone_id ?? '',
  collector_id: props.filters.collector_id ?? '',
  month:        props.filters.month ?? '',
  year:         props.filters.year ?? new Date().getFullYear(),
  date_from:    props.filters.date_from ?? '',
  date_to:      props.filters.date_to ?? '',
})

const months = [
  { value: 1, label: 'January' }, { value: 2, label: 'February' }, { value: 3, label: 'March' },
  { value: 4, label: 'April' }, { value: 5, label: 'May' }, { value: 6, label: 'June' },
  { value: 7, label: 'July' }, { value: 8, label: 'August' }, { value: 9, label: 'September' },
  { value: 10, label: 'October' }, { value: 11, label: 'November' }, { value: 12, label: 'December' },
]
const years = Array.from({ length: 6 }, (_, i) => new Date().getFullYear() - i)

function applyFilters() {
  const params = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== '' && v !== null))
  // Date range overrides month when both set
  if (params.date_from && params.date_to) { delete params.month; delete params.year }
  router.get('/collections', params, { preserveState: true, preserveScroll: true })
}

function clearFilters() {
  filters.zone_id = ''
  filters.collector_id = ''
  filters.month = ''
  filters.year = new Date().getFullYear()
  filters.date_from = ''
  filters.date_to = ''
  router.get('/collections', {}, { preserveState: true })
}

function goToPage(page) {
  const params = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== '')) 
  params.page = page
  router.get('/collections', params, { preserveState: true, preserveScroll: true })
}

function exportCsv() {
  const params = new URLSearchParams(Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== '')))
  window.location.href = `/collections/export?${params.toString()}`
}

function barWidth(total, rows) {
  const max = Math.max(...rows.map(r => Number(r.total)), 1)
  return Math.max((Number(total) / max) * 100, 2) + '%'
}

function formatTZS(v) {
  return 'TZS ' + Number(v || 0).toLocaleString('en-TZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>

<style scoped>
.collections-container { padding: 20px; max-width: 1400px; margin: 0 auto; }
.header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
.header h1 { font-size: 24px; font-weight: 700; color: #1a2e24; margin-bottom: 4px; }
.header p { color: #4a6357; font-size: 14px; }
.btn-export { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; background: #4caf76; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: background 0.15s; }
.btn-export:hover { background: #2d7a50; }

.filters-card { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 20px; margin-bottom: 20px; }
.filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; align-items: end; }
.form-group label { display: block; font-size: 11px; font-weight: 600; color: #4a6357; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
.form-input { width: 100%; padding: 8px 10px; border: 1px solid rgba(0,0,0,0.12); border-radius: 6px; font-size: 13px; color: #1a2e24; background: white; }
.form-input:focus { outline: none; border-color: #4caf76; box-shadow: 0 0 0 2px rgba(76,175,118,0.15); }
.btn-clear { width: 100%; padding: 9px; background: #f0faf3; color: #2d7a50; border: 1px solid #a8ddb8; border-radius: 6px; font-size: 12px; cursor: pointer; }
.btn-clear:hover { background: #dcf2e3; }

.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; }
.summary-card { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 18px 20px; }
.sc-label { display: block; font-size: 11px; color: #4a6357; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
.sc-value { font-size: 22px; font-weight: 700; color: #1a2e24; }
.sc-value.green { color: #2d7a50; }

.breakdown-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
.card { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 20px; }
.card h3 { font-size: 14px; font-weight: 600; color: #1a2e24; margin-bottom: 14px; }
.bar-list { display: flex; flex-direction: column; gap: 10px; }
.bar-row { display: grid; grid-template-columns: 110px 1fr 110px 55px; gap: 10px; align-items: center; }
.bar-label { font-size: 12px; color: #1a2e24; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.bar-track { height: 8px; background: #eef5f0; border-radius: 4px; overflow: hidden; }
.bar-fill { height: 100%; background: linear-gradient(90deg, #4caf76, #2d7a50); border-radius: 4px; transition: width 0.4s ease; }
.bar-fill--blue { background: linear-gradient(90deg, #3b82f6, #2563eb); }
.bar-value { font-size: 11px; color: #4a6357; text-align: right; font-variant-numeric: tabular-nums; }
.bar-count { font-size: 10px; color: #7a9489; text-align: right; }
.empty { text-align: center; color: #7a9489; font-size: 13px; padding: 20px 0; }

.table-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.table-head h3 { font-size: 14px; font-weight: 600; color: #1a2e24; }
.count-badge { font-size: 11px; background: #f0faf3; color: #2d7a50; padding: 4px 10px; border-radius: 12px; font-weight: 600; }
.table-scroll { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { text-align: left; padding: 10px 12px; font-size: 11px; font-weight: 600; color: #4a6357; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid rgba(0,0,0,0.08); }
.data-table td { padding: 10px 12px; font-size: 13px; color: #1a2e24; border-bottom: 1px solid rgba(0,0,0,0.04); }
.data-table tbody tr:hover { background: #f8fbf9; }
.mono { font-family: monospace; font-size: 12px; color: #2d7a50; }
.amount { font-weight: 600; font-variant-numeric: tabular-nums; }
.method-badge { background: #eef5f0; color: #2d7a50; padding: 2px 8px; border-radius: 10px; font-size: 11px; text-transform: capitalize; }
.empty-row { text-align: center; color: #7a9489; padding: 30px !important; }

.pagination { display: flex; justify-content: center; align-items: center; gap: 16px; margin-top: 16px; }
.page-btn { padding: 7px 14px; background: white; border: 1px solid rgba(0,0,0,0.12); border-radius: 6px; font-size: 12px; cursor: pointer; color: #1a2e24; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-btn:not(:disabled):hover { border-color: #4caf76; color: #2d7a50; }
.page-info { font-size: 12px; color: #4a6357; }

@media (max-width: 1024px) {
  .breakdown-grid { grid-template-columns: 1fr; }
  .bar-row { grid-template-columns: 90px 1fr 95px 45px; }
}
@media (max-width: 640px) {
  .header { flex-direction: column; gap: 12px; }
}
</style>
