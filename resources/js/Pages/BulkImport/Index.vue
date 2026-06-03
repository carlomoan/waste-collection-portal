<template>
  <AppLayout title="Bulk Import">
    <div class="bulk-import-container">
      <div class="header">
        <h1>Bulk Data Import</h1>
        <p>Import daily collection data from Excel/CSV files</p>
      </div>

      <!-- Summary Cards -->
      <div class="summary-grid">
        <div class="summary-card">
          <div class="summary-label">Total Imports</div>
          <div class="summary-value">24</div>
          <div class="summary-change summary-change--positive">This month</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Records Imported</div>
          <div class="summary-value">3,456</div>
          <div class="summary-change summary-change--positive">This month</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Success Rate</div>
          <div class="summary-value">98.5%</div>
          <div class="summary-change summary-change--neutral">Last 30 days</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Last Import</div>
          <div class="summary-value">156</div>
          <div class="summary-change summary-change--neutral">Records</div>
        </div>
      </div>

      <!-- Import Section -->
      <div class="import-section">
        <div class="section-header">
          <h3>Import New Data</h3>
          <button class="action-btn" @click="downloadTemplate">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            Download Template
          </button>
        </div>
        
        <form @submit.prevent="submitImport" class="import-form">
          <div class="form-group">
            <label>Import Date</label>
            <input type="date" v-model="importDate" class="form-input" required>
          </div>
          <div class="form-group">
            <label>Upload File</label>
            <div class="file-upload" :class="{ 'file-upload--drag': isDragging }" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false" @drop.prevent="handleDrop">
              <input type="file" ref="fileInput" @change="handleFileChange" accept=".xlsx,.xls,.csv" class="file-input" required>
              <div class="file-upload-content">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="48" height="48">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                <div class="file-upload-text">
                  <div class="file-upload-title">Drag & drop your file here</div>
                  <div class="file-upload-subtitle">or click to browse</div>
                </div>
                <div class="file-upload-formats">Supported formats: XLSX, XLS, CSV (Max 10MB)</div>
              </div>
              <div v-if="selectedFile" class="selected-file">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="20" height="20">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                </svg>
                <span>{{ selectedFile.name }}</span>
                <button type="button" @click="clearFile" class="clear-file">✕</button>
              </div>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="action-btn action-btn--primary" :disabled="!selectedFile || isSubmitting">
              <svg v-if="!isSubmitting" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
              </svg>
              <span v-if="isSubmitting">Importing...</span>
              <span v-else>Import Data</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Recent Imports -->
      <div class="recent-section">
        <div class="section-header">
          <h3>Recent Imports</h3>
        </div>
        <table class="imports-table">
          <thead>
            <tr>
              <th>File Name</th>
              <th>Records</th>
              <th>Status</th>
              <th>Import Date</th>
              <th>Imported By</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="bulkImport in recentImports" :key="bulkImport.id">
              <td>
                <div class="file-name">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                  </svg>
                  {{ bulkImport.file_name }}
                </div>
              </td>
              <td>{{ bulkImport.records_imported }}</td>
              <td><span class="status-badge" :class="getStatusClass(bulkImport.status)">{{ bulkImport.status }}</span></td>
              <td>{{ formatDate(bulkImport.imported_at) }}</td>
              <td>{{ bulkImport.imported_by }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Instructions -->
      <div class="instructions-section">
        <div class="section-header">
          <h3>Import Instructions</h3>
        </div>
        <div class="instructions-content">
          <div class="instruction-step">
            <div class="step-number">1</div>
            <div class="step-content">
              <div class="step-title">Download Template</div>
              <div class="step-description">Click the "Download Template" button to get the Excel template with the correct format.</div>
            </div>
          </div>
          <div class="instruction-step">
            <div class="step-number">2</div>
            <div class="step-content">
              <div class="step-title">Fill Data</div>
              <div class="step-description">Fill in your daily collection data in the template following the column headers.</div>
            </div>
          </div>
          <div class="instruction-step">
            <div class="step-number">3</div>
            <div class="step-content">
              <div class="step-title">Upload File</div>
              <div class="step-description">Select the import date and upload your filled file using the form above.</div>
            </div>
          </div>
          <div class="instruction-step">
            <div class="step-number">4</div>
            <div class="step-content">
              <div class="step-title">Review & Confirm</div>
              <div class="step-description">The system will validate the data and import it. Check the recent imports table for status.</div>
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
import { useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const props = defineProps({
  recentImports: {
    type: Array,
    default: () => []
  }
})

const importDate = ref(new Date().toISOString().split('T')[0])
const selectedFile = ref(null)
const isDragging = ref(false)
const isSubmitting = ref(false)
const fileInput = ref(null)

const handleFileChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    selectedFile.value = file
  }
}

const handleDrop = (event) => {
  isDragging.value = false
  const file = event.dataTransfer.files[0]
  if (file && (file.name.endsWith('.xlsx') || file.name.endsWith('.xls') || file.name.endsWith('.csv'))) {
    selectedFile.value = file
  }
}

const clearFile = () => {
  selectedFile.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const submitImport = () => {
  if (!selectedFile.value) return
  
  isSubmitting.value = true
  
  const formData = new FormData()
  formData.append('file', selectedFile.value)
  formData.append('import_date', importDate.value)
  
  useForm({
    file: selectedFile.value,
    import_date: importDate.value,
  }).post(route('bulk-import.store'), {
    onSuccess: () => {
      isSubmitting.value = false
      selectedFile.value = null
      if (fileInput.value) {
        fileInput.value.value = ''
      }
    },
    onError: () => {
      isSubmitting.value = false
    },
  })
}

const downloadTemplate = () => {
  window.location.href = '/bulk-import/download-template'
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleString('en-TZ', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getStatusClass = (status) => {
  switch (status) {
    case 'completed':
      return 'status-badge--success'
    case 'pending':
      return 'status-badge--warning'
    case 'failed':
      return 'status-badge--error'
    default:
      return ''
  }
}
</script>

<style scoped>
.bulk-import-container {
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

.import-section,
.recent-section,
.instructions-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 24px;
  margin-bottom: 24px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.section-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1a2e24;
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

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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

.import-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 13px;
  font-weight: 500;
  color: #4a6357;
}

.form-input {
  padding: 10px 12px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  font-size: 13px;
  color: #1a2e24;
  background: white;
  transition: all 0.15s;
}

.form-input:focus {
  outline: none;
  border-color: #4caf76;
}

.file-upload {
  border: 2px dashed rgba(0,0,0,0.12);
  border-radius: 8px;
  padding: 40px;
  text-align: center;
  cursor: pointer;
  transition: all 0.15s;
  position: relative;
}

.file-upload--drag {
  border-color: #4caf76;
  background: rgba(76, 175, 118, 0.05);
}

.file-input {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}

.file-upload-content svg {
  color: #4a6357;
  margin-bottom: 16px;
}

.file-upload-text {
  margin-bottom: 8px;
}

.file-upload-title {
  font-size: 14px;
  font-weight: 500;
  color: #1a2e24;
  margin-bottom: 4px;
}

.file-upload-subtitle {
  font-size: 12px;
  color: #4a6357;
}

.file-upload-formats {
  font-size: 11px;
  color: #7a9489;
}

.selected-file {
  position: absolute;
  top: 12px;
  left: 12px;
  right: 12px;
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 6px;
  padding: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #1a2e24;
}

.selected-file svg {
  color: #4caf76;
  flex-shrink: 0;
}

.clear-file {
  margin-left: auto;
  background: none;
  border: none;
  color: #7a9489;
  cursor: pointer;
  font-size: 16px;
  padding: 4px;
}

.clear-file:hover {
  color: #c0392b;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
}

.imports-table {
  width: 100%;
  border-collapse: collapse;
}

.imports-table th {
  text-align: left;
  padding: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #4a6357;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.imports-table td {
  padding: 12px;
  font-size: 13px;
  color: #1a2e24;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.imports-table tr:last-child td {
  border-bottom: none;
}

.file-name {
  display: flex;
  align-items: center;
  gap: 8px;
}

.file-name svg {
  color: #4a6357;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge--success {
  background: #e8f5e9;
  color: #2d7a50;
}

.status-badge--warning {
  background: #fff3e0;
  color: #e65100;
}

.status-badge--error {
  background: #ffebee;
  color: #c62828;
}

.instructions-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.instruction-step {
  display: flex;
  gap: 16px;
}

.step-number {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #4caf76;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 600;
  flex-shrink: 0;
}

.step-content {
  flex: 1;
}

.step-title {
  font-size: 14px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 4px;
}

.step-description {
  font-size: 13px;
  color: #4a6357;
  line-height: 1.5;
}

@media (max-width: 1024px) {
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
}
</style>
