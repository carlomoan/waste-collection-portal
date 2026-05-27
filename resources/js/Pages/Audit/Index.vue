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
          <div class="summary-value">1,247</div>
          <div class="summary-change summary-change--positive">This month</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Active Users</div>
          <div class="summary-value">8</div>
          <div class="summary-change summary-change--neutral">Today</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Failed Logins</div>
          <div class="summary-value">3</div>
          <div class="summary-change summary-change--negative">Security alerts</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Data Changes</div>
          <div class="summary-value">156</div>
          <div class="summary-change summary-change--neutral">This week</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters-bar">
        <div class="filter-group">
          <label>Date Range</label>
          <select class="filter-select">
            <option>Last 7 days</option>
            <option>Last 30 days</option>
            <option>Last 90 days</option>
            <option>Custom range</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Action Type</label>
          <select class="filter-select">
            <option>All Actions</option>
            <option>Create</option>
            <option>Update</option>
            <option>Delete</option>
            <option>Login</option>
            <option>Export</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Module</label>
          <select class="filter-select">
            <option>All Modules</option>
            <option>Clients</option>
            <option>Transactions</option>
            <option>Staff</option>
            <option>Settings</option>
          </select>
        </div>
        <div class="filter-group">
          <label>User</label>
          <select class="filter-select">
            <option>All Users</option>
            <option>Admin</option>
            <option>Manager</option>
            <option>Finance</option>
          </select>
        </div>
        <button class="action-btn">
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
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>{{ formatDateTime('2026-05-27T14:32:15') }}</td>
              <td>
                <div class="user-info">
                  <div class="user-avatar user-avatar--admin">AD</div>
                  <span>Admin User</span>
                </div>
              </td>
              <td><span class="action-badge action-badge--create">Create</span></td>
              <td>Clients</td>
              <td>Created new client: Juma Mwinyi</td>
              <td>192.168.1.100</td>
              <td><span class="status-badge status-badge--success">Success</span></td>
            </tr>
            <tr>
              <td>{{ formatDateTime('2026-05-27T14:28:42') }}</td>
              <td>
                <div class="user-info">
                  <div class="user-avatar user-avatar--finance">FN</div>
                  <span>Mary Kileo</span>
                </div>
              </td>
              <td><span class="action-badge action-badge--update">Update</span></td>
              <td>Transactions</td>
              <td>Updated payment status for INV-2026-001</td>
              <td>192.168.1.105</td>
              <td><span class="status-badge status-badge--success">Success</span></td>
            </tr>
            <tr>
              <td>{{ formatDateTime('2026-05-27T14:15:30') }}</td>
              <td>
                <div class="user-info">
                  <div class="user-avatar user-avatar--manager">MG</div>
                  <span>John Mwangi</span>
                </div>
              </td>
              <td><span class="action-badge action-badge--export">Export</span></td>
              <td>Reports</td>
              <td>Exported monthly collection report</td>
              <td>192.168.1.110</td>
              <td><span class="status-badge status-badge--success">Success</span></td>
            </tr>
            <tr>
              <td>{{ formatDateTime('2026-05-27T14:02:18') }}</td>
              <td>
                <div class="user-info">
                  <div class="user-avatar user-avatar--unknown">?</div>
                  <span>Unknown</span>
                </div>
              </td>
              <td><span class="action-badge action-badge--login">Login</span></td>
              <td>Auth</td>
              <td>Failed login attempt - invalid credentials</td>
              <td>10.0.0.45</td>
              <td><span class="status-badge status-badge--error">Failed</span></td>
            </tr>
            <tr>
              <td>{{ formatDateTime('2026-05-27T13:45:55') }}</td>
              <td>
                <div class="user-info">
                  <div class="user-avatar user-avatar--collector">CL</div>
                  <span>Sarah Shechambo</span>
                </div>
              </td>
              <td><span class="action-badge action-badge--update">Update</span></td>
              <td>Collections</td>
              <td>Recorded collection for client C-001</td>
              <td>192.168.1.115</td>
              <td><span class="status-badge status-badge--success">Success</span></td>
            </tr>
            <tr>
              <td>{{ formatDateTime('2026-05-27T13:30:22') }}</td>
              <td>
                <div class="user-info">
                  <div class="user-avatar user-avatar--admin">AD</div>
                  <span>Admin User</span>
                </div>
              </td>
              <td><span class="action-badge action-badge--delete">Delete</span></td>
              <td>Staff</td>
              <td>Deleted staff record: STF-999</td>
              <td>192.168.1.100</td>
              <td><span class="status-badge status-badge--success">Success</span></td>
            </tr>
            <tr>
              <td>{{ formatDateTime('2026-05-27T13:15:10') }}</td>
              <td>
                <div class="user-info">
                  <div class="user-avatar user-avatar--finance">FN</div>
                  <span>Mary Kileo</span>
                </div>
              </td>
              <td><span class="action-badge action-badge--create">Create</span></td>
              <td>Expenses</td>
              <td>Created expense record: Fuel purchase</td>
              <td>192.168.1.105</td>
              <td><span class="status-badge status-badge--success">Success</span></td>
            </tr>
            <tr>
              <td>{{ formatDateTime('2026-05-27T12:58:45') }}</td>
              <td>
                <div class="user-info">
                  <div class="user-avatar user-avatar--manager">MG</div>
                  <span>John Mwangi</span>
                </div>
              </td>
              <td><span class="action-badge action-badge--update">Update</span></td>
              <td>Settings</td>
              <td>Updated system configuration</td>
              <td>192.168.1.110</td>
              <td><span class="status-badge status-badge--success">Success</span></td>
            </tr>
          </tbody>
        </table>
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
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

const formatDateTime = (datetime) => {
  const date = new Date(datetime)
  return date.toLocaleString('en-TZ', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
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
</style>
