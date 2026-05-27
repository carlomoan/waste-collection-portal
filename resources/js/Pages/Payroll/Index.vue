<template>
  <AppLayout title="Payroll">
    <div class="payroll-container">
      <div class="header">
        <h1>Payroll</h1>
        <p>Manage staff payroll and payments</p>
      </div>

      <!-- Month Selector -->
      <div class="month-selector">
        <button class="month-btn" @click="previousMonth">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
          </svg>
        </button>
        <div class="current-month">{{ currentMonthYear }}</div>
        <button class="month-btn" @click="nextMonth">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
          </svg>
        </button>
      </div>

      <!-- Summary Cards -->
      <div class="summary-grid">
        <div class="summary-card">
          <div class="summary-label">Total Payroll</div>
          <div class="summary-value">{{ formatCurrency(4850000) }}</div>
          <div class="summary-change summary-change--neutral">For {{ currentMonthYear }}</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Paid</div>
          <div class="summary-value">{{ formatCurrency(3200000) }}</div>
          <div class="summary-change summary-change--positive">12 staff members paid</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Pending</div>
          <div class="summary-value">{{ formatCurrency(1650000) }}</div>
          <div class="summary-change summary-change--neutral">6 staff pending payment</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Overtime</div>
          <div class="summary-value">{{ formatCurrency(450000) }}</div>
          <div class="summary-change summary-change--positive">+{{ formatCurrency(85000) }} vs last month</div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="actions-bar">
        <button class="action-btn action-btn--primary">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          Generate Payroll
        </button>
        <button class="action-btn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export Payslips
        </button>
        <button class="action-btn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
          </svg>
          Process Payments
        </button>
      </div>

      <!-- Staff Payroll List -->
      <div class="payroll-section">
        <div class="section-header">
          <h3>Staff Payroll - {{ currentMonthYear }}</h3>
          <div class="filter-actions">
            <select class="filter-select">
              <option>All Staff</option>
              <option>Collectors</option>
              <option>Drivers</option>
              <option>Admin</option>
            </select>
            <button class="view-all-btn">View All</button>
          </div>
        </div>
        <table class="payroll-table">
          <thead>
            <tr>
              <th>Staff</th>
              <th>Role</th>
              <th>Base Salary</th>
              <th>Overtime</th>
              <th>Deductions</th>
              <th>Net Pay</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <div class="staff-info">
                  <div class="staff-name">Sarah Shechambo</div>
                  <div class="staff-id">STF-001</div>
                </div>
              </td>
              <td>Collector</td>
              <td>{{ formatCurrency(350000) }}</td>
              <td>{{ formatCurrency(45000) }}</td>
              <td>{{ formatCurrency(15000) }}</td>
              <td>{{ formatCurrency(380000) }}</td>
              <td><span class="status-badge status-badge--paid">Paid</span></td>
              <td><button class="table-action">View</button></td>
            </tr>
            <tr>
              <td>
                <div class="staff-info">
                  <div class="staff-name">John Mwangi</div>
                  <div class="staff-id">STF-002</div>
                </div>
              </td>
              <td>Collector</td>
              <td>{{ formatCurrency(350000) }}</td>
              <td>{{ formatCurrency(32000) }}</td>
              <td>{{ formatCurrency(12000) }}</td>
              <td>{{ formatCurrency(370000) }}</td>
              <td><span class="status-badge status-badge--paid">Paid</span></td>
              <td><button class="table-action">View</button></td>
            </tr>
            <tr>
              <td>
                <div class="staff-info">
                  <div class="staff-name">Fatuma Makame</div>
                  <div class="staff-id">STF-003</div>
                </div>
              </td>
              <td>Collector</td>
              <td>{{ formatCurrency(350000) }}</td>
              <td>{{ formatCurrency(28000) }}</td>
              <td>{{ formatCurrency(10000) }}</td>
              <td>{{ formatCurrency(368000) }}</td>
              <td><span class="status-badge status-badge--pending">Pending</span></td>
              <td><button class="table-action">Pay</button></td>
            </tr>
            <tr>
              <td>
                <div class="staff-info">
                  <div class="staff-name">Ali Hassan</div>
                  <div class="staff-id">STF-004</div>
                </div>
              </td>
              <td>Driver</td>
              <td>{{ formatCurrency(400000) }}</td>
              <td>{{ formatCurrency(55000) }}</td>
              <td>{{ formatCurrency(20000) }}</td>
              <td>{{ formatCurrency(435000) }}</td>
              <td><span class="status-badge status-badge--paid">Paid</span></td>
              <td><button class="table-action">View</button></td>
            </tr>
            <tr>
              <td>
                <div class="staff-info">
                  <div class="staff-name">Mary Kileo</div>
                  <div class="staff-id">STF-005</div>
                </div>
              </td>
              <td>Admin</td>
              <td>{{ formatCurrency(450000) }}</td>
              <td>{{ formatCurrency(0) }}</td>
              <td>{{ formatCurrency(25000) }}</td>
              <td>{{ formatCurrency(425000) }}</td>
              <td><span class="status-badge status-badge--pending">Pending</span></td>
              <td><button class="table-action">Pay</button></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Payroll Summary -->
      <div class="summary-section">
        <h3>Payroll Summary</h3>
        <div class="summary-details">
          <div class="summary-row">
            <span class="summary-detail-label">Total Base Salary</span>
            <span class="summary-detail-value">{{ formatCurrency(4250000) }}</span>
          </div>
          <div class="summary-row">
            <span class="summary-detail-label">Total Overtime</span>
            <span class="summary-detail-value">{{ formatCurrency(450000) }}</span>
          </div>
          <div class="summary-row">
            <span class="summary-detail-label">Total Deductions</span>
            <span class="summary-detail-value">{{ formatCurrency(150000) }}</span>
          </div>
          <div class="summary-row summary-row--total">
            <span class="summary-detail-label">Net Payroll Total</span>
            <span class="summary-detail-value">{{ formatCurrency(4550000) }}</span>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const currentDate = ref(new Date())

const currentMonthYear = computed(() => {
  return currentDate.value.toLocaleDateString('en-TZ', {
    year: 'numeric',
    month: 'long',
  })
})

const previousMonth = () => {
  currentDate.value = new Date(currentDate.value.setMonth(currentDate.value.getMonth() - 1))
}

const nextMonth = () => {
  currentDate.value = new Date(currentDate.value.setMonth(currentDate.value.getMonth() + 1))
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('sw-TZ', {
    style: 'currency',
    currency: 'TZS',
    minimumFractionDigits: 0,
  }).format(value)
}
</script>

<style scoped>
.payroll-container {
  padding: 20px;
}

.header {
  margin-bottom: 24px;
}

.header h1 {
  font-size: 24px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 4px;
}

.header p {
  color: #4a6357;
  font-size: 14px;
}

.month-selector {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 12px 20px;
  width: fit-content;
}

.month-btn {
  padding: 8px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  background: white;
  color: #4a6357;
  cursor: pointer;
  transition: all 0.15s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.month-btn:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.current-month {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
  min-width: 150px;
  text-align: center;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.summary-card {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.summary-label {
  font-size: 12px;
  color: #4a6357;
  margin-bottom: 8px;
}

.summary-value {
  font-size: 24px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 8px;
}

.summary-change {
  font-size: 11px;
}

.summary-change--positive {
  color: #2d7a50;
}

.summary-change--negative {
  color: #c0392b;
}

.summary-change--neutral {
  color: #4a6357;
}

.actions-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
}

.action-btn {
  padding: 10px 20px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 8px;
  background: white;
  color: #4a6357;
  font-size: 13px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.15s;
}

.action-btn:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.action-btn--primary {
  background: #4caf76;
  color: white;
  border-color: #4caf76;
}

.action-btn--primary:hover {
  background: #2d7a50;
  border-color: #2d7a50;
}

.payroll-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 24px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.section-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1a2e24;
}

.filter-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 12px;
  color: #4a6357;
  background: white;
}

.view-all-btn {
  padding: 8px 16px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 12px;
  color: #4a6357;
  cursor: pointer;
  transition: all 0.15s;
}

.view-all-btn:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.payroll-table {
  width: 100%;
  border-collapse: collapse;
}

.payroll-table th {
  text-align: left;
  padding: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #4a6357;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.payroll-table td {
  padding: 12px;
  font-size: 13px;
  color: #1a2e24;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.payroll-table tr:last-child td {
  border-bottom: none;
}

.staff-info {
  display: flex;
  flex-direction: column;
}

.staff-name {
  font-weight: 500;
  color: #1a2e24;
}

.staff-id {
  font-size: 11px;
  color: #4a6357;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge--paid {
  background: #e8f5e9;
  color: #2d7a50;
}

.status-badge--pending {
  background: #fff3e0;
  color: #e65100;
}

.status-badge--processing {
  background: #e3f2fd;
  color: #1565c0;
}

.table-action {
  padding: 4px 12px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 4px;
  font-size: 11px;
  color: #4a6357;
  cursor: pointer;
  transition: all 0.15s;
}

.table-action:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.summary-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.summary-section h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 16px;
}

.summary-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.summary-row--total {
  border-top: 2px solid rgba(0,0,0,0.08);
  border-bottom: none;
  padding-top: 16px;
  margin-top: 8px;
}

.summary-detail-label {
  font-size: 13px;
  color: #4a6357;
}

.summary-detail-value {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
}

.summary-row--total .summary-detail-value {
  font-size: 18px;
  color: #2d7a50;
}

@media (max-width: 1024px) {
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
