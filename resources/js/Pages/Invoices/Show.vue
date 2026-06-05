<template>
  <AppLayout :title="`Invoice ${invoice.invoice_number}`">
    <div class="invoice-show-container">
      <div class="header">
        <div>
          <Link href="/invoices" class="back-link">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Back to Invoices
          </Link>
          <h1>Invoice #{{ invoice.invoice_number }}</h1>
        </div>
        <div class="header-actions">
          <button class="action-btn" @click="downloadInvoice">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            Download PDF
          </button>
        </div>
      </div>

      <!-- Invoice Details Card -->
      <div class="card">
        <div class="card-header">
          <h3>Invoice Details</h3>
          <span class="status-badge" :class="getStatusClass(invoice.status)">{{ invoice.status }}</span>
        </div>
        
        <div class="invoice-details">
          <div class="detail-row">
            <span class="detail-label">Client</span>
            <span class="detail-value">{{ invoice.client?.name || 'Unknown' }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Amount</span>
            <span class="detail-value">{{ formatCurrency(invoice.amount) }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Amount Paid</span>
            <span class="detail-value">{{ formatCurrency(invoice.amount_paid || 0) }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Balance Due</span>
            <span class="detail-value" :class="(invoice.amount - (invoice.amount_paid || 0)) > 0 ? 'danger' : 'success'">
              {{ formatCurrency(invoice.amount - (invoice.amount_paid || 0)) }}
            </span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Billing Period</span>
            <span class="detail-value">{{ getMonthName(invoice.billing_month) }} {{ invoice.billing_year }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Due Date</span>
            <span class="detail-value">{{ formatDate(invoice.due_date) }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Created At</span>
            <span class="detail-value">{{ formatDate(invoice.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- Payments Card -->
      <div class="card">
        <div class="card-header">
          <h3>Payments</h3>
          <span class="payment-count">{{ payments.length }} payment(s)</span>
        </div>
        
        <div v-if="payments.length > 0" class="payments-list">
          <div v-for="payment in payments" :key="payment.id" class="payment-item">
            <div class="payment-info">
              <span class="payment-control">{{ payment.control_number }}</span>
              <span class="payment-method">{{ payment.payment_method }}</span>
            </div>
            <div class="payment-amount">{{ formatCurrency(payment.amount) }}</div>
            <div class="payment-date">{{ formatDate(payment.paid_at) }}</div>
          </div>
        </div>
        <div v-else class="empty-state">
          <p>No payments recorded for this invoice</p>
        </div>
      </div>

      <!-- Client Info Card -->
      <div class="card" v-if="invoice.client">
        <div class="card-header">
          <h3>Client Information</h3>
        </div>
        
        <div class="client-info">
          <div class="detail-row">
            <span class="detail-label">Name</span>
            <span class="detail-value">{{ invoice.client.name }}</span>
          </div>
          <div class="detail-row" v-if="invoice.client.phone">
            <span class="detail-label">Phone</span>
            <span class="detail-value">{{ invoice.client.phone }}</span>
          </div>
          <div class="detail-row" v-if="invoice.client.address">
            <span class="detail-label">Address</span>
            <span class="detail-value">{{ invoice.client.address }}</span>
          </div>
          <div class="detail-row" v-if="invoice.client.zone">
            <span class="detail-label">Zone</span>
            <span class="detail-value">{{ invoice.client.zone.name }}</span>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  invoice: { type: Object, required: true },
})

const payments = computed(() => props.invoice.payments || [])

const downloadInvoice = () => {
  window.location.href = `/invoices/${props.invoice.id}/download`
}

const getStatusClass = (status) => {
  const classes = {
    paid: 'status-badge--success',
    pending: 'status-badge--warning',
    overdue: 'status-badge--danger',
    cancelled: 'status-badge--neutral',
  }
  return classes[status] || 'status-badge--neutral'
}

const getMonthName = (monthNum) => {
  const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
  return months[monthNum - 1] || 'Unknown'
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('sw-TZ', {
    style: 'currency',
    currency: 'TZS',
    minimumFractionDigits: 0,
  }).format(value || 0)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-TZ', { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<style scoped>
.invoice-show-container {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #4a6357;
  text-decoration: none;
  font-size: 13px;
  margin-bottom: 8px;
  transition: color 0.15s;
}

.back-link:hover {
  color: #4caf76;
}

.header h1 {
  font-size: 24px;
  font-weight: 600;
  color: #1a2e24;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  padding: 8px 16px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  background: white;
  color: #4a6357;
  transition: background 0.15s;
}

.action-btn:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.card {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 24px;
  margin-bottom: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.card-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1a2e24;
  margin: 0;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.status-badge--success {
  background: #e8f5e9;
  color: #2d7a50;
}

.status-badge--warning {
  background: #fef3c7;
  color: #92400e;
}

.status-badge--danger {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge--neutral {
  background: #f5f5f5;
  color: #4a6357;
}

.invoice-details {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.detail-row {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-label {
  font-size: 12px;
  color: #4a6357;
}

.detail-value {
  font-size: 14px;
  font-weight: 500;
  color: #1a2e24;
}

.detail-value.danger {
  color: #c0392b;
}

.detail-value.success {
  color: #2d7a50;
}

.payment-count {
  font-size: 12px;
  color: #7a9489;
}

.payments-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.payment-item {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 16px;
  padding: 12px;
  background: #f8faf9;
  border-radius: 6px;
  align-items: center;
}

.payment-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.payment-control {
  font-size: 13px;
  font-weight: 500;
  color: #1a2e24;
}

.payment-method {
  font-size: 11px;
  color: #7a9489;
  text-transform: capitalize;
}

.payment-amount {
  font-size: 14px;
  font-weight: 600;
  color: #2d7a50;
}

.payment-date {
  font-size: 12px;
  color: #4a6357;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #7a9489;
}

.empty-state p {
  font-size: 14px;
}

.client-info {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

@media (max-width: 768px) {
  .header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .invoice-details {
    grid-template-columns: 1fr;
  }
  
  .payment-item {
    grid-template-columns: 1fr;
    gap: 8px;
  }
}
</style>
