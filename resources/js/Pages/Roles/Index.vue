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
          <div class="summary-value">{{ totalRoles }}</div>
          <div class="summary-change summary-change--neutral">All configured</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Active Users</div>
          <div class="summary-value">{{ totalUsers }}</div>
          <div class="summary-change summary-change--positive">Assigned to roles</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Permissions</div>
          <div class="summary-value">{{ totalPermissions }}</div>
          <div class="summary-change summary-change--neutral">Across all modules</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Custom Roles</div>
          <div class="summary-value">{{ customRolesCount }}</div>
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
          <div v-for="role in roles" :key="role.id" class="role-card">
            <div class="role-header">
              <div class="role-icon role-icon--admin">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
              </div>
              <div class="role-info">
                <div class="role-name">{{ role.name }}</div>
                <div class="role-users">{{ role.users_count }} users</div>
              </div>
            </div>
            <div class="role-description">{{ role.description }}</div>
            <div class="role-permissions">
              <span v-for="permission in role.permissions" :key="permission" class="permission-tag">{{ permission }}</span>
            </div>
            <div class="role-actions">
              <Link :href="route('roles.show', role.id)" class="role-action">View</Link>
              <Link :href="route('roles.edit', role.id)" class="role-action">Edit</Link>
            </div>
          </div>
          <div v-if="roles.length === 0" style="grid-column: 1/-1; text-align: center; color: #4a6357; padding: 40px;">
            No roles found
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
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  roles: {
    type: Array,
    default: () => []
  }
})

const totalRoles = computed(() => props.roles.length)
const totalUsers = computed(() => props.roles.reduce((sum, role) => sum + (role.users_count || 0), 0))
const totalPermissions = computed(() => props.roles.reduce((sum, role) => sum + (role.permissions?.length || 0), 0))
const customRolesCount = computed(() => props.roles.filter(r => r.name !== 'admin' && r.name !== 'manager' && r.name !== 'collector').length)
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
