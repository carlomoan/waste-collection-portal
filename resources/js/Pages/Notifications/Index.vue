<template>
  <AppLayout title="Notifications">
    <div class="notifications-container">
      <div class="header">
        <h1>Notifications</h1>
        <div class="header-actions">
          <button class="action-btn" @click="markAllAsRead">Mark All as Read</button>
        </div>
      </div>

      <!-- Notifications List -->
      <div class="notifications-list">
        <div v-for="notification in notifications.data" :key="notification.id" 
             class="notification-item" 
             :class="{ 'notification-item--unread': !notification.read_at }">
          <div class="notification-icon">
            <svg v-if="notification.type === 'payment'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="20" height="20">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <svg v-else-if="notification.type === 'debt'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="20" height="20">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="20" height="20">
              <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
            </svg>
          </div>
          <div class="notification-content">
            <div class="notification-header">
              <span class="notification-title">{{ notification.data?.title || 'Notification' }}</span>
              <span class="notification-time">{{ formatTime(notification.created_at) }}</span>
            </div>
            <p class="notification-message">{{ notification.data?.message || notification.data?.body || 'No message content' }}</p>
          </div>
          <div class="notification-actions">
            <button v-if="!notification.read_at" class="mark-read-btn" @click="markAsRead(notification.id)">
              Mark as Read
            </button>
          </div>
        </div>
        <div v-if="notifications.data.length === 0" class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="48" height="48">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
          </svg>
          <p>No notifications</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="notifications.last_page > 1" class="pagination">
        <button :disabled="notifications.current_page === 1" @click="goToPage(notifications.current_page - 1)" class="pagination-btn">
          Previous
        </button>
        <span class="pagination-info">Page {{ notifications.current_page }} of {{ notifications.last_page }}</span>
        <button :disabled="notifications.current_page === notifications.last_page" @click="goToPage(notifications.current_page + 1)" class="pagination-btn">
          Next
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  notifications: { type: Object, default: () => ({ data: [], last_page: 1, current_page: 1 }) },
})

const markAsRead = (id) => {
  router.post(`/notifications/${id}/mark-read`, {}, { preserveScroll: true })
}

const markAllAsRead = () => {
  router.post('/notifications/mark-all-read', {}, { preserveScroll: true })
}

const goToPage = (page) => {
  router.get('/notifications', { page }, { preserveScroll: true })
}

const formatTime = (date) => {
  const now = new Date()
  const notificationDate = new Date(date)
  const diffInMinutes = Math.floor((now - notificationDate) / (1000 * 60))
  
  if (diffInMinutes < 1) return 'Just now'
  if (diffInMinutes < 60) return `${diffInMinutes}m ago`
  if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`
  if (diffInMinutes < 10080) return `${Math.floor(diffInMinutes / 1440)}d ago`
  return notificationDate.toLocaleDateString('en-TZ', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<style scoped>
.notifications-container {
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

.action-btn {
  padding: 8px 16px;
  background: #f0faf3;
  color: #2d7a50;
  border: 1px solid #a8ddb8;
  border-radius: 6px;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.15s;
}

.action-btn:hover {
  background: #2d7a50;
  color: white;
}

.notifications-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.notification-item {
  display: flex;
  gap: 16px;
  padding: 16px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  transition: background 0.15s;
}

.notification-item--unread {
  background: #f0faf3;
  border-color: #a8ddb8;
}

.notification-icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #e8f5e9;
  border-radius: 50%;
  color: #2d7a50;
}

.notification-content {
  flex: 1;
}

.notification-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.notification-title {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
}

.notification-time {
  font-size: 12px;
  color: #7a9489;
}

.notification-message {
  font-size: 13px;
  color: #4a6357;
  line-height: 1.5;
}

.notification-actions {
  display: flex;
  align-items: center;
}

.mark-read-btn {
  padding: 6px 12px;
  background: white;
  color: #4caf76;
  border: 1px solid #4caf76;
  border-radius: 6px;
  font-size: 11px;
  cursor: pointer;
  transition: background 0.15s;
}

.mark-read-btn:hover {
  background: #4caf76;
  color: white;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #7a9489;
}

.empty-state svg {
  color: #d1d5db;
  margin-bottom: 16px;
}

.empty-state p {
  font-size: 14px;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}

.pagination-btn {
  padding: 8px 16px;
  background: white;
  color: #4a6357;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.15s;
}

.pagination-btn:hover:not(:disabled) {
  background: #f0faf3;
  border-color: #4caf76;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  font-size: 13px;
  color: #4a6357;
}
</style>
