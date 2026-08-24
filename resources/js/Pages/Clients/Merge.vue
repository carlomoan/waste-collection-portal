<template>
  <AppLayout title="Merge Clients">
    <div class="merge-container">
      <div class="header">
        <h1>Merge & Name Fix</h1>
        <p>Combine duplicate client records created by name miswriting (e.g. Mohamed / Mohhamedi / Mohammed)</p>
      </div>

      <div class="merge-grid">
        <!-- Left: search & select duplicates -->
        <div class="card">
          <h3>1. Find Duplicate Records</h3>
          <input
            v-model="searchTerm"
            class="search-input"
            placeholder="Search by name or client number…"
            @input="debouncedSearch"
          />

          <div v-if="searchResults.length" class="results-list">
            <label
              v-for="c in searchResults"
              :key="c.id"
              class="result-item"
              :class="{ 'result-item--selected': selectedIds.includes(c.id), 'result-item--target': c.id === targetId }"
            >
              <input
                type="checkbox"
                :value="c.id"
                v-model="selectedIds"
                :disabled="c.id === targetId"
              />
              <div class="result-info">
                <span class="result-name">{{ c.name }}</span>
                <span class="result-meta">#{{ c.client_number }} · {{ c.payments_count }} payments</span>
              </div>
              <button
                v-if="!isTargetCandidate(c)"
                class="set-target-btn"
                @click.prevent="targetId = c.id"
              >Keep</button>
            </label>
          </div>
          <p v-else-if="searchTerm.length >= 2" class="empty">No clients found for "{{ searchTerm }}"</p>
          <p v-else class="empty">Type at least 2 characters to search</p>
        </div>

        <!-- Right: target & canonical name -->
        <div class="card">
          <h3>2. Choose Record to Keep</h3>
          <div class="form-group">
            <label>Target Client</label>
            <select v-model="targetId" class="form-input">
              <option :value="null">— Select from left panel —</option>
              <option v-for="c in selectedClients" :key="c.id" :value="c.id">
                {{ c.name }} (#{{ c.client_number }})
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Canonical Name (correct spelling)</label>
            <input
              type="text"
              v-model="canonicalName"
              class="form-input"
              :placeholder="targetClient?.name || 'e.g. Mohamed Seif Hamad'"
            />
          </div>

          <!-- Preview -->
          <div v-if="targetClient && duplicatesCount > 0" class="preview-box">
            <strong>Merge Preview</strong>
            <ul>
              <li>{{ duplicatesCount }} duplicate record(s) will be soft-deleted</li>
              <li>{{ totalPaymentsToMove }} payment(s) will move to {{ targetClient.name }}</li>
              <li>Invoices and debts transfer automatically</li>
              <li v-if="canonicalName && canonicalName !== targetClient.name">
                Name will be updated to "<em>{{ canonicalName }}</em>"
              </li>
            </ul>
          </div>

          <button
            class="btn-merge"
            :disabled="!canMerge || processing"
            @click="runMerge"
          >
            {{ processing ? 'Merging…' : 'Merge Clients' }}
          </button>
        </div>
      </div>

      <!-- Payment reassignment -->
      <div class="card reassign-card">
        <h3>3. Reassign Individual Payments</h3>
        <p class="section-hint">Move specific receipts to a different client without a full merge — useful when only a few receipts were misassigned.</p>

        <div class="reassign-grid">
          <div class="form-group">
            <label>Search Payments (payer name / receipt #)</label>
            <input v-model="paymentSearch" class="form-input" placeholder="e.g. Mohhamedi" />
            <button class="btn-search-payments" @click="searchPayments">Search</button>
          </div>
          <div class="form-group">
            <label>Reassign To Client</label>
            <input
              v-model="reassignSearch"
              class="form-input"
              placeholder="Search target client…"
              @input="debouncedReassignSearch"
            />
            <div v-if="reassignResults.length" class="mini-results">
              <button
                v-for="c in reassignResults"
                :key="c.id"
                class="mini-result"
                @click="selectReassignTarget(c)"
              >{{ c.name }} (#{{ c.client_number }})</button>
            </div>
          </div>
        </div>

        <table v-if="foundPayments.length" class="data-table">
          <thead>
            <tr>
              <th></th>
              <th>Control #</th>
              <th>Payer Name</th>
              <th>Current Client</th>
              <th>Amount</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in foundPayments" :key="p.id">
              <td><input type="checkbox" :value="p.id" v-model="selectedPaymentIds" /></td>
              <td class="mono">{{ p.control_number }}</td>
              <td>{{ p.payer_name || '—' }}</td>
              <td>{{ p.client_name }}</td>
              <td class="amount">{{ formatTZS(p.amount) }}</td>
              <td>{{ formatDate(p.paid_at) }}</td>
            </tr>
          </tbody>
        </table>

        <button
          class="btn-reassign"
          :disabled="selectedPaymentIds.length === 0 || !reassignTarget || processing"
          @click="runReassign"
        >
          Reassign {{ selectedPaymentIds.length || '' }} Payment(s)
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({})

// ── Merge state ──────────────────────────────────────────────────────
const searchTerm = ref('')
const searchResults = ref([])
const selectedIds = ref([])
const targetId = ref(null)
const canonicalName = ref('')
const processing = ref(false)

// ── Reassign state ───────────────────────────────────────────────────
const paymentSearch = ref('')
const foundPayments = ref([])
const selectedPaymentIds = ref([])
const reassignSearch = ref('')
const reassignResults = ref([])
const reassignTarget = ref(null)

let searchTimer = null
let reassignTimer = null

const selectedClients = computed(() =>
  searchResults.value.filter(c => selectedIds.value.includes(c.id))
)

const targetClient = computed(() =>
  selectedClients.value.find(c => c.id === targetId.value) ?? null
)

const duplicatesCount = computed(() =>
  selectedIds.value.filter(id => id !== targetId.value).length
)

const totalPaymentsToMove = computed(() =>
  selectedClients.value
    .filter(c => c.id !== targetId.value)
    .reduce((sum, c) => sum + (c.payments_count || 0), 0)
)

const canMerge = computed(() =>
  targetId.value !== null && duplicatesCount.value > 0
)

function isTargetCandidate(client) {
  return client.id === targetId.value
}

function debouncedSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(performSearch, 300)
}

async function performSearch() {
  if (searchTerm.value.trim().length < 2) {
    searchResults.value = []
    return
  }
  const res = await fetch(`/collections/clients/search?q=${encodeURIComponent(searchTerm.value)}`)
  const data = await res.json()
  // Keep already-selected clients visible even if outside current results
  const existing = searchResults.value.filter(c => selectedIds.value.includes(c.id))
  const merged = [...existing]
  data.results.forEach(r => {
    if (!merged.find(m => m.id === r.id)) merged.push(r)
  })
  searchResults.value = merged
}

function runMerge() {
  if (!canMerge.value) return
  if (!confirm(`This will merge ${duplicatesCount.value} record(s) into "${targetClient.value.name}". This cannot be undone. Continue?`)) return

  processing.value = true
  router.post('/collections/clients/merge', {
    target_id: targetId.value,
    duplicate_ids: selectedIds.value.filter(id => id !== targetId.value),
    canonical_name: canonicalName.value.trim() || null,
  }, {
    onFinish: () => {
      processing.value = false
      // Reset after successful merge
      selectedIds.value = []
      targetId.value = null
      canonicalName.value = ''
      performSearch()
    },
  })
}

async function searchPayments() {
  if (paymentSearch.value.trim().length < 2) return
  const res = await fetch(`/collections/payments/search?q=${encodeURIComponent(paymentSearch.value)}`)
  const data = await res.json()
  foundPayments.value = data.results
  selectedPaymentIds.value = []
}

function debouncedReassignSearch() {
  clearTimeout(reassignTimer)
  reassignTimer = setTimeout(async () => {
    if (reassignSearch.value.trim().length < 2) {
      reassignResults.value = []
      return
    }
    const res = await fetch(`/collections/clients/search?q=${encodeURIComponent(reassignSearch.value)}`)
    const data = await res.json()
    reassignResults.value = data.results.slice(0, 5)
  }, 300)
}

function selectReassignTarget(client) {
  reassignTarget.value = client
  reassignSearch.value = `${client.name} (#${client.client_number})`
  reassignResults.value = []
}

function runReassign() {
  if (!selectedPaymentIds.value.length || !reassignTarget.value) return
  if (!confirm(`Move ${selectedPaymentIds.value.length} payment(s) to "${reassignTarget.value.name}"?`)) return

  processing.value = true
  router.post('/collections/payments/reassign', {
    payment_ids: selectedPaymentIds.value,
    target_id: reassignTarget.value.id,
  }, {
    onFinish: () => {
      processing.value = false
      selectedPaymentIds.value = []
      searchPayments() // refresh list
    },
  })
}

function formatTZS(v) {
  return 'TZS ' + Number(v || 0).toLocaleString('en-TZ', { minimumFractionDigits: 2 })
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>

<style scoped>
.merge-container { padding: 20px; max-width: 1300px; margin: 0 auto; }
.header { margin-bottom: 24px; }
.header h1 { font-size: 24px; font-weight: 700; color: #1a2e24; margin-bottom: 4px; }
.header p { color: #4a6357; font-size: 14px; }

.merge-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
.card { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 20px; }
.card h3 { font-size: 14px; font-weight: 600; color: #1a2e24; margin-bottom: 14px; }

.search-input { width: 100%; padding: 10px 12px; border: 1px solid rgba(0,0,0,0.12); border-radius: 8px; font-size: 13px; margin-bottom: 12px; }
.search-input:focus { outline: none; border-color: #4caf76; box-shadow: 0 0 0 2px rgba(76,175,118,0.15); }

.results-list { display: flex; flex-direction: column; gap: 8px; max-height: 340px; overflow-y: auto; }
.result-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid rgba(0,0,0,0.08); border-radius: 8px; cursor: pointer; transition: all 0.15s; }
.result-item:hover { border-color: #a8ddb8; background: #f8fbf9; }
.result-item--selected { border-color: #4caf76; background: #f0faf3; }
.result-item--target { border-color: #2563eb; background: #eff6ff; }
.result-info { flex: 1; display: flex; flex-direction: column; }
.result-name { font-size: 13px; font-weight: 600; color: #1a2e24; }
.result-meta { font-size: 11px; color: #7a9489; }
.set-target-btn { padding: 5px 12px; background: white; border: 1px solid #93c5fd; color: #2563eb; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600; }
.set-target-btn:hover { background: #eff6ff; }

.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-size: 11px; font-weight: 600; color: #4a6357; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
.form-input { width: 100%; padding: 9px 12px; border: 1px solid rgba(0,0,0,0.12); border-radius: 6px; font-size: 13px; }
.form-input:focus { outline: none; border-color: #4caf76; }

.preview-box { background: #f0faf3; border: 1px solid #a8ddb8; border-radius: 8px; padding: 14px; margin-bottom: 14px; font-size: 12px; color: #1a2e24; }
.preview-box strong { display: block; margin-bottom: 8px; color: #2d7a50; }
.preview-box ul { margin: 0; padding-left: 18px; display: flex; flex-direction: column; gap: 4px; }

.btn-merge { width: 100%; padding: 12px; background: linear-gradient(135deg, #4caf76, #2d7a50); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.15s; }
.btn-merge:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(76,175,118,0.35); }
.btn-merge:disabled { opacity: 0.45; cursor: not-allowed; }

.reassign-card .section-hint { font-size: 12px; color: #7a9489; margin: -8px 0 16px; }
.reassign-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
.btn-search-payments { margin-top: 8px; padding: 8px 16px; background: #f0faf3; color: #2d7a50; border: 1px solid #a8ddb8; border-radius: 6px; font-size: 12px; cursor: pointer; }
.btn-search-payments:hover { background: #dcf2e3; }

.mini-results { display: flex; flex-direction: column; gap: 4px; margin-top: 6px; }
.mini-result { text-align: left; padding: 7px 10px; background: #f8fbf9; border: 1px solid rgba(0,0,0,0.08); border-radius: 6px; font-size: 12px; cursor: pointer; color: #1a2e24; }
.mini-result:hover { border-color: #4caf76; background: #f0faf3; }

.data-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
.data-table th { text-align: left; padding: 9px 10px; font-size: 11px; font-weight: 600; color: #4a6357; text-transform: uppercase; border-bottom: 1px solid rgba(0,0,0,0.08); }
.data-table td { padding: 9px 10px; font-size: 12px; color: #1a2e24; border-bottom: 1px solid rgba(0,0,0,0.04); }
.mono { font-family: monospace; }
.amount { font-weight: 600; }

.btn-reassign { padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-reassign:hover:not(:disabled) { background: #1d4ed8; }
.btn-reassign:disabled { opacity: 0.45; cursor: not-allowed; }

.empty { text-align: center; color: #7a9489; font-size: 12px; padding: 16px 0; }

@media (max-width: 1024px) {
  .merge-grid, .reassign-grid { grid-template-columns: 1fr; }
}
</style>
