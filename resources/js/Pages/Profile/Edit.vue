<template>
  <AppLayout title="Edit Profile">
    <div class="profile-container">
      <div class="header">
        <h1>Edit Profile</h1>
        <p>Update your account information</p>
      </div>

      <div class="profile-layout">
        <!-- Profile Form -->
        <div class="card">
          <div class="card-header">
            <h3>Profile Information</h3>
          </div>
          <form @submit.prevent="updateProfile">
            <div class="form-group">
              <label>Name</label>
              <input type="text" v-model="form.name" class="form-input" required>
              <span v-if="form.errors.name" class="error-text">{{ form.errors.name }}</span>
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" v-model="form.email" class="form-input" required>
              <span v-if="form.errors.email" class="error-text">{{ form.errors.email }}</span>
            </div>
            <div class="form-group">
              <label>Role</label>
              <input type="text" :value="user.staff?.role || 'N/A'" class="form-input" disabled>
            </div>
            <div class="form-group">
              <label>Staff ID</label>
              <input type="text" :value="user.staff?.employee_number || 'N/A'" class="form-input" disabled>
            </div>
            <button type="submit" class="submit-btn" :disabled="form.processing">
              {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </button>
          </form>
        </div>

        <!-- Password Change -->
        <div class="card">
          <div class="card-header">
            <h3>Change Password</h3>
          </div>
          <form @submit.prevent="updatePassword">
            <div class="form-group">
              <label>Current Password</label>
              <input type="password" v-model="passwordForm.current_password" class="form-input" required>
              <span v-if="passwordForm.errors.current_password" class="error-text">{{ passwordForm.errors.current_password }}</span>
            </div>
            <div class="form-group">
              <label>New Password</label>
              <input type="password" v-model="passwordForm.password" class="form-input" required minlength="8">
              <span v-if="passwordForm.errors.password" class="error-text">{{ passwordForm.errors.password }}</span>
            </div>
            <div class="form-group">
              <label>Confirm Password</label>
              <input type="password" v-model="passwordForm.password_confirmation" class="form-input" required>
            </div>
            <button type="submit" class="submit-btn" :disabled="passwordForm.processing">
              {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
            </button>
          </form>
        </div>
      </div>

      <!-- Danger Zone -->
      <div class="card danger-card">
        <div class="card-header">
          <h3>Danger Zone</h3>
        </div>
        <p class="danger-text">Once you delete your account, there is no going back. Please be certain.</p>
        <button class="delete-btn" @click="showDeleteModal = true">Delete Account</button>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Modal :show="showDeleteModal" @close="showDeleteModal = false" title="Delete Account">
      <p class="modal-text">Are you sure you want to delete your account? This action cannot be undone.</p>
      <form @submit.prevent="deleteAccount">
        <div class="form-group">
          <label>Type your password to confirm</label>
          <input type="password" v-model="deleteForm.password" class="form-input" required>
        </div>
      </form>
      <template #footer>
        <button class="modal-btn modal-btn--cancel" @click="showDeleteModal = false">Cancel</button>
        <button class="modal-btn modal-btn--danger" @click="deleteAccount" :disabled="deleteForm.processing">
          {{ deleteForm.processing ? 'Deleting...' : 'Delete Account' }}
        </button>
      </template>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  user: { type: Object, required: true },
})

const showDeleteModal = ref(false)

const form = useForm({
  name: props.user.name,
  email: props.user.email,
})

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const deleteForm = useForm({
  password: '',
})

const updateProfile = () => {
  form.put('/profile', {
    onSuccess: () => {
      // Success message handled by Inertia
    }
  })
}

const updatePassword = () => {
  passwordForm.put('/profile/password', {
    onSuccess: () => {
      passwordForm.reset()
    }
  })
}

const deleteAccount = () => {
  deleteForm.delete('/profile', {
    onSuccess: () => {
      // Redirect handled by controller
    }
  })
}
</script>

<style scoped>
.profile-container {
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

.profile-layout {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
  margin-bottom: 24px;
}

.card {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 24px;
}

.card-header {
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.card-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1a2e24;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  font-weight: 500;
  color: #4a6357;
}

.form-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 13px;
  color: #1a2e24;
  background: white;
}

.form-input:focus {
  outline: none;
  border-color: #4caf76;
}

.form-input:disabled {
  background: #f5f5f5;
  color: #7a9489;
}

.error-text {
  color: #c0392b;
  font-size: 12px;
  margin-top: 4px;
  display: block;
}

.submit-btn {
  padding: 10px 20px;
  background: #4caf76;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.15s;
}

.submit-btn:hover:not(:disabled) {
  background: #2d7a50;
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.danger-card {
  border-color: #fee2e2;
}

.danger-text {
  color: #7f1d1d;
  font-size: 13px;
  margin-bottom: 16px;
}

.delete-btn {
  padding: 10px 20px;
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fca5a5;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.15s;
}

.delete-btn:hover {
  background: #fca5a5;
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

.modal-btn--danger {
  background: #c0392b;
  color: white;
}

@media (max-width: 768px) {
  .profile-layout {
    grid-template-columns: 1fr;
  }
}
</style>
