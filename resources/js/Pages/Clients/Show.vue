<template>
  <AppLayout title="Client Profile">
    <div class="profile-container">
      <!-- Header card -->
      <div class="profile-header">
        <div class="ph-left">
          <div class="avatar">{{ initials(client.name) }}</div>
          <div>
            <h1>{{ client.name }}</h1>
            <p class="client-num">#{{ client.client_number }}</p>
          </div>
        </div>
        <div class="ph-right">
          <Link href="/clients" class="back-link">← Back to Clients</Link>
        </div>
      </div>

      <!-- Info strip -->
      <div class="info-grid">
        <div class="info-card">
          <span class="ic-label">Phone</span>
          <span class="ic-value">{{ client.phone || '—' }}</span>
        </div>
        <div class="info-card">
          <span class="ic-label">Zone</span>
          <span class="ic-value">{{ client.zone_name || '—' }}</span>
        </div>
        <div class="info-card">
          <span class="ic-label">Monthly Fee</span>
          <span class="ic-value">{{ formatTZS(client.monthly_fee) }}</span>
        </div>
        <div class="info-card">
          <span class="ic-label">Total Paid</span>
          <span class="ic-value green">{{ formatTZS(client.total_paid) }}</span>
        </div>
        <div class="info-card">
          <span class="ic-label">Outstanding</span>
          <span class="ic-value" :class="(client.outstanding_balance || 0) > 0 ? 'red' : 'green'">
            {{ formatTZS(client.outstanding_balance) }}
          </span>
        </div>
        <div class="info-card">
          <span class="ic-label">Status</span>
          <span class="status-badge" :class="`status--${client.status}`">{{ client.status }}</span>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tabs-bar">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          class="tab-btn"
          :class="{ 'tab-btn--active': activeTab === tab.key }"
          @click="activeTab = tab.key"
        >
          {{ tab.label }}
          <span v-if="tab.count !== undefined" class="tab-count">{{ tab.count }}</span>
        </button>
      </div>

      <!-- Monthly History Tab -->
      <div v-show="activeTab === 'monthly'" class="card">
        <div class="card-head"><h3>Monthly Payment History</h3></div>
        <table class="data-table" v-if="monthlyHistory.length">
          <thead>
            <tr>
              <th>Month</th>
              <th>Total Paid</th>
              <th>Transactions</th>
              <th>Expected (Fee)</th>
              <th>Difference</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in monthlyHistory" :key="row.month_key">
              <td>{{ row.label }}</td>
              <td class="amount">{{ formatTZS(row.total_paid) }}</td>
              <td>{{ row.transaction_count }}</td>
              <td>{{ formatTZS(row.monthly_fee) }}</td>
              <td>
                <span :class="row.difference >= 0 ? 'diff-pos' : 'diff-neg'">
                  {{ row.difference >= 0 ? '+' : '' }}{{ formatTZS(row.difference) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-else class="empty">No payment history recorded yet.</p>
      </div>

      <!-- Payments Tab -->
      <div v-show="activeTab === 'payments'" class="card">
        <div class="card-head">
          <h3>All Payments</h3>
          <span class="hint">⚠ = payer name on receipt differs from client record</span>
        </div>
        <table class="data-table" v-if="payments.length">
          <thead>
            <tr>
              <th></th>
              <th>Control #</th>
              <th>Receipt #</th>
              <th>Payer Name on Receipt</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Collector</th>
              <th>Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in payments" :key="p.id">
              <td><span v-if="p.name_mismatch" class="warn-flag" title="Payer name differs from client record">⚠</span></td>
              <td class="mono">{{ p.control_number }}</td>
              <td class="mono">{{ p.receipt_number || '—' }}</td>
              <td>
                <span :class="{ 'mismatch-name': p.name_mismatch }">{{ p.payer_name || '—' }}</span>
              </td>
              <td class="amount">{{ formatTZS(p.amount) }}</td>
              <td><span class="method-badge">{{ p.method }}</span></td>
              <td>{{ p.collector }}</td>
              <td>{{ formatDate(p.paid_at) }}</td>
              <td><span class="status-badge" :class="`status--${p.status}`">{{ p.status }}</span></td>
            </tr>
          </tbody>
        </table>
        <p v-else class="empty">No payments recorded for this client.</p>
      </div>

      <!-- Invoices Tab -->
      <div v-show="activeTab === 'invoices'" class="card">
        <div class="card-head"><h3>Invoice Ledger</h3></div>
        <table class="data-table" v-if="invoices.length">
          <thead>
            <tr>
              <th>Invoice #</th>
              <th>Billing Month</th>
              <th>Amount Due</th>
              <th>Paid</th>
              <th>Balance</th>
              <th>Penalty</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="inv in invoices" :key="inv.id">
              <td class="mono">{{ inv.invoice_number }}</td>
              <td>{{ inv.billing_month }}</td>
              <td>{{ formatTZS(inv.amount_due) }}</td>
              <td class="amount green-text">{{ formatTZS(inv.amount_paid) }}</td>
              <td class="amount" :class="inv.balance > 0 ? 'red-text' : ''">{{ formatTZS(inv.balance) }}</td>
              <td>{{ inv.penalty_amount > 0 ? formatTZS(inv.penalty_amount) : '—' }}</td>
              <td><span class="status-badge" :class="`status--${inv.status}`">{{ inv.status }}</span></td>
            </tr>
          </tbody>
        </table>
        <p v-else class="empty">No invoices generated for this client.</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  client:         { type: Object, required: true },
  monthlyHistory: { type: Array, default: () => [] },
  payments:       { type: Array, default: () => [] },
  invoices:       { type: Array, default: () => [] },
})

const activeTab = ref('monthly')

const tabs = computed(() => [
  { key: 'monthly',  label: 'Monthly History', count: props.monthlyHistory.length },
  { key: 'payments', label: 'Payments',        count: props.payments.length },
  { key: 'invoices', label: 'Invoices',        count: props.invoices.length },
])

const mismatchCount = computed(() => props.payments.filter(p => p.name_mismatch).length)

function initials(n) {
  return (n || '').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
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
.profile-container { padding: 20px; max-width: 1300px; margin: 0 auto; }

.profile-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.ph-left { display: flex; align-items: center; gap: 16px; }
.avatar { width: 60px; height: 60px; border-radius: 14px; background: linear-gradient(135deg, #4caf76, #2d7a50); color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; box-shadow: 0 4px 12px rgba(76,175,118,0.3); }
.profile-header h1 { font-size: 22px; font-weight: 700; color: #1a2e24; margin: 0; }
.client-num { font-size: 13px; color: #7a9489; font-family: monospace; margin: 2px 0 0; }
.back-link { font-size: 13px; color: #2d7a50; text-decoration: none; font-weight: 600; }
.back-link:hover { text-decoration: underline; }

.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 24px; }
.info-card { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 14px 16px; }
.ic-label { display: block; font-size: 10px; color: #7a9489; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
.ic-value { font-size: 15px; font-weight: 600; color: #1a2e24; }
.ic-value.green { color: #2d7a50; }
.ic-value.red { color: #c0392b; }

.tabs-bar { display: flex; gap: 6px; margin-bottom: 16px; border-bottom: 2px solid rgba(0,0,0,0.06); }
.tab-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; background: transparent; border: none; border-bottom: 2px solid transparent; margin-bottom: -2px; font-size: 13px; font-weight: 600; color: #4a6357; cursor: pointer; transition: all 0.15s; }
.tab-btn:hover { color: #2d7a50; }
.tab-btn--active { color: #2d7a50; border-bottom-color: #4caf76; }
.tab-count { background: #f0faf3; color: #2d7a50; font-size: 11px; padding: 2px 8px; border-radius: 10px; }

.card { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 20px; margin-bottom: 16px; }
.card-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
.card-head h3 { font-size: 14px; font-weight: 600; color: #1a2e24; margin: 0; }
.hint { font-size: 11px; color: #b45309; }

.data-table { width: 100%; border-collapse: collapse; }
.data-table th { text-align: left; padding: 10px 12px; font-size: 11px; font-weight: 600; color: #4a6357; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid rgba(0,0,0,0.08); }
.data-table td { padding: 10px 12px; font-size: 13px; color: #1a2e24; border-bottom: 1px solid rgba(0,0,0,0.04); }
.data-table tbody tr:hover { background: #f8fbf9; }

.mono { font-family: monospace; font-size: 12px; }
.amount { font-weight: 600; font-variant-numeric: tabular-nums; }
.green-text { color: #2d7a50; }
.red-text { color: #c0392b; }
.diff-pos { color: #2d7a50; font-weight: 600; }
.diff-neg { color: #c0392b; font-weight: 600; }

.warn-flag { color: #d97706; font-size: 15px; cursor: help; }
.mismatch-name { color: #b45309; font-style: italic; }

.method-badge { background: #eef5f0; color: #2d7a50; padding: 2px 8px; border-radius: 10px; font-size: 11px; text-transform: capitalize; }
.status-badge { padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: capitalize; }
.status--paid, .status--active { background: #dcfce7; color: #166534; }
.status--pending, .status--inactive { background: #fef3c7; color: #92400e; }
.status--unpaid, .status--overdue, .status--suspended, .status--penalized { background: #fee2e2; color: #991b1b; }
.status--partial { background: #dbeafe; color: #1e40af; }

.empty { text-align: center; color: #7a9489; font-size: 13px; padding: 30px 0; }

@media (max-width: 768px) {
  .profile-header { flex-direction: column; align-items: flex-start; gap: 12px; }
  .tabs-bar { overflow-x: auto; }
}
</style>
