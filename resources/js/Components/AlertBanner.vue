<template>
  <div
    v-if="show"
    class="alert-banner"
    :class="`alert-banner--${type}`"
    role="alert"
  >
    <div class="alert-content">
      <slot name="default" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['info', 'warning', 'error', 'success'].includes(value)
  },
  show: {
    type: Boolean,
    default: true
  }
})

const typeClasses = computed(() => {
  const classes = {
    info: 'alert-banner--info',
    warning: 'alert-banner--warning',
    error: 'alert-banner--error',
    success: 'alert-banner--success'
  }
  return classes[props.type] || classes.info
})
</script>

<style scoped>
.alert-banner {
  display: flex;
  align-items: center;
  padding: 1rem 1.5rem;
  border-radius: 0.5rem;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
  line-height: 1.5;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.alert-banner:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
}

.alert-banner--info {
  background-color: rgb(239 246 255);
  border: 1px solid rgb(191 219 254);
  color: rgb(29 78 216);
}

.alert-banner--warning {
  background-color: rgb(254 252 232);
  border: 1px solid rgb(254 215 170);
  color: rgb(180 83 9);
}

.alert-banner--error {
  background-color: rgb(254 242 242);
  border: 1px solid rgb(254 202 202);
  color: rgb(220 38 38);
}

.alert-banner--success {
  background-color: rgb(240 253 244);
  border: 1px solid rgb(187 247 208);
  color: rgb(22 163 74);
}

.alert-link {
  font-weight: 500;
  text-decoration: underline;
  transition: all 0.2s;
}

.alert-link:hover {
  opacity: 0.8;
}
</style>