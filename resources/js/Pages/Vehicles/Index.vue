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
        <button class="action-btn" @click="showMaintenanceModal = true">Schedule Maintenance</button>
        <button class="action-btn" @click="showFuelModal = true">Add Fuel Log</button>
        <button class="action-btn" @click="exportReport">Export Report</button>
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
          <button class="action-btn action-btn--primary" @click="showMaintenanceModal = true">
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
            <tr v-for="maintenance in maintenanceSchedule" :key="maintenance.id">
              <td>{{ maintenance.plate_number }}</td>
              <td>{{ maintenance.maintenance_type }}</td>
              <td>{{ formatDate(maintenance.scheduled_date) }}</td>
              <td>{{ maintenance.description }}</td>
              <td>{{ formatCurrency(maintenance.estimated_cost) }}</td>
              <td><span class="status-badge" :class="`status-badge--${maintenance.status}`">{{ maintenance.status }}</span></td>
              <td>
                <button v-if="maintenance.status === 'pending'" class="table-action" @click="showCompleteMaintenanceModal(maintenance)">Complete</button>
                <button v-else class="table-action">View</button>
              </td>
            </tr>
            <tr v-if="maintenanceSchedule.length === 0">
              <td colspan="7" style="text-align: center; padding: 20px;">No scheduled maintenance</td>
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

    <!-- Add Vehicle Modal -->
    <Modal :show="showAddModal" @close="showAddModal = false" title="Add Vehicle">
      <form @submit.prevent="addVehicle">
        <div class="form-group">
          <label>Plate Number</label>
          <input type="text" v-model="addForm.plate_number" class="form-input" required>
        </div>
        <div class="form-group">
          <label>Vehicle Type</label>
          <select v-model="addForm.type" class="form-input" required>
            <option value="">Select type</option>
            <option value="truck">Truck</option>
            <option value="van">Van</option>
            <option value="pickup">Pickup</option>
          </select>
        </div>
        <div class="form-group">
          <label>Driver</label>
          <select v-model="addForm.driver_id" class="form-input">
            <option value="">Select driver</option>
            <option v-for="driver in drivers" :key="driver.id" :value="driver.id">{{ driver.name }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select v-model="addForm.status" class="form-input">
            <option value="active">Active</option>
            <option value="maintenance">Maintenance</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="form-group">
          <label>Fuel Level (%)</label>
          <input type="number" v-model="addForm.fuel_level" class="form-input" min="0" max="100">
        </div>
        <div class="form-group">
          <label><input type="checkbox" v-model="addForm.is_hired"> Hired Vehicle</label>
        </div>
        <div v-if="addForm.is_hired" class="form-group">
          <label>Payment Type</label>
          <select v-model="addForm.payment_type" class="form-input" required>
            <option value="">Select payment type</option>
            <option value="per_trip">Per Trip</option>
            <option value="per_day">Per Day</option>
          </select>
        </div>
        <div v-if="addForm.is_hired" class="form-group">
          <label>Hire Fee (TZS)</label>
          <input type="number" v-model="addForm.hire_fee" class="form-input" required>
        </div>
        <div v-if="addForm.is_hired" class="form-group">
          <label>Hire Start Date</label>
          <input type="date" v-model="addForm.hire_start_date" class="form-input">
        </div>
        <div v-if="addForm.is_hired" class="form-group">
          <label>Hire End Date</label>
          <input type="date" v-model="addForm.hire_end_date" class="form-input">
        </div>
        <div class="form-group">
          <label>Notes</label>
          <textarea v-model="addForm.notes" class="form-input" rows="2"></textarea>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showAddModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="addVehicle" :disabled="addForm.processing">
          {{ addForm.processing ? 'Adding...' : 'Add Vehicle' }}
        </button>
      </template>
    </Modal>

    <!-- Maintenance Modal -->
    <Modal :show="showMaintenanceModal" @close="showMaintenanceModal = false" title="Schedule Maintenance">
      <form @submit.prevent="submitMaintenance">
        <div class="form-group">
          <label>Vehicle</label>
          <select v-model="maintenanceForm.vehicle_id" class="form-input" required>
            <option value="">Select vehicle</option>
            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.plate_number }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>Maintenance Type</label>
          <select v-model="maintenanceForm.type" class="form-input" required>
            <option value="">Select type</option>
            <option value="oil_change">Oil Change</option>
            <option value="tire_rotation">Tire Rotation</option>
            <option value="engine_repair">Engine Repair</option>
            <option value="general_service">General Service</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Scheduled Date</label>
          <input type="date" v-model="maintenanceForm.scheduled_date" class="form-input" required>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea v-model="maintenanceForm.description" class="form-input" rows="3" required></textarea>
        </div>
        <div class="form-group">
          <label>Estimated Cost (TZS)</label>
          <input type="number" v-model="maintenanceForm.estimated_cost" class="form-input">
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showMaintenanceModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="submitMaintenance" :disabled="maintenanceForm.processing">
          {{ maintenanceForm.processing ? 'Submitting...' : 'Schedule Maintenance' }}
        </button>
      </template>
    </Modal>

    <!-- Complete Maintenance Modal -->
    <Modal :show="showCompleteModal" @close="showCompleteModal = false" title="Complete Maintenance">
      <form @submit.prevent="completeMaintenance">
        <div class="form-group">
          <label>Actual Cost (TZS)</label>
          <input type="number" v-model="completeForm.actual_cost" class="form-input" required>
        </div>
        <div class="form-group">
          <label>Notes</label>
          <textarea v-model="completeForm.notes" class="form-input" rows="3"></textarea>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showCompleteModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="completeMaintenance" :disabled="completeForm.processing">
          {{ completeForm.processing ? 'Completing...' : 'Complete Maintenance' }}
        </button>
      </template>
    </Modal>

    <!-- Fuel Log Modal -->
    <Modal :show="showFuelModal" @close="showFuelModal = false" title="Add Fuel Log">
      <form @submit.prevent="addFuelLog">
        <div class="form-group">
          <label>Vehicle</label>
          <select v-model="fuelForm.vehicle_id" class="form-input" required>
            <option value="">Select vehicle</option>
            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.plate_number }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>Refill Date</label>
          <input type="date" v-model="fuelForm.refill_date" class="form-input" required>
        </div>
        <div class="form-group">
          <label>Liters</label>
          <input type="number" v-model="fuelForm.liters" class="form-input" required>
        </div>
        <div class="form-group">
          <label>Cost (TZS)</label>
          <input type="number" v-model="fuelForm.cost" class="form-input" required>
        </div>
        <div class="form-group">
          <label>Odometer (km)</label>
          <input type="number" v-model="fuelForm.odometer_km" class="form-input">
        </div>
        <div class="form-group">
          <label>Notes</label>
          <textarea v-model="fuelForm.notes" class="form-input" rows="2"></textarea>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showFuelModal = false">Cancel</button>
        <button class="modal-btn modal-btn--primary" @click="addFuelLog" :disabled="fuelForm.processing">
          {{ fuelForm.processing ? 'Adding...' : 'Add Fuel Log' }}
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
  },
  drivers: {
    type: Array,
    default: () => []
  }
})

const showAddModal = ref(false)
const showMaintenanceModal = ref(false)
const showCompleteModal = ref(false)
const showFuelModal = ref(false)
const showDeleteModal = ref(false)
const vehicleToDelete = ref(null)
const maintenanceToComplete = ref(null)

const addForm = useForm({
  plate_number: '',
  type: '',
  driver_id: '',
  status: 'active',
  fuel_level: 100,
  is_hired: false,
  payment_type: '',
  hire_fee: '',
  hire_start_date: '',
  hire_end_date: '',
  notes: ''
})

const maintenanceForm = useForm({
  vehicle_id: '',
  type: '',
  scheduled_date: '',
  description: '',
  estimated_cost: ''
})

const completeForm = useForm({
  actual_cost: '',
  notes: ''
})

const fuelForm = useForm({
  vehicle_id: '',
  refill_date: '',
  liters: '',
  cost: '',
  odometer_km: '',
  notes: ''
})

const exportReport = () => {
  window.location.href = '/vehicles/export?format=csv'
}

const addVehicle = () => {
  addForm.post('/vehicles', {
    onSuccess: () => {
      showAddModal.value = false
      addForm.reset()
    }
  })
}

const submitMaintenance = () => {
  maintenanceForm.post(`/vehicles/${maintenanceForm.vehicle_id}/maintenance`, {
    onSuccess: () => {
      showMaintenanceModal.value = false
      maintenanceForm.reset()
    }
  })
}

const showCompleteMaintenanceModal = (maintenance) => {
  maintenanceToComplete.value = maintenance
  showCompleteModal.value = true
}

const completeMaintenance = () => {
  if (!maintenanceToComplete.value) return
  
  completeForm.patch(`/vehicles/maintenance/${maintenanceToComplete.value.id}/complete`, {
    onSuccess: () => {
      showCompleteModal.value = false
      completeForm.reset()
      maintenanceToComplete.value = null
    }
  })
}

const addFuelLog = () => {
  fuelForm.post('/vehicles/fuel-log', {
    onSuccess: () => {
      showFuelModal.value = false
      fuelForm.reset()
    }
  })
}

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

.modal-btn {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
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
