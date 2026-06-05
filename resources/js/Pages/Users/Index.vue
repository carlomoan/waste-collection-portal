<template>
  <AppLayout title="Users">
    <div class="users-container">
      <div class="header">
        <h1>User Management</h1>
        <p>Manage user accounts, roles and access permissions</p>
      </div>

      <!-- Summary Cards -->
      <div class="summary-grid">
        <div class="summary-card">
          <div class="summary-label">Total Users</div>
          <div class="summary-value">{{ stats.total }}</div>
          <div class="summary-change summary-change--neutral">All accounts</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Active</div>
          <div class="summary-value">{{ stats.active }}</div>
          <div class="summary-change summary-change--positive">Can sign in</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Inactive</div>
          <div class="summary-value">{{ stats.inactive }}</div>
          <div class="summary-change summary-change--negative">Access disabled</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Roles</div>
          <div class="summary-value">{{ stats.roles }}</div>
          <div class="summary-change summary-change--neutral">Configured</div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="actions-bar">
        <button class="action-btn action-btn--primary" @click="openCreate">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          Add User
        </button>
        <select v-model="roleFilter" class="filter-select">
          <option value="">All Roles</option>
          <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
        </select>
        <select v-model="statusFilter" class="filter-select">
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
        <div class="search-box">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
          </svg>
          <input type="text" v-model="search" placeholder="Search users..." class="search-input">
        </div>
      </div>

      <!-- Users Table -->
      <div class="users-section">
        <div class="section-header">
          <h3>All Users</h3>
          <span class="result-count">{{ users.total }} total</span>
        </div>
        <div class="table-wrap">
          <table class="users-table">
            <thead>
              <tr>
                <th>User</th>
                <th>Username</th>
                <th>Phone</th>
                <th>Roles</th>
                <th>Status</th>
                <th>Last Login</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users.data" :key="user.id">
                <td>
                  <div class="user-info">
                    <div class="user-avatar" :style="{ background: avatarColor(user.name) }">{{ initials(user.name) }}</div>
                    <div class="user-details">
                      <div class="user-name">{{ user.name }}</div>
                      <div class="user-email">{{ user.email }}</div>
                    </div>
                  </div>
                </td>
                <td>{{ user.username || '—' }}</td>
                <td>{{ user.phone || '—' }}</td>
                <td>
                  <div class="role-tags">
                    <span v-for="role in user.roles" :key="role.id" class="role-tag">{{ role.name }}</span>
                    <span v-if="user.roles.length === 0" class="role-tag role-tag--none">No role</span>
                  </div>
                </td>
                <td>
                  <span class="status-badge" :class="user.is_active ? 'status-badge--active' : 'status-badge--inactive'">
                    {{ user.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td>{{ user.last_login_at ? formatDate(user.last_login_at) : 'Never' }}</td>
                <td class="td-actions">
                  <button class="table-action" @click="openEdit(user)">Edit</button>
                  <button class="table-action" @click="openRoles(user)">Roles</button>
                  <button class="table-action" @click="openReset(user)">Password</button>
                  <button class="table-action" @click="toggleStatus(user)">{{ user.is_active ? 'Disable' : 'Enable' }}</button>
                  <button class="table-action table-action--danger" @click="openDelete(user)">Delete</button>
                </td>
              </tr>
              <tr v-if="users.data.length === 0">
                <td colspan="7" class="empty-row">No users found</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="users.last_page > 1" class="pagination">
          <button
            v-for="link in users.links"
            :key="link.label"
            class="page-btn"
            :class="{ 'page-btn--active': link.active, 'page-btn--disabled': !link.url }"
            :disabled="!link.url"
            @click="goTo(link.url)"
            v-html="link.label"
          />
        </div>
      </div>
    </div>

    <!-- Create User Modal -->
    <Modal :show="showCreate" title="Add User" @close="showCreate = false">
      <form @submit.prevent="submitCreate">
        <div class="form-grid">
          <div class="form-group">
            <label>Full Name *</label>
            <input v-model="createForm.name" type="text" class="form-input" required>
            <span v-if="createForm.errors.name" class="form-error">{{ createForm.errors.name }}</span>
          </div>
          <div class="form-group">
            <label>Email *</label>
            <input v-model="createForm.email" type="email" class="form-input" required>
            <span v-if="createForm.errors.email" class="form-error">{{ createForm.errors.email }}</span>
          </div>
          <div class="form-group">
            <label>Username</label>
            <input v-model="createForm.username" type="text" class="form-input">
            <span v-if="createForm.errors.username" class="form-error">{{ createForm.errors.username }}</span>
          </div>
          <div class="form-group">
            <label>National ID (NIDA)</label>
            <input v-model="createForm.nida_id" type="text" class="form-input">
            <span v-if="createForm.errors.nida_id" class="form-error">{{ createForm.errors.nida_id }}</span>
          </div>
          <div class="form-group">
            <label>Phone</label>
            <input v-model="createForm.phone" type="text" class="form-input">
            <span v-if="createForm.errors.phone" class="form-error">{{ createForm.errors.phone }}</span>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select v-model="createForm.is_active" class="form-input">
              <option :value="true">Active</option>
              <option :value="false">Inactive</option>
            </select>
          </div>
          <div class="form-group">
            <label>Password *</label>
            <input v-model="createForm.password" type="password" class="form-input" required>
            <span v-if="createForm.errors.password" class="form-error">{{ createForm.errors.password }}</span>
          </div>
          <div class="form-group">
            <label>Confirm Password *</label>
            <input v-model="createForm.password_confirmation" type="password" class="form-input" required>
          </div>
        </div>
        <div class="form-group">
          <label>Roles</label>
          <div class="role-checkboxes">
            <label v-for="role in roles" :key="role.id" class="role-checkbox">
              <input type="checkbox" :value="role.id" v-model="createForm.roles">
              <span>{{ role.name }}</span>
            </label>
          </div>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showCreate = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" :disabled="createForm.processing" @click="submitCreate">
          {{ createForm.processing ? 'Saving...' : 'Create User' }}
        </button>
      </template>
    </Modal>

    <!-- Edit User Modal -->
    <Modal :show="showEdit" title="Edit User" @close="showEdit = false">
      <form v-if="editForm" @submit.prevent="submitEdit">
        <div class="form-grid">
          <div class="form-group">
            <label>Full Name *</label>
            <input v-model="editForm.name" type="text" class="form-input" required>
            <span v-if="editForm.errors.name" class="form-error">{{ editForm.errors.name }}</span>
          </div>
          <div class="form-group">
            <label>Email *</label>
            <input v-model="editForm.email" type="email" class="form-input" required>
            <span v-if="editForm.errors.email" class="form-error">{{ editForm.errors.email }}</span>
          </div>
          <div class="form-group">
            <label>Username</label>
            <input v-model="editForm.username" type="text" class="form-input">
            <span v-if="editForm.errors.username" class="form-error">{{ editForm.errors.username }}</span>
          </div>
          <div class="form-group">
            <label>National ID (NIDA)</label>
            <input v-model="editForm.nida_id" type="text" class="form-input">
            <span v-if="editForm.errors.nida_id" class="form-error">{{ editForm.errors.nida_id }}</span>
          </div>
          <div class="form-group">
            <label>Phone</label>
            <input v-model="editForm.phone" type="text" class="form-input">
          </div>
          <div class="form-group">
            <label>Status</label>
            <select v-model="editForm.is_active" class="form-input">
              <option :value="true">Active</option>
              <option :value="false">Inactive</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Roles</label>
          <div class="role-checkboxes">
            <label v-for="role in roles" :key="role.id" class="role-checkbox">
              <input type="checkbox" :value="role.id" v-model="editForm.roles">
              <span>{{ role.name }}</span>
            </label>
          </div>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showEdit = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" :disabled="editForm?.processing" @click="submitEdit">
          {{ editForm?.processing ? 'Saving...' : 'Save Changes' }}
        </button>
      </template>
    </Modal>

    <!-- Manage Roles Modal -->
    <Modal :show="showRoles" title="Manage Roles" @close="showRoles = false">
      <div v-if="rolesForm && activeUser">
        <p class="modal-text">Assign roles to <strong>{{ activeUser.name }}</strong>.</p>
        <div class="role-checkboxes role-checkboxes--stacked">
          <label v-for="role in roles" :key="role.id" class="role-checkbox role-checkbox--card">
            <input type="checkbox" :value="role.id" v-model="rolesForm.roles">
            <span>
              <span class="role-checkbox-name">{{ role.name }}</span>
              <span class="role-checkbox-desc">{{ role.description || 'No description' }}</span>
            </span>
          </label>
        </div>
      </div>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showRoles = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" :disabled="rolesForm?.processing" @click="submitRoles">Save Roles</button>
      </template>
    </Modal>

    <!-- Reset Password Modal -->
    <Modal :show="showReset" title="Reset Password" @close="showReset = false">
      <div v-if="passwordForm && activeUser">
        <p class="modal-text">Set a new password for <strong>{{ activeUser.name }}</strong>.</p>
        <div class="form-group">
          <label>New Password *</label>
          <input v-model="passwordForm.password" type="password" class="form-input" required>
          <span v-if="passwordForm.errors.password" class="form-error">{{ passwordForm.errors.password }}</span>
        </div>
        <div class="form-group">
          <label>Confirm Password *</label>
          <input v-model="passwordForm.password_confirmation" type="password" class="form-input" required>
        </div>
      </div>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showReset = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" :disabled="passwordForm?.processing" @click="submitReset">Reset Password</button>
      </template>
    </Modal>

    <!-- Delete Modal -->
    <Modal :show="showDelete" title="Delete User" @close="showDelete = false">
      <p class="modal-text" v-if="activeUser">
        Are you sure you want to delete <strong>{{ activeUser.name }}</strong>? This action cannot be undone.
      </p>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showDelete = false">Cancel</button>
        <button class="modal-btn modal-btn--danger" @click="submitDelete">Delete</button>
      </template>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  users: { type: Object, default: () => ({ data: [], links: [], total: 0, last_page: 1 }) },
  filters: { type: Object, default: () => ({}) },
  roles: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({ total: 0, active: 0, inactive: 0, roles: 0 }) },
})

const search = ref(props.filters.search ?? '')
const roleFilter = ref(props.filters.role ?? '')
const statusFilter = ref(props.filters.status ?? '')

let searchTimeout = null
watch([search, roleFilter, statusFilter], () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get('/users', {
      search: search.value || undefined,
      role: roleFilter.value || undefined,
      status: statusFilter.value || undefined,
    }, { preserveState: true, replace: true, preserveScroll: true })
  }, 300)
})

const goTo = (url) => {
  if (url) {
    router.get(url, {}, { preserveState: true, preserveScroll: true })
  }
}

// Modals
const showCreate = ref(false)
const showEdit = ref(false)
const showRoles = ref(false)
const showReset = ref(false)
const showDelete = ref(false)
const activeUser = ref(null)
const editForm = ref(null)
const rolesForm = ref(null)
const passwordForm = ref(null)

const createForm = useForm({
  name: '', email: '', username: '', nida_id: '', phone: '',
  password: '', password_confirmation: '', is_active: true, roles: [],
})

const openCreate = () => {
  createForm.reset()
  createForm.clearErrors()
  createForm.is_active = true
  showCreate.value = true
}

const submitCreate = () => {
  createForm.post('/users', {
    preserveScroll: true,
    onSuccess: () => { showCreate.value = false; createForm.reset() },
  })
}

const openEdit = (user) => {
  activeUser.value = user
  editForm.value = useForm({
    name: user.name ?? '',
    email: user.email ?? '',
    username: user.username ?? '',
    nida_id: user.nida_id ?? '',
    phone: user.phone ?? '',
    is_active: user.is_active,
    roles: user.roles.map((r) => r.id),
  })
  showEdit.value = true
}

const submitEdit = () => {
  editForm.value.patch(`/users/${activeUser.value.id}`, {
    preserveScroll: true,
    onSuccess: () => { showEdit.value = false },
  })
}

const openRoles = (user) => {
  activeUser.value = user
  rolesForm.value = useForm({ roles: user.roles.map((r) => r.id) })
  showRoles.value = true
}

const submitRoles = () => {
  rolesForm.value.patch(`/users/${activeUser.value.id}/roles`, {
    preserveScroll: true,
    onSuccess: () => { showRoles.value = false },
  })
}

const openReset = (user) => {
  activeUser.value = user
  passwordForm.value = useForm({ password: '', password_confirmation: '' })
  showReset.value = true
}

const submitReset = () => {
  passwordForm.value.patch(`/users/${activeUser.value.id}/reset-password`, {
    preserveScroll: true,
    onSuccess: () => { showReset.value = false },
  })
}

const openDelete = (user) => {
  activeUser.value = user
  showDelete.value = true
}

const submitDelete = () => {
  router.delete(`/users/${activeUser.value.id}`, {
    preserveScroll: true,
    onSuccess: () => { showDelete.value = false },
  })
}

const toggleStatus = (user) => {
  router.patch(`/users/${user.id}/toggle-status`, {}, { preserveScroll: true })
}

// Helpers
const initials = (name) => (name || '?').split(' ').map((n) => n[0]).join('').substring(0, 2).toUpperCase()

const avatarColors = ['#e91e63', '#2196f3', '#ff9800', '#9c27b0', '#4caf50', '#009688', '#3f51b5']
const avatarColor = (name) => {
  const str = name || ''
  let hash = 0
  for (let i = 0; i < str.length; i++) { hash = str.charCodeAt(i) + ((hash << 5) - hash) }
  return avatarColors[Math.abs(hash) % avatarColors.length]
}

const formatDate = (value) => {
  const d = new Date(value)
  return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<style scoped>
.users-container { padding: 20px; }

.header { margin-bottom: 24px; }
.header h1 { font-size: 24px; font-weight: 600; color: #1a2e24; margin-bottom: 4px; }
.header p { color: #4a6357; font-size: 14px; }

.summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.summary-card { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 20px; }
.summary-label { font-size: 12px; color: #4a6357; margin-bottom: 8px; }
.summary-value { font-size: 24px; font-weight: 600; color: #1a2e24; margin-bottom: 8px; }
.summary-change { font-size: 11px; }
.summary-change--positive { color: #2d7a50; }
.summary-change--negative { color: #c0392b; }
.summary-change--neutral { color: #4a6357; }

.actions-bar { display: flex; gap: 12px; margin-bottom: 24px; align-items: center; }

.action-btn { padding: 10px 20px; border: 1px solid rgba(0,0,0,0.08); border-radius: 8px; background: white; color: #4a6357; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.15s; }
.action-btn:hover { border-color: #4caf76; color: #2d7a50; }
.action-btn--primary { background: #4caf76; color: white; border-color: #4caf76; }
.action-btn--primary:hover { background: #2d7a50; border-color: #2d7a50; }

.filter-select { padding: 9px 12px; border: 1px solid rgba(0,0,0,0.08); border-radius: 8px; font-size: 13px; color: #4a6357; background: white; }

.search-box { display: flex; align-items: center; gap: 8px; padding: 9px 16px; background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 8px; flex: 1; max-width: 300px; margin-left: auto; }
.search-box svg { color: #4a6357; }
.search-input { border: none; outline: none; font-size: 13px; color: #1a2e24; flex: 1; }
.search-input::placeholder { color: #9ca3af; }

.users-section { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 20px; margin-bottom: 24px; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.section-header h3 { font-size: 16px; font-weight: 600; color: #1a2e24; }
.result-count { font-size: 12px; color: #4a6357; }

.table-wrap { overflow-x: auto; }
.users-table { width: 100%; border-collapse: collapse; }
.users-table th { text-align: left; padding: 12px; font-size: 12px; font-weight: 600; color: #4a6357; border-bottom: 1px solid rgba(0,0,0,0.08); white-space: nowrap; }
.users-table td { padding: 12px; font-size: 13px; color: #1a2e24; border-bottom: 1px solid rgba(0,0,0,0.04); }
.users-table tr:last-child td { border-bottom: none; }
.empty-row { text-align: center; color: #4a6357; padding: 40px !important; }

.user-info { display: flex; align-items: center; gap: 12px; }
.user-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; color: white; flex-shrink: 0; }
.user-details { display: flex; flex-direction: column; }
.user-name { font-weight: 500; color: #1a2e24; }
.user-email { font-size: 11px; color: #4a6357; }

.role-tags { display: flex; flex-wrap: wrap; gap: 4px; }
.role-tag { padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 500; background: #e8f5e9; color: #2d7a50; }
.role-tag--none { background: #f5f5f5; color: #9ca3af; }

.status-badge { padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 500; }
.status-badge--active { background: #e8f5e9; color: #2d7a50; }
.status-badge--inactive { background: #f5f5f5; color: #757575; }

.table-action { padding: 4px 12px; background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 4px; font-size: 11px; color: #4a6357; cursor: pointer; transition: all 0.15s; margin-right: 4px; }
.table-action:hover { border-color: #4caf76; color: #2d7a50; }
.table-action--danger { color: #c62828; }
.table-action--danger:hover { border-color: #c62828; color: #b71c1c; }
.td-actions { white-space: nowrap; }

.pagination { display: flex; gap: 6px; justify-content: flex-end; margin-top: 16px; flex-wrap: wrap; }
.page-btn { min-width: 34px; padding: 6px 10px; border: 1px solid rgba(0,0,0,0.08); border-radius: 6px; background: white; color: #4a6357; font-size: 12px; cursor: pointer; }
.page-btn:hover:not(.page-btn--disabled) { border-color: #4caf76; color: #2d7a50; }
.page-btn--active { background: #4caf76; color: white; border-color: #4caf76; }
.page-btn--disabled { opacity: 0.5; cursor: not-allowed; }

.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
.form-group { margin-bottom: 14px; }
.form-group label { display: block; margin-bottom: 5px; font-size: 12px; font-weight: 500; color: #1a2e24; }
.form-input { width: 100%; padding: 8px 10px; border: 1px solid rgba(0,0,0,0.12); border-radius: 6px; font-size: 13px; color: #1a2e24; background: white; box-sizing: border-box; }
.form-input:focus { outline: none; border-color: #4caf76; }
.form-error { display: block; font-size: 11px; color: #c0392b; margin-top: 4px; }

.role-checkboxes { display: flex; flex-wrap: wrap; gap: 10px; }
.role-checkboxes--stacked { flex-direction: column; }
.role-checkbox { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #1a2e24; cursor: pointer; }
.role-checkbox--card { align-items: flex-start; gap: 10px; padding: 10px 12px; border: 1px solid rgba(0,0,0,0.08); border-radius: 8px; width: 100%; box-sizing: border-box; }
.role-checkbox-name { display: block; font-weight: 500; }
.role-checkbox-desc { display: block; font-size: 11px; color: #7a9489; margin-top: 2px; }

.modal-btn { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; }
.modal-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.modal-btn--cancel { background: #f5f5f5; color: #4a6357; }
.modal-btn--primary { background: #4caf76; color: white; }
.modal-btn--primary:hover { background: #2d7a50; }
.modal-btn--danger { background: #c0392b; color: white; }
.modal-text { font-size: 13px; color: #1a2e24; margin-bottom: 14px; }

@media (max-width: 1024px) {
  .summary-grid { grid-template-columns: repeat(2, 1fr); }
  .form-grid { grid-template-columns: 1fr; }
}
</style>
