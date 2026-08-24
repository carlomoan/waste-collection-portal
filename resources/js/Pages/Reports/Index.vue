<template>
  <AppLayout title="Reports">
    <div class="reports-container">
      <div class="header">
        <h1>Reports</h1>
        <p>Generate and view reports</p>
      </div>

      <!-- Report Type Selection -->
      <div class="report-types">
        <div
          v-for="(label, key) in reportTypes"
          :key="key"
          class="report-type-card"
          :class="{ 'report-type-card--active': selectedReportType === key }"
          @click="selectedReportType = key"
        >
          <div class="report-type-icon" :class="`report-type-icon--${iconFor(key)}`">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
              <path stroke-linecap="round" stroke-linejoin="round" :d="icons[iconFor(key)]"/>
            </svg>
          </div>
          <div class="report-type-name">{{ label }}</div>
          <div class="report-type-desc">{{ descriptions[key] }}</div>
        </div>
      </div>

      <!-- Report Filters -->
      <div class="filters-section">
        <div class="filter-group">
          <label class="filter-label">Month</label>
          <select v-model="selectedMonth" class="filter-select">
            <option v-for="m in 12" :key="m" :value="m">{{ monthName(m) }}</option>
          </select>
        </div>
        <div class="filter-group">
          <label class="filter-label">Year</label>
          <select v-model="selectedYear" class="filter-select">
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <div class="filter-group">
          <label class="filter-label">Zone</label>
          <select v-model="selectedZone" class="filter-select">
            <option value="">All Zones</option>
            <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }}</option>
          </select>
        </div>
        <div class="filter-group">
          <label class="filter-label">Collector</label>
          <select v-model="selectedStaff" class="filter-select">
            <option value="">All Collectors</option>
            <option v-for="s in collectors" :key="s.id" :value="s.id">{{ s.user?.name || s.name }}</option>
          </select>
        </div>
        <button class="generate-btn" @click="viewReport">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
          </svg>
          View Report
        </button>
        <button class="generate-btn generate-btn--csv" @click="exportReport('csv')">Export CSV</button>
        <button class="generate-btn generate-btn--pdf" @click="exportReport('pdf')">Export PDF</button>
      </div>

      <!-- KPI Snapshot -->
      <div class="reports-section">
        <div class="section-header">
          <h3>This Month at a Glance</h3>
        </div>
        <table class="reports-table">
          <tbody>
            <tr><td>Collections (sessions)</td><td>{{ formatCurrency(kpi.total_collections_mtd) }}</td></tr>
            <tr><td>Payments received</td><td>{{ formatCurrency(kpi.total_payments_mtd) }}</td></tr>
            <tr><td>Collection efficiency</td><td>{{ kpi.collection_efficiency }}%</td></tr>
            <tr><td>Active collectors</td><td>{{ kpi.active_collectors }}</td></tr>
            <tr><td>Pending invoices</td><td>{{ kpi.pending_invoices }}</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Scheduled Reports -->
      <div class="scheduled-section">
        <div class="section-header">
          <h3>Scheduled Reports</h3>
          <button class="action-btn action-btn--primary" @click="showScheduleModal = true">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Schedule Report
          </button>
        </div>
        <div class="scheduled-grid">
          <div class="scheduled-card" v-for="report in scheduledReports" :key="report.id">
            <div class="scheduled-header">
              <div class="scheduled-name">{{ report.name }}</div>
              <div class="scheduled-badge" :class="report.is_active ? 'scheduled-badge--active' : 'scheduled-badge--inactive'">
                {{ report.is_active ? 'Active' : 'Paused' }}
              </div>
            </div>
            <div class="scheduled-details">
              <div class="scheduled-detail">
                <span class="detail-label">Type:</span>
                <span class="detail-value">{{ report.type_label }}</span>
              </div>
              <div class="scheduled-detail">
                <span class="detail-label">Frequency:</span>
                <span class="detail-value">{{ report.frequency }}</span>
              </div>
              <div class="scheduled-detail">
                <span class="detail-label">Last Sent:</span>
                <span class="detail-value">{{ report.last_sent_at || 'Never' }}</span>
              </div>
              <div class="scheduled-detail">
                <span class="detail-label">Recipients:</span>
                <span class="detail-value">{{ report.recipients }}</span>
              </div>
            </div>
            <div class="scheduled-actions">
              <button class="scheduled-action" @click="sendNow(report)">Send Now</button>
              <button class="scheduled-action" :class="report.is_active ? 'scheduled-action--danger' : ''" @click="toggleSchedule(report)">
                {{ report.is_active ? 'Pause' : 'Activate' }}
              </button>
            </div>
          </div>
          <p v-if="!scheduledReports.length" style="grid-column: 1/-1; text-align: center; color: #7a9489; font-size: 13px; padding: 20px;">
            No scheduled reports yet. Create one to receive reports automatically by email.
          </p>
        </div>
      </div>

      <!-- Monthly Comparison -->
      <div class="comparison-section">
        <div class="section-header">
          <h3>Monthly Comparison</h3>
          <div class="comparison-controls">
            <select v-model="comparisonMonth1" class="filter-select">
              <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
            <span class="vs-label">vs</span>
            <select v-model="comparisonMonth2" class="filter-select">
              <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
            <button class="compare-btn" @click="generateComparison">Compare</button>
          </div>
        </div>
        <div class="comparison-grid">
          <div class="comparison-card">
            <div class="comparison-title">Revenue</div>
            <div class="comparison-value">{{ formatCurrency(comparisonData.revenue_diff) }}</div>
            <div class="comparison-change" :class="comparisonData.revenue_diff >= 0 ? 'positive' : 'negative'">
              {{ comparisonData.revenue_percent >= 0 ? '+' : '' }}{{ comparisonData.revenue_percent }}%
            </div>
          </div>
          <div class="comparison-card">
            <div class="comparison-title">Collections</div>
            <div class="comparison-value">{{ comparisonData.collections_diff }}</div>
            <div class="comparison-change" :class="comparisonData.collections_diff >= 0 ? 'positive' : 'negative'">
              {{ comparisonData.collections_percent >= 0 ? '+' : '' }}{{ comparisonData.collections_percent }}%
            </div>
          </div>
          <div class="comparison-card">
            <div class="comparison-title">Expenses</div>
            <div class="comparison-value">{{ formatCurrency(comparisonData.expenses_diff) }}</div>
            <div class="comparison-change" :class="comparisonData.expenses_diff <= 0 ? 'positive' : 'negative'">
              {{ comparisonData.expenses_percent >= 0 ? '+' : '' }}{{ comparisonData.expenses_percent }}%
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Schedule Report Modal -->
    <Modal :show="showScheduleModal" title="Schedule Report" @close="showScheduleModal = false">
      <form @submit.prevent="submitSchedule">
        <div class="form-group">
          <label>Report Name</label>
          <input type="text" v-model="scheduleForm.name" class="form-input" required />
        </div>
        <div class="form-group">
          <label>Report Type</label>
          <select v-model="scheduleForm.type" class="form-input" required>
            <option v-for="(label, key) in reportTypes" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>Frequency</label>
          <select v-model="scheduleForm.frequency" class="form-input" required>
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
          </select>
        </div>
        <div class="form-group">
          <label>Recipients (comma-separated emails)</label>
          <input type="text" v-model="scheduleForm.recipients" class="form-input" required placeholder="manager@example.com, owner@example.com" />
        </div>
      </form>
      <template #footer>
        <button class="btn-secondary" @click="showScheduleModal = false">Cancel</button>
        <button class="btn-primary" @click="submitSchedule" :disabled="scheduleForm.processing">
          {{ scheduleForm.processing ? 'Scheduling...' : 'Schedule Report' }}
        </button>
      </template>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  reportTypes:      { type: Object, default: () => ({}) },
  staff:            { type: Array,  default: () => [] },
  months:           { type: Array,  default: () => [] },
  zones:            { type: Array,  default: () => [] },
  scheduledReports: { type: Array,  default: () => [] },
  kpi:              { type: Object, default: () => ({}) },
})

const selectedReportType = ref('revenue')
const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())
const selectedZone = ref('')
const selectedStaff = ref('')
const showScheduleModal = ref(false)
const comparisonMonth1 = ref(new Date().getMonth() + 1)
const comparisonMonth2 = ref(new Date().getMonth() || 12)

const years = Array.from({ length: 6 }, (_, i) => new Date().getFullYear() - i)

const collectors = computed(() => props.staff.filter(s => s.role === 'collector'))

const descriptions = {
  revenue: 'Income and collections analysis',
  collection: 'Sessions, planned vs actual',
  staff: 'Collector performance & efficiency',
  financial: 'P&L with expense breakdown',
  debts: 'Outstanding balances & penalties',
  clients: 'Client registry & payment totals',
  banking: 'Deposits & cash position',
}

const icons = {
  revenue: 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
  collection: 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
  staff: 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
  financial: 'M9 14.25l6-6m-5.5.5h.01m4.99 5h.01M19.5 9.75v10.5c0 .621-.504 1.125-1.125 1.125h-13.5A1.125 1.125 0 0 1 3.75 20.25V9.75m16.5-6a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 3.75v6m16.5-6v6m-16.5-6v6',
  debts: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
  clients: 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
  banking: 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z',
}

function iconFor(key) {
  return ['debts', 'banking'].includes(key) ? key : (['collection'].includes(key) ? 'collection' : key)
}

function monthName(m) {
  return new Date(2000, m - 1, 1).toLocaleString('en-US', { month: 'long' })
}

function filterParams() {
  const params = { type: selectedReportType.value, month: selectedMonth.value, year: selectedYear.value }
  if (selectedZone.value) params.zone_id = selectedZone.value
  if (selectedStaff.value) params.staff_id = selectedStaff.value
  return params
}

function viewReport() {
  router.get('/reports/show', filterParams())
}

function exportReport(format) {
  const params = new URLSearchParams(filterParams())
  window.location.href = `/reports/export-${format}?${params.toString()}`
}

const submitSchedule = () => {
  scheduleForm.recipients = scheduleForm.recipients
    .split(',')
    .map(e => e.trim())
    .filter(Boolean)

  scheduleForm.post('/reports/schedule', {
    onSuccess: () => {
      showScheduleModal.value = false
      scheduleForm.reset()
    }
  })
}

const sendNow = (report) => {
  router.post(`/reports/send-now/${report.id}`, {}, {
    onSuccess: () => {
      alert('Report sent successfully')
    }
  })
}

const toggleSchedule = (report) => {
  router.patch(`/reports/${report.id}/toggle`, {}, {
    onSuccess: () => router.reload(),
  })
}

const generateComparison = () => {
  const [m1, y1] = String(comparisonMonth1.value).split('-').map(Number)
  const [m2, y2] = String(comparisonMonth2.value).split('-').map(Number)

  router.get('/reports/compare', { month: m1, year: y1 }, {
    onSuccess: (page) => {
      const d = page.props.comparisonData || {}
      comparisonData.value = {
        revenue_diff: d.revenue_diff ?? 0,
        revenue_percent: d.revenue_percent ?? 0,
        collections_diff: d.collections_diff ?? 0,
        collections_percent: d.collections_percent ?? 0,
        expenses_diff: d.expenses_diff ?? 0,
        expenses_percent: d.expenses_percent ?? 0,
      }
    },
    onError: () => alert('Comparison failed'),
  })
}

const scheduleForm = useForm({
  name: '',
  type: 'revenue',
  frequency: 'monthly',
  recipients: '',
})

const comparisonData = ref({
  revenue_diff: 0,
  revenue_percent: 0,
  collections_diff: 0,
  collections_percent: 0,
  expenses_diff: 0,
  expenses_percent: 0,
})

const formatCurrency = (value) => {
  return new Intl.NumberFormat('sw-TZ', {
    style: 'currency',
    currency: 'TZS',
    minimumFractionDigits: 0,
  }).format(value || 0)
}
</script>

<style scoped>
.reports-container {
  padding: 20px;
}

.header {
  margin-bottom: 24px;
}

.header h1 {
  font-size: 24px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 4px;
}

.header p {
  color: #4a6357;
  font-size: 14px;
}

.report-types {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.report-type-card {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
  cursor: pointer;
  transition: all 0.15s;
  text-align: center;
}

.report-type-card:hover {
  border-color: #4caf76;
  transform: translateY(-2px);
}

.report-type-card--active {
  border-color: #4caf76;
  background: #f0faf3;
}

.report-type-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 12px;
}

.report-type-icon--revenue {
  background: #c8e6c9;
  color: #2d7a50;
}

.report-type-icon--collection {
  background: #ffe0b2;
  color: #e65100;
}

.report-type-icon--staff {
  background: #e1bee7;
  color: #7b1fa2;
}

.report-type-icon--financial {
  background: #bbdefb;
  color: #1565c0;
}

.report-type-name {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 4px;
}

.report-type-desc {
  font-size: 12px;
  color: #4a6357;
}

.filters-section {
  display: flex;
  gap: 16px;
  align-items: flex-end;
  margin-bottom: 24px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.filter-label {
  font-size: 12px;
  font-weight: 500;
  color: #4a6357;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 13px;
  color: #4a6357;
  background: white;
  min-width: 150px;
}

.generate-btn {
  padding: 10px 20px;
  background: #4caf76;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: background 0.15s;
  margin-left: auto;
}

.generate-btn:hover {
  background: #2d7a50;
}

.generate-btn--csv {
  background: #2563eb;
}

.generate-btn--csv:hover {
  background: #1d4ed8;
}

.generate-btn--pdf {
  background: #c0392b;
}

.generate-btn--pdf:hover {
  background: #96281b;
}

.reports-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 24px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.section-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1a2e24;
}

.view-all-btn {
  padding: 8px 16px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 12px;
  color: #4a6357;
  cursor: pointer;
  transition: all 0.15s;
}

.view-all-btn:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.reports-table {
  width: 100%;
  border-collapse: collapse;
}

.reports-table th {
  text-align: left;
  padding: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #4a6357;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.reports-table td {
  padding: 12px;
  font-size: 13px;
  color: #1a2e24;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.reports-table tr:last-child td {
  border-bottom: none;
}

.table-action {
  padding: 4px 12px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 4px;
  font-size: 11px;
  color: #4a6357;
  cursor: pointer;
  transition: all 0.15s;
  margin-right: 4px;
}

.table-action:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.scheduled-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.action-btn {
  padding: 8px 16px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  background: white;
  color: #4a6357;
  font-size: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.15s;
}

.action-btn:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.action-btn--primary {
  background: #4caf76;
  color: white;
  border-color: #4caf76;
}

.action-btn--primary:hover {
  background: #2d7a50;
  border-color: #2d7a50;
}

.scheduled-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.scheduled-card {
  background: #f9fafb;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 8px;
  padding: 16px;
}

.scheduled-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.scheduled-name {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
}

.scheduled-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.scheduled-badge--active {
  background: #e8f5e9;
  color: #2d7a50;
}

.scheduled-badge--inactive {
  background: #f5f5f5;
  color: #757575;
}

.scheduled-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 12px;
}

.scheduled-detail {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
}

.detail-label {
  color: #4a6357;
}

.detail-value {
  color: #1a2e24;
  font-weight: 500;
}

.scheduled-actions {
  display: flex;
  gap: 8px;
}

.scheduled-action {
  flex: 1;
  padding: 6px 12px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 4px;
  font-size: 11px;
  color: #4a6357;
  cursor: pointer;
  transition: all 0.15s;
}

.scheduled-action:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.scheduled-action--danger {
  color: #c62828;
}

.scheduled-action--danger:hover {
  border-color: #c62828;
  color: #b71c1c;
}

.comparison-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 24px;
}

.comparison-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.vs-label {
  font-size: 12px;
  color: #4a6357;
  font-weight: 500;
}

.compare-btn {
  padding: 8px 16px;
  background: #4caf76;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.15s;
}

.compare-btn:hover {
  background: #2d7a50;
}

.comparison-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-top: 16px;
}

.comparison-card {
  background: #f9fafb;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 8px;
  padding: 16px;
  text-align: center;
}

.comparison-title {
  font-size: 12px;
  color: #4a6357;
  margin-bottom: 8px;
}

.comparison-value {
  font-size: 20px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 4px;
}

.comparison-change {
  font-size: 13px;
  font-weight: 500;
}

.comparison-change.positive {
  color: #2d7a50;
}

.comparison-change.negative {
  color: #c62828;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  font-size: 12px;
  font-weight: 500;
  color: #4a6357;
  margin-bottom: 6px;
}

.form-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 13px;
  color: #1a2e24;
}

.form-input:focus {
  outline: none;
  border-color: #4caf76;
}

.btn-secondary {
  padding: 8px 16px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 12px;
  color: #4a6357;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-secondary:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.btn-primary {
  padding: 8px 16px;
  background: #4caf76;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-primary:hover {
  background: #2d7a50;
}

@media (max-width: 1024px) {
  .report-types {
    grid-template-columns: repeat(2, 1fr);
  }
  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }
  .scheduled-grid {
    grid-template-columns: 1fr;
  }
}
</style>
