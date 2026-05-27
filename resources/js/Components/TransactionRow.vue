<template>
  <div class="tx-row">
    <div class="tx-avatar" :class="statusClass">{{ initials }}</div>
    <div class="tx-body">
      <div class="tx-name">{{ payerName || 'Unknown Payer' }}</div>
      <div class="tx-meta">Ctrl: ...{{ controlSuffix }} · {{ formattedDate }}</div>
    </div>
    <div class="tx-right">
      <div class="tx-amount">{{ formatTZS(amount) }}</div>
      <span class="tx-badge" :class="badgeClass">{{ statusLabel }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  payerName:     { type: String, default: null },
  controlNumber: { type: String, required: true },
  amount:        { type: Number, required: true },
  status:        { type: String, default: 'paid' }, // paid | partial | unmatched
  paidAt:        { type: String, required: true },
})

const initials = computed(() => {
  if (!props.payerName) return '??'
  return props.payerName.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
})

const controlSuffix = computed(() => props.controlNumber.slice(-4))

const formattedDate = computed(() =>
  new Date(props.paidAt).toLocaleString('en-TZ', {
    month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
  })
)

const formatTZS = (v) =>
  new Intl.NumberFormat('sw-TZ', { minimumFractionDigits: 0 }).format(v)

const statusMap = {
  paid:      { label: 'PAID',      badge: 'badge--paid',    avatar: 'avatar--paid' },
  partial:   { label: 'PARTIAL',   badge: 'badge--partial', avatar: 'avatar--partial' },
  unmatched: { label: 'UNMATCHED', badge: 'badge--danger',  avatar: 'avatar--danger' },
}

const statusLabel = computed(() => statusMap[props.status]?.label ?? props.status.toUpperCase())
const badgeClass  = computed(() => statusMap[props.status]?.badge ?? '')
const statusClass = computed(() => statusMap[props.status]?.avatar ?? '')
</script>

<style scoped>
.tx-row {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 0; border-bottom: 1px solid rgba(0,0,0,0.06);
}
.tx-row:last-child { border-bottom: none; }
.tx-avatar {
  width: 28px; height: 28px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 9px; font-weight: 600; flex-shrink: 0;
}
.avatar--paid    { background: #d6f0df; color: #2d7a50; }
.avatar--partial { background: #fdf6e3; color: #b88a00; }
.avatar--danger  { background: #fef0f0; color: #c0392b; }
.tx-body { flex: 1; min-width: 0; }
.tx-name { font-size: 12px; font-weight: 500; color: #1a2e24; }
.tx-meta { font-size: 10px; color: #7a9489; }
.tx-right { text-align: right; flex-shrink: 0; }
.tx-amount { font-size: 13px; font-weight: 600; color: #2d7a50; }
.tx-badge {
  display: inline-block; font-size: 9px; padding: 2px 6px;
  border-radius: 8px; margin-top: 2px;
}
.badge--paid    { background: #f0faf3; color: #2d7a50; }
.badge--partial { background: #fdf6e3; color: #b88a00; }
.badge--danger  { background: #fef0f0; color: #c0392b; }
</style>
