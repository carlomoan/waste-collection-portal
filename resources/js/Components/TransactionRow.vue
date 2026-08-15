<template>
  <div class="transaction-row">
    <div class="transaction-row__info">
      <div class="transaction-row__payer">{{ transaction.payerName || transaction.payer_name }}</div>
      <div class="transaction-row__meta">
        <span class="transaction-row__control">{{ transaction.controlNumber || transaction.control_number }}</span>
        <span class="transaction-row__date">{{ formatDate(transaction.paidAt || transaction.paid_at) }}</span>
      </div>
    </div>
    <div class="transaction-row__amount">
      <span class="transaction-row__value">{{ formatTZS(transaction.amount) }}</span>
      <span class="transaction-row__method">{{ formatMethod(transaction.paymentMethod || transaction.payment_method) }}</span>
    </div>
    <div class="transaction-row__status">
      <span class="status-badge" :class="`status-badge--${getStatusClass(transaction.status)}`">
        {{ formatStatus(transaction.status) }}
      </span>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  transaction: {
    type: Object,
    required: true
  }
})

function formatTZS(amount) {
  if (!amount) return 'TZS 0.00'
  return 'TZS ' + amount.toLocaleString('en-TZ', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatMethod(method) {
  const methods = {
    cash: 'Cash',
    mobile_money: 'Mobile Money',
    bank: 'Bank Transfer'
  }
  return methods[method] || method
}

function formatStatus(status) {
  return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown'
}

function getStatusClass(status) {
  const statusMap = {
    paid: 'success',
    pending: 'warning',
    refunded: 'info',
    failed: 'error'
  }
  return statusMap[status] || 'info'
}
</script>

<style scoped>
.transaction-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  background: rgb(249, 250, 251);
  border-radius: 0.5rem;
  transition: all 0.2s;
}

.transaction-row:hover {
  background: rgb(243, 244, 246);
}

.transaction-row__info {
  flex: 1;
  min-width: 0;
}

.transaction-row__payer {
  font-weight: 600;
  color: rgb(31, 41, 55);
  margin-bottom: 0.25rem;
}

.transaction-row__meta {
  display: flex;
  gap: 1rem;
  font-size: 0.75rem;
  color: rgb(107, 114, 128);
}

.transaction-row__amount {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  margin: 0 1rem;
}

.transaction-row__value {
  font-weight: 700;
  color: rgb(31, 41, 55);
  font-size: 1rem;
}

.transaction-row__method {
  font-size: 0.75rem;
  color: rgb(107, 114, 128);
  margin-top: 0.125rem;
}

.transaction-row__status {
  flex-shrink: 0;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-badge--success {
  background: rgb(240, 253, 244);
  color: rgb(22, 163, 74);
}

.status-badge--warning {
  background: rgb(254, 252, 232);
  color: rgb(180, 83, 9);
}

.status-badge--info {
  background: rgb(239, 246, 255);
  color: rgb(29, 78, 216);
}

.status-badge--error {
  background: rgb(254, 242, 242);
  color: rgb(220, 38, 38);
}

@media (max-width: 640px) {
  .transaction-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .transaction-row__amount {
    align-items: flex-start;
    margin: 0;
  }
  
  .transaction-row__status {
    align-self: flex-end;
  }
}
</style>
