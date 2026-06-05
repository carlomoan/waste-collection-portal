<template>
  <AppLayout title="Audit Log">
    <div class="audit-container">
      <div class="header">
        <h1>Audit Log</h1>
        <p>View system activity logs</p>
      </div>

      <!-- Summary Cards -->
      <div class="summary-grid">
        <div class="summary-card">
          <div class="summary-label">Total Activities</div>
          <div class="summary-value">{{ totalActivities }}</div>
          <div class="summary-change summary-change--positive">This month</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Active Users</div>
          <div class="summary-value">{{ activeUsers }}</div>
          <div class="summary-change summary-change--neutral">Today</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Failed Logins</div>
          <div class="summary-value">{{ failedLogins }}</div>
          <div class="summary-change summary-change--negative">Security alerts</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Data Changes</div>
          <div class="summary-value">{{ dataChanges }}</div>
          <div class="summary-change summary-change--neutral">This week</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters-bar">
        <div class="filter-group">
          <label>Date Range</label>
          <select v-model="filters.date_range" class="filter-select" @change="applyFilters">
            <option value="">All Time</option>
            <option value="7">Last 7 days</option>
            <option value="30">Last 30 days</option>
            <option value="90">Last 90 days</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Action Type</label>
          <select v-model="filters.action" class="filter-select" @change="applyFilters">
            <option value="">All Actions</option>
            <option value="create">Create</option>
            <option value="update">Update</option>
            <option value="delete">Delete</option>
            <option value="login">Login</option>
            <option value="export">Export</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Module</label>
          <select v-model="filters.module" class="filter-select" @change="applyFilters">
            <option value="">All Modules</option>
            <option value="Client">Clients</option>
            <option value="Payment">Transactions</option>
            <option value="Staff">Staff</option>
            <option value="Invoice">Invoices</option>
            <option value="Expense">Expenses</option>
          </select>
        </div>
        <div class="filter-group">
          <label>User</label>
          <select v-model="filters.user_id" class="filter-select" @change="applyFilters">
            <option value="">All Users</option>
            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
          </select>
        </div>
        <button class="action-btn" @click="showCleanupModal = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
          </svg>
          Cleanup Old Logs
        </button>
        <button class="action-btn" @click="exportLogs">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export Logs
        </button>
      </div>

      <!-- Activity Log Table -->
      <div class="audit-section">
        <div class="section-header">
          <h3>Recent Activities</h3>
          <div class="log-stats">
            <span class="log-stat">Showing 50 of 1,247 entries</span>
          </div>
        </div>
        <table class="audit-table">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>User</th>
              <th>Action</th>
              <th>Module</th>
              <th>Description</th>
              <th>IP Address</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs.data" :key="log.id">
              <td>{{ formatDateTime(log.timestamp) }}</td>
              <td>
                <div class="user-info">
                  <div class="user-avatar">{{ initials(log.user?.name) }}</div>
                  <span>{{ log.user?.name || 'System' }}</span>
                </div>
              </td>
              <td><span class="action-badge" :class="`action-badge--${log.action}`">{{ log.action }}</span></td>
              <td>{{ log.module }}</td>
              <td>{{ log.description }}</td>
              <td>{{ log.ip_address }}</td>
              <td><span class="status-badge status-badge--success">success</span></td>
              <td class="td-actions">
                <button v-if="log.action === 'delete' && log.old_values" class="table-action table-action--success" @click="restoreLog(log)">Restore</button>
                <button class="table-action" @click="viewDetails(log)">View</button>
              </td>
            </tr>
            <tr v-if="logs.data && logs.data.length === 0">
              <td colspan="8" style="text-align: center; color: #4a6357;">No audit logs found</td>
            </tr>
          </tbody>
        </table>
        <div class="pagination">
          <button class="page-btn" :disabled="!logs.prev_page_url" @click="goToPage(logs.current_page - 1)">← Prev</button>
          <span class="page-info">Page {{ logs.current_page }} of {{ logs.last_page }}</span>
          <button class="page-btn" :disabled="!logs.next_page_url" @click="goToPage(logs.current_page + 1)">Next →</button>
        </div>
      </div>

      <!-- Activity Summary -->
      <div class="summary-section">
        <div class="section-header">
          <h3>Activity Summary</h3>
        </div>
        <div class="summary-grid-2">
          <div class="summary-item">
            <div class="summary-item-label">Most Active User</div>
            <div class="summary-item-value">Admin User</div>
            <div class="summary-item-detail">342 activities this month</div>
          </div>
          <div class="summary-item">
            <div class="summary-item-label">Most Accessed Module</div>
            <div class="summary-item-value">Clients</div>
            <div class="summary-item-detail">287 accesses this month</div>
          </div>
          <div class="summary-item">
            <div class="summary-item-label">Peak Activity Time</div>
            <div class="summary-item-value">9:00 AM - 11:00 AM</div>
            <div class="summary-item-detail">Average 45 activities/hour</div>
          </div>
          <div class="summary-item">
            <div class="summary-item-label">Security Events</div>
            <div class="summary-item-value">3</div>
            <div class="summary-item-detail">All resolved</div>
          </div>
        </div>
      </div>
    </div>

    <!-- View Log Details Modal -->
    <Modal :show="showLogModal" title="Audit Log Details" @close="showLogModal = false">
      <div v-if="viewingLog" class="detail-grid">
        <div class="detail-row"><span class="detail-label">Action</span><span class="action-badge" :class="`action-badge--${viewingLog.action}`">{{ viewingLog.action }}</span></div>
        <div class="detail-row"><span class="detail-label">Module</span><span class="detail-val">{{ viewingLog.module }}</span></div>
        <div class="detail-row"><span class="detail-label">Record #</span><span class="detail-val mono">{{ viewingLog.record_id ?? '—' }}</span></div>
        <div class="detail-row"><span class="detail-label">User</span><span class="detail-val">{{ viewingLog.user?.name ?? 'System' }}</span></div>
        <div class="detail-row"><span class="detail-label">IP Address</span><span class="detail-val mono">{{ viewingLog.ip_address ?? '—' }}</span></div>
        <div class="detail-row"><span class="detail-label">Timestamp</span><span class="detail-val">{{ formatTs(viewingLog.timestamp) }}</span></div>
        <div class="detail-row"><span class="detail-label">Description</span><span class="detail-val">{{ viewingLog.description }}</span></div>
        <div v-if="viewingLog.old_values" class="log-values">
          <div class="log-values-title">Previous Values</div>
          <pre class="log-values-pre">{{ JSON.stringify(parseValues(viewingLog.old_values), null, 2) }}</pre>
        </div>
        <div v-if="viewingLog.new_values" class="log-values">
          <div class="log-values-title">New Values</div>
          <pre class="log-values-pre">{{ JSON.stringify(parseValues(viewingLog.new_values), null, 2) }}</pre>
        </div>
      </div>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showLogModal = false">Close</button>
        <button v-if="viewingLog?.action === 'deleted' && viewingLog?.old_values" class="modal-btn modal-btn--primary" @click="restoreLog(viewingLog); showLogModal = false">
          Restore Record
        </button>
      </template>
    </Modal>

    <!-- Cleanup Modal -->
    <Modal :show="showCleanupModal" @close="showCleanupModal = false" title="Cleanup Old Audit Logs">
      <form @submit.prevent="submitCleanup">
        <div class="form-group">
          <label>Delete logs older than (days)</label>
          <input type="number" v-model="cleanupForm.days" class="form-input" min="30" max="365" required>
        </div>
        <p class="cleanup-warning">This will permanently delete audit logs older than {{ cleanupForm.days }} days. This action cannot be undone.</p>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showCleanupModal = false">Cancel</button>
        <button class="modal-btn modal-btn--danger" @click="submitCleanup" :disabled="cleanupForm.processing">
          {{ cleanupForm.processing ? 'Cleaning...' : 'Cleanup' }}
        </button>
      </template>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  logs: {
    type: Object,
    default: () => ({ data: [] })
  },
  users: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const showCleanupModal = ref(false)
const showLogModal = ref(false)
const viewingLog = ref(null)

const filters = reactive({
  date_range: props.filters.date_range || '',
  action: props.filters.action || '',
  module: props.filters.module || '',
  user_id: props.filters.user_id || ''
})

const cleanupForm = useForm({
  days: 90
})

const applyFilters = () => {
  router.get('/audit', filters, {
    preserveState: true
  })
}

const goToPage = (page) => {
  router.get('/audit', { ...filters, page }, {
    preserveState: true
  })
}

const restoreLog = (log) => {
  if (confirm(`Restore this deleted record? This will restore the ${log.module} record.`)) {
    router.post(`/audit/${log.id}/restore`, {}, {
      onSuccess: () => {
        router.reload()
      }
    })
  }
}

const viewDetails = (log) => {
  viewingLog.value = log
  showLogModal.value = true
}

const parseValues = (v) => {
  if (!v) return null
  try { return typeof v === 'string' ? JSON.parse(v) : v } catch { return v }
}

const formatTs = (ts) => ts ? new Date(ts).toLocaleString() : '—'

const submitCleanup = () => {
  cleanupForm.post('/audit/cleanup', {
    onSuccess: () => {
      showCleanupModal.value = false
      cleanupForm.reset()
      router.reload()
    }
  })
}

const exportLogs = () => {
  const params = new URLSearchParams(filters).toString()
  window.location.href = `/audit/export?${params}`
}

const initials = (name) => {
  if (!name) return '??'
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
}

const formatDateTime = (datetime) => {
  if (!datetime) return 'N/A'
  const date = new Date(datetime)
  return date.toLocaleString('en-TZ', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const totalActivities = computed(() => props.logs.total || 0)
const activeUsers = computed(() => new Set(props.logs.data?.map(l => l.user?.name).filter(Boolean)).size)
const failedLogins = computed(() => props.logs.data?.filter(l => l.action === 'login').length || 0)
const dataChanges = computed(() => props.logs.data?.filter(l => ['create', 'update', 'delete'].includes(l.action)).length || 0)
</script>

<style scoped>
.audit-container {
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

.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.summary-card {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.summary-label {
  font-size: 12px;
  color: #4a6357;
  margin-bottom: 8px;
}

.summary-value {
  font-size: 24px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 8px;
}

.summary-change {
  font-size: 11px;
}

.summary-change--positive {
  color: #2d7a50;
}

.summary-change--negative {
  color: #c0392b;
}

.summary-change--neutral {
  color: #4a6357;
}

.filters-bar {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  align-items: flex-end;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.filter-group label {
  font-size: 12px;
  color: #4a6357;
  font-weight: 500;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 12px;
  color: #4a6357;
  background: white;
  min-width: 140px;
}

.action-btn {
  padding: 10px 20px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 8px;
  background: white;
  color: #4a6357;
  font-size: 13px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.15s;
  margin-left: auto;
}

.action-btn:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.audit-section {
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

.log-stats {
  font-size: 12px;
  color: #4a6357;
}

.audit-table {
  width: 100%;
  border-collapse: collapse;
}

.audit-table th {
  text-align: left;
  padding: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #4a6357;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.audit-table td {
  padding: 12px;
  font-size: 13px;
  color: #1a2e24;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.audit-table tr:last-child td {
  border-bottom: none;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.user-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 600;
  color: white;
}

.user-avatar--admin {
  background: #1565c0;
}

.user-avatar--finance {
  background: #2d7a50;
}

.user-avatar--manager {
  background: #e65100;
}

.user-avatar--collector {
  background: #7b1fa2;
}

.user-avatar--unknown {
  background: #757575;
}

.action-badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 10px;
  font-weight: 500;
}

.action-badge--create {
  background: #e8f5e9;
  color: #2d7a50;
}

.action-badge--update {
  background: #e3f2fd;
  color: #1565c0;
}

.action-badge--delete {
  background: #ffebee;
  color: #c62828;
}

.action-badge--login {
  background: #f3e5f5;
  color: #7b1fa2;
}

.action-badge--export {
  background: #fff3e0;
  color: #e65100;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge--success {
  background: #e8f5e9;
  color: #2d7a50;
}

.status-badge--error {
  background: #ffebee;
  color: #c62828;
}

.status-badge--warning {
  background: #fff3e0;
  color: #e65100;
}

.td-actions {
  display: flex;
  gap: 4px;
}

.table-action {
  padding: 4px 10px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 4px;
  font-size: 10px;
  color: #4a6357;
  cursor: pointer;
  transition: all 0.15s;
}

.table-action:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.table-action--success {
  color: #2d7a50;
}

.table-action--success:hover {
  border-color: #2d7a50;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 12px;
  border-top: 1px solid rgba(0,0,0,0.06);
}

.page-btn {
  padding: 5px 12px;
  border: 1px solid rgba(0,0,0,0.12);
  border-radius: 6px;
  font-size: 11px;
  color: #4a6357;
  background: #fff;
  cursor: pointer;
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-info {
  font-size: 11px;
  color: #7a9489;
}

.modal-btn {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 13px;
  cursor: pointer;
  border: none;
}

.modal-btn--cancel {
  background: #f5f5f5;
  color: #4a6357;
}

.modal-btn--primary {
  background: #4caf76;
  color: white;
}

.modal-btn--danger {
  background: #c0392b;
  color: white;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  font-weight: 500;
  color: #1a2e24;
}

.form-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid rgba(0,0,0,0.12);
  border-radius: 6px;
  font-size: 13px;
  color: #1a2e24;
  background: white;
}

.form-input:focus {
  outline: none;
  border-color: #4caf76;
}

.cleanup-warning {
  color: #c0392b;
  font-size: 12px;
  margin-top: 12px;
  font-weight: 500;
}

.summary-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.summary-grid-2 {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.summary-item {
  background: #f9fafb;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 8px;
  padding: 16px;
}

.summary-item-label {
  font-size: 12px;
  color: #4a6357;
  margin-bottom: 8px;
}

.summary-item-value {
  font-size: 16px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 4px;
}

.summary-item-detail {
  font-size: 11px;
  color: #4a6357;
}

@media (max-width: 1024px) {
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .filters-bar {
    flex-direction: column;
    align-items: stretch;
  }
  .action-btn {
    margin-left: 0;
  }
  .summary-grid-2 {
    grid-template-columns: repeat(2, 1fr);
  }
}

.detail-grid { display: flex; flex-direction: column; gap: 8px; }
.detail-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 5px 0; border-bottom: 1px solid rgba(0,0,0,0.05); font-size: 13px; }
.detail-label { color: #7a9489; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; min-width: 90px; flex-shrink: 0; }
.detail-val { color: #1a2e24; font-weight: 500; text-align: right; }
.detail-val.mono { font-family: monospace; }

.log-values { margin-top: 10px; }
.log-values-title { font-size: 11px; text-transform: uppercase; color: #7a9489; letter-spacing: 0.5px; margin-bottom: 4px; }
.log-values-pre {
  background: #f4f6f5; border: 1px solid rgba(0,0,0,0.08); border-radius: 6px;
  padding: 10px; font-size: 11px; font-family: monospace; max-height: 180px;
  overflow-y: auto; white-space: pre-wrap; word-break: break-all;
}

.modal-btn { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; }
.modal-btn--cancel { background: #f5f5f5; color: #4a6357; }
.modal-btn--primary { background: #4caf76; color: white; }
.modal-btn--danger { background: #c0392b; color: white; }
</style>
