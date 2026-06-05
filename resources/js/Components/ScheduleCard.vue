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
  background: linear-gradient(135deg, #ffffff 0%, #f8faf9 100%);
  border: 1.5px solid rgba(0,0,0,0.06);
  border-radius: 14px; padding: 18px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.schedule-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  transform: translateY(-2px);
}

.sched-day {
  font-size: 11px; text-transform: uppercase; letter-spacing: 1.2px;
  color: #7a9489; margin-bottom: 8px; font-weight: 600;
}

.sched-zone {
  font-size: 15px; font-weight: 700; color: #1a2e24;
  display: flex; align-items: center; gap: 8px; margin-bottom: 10px;
  letter-spacing: -0.2px;
}

.zone-dot {
  width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
  box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}

.sched-staff {
  font-size: 12px; color: #4a6357;
  display: flex; align-items: center; gap: 6px;
  font-weight: 500;
}

.sched-count {
  font-size: 11px; color: #7a9489; margin-top: 8px;
  font-weight: 600;
  background: rgba(76, 175, 118, 0.08);
  padding: 6px 10px;
  border-radius: 8px;
  display: inline-block;
}
</style>
