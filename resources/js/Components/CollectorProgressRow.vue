<template>
  <div class="collector-row">
    <div class="avatar" :style="{ background: avatarBg, color: avatarColor }">
      {{ initials }}
    </div>
    <div class="info">
      <div class="info-top">
        <span class="name">{{ name }}</span>
        <span class="amount" :style="{ color: amountColor }">
          {{ formatTZS(collected) }}
        </span>
      </div>
      <div class="progress-bar">
        <div class="progress-fill" :style="{ width: percent + '%', background: barColor }" />
      </div>
      <div class="meta">{{ transactions }} transactions · {{ zone }}</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  name:         { type: String, required: true },
  collected:    { type: Number, required: true },
  target:       { type: Number, default: 1200000 },
  transactions: { type: Number, default: 0 },
  zone:         { type: String, default: '' },
})

const percent    = computed(() => Math.min(100, Math.round(props.collected / props.target * 100)))
const barColor   = computed(() => percent.value >= 80 ? '#4caf76' : percent.value >= 50 ? '#f5c842' : '#c0392b')
const amountColor = computed(() => percent.value >= 80 ? '#2d7a50' : percent.value >= 50 ? '#b88a00' : '#c0392b')
const avatarBg   = computed(() => percent.value >= 80 ? '#d6f0df' : percent.value >= 50 ? '#fdf6e3' : '#fef0f0')
const avatarColor = computed(() => percent.value >= 80 ? '#1a4d32' : percent.value >= 50 ? '#b88a00' : '#c0392b')

const initials = computed(() =>
  props.name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
)

const formatTZS = (v) =>
  new Intl.NumberFormat('sw-TZ', { style: 'currency', currency: 'TZS', minimumFractionDigits: 0 }).format(v)
</script>

<style scoped>
.collector-row {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 0; border-bottom: 1px solid rgba(0,0,0,0.06);
  transition: all 0.2s;
}

.collector-row:hover {
  background: linear-gradient(90deg, transparent 0%, rgba(76, 175, 118, 0.03) 100%);
  padding-left: 8px;
  padding-right: 8px;
  border-radius: 8px;
}

.collector-row:last-child { border-bottom: none; }

.avatar {
  width: 38px; height: 38px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; flex-shrink: 0;
  box-shadow: 0 2px 4px rgba(0,0,0,0.08);
}

.info { flex: 1; min-width: 0; }

.info-top {
  display: flex; justify-content: space-between;
  margin-bottom: 6px;
}

.name { font-size: 13px; font-weight: 600; color: #1a2e24; letter-spacing: -0.2px; }

.amount { font-size: 13px; font-weight: 700; letter-spacing: -0.3px; }

.progress-bar {
  height: 6px; background: linear-gradient(90deg, #f0faf3 0%, #e8f5e9 100%);
  border-radius: 4px; overflow: hidden;
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
}

.progress-fill { 
  height: 100%; 
  border-radius: 4px; 
  transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}

.meta { font-size: 11px; color: #7a9489; margin-top: 4px; font-weight: 500; }
</style>
