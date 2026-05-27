<template>
  <AppLayout title="Reports">
    <div class="reports-container">
      <div class="header">
        <h1>Reports</h1>
        <p>Generate and view reports</p>
      </div>

      <!-- Report Type Selection -->
      <div class="report-types">
        <div class="report-type-card" :class="{ 'report-type-card--active': selectedReportType === 'revenue' }" @click="selectedReportType = 'revenue'">
          <div class="report-type-icon report-type-icon--revenue">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
          </div>
          <div class="report-type-name">Revenue Report</div>
          <div class="report-type-desc">Income and collections analysis</div>
        </div>
        <div class="report-type-card" :class="{ 'report-type-card--active': selectedReportType === 'collection' }" @click="selectedReportType = 'collection'">
          <div class="report-type-icon report-type-icon--collection">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
            </svg>
          </div>
          <div class="report-type-name">Collection Report</div>
          <div class="report-type-desc">Waste collection statistics</div>
        </div>
        <div class="report-type-card" :class="{ 'report-type-card--active': selectedReportType === 'staff' }" @click="selectedReportType = 'staff'">
          <div class="report-type-icon report-type-icon--staff">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0z"/>
            </svg>
          </div>
          <div class="report-type-name">Staff Report</div>
          <div class="report-type-desc">Performance and attendance</div>
        </div>
        <div class="report-type-card" :class="{ 'report-type-card--active': selectedReportType === 'financial' }" @click="selectedReportType = 'financial'">
          <div class="report-type-icon report-type-icon--financial">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m-5.5.5h.01m4.99 5h.01M19.5 9.75v10.5c0 .621-.504 1.125-1.125 1.125h-13.5A1.125 1.125 0 0 1 3.75 20.25V9.75m16.5-6a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 3.75v6m16.5-6v6m-16.5-6v6"/>
            </svg>
          </div>
          <div class="report-type-name">Financial Report</div>
          <div class="report-type-desc">Expenses and profitability</div>
        </div>
      </div>

      <!-- Report Filters -->
      <div class="filters-section">
        <div class="filter-group">
          <label class="filter-label">Period</label>
          <select class="filter-select">
            <option>This Month</option>
            <option>Last Month</option>
            <option>Last 3 Months</option>
            <option>Last 6 Months</option>
            <option>This Year</option>
            <option>Custom Range</option>
          </select>
        </div>
        <div class="filter-group">
          <label class="filter-label">Zone</label>
          <select class="filter-select">
            <option>All Zones</option>
            <option>Zone A-F</option>
            <option>Zone G-J</option>
            <option>Zone K-L</option>
          </select>
        </div>
        <div class="filter-group">
          <label class="filter-label">Format</label>
          <select class="filter-select">
            <option>PDF</option>
            <option>Excel</option>
            <option>CSV</option>
          </select>
        </div>
        <button class="generate-btn">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Generate Report
        </button>
      </div>

      <!-- Recent Reports -->
      <div class="reports-section">
        <div class="section-header">
          <h3>Recent Reports</h3>
          <button class="view-all-btn">View All</button>
        </div>
        <table class="reports-table">
          <thead>
            <tr>
              <th>Report Name</th>
              <th>Type</th>
              <th>Period</th>
              <th>Generated</th>
              <th>Size</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Monthly Revenue Report - May 2026</td>
              <td>Revenue</td>
              <td>May 2026</td>
              <td>{{ formatDate('2026-05-25') }}</td>
              <td>2.4 MB</td>
              <td>
                <button class="table-action">Download</button>
                <button class="table-action">View</button>
              </td>
            </tr>
            <tr>
              <td>Collection Performance - Q2 2026</td>
              <td>Collection</td>
              <td>Apr - Jun 2026</td>
              <td>{{ formatDate('2026-05-20') }}</td>
              <td>3.1 MB</td>
              <td>
                <button class="table-action">Download</button>
                <button class="table-action">View</button>
              </td>
            </tr>
            <tr>
              <td>Staff Performance Report - May 2026</td>
              <td>Staff</td>
              <td>May 2026</td>
              <td>{{ formatDate('2026-05-18') }}</td>
              <td>1.8 MB</td>
              <td>
                <button class="table-action">Download</button>
                <button class="table-action">View</button>
              </td>
            </tr>
            <tr>
              <td>Financial Summary - April 2026</td>
              <td>Financial</td>
              <td>April 2026</td>
              <td>{{ formatDate('2026-05-01') }}</td>
              <td>2.7 MB</td>
              <td>
                <button class="table-action">Download</button>
                <button class="table-action">View</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Scheduled Reports -->
      <div class="scheduled-section">
        <div class="section-header">
          <h3>Scheduled Reports</h3>
          <button class="action-btn action-btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Schedule Report
          </button>
        </div>
        <div class="scheduled-grid">
          <div class="scheduled-card">
            <div class="scheduled-header">
              <div class="scheduled-name">Monthly Revenue</div>
              <div class="scheduled-badge scheduled-badge--active">Active</div>
            </div>
            <div class="scheduled-details">
              <div class="scheduled-detail">
                <span class="detail-label">Frequency:</span>
                <span class="detail-value">Monthly</span>
              </div>
              <div class="scheduled-detail">
                <span class="detail-label">Next Run:</span>
                <span class="detail-value">Jun 1, 2026</span>
              </div>
              <div class="scheduled-detail">
                <span class="detail-label">Recipients:</span>
                <span class="detail-value">3 emails</span>
              </div>
            </div>
            <div class="scheduled-actions">
              <button class="scheduled-action">Edit</button>
              <button class="scheduled-action scheduled-action--danger">Disable</button>
            </div>
          </div>
          <div class="scheduled-card">
            <div class="scheduled-header">
              <div class="scheduled-name">Weekly Collection</div>
              <div class="scheduled-badge scheduled-badge--active">Active</div>
            </div>
            <div class="scheduled-details">
              <div class="scheduled-detail">
                <span class="detail-label">Frequency:</span>
                <span class="detail-value">Weekly</span>
              </div>
              <div class="scheduled-detail">
                <span class="detail-label">Next Run:</span>
                <span class="detail-value">Jun 1, 2026</span>
              </div>
              <div class="scheduled-detail">
                <span class="detail-label">Recipients:</span>
                <span class="detail-value">5 emails</span>
              </div>
            </div>
            <div class="scheduled-actions">
              <button class="scheduled-action">Edit</button>
              <button class="scheduled-action scheduled-action--danger">Disable</button>
            </div>
          </div>
          <div class="scheduled-card">
            <div class="scheduled-header">
              <div class="scheduled-name">Quarterly Financial</div>
              <div class="scheduled-badge scheduled-badge--inactive">Paused</div>
            </div>
            <div class="scheduled-details">
              <div class="scheduled-detail">
                <span class="detail-label">Frequency:</span>
                <span class="detail-value">Quarterly</span>
              </div>
              <div class="scheduled-detail">
                <span class="detail-label">Next Run:</span>
                <span class="detail-value">-</span>
              </div>
              <div class="scheduled-detail">
                <span class="detail-label">Recipients:</span>
                <span class="detail-value">2 emails</span>
              </div>
            </div>
            <div class="scheduled-actions">
              <button class="scheduled-action">Edit</button>
              <button class="scheduled-action">Enable</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const selectedReportType = ref('revenue')

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-TZ', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
</script>

<style scoped>
.reports-container {
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

.report-types {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.report-type-card {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
  cursor: pointer;
  transition: all 0.15s;
  text-align: center;
}

.report-type-card:hover {
  border-color: #4caf76;
  transform: translateY(-2px);
}

.report-type-card--active {
  border-color: #4caf76;
  background: #f0faf3;
}

.report-type-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 12px;
}

.report-type-icon--revenue {
  background: #c8e6c9;
  color: #2d7a50;
}

.report-type-icon--collection {
  background: #ffe0b2;
  color: #e65100;
}

.report-type-icon--staff {
  background: #e1bee7;
  color: #7b1fa2;
}

.report-type-icon--financial {
  background: #bbdefb;
  color: #1565c0;
}

.report-type-name {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 4px;
}

.report-type-desc {
  font-size: 12px;
  color: #4a6357;
}

.filters-section {
  display: flex;
  gap: 16px;
  align-items: flex-end;
  margin-bottom: 24px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.filter-label {
  font-size: 12px;
  font-weight: 500;
  color: #4a6357;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 13px;
  color: #4a6357;
  background: white;
  min-width: 150px;
}

.generate-btn {
  padding: 10px 20px;
  background: #4caf76;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: background 0.15s;
  margin-left: auto;
}

.generate-btn:hover {
  background: #2d7a50;
}

.reports-section {
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

.reports-table {
  width: 100%;
  border-collapse: collapse;
}

.reports-table th {
  text-align: left;
  padding: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #4a6357;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.reports-table td {
  padding: 12px;
  font-size: 13px;
  color: #1a2e24;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.reports-table tr:last-child td {
  border-bottom: none;
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
  margin-right: 4px;
}

.table-action:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.scheduled-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.action-btn {
  padding: 8px 16px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  background: white;
  color: #4a6357;
  font-size: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
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

.scheduled-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.scheduled-card {
  background: #f9fafb;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 8px;
  padding: 16px;
}

.scheduled-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.scheduled-name {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
}

.scheduled-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.scheduled-badge--active {
  background: #e8f5e9;
  color: #2d7a50;
}

.scheduled-badge--inactive {
  background: #f5f5f5;
  color: #757575;
}

.scheduled-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 12px;
}

.scheduled-detail {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
}

.detail-label {
  color: #4a6357;
}

.detail-value {
  color: #1a2e24;
  font-weight: 500;
}

.scheduled-actions {
  display: flex;
  gap: 8px;
}

.scheduled-action {
  flex: 1;
  padding: 6px 12px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 4px;
  font-size: 11px;
  color: #4a6357;
  cursor: pointer;
  transition: all 0.15s;
}

.scheduled-action:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.scheduled-action--danger {
  color: #c62828;
}

.scheduled-action--danger:hover {
  border-color: #c62828;
  color: #b71c1c;
}

@media (max-width: 1024px) {
  .report-types {
    grid-template-columns: repeat(2, 1fr);
  }
  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }
  .scheduled-grid {
    grid-template-columns: 1fr;
  }
}
</style>
