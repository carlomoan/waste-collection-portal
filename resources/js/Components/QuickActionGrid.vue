<template>
  <div class="quick-action-grid">
    <button
      v-for="action in actions"
      :key="action.id"
      class="quick-action-grid__action"
      :class="`quick-action-grid__action--${action.variant}`"
      @click="$emit('action-click', action)"
    >
      <component :is="action.icon" class="quick-action-grid__icon" />
      <span class="quick-action-grid__label">{{ action.label }}</span>
      <span v-if="action.badge" class="quick-action-grid__badge">{{ action.badge }}</span>
    </button>
  </div>
</template>

<script setup>
const props = defineProps({
  actions: {
    type: Array,
    required: true,
    default: () => []
  }
})

const emit = defineEmits(['action-click'])
</script>

<style scoped>
.quick-action-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1rem;
}

.quick-action-grid__action {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
  border: 1px solid rgb(229, 231, 235);
  background: white;
  border-radius: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
  text-align: center;
}

.quick-action-grid__action:hover {
  border-color: rgb(209, 213, 219);
  background: rgb(249, 250, 251);
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.quick-action-grid__action--primary {
  border-color: rgb(59, 130, 246);
}

.quick-action-grid__action--primary:hover {
  background: rgb(239, 246, 255);
}

.quick-action-grid__icon {
  width: 2rem;
  height: 2rem;
  margin-bottom: 0.5rem;
  color: rgb(59, 130, 246);
}

.quick-action-grid__label {
  font-size: 0.875rem;
  font-weight: 500;
  color: rgb(31, 41, 55);
}

.quick-action-grid__badge {
  margin-top: 0.25rem;
  padding: 0.125rem 0.5rem;
  background: rgb(239, 68, 68);
  color: white;
  border-radius: 1rem;
  font-size: 0.75rem;
  font-weight: 600;
}
</style>
