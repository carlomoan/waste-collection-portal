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
          <div class="summary-value">14</div>
          <div class="summary-change summary-change--positive">78% attendance rate</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Absent</div>
          <div class="summary-value">2</div>
          <div class="summary-change summary-change--negative">1 sick, 1 leave</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Late</div>
          <div class="summary-value">2</div>
          <div class="summary-change summary-change--neutral">Arrived after 8:00 AM</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">On Leave</div>
          <div class="summary-value">0</div>
          <div class="summary-change summary-change--neutral">No approved leave</div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="actions-bar">
        <button class="action-btn action-btn--primary">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          Mark Attendance
        </button>
        <button class="action-btn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
          </svg>
          Request Leave
        </button>
        <button class="action-btn">
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
            <tr>
              <td>
                <div class="staff-info">
                  <div class="staff-avatar staff-avatar--sarah">SS</div>
                  <div class="staff-details">
                    <div class="staff-name">Sarah Shechambo</div>
                    <div class="staff-id">STF-001</div>
                  </div>
                </div>
              </td>
              <td>Collector</td>
              <td>07:45 AM</td>
              <td>05:15 PM</td>
              <td>9.5h</td>
              <td><span class="status-badge status-badge--present">Present</span></td>
              <td><button class="table-action">View</button></td>
            </tr>
            <tr>
              <td>
                <div class="staff-info">
                  <div class="staff-avatar staff-avatar--john">JM</div>
                  <div class="staff-details">
                    <div class="staff-name">John Mwangi</div>
                    <div class="staff-id">STF-002</div>
                  </div>
                </div>
              </td>
              <td>Collector</td>
              <td>08:15 AM</td>
              <td>05:00 PM</td>
              <td>8.75h</td>
              <td><span class="status-badge status-badge--late">Late</span></td>
              <td><button class="table-action">View</button></td>
            </tr>
            <tr>
              <td>
                <div class="staff-info">
                  <div class="staff-avatar staff-avatar--ali">AH</div>
                  <div class="staff-details">
                    <div class="staff-name">Ali Hassan</div>
                    <div class="staff-id">STF-004</div>
                  </div>
                </div>
              </td>
              <td>Driver</td>
              <td>07:30 AM</td>
              <td>05:30 PM</td>
              <td>10h</td>
              <td><span class="status-badge status-badge--present">Present</span></td>
              <td><button class="table-action">View</button></td>
            </tr>
            <tr>
              <td>
                <div class="staff-info">
                  <div class="staff-avatar staff-avatar--mary">MK</div>
                  <div class="staff-details">
                    <div class="staff-name">Mary Kileo</div>
                    <div class="staff-id">STF-005</div>
                  </div>
                </div>
              </td>
              <td>Admin</td>
              <td>-</td>
              <td>-</td>
              <td>-</td>
              <td><span class="status-badge status-badge--absent">Absent</span></td>
              <td><button class="table-action">Mark</button></td>
            </tr>
            <tr>
              <td>
                <div class="staff-info">
                  <div class="staff-avatar staff-avatar--fatuma">FM</div>
                  <div class="staff-details">
                    <div class="staff-name">Fatuma Makame</div>
                    <div class="staff-id">STF-003</div>
                  </div>
                </div>
              </td>
              <td>Collector</td>
              <td>08:30 AM</td>
              <td>05:00 PM</td>
              <td>8.5h</td>
              <td><span class="status-badge status-badge--late">Late</span></td>
              <td><button class="table-action">View</button></td>
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
          <div class="leave-card">
            <div class="leave-header">
              <div class="leave-staff">Sarah Shechambo</div>
              <div class="leave-days">3 days</div>
            </div>
            <div class="leave-details">
              <div class="leave-detail">
                <span class="detail-label">Type:</span>
                <span class="detail-value">Vacation</span>
              </div>
              <div class="leave-detail">
                <span class="detail-label">From:</span>
                <span class="detail-value">Jun 5, 2026</span>
              </div>
              <div class="leave-detail">
                <span class="detail-label">To:</span>
                <span class="detail-value">Jun 7, 2026</span>
              </div>
            </div>
            <div class="leave-actions">
              <button class="leave-action leave-action--approve">Approve</button>
              <button class="leave-action leave-action--reject">Reject</button>
            </div>
          </div>
          <div class="leave-card">
            <div class="leave-header">
              <div class="leave-staff">John Mwangi</div>
              <div class="leave-days">1 day</div>
            </div>
            <div class="leave-details">
              <div class="leave-detail">
                <span class="detail-label">Type:</span>
                <span class="detail-value">Sick Leave</span>
              </div>
              <div class="leave-detail">
                <span class="detail-label">From:</span>
                <span class="detail-value">Jun 10, 2026</span>
              </div>
              <div class="leave-detail">
                <span class="detail-label">To:</span>
                <span class="detail-value">Jun 10, 2026</span>
              </div>
            </div>
            <div class="leave-actions">
              <button class="leave-action leave-action--approve">Approve</button>
              <button class="leave-action leave-action--reject">Reject</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const currentDate = ref(new Date())

const currentDateFormatted = computed(() => {
  return currentDate.value.toLocaleDateString('en-TZ', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
})

const previousDay = () => {
  currentDate.value = new Date(currentDate.value.setDate(currentDate.value.getDate() - 1))
}

const nextDay = () => {
  currentDate.value = new Date(currentDate.value.setDate(currentDate.value.getDate() + 1))
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

@media (max-width: 1024px) {
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .leave-grid {
    grid-template-columns: 1fr;
  }
}
</style>
