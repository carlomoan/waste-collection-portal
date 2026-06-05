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
        <button class="action-btn action-btn--primary" @click="showCreateModal = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          Create Role
        </button>
        <button class="action-btn" @click="seedDefaults">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Seed Defaults
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
              <button class="role-action" @click="openViewRole(role)">View</button>
              <button class="role-action" @click="openEditRole(role)">Edit</button>
              <button class="role-action" @click="showCloneModal(role)">Clone</button>
              <button class="role-action role-action--danger" @click="openDeleteRole(role)">Delete</button>
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
          <h3>Permission Groups</h3>
          <select v-model="selectedGroup" class="filter-select">
            <option value="">All Groups</option>
            <option v-for="(group, name) in permissionGroups" :key="name" :value="name">{{ name }}</option>
          </select>
        </div>
        <div v-for="(group, groupName) in filteredPermissionGroups" :key="groupName" class="permission-group">
          <h4>{{ groupName }}</h4>
          <div class="permission-list">
            <div v-for="perm in group" :key="perm.id" class="permission-item">
              <span class="perm-name">{{ perm.name }}</span>
              <span class="perm-desc">{{ perm.description }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- View Role Modal -->
    <Modal :show="showViewRoleModal" title="Role Details" @close="showViewRoleModal = false">
      <div v-if="viewingRole" class="detail-grid">
        <div class="detail-row"><span class="detail-label">Name</span><span class="detail-val">{{ viewingRole.name }}</span></div>
        <div class="detail-row"><span class="detail-label">Description</span><span class="detail-val">{{ viewingRole.description ?? '—' }}</span></div>
        <div class="detail-row"><span class="detail-label">Users</span><span class="detail-val">{{ viewingRole.users_count }}</span></div>
        <div v-if="viewingRole.permissions?.length" class="detail-row" style="flex-direction:column;align-items:flex-start;">
          <span class="detail-label" style="margin-bottom:6px;">Permissions</span>
          <div style="display:flex;flex-wrap:wrap;gap:4px;">
            <span v-for="p in viewingRole.permissions" :key="p" class="permission-tag">{{ p }}</span>
          </div>
        </div>
      </div>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showViewRoleModal = false">Close</button>
        <button class="modal-btn modal-btn--primary" @click="openEditRole(viewingRole); showViewRoleModal = false">Edit</button>
      </template>
    </Modal>

    <!-- Edit Role Modal -->
    <Modal :show="showEditRoleModal" title="Edit Role" @close="showEditRoleModal = false">
      <form v-if="roleEditForm" @submit.prevent="submitRoleEdit">
        <div class="form-group">
          <label>Role Name</label>
          <input type="text" v-model="roleEditForm.name" class="form-input" required />
        </div>
        <div class="form-group">
          <label>Permissions</label>
          <div v-for="(permissions, group) in props.permissionGroups" :key="group" class="permission-group">
            <div class="permission-group-label">{{ group }}</div>
            <label v-for="perm in permissions" :key="perm.id" class="permission-item">
              <input type="checkbox" :value="perm.id" v-model="roleEditForm.permissions">
              {{ perm.name }}
            </label>
          </div>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showEditRoleModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="submitRoleEdit" :disabled="roleEditForm?.processing">
          {{ roleEditForm?.processing ? 'Saving...' : 'Save Changes' }}
        </button>
      </template>
    </Modal>

    <!-- Delete Role Modal -->
    <Modal :show="showDeleteRoleModal" title="Delete Role" @close="showDeleteRoleModal = false">
      <p class="modal-text">Delete role <strong>{{ deletingRole?.name }}</strong>? Users assigned to this role will lose its permissions.</p>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showDeleteRoleModal = false">Cancel</button>
        <button class="modal-btn modal-btn--danger" @click="submitRoleDelete">Delete</button>
      </template>
    </Modal>

    <!-- Create Role Modal -->
    <Modal :show="showCreateModal" @close="showCreateModal = false" title="Create Role">
      <form @submit.prevent="submitCreate">
        <div class="form-group">
          <label>Role Name</label>
          <input type="text" v-model="createForm.name" class="form-input" required placeholder="Enter role name">
        </div>
        <div class="form-group">
          <label>Permissions</label>
          <div v-for="(permissions, group) in props.permissionGroups" :key="group" class="permission-group">
            <div class="permission-group-label">{{ group }}</div>
            <label v-for="perm in permissions" :key="perm.id" class="permission-item">
              <input type="checkbox" :value="perm.id" v-model="createForm.permissions">
              {{ perm.name }}
            </label>
          </div>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showCreateModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="submitCreate" :disabled="createForm.processing">
          {{ createForm.processing ? 'Creating...' : 'Create Role' }}
        </button>
      </template>
    </Modal>

    <!-- Clone Role Modal -->
    <Modal :show="showCloneModalFlag" @close="showCloneModalFlag = false" title="Clone Role">
      <form @submit.prevent="submitClone">
        <div class="form-group">
          <label>Source Role</label>
          <input type="text" :value="selectedRole?.name" class="form-input" disabled>
        </div>
        <div class="form-group">
          <label>New Role Name</label>
          <input type="text" v-model="cloneForm.new_name" class="form-input" required placeholder="Enter new role name">
        </div>
        <p class="clone-info">This will create a new role with all permissions from {{ selectedRole?.name }}.</p>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showCloneModalFlag = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="submitClone" :disabled="cloneForm.processing">
          {{ cloneForm.processing ? 'Cloning...' : 'Clone Role' }}
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
  roles: {
    type: Array,
    default: () => []
  },
  permissionGroups: {
    type: Object,
    default: () => ({})
  }
})

const showCreateModal = ref(false)
const showCloneModalFlag = ref(false)
const showViewRoleModal = ref(false)
const showEditRoleModal = ref(false)
const showDeleteRoleModal = ref(false)
const selectedRole = ref(null)
const viewingRole = ref(null)
const editingRole = ref(null)
const deletingRole = ref(null)
const roleEditForm = ref(null)
const selectedGroup = ref('')

const createForm = useForm({
  name: '',
  permissions: []
})

const cloneForm = useForm({
  new_name: ''
})

const filteredPermissionGroups = computed(() => {
  if (!selectedGroup.value) return props.permissionGroups
  return { [selectedGroup.value]: props.permissionGroups[selectedGroup.value] }
})

const totalRoles = computed(() => props.roles.length)
const totalUsers = computed(() => props.roles.reduce((sum, role) => sum + (role.users_count || 0), 0))
const totalPermissions = computed(() => props.roles.reduce((sum, role) => sum + (role.permissions?.length || 0), 0))
const customRolesCount = computed(() => props.roles.filter(r => r.name !== 'admin' && r.name !== 'manager' && r.name !== 'collector').length)

const showCloneModal = (role) => {
  selectedRole.value = role
  cloneForm.new_name = role.name + ' (Copy)'
  showCloneModalFlag.value = true
}

const submitClone = () => {
  cloneForm.post(`/roles/${selectedRole.value.id}/clone`, {
    onSuccess: () => {
      showCloneModalFlag.value = false
      cloneForm.reset()
      selectedRole.value = null
    }
  })
}

const submitCreate = () => {
  createForm.post('/roles', {
    onSuccess: () => {
      showCreateModal.value = false
      createForm.reset()
    }
  })
}

const openViewRole = (r) => { viewingRole.value = r; showViewRoleModal.value = true }

const openEditRole = (r) => {
  editingRole.value = r
  roleEditForm.value = useForm({
    name:        r.name ?? '',
    permissions: Array.isArray(r.permissions) ? r.permissions.map(p => p?.id ?? p) : [],
  })
  showEditRoleModal.value = true
}

const submitRoleEdit = () => {
  roleEditForm.value.patch(`/roles/${editingRole.value.id}`, {
    onSuccess: () => {
      showEditRoleModal.value = false
      editingRole.value = null
      router.reload()
    }
  })
}

const openDeleteRole = (r) => { deletingRole.value = r; showDeleteRoleModal.value = true }

const submitRoleDelete = () => {
  router.delete(`/roles/${deletingRole.value.id}`, {
    onSuccess: () => { showDeleteRoleModal.value = false; deletingRole.value = null; router.reload() }
  })
}

const seedDefaults = () => {
  if (confirm('This will seed default roles with standard permissions. Continue?')) {
    router.post('/roles/seed-defaults', {}, {
      onSuccess: () => {
        router.reload()
      }
    })
  }
}
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

.permission-group {
  margin-bottom: 24px;
}

.permission-group h4 {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 12px;
}

.permission-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 12px;
}

.permission-item {
  background: #f9fafb;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  padding: 12px;
}

.perm-name {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 4px;
}

.perm-desc {
  font-size: 11px;
  color: #4a6357;
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

.form-input:disabled {
  background: #f5f5f5;
  color: #4a6357;
}

.form-input:focus {
  outline: none;
  border-color: #4caf76;
}

.clone-info {
  font-size: 12px;
  color: #4a6357;
  margin-top: 12px;
}

@media (max-width: 1024px) {
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .roles-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.role-action--danger { color: #c0392b !important; }
.modal-text { font-size: 13px; color: #1a2e24; margin-bottom: 6px; }
.detail-grid { display: flex; flex-direction: column; gap: 8px; }
.detail-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px solid rgba(0,0,0,0.05); font-size: 13px; }
.detail-label { color: #7a9489; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; min-width: 90px; }
.detail-val { color: #1a2e24; font-weight: 500; text-align: right; }
.modal-btn { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; }
.modal-btn--cancel { background: #f5f5f5; color: #4a6357; }
.modal-btn--primary { background: #4caf76; color: white; }
.modal-btn--danger { background: #c0392b; color: white; }
</style>
