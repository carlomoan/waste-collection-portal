<template>
  <AppLayout title="Attendance">
    <div class="attendance-container">
      <div class="header">
        <h1>Attendance</h1>
        <p>Track staff attendance</p>
      </div>

      <!-- Date Selector -->
      <div class="date-selector">
        <button class="date-btn" @click="previousDay">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
          </svg>
        </button>
        <div class="current-date">{{ currentDateFormatted }}</div>
        <button class="date-btn" @click="nextDay">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
          </svg>
        </button>
      </div>

      <!-- Summary Cards -->
      <div class="summary-grid">
        <div class="summary-card">
          <div class="summary-label">Present</div>
          <div class="summary-value">{{ presentCount }}</div>
          <div class="summary-change summary-change--positive">{{ ((presentCount / staff.length) * 100).toFixed(0) }}% attendance rate</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Absent</div>
          <div class="summary-value">{{ absentCount }}</div>
          <div class="summary-change summary-change--negative">Not present today</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Late</div>
          <div class="summary-value">{{ lateCount }}</div>
          <div class="summary-change summary-change--neutral">Arrived after 8:00 AM</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">On Leave</div>
          <div class="summary-value">{{ onLeaveCount }}</div>
          <div class="summary-change summary-change--neutral">Approved leave</div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="actions-bar">
        <button class="action-btn action-btn--primary" @click="showMarkAttendanceModal = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          Mark Attendance
        </button>
        <button class="action-btn" @click="showBulkClockModal = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
          </svg>
          Bulk Clock
        </button>
        <button class="action-btn" @click="showRequestLeaveModal = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
          </svg>
          Request Leave
        </button>
        <button class="action-btn" @click="showMonthlyReportModal = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125z"/>
          </svg>
          Monthly Report
        </button>
        <button class="action-btn" @click="exportReport">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export Report
        </button>
      </div>

      <!-- Attendance List -->
      <div class="attendance-section">
        <div class="section-header">
          <h3>Attendance Record - {{ currentDateFormatted }}</h3>
          <div class="filter-actions">
            <select class="filter-select">
              <option>All Staff</option>
              <option>Operations</option>
              <option>Admin</option>
              <option>Finance</option>
            </select>
            <select class="filter-select">
              <option>All Status</option>
              <option>Present</option>
              <option>Absent</option>
              <option>Late</option>
              <option>On Leave</option>
            </select>
          </div>
        </div>
        <table class="attendance-table">
          <thead>
            <tr>
              <th>Staff</th>
              <th>Role</th>
              <th>Check In</th>
              <th>Check Out</th>
              <th>Hours</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="attendance in todayAttendance" :key="attendance.id">
              <td>
                <div class="staff-info">
                  <div class="staff-avatar">{{ initials(attendance.staff_name) }}</div>
                  <div class="staff-details">
                    <div class="staff-name">{{ attendance.staff_name }}</div>
                    <div class="staff-id">STF-{{ attendance.staff_id }}</div>
                  </div>
                </div>
              </td>
              <td>Collector</td>
              <td>{{ attendance.clock_in }}</td>
              <td>{{ attendance.clock_out }}</td>
              <td>-</td>
              <td><span class="status-badge" :class="`status-badge--${attendance.status}`">{{ attendance.status }}</span></td>
              <td><button class="table-action">View</button></td>
            </tr>
            <tr v-if="todayAttendance.length === 0">
              <td colspan="7" style="text-align: center; color: #4a6357;">No attendance records found</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Leave Requests -->
      <div class="leave-section">
        <div class="section-header">
          <h3>Pending Leave Requests</h3>
          <button class="view-all-btn">View All</button>
        </div>
        <div class="leave-grid">
          <div class="leave-card" v-for="request in pendingLeaveRequests" :key="request.id">
            <div class="leave-header">
              <div class="leave-staff">{{ request.staff_name }}</div>
              <div class="leave-days">{{ request.days }} day{{ request.days > 1 ? 's' : '' }}</div>
            </div>
            <div class="leave-details">
              <div class="leave-detail">
                <span class="detail-label">Type:</span>
                <span class="detail-value">{{ request.leave_type }}</span>
              </div>
              <div class="leave-detail">
                <span class="detail-label">From:</span>
                <span class="detail-value">{{ formatDate(request.start_date) }}</span>
              </div>
              <div class="leave-detail">
                <span class="detail-label">To:</span>
                <span class="detail-value">{{ formatDate(request.end_date) }}</span>
              </div>
            </div>
            <div class="leave-actions">
              <button class="leave-action leave-action--approve" @click="approveLeave(request.id)">Approve</button>
              <button class="leave-action leave-action--reject" @click="rejectLeave(request.id)">Reject</button>
            </div>
          </div>
          <div v-if="pendingLeaveRequests.length === 0" class="empty-state">
            <p>No pending leave requests</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Mark Attendance Modal -->
    <Modal :show="showMarkAttendanceModal" @close="showMarkAttendanceModal = false" title="Mark Attendance">
      <form @submit.prevent="markAttendance">
        <div class="form-group">
          <label>Staff Member</label>
          <select v-model="attendanceForm.staff_id" class="form-input" required>
            <option value="">Select staff</option>
            <option v-for="s in staff" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>Date</label>
          <input type="date" v-model="attendanceForm.work_date" class="form-input" required />
        </div>
        <div class="form-group">
          <label>Status</label>
          <select v-model="attendanceForm.status" class="form-input" required>
            <option value="present">Present</option>
            <option value="absent">Absent</option>
            <option value="late">Late</option>
            <option value="half_day">Half Day</option>
            <option value="leave">On Leave</option>
          </select>
        </div>
        <div class="form-group">
          <label>Clock In (Optional)</label>
          <input type="time" v-model="attendanceForm.clock_in" class="form-input" />
        </div>
        <div class="form-group">
          <label>Clock Out (Optional)</label>
          <input type="time" v-model="attendanceForm.clock_out" class="form-input" />
        </div>
        <div class="form-group">
          <label>Overtime Hours (Optional)</label>
          <input type="number" v-model="attendanceForm.overtime_hours" class="form-input" min="0" max="24" step="0.5" />
        </div>
        <div class="form-group">
          <label>Notes (Optional)</label>
          <textarea v-model="attendanceForm.notes" class="form-input" rows="2"></textarea>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showMarkAttendanceModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="markAttendance" :disabled="attendanceForm.processing">
          {{ attendanceForm.processing ? 'Saving...' : 'Save Attendance' }}
        </button>
      </template>
    </Modal>

    <!-- Bulk Clock Modal -->
    <Modal :show="showBulkClockModal" @close="showBulkClockModal = false" title="Bulk Clock In/Out">
      <form @submit.prevent="processBulkClock">
        <div class="form-group">
          <label>Select Staff</label>
          <div class="staff-checkbox-list">
            <label v-for="s in staff" :key="s.id" class="staff-checkbox-item">
              <input type="checkbox" v-model="bulkClockForm.staff_ids" :value="s.id" />
              <span>{{ s.name }}</span>
            </label>
          </div>
        </div>
        <div class="form-group">
          <label>Action</label>
          <select v-model="bulkClockForm.action" class="form-input" required>
            <option value="clock_in">Clock In</option>
            <option value="clock_out">Clock Out</option>
          </select>
        </div>
        <div class="form-group">
          <label>Date</label>
          <input type="date" v-model="bulkClockForm.date" class="form-input" required />
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showBulkClockModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="processBulkClock" :disabled="bulkClockForm.processing">
          {{ bulkClockForm.processing ? 'Processing...' : 'Process' }}
        </button>
      </template>
    </Modal>

    <!-- Request Leave Modal -->
    <Modal :show="showRequestLeaveModal" @close="showRequestLeaveModal = false" title="Request Leave">
      <form @submit.prevent="submitLeaveRequest">
        <div class="form-group">
          <label>Staff Member</label>
          <select v-model="leaveForm.staff_id" class="form-input" required>
            <option value="">Select staff</option>
            <option v-for="s in staff" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>Leave Type</label>
          <select v-model="leaveForm.leave_type" class="form-input" required>
            <option value="vacation">Vacation</option>
            <option value="sick">Sick Leave</option>
            <option value="emergency">Emergency</option>
            <option value="unpaid">Unpaid Leave</option>
          </select>
        </div>
        <div class="form-group">
          <label>Start Date</label>
          <input type="date" v-model="leaveForm.start_date" class="form-input" required />
        </div>
        <div class="form-group">
          <label>End Date</label>
          <input type="date" v-model="leaveForm.end_date" class="form-input" required />
        </div>
        <div class="form-group">
          <label>Reason (Optional)</label>
          <textarea v-model="leaveForm.reason" class="form-input" rows="3"></textarea>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showRequestLeaveModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="submitLeaveRequest" :disabled="leaveForm.processing">
          {{ leaveForm.processing ? 'Submitting...' : 'Submit Request' }}
        </button>
      </template>
    </Modal>

    <!-- Monthly Report Modal -->
    <Modal :show="showMonthlyReportModal" @close="showMonthlyReportModal = false" title="Monthly Attendance Report">
      <div class="monthly-report-content">
        <div class="form-group">
          <label>Month</label>
          <select v-model="reportMonth" class="form-input" @change="loadMonthlyReport">
            <option value="1">January</option>
            <option value="2">February</option>
            <option value="3">March</option>
            <option value="4">April</option>
            <option value="5">May</option>
            <option value="6">June</option>
            <option value="7">July</option>
            <option value="8">August</option>
            <option value="9">September</option>
            <option value="10">October</option>
            <option value="11">November</option>
            <option value="12">December</option>
          </select>
        </div>
        <div class="form-group">
          <label>Year</label>
          <select v-model="reportYear" class="form-input" @change="loadMonthlyReport">
            <option value="2024">2024</option>
            <option value="2025">2025</option>
            <option value="2026">2026</option>
          </select>
        </div>
        <div v-if="monthlyReportData" class="report-summary">
          <div class="report-summary-item">
            <span class="rs-label">Total Present</span>
            <span class="rs-value">{{ monthlyReportData.summary?.total_present || 0 }}</span>
          </div>
          <div class="report-summary-item">
            <span class="rs-label">Total Absent</span>
            <span class="rs-value">{{ monthlyReportData.summary?.total_absent || 0 }}</span>
          </div>
          <div class="report-summary-item">
            <span class="rs-label">Half Days</span>
            <span class="rs-value">{{ monthlyReportData.summary?.total_half_days || 0 }}</span>
          </div>
          <div class="report-summary-item">
            <span class="rs-label">Total Leave</span>
            <span class="rs-value">{{ monthlyReportData.summary?.total_leave || 0 }}</span>
          </div>
          <div class="report-summary-item">
            <span class="rs-label">Late Arrivals</span>
            <span class="rs-value">{{ monthlyReportData.late_arrivals || 0 }}</span>
          </div>
          <div class="report-summary-item">
            <span class="rs-label">Total Overtime</span>
            <span class="rs-value">{{ monthlyReportData.summary?.total_overtime?.toFixed(1) || 0 }} hrs</span>
          </div>
        </div>
      </div>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showMonthlyReportModal = false">Close</button>
        <button class="modal-btn modal-btn--primary" @click="exportMonthlyReport">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14" style="display:inline;vertical-align:middle;margin-right:4px">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export CSV
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
  staff: {
    type: Array,
    default: () => []
  },
  todayAttendance: {
    type: Array,
    default: () => []
  },
  pendingLeaveRequests: {
    type: Array,
    default: () => []
  },
  date: {
    type: String,
    default: null
  }
})

const showMarkAttendanceModal = ref(false)
const showBulkClockModal = ref(false)
const showRequestLeaveModal = ref(false)
const showMonthlyReportModal = ref(false)
const currentDate = ref(props.date ? new Date(props.date) : new Date())
const reportMonth = ref(new Date().getMonth() + 1)
const reportYear = ref(new Date().getFullYear())
const monthlyReportData = ref(null)

const currentDateFormatted = computed(() => {
  return currentDate.value.toLocaleDateString('en-TZ', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
})

const presentCount = computed(() => props.todayAttendance.filter(a => a.status === 'present').length)
const absentCount = computed(() => props.todayAttendance.filter(a => a.status === 'absent').length)
const lateCount = computed(() => props.todayAttendance.filter(a => a.status === 'late').length)
const onLeaveCount = computed(() => props.todayAttendance.filter(a => a.status === 'leave').length)

const initials = (name) => {
  if (!name) return '??'
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
}

const previousDay = () => {
  currentDate.value = new Date(currentDate.value.setDate(currentDate.value.getDate() - 1))
}

const nextDay = () => {
  currentDate.value = new Date(currentDate.value.setDate(currentDate.value.getDate() + 1))
}

const exportReport = () => {
  const date = currentDate.value.toISOString().split('T')[0]
  window.location.href = `/attendance/export?date=${date}&format=csv`
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-TZ', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const approveLeave = (id) => {
  window.location.href = `/attendance/leave/${id}/approve`
}

const attendanceForm = useForm({
  staff_id: '',
  work_date: new Date().toISOString().slice(0, 10),
  status: 'present',
  clock_in: '',
  clock_out: '',
  overtime_hours: 0,
  notes: ''
})

const bulkClockForm = useForm({
  staff_ids: [],
  action: 'clock_in',
  date: new Date().toISOString().slice(0, 10)
})

const leaveForm = useForm({
  staff_id: '',
  leave_type: 'vacation',
  start_date: '',
  end_date: '',
  reason: ''
})

const markAttendance = () => {
  attendanceForm.post('/attendance', {
    onSuccess: () => {
      showMarkAttendanceModal.value = false
      attendanceForm.reset()
    }
  })
}

const processBulkClock = () => {
  router.post('/attendance/bulk-clock', bulkClockForm.data(), {
    onSuccess: (response) => {
      alert(`Processed ${response.props.count} staff records`)
      showBulkClockModal.value = false
      bulkClockForm.reset()
    }
  })
}

const submitLeaveRequest = () => {
  leaveForm.post('/attendance/leave', {
    onSuccess: () => {
      showRequestLeaveModal.value = false
      leaveForm.reset()
    }
  })
}

const loadMonthlyReport = async () => {
  const response = await fetch(`/attendance/monthly-report?month=${reportMonth.value}&year=${reportYear.value}`)
  monthlyReportData.value = await response.json()
}

const exportMonthlyReport = () => {
  window.location.href = `/attendance/monthly-report?month=${reportMonth.value}&year=${reportYear.value}&export=csv`
}

const approveLeave = (id) => {
  router.post(`/attendance/leave/${id}/approve`, {}, {
    onSuccess: () => {
      router.reload()
    }
  })
}

const rejectLeave = (id) => {
  router.post(`/attendance/leave/${id}/reject`, {}, {
    onSuccess: () => {
      router.reload()
    }
  })
}
</script>

<style scoped>
.attendance-container {
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

.date-selector {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 12px 20px;
  width: fit-content;
}

.date-btn {
  padding: 8px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  background: white;
  color: #4a6357;
  cursor: pointer;
  transition: all 0.15s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.date-btn:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.current-date {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
  min-width: 200px;
  text-align: center;
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

.actions-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
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

.attendance-section {
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

.filter-actions {
  display: flex;
  gap: 12px;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 12px;
  color: #4a6357;
  background: white;
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

.attendance-table {
  width: 100%;
  border-collapse: collapse;
}

.attendance-table th {
  text-align: left;
  padding: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #4a6357;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.attendance-table td {
  padding: 12px;
  font-size: 13px;
  color: #1a2e24;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.attendance-table tr:last-child td {
  border-bottom: none;
}

.staff-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.staff-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 600;
  color: white;
}

.staff-avatar--sarah {
  background: #e91e63;
}

.staff-avatar--john {
  background: #2196f3;
}

.staff-avatar--ali {
  background: #ff9800;
}

.staff-avatar--mary {
  background: #9c27b0;
}

.staff-avatar--fatuma {
  background: #4caf50;
}

.staff-details {
  display: flex;
  flex-direction: column;
}

.staff-name {
  font-weight: 500;
  color: #1a2e24;
}

.staff-id {
  font-size: 11px;
  color: #4a6357;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge--present {
  background: #e8f5e9;
  color: #2d7a50;
}

.status-badge--absent {
  background: #ffebee;
  color: #c62828;
}

.status-badge--late {
  background: #fff3e0;
  color: #e65100;
}

.status-badge--leave {
  background: #e3f2fd;
  color: #1565c0;
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
}

.table-action:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.leave-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.leave-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.leave-card {
  background: #f9fafb;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 8px;
  padding: 16px;
}

.leave-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.leave-staff {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
}

.leave-days {
  font-size: 12px;
  color: #4a6357;
  background: white;
  padding: 4px 8px;
  border-radius: 4px;
}

.leave-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 12px;
}

.leave-detail {
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

.leave-actions {
  display: flex;
  gap: 8px;
}

.leave-action {
  flex: 1;
  padding: 6px 12px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 4px;
  font-size: 11px;
  cursor: pointer;
  transition: all 0.15s;
}

.leave-action--approve {
  background: #e8f5e9;
  color: #2d7a50;
  border-color: #2d7a50;
}

.leave-action--approve:hover {
  background: #2d7a50;
  color: white;
}

.leave-action--reject {
  background: #ffebee;
  color: #c62828;
  border-color: #c62828;
}

.leave-action--reject:hover {
  background: #c62828;
  color: white;
}

.empty-state {
  grid-column: 1/-1;
  text-align: center;
  padding: 40px;
  color: #4a6357;
}

.modal-btn {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.15s;
}

.modal-btn--cancel {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  color: #4a6357;
}

.modal-btn--cancel:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.modal-btn--primary {
  background: #4caf76;
  border: 1px solid #4caf76;
  color: white;
}

.modal-btn--primary:hover {
  background: #2d7a50;
  border-color: #2d7a50;
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

.staff-checkbox-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 200px;
  overflow-y: auto;
  padding: 8px;
  background: #f9fafb;
  border-radius: 6px;
}

.staff-checkbox-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #1a2e24;
}

.staff-checkbox-item input {
  width: auto;
}

.monthly-report-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.report-summary {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.report-summary-item {
  display: flex;
  justify-content: space-between;
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
}

.rs-label {
  font-size: 13px;
  color: #4a6357;
}

.rs-value {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
}

@media (max-width: 1024px) {
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .leave-grid {
    grid-template-columns: 1fr;
  }
}
</style>
