<template>
  <AppLayout title="Staff">
    <div class="staff-container">
      <div class="header">
        <h1>Staff</h1>
        <p>Manage staff members</p>
      </div>

      <!-- Summary Cards -->
      <div class="summary-grid">
        <div class="summary-card">
          <div class="summary-label">Total Staff</div>
          <div class="summary-value">18</div>
          <div class="summary-change summary-change--neutral">All departments</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Active</div>
          <div class="summary-value">16</div>
          <div class="summary-change summary-change--positive">Currently working</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">On Leave</div>
          <div class="summary-value">2</div>
          <div class="summary-change summary-change--neutral">1 sick, 1 vacation</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Departments</div>
          <div class="summary-value">4</div>
          <div class="summary-change summary-change--positive">Operations, Admin, etc.</div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="actions-bar">
        <button class="action-btn action-btn--primary">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          Add Staff
        </button>
        <button class="action-btn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export List
        </button>
        <div class="search-box">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z"/>
          </svg>
          <input type="text" placeholder="Search staff..." class="search-input">
        </div>
      </div>

      <!-- Staff List -->
      <div class="staff-section">
        <div class="section-header">
          <h3>All Staff Members</h3>
          <div class="filter-actions">
            <select class="filter-select">
              <option>All Departments</option>
              <option>Operations</option>
              <option>Admin</option>
              <option>Finance</option>
            </select>
            <select class="filter-select">
              <option>All Status</option>
              <option>Active</option>
              <option>On Leave</option>
              <option>Inactive</option>
            </select>
          </div>
        </div>
        <table class="staff-table">
          <thead>
            <tr>
              <th>Staff</th>
              <th>Role</th>
              <th>Department</th>
              <th>Zone</th>
              <th>Phone</th>
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
              <td>Operations</td>
              <td>Zone A-F</td>
              <td>+255 712 345 678</td>
              <td><span class="status-badge status-badge--active">Active</span></td>
              <td>
                <button class="table-action">View</button>
                <button class="table-action">Edit</button>
              </td>
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
              <td>Operations</td>
              <td>Zone G-J</td>
              <td>+255 713 456 789</td>
              <td><span class="status-badge status-badge--active">Active</span></td>
              <td>
                <button class="table-action">View</button>
                <button class="table-action">Edit</button>
              </td>
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
              <td>Operations</td>
              <td>All Zones</td>
              <td>+255 714 567 890</td>
              <td><span class="status-badge status-badge--active">Active</span></td>
              <td>
                <button class="table-action">View</button>
                <button class="table-action">Edit</button>
              </td>
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
                  <td>Admin</td>
              <td>-</td>
              <td>+255 715 678 901</td>
              <td><span class="status-badge status-badge--leave">On Leave</span></td>
              <td>
                <button class="table-action">View</button>
                <button class="table-action">Edit</button>
              </td>
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
              <td>Operations</td>
              <td>Zone K-L</td>
              <td>+255 716 789 012</td>
              <td><span class="status-badge status-badge--active">Active</span></td>
              <td>
                <button class="table-action">View</button>
                <button class="table-action">Edit</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Department Overview -->
      <div class="departments-section">
        <h3>Department Overview</h3>
        <div class="departments-grid">
          <div class="department-card">
            <div class="department-header">
              <div class="department-icon department-icon--operations">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="20" height="20">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                </svg>
              </div>
              <div class="department-name">Operations</div>
            </div>
            <div class="department-stats">
              <div class="department-stat">
                <span class="stat-label">Staff</span>
                <span class="stat-value">12</span>
              </div>
              <div class="department-stat">
                <span class="stat-label">Active</span>
                <span class="stat-value">11</span>
              </div>
            </div>
          </div>
          <div class="department-card">
            <div class="department-header">
              <div class="department-icon department-icon--admin">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="20" height="20">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
                </svg>
              </div>
              <div class="department-name">Admin</div>
            </div>
            <div class="department-stats">
              <div class="department-stat">
                <span class="stat-label">Staff</span>
                <span class="stat-value">3</span>
              </div>
              <div class="department-stat">
                <span class="stat-label">Active</span>
                <span class="stat-value">2</span>
              </div>
            </div>
          </div>
          <div class="department-card">
            <div class="department-header">
              <div class="department-icon department-icon--finance">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="20" height="20">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
              </div>
              <div class="department-name">Finance</div>
            </div>
            <div class="department-stats">
              <div class="department-stat">
                <span class="stat-label">Staff</span>
                <span class="stat-value">2</span>
              </div>
              <div class="department-stat">
                <span class="stat-label">Active</span>
                <span class="stat-value">2</span>
              </div>
            </div>
          </div>
          <div class="department-card">
            <div class="department-header">
              <div class="department-icon department-icon--maintenance">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="20" height="20">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>
                </svg>
              </div>
              <div class="department-name">Maintenance</div>
            </div>
            <div class="department-stats">
              <div class="department-stat">
                <span class="stat-label">Staff</span>
                <span class="stat-value">1</span>
              </div>
              <div class="department-stat">
                <span class="stat-label">Active</span>
                <span class="stat-value">1</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
</script>

<style scoped>
.staff-container {
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

.actions-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  align-items: center;
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

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 8px;
  flex: 1;
  max-width: 300px;
  margin-left: auto;
}

.search-box svg {
  color: #4a6357;
}

.search-input {
  border: none;
  outline: none;
  font-size: 13px;
  color: #1a2e24;
  flex: 1;
}

.search-input::placeholder {
  color: #9ca3af;
}

.staff-section {
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

.staff-table {
  width: 100%;
  border-collapse: collapse;
}

.staff-table th {
  text-align: left;
  padding: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #4a6357;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.staff-table td {
  padding: 12px;
  font-size: 13px;
  color: #1a2e24;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.staff-table tr:last-child td {
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

.status-badge--active {
  background: #e8f5e9;
  color: #2d7a50;
}

.status-badge--leave {
  background: #fff3e0;
  color: #e65100;
}

.status-badge--inactive {
  background: #f5f5f5;
  color: #757575;
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

.departments-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.departments-section h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 16px;
}

.departments-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.department-card {
  background: #f9fafb;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 8px;
  padding: 16px;
}

.department-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.department-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.department-icon--operations {
  background: #ffe0b2;
  color: #e65100;
}

.department-icon--admin {
  background: #e1bee7;
  color: #7b1fa2;
}

.department-icon--finance {
  background: #c8e6c9;
  color: #2d7a50;
}

.department-icon--maintenance {
  background: #bbdefb;
  color: #1565c0;
}

.department-name {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
}

.department-stats {
  display: flex;
  gap: 16px;
}

.department-stat {
  display: flex;
  flex-direction: column;
}

.stat-label {
  font-size: 11px;
  color: #4a6357;
}

.stat-value {
  font-size: 18px;
  font-weight: 600;
  color: #1a2e24;
}

@media (max-width: 1024px) {
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .departments-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
