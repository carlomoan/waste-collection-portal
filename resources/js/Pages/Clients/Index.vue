<template>
  <AppLayout title="Client Registry">
    <div class="page-actions">
      <button class="btn-secondary" @click="exportClients">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
        </svg>
        Export
      </button>
      <button class="btn-primary" @click="showAddModal = true">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Add Client
      </button>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <input v-model="search" class="search-input" placeholder="Search name, phone, client number…" />
      <select v-model="filterZone" class="filter-select">
        <option value="">All Zones</option>
        <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }}</option>
      </select>
      <select v-model="filterType" class="filter-select">
        <option value="">All Types</option>
        <option value="residential">Residential</option>
        <option value="commercial">Commercial</option>
      </select>
      <select v-model="filterStatus" class="filter-select">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
        <option value="suspended">Suspended</option>
      </select>
    </div>

    <!-- Stats strip -->
    <div class="summary-strip">
      <div class="strip-item">
        <span class="strip-label">Total Clients</span>
        <span class="strip-val">{{ filteredClients.length }}</span>
      </div>
      <div class="strip-item">
        <span class="strip-label">Active</span>
        <span class="strip-val green">{{ activeCount }}</span>
      </div>
      <div class="strip-item">
        <span class="strip-label">With Debt</span>
        <span class="strip-val red">{{ debtCount }}</span>
      </div>
      <div class="strip-item">
        <span class="strip-label">Monthly Revenue</span>
        <span class="strip-val">{{ formatTZS(monthlyRevenue) }}</span>
      </div>
    </div>

    <!-- Table -->
    <div class="card">
      <table class="clients-table">
        <thead>
          <tr>
            <th>Client #</th>
            <th>Name</th>
            <th>Zone</th>
            <th>Type</th>
            <th>Monthly Fee</th>
            <th>Outstanding</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="client in paginatedClients" :key="client.id">
            <td class="td-num">{{ client.client_number }}</td>
            <td>
              <div class="client-cell">
                <div class="client-av">{{ initials(client.name) }}</div>
                <div>
                  <div class="client-name">{{ client.name }}</div>
                  <div class="client-phone">{{ client.phone ?? '—' }}</div>
                </div>
              </div>
            </td>
            <td>
              <span class="zone-badge" :style="{ background: (client.zone_color || '#4caf76') + '22', color: client.zone_color || '#2d7a50' }">
                {{ client.zone_name || '—' }}
              </span>
            </td>
            <td class="td-type">{{ client.client_type || '—' }}</td>
            <td class="td-fee">{{ formatTZS(client.monthly_fee) }}</td>
            <td>
              <span v-if="client.outstanding > 0" class="debt-amount">{{ formatTZS(client.outstanding) }}</span>
              <span v-else class="no-debt">—</span>
            </td>
            <td>
              <span class="status-badge" :class="`status--${client.status}`">{{ client.status }}</span>
            </td>
            <td class="td-actions">
              <button class="action-link" @click="openViewClient(client)">View</button>
              <button class="action-link" @click="openEditClient(client)">Edit</button>
              <button class="action-link" @click="showAddContactModal(client.id)">Contact</button>
              <button class="action-link danger-link" @click="openDeleteClient(client)">Delete</button>
            </td>
          </tr>
          <tr v-if="filteredClients.length === 0">
            <td colspan="8" class="empty-row">No clients found.</td>
          </tr>
        </tbody>
      </table>
      <div class="pagination">
        <button class="page-btn" :disabled="currentPage === 1" @click="currentPage--">← Prev</button>
        <span class="page-info">Page {{ currentPage }} of {{ totalPages }}</span>
        <button class="page-btn" :disabled="currentPage === totalPages" @click="currentPage++">Next →</button>
      </div>
    </div>

    <!-- View Client Modal -->
    <Modal :show="showViewModal" title="Client Details" @close="showViewModal = false">
      <div v-if="viewingClient" class="detail-grid">
        <div class="detail-row"><span class="detail-label">Client #</span><span class="detail-val mono">{{ viewingClient.client_number }}</span></div>
        <div class="detail-row"><span class="detail-label">Name</span><span class="detail-val">{{ viewingClient.name }}</span></div>
        <div class="detail-row"><span class="detail-label">Phone</span><span class="detail-val">{{ viewingClient.phone ?? '—' }}</span></div>
        <div class="detail-row"><span class="detail-label">Email</span><span class="detail-val">{{ viewingClient.email ?? '—' }}</span></div>
        <div class="detail-row"><span class="detail-label">Zone</span><span class="detail-val">{{ viewingClient.zone_name ?? '—' }}</span></div>
        <div class="detail-row"><span class="detail-label">Monthly Fee</span><span class="detail-val">{{ formatTZS(viewingClient.monthly_fee) }} TZS</span></div>
        <div class="detail-row"><span class="detail-label">Address</span><span class="detail-val">{{ viewingClient.address ?? '—' }}</span></div>
        <div class="detail-row"><span class="detail-label">Status</span>
          <span class="status-badge" :class="`status--${viewingClient.status}`">{{ viewingClient.status }}</span>
        </div>
      </div>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showViewModal = false">Close</button>
        <button class="modal-btn modal-btn--primary" @click="openEditClient(viewingClient); showViewModal = false">Edit</button>
      </template>
    </Modal>

    <!-- Edit Client Modal -->
    <Modal :show="showEditModal" title="Edit Client" @close="showEditModal = false">
      <form v-if="clientEditForm" @submit.prevent="submitClientEdit">
        <div class="form-group"><label>Name</label><input type="text" v-model="clientEditForm.name" class="form-input" required /></div>
        <div class="form-group"><label>Phone</label><input type="text" v-model="clientEditForm.phone" class="form-input" /></div>
        <div class="form-group"><label>Email</label><input type="email" v-model="clientEditForm.email" class="form-input" /></div>
        <div class="form-group"><label>Address</label><input type="text" v-model="clientEditForm.address" class="form-input" /></div>
        <div class="form-group">
          <label>Zone</label>
          <select v-model="clientEditForm.zone_id" class="form-input">
            <option value="">No Zone</option>
            <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }}</option>
          </select>
        </div>
        <div class="form-group"><label>Monthly Fee (TZS)</label><input type="number" v-model="clientEditForm.monthly_fee" class="form-input" required /></div>
        <div class="form-group">
          <label>Status</label>
          <select v-model="clientEditForm.status" class="form-input">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
          </select>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showEditModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="submitClientEdit" :disabled="clientEditForm?.processing">
          {{ clientEditForm?.processing ? 'Saving...' : 'Save Changes' }}
        </button>
      </template>
    </Modal>

    <!-- Delete Client Modal -->
    <Modal :show="showDeleteModal" title="Delete Client" @close="showDeleteModal = false">
      <p class="modal-text">Delete client <strong>{{ deletingClient?.name }}</strong> [{{ deletingClient?.client_number }}]? This cannot be undone.</p>
      <p class="modal-text" style="color:#c0392b;font-size:11px;">Note: clients with financial records cannot be deleted.</p>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showDeleteModal = false">Cancel</button>
        <button class="modal-btn modal-btn--danger" @click="submitClientDelete" :disabled="clientDeleteForm?.processing">
          {{ clientDeleteForm?.processing ? 'Deleting...' : 'Delete' }}
        </button>
      </template>
    </Modal>

    <!-- Add Client Modal -->
    <Modal :show="showAddModal" @close="showAddModal = false" title="Add Client">
      <form @submit.prevent="addClient">
        <div class="form-group"><label>Name</label><input type="text" v-model="addForm.name" class="form-input" required /></div>
        <div class="form-group"><label>Phone</label><input type="text" v-model="addForm.phone" class="form-input" /></div>
        <div class="form-group"><label>Email</label><input type="email" v-model="addForm.email" class="form-input" /></div>
        <div class="form-group"><label>Address</label><input type="text" v-model="addForm.address" class="form-input" /></div>
        <div class="form-group">
          <label>Zone</label>
          <select v-model="addForm.zone_id" class="form-input" required>
            <option value="">Select zone</option>
            <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>Client Type</label>
          <select v-model="addForm.client_type" class="form-input">
            <option value="residential">Residential</option>
            <option value="commercial">Commercial</option>
          </select>
        </div>
        <div class="form-group"><label>Monthly Fee (TZS)</label><input type="number" v-model="addForm.monthly_fee" class="form-input" required /></div>
        <div class="form-group">
          <label>Status</label>
          <select v-model="addForm.status" class="form-input">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
          </select>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showAddModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="addClient" :disabled="addForm.processing">
          {{ addForm.processing ? 'Adding...' : 'Add Client' }}
        </button>
      </template>
    </Modal>

    <!-- Add Contact Modal -->
    <Modal :show="showContactModal" @close="showContactModal = false" title="Add Contact">
      <form @submit.prevent="addContact">
        <div class="form-group"><label>Contact Name</label><input type="text" v-model="contactForm.name" class="form-input" required /></div>
        <div class="form-group"><label>Position (Optional)</label><input type="text" v-model="contactForm.position" class="form-input" /></div>
        <div class="form-group"><label>Phone</label><input type="text" v-model="contactForm.phone" class="form-input" required /></div>
        <div class="form-group"><label>Email (Optional)</label><input type="email" v-model="contactForm.email" class="form-input" /></div>
        <div class="form-group checkbox-group">
          <label><input type="checkbox" v-model="contactForm.is_primary" /> Set as Primary Contact</label>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showContactModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="addContact" :disabled="contactForm.processing">
          {{ contactForm.processing ? 'Adding...' : 'Add Contact' }}
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
  clients: { type: Array, default: () => [] },
  zones:   { type: Array, default: () => [] },
})

const showAddModal     = ref(false)
const showContactModal = ref(false)
const showViewModal    = ref(false)
const showEditModal    = ref(false)
const showDeleteModal  = ref(false)
const selectedClientId = ref(null)
const viewingClient    = ref(null)
const editingClient    = ref(null)
const deletingClient   = ref(null)
const clientEditForm   = ref(null)
const clientDeleteForm = ref(null)

const addForm = useForm({
  name: '', phone: '', email: '', address: '',
  zone_id: '', client_type: 'residential', monthly_fee: '', status: 'active',
})

const contactForm = useForm({
  name: '', position: '', phone: '', email: '', is_primary: false,
})

const search       = ref('')
const filterZone   = ref('')
const filterType   = ref('')
const filterStatus = ref('')
const currentPage  = ref(1)
const perPage      = 25

const filteredClients = computed(() => props.clients.filter(c => {
  const q = search.value.toLowerCase()
  return (!q || c.name.toLowerCase().includes(q) || (c.client_number || '').includes(q) || (c.phone ?? '').includes(q))
    && (!filterZone.value   || c.zone_id == filterZone.value)
    && (!filterType.value   || c.client_type === filterType.value)
    && (!filterStatus.value || c.status === filterStatus.value)
}))

const totalPages       = computed(() => Math.max(1, Math.ceil(filteredClients.value.length / perPage)))
const paginatedClients = computed(() => filteredClients.value.slice((currentPage.value - 1) * perPage, currentPage.value * perPage))
const activeCount      = computed(() => filteredClients.value.filter(c => c.status === 'active').length)
const debtCount        = computed(() => filteredClients.value.filter(c => (c.outstanding || 0) > 0).length)
const monthlyRevenue   = computed(() => filteredClients.value.filter(c => c.status === 'active').reduce((s, c) => s + (c.monthly_fee || 0), 0))

const addClient = () => {
  addForm.post('/clients', {
    onSuccess: () => { showAddModal.value = false; addForm.reset() },
  })
}

const showAddContactModal = (clientId) => {
  selectedClientId.value = clientId
  showContactModal.value = true
}

const addContact = () => {
  contactForm.post(`/clients/${selectedClientId.value}/contacts`, {
    onSuccess: () => { showContactModal.value = false; contactForm.reset(); selectedClientId.value = null },
  })
}

const openViewClient = (c) => { viewingClient.value = c; showViewModal.value = true }

const openEditClient = (c) => {
  editingClient.value = c
  clientEditForm.value = useForm({
    name: c.name ?? '', client_number: c.client_number ?? '',
    phone: c.phone ?? '', email: c.email ?? '', address: c.address ?? '',
    zone_id: c.zone_id ?? '', client_type_id: c.client_type_id ?? '',
    monthly_fee: c.monthly_fee ?? '', status: c.status ?? 'active',
  })
  showEditModal.value = true
}

const submitClientEdit = () => {
  clientEditForm.value.patch(`/clients/${editingClient.value.id}`, {
    onSuccess: () => { showEditModal.value = false; editingClient.value = null; router.reload() },
  })
}

const openDeleteClient = (c) => {
  deletingClient.value = c
  clientDeleteForm.value = useForm({})
  showDeleteModal.value = true
}

const submitClientDelete = () => {
  clientDeleteForm.value.delete(`/clients/${deletingClient.value.id}`, {
    onSuccess: () => { showDeleteModal.value = false; deletingClient.value = null; router.reload() },
  })
}

const exportClients = () => { window.location.href = '/clients/export' }

const formatTZS = v => new Intl.NumberFormat('sw-TZ', { minimumFractionDigits: 0 }).format(v || 0)
const initials  = n => n ? n.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() : '??'
</script>

<style scoped>
.page-actions { display: flex; justify-content: flex-end; margin-bottom: 12px; gap: 8px; }
.btn-primary { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: #2d7a50; color: #fff; border: none; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer; }
.btn-primary:hover { background: #1a4d32; }
.btn-secondary { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: #fff; color: #4a6357; border: 1px solid rgba(0,0,0,0.12); border-radius: 8px; font-size: 12px; cursor: pointer; }
.btn-secondary:hover { border-color: #4caf76; color: #2d7a50; }
.filters-bar { display: flex; gap: 10px; margin-bottom: 12px; flex-wrap: wrap; }
.search-input { flex: 1; min-width: 200px; padding: 7px 12px; border: 1px solid rgba(0,0,0,0.12); border-radius: 7px; font-size: 12px; background: #fff; }
.search-input:focus { outline: none; border-color: #4caf76; }
.filter-select { padding: 7px 10px; border: 1px solid rgba(0,0,0,0.12); border-radius: 7px; font-size: 12px; background: #fff; }
.summary-strip { display: flex; background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; overflow: hidden; margin-bottom: 14px; }
.strip-item { flex: 1; padding: 10px 16px; text-align: center; border-right: 1px solid rgba(0,0,0,0.06); }
.strip-item:last-child { border-right: none; }
.strip-label { display: block; font-size: 10px; color: #7a9489; text-transform: uppercase; letter-spacing: 0.6px; }
.strip-val { display: block; font-size: 15px; font-weight: 600; color: #1a2e24; margin-top: 2px; }
.strip-val.green { color: #2d7a50; }
.strip-val.red { color: #c0392b; }
.card { background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; overflow: hidden; }
.clients-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.clients-table th { text-align: left; padding: 10px 14px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; color: #7a9489; background: #f8faf9; border-bottom: 1px solid rgba(0,0,0,0.08); }
.clients-table td { padding: 10px 14px; border-bottom: 1px solid rgba(0,0,0,0.05); }
.clients-table tbody tr:hover { background: #f8faf9; }
.client-cell { display: flex; align-items: center; gap: 8px; }
.client-av { width: 28px; height: 28px; border-radius: 50%; background: #d6f0df; color: #2d7a50; font-size: 9px; font-weight: 600; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.client-name { font-size: 12px; font-weight: 500; color: #1a2e24; }
.client-phone { font-size: 10px; color: #7a9489; }
.zone-badge { font-size: 10px; padding: 2px 8px; border-radius: 10px; font-weight: 500; }
.td-type { font-size: 11px; color: #4a6357; text-transform: capitalize; }
.td-fee { font-weight: 600; color: #2d7a50; }
.debt-amount { font-size: 11px; color: #c0392b; font-weight: 600; }
.no-debt { color: #7a9489; }
.td-num { font-family: monospace; font-size: 11px; color: #4a6357; }
.td-actions { display: flex; gap: 8px; }
.action-link { font-size: 11px; color: #4caf76; cursor: pointer; background: none; border: none; padding: 0; }
.action-link:hover { text-decoration: underline; }
.danger-link { color: #c0392b !important; }
.status-badge { font-size: 9px; padding: 2px 7px; border-radius: 8px; font-weight: 600; text-transform: capitalize; }
.status--active { background: #f0faf3; color: #2d7a50; }
.status--inactive { background: #f5f5f5; color: #666; }
.status--suspended { background: #fef0f0; color: #c0392b; }
.empty-row { text-align: center; color: #7a9489; padding: 32px; }
.pagination { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 12px; border-top: 1px solid rgba(0,0,0,0.06); }
.page-btn { padding: 5px 12px; border: 1px solid rgba(0,0,0,0.12); border-radius: 6px; font-size: 11px; color: #4a6357; background: #fff; cursor: pointer; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-info { font-size: 11px; color: #7a9489; }
.modal-text { font-size: 13px; color: #1a2e24; margin-bottom: 6px; }
.detail-grid { display: flex; flex-direction: column; gap: 8px; }
.detail-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px solid rgba(0,0,0,0.05); font-size: 13px; }
.detail-label { color: #7a9489; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; min-width: 90px; }
.detail-val { color: #1a2e24; font-weight: 500; text-align: right; }
.detail-val.mono { font-family: monospace; }
.modal-btn { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; }
.modal-btn--cancel { background: #f5f5f5; color: #4a6357; }
.modal-btn--primary { background: #4caf76; color: white; }
.modal-btn--danger { background: #c0392b; color: white; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 500; color: #1a2e24; }
.form-input { width: 100%; padding: 8px 12px; border: 1px solid rgba(0,0,0,0.12); border-radius: 6px; font-size: 13px; color: #1a2e24; background: white; box-sizing: border-box; }
.form-input:focus { outline: none; border-color: #4caf76; }
.checkbox-group label { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.checkbox-group input[type="checkbox"] { width: auto; }
</style>
