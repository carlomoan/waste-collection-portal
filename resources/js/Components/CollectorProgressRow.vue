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
  display: flex; align-items: center; gap: 10px;
  padding: 8px 0; border-bottom: 1px solid rgba(0,0,0,0.06);
}
.collector-row:last-child { border-bottom: none; }
.avatar {
  width: 30px; height: 30px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 600; flex-shrink: 0;
}
.info { flex: 1; min-width: 0; }
.info-top {
  display: flex; justify-content: space-between;
  margin-bottom: 4px;
}
.name { font-size: 12px; font-weight: 500; color: #1a2e24; }
.amount { font-size: 11px; font-weight: 600; }
.progress-bar {
  height: 5px; background: #f0faf3; border-radius: 3px; overflow: hidden;
}
.progress-fill { height: 100%; border-radius: 3px; transition: width 0.4s ease; }
.meta { font-size: 10px; color: #7a9489; margin-top: 2px; }
</style>
