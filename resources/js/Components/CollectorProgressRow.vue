<template>
  <div class="collector-progress-row">
    <div class="collector-progress-row__info">
      <div class="collector-progress-row__name">{{ collector.name }}</div>
      <div class="collector-progress-row__zone">{{ collector.zone }}</div>
    </div>
    <div class="collector-progress-row__progress">
      <div class="progress-bar">
        <div 
          class="progress-fill" 
          :style="{ width: progressPercent + '%' }"
          :class="progressColor"
        ></div>
      </div>
      <div class="progress-labels">
        <span class="progress-label">{{ formatTZS(collector.collected) }}</span>
        <span class="progress-label">{{ formatTZS(collector.target) }} target</span>
      </div>
    </div>
    <div class="collector-progress-row__stats">
      <span class="stat-item">
        <span class="stat-value">{{ collector.transactions }}</span>
        <span class="stat-label">txns</span>
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  collector: {
    type: Object,
    required: true
  }
})

const progressPercent = computed(() => {
  if (!props.collector.target || props.collector.target === 0) return 0
  return Math.min((props.collector.collected / props.collector.target) * 100, 100)
})

const progressColor = computed(() => {
  const p = progressPercent.value
  if (p >= 90) return 'progress-fill--success'
  if (p >= 75) return 'progress-fill--warning'
  if (p >= 50) return 'progress-fill--info'
  return 'progress-fill--danger'
})

function formatTZS(amount) {
  if (!amount) return 'TZS 0'
  return 'TZS ' + amount.toLocaleString('en-TZ')
}
</script>

<style scoped>
.collector-progress-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem 1rem;
  background: rgb(249, 250, 251);
  border-radius: 0.5rem;
  transition: all 0.2s;
}

.collector-progress-row:hover {
  background: rgb(243, 244, 246);
}

.collector-progress-row__info {
  flex: 1;
  min-width: 0;
}

.collector-progress-row__name {
  font-weight: 600;
  color: rgb(31, 41, 55);
  margin-bottom: 0.125rem;
}

.collector-progress-row__zone {
  font-size: 0.75rem;
  color: rgb(107, 114, 128);
}

.collector-progress-row__progress {
  flex: 2;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.progress-bar {
  height: 0.5rem;
  background: rgb(229, 231, 235);
  border-radius: 0.25rem;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  border-radius: 0.25rem;
  transition: width 1s ease, background-color 0.3s;
}

.progress-fill--success {
  background: rgb(34, 197, 94);
}

.progress-fill--warning {
  background: rgb(251, 146, 60);
}

.progress-fill--info {
  background: rgb(59, 130, 246);
}

.progress-fill--danger {
  background: rgb(239, 68, 68);
}

.progress-labels {
  display: flex;
  justify-content: space-between;
  font-size: 0.7rem;
  color: rgb(107, 114, 128);
}

.collector-progress-row__stats {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  min-width: 80px;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.stat-value {
  font-weight: 700;
  color: rgb(31, 41, 55);
  font-size: 1rem;
}

.stat-label {
  font-size: 0.625rem;
  color: rgb(107, 114, 128);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

@media (max-width: 640px) {
  .collector-progress-row {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .collector-progress-row__progress {
    width: 100%;
  }
  
  .collector-progress-row__stats {
    align-items: flex-start;
    width: 100%;
    flex-direction: row;
    justify-content: space-between;
  }
}
</style>