<template>
  <AppLayout title="Zones">
    <div class="zones-container">
      <div class="header">
        <h1>Zones</h1>
        <div class="header-actions">
          <button class="action-btn action-btn--primary" @click="showAddModal = true">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Zone
          </button>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="summary-cards">
        <div class="summary-card">
          <div class="summary-label">Total Zones</div>
          <div class="summary-value">{{ zones.length }}</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Total Staff</div>
          <div class="summary-value">{{ totalStaff }}</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Total Clients</div>
          <div class="summary-value">{{ totalClients }}</div>
        </div>
      </div>

      <!-- Zones Grid -->
      <div class="zones-grid">
        <div v-for="zone in zones" :key="zone.id" class="zone-card">
          <div class="zone-header" :style="{ borderTopColor: zone.color || '#4caf76' }">
            <div class="zone-info">
              <h3 class="zone-name">{{ zone.name }}</h3>
              <span class="zone-code">{{ zone.code || 'N/A' }}</span>
            </div>
            <div class="zone-actions">
              <button class="zone-action-btn" @click="editZone(zone)" title="Edit">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                </svg>
              </button>
              <button class="zone-action-btn zone-action-btn--danger" @click="confirmDelete(zone)" title="Delete">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                </svg>
              </button>
            </div>
          </div>
          <div class="zone-body">
            <div class="zone-stat">
              <span class="zone-stat-label">Staff</span>
              <span class="zone-stat-value">{{ zone.staff_count || 0 }}</span>
            </div>
            <div class="zone-stat">
              <span class="zone-stat-label">Clients</span>
              <span class="zone-stat-value">{{ zone.clients_count || 0 }}</span>
            </div>
            <div v-if="zone.description" class="zone-description">
              {{ zone.description }}
            </div>
          </div>
        </div>
        <div v-if="zones.length === 0" class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="48" height="48">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
          </svg>
          <p>No zones found</p>
          <button class="add-first-btn" @click="showAddModal = true">Add your first zone</button>
        </div>
      </div>
    </div>

    <!-- Add/Edit Zone Modal -->
    <Modal :show="showAddModal" @close="closeModal" :title="editingZone ? 'Edit Zone' : 'Add Zone'">
      <form @submit.prevent="saveZone">
        <div class="form-group">
          <label>Zone Name</label>
          <input type="text" v-model="form.name" class="form-input" required>
          <span v-if="form.errors.name" class="error-text">{{ form.errors.name }}</span>
        </div>
        <div class="form-group">
          <label>Zone Code</label>
          <input type="text" v-model="form.code" class="form-input">
          <span v-if="form.errors.code" class="error-text">{{ form.errors.code }}</span>
        </div>
        <div class="form-group">
          <label>Color</label>
          <input type="color" v-model="form.color" class="color-input">
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea v-model="form.description" class="form-input" rows="3"></textarea>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="closeModal">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="saveZone" :disabled="form.processing">
          {{ form.processing ? 'Saving...' : 'Save Zone' }}
        </button>
      </template>
    </Modal>

    <!-- Delete Confirmation Modal -->
    <Modal :show="showDeleteModal" @close="showDeleteModal = false" title="Delete Zone">
      <p class="modal-text">Are you sure you want to delete <strong>{{ zoneToDelete?.name }}</strong>? This action cannot be undone.</p>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showDeleteModal = false">Cancel</button>
        <button class="modal-btn modal-btn--danger" @click="deleteZone" :disabled="deleteForm.processing">
          {{ deleteForm.processing ? 'Deleting...' : 'Delete Zone' }}
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
  zones: { type: Array, default: () => [] },
})

const showAddModal = ref(false)
const showDeleteModal = ref(false)
const editingZone = ref(null)
const zoneToDelete = ref(null)

const form = useForm({
  name: '',
  code: '',
  color: '#4caf76',
  description: '',
})

const deleteForm = useForm({})

const totalStaff = computed(() => props.zones.reduce((sum, z) => sum + (z.staff_count || 0), 0))
const totalClients = computed(() => props.zones.reduce((sum, z) => sum + (z.clients_count || 0), 0))

const editZone = (zone) => {
  editingZone.value = zone
  form.name = zone.name
  form.code = zone.code || ''
  form.color = zone.color || '#4caf76'
  form.description = zone.description || ''
  showAddModal.value = true
}

const saveZone = () => {
  if (editingZone.value) {
    form.put(`/zones/${editingZone.value.id}`, {
      onSuccess: () => closeModal()
    })
  } else {
    form.post('/zones', {
      onSuccess: () => closeModal()
    })
  }
}

const confirmDelete = (zone) => {
  zoneToDelete.value = zone
  showDeleteModal.value = true
}

const deleteZone = () => {
  deleteForm.delete(`/zones/${zoneToDelete.value.id}`, {
    onSuccess: () => {
      showDeleteModal.value = false
      zoneToDelete.value = null
    }
  })
}

const closeModal = () => {
  showAddModal.value = false
  editingZone.value = null
  form.reset()
  form.color = '#4caf76'
}
</script>

<style scoped>
.zones-container {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.header h1 {
  font-size: 24px;
  font-weight: 600;
  color: #1a2e24;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  padding: 8px 16px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  background: white;
  color: #4a6357;
  transition: background 0.15s;
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

.summary-cards {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
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
  font-size: 28px;
  font-weight: 600;
  color: #1a2e24;
}

.zones-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

.zone-card {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  overflow: hidden;
}

.zone-header {
  padding: 16px;
  border-top: 3px solid;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8faf9;
}

.zone-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.zone-name {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
  margin: 0;
}

.zone-code {
  font-size: 11px;
  color: #7a9489;
}

.zone-actions {
  display: flex;
  gap: 4px;
}

.zone-action-btn {
  padding: 6px;
  background: white;
  color: #4a6357;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.15s;
}

.zone-action-btn:hover {
  background: #f0faf3;
  border-color: #4caf76;
}

.zone-action-btn--danger:hover {
  background: #fee2e2;
  border-color: #fca5a5;
  color: #991b1b;
}

.zone-body {
  padding: 16px;
}

.zone-stat {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.zone-stat:last-child {
  border-bottom: none;
}

.zone-stat-label {
  font-size: 12px;
  color: #4a6357;
}

.zone-stat-value {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
}

.zone-description {
  margin-top: 12px;
  font-size: 12px;
  color: #7a9489;
  line-height: 1.5;
}

.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 60px 20px;
  color: #7a9489;
  background: white;
  border: 1px dashed rgba(0,0,0,0.08);
  border-radius: 10px;
}

.empty-state svg {
  color: #d1d5db;
  margin-bottom: 16px;
}

.empty-state p {
  font-size: 14px;
  margin-bottom: 16px;
}

.add-first-btn {
  padding: 8px 16px;
  background: #4caf76;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.15s;
}

.add-first-btn:hover {
  background: #2d7a50;
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

.color-input {
  width: 60px;
  height: 40px;
  padding: 2px;
  cursor: pointer;
}

.error-text {
  color: #c0392b;
  font-size: 12px;
  margin-top: 4px;
  display: block;
}

.modal-text {
  color: #4a6357;
  font-size: 14px;
  margin-bottom: 16px;
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

@media (max-width: 768px) {
  .summary-cards {
    grid-template-columns: 1fr;
  }
  .zones-grid {
    grid-template-columns: 1fr;
  }
}
</style>
