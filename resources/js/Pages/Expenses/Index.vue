<template>
  <AppLayout title="Expenses">
    <div class="expenses-container">
      <div class="header">
        <h1>Expenses</h1>
        <p>Track and manage expenses</p>
      </div>

      <!-- Summary Cards -->
      <div class="summary-grid">
        <div class="summary-card">
          <div class="summary-label">Total Expenses (This Month)</div>
          <div class="summary-value">{{ formatCurrency(2450000) }}</div>
          <div class="summary-change summary-change--negative">+8.3% vs last month</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Pending Approval</div>
          <div class="summary-value">{{ formatCurrency(320000) }}</div>
          <div class="summary-change summary-change--neutral">5 expenses awaiting approval</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Budget Used</div>
          <div class="summary-value">67.2%</div>
          <div class="summary-change summary-change--neutral">of {{ formatCurrency(3650000) }} monthly budget</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Categories</div>
          <div class="summary-value">8 Active</div>
          <div class="summary-change summary-change--positive">Fuel, Maintenance, Salaries, etc.</div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="actions-bar">
        <button class="action-btn action-btn--primary">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          New Expense
        </button>
        <button class="action-btn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Export Report
        </button>
        <button class="action-btn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
          </svg>
          Manage Categories
        </button>
      </div>

      <!-- Expense Categories -->
      <div class="categories-section">
        <h3>Expense by Category</h3>
        <div class="categories-grid">
          <div class="category-card">
            <div class="category-icon category-icon--fuel">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
              </svg>
            </div>
            <div class="category-name">Fuel</div>
            <div class="category-amount">{{ formatCurrency(850000) }}</div>
            <div class="category-bar">
              <div class="category-progress" style="width: 35%"></div>
            </div>
          </div>
          <div class="category-card">
            <div class="category-icon category-icon--maintenance">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>
              </svg>
            </div>
            <div class="category-name">Vehicle Maintenance</div>
            <div class="category-amount">{{ formatCurrency(420000) }}</div>
            <div class="category-bar">
              <div class="category-progress" style="width: 17%"></div>
            </div>
          </div>
          <div class="category-card">
            <div class="category-icon category-icon--salaries">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
              </svg>
            </div>
            <div class="category-name">Staff Salaries</div>
            <div class="category-amount">{{ formatCurrency(980000) }}</div>
            <div class="category-bar">
              <div class="category-progress" style="width: 40%"></div>
            </div>
          </div>
          <div class="category-card">
            <div class="category-icon category-icon--office">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0-3h.008v.008h-.008v-.008zm0-3h.008v.008h-.008v-.008z"/>
              </svg>
            </div>
            <div class="category-name">Office Supplies</div>
            <div class="category-amount">{{ formatCurrency(200000) }}</div>
            <div class="category-bar">
              <div class="category-progress" style="width: 8%"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Expenses -->
      <div class="expenses-section">
        <div class="section-header">
          <h3>Recent Expenses</h3>
          <button class="view-all-btn">View All</button>
        </div>
        <table class="expenses-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Description</th>
              <th>Category</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>{{ formatDate('2026-05-25') }}</td>
              <td>Fuel for Collection Truck</td>
              <td>Fuel</td>
              <td>{{ formatCurrency(45000) }}</td>
              <td><span class="status-badge status-badge--approved">Approved</span></td>
              <td><button class="table-action">View</button></td>
            </tr>
            <tr>
              <td>{{ formatDate('2026-05-24') }}</td>
              <td>Vehicle Maintenance - Oil Change</td>
              <td>Maintenance</td>
              <td>{{ formatCurrency(85000) }}</td>
              <td><span class="status-badge status-badge--pending">Pending</span></td>
              <td><button class="table-action">Approve</button></td>
            </tr>
            <tr>
              <td>{{ formatDate('2026-05-23') }}</td>
              <td>Office Supplies - Paper & Ink</td>
              <td>Office</td>
              <td>{{ formatCurrency(35000) }}</td>
              <td><span class="status-badge status-badge--approved">Approved</span></td>
              <td><button class="table-action">View</button></td>
            </tr>
            <tr>
              <td>{{ formatDate('2026-05-22') }}</td>
              <td>Staff Overtime Payment</td>
              <td>Salaries</td>
              <td>{{ formatCurrency(120000) }}</td>
              <td><span class="status-badge status-badge--approved">Approved</span></td>
              <td><button class="table-action">View</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

const formatCurrency = (value) => {
  return new Intl.NumberFormat('sw-TZ', {
    style: 'currency',
    currency: 'TZS',
    minimumFractionDigits: 0,
  }).format(value)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-TZ', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
</script>

<style scoped>
.expenses-container {
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

.categories-section {
  margin-bottom: 24px;
}

.categories-section h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 16px;
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.category-card {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.category-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12px;
}

.category-icon--fuel {
  background: #ffe0b2;
  color: #e65100;
}

.category-icon--maintenance {
  background: #e1bee7;
  color: #7b1fa2;
}

.category-icon--salaries {
  background: #c8e6c9;
  color: #2d7a50;
}

.category-icon--office {
  background: #bbdefb;
  color: #1565c0;
}

.category-name {
  font-size: 13px;
  font-weight: 500;
  color: #1a2e24;
  margin-bottom: 8px;
}

.category-amount {
  font-size: 18px;
  font-weight: 600;
  color: #2d7a50;
  margin-bottom: 12px;
}

.category-bar {
  width: 100%;
  height: 6px;
  background: #f0faf3;
  border-radius: 3px;
  overflow: hidden;
}

.category-progress {
  height: 100%;
  background: #4caf76;
  border-radius: 3px;
  transition: width 0.3s ease;
}

.expenses-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
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

.expenses-table {
  width: 100%;
  border-collapse: collapse;
}

.expenses-table th {
  text-align: left;
  padding: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #4a6357;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.expenses-table td {
  padding: 12px;
  font-size: 13px;
  color: #1a2e24;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.expenses-table tr:last-child td {
  border-bottom: none;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge--approved {
  background: #e8f5e9;
  color: #2d7a50;
}

.status-badge--pending {
  background: #fff3e0;
  color: #e65100;
}

.status-badge--rejected {
  background: #ffebee;
  color: #c62828;
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

@media (max-width: 1024px) {
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .categories-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
