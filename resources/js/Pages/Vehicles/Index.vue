<template>
  <AppLayout title="Vehicles">
    <div class="vehicles-container">
      <div class="header">
        <h1>Vehicles</h1>
        <p>Manage fleet vehicles</p>
      </div>

      <!-- Summary Cards -->
      <div class="summary-grid">
        <div class="summary-card">
          <div class="summary-label">Total Vehicles</div>
          <div class="summary-value">{{ vehicles.length }}</div>
          <div class="summary-change summary-change--neutral">All registered</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Active</div>
          <div class="summary-value">{{ activeVehiclesCount }}</div>
          <div class="summary-change summary-change--positive">Currently in operation</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">In Maintenance</div>
          <div class="summary-value">{{ maintenanceVehiclesCount }}</div>
          <div class="summary-change summary-change--neutral">Scheduled service</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Hired Vehicles</div>
          <div class="summary-value">{{ hiredVehiclesCount }}</div>
          <div class="summary-change summary-change--neutral">On hire agreement</div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="actions-bar">
        <button class="action-btn action-btn--primary" @click="showAddModal = true">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          Add Vehicle
        </button>
        <button class="action-btn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
          </svg>
          Log Maintenance
        </button>
        <button class="action-btn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export Report
        </button>
      </div>

      <!-- Vehicle List -->
      <div class="vehicles-section">
        <div class="section-header">
          <h3>Fleet Overview</h3>
          <div class="filter-actions">
            <select class="filter-select">
              <option>All Status</option>
              <option>Active</option>
              <option>In Maintenance</option>
              <option>Out of Service</option>
            </select>
            <button class="view-all-btn">View All</button>
          </div>
        </div>
        <div class="vehicles-grid">
          <div v-for="vehicle in vehicles" :key="vehicle.id" class="vehicle-card">
            <div class="vehicle-header">
              <div class="vehicle-icon vehicle-icon--truck">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                </svg>
              </div>
              <div class="vehicle-status" :class="`vehicle-status--${vehicle.status}`">{{ vehicle.status }}</div>
              <div v-if="vehicle.is_hired" class="vehicle-badge vehicle-badge--hired">Hired</div>
            </div>
            <div class="vehicle-name">{{ vehicle.type }}</div>
            <div class="vehicle-plate">{{ vehicle.plate_number }}</div>
            <div class="vehicle-details">
              <div class="vehicle-detail">
                <span class="detail-label">Driver:</span>
                <span class="detail-value">{{ vehicle.driver }}</span>
              </div>
              <div class="vehicle-detail">
                <span class="detail-label">Fuel Level:</span>
                <span class="detail-value">{{ vehicle.fuel_level }}%</span>
              </div>
              <div v-if="vehicle.is_hired" class="vehicle-detail">
                <span class="detail-label">Hire End:</span>
                <span class="detail-value">{{ formatDate(vehicle.hire_end_date) }}</span>
              </div>
              <div v-if="!vehicle.is_hired" class="vehicle-detail">
                <span class="detail-label">Last Service:</span>
                <span class="detail-value">{{ formatDate(vehicle.last_service) }}</span>
              </div>
            </div>
            <div class="vehicle-actions">
              <button class="vehicle-action">View</button>
              <button class="vehicle-action vehicle-action--delete" @click="confirmDelete(vehicle)">Delete</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Maintenance Schedule -->
      <div class="maintenance-section">
        <div class="section-header">
          <h3>Upcoming Maintenance</h3>
          <button class="action-btn action-btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Schedule Maintenance
          </button>
        </div>
        <table class="maintenance-table">
          <thead>
            <tr>
              <th>Vehicle</th>
              <th>Type</th>
              <th>Scheduled Date</th>
              <th>Description</th>
              <th>Estimated Cost</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>TRK-003</td>
              <td>Oil Change</td>
              <td>{{ formatDate('2026-05-28') }}</td>
              <td>Routine oil change and filter replacement</td>
              <td>{{ formatCurrency(85000) }}</td>
              <td><span class="status-badge status-badge--pending">Pending</span></td>
              <td><button class="table-action">Complete</button></td>
            </tr>
            <tr>
              <td>TRK-001</td>
              <td>Tire Service</td>
              <td>{{ formatDate('2026-06-05') }}</td>
              <td>Tire rotation and alignment check</td>
              <td>{{ formatCurrency(120000) }}</td>
              <td><span class="status-badge status-badge--scheduled">Scheduled</span></td>
              <td><button class="table-action">View</button></td>
            </tr>
            <tr>
              <td>PKP-002</td>
              <td>Brake Inspection</td>
              <td>{{ formatDate('2026-06-10') }}</td>
              <td>Brake pads and fluid check</td>
              <td>{{ formatCurrency(95000) }}</td>
              <td><span class="status-badge status-badge--scheduled">Scheduled</span></td>
              <td><button class="table-action">View</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Modal :show="showDeleteModal" @close="showDeleteModal = false" title="Delete Vehicle">
      <p>Are you sure you want to delete this vehicle? This action cannot be undone.</p>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showDeleteModal = false">Cancel</button>
        <button class="modal-btn modal-btn--danger" @click="deleteVehicle" :disabled="deleteForm.processing">
          {{ deleteForm.processing ? 'Deleting...' : 'Delete' }}
        </button>
      </template>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  vehicles: {
    type: Array,
    default: () => []
  },
  maintenanceSchedule: {
    type: Array,
    default: () => []
  }
})

const showAddModal = ref(false)
const showDeleteModal = ref(false)
const vehicleToDelete = ref(null)

const activeVehiclesCount = computed(() => {
  return props.vehicles.filter(v => v.status === 'active').length
})

const maintenanceVehiclesCount = computed(() => {
  return props.vehicles.filter(v => v.status === 'maintenance').length
})

const hiredVehiclesCount = computed(() => {
  return props.vehicles.filter(v => v.is_hired).length
})

const deleteForm = useForm({})

const confirmDelete = (vehicle) => {
  vehicleToDelete.value = vehicle
  showDeleteModal.value = true
}

const deleteVehicle = () => {
  if (vehicleToDelete.value) {
    deleteForm.delete(`/vehicles/${vehicleToDelete.value.id}`, {
      onSuccess: () => {
        showDeleteModal.value = false
        vehicleToDelete.value = null
      }
    })
  }
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('sw-TZ', {
    style: 'currency',
    currency: 'TZS',
    minimumFractionDigits: 0,
  }).format(value)
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-TZ', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
</script>

<style scoped>
.vehicles-container {
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

.vehicles-section {
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
  align-items: center;
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

.vehicles-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 16px;
}

.vehicle-card {
  background: #f9fafb;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 8px;
  padding: 16px;
  display: flex;
  flex-direction: column;
}

.vehicle-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.vehicle-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.vehicle-icon--truck {
  background: #ffe0b2;
  color: #e65100;
}

.vehicle-icon--pickup {
  background: #c8e6c9;
  color: #2d7a50;
}

.vehicle-status {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.vehicle-status--active {
  background: #e8f5e9;
  color: #2d7a50;
}

.vehicle-status--maintenance {
  background: #fff3e0;
  color: #e65100;
}

.vehicle-status--out {
  background: #ffebee;
  color: #c62828;
}

.vehicle-name {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 4px;
}

.vehicle-plate {
  font-size: 12px;
  color: #4a6357;
  margin-bottom: 12px;
  font-family: monospace;
}

.vehicle-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 12px;
  flex: 1;
}

.vehicle-detail {
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

.vehicle-actions {
  display: flex;
  gap: 8px;
}

.vehicle-action {
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

.vehicle-action:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.vehicle-action--delete {
  color: #c0392b;
}

.vehicle-action--delete:hover {
  border-color: #c0392b;
  color: #a93226;
}

.vehicle-badge {
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 10px;
  font-weight: 500;
}

.vehicle-badge--hired {
  background: #fff3e0;
  color: #e65100;
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

.modal-btn--danger {
  background: #c0392b;
  border: 1px solid #c0392b;
  color: white;
}

.modal-btn--danger:hover {
  background: #a93226;
  border-color: #a93226;
}

.modal-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.maintenance-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.maintenance-table {
  width: 100%;
  border-collapse: collapse;
}

.maintenance-table th {
  text-align: left;
  padding: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #4a6357;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.maintenance-table td {
  padding: 12px;
  font-size: 13px;
  color: #1a2e24;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.maintenance-table tr:last-child td {
  border-bottom: none;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge--pending {
  background: #fff3e0;
  color: #e65100;
}

.status-badge--scheduled {
  background: #e3f2fd;
  color: #1565c0;
}

.status-badge--completed {
  background: #e8f5e9;
  color: #2d7a50;
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

@media (max-width: 1024px) {
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .vehicles-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
