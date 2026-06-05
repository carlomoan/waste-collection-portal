<template>
  <div class="stat-card" :class="`stat-card--${accent}`">
    <div class="stat-label">{{ label }}</div>
    <div class="stat-value">{{ value }}</div>
    <div v-if="sub" class="stat-sub" :class="subClass">
      <svg v-if="trend === 'up'" xmlns="http://www.w3.org/2000/svg" fill="none"
           viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="12" height="12">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/>
      </svg>
      <svg v-if="trend === 'down'" xmlns="http://www.w3.org/2000/svg" fill="none"
           viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="12" height="12">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.43l.776 2.898m0 0 3.182-5.511m-3.182 5.51-5.511-3.181"/>
      </svg>
      {{ sub }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label:  { type: String, required: true },
  value:  { type: [String, Number], required: true },
  sub:    { type: String, default: null },
  trend:  { type: String, default: null },   // 'up' | 'down'
  accent: { type: String, default: 'green' }, // 'green' | 'amber' | 'red' | 'blue'
})

const subClass = computed(() => ({
  'stat-sub--up':   props.trend === 'up',
  'stat-sub--down': props.trend === 'down',
}))
</script>

<style scoped>
.stat-card {
  background: linear-gradient(135deg, #ffffff 0%, #f8faf9 100%);
  border: 1.5px solid rgba(0,0,0,0.06);
  border-left-width: 4px;
  border-radius: 14px;
  padding: 18px 20px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.stat-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  transform: translateY(-2px);
}

.stat-card--green  { border-left-color: #4caf76; }
.stat-card--amber  { border-left-color: #f5c842; }
.stat-card--red    { border-left-color: #c0392b; }
.stat-card--blue   { border-left-color: #3b82f6; }

.stat-label {
  font-size: 11px; text-transform: uppercase;
  letter-spacing: 0.8px; color: #7a9489; margin-bottom: 8px;
  font-weight: 600;
}

.stat-value {
  font-size: 26px; font-weight: 800; color: #1a2e24; line-height: 1.1;
  letter-spacing: -0.5px;
}

.stat-sub {
  display: flex; align-items: center; gap: 4px;
  font-size: 12px; color: #7a9489; margin-top: 6px;
  font-weight: 500;
}

.stat-sub--up   { color: #2d7a50; font-weight: 600; }
.stat-sub--down { color: #c0392b; font-weight: 600; }
</style>
