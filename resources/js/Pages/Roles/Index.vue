<template>
  <AppLayout title="Roles">
    <div class="roles-container">
      <div class="header">
        <h1>Roles & Permissions</h1>
        <p>Manage user roles and permissions</p>
      </div>

      <!-- Summary Cards -->
      <div class="summary-grid">
        <div class="summary-card">
          <div class="summary-label">Total Roles</div>
          <div class="summary-value">5</div>
          <div class="summary-change summary-change--neutral">All configured</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Active Users</div>
          <div class="summary-value">18</div>
          <div class="summary-change summary-change--positive">Assigned to roles</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Permissions</div>
          <div class="summary-value">32</div>
          <div class="summary-change summary-change--neutral">Across all modules</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Custom Roles</div>
          <div class="summary-value">2</div>
          <div class="summary-change summary-change--positive">User-defined</div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="actions-bar">
        <button class="action-btn action-btn--primary">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          Create Role
        </button>
        <button class="action-btn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
          </svg>
          Review Permissions
        </button>
      </div>

      <!-- Roles List -->
      <div class="roles-section">
        <div class="section-header">
          <h3>System Roles</h3>
          <button class="view-all-btn">View All Permissions</button>
        </div>
        <div class="roles-grid">
          <div class="role-card">
            <div class="role-header">
              <div class="role-icon role-icon--admin">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
              </div>
              <div class="role-info">
                <div class="role-name">Administrator</div>
                <div class="role-users">3 users</div>
              </div>
            </div>
            <div class="role-description">Full system access with all permissions</div>
            <div class="role-permissions">
              <span class="permission-tag">All Access</span>
              <span class="permission-tag">User Management</span>
              <span class="permission-tag">Settings</span>
            </div>
            <div class="role-actions">
              <button class="role-action">View</button>
              <button class="role-action">Edit</button>
            </div>
          </div>
          <div class="role-card">
            <div class="role-header">
              <div class="role-icon role-icon--manager">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
              </div>
              <div class="role-info">
                <div class="role-name">Manager</div>
                <div class="role-users">4 users</div>
              </div>
            </div>
            <div class="role-description">Manage operations and view reports</div>
            <div class="role-permissions">
              <span class="permission-tag">Operations</span>
              <span class="permission-tag">Reports</span>
              <span class="permission-tag">Staff</span>
            </div>
            <div class="role-actions">
              <button class="role-action">View</button>
              <button class="role-action">Edit</button>
            </div>
          </div>
          <div class="role-card">
            <div class="role-header">
              <div class="role-icon role-icon--finance">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
              </div>
              <div class="role-info">
                <div class="role-name">Finance</div>
                <div class="role-users">2 users</div>
              </div>
            </div>
            <div class="role-description">Handle payments, expenses, and banking</div>
            <div class="role-permissions">
              <span class="permission-tag">Transactions</span>
              <span class="permission-tag">Expenses</span>
              <span class="permission-tag">Banking</span>
            </div>
            <div class="role-actions">
              <button class="role-action">View</button>
              <button class="role-action">Edit</button>
            </div>
          </div>
          <div class="role-card">
            <div class="role-header">
              <div class="role-icon role-icon--collector">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                </svg>
              </div>
              <div class="role-info">
                <div class="role-name">Collector</div>
                <div class="role-users">8 users</div>
              </div>
            </div>
            <div class="role-description">Field operations and client interactions</div>
            <div class="role-permissions">
              <span class="permission-tag">Collections</span>
              <span class="permission-tag">Clients</span>
              <span class="permission-tag">Schedule</span>
            </div>
            <div class="role-actions">
              <button class="role-action">View</button>
              <button class="role-action">Edit</button>
            </div>
          </div>
          <div class="role-card">
            <div class="role-header">
              <div class="role-icon role-icon--viewer">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                </svg>
              </div>
              <div class="role-info">
                <div class="role-name">Viewer</div>
                <div class="role-users">1 user</div>
              </div>
            </div>
            <div class="role-description">Read-only access to reports and data</div>
            <div class="role-permissions">
              <span class="permission-tag">View Only</span>
              <span class="permission-tag">Reports</span>
            </div>
            <div class="role-actions">
              <button class="role-action">View</button>
              <button class="role-action">Edit</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Permissions Matrix -->
      <div class="permissions-section">
        <div class="section-header">
          <h3>Permission Matrix</h3>
          <select class="filter-select">
            <option>All Modules</option>
            <option>Operations</option>
            <option>Finance</option>
            <option>Admin</option>
          </select>
        </div>
        <table class="permissions-table">
          <thead>
            <tr>
              <th>Permission</th>
              <th>Admin</th>
              <th>Manager</th>
              <th>Finance</th>
              <th>Collector</th>
              <th>Viewer</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>View Dashboard</td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">✓</span></td>
            </tr>
            <tr>
              <td>Manage Clients</td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">✓</span></td>
            </tr>
            <tr>
              <td>Process Transactions</td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">-</span></td>
            </tr>
            <tr>
              <td>Manage Expenses</td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">-</span></td>
              <td><span class="check-icon">-</span></td>
            </tr>
            <tr>
              <td>Manage Staff</td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">-</span></td>
              <td><span class="check-icon">-</span></td>
              <td><span class="check-icon">-</span></td>
            </tr>
            <tr>
              <td>Manage Vehicles</td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">-</span></td>
              <td><span class="check-icon">-</span></td>
              <td><span class="check-icon">-</span></td>
            </tr>
            <tr>
              <td>Generate Reports</td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">-</span></td>
              <td><span class="check-icon">✓</span></td>
            </tr>
            <tr>
              <td>Manage Roles</td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">-</span></td>
              <td><span class="check-icon">-</span></td>
              <td><span class="check-icon">-</span></td>
              <td><span class="check-icon">-</span></td>
            </tr>
            <tr>
              <td>System Settings</td>
              <td><span class="check-icon">✓</span></td>
              <td><span class="check-icon">-</span></td>
              <td><span class="check-icon">-</span></td>
              <td><span class="check-icon">-</span></td>
              <td><span class="check-icon">-</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
</script>

<style scoped>
.roles-container {
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

.roles-section {
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

.filter-select {
  padding: 8px 12px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 12px;
  color: #4a6357;
  background: white;
}

.roles-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 16px;
}

.role-card {
  background: #f9fafb;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 8px;
  padding: 16px;
  display: flex;
  flex-direction: column;
}

.role-header {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
}

.role-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.role-icon--admin {
  background: #e3f2fd;
  color: #1565c0;
}

.role-icon--manager {
  background: #fff3e0;
  color: #e65100;
}

.role-icon--finance {
  background: #c8e6c9;
  color: #2d7a50;
}

.role-icon--collector {
  background: #ffe0b2;
  color: #e65100;
}

.role-icon--viewer {
  background: #f5f5f5;
  color: #757575;
}

.role-info {
  display: flex;
  flex-direction: column;
}

.role-name {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
}

.role-users {
  font-size: 11px;
  color: #4a6357;
}

.role-description {
  font-size: 12px;
  color: #4a6357;
  margin-bottom: 12px;
  line-height: 1.4;
}

.role-permissions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 12px;
}

.permission-tag {
  padding: 4px 10px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 12px;
  font-size: 10px;
  color: #4a6357;
}

.role-actions {
  display: flex;
  gap: 8px;
  margin-top: auto;
}

.role-action {
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

.role-action:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.permissions-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.permissions-table {
  width: 100%;
  border-collapse: collapse;
}

.permissions-table th {
  text-align: left;
  padding: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #4a6357;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.permissions-table td {
  padding: 12px;
  font-size: 13px;
  color: #1a2e24;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.permissions-table tr:last-child td {
  border-bottom: none;
}

.permissions-table th:not(:first-child),
.permissions-table td:not(:first-child) {
  text-align: center;
}

.check-icon {
  color: #2d7a50;
  font-weight: 600;
  font-size: 16px;
}

.permissions-table td:not(:first-child) span:not(.check-icon) {
  color: #9ca3af;
}

@media (max-width: 1024px) {
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .roles-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
