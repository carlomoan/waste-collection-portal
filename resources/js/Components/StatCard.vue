<template>
  <div class="stat-card" :class="`stat-card--${accent}`">
    <div class="stat-card__header">
      <h3 class="stat-card__label">{{ label }}</h3>
      <div v-if="trend" class="stat-card__trend">
        <svg
          v-if="trend === 'up'"
          class="stat-card__trend-icon stat-card__trend-icon--up"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"
          />
        </svg>
        <svg
          v-else
          class="stat-card__trend-icon stat-card__trend-icon--down"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3"
          />
        </svg>
        <span class="stat-card__trend-text">{{ trendText }}</span>
      </div>
    </div>
    <div class="stat-card__body">
      <div class="stat-card__value">{{ value }}</div>
      <div v-if="sub" class="stat-card__sub">{{ sub }}</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  value: { type: String, required: true },
  sub: { type: String, default: '' },
  trend: { type: String, default: '' }, // 'up', 'down', or ''
  trendValue: { type: [Number, String], default: null }, // actual % change
  accent: { type: String, default: 'green' }
})

const trendText = computed(() => {
  if (!props.trend) return ''
  if (props.trendValue !== null && props.trendValue !== undefined) {
    const v = Number(props.trendValue)
    return `${v >= 0 ? '+' : ''}${v.toFixed(1)}%`
  }
  return props.trend === 'up' ? '↑' : '↓'
})
</script>

<style scoped>
.stat-card {
  background: white;
  border-radius: 0.75rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.2s;
  border-left: 4px solid;
}

.stat-card:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.stat-card--green {
  border-left-color: rgb(34 197 94);
}

.stat-card--amber {
  border-left-color: rgb(251 146 60);
}

.stat-card--red {
  border-left-color: rgb(239 68 68);
}

.stat-card--blue {
  border-left-color: rgb(59 130 246);
}

.stat-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.stat-card__label {
  font-size: 0.875rem;
  font-weight: 600;
  color: rgb(107 114 128);
  margin: 0;
}

.stat-card__trend {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.stat-card__trend-icon {
  width: 1rem;
  height: 1rem;
}

.stat-card__trend-icon--up {
  color: rgb(34 197 94);
}

.stat-card__trend-icon--down {
  color: rgb(239 68 68);
}

.stat-card__trend-text {
  color: inherit;
}

.stat-card__body {
  margin-top: 0.5rem;
}

.stat-card__value {
  font-size: 1.875rem;
  font-weight: 700;
  color: rgb(17 24 39);
  line-height: 1.2;
}

.stat-card__sub {
  font-size: 0.75rem;
  color: rgb(107 114 128);
  margin-top: 0.25rem;
}
</style>