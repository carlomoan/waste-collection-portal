<template>
  <div class="schedule-card">
    <div class="schedule-card__header">
      <div class="schedule-card__day">{{ schedule.dayLabel || schedule.day }}</div>
      <div class="schedule-card__time" v-if="schedule.start_time">
        <span class="time-badge">{{ schedule.start_time }} - {{ schedule.end_time }}</span>
      </div>
    </div>
    <div class="schedule-card__body">
      <div class="schedule-card__zone">
        <span class="zone-indicator" :style="{ backgroundColor: schedule.zoneColor || schedule.zone_color || '#4caf76' }"></span>
        <span class="zone-name">{{ schedule.zoneName || schedule.zone }}</span>
      </div>
      <div class="schedule-card__collector">
        <svg class="collector-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        <span class="collector-name">{{ schedule.staffName || schedule.staff || 'Unassigned' }}</span>
      </div>
      <div class="schedule-card__clients">
        <svg class="clients-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <span class="clients-count">{{ schedule.clientCount ?? schedule.client_count ?? 0 }} clients</span>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  schedule: {
    type: Object,
    required: true
  }
})
</script>

<style scoped>
.schedule-card {
  background: white;
  border: 1px solid rgb(229, 231, 235);
  border-radius: 0.75rem;
  padding: 1rem;
  transition: all 0.2s;
}

.schedule-card:hover {
  border-color: rgb(209, 213, 219);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.schedule-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid rgb(229, 231, 235);
}

.schedule-card__day {
  font-weight: 600;
  color: rgb(31, 41, 55);
  font-size: 1rem;
}

.schedule-card__time .time-badge {
  padding: 0.25rem 0.5rem;
  background: rgb(239, 246, 255);
  color: rgb(29, 78, 216);
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.schedule-card__body {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.schedule-card__zone,
.schedule-card__collector,
.schedule-card__clients {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: rgb(75, 85, 99);
}

.zone-indicator {
  width: 0.75rem;
  height: 0.75rem;
  border-radius: 50%;
  flex-shrink: 0;
}

.zone-name {
  font-weight: 500;
  color: rgb(31, 41, 55);
}

.collector-icon,
.clients-icon {
  width: 1rem;
  height: 1rem;
  color: rgb(107, 114, 128);
  flex-shrink: 0;
}

.collector-name,
.clients-count {
  color: rgb(75, 85, 99);
}
</style>
