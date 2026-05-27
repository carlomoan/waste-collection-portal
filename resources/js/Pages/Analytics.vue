<template>
  <AppLayout title="Analytics">
    <div class="analytics-container">
      <div class="header">
        <h1>Analytics</h1>
        <p>Detailed analysis and reports</p>
      </div>

      <!-- Period Selector -->
      <div class="period-selector">
        <button 
          v-for="period in periods" 
          :key="period.key"
          class="period-btn"
          :class="{ 'period-btn--active': activePeriod === period.key }"
          @click="activePeriod = period.key"
        >
          {{ period.label }}
        </button>
      </div>

      <!-- KPI Cards -->
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-label">Total Revenue</div>
          <div class="kpi-value">{{ formatCurrency(12500000) }}</div>
          <div class="kpi-change kpi-change--positive">+12.5% vs last period</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Collection Rate</div>
          <div class="kpi-value">78.4%</div>
          <div class="kpi-change kpi-change--positive">+3.2% vs last period</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Active Clients</div>
          <div class="kpi-value">1,247</div>
          <div class="kpi-change kpi-change--neutral">+15 new clients</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Outstanding Debt</div>
          <div class="kpi-value">{{ formatCurrency(3420000) }}</div>
          <div class="kpi-change kpi-change--negative">+8.1% vs last period</div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="charts-section">
        <div class="chart-card">
          <div class="chart-header">
            <h3>Revenue Trend</h3>
            <select class="chart-select">
              <option>Last 6 months</option>
              <option>Last 12 months</option>
              <option>Year to date</option>
            </select>
          </div>
          <div class="chart-placeholder">
            <p>Revenue trend chart will be displayed here</p>
          </div>
        </div>

        <div class="chart-card">
          <div class="chart-header">
            <h3>Collection by Zone</h3>
          </div>
          <div class="chart-placeholder">
            <p>Zone distribution chart will be displayed here</p>
          </div>
        </div>
      </div>

      <!-- Data Table -->
      <div class="data-table-section">
        <div class="table-header">
          <h3>Top Performing Collectors</h3>
          <button class="export-btn">Export Data</button>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Collector</th>
              <th>Zone</th>
              <th>Collections</th>
              <th>Amount</th>
              <th>Rate</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Sarah Shechambo</td>
              <td>Zone A-F</td>
              <td>156</td>
              <td>{{ formatCurrency(4500000) }}</td>
              <td>92.3%</td>
            </tr>
            <tr>
              <td>John Mwangi</td>
              <td>Zone G-J</td>
              <td>142</td>
              <td>{{ formatCurrency(3800000) }}</td>
              <td>87.5%</td>
            </tr>
            <tr>
              <td>Fatuma Makame</td>
              <td>Zone K-L</td>
              <td>128</td>
              <td>{{ formatCurrency(3200000) }}</td>
              <td>81.2%</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const activePeriod = ref('monthly')

const periods = [
  { key: 'daily', label: 'Daily' },
  { key: 'weekly', label: 'Weekly' },
  { key: 'monthly', label: 'Monthly' },
  { key: 'quarterly', label: 'Quarterly' },
  { key: 'yearly', label: 'Yearly' },
]

const formatCurrency = (value) => {
  return new Intl.NumberFormat('sw-TZ', {
    style: 'currency',
    currency: 'TZS',
    minimumFractionDigits: 0,
  }).format(value)
}
</script>

<style scoped>
.analytics-container {
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

.period-selector {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
}

.period-btn {
  padding: 8px 16px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 8px;
  background: white;
  color: #4a6357;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.15s;
}

.period-btn:hover {
  border-color: #4caf76;
  color: #2d7a50;
}

.period-btn--active {
  background: #4caf76;
  color: white;
  border-color: #4caf76;
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.kpi-card {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.kpi-label {
  font-size: 12px;
  color: #4a6357;
  margin-bottom: 8px;
}

.kpi-value {
  font-size: 24px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 8px;
}

.kpi-change {
  font-size: 11px;
}

.kpi-change--positive {
  color: #2d7a50;
}

.kpi-change--negative {
  color: #c0392b;
}

.kpi-change--neutral {
  color: #4a6357;
}

.charts-section {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 16px;
  margin-bottom: 24px;
}

.chart-card {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.chart-header h3 {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
}

.chart-select {
  padding: 6px 12px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 12px;
  color: #4a6357;
  background: white;
}

.chart-placeholder {
  height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0faf3;
  border-radius: 8px;
  color: #4a6357;
  font-size: 13px;
}

.data-table-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 20px;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.table-header h3 {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
}

.export-btn {
  padding: 8px 16px;
  background: #4caf76;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.15s;
}

.export-btn:hover {
  background: #2d7a50;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  text-align: left;
  padding: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #4a6357;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.data-table td {
  padding: 12px;
  font-size: 13px;
  color: #1a2e24;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.data-table tr:last-child td {
  border-bottom: none;
}

@media (max-width: 1024px) {
  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .charts-section {
    grid-template-columns: 1fr;
  }
}
</style>
