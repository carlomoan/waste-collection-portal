<template>
  <div class="schedule-card">
    <div class="sched-day">{{ dayLabel }}</div>
    <div class="sched-zone">
      <span class="zone-dot" :style="{ background: zoneColor }" />
      {{ zoneName }}
    </div>
    <div class="sched-staff">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
           stroke-width="1.8" stroke="currentColor" width="12" height="12">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5
             0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
      </svg>
      {{ staffName }}
    </div>
    <div class="sched-count">{{ clientCount }} clients scheduled</div>
  </div>
</template>

<script setup>
defineProps({
  dayLabel:    { type: String, required: true },
  zoneName:    { type: String, required: true },
  zoneColor:   { type: String, default: '#4caf76' },
  staffName:   { type: String, required: true },
  clientCount: { type: Number, default: 0 },
})
</script>

<style scoped>
.schedule-card {
  background: #ffffff; border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px; padding: 14px;
}
.sched-day {
  font-size: 10px; text-transform: uppercase; letter-spacing: 1px;
  color: #7a9489; margin-bottom: 4px;
}
.sched-zone {
  font-size: 13px; font-weight: 600; color: #1a2e24;
  display: flex; align-items: center; gap: 6px; margin-bottom: 6px;
}
.zone-dot {
  width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.sched-staff {
  font-size: 11px; color: #4a6357;
  display: flex; align-items: center; gap: 4px;
}
.sched-count {
  font-size: 10px; color: #7a9489; margin-top: 6px;
}
</style>
