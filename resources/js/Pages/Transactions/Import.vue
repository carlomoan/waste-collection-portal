<template>
  <AppLayout title="Import Transactions">
    <!-- Step indicator -->
    <div class="steps-bar">
      <div
        v-for="(step, i) in steps"
        :key="step.key"
        class="step"
        :class="{ 'step--active': currentStep === i, 'step--done': currentStep > i }"
      >
        <div class="step-circle">
          <svg v-if="currentStep > i" xmlns="http://www.w3.org/2000/svg" fill="none"
               viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="14" height="14">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
          </svg>
          <span v-else>{{ i + 1 }}</span>
        </div>
        <span class="step-label">{{ step.label }}</span>
        <div v-if="i < steps.length - 1" class="step-line"
             :class="{ 'step-line--done': currentStep > i }" />
      </div>
    </div>

    <!-- ── STEP 0: Upload ─────────────────────────────────────────────── -->
    <div v-if="currentStep === 0" class="card step-card">
      <h2 class="card-title">Upload POS Report</h2>
      <p class="card-sub">
        Accepts Tausi POS PDF reports or Excel/CSV files. The system will parse
        all transactions, detect new clients, and let you review before importing.
      </p>

      <div
        class="drop-zone"
        :class="{ 'drop-zone--over': isDragging, 'drop-zone--selected': selectedFile }"
        @dragover.prevent="isDragging = true"
        @dragleave="isDragging = false"
        @drop.prevent="onDrop"
        @click="$refs.fileInput.click()"
      >
        <input
          ref="fileInput"
          type="file"
          accept=".pdf,.xlsx,.xls,.csv"
          class="hidden-input"
          @change="onFileSelected"
        />

        <div v-if="!selectedFile" class="drop-content">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke-width="1.5" stroke="currentColor" width="40" height="40" class="drop-icon">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1
                 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6
                 m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125
                 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
          </svg>
          <p class="drop-title">Drop file here or click to browse</p>
          <p class="drop-hint">PDF, Excel (.xlsx/.xls), or CSV · Max 10 MB</p>
        </div>

        <div v-else class="file-selected">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke-width="1.8" stroke="currentColor" width="28" height="28" class="file-icon">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1
                 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621
                 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621
                 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
          </svg>
          <div>
            <p class="file-name">{{ selectedFile.name }}</p>
            <p class="file-size">{{ fileSize }}</p>
          </div>
          <button class="file-remove" @click.stop="clearFile">✕</button>
        </div>
      </div>

      <div class="step-footer">
        <button
          class="btn-primary"
          :disabled="!selectedFile || parsing"
          @click="parseFile"
        >
          <span v-if="parsing" class="spinner" />
          {{ parsing ? 'Parsing file…' : 'Parse & Preview →' }}
        </button>
      </div>

      <div v-if="parseError" class="error-banner">{{ parseError }}</div>
    </div>

    <!-- ── STEP 1: Preview ───────────────────────────────────────────── -->
    <div v-if="currentStep === 1" class="step-card">
      <!-- Summary strip -->
      <div class="preview-summary">
        <div class="ps-item">
          <span class="ps-label">Total Rows</span>
          <span class="ps-val">{{ preview.total }}</span>
        </div>
        <div class="ps-item ps-item--green">
          <span class="ps-label">Will Import</span>
          <span class="ps-val">{{ preview.will_import }}</span>
        </div>
        <div class="ps-item ps-item--amber" v-if="preview.duplicates > 0">
          <span class="ps-label">Duplicates (skip)</span>
          <span class="ps-val">{{ preview.duplicates }}</span>
        </div>
        <div class="ps-item ps-item--blue" v-if="preview.new_clients?.length">
          <span class="ps-label">New Clients</span>
          <span class="ps-val">{{ preview.new_clients.length }}</span>
        </div>
        <div class="ps-item ps-item--green">
          <span class="ps-label">Total Amount</span>
          <span class="ps-val">{{ formatTZS(preview.summary?.total_amount) }}</span>
        </div>
      </div>

      <!-- New clients notice -->
      <div v-if="preview.new_clients?.length" class="new-clients-notice">
        <strong>{{ preview.new_clients.length }} new client(s) will be auto-created</strong>
        with default monthly fee of TZS 3,000 (editable after import):
        <div class="new-clients-list">
          <span v-for="name in preview.new_clients" :key="name" class="client-chip">
            {{ name }}
          </span>
        </div>
      </div>

      <!-- Rows table -->
      <div class="card">
        <div class="card-head">
          <span class="card-title">Parsed Transactions ({{ preview.rows?.length }})</span>
          <div class="filter-tabs">
            <button
              v-for="f in rowFilters"
              :key="f.key"
              class="ftab"
              :class="{ 'ftab--active': rowFilter === f.key }"
              @click="rowFilter = f.key"
            >{{ f.label }}</button>
          </div>
        </div>

        <div class="table-scroll">
          <table class="preview-table">
            <thead>
              <tr>
                <th>No.</th>
                <th>Receipt</th>
                <th>Control Number</th>
                <th>Payer / Client</th>
                <th>Amount</th>
                <th>Collector</th>
                <th>Date</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, i) in filteredRows"
                :key="row.receipt_number || i"
                :class="{ 'row--duplicate': row.already_exists, 'row--new-client': row.will_create_client }"
              >
                <td class="td-num">{{ i + 1 }}</td>
                <td class="td-mono small">{{ row.receipt_number || '—' }}</td>
                <td class="td-mono">{{ row.control_number }}</td>
                <td>
                  <div class="client-cell">
                    <span class="client-name">{{ row.payer_name || '—' }}</span>
                    <span v-if="row.will_create_client" class="badge-new">NEW</span>
                  </div>
                </td>
                <td class="td-amount">{{ formatTZS(row.amount) }}</td>
                <td class="td-collector">{{ row.collector_name || '—' }}</td>
                <td class="td-date">{{ formatDate(row.paid_at) }}</td>
                <td>
                  <span v-if="row.already_exists" class="status-skip">DUPLICATE</span>
                  <span v-else class="status-import">IMPORT</span>
                </td>
              </tr>
              <tr v-if="!filteredRows.length">
                <td colspan="8" class="empty-row">No rows match this filter.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="step-footer">
        <button class="btn-secondary" @click="currentStep = 0">← Back</button>
        <button class="btn-primary" @click="currentStep = 2" :disabled="preview.will_import === 0">
          Confirm & Import {{ preview.will_import }} records →
        </button>
      </div>
    </div>

    <!-- ── STEP 2: Confirm ───────────────────────────────────────────── -->
    <div v-if="currentStep === 2" class="card step-card">
      <h2 class="card-title">Confirm Import</h2>

      <div class="confirm-grid">
        <div class="confirm-item">
          <span class="ci-label">Transactions to import</span>
          <span class="ci-val ci-val--green">{{ preview.will_import }}</span>
        </div>
        <div class="confirm-item" v-if="preview.duplicates">
          <span class="ci-label">Duplicates to skip</span>
          <span class="ci-val ci-val--amber">{{ preview.duplicates }}</span>
        </div>
        <div class="confirm-item" v-if="preview.new_clients?.length">
          <span class="ci-label">New clients to create</span>
          <span class="ci-val ci-val--blue">{{ preview.new_clients.length }}</span>
        </div>
        <div class="confirm-item">
          <span class="ci-label">Total amount</span>
          <span class="ci-val">{{ formatTZS(preview.summary?.total_amount) }}</span>
        </div>
      </div>

      <p class="confirm-note">
        This action cannot be undone. Payments will be saved and new clients will be created
        automatically. You can edit client details (zone, type, monthly fee) from the
        Clients module after import.
      </p>

      <div class="step-footer">
        <button class="btn-secondary" @click="currentStep = 1">← Back to Preview</button>
        <button class="btn-danger" :disabled="importing" @click="runImport">
          <span v-if="importing" class="spinner" />
          {{ importing ? 'Importing…' : '✓ Run Import' }}
        </button>
      </div>
    </div>

    <!-- ── STEP 3: Results ───────────────────────────────────────────── -->
    <div v-if="currentStep === 3" class="card step-card">
      <div class="result-icon" :class="results.errors?.length ? 'result-icon--warn' : 'result-icon--ok'">
        <svg v-if="!results.errors?.length" xmlns="http://www.w3.org/2000/svg" fill="none"
             viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="32" height="32">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
        </svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="2.5" stroke="currentColor" width="32" height="32">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0
               2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697
               16.126ZM12 15.75h.007v.008H12v-.008Z"/>
        </svg>
      </div>

      <h2 class="result-title">
        {{ results.errors?.length ? 'Import completed with warnings' : 'Import Successful!' }}
      </h2>

      <div class="results-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="rg-item rg-item--green">
          <span class="rg-val">{{ results.imported }}</span>
          <span class="rg-label">Records Imported</span>
        </div>
        <div class="rg-item">
          <span class="rg-val">{{ formatTZS(results.total_amount) }}</span>
          <span class="rg-label">Grand Total (All)</span>
        </div>
        <div class="rg-item rg-item--green">
          <span class="rg-val">{{ formatTZS(results.total_amount_paid) }}</span>
          <span class="rg-label">Total PAID (Cash)</span>
        </div>
        <div class="rg-item rg-item--amber" v-if="results.total_amount_pending > 0">
          <span class="rg-val">{{ formatTZS(results.total_amount_pending) }}</span>
          <span class="rg-label">Total NOT PAID (Bank Ref)</span>
        </div>
      </div>

      <div v-if="results.errors?.length" class="errors-list">
        <p class="errors-title">Errors ({{ results.errors.length }}):</p>
        <p v-for="err in results.errors.slice(0, 10)" :key="err" class="error-line">{{ err }}</p>
        <p v-if="results.errors.length > 10" class="error-line">... and {{ results.errors.length - 10 }} more</p>
      </div>

      <div v-if="results.warnings?.length" class="warnings-list">
        <p class="warnings-title">Warnings ({{ results.warnings.length }}):</p>
        <p v-for="warn in results.warnings.slice(0, 5)" :key="warn" class="warning-line">{{ warn }}</p>
      </div>

      <div class="step-footer">
        <button class="btn-secondary" @click="resetWizard">Import Another File</button>
        <a
          v-if="results.imported > 0"
          :href="route('transactions.export.imported-pdf')"
          class="btn-primary"
          target="_blank"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke-width="1.8" stroke="currentColor" width="14" height="14">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Download PDF
        </a>
        <Link href="/transactions" class="btn-primary">View Transactions →</Link>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'

const steps = [
  { key: 'upload', label: 'Upload File' },
  { key: 'preview', label: 'Preview Data' },
  { key: 'confirm', label: 'Confirm' },
  { key: 'results', label: 'Results' },
]

const currentStep = ref(0)
const isDragging = ref(false)
const selectedFile = ref(null)
const parsing = ref(false)
const importing = ref(false)
const parseError = ref(null)
const preview = ref({})
const results = ref({})
const rowFilter = ref('all')

const rowFilters = [
  { key: 'all', label: 'All' },
  { key: 'import', label: 'To Import' },
  { key: 'duplicate', label: 'Duplicates' },
  { key: 'new', label: 'New Clients' },
]

const fileSize = computed(() => {
  if (!selectedFile.value) return ''
  const kb = selectedFile.value.size / 1024
  return kb > 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${Math.round(kb)} KB`
})

const filteredRows = computed(() => {
  const rows = preview.value?.rows ?? []
  if (rowFilter.value === 'import') return rows.filter(r => !r.already_exists)
  if (rowFilter.value === 'duplicate') return rows.filter(r => r.already_exists)
  if (rowFilter.value === 'new') return rows.filter(r => r.will_create_client)
  return rows
})

function onDrop(e) {
  isDragging.value = false
  const file = e.dataTransfer.files[0]
  if (file) setFile(file)
}

function onFileSelected(e) {
  const file = e.target.files[0]
  if (file) setFile(file)
}

function setFile(file) {
  const allowed = [
    'application/pdf',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/csv'
  ]
  if (!allowed.includes(file.type) && !file.name.match(/\.(pdf|xlsx|xls|csv)$/i)) {
    parseError.value = 'Unsupported file type. Please upload a PDF, Excel, or CSV file.'
    return
  }
  selectedFile.value = file
  parseError.value = null
}

function clearFile() {
  selectedFile.value = null
}

async function parseFile() {
  if (!selectedFile.value) return
  parsing.value = true
  parseError.value = null

  const formData = new FormData()
  formData.append('file', selectedFile.value)

  try {
    const { data } = await axios.post('/transactions/preview', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (data.success === false) {
      parseError.value = data.message || 'Failed to parse file.'
      return
    }

    preview.value = data
    currentStep.value = 1
  } catch (err) {
    parseError.value = err.response?.data?.message
      ?? err.response?.data?.errors?.file?.[0]
      ?? 'Failed to parse file. Please check the format and try again.'
  } finally {
    parsing.value = false
  }
}

async function runImport() {
  importing.value = true
  try {
    const { data } = await axios.post('/transactions/confirm-import', {}, {
      timeout: 120000 // 2-minute timeout for large files
    })

    if (data.success === false) {
      alert(data.message || 'Import failed.')
      currentStep.value = 1 // Go back to preview
      return
    }

    results.value = data
    currentStep.value = 3
  } catch (err) {
    // Catch 500 Fatal Errors and network timeouts gracefully
    const msg = err.response?.data?.message
             || err.response?.data?.exception
             || err.message
             || 'Server error. Please check your logs and try again.'

    alert(msg)
    currentStep.value = 1 // Return to preview step so user can retry
  } finally {
    importing.value = false
  }
}

function resetWizard() {
  currentStep.value = 0
  selectedFile.value = null
  preview.value = {}
  results.value = {}
  parseError.value = null
  rowFilter.value = 'all'
}

const formatTZS = v => new Intl.NumberFormat('sw-TZ', { minimumFractionDigits: 0 }).format(v ?? 0)
const formatDate = d => d ? new Date(d).toLocaleDateString('en-TZ', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'
</script>

<style scoped>
/* Steps */
.steps-bar {
  display: flex; align-items: center; margin-bottom: 24px;
  background: #fff; border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px; padding: 16px 24px;
}
.step { display: flex; align-items: center; }
.step-circle {
  width: 28px; height: 28px; border-radius: 50%;
  border: 2px solid rgba(0,0,0,0.15); background: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 600; color: #7a9489; flex-shrink: 0;
}
.step--active .step-circle { border-color: #4caf76; color: #2d7a50; background: #f0faf3; }
.step--done .step-circle { border-color: #4caf76; background: #4caf76; color: #fff; }
.step-label { font-size: 12px; color: #7a9489; margin-left: 8px; white-space: nowrap; }
.step--active .step-label { color: #1a2e24; font-weight: 500; }
.step--done .step-label { color: #2d7a50; }
.step-line {
  flex: 1; height: 2px; background: rgba(0,0,0,0.1);
  margin: 0 12px; min-width: 20px;
}
.step-line--done { background: #4caf76; }

/* Cards */
.step-card { background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 24px; }
.card { background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 16px; }
.card-title { font-size: 15px; font-weight: 600; color: #1a2e24; margin-bottom: 6px; }
.card-sub { font-size: 12px; color: #7a9489; margin-bottom: 20px; line-height: 1.6; }
.card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }

/* Drop zone */
.drop-zone {
  border: 2px dashed rgba(0,0,0,0.15); border-radius: 10px;
  padding: 40px 24px; text-align: center; cursor: pointer;
  transition: all 0.2s; margin-bottom: 20px;
}
.drop-zone:hover, .drop-zone--over { border-color: #4caf76; background: #f0faf3; }
.drop-zone--selected { border-color: #4caf76; border-style: solid; background: #f8fdf9; }
.hidden-input { display: none; }
.drop-icon { color: #a8ddb8; margin: 0 auto 12px; }
.drop-title { font-size: 14px; font-weight: 500; color: #1a2e24; margin-bottom: 4px; }
.drop-hint { font-size: 12px; color: #7a9489; }
.file-selected { display: flex; align-items: center; gap: 12px; justify-content: center; }
.file-icon { color: #4caf76; flex-shrink: 0; }
.file-name { font-size: 13px; font-weight: 500; color: #1a2e24; }
.file-size { font-size: 11px; color: #7a9489; }
.file-remove {
  background: none; border: none; cursor: pointer; color: #c0392b;
  font-size: 14px; margin-left: 8px;
}

/* Buttons */
.btn-primary {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 9px 18px; background: #2d7a50; color: #fff;
  border: none; border-radius: 8px; font-size: 13px; font-weight: 500;
  cursor: pointer; text-decoration: none; transition: background 0.15s;
}
.btn-primary:hover:not(:disabled) { background: #1a4d32; }
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-secondary {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 9px 18px; background: #fff; color: #4a6357;
  border: 1px solid rgba(0,0,0,0.12); border-radius: 8px;
  font-size: 13px; cursor: pointer; transition: all 0.15s;
}
.btn-secondary:hover { border-color: #4caf76; color: #2d7a50; }
.btn-danger {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 9px 18px; background: #2d7a50; color: #fff;
  border: none; border-radius: 8px; font-size: 13px; font-weight: 500;
  cursor: pointer; transition: background 0.15s;
}
.btn-danger:hover:not(:disabled) { background: #1a4d32; }
.btn-danger:disabled { opacity: 0.5; cursor: not-allowed; }
.step-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }

.spinner {
  width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff; border-radius: 50%; animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Preview summary strip */
.preview-summary {
  display: flex; gap: 0; background: #fff;
  border: 1px solid rgba(0,0,0,0.08); border-radius: 10px;
  overflow: hidden; margin-bottom: 14px;
}
.ps-item { flex: 1; padding: 12px 16px; text-align: center; border-right: 1px solid rgba(0,0,0,0.06); }
.ps-item:last-child { border-right: none; }
.ps-label { display: block; font-size: 10px; color: #7a9489; text-transform: uppercase; letter-spacing: 0.6px; }
.ps-val { display: block; font-size: 18px; font-weight: 600; color: #1a2e24; margin-top: 2px; }
.ps-item--green .ps-val { color: #2d7a50; }
.ps-item--amber .ps-val { color: #b88a00; }
.ps-item--blue .ps-val { color: #3b5cb8; }

/* New clients notice */
.new-clients-notice {
  background: #f0f4ff; border: 1px solid #c7d4f5;
  border-radius: 8px; padding: 12px 16px; font-size: 12px;
  color: #2a3f80; margin-bottom: 14px;
}
.new-clients-list { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.client-chip {
  background: #fff; border: 1px solid #c7d4f5; border-radius: 12px;
  padding: 2px 10px; font-size: 11px; color: #2a3f80;
}

/* Table */
.table-scroll { overflow-x: auto; }
.preview-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.preview-table th {
  text-align: left; padding: 8px 12px; font-size: 10px;
  text-transform: uppercase; letter-spacing: 0.8px; color: #7a9489;
  background: #f8faf9; border-bottom: 1px solid rgba(0,0,0,0.08);
}
.preview-table td { padding: 8px 12px; border-bottom: 1px solid rgba(0,0,0,0.05); }
.preview-table tbody tr:hover { background: #f8faf9; }
.row--duplicate { background: #fffdf0 !important; opacity: 0.6; }
.row--new-client { background: #f0f4ff !important; }
.td-num { color: #7a9489; width: 40px; }
.td-mono { font-family: monospace; font-size: 11px; color: #4a6357; }
.td-amount { font-weight: 600; color: #2d7a50; }
.td-date { font-size: 11px; color: #7a9489; white-space: nowrap; }
.td-collector { font-size: 11px; color: #4a6357; }
.small { font-size: 10px; }
.client-cell { display: flex; align-items: center; gap: 6px; }
.client-name { font-size: 12px; color: #1a2e24; }
.badge-new { font-size: 8px; padding: 1px 5px; border-radius: 6px; background: #e8eeff; color: #2a3f80; font-weight: 600; }
.status-import { font-size: 9px; padding: 2px 7px; border-radius: 8px; background: #f0faf3; color: #2d7a50; font-weight: 600; }
.status-skip { font-size: 9px; padding: 2px 7px; border-radius: 8px; background: #fdf6e3; color: #b88a00; font-weight: 600; }

/* Row filters */
.filter-tabs { display: flex; gap: 2px; }
.ftab { padding: 4px 10px; border: 1px solid rgba(0,0,0,0.1); border-radius: 6px; font-size: 11px; color: #7a9489; background: #fff; cursor: pointer; }
.ftab--active { background: #f0faf3; border-color: #4caf76; color: #2d7a50; }

/* Confirm */
.confirm-grid {
  display: grid; grid-template-columns: repeat(4, 1fr);
  gap: 12px; margin: 20px 0;
}
.confirm-item { background: #f8faf9; border-radius: 8px; padding: 14px 16px; text-align: center; }
.ci-label { display: block; font-size: 11px; color: #7a9489; margin-bottom: 6px; }
.ci-val { display: block; font-size: 22px; font-weight: 600; color: #1a2e24; }
.ci-val--green { color: #2d7a50; }
.ci-val--amber { color: #b88a00; }
.ci-val--blue { color: #3b5cb8; }
.confirm-note {
  font-size: 12px; color: #7a9489; background: #fdf6e3;
  border: 1px solid #f5c842; border-radius: 8px; padding: 12px 14px;
}

/* Results */
.result-icon {
  width: 60px; height: 60px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 16px;
}
.result-icon--ok { background: #f0faf3; color: #2d7a50; }
.result-icon--warn { background: #fdf6e3; color: #b88a00; }
.result-title { font-size: 18px; font-weight: 600; color: #1a2e24; text-align: center; margin-bottom: 24px; }
.results-grid {
  display: grid; grid-template-columns: repeat(4, 1fr);
  gap: 12px; margin-bottom: 20px;
}
.rg-item { background: #f8faf9; border-radius: 8px; padding: 14px; text-align: center; }
.rg-val { display: block; font-size: 24px; font-weight: 600; color: #1a2e24; }
.rg-label { display: block; font-size: 11px; color: #7a9489; margin-top: 4px; }
.rg-item--green .rg-val { color: #2d7a50; }
.rg-item--amber .rg-val { color: #b88a00; }
.rg-item--blue .rg-val { color: #3b5cb8; }
.errors-list { background: #fef0f0; border: 1px solid #f5a5a5; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; }
.errors-title { font-size: 12px; font-weight: 600; color: #c0392b; margin-bottom: 6px; }
.error-line { font-size: 11px; color: #c0392b; margin-bottom: 2px; font-family: monospace; }
.warnings-list { background: #fdf6e3; border: 1px solid #f5c842; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; }
.warnings-title { font-size: 12px; font-weight: 600; color: #b88a00; margin-bottom: 6px; }
.warning-line { font-size: 11px; color: #b88a00; margin-bottom: 2px; }
.error-banner {
  background: #fef0f0; border: 1px solid #f5a5a5;
  border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #c0392b; margin-top: 12px;
}
.empty-row { text-align: center; padding: 24px; color: #7a9489; }
</style>
