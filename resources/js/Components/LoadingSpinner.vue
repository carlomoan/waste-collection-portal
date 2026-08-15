<template>
  <div class="loading-spinner" :class="size">
    <svg class="spinner" viewBox="0 0 50 50" preserveAspectRatio="xMidYMid meet">
      <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="4" />
    </svg>
  </div>
</template>

<script setup>
const props = defineProps({
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
  }
})
</script>

<style scoped>
.loading-spinner {
  display: inline-block;
  position: relative;
}

.spinner {
  animation: spin 1.2s linear infinite;
  width: 100%;
  height: 100%;
}

.path {
  stroke: currentColor;
  stroke-linecap: round;
  animation: dash 1.2s ease-in-out infinite;
}

.loading-spinner--sm {
  width: 16px;
  height: 16px;
}

.loading-spinner--md {
  width: 24px;
  height: 24px;
}

.loading-spinner--lg {
  width: 32px;
  height: 32px;
}

.loading-spinner--xl {
  width: 48px;
  height: 48px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@keyframes dash {
  0% {
    stroke-dasharray: 1, 150;
    stroke-dashoffset: 0;
  }
  50% {
    stroke-dasharray: 90, 150;
    stroke-dashoffset: -35;
  }
  100% {
    stroke-dasharray: 90, 150;
    stroke-dashoffset: -124;
  }
}
</style>
