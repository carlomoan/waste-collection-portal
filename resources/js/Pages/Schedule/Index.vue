<template>
  <AppLayout title="Collection Schedule">

    <!-- Week navigation -->
    <div class="week-nav">
      <button class="week-btn" @click="prevWeek">← Prev Week</button>
      <span class="week-label">Week {{ currentWeek }} · {{ weekRange }}</span>
      <button class="week-btn" @click="nextWeek">Next Week →</button>
    </div>

    <!-- Weekly grid -->
    <div class="week-grid">
      <div v-for="day in weekDays" :key="day.date" class="day-column">
        <div class="day-header" :class="{ 'day-header--today': day.isToday }">
          <span class="day-name">{{ day.name }}</span>
          <span class="day-date">{{ day.dateLabel }}</span>
        </div>
        <div class="day-slots">
          <div v-if="getSchedulesForDay(day.date).length === 0" class="empty-slot">
            No collections
          </div>
          <div
            v-for="sched in getSchedulesForDay(day.date)"
            :key="sched.id"
            class="sched-slot"
            :style="{ borderLeftColor: sched.zone_color }"
          >
            <div class="sched-zone">{{ sched.zone_name }}</div>
            <div class="sched-collector">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                   stroke-width="1.8" stroke="currentColor" width="11" height="11">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5
                     7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
              </svg>
              {{ sched.staff_name }}
            </div>
            <div class="sched-count">{{ sched.client_count }} clients</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Monthly calendar view -->
    <div class="section-title">Monthly Overview — {{ monthLabel }}</div>
    <div class="card">
      <div class="month-grid-header">
        <span v-for="d in ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']" :key="d" class="month-day-label">{{ d }}</span>
      </div>
      <div class="month-grid">
        <div v-for="cell in monthCells" :key="cell.key"
             class="month-cell" :class="{ 'month-cell--empty': !cell.day, 'month-cell--today': cell.isToday }">
          <span v-if="cell.day" class="cell-num">{{ cell.day }}</span>
          <div v-if="cell.schedules?.length" class="cell-scheds">
            <span v-for="s in cell.schedules" :key="s.zone_code"
                  class="cell-dot" :style="{ background: s.zone_color }"
                  :title="s.zone_name" />
          </div>
        </div>
      </div>
    </div>

    <!-- Schedule management -->
    <div class="section-title" style="margin-top:16px">Zone Assignments</div>
    <div class="assignments-grid">
      <div v-for="zone in zones" :key="zone.id" class="assignment-card">
        <div class="zone-header" :style="{ borderTopColor: zone.color }">
          <span class="zone-name">{{ zone.name }}</span>
          <span class="zone-code">{{ zone.code }}</span>
        </div>
        <div class="zone-body">
          <div class="assign-row">
            <span class="assign-label">Collector</span>
            <span class="assign-val">{{ zone.collector_name ?? 'Unassigned' }}</span>
          </div>
          <div class="assign-row">
            <span class="assign-label">Days</span>
            <span class="assign-val">{{ zone.schedule_days }}</span>
          </div>
          <div class="assign-row">
            <span class="assign-label">Clients</span>
            <span class="assign-val">{{ zone.client_count }}</span>
          </div>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  schedules: { type: Array, default: () => [] },
  zones:     { type: Array, default: () => [] },
})

const today       = new Date()
const weekOffset  = ref(0)
const dayNames    = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']

const weekStart = computed(() => {
  const d = new Date(today)
  const dow = (d.getDay() + 6) % 7
  d.setDate(d.getDate() - dow + weekOffset.value * 7)
  d.setHours(0, 0, 0, 0)
  return d
})

const weekDays = computed(() => {
  return Array.from({ length: 7 }, (_, i) => {
    const d = new Date(weekStart.value)
    d.setDate(d.getDate() + i)
    return {
      date: d.toISOString().slice(0, 10),
      name: dayNames[i],
      dateLabel: d.toLocaleDateString('en-TZ', { month: 'short', day: 'numeric' }),
      isToday: d.toDateString() === today.toDateString(),
    }
  })
})

const weekRange = computed(() => {
  const start = weekDays.value[0].dateLabel
  const end   = weekDays.value[6].dateLabel
  return `${start} – ${end}`
})

const currentWeek = computed(() => {
  const d = new Date(weekStart.value)
  d.setDate(d.getDate() + 3)
  const yearStart = new Date(d.getFullYear(), 0, 1)
  return Math.ceil(((d - yearStart) / 86400000 + yearStart.getDay() + 1) / 7)
})

const monthLabel = computed(() =>
  today.toLocaleDateString('en-TZ', { month: 'long', year: 'numeric' })
)

const monthCells = computed(() => {
  const year  = today.getFullYear()
  const month = today.getMonth()
  const first = new Date(year, month, 1)
  const last  = new Date(year, month + 1, 0)
  const startDow = (first.getDay() + 6) % 7
  const cells = []
  for (let i = 0; i < startDow; i++) cells.push({ key: `e${i}`, day: null })
  for (let d = 1; d <= last.getDate(); d++) {
    const date = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`
    cells.push({
      key: date, day: d,
      isToday: d === today.getDate(),
      schedules: props.zones.filter(z => props.schedules.some(s => s.date === date && s.zone_id === z.id)),
    })
  }
  return cells
})

const getSchedulesForDay = (date) => props.schedules.filter(s => s.date === date)

const prevWeek = () => weekOffset.value--
const nextWeek = () => weekOffset.value++
</script>

<style scoped>
.week-nav {
  display: flex; align-items: center; gap: 16px; margin-bottom: 16px;
}
.week-btn {
  padding: 6px 14px; border: 1px solid rgba(0,0,0,0.12);
  border-radius: 7px; font-size: 12px; color: #4a6357; background: #fff; cursor: pointer;
}
.week-btn:hover { border-color: #4caf76; color: #2d7a50; }
.week-label { font-size: 14px; font-weight: 600; color: #1a2e24; }

.week-grid {
  display: grid; grid-template-columns: repeat(7, 1fr);
  gap: 8px; margin-bottom: 20px;
}
.day-column { min-width: 0; }
.day-header {
  text-align: center; padding: 8px 4px;
  background: #fff; border: 1px solid rgba(0,0,0,0.08);
  border-radius: 8px 8px 0 0; margin-bottom: 2px;
}
.day-header--today { background: #f0faf3; border-color: #4caf76; }
.day-name  { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; color: #7a9489; }
.day-date  { display: block; font-size: 12px; font-weight: 600; color: #1a2e24; margin-top: 2px; }
.day-slots { display: flex; flex-direction: column; gap: 4px; }
.empty-slot {
  font-size: 10px; color: #7a9489; text-align: center;
  padding: 12px 4px; background: #f8faf9;
  border: 1px dashed rgba(0,0,0,0.08); border-radius: 6px;
}
.sched-slot {
  padding: 8px 8px; background: #fff;
  border: 1px solid rgba(0,0,0,0.08); border-left-width: 3px;
  border-radius: 0 6px 6px 0;
}
.sched-zone      { font-size: 11px; font-weight: 600; color: #1a2e24; }
.sched-collector { font-size: 10px; color: #4a6357; display: flex; align-items: center; gap: 3px; margin-top: 3px; }
.sched-count     { font-size: 10px; color: #7a9489; margin-top: 2px; }

.section-title { font-size: 13px; font-weight: 600; color: #1a2e24; margin-bottom: 10px; }
.card { background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; overflow: hidden; }
.month-grid-header {
  display: grid; grid-template-columns: repeat(7,1fr);
  background: #f8faf9; border-bottom: 1px solid rgba(0,0,0,0.08);
}
.month-day-label {
  text-align: center; padding: 8px; font-size: 10px;
  text-transform: uppercase; letter-spacing: 0.8px; color: #7a9489;
}
.month-grid { display: grid; grid-template-columns: repeat(7,1fr); gap: 1px; background: rgba(0,0,0,0.05); }
.month-cell {
  background: #fff; min-height: 56px; padding: 6px;
}
.month-cell--empty { background: #f8faf9; }
.month-cell--today { background: #f0faf3; }
.cell-num { font-size: 12px; font-weight: 500; color: #1a2e24; }
.cell-scheds { display: flex; gap: 3px; margin-top: 4px; flex-wrap: wrap; }
.cell-dot { width: 8px; height: 8px; border-radius: 50%; }

.assignments-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
.assignment-card { background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; overflow: hidden; }
.zone-header { padding: 10px 12px; border-top: 3px solid; display: flex; justify-content: space-between; align-items: center; }
.zone-name { font-size: 12px; font-weight: 600; color: #1a2e24; }
.zone-code { font-size: 10px; color: #7a9489; }
.zone-body { padding: 8px 12px; }
.assign-row { display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid rgba(0,0,0,0.05); }
.assign-row:last-child { border-bottom: none; }
.assign-label { font-size: 10px; color: #7a9489; }
.assign-val   { font-size: 11px; color: #1a2e24; font-weight: 500; }
</style>
