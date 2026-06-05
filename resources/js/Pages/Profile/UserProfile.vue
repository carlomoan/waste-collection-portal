<template>
  <AppLayout title="My Profile">
    <div class="profile-container">
      <div class="profile-card">
        <div class="avatar">{{ initials(user.name) }}</div>
        <h2>{{ user.name }}</h2>
        <p class="email">{{ user.email }}</p>
        <div class="detail-grid">
          <div class="detail-row"><span class="lbl">Name</span><span class="val">{{ user.name }}</span></div>
          <div class="detail-row"><span class="lbl">Email</span><span class="val">{{ user.email }}</span></div>
          <div class="detail-row"><span class="lbl">Role</span><span class="val">{{ user.role ?? '—' }}</span></div>
          <div class="detail-row"><span class="lbl">Joined</span><span class="val">{{ formatDate(user.created_at) }}</span></div>
        </div>
        <button class="btn" @click="showEdit = true">Edit Profile</button>
      </div>

      <Modal :show="showEdit" title="Edit Profile" @close="showEdit = false">
        <form @submit.prevent="submit">
          <div class="fg"><label>Name</label><input v-model="form.name" class="fi" required></div>
          <div class="fg"><label>Email</label><input v-model="form.email" class="fi" type="email" required></div>
          <div class="fg"><label>Password</label><input v-model="form.password" class="fi" type="password"></div>
        </form>
        <template #footer>
          <button type="button" class="mb mc" @click="showEdit = false">Cancel</button>
          <button type="button" class="mb mp" @click="submit" :disabled="form.processing">Save</button>
        </template>
      </Modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({ user: Object })

const showEdit = ref(false)
const form = useForm({ name: props.user.name, email: props.user.email, password: '' })

const submit = () => form.patch('/profile', { onSuccess: () => showEdit.value = false })
const initials = (n) => (n||'').split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase()
const formatDate = (d) => d ? new Date(d).toLocaleDateString() : '—'
</script>

<style scoped>
.profile-container { max-width: 500px; margin: 40px auto; }
.profile-card { background: #fff; border-radius: 12px; padding: 30px; text-align: center; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
.avatar { width: 64px; height: 64px; border-radius: 50%; background: #4caf76; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; margin: 0 auto 12px; }
h2 { margin: 0 0 4px; color: #1a2e24; }
.email { color: #7a9489; font-size: 13px; margin: 0 0 16px; }
.detail-grid { text-align: left; margin-bottom: 16px; }
.detail-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.05); font-size: 13px; }
.lbl { color: #7a9489; font-size: 11px; text-transform: uppercase; }
.val { color: #1a2e24; font-weight: 500; }
.btn { padding: 8px 20px; border-radius: 6px; background: #4caf76; color: #fff; border: none; cursor: pointer; font-size: 13px; }
.fg { margin-bottom: 12px; }
.fg label { display: block; font-size: 12px; margin-bottom: 4px; color: #1a2e24; }
.fi { width: 100%; padding: 7px 10px; border: 1px solid rgba(0,0,0,0.12); border-radius: 6px; font-size: 13px; box-sizing: border-box; }
.mb { padding: 8px 16px; border-radius: 6px; font-size: 13px; border: none; cursor: pointer; }
.mc { background: #f5f5f5; color: #4a6357; }
.mp { background: #4caf76; color: #fff; }
</style>
