<template>
  <AppLayout :title="reportLabel">
    <div class="show-container">
      <!-- Header -->
      <div class="header">
        <div>
          <h1>{{ reportLabel }}</h1>
          <p>{{ monthLabel }} <span v-if="filterLabels.length">· {{ filterLabels.join(' · ') }}</span></p>
        </div>
        <div class="header-actions">
          <Link href="/reports" class="btn-back">← All Reports</Link>
          <button class="btn-export" @click="exportAs('csv')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            CSV
          </button>
          <button class="btn-export btn-pdf" @click="exportAs('pdf')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
            </svg>
            PDF
          </button>
        </div>
      </div>

      <!-- Summary cards -->
      <div v-if="summaryEntries.length" class="summary-grid">
        <div v-for="[key, value] in summaryEntries" :key="key" class="summary-card">
          <span class="sc-label">{{ prettyKey(key) }}</span>
          <span class="sc-value" :class="valueClass(key, value)">
            {{ isMoney(key) ? formatTZS(value) : (typeof value === 'number' ? value.toLocaleString() : value) }}
          </span>
        </div>
      </div>

      <!-- Data sections -->
      <div v-for="(section, si) in sections" :key="si" class="card">
        <div class="card-head"><h3>{{ section.title }}</h3></div>
        <div class="table-scroll">
          <table class="data-table" v-if="section.rows.length">
            <thead>
              <tr>
                <th>#</th>
                <th v-for="h in section.headers" :key="h">{{ h }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, ri) in section.rows.slice(0, maxRows)" :key="ri">
                <td class="row-num">{{ ri + 1 }}</td>
                <td
                  v-for="(cell, ci) in Object.values(row)"
                  :key="ci"
                  :class="{ 'num-cell': isNumericCell(cell), 'mono-cell': looksLikeRef(section.headers[ci]) }"
                >
                  {{ isNumericCell(cell) ? Number(cell).toLocaleString('en-TZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : (cell ?? '—') }}
                </td>
              </tr>
            </tbody>
          </table>
          <p v-else class="empty">No data for the selected period.</p>
        </div>
        <p v-if="section.rows.length > maxRows" class="truncated-note">
          Showing first {{ maxRows }} of {{ section.rows.length }} rows — export to CSV/PDF for the full dataset.
        </p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  reportType:  { type: String, required: true },
  reportLabel: { type: String, required: true },
  month:       { type: [Number, String], required: true },
  year:        { type: [Number, String], required: true },
  monthLabel:  { type: String, default: '' },
  filters:     { type: Object, default: () => ({}) },
  zones:       { type: Array, default: () => [] },
  staff:       { type: Array, default: () => [] },
  data:        { type: Object, default: () => ({}) },
})

const MONEY_KEYS = ['total', 'amount', 'revenue', 'expenses', 'net_profit', 'balance', 'planned', 'actual',
  'collected', 'monthly_fee', 'total_paid', 'outstanding', 'penalty', 'penalties', 'cash_collected',
  'total_deposited', 'pending_deposits', 'confirmed_deposits', 'unbanked_cash', 'margin', 'avg_transaction',
  'total_outstanding', 'efficiency', 'monthly_recurring']

const maxRows = 200

const summaryEntries = computed(() => Object.entries(props.data.summary || {}))

const sections = computed(() => {
  const d = props.data || {}
  const out = []
  if (d.rows?.length) {
    out.push({ title: sectionTitle(props.reportType), headers: headersFor(d.rows[0]), rows: d.rows })
  }
  if (d.by_method?.length) out.push({ title: 'By Payment Method', headers: ['Method', 'Transactions', 'Total'], rows: d.by_method })
  if (d.by_zone?.length) out.push({ title: 'By Zone', headers: ['Zone', 'Transactions', 'Total'], rows: d.by_zone })
  if (d.expense_categories?.length) out.push({ title: 'Expenses by Category', headers: ['Category', 'Total'], rows: d.expense_categories })
  if (d.daily?.length) out.push({ title: 'Daily Breakdown', headers: ['Day', 'Amount', 'Transactions'], rows: d.daily })
  return out
})

const filterLabels = computed(() => {
  const labels = []
  if (props.filters.zone_id) {
    const z = props.zones.find(z => z.id == props.filters.zone_id)
    if (z) labels.push(`Zone: ${z.name}`)
  }
  if (props.filters.staff_id) {
    const s = props.staff.find(s => s.id == props.filters.staff_id)
    if (s) labels.push(`Collector: ${s.name}`)
  }
  return labels
})

function sectionTitle(type) {
  const titles = {
    revenue: 'Transactions',
    collection: 'Collection Sessions',
    staff: 'Collector Performance',
    debts: 'Outstanding Invoices',
    clients: 'Clients',
    banking: 'Bank Deposits',
    financial: 'Financial Summary',
  }
  return titles[type] || 'Details'
}

function headersFor(row) {
  return Object.keys(row).map(k =>
    k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
  )
}

function prettyKey(key) {
  return key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function isMoney(key) {
  return MONEY_KEYS.includes(key)
}

function valueClass(key, value) {
  if (typeof value !== 'number') return ''
  if (['net_profit', 'margin'].includes(key)) return value >= 0 ? 'green' : 'red'
  if (['expenses', 'outstanding', 'penalties', 'penalty', 'unbanked_cash', 'total_outstanding'].includes(key) && value > 0) return 'red'
  if (['revenue', 'total', 'collected', 'total_paid', 'cash_collected', 'total_deposited'].includes(key)) return 'green'
  return ''
}

function isNumericCell(cell) {
  return typeof cell === 'number'
}

function looksLikeRef(header) {
  return /control|receipt|reference|client #|invoice/i.test(header || '')
}

function exportAs(format) {
  const params = new URLSearchParams({
    type: props.reportType,
    month: props.month,
    year: props.year,
    ...(props.filters.zone_id ? { zone_id: props.filters.zone_id } : {}),
    ...(props.filters.staff_id ? { staff_id: props.filters.staff_id } : {}),
  })
  window.location.href = `/reports/export-${format}?${params.toString()}`
}

function formatTZS(v) {
  return 'TZS ' + Number(v || 0).toLocaleString('en-TZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.show-container { padding: 20px; max-width: 1400px; margin: 0 auto; }

.header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; gap: 16px; flex-wrap: wrap; }
.header h1 { font-size: 24px; font-weight: 700; color: #1a2e24; margin-bottom: 4px; }
.header p { color: #4a6357; font-size: 14px; margin: 0; }
.header-actions { display: flex; gap: 10px; align-items: center; }
.btn-back { font-size: 13px; color: #2d7a50; text-decoration: none; font-weight: 600; padding: 9px 14px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; background: white; }
.btn-back:hover { background: #f0faf3; border-color: #a8ddb8; }
.btn-export { display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; background: #4caf76; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.15s; }
.btn-export:hover { background: #2d7a50; transform: translateY(-1px); }
.btn-pdf { background: #c0392b; }
.btn-pdf:hover { background: #96281b; }

.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 20px; }
.summary-card { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 16px 18px; }
.sc-label { display: block; font-size: 10px; color: #7a9489; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px; }
.sc-value { font-size: 19px; font-weight: 700; color: #1a2e24; }
.sc-value.green { color: #2d7a50; }
.sc-value.red { color: #c0392b; }

.card { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 20px; margin-bottom: 16px; }
.card-head { margin-bottom: 12px; }
.card-head h3 { font-size: 15px; font-weight: 600; color: #1a2e24; margin: 0; }

.table-scroll { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { text-align: left; padding: 9px 11px; font-size: 10px; font-weight: 700; color: #4a6357; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid rgba(0,0,0,0.08); white-space: nowrap; }
.data-table td { padding: 8px 11px; font-size: 13px; color: #1a2e24; border-bottom: 1px solid rgba(0,0,0,0.04); }
.data-table tbody tr:hover { background: #f8fbf9; }
.row-num { color: #b0c4b8 !important; font-size: 11px !important; }
.num-cell { text-align: right; font-variant-numeric: tabular-nums; font-weight: 500; }
.mono-cell { font-family: monospace; font-size: 12px; color: #2d7a50; }

.empty { text-align: center; color: #7a9489; font-size: 13px; padding: 30px 0; }
.truncated-note { font-size: 11px; color: #b45309; margin-top: 10px; text-align: center; }

@media (max-width: 768px) {
  .header-actions { flex-wrap: wrap; }
}
</style>
