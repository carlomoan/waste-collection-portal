<template>
  <AppLayout title="Staff Profile">
    <div class="pc">
      <div class="card">
        <div class="av">{{ initials(staff.name) }}</div>
        <h2>{{ staff.name }}</h2>
        <p class="sub">{{ staff.role }} · {{ staff.zone ?? 'Unassigned' }}</p>
        <div class="dg">
          <div class="dr"><span class="l">Staff #</span><span class="v">{{ staff.staff_number ?? '—' }}</span></div>
          <div class="dr"><span class="l">Phone</span><span class="v">{{ staff.phone ?? '—' }}</span></div>
          <div class="dr"><span class="l">National ID</span><span class="v">{{ staff.national_id ?? '—' }}</span></div>
          <div class="dr"><span class="l">Role</span><span class="v">{{ staff.role }}</span></div>
          <div class="dr"><span class="l">Zone</span><span class="v">{{ staff.zone ?? '—' }}</span></div>
          <div class="dr"><span class="l">Base Salary</span><span class="v">{{ formatTZS(staff.base_salary) }}</span></div>
          <div class="dr"><span class="l">Hire Date</span><span class="v">{{ staff.hire_date ?? '—' }}</span></div>
          <div class="dr"><span class="l">Status</span><span class="v">{{ staff.is_active ? 'Active' : 'Inactive' }}</span></div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
const props = defineProps({ staff: Object })
const initials = (n) => (n||'').split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase()
const formatTZS = (v) => 'TZS ' + Number(v||0).toLocaleString()
</script>

<style scoped>
.pc { max-width: 500px; margin: 40px auto; }
.card { background: #fff; border-radius: 12px; padding: 30px; text-align: center; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
.av { width: 64px; height: 64px; border-radius: 50%; background: #4caf76; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; margin: 0 auto 12px; }
h2 { margin: 0 0 4px; color: #1a2e24; }
.sub { color: #7a9489; font-size: 13px; margin: 0 0 16px; }
.dg { text-align: left; }
.dr { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,0.05); font-size: 13px; }
.l { color: #7a9489; font-size: 11px; text-transform: uppercase; }
.v { color: #1a2e24; font-weight: 500; }
</style>
