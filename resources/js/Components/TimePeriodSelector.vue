<template>
  <div class="time-period-selector">
    <button
      v-for="tab in tabs"
      :key="tab.key"
      class="time-period-selector__tab"
      :class="{ 'time-period-selector__tab--active': activePeriod === tab.key }"
      :disabled="loading"
      @click="$emit('select', tab.key)"
    >
      <span v-if="loading && activePeriod === tab.key" class="time-period-selector__spinner" />
      {{ tab.label }}
    </button>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
  tabs: {
    type: Array,
    required: true,
    default: () => []
  },
  activePeriod: {
    type: String,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['select'])
</script>

<style scoped>
.time-period-selector {
  display: flex;
  gap: 0.5rem;
}

.time-period-selector__tab {
  padding: 0.5rem 1rem;
  border: 1px solid rgb(229, 231, 235);
  background: white;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: rgb(107, 114, 128);
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.time-period-selector__tab:hover:not(:disabled) {
  background: rgb(249, 250, 251);
  border-color: rgb(209, 213, 219);
}

.time-period-selector__tab:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.time-period-selector__tab--active {
  background: rgb(239, 246, 255);
  border-color: rgb(59, 130, 246);
  color: rgb(29, 78, 216);
}

.time-period-selector__spinner {
  display: inline-block;
  width: 0.75rem;
  height: 0.75rem;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-right: 0.5rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>