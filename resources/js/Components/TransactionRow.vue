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
  display: flex; align-items: center; gap: 12px;
  padding: 12px 0; border-bottom: 1px solid rgba(0,0,0,0.06);
  transition: all 0.2s;
}

.tx-row:hover {
  background: linear-gradient(90deg, transparent 0%, rgba(76, 175, 118, 0.03) 100%);
  padding-left: 8px;
  padding-right: 8px;
  border-radius: 8px;
}

.tx-row:last-child { border-bottom: none; }

.tx-avatar {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700; flex-shrink: 0;
  box-shadow: 0 2px 4px rgba(0,0,0,0.08);
}

.avatar--paid    { background: linear-gradient(135deg, #d6f0df 0%, #b8e6d0 100%); color: #2d7a50; }
.avatar--partial { background: linear-gradient(135deg, #fdf6e3 0%, #fef0d4 100%); color: #b88a00; }
.avatar--danger  { background: linear-gradient(135deg, #fef0f0 0%, #fee2e2 100%); color: #c0392b; }

.tx-body { flex: 1; min-width: 0; }

.tx-name { 
  font-size: 13px; font-weight: 600; color: #1a2e24;
  letter-spacing: -0.2px;
}

.tx-meta { font-size: 11px; color: #7a9489; font-weight: 500; }

.tx-right { text-align: right; flex-shrink: 0; }

.tx-amount { font-size: 15px; font-weight: 700; color: #2d7a50; letter-spacing: -0.3px; }

.tx-badge {
  display: inline-block; font-size: 10px; padding: 3px 8px;
  border-radius: 10px; margin-top: 4px; font-weight: 600;
  box-shadow: 0 1px 2px rgba(0,0,0,0.08);
}

.badge--paid    { background: linear-gradient(135deg, #f0faf3 0%, #e8f5e9 100%); color: #2d7a50; border: 1px solid #a8ddb8; }
.badge--partial { background: linear-gradient(135deg, #fdf6e3 0%, #fef0d4 100%); color: #b88a00; border: 1px solid #f5c842; }
.badge--danger  { background: linear-gradient(135deg, #fef0f0 0%, #fee2e2 100%); color: #c0392b; border: 1px solid #fca5a5; }
</style>
