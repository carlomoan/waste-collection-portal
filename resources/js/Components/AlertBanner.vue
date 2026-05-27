<template>
  <div v-if="visible" class="alert-banner" :class="`alert-banner--${type}`">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
         stroke-width="1.8" stroke="currentColor" width="16" height="16" class="alert-icon">
      <path stroke-linecap="round" stroke-linejoin="round"
        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874
           1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
    </svg>
    <span class="alert-text"><slot /></span>
    <button class="alert-dismiss" @click="visible = false" aria-label="Dismiss">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
           stroke-width="2" stroke="currentColor" width="14" height="14">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
defineProps({ type: { type: String, default: 'warning' } }) // warning | danger | info
const visible = ref(true)
</script>

<style scoped>
.alert-banner {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 14px; border-radius: 8px; font-size: 12px;
  margin-bottom: 14px;
}
.alert-banner--warning {
  background: #fdf6e3; border: 1px solid #f5c842; color: #5a3e00;
}
.alert-banner--danger {
  background: #fef0f0; border: 1px solid #f5a5a5; color: #7a1a1a;
}
.alert-banner--info {
  background: #f0faf3; border: 1px solid #a8ddb8; color: #1a4d32;
}
.alert-icon { flex-shrink: 0; }
.alert-text { flex: 1; }
.alert-dismiss {
  background: none; border: none; cursor: pointer;
  color: inherit; opacity: 0.6; padding: 2px;
  display: flex; align-items: center;
}
.alert-dismiss:hover { opacity: 1; }
</style>
