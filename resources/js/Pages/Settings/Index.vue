<template>
  <AppLayout title="Settings">
    <div class="settings-container">
      <div class="header">
        <h1>Settings</h1>
        <p>Configure system settings</p>
      </div>

      <!-- Settings Navigation -->
      <div class="settings-nav">
        <button class="nav-btn" :class="{ 'nav-btn--active': activeTab === 'general' }" @click="setActiveTab('general')">General</button>
        <button class="nav-btn" :class="{ 'nav-btn--active': activeTab === 'billing' }" @click="setActiveTab('billing')">Billing</button>
        <button class="nav-btn" :class="{ 'nav-btn--active': activeTab === 'notifications' }" @click="setActiveTab('notifications')">Notifications</button>
        <button class="nav-btn" :class="{ 'nav-btn--active': activeTab === 'security' }" @click="setActiveTab('security')">Security & Maintenance</button>
      </div>

      <!-- General Settings -->
      <div class="settings-section" v-show="activeTab === 'general'">
        <div class="section-header">
          <h3>General Settings</h3>
          <button class="action-btn action-btn--primary" @click="saveSettings">Save Changes</button>
        </div>
        <div class="settings-form">
          <div class="form-group">
            <label>Company Name</label>
            <input type="text" class="form-input" v-model="form.company_name">
          </div>
          <div class="form-group">
            <label>Business Email</label>
            <input type="email" class="form-input" v-model="form.email">
          </div>
          <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" class="form-input" v-model="form.phone">
          </div>
          <div class="form-group">
            <label>Address</label>
            <textarea class="form-input" rows="3" v-model="form.address"></textarea>
          </div>
          <div class="form-group">
            <label>Currency</label>
            <select class="form-input" v-model="form.currency">
              <option value="TZS">Tanzanian Shilling (TZS)</option>
              <option value="USD">US Dollar (USD)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Billing Settings -->
      <div class="settings-section" v-show="activeTab === 'billing'">
        <div class="section-header">
          <h3>Billing Settings</h3>
          <button class="action-btn action-btn--primary" @click="saveSettings">Save Changes</button>
        </div>
        <div class="settings-form">
          <div class="form-group">
            <label>Default Monthly Fee</label>
            <input type="number" class="form-input" v-model="form.default_monthly_fee">
          </div>
          <div class="form-group">
            <label>Payment Due Days</label>
            <input type="number" class="form-input" v-model="form.payment_due_days">
          </div>
          <div class="form-group">
            <label>Late Fee Percentage (%)</label>
            <input type="number" class="form-input" v-model="form.late_fee_percentage" step="0.1">
          </div>
          <div class="form-group">
            <label>Tax Rate (%)</label>
            <input type="number" class="form-input" v-model="form.tax_rate" step="0.1">
          </div>
          <div class="form-group">
            <label>Invoice Prefix</label>
            <input type="text" class="form-input" v-model="form.invoice_prefix">
          </div>
        </div>
      </div>

      <!-- Notification Settings -->
      <div class="settings-section" v-show="activeTab === 'notifications'">
        <div class="section-header">
          <h3>Notification Settings</h3>
          <div style="display: flex; gap: 8px;">
            <button class="action-btn" @click="testEmail">Test Email</button>
            <button class="action-btn action-btn--primary" @click="saveSettings">Save Changes</button>
          </div>
        </div>
        <div class="settings-form">
          <div class="toggle-group">
            <div class="toggle-info">
              <div class="toggle-label">Email Notifications</div>
              <div class="toggle-description">Receive email alerts for important events</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="form.email_notifications">
              <span class="toggle-slider"></span>
            </label>
          </div>
          <div class="toggle-group">
            <div class="toggle-info">
              <div class="toggle-label">SMS Notifications</div>
              <div class="toggle-description">Receive SMS alerts for critical events</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="form.sms_notifications">
              <span class="toggle-slider"></span>
            </label>
          </div>
          <div class="toggle-group">
            <div class="toggle-info">
              <div class="toggle-label">Payment Reminders</div>
              <div class="toggle-description">Automatically send payment reminders to clients</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="form.payment_reminders">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>
      </div>

      <!-- Security & Danger Zone -->
      <div class="settings-section" v-show="activeTab === 'security'">
        <div class="section-header">
          <h3>Security Settings</h3>
          <button class="action-btn action-btn--primary" @click="saveSettings">Save Changes</button>
        </div>
        <div class="settings-form">
          <div class="form-group">
            <label>Session Timeout (minutes)</label>
            <input type="number" class="form-input" v-model="form.session_timeout">
          </div>
          <div class="toggle-group">
            <div class="toggle-info">
              <div class="toggle-label">Two-Factor Authentication</div>
              <div class="toggle-description">Require 2FA for all admin accounts</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="form.two_factor_auth">
              <span class="toggle-slider"></span>
            </label>
          </div>
          <div class="toggle-group">
            <div class="toggle-info">
              <div class="toggle-label">Audit Logging</div>
              <div class="toggle-description">Log all user activities for security review</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="form.audit_logging">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>
      </div>

      <!-- Danger Zone (System Maintenance) -->
      <div class="danger-section" v-show="activeTab === 'security'">
        <div class="section-header">
          <h3>Danger Zone (Superuser Maintenance)</h3>
        </div>
        <div class="danger-content">
          <div class="danger-item">
            <div class="danger-info">
              <div class="danger-label">Clear Cache</div>
              <div class="danger-description">Clear application cache to refresh settings</div>
            </div>
            <button class="danger-btn danger-btn--warning" @click="clearCache">Clear Cache</button>
          </div>
          <div class="danger-item">
            <div class="danger-info">
              <div class="danger-label">Run Backup</div>
              <div class="danger-description">Create a full system backup</div>
            </div>
            <button class="danger-btn danger-btn--warning" @click="runBackup">Run Backup</button>
          </div>
          <div class="danger-item">
            <div class="danger-info">
              <div class="danger-label">Export Database</div>
              <div class="danger-description">Download complete database data as SQL</div>
            </div>
            <button class="danger-btn danger-btn--warning" @click="exportDatabase">Export Database</button>
          </div>
          <div class="danger-item">
            <div class="danger-info">
              <div class="danger-label">Restore Database</div>
              <div class="danger-description">Restore database from SQL file (WARNING: Overwrites data)</div>
            </div>
            <button class="danger-btn danger-btn--danger" @click="restoreDatabase">Restore Database</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  settings: { type: Object, default: () => ({}) }
})

const activeTab = ref('general')
const setActiveTab = (tab) => { activeTab.value = tab }

// Flatten settings into a single useForm instance for easier submission
const form = useForm({
  company_name: props.settings.company_name || '',
  email: props.settings.email || '',
  phone: props.settings.phone || '',
  address: props.settings.address || '',
  currency: props.settings.currency || 'TZS',
  default_monthly_fee: props.settings.default_monthly_fee || 3000,
  payment_due_days: props.settings.payment_due_days || 30,
  late_fee_percentage: props.settings.late_fee_percentage || 10,
  tax_rate: props.settings.tax_rate || 18,
  invoice_prefix: props.settings.invoice_prefix || 'INV-',
  email_notifications: props.settings.email_notifications ?? true,
  sms_notifications: props.settings.sms_notifications ?? false,
  payment_reminders: props.settings.payment_reminders ?? true,
  session_timeout: props.settings.session_timeout || 30,
  two_factor_auth: props.settings.two_factor_auth ?? false,
  audit_logging: props.settings.audit_logging ?? true,
})

const saveSettings = () => {
  form.post('/settings', {
    preserveScroll: true,
    onSuccess: () => alert('Settings saved successfully!'),
    onError: () => alert('Failed to save settings.')
  })
}

const testEmail = () => {
  const email = prompt('Enter email address to send test email:', form.email)
  if (email) {
    router.post('/settings/test-email', { test_email: email }, {
      onSuccess: () => alert('Test email sent!'),
      onError: () => alert('Failed to send test email.')
    })
  }
}

const clearCache = () => {
  if (confirm('Clear all application cache?')) {
    router.post('/settings/clear-cache', {}, { onSuccess: () => alert('Cache cleared!') })
  }
}

const runBackup = () => {
  if (confirm('Initiate full system backup?')) {
    router.post('/settings/backup', {}, { onSuccess: () => alert('Backup initiated!') })
  }
}

const exportDatabase = () => {
  if (confirm('Download full database export?')) {
    window.location.href = '/settings/export-database'
  }
}

const restoreDatabase = () => {
  const fileInput = document.createElement('input')
  fileInput.type = 'file'
  fileInput.accept = '.sql'
  fileInput.onchange = (e) => {
    const file = e.target.files[0]
    if (file && confirm('WARNING: This will OVERWRITE existing data! Are you sure?')) {
      const formData = new FormData()
      formData.append('sql_file', file)
      router.post('/settings/restore-database', formData, {
        onSuccess: () => alert('Database restored!'),
        onError: () => alert('Failed to restore database.')
      })
    }
  }
  fileInput.click()
}
</script>

<style scoped>
.settings-container { padding: 20px; max-width: 1200px; margin: 0 auto; }
.header { margin-bottom: 24px; }
.header h1 { font-size: 24px; font-weight: 600; color: #1a2e24; margin-bottom: 4px; }
.header p { color: #4a6357; font-size: 14px; }
.settings-nav { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid rgba(0,0,0,0.08); padding-bottom: 16px; flex-wrap: wrap; }
.nav-btn { padding: 10px 20px; border: none; border-radius: 8px; background: white; color: #4a6357; font-size: 13px; cursor: pointer; transition: all 0.15s; font-weight: 500; }
.nav-btn:hover { background: #f9fafb; color: #1a2e24; }
.nav-btn--active { background: #4caf76; color: white; }
.nav-btn--active:hover { background: #2d7a50; }
.settings-section { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; padding: 24px; margin-bottom: 24px; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid rgba(0,0,0,0.08); }
.section-header h3 { font-size: 16px; font-weight: 600; color: #1a2e24; }
.action-btn { padding: 10px 20px; border: 1px solid rgba(0,0,0,0.08); border-radius: 8px; background: white; color: #4a6357; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.15s; }
.action-btn:hover { border-color: #4caf76; color: #2d7a50; }
.action-btn--primary { background: #4caf76; color: white; border-color: #4caf76; }
.action-btn--primary:hover { background: #2d7a50; border-color: #2d7a50; }
.settings-form { display: flex; flex-direction: column; gap: 20px; max-width: 600px; }
.form-group { display: flex; flex-direction: column; gap: 8px; }
.form-group label { font-size: 13px; font-weight: 500; color: #4a6357; }
.form-input { padding: 10px 12px; border: 1px solid rgba(0,0,0,0.08); border-radius: 6px; font-size: 13px; color: #1a2e24; background: white; transition: all 0.15s; }
.form-input:focus { outline: none; border-color: #4caf76; }
.toggle-group { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(0,0,0,0.04); }
.toggle-group:last-child { border-bottom: none; }
.toggle-info { display: flex; flex-direction: column; gap: 4px; }
.toggle-label { font-size: 14px; font-weight: 500; color: #1a2e24; }
.toggle-description { font-size: 12px; color: #4a6357; }
.toggle-switch { position: relative; display: inline-block; width: 48px; height: 24px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #d1d5db; transition: 0.3s; border-radius: 24px; }
.toggle-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: 0.3s; border-radius: 50%; }
.toggle-switch input:checked + .toggle-slider { background-color: #4caf76; }
.toggle-switch input:checked + .toggle-slider:before { transform: translateX(24px); }
.danger-section { background: white; border: 1px solid #fee2e2; border-radius: 10px; padding: 24px; margin-bottom: 24px; }
.danger-content { display: flex; flex-direction: column; gap: 16px; }
.danger-item { display: flex; justify-content: space-between; align-items: center; padding: 16px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; }
.danger-info { display: flex; flex-direction: column; gap: 4px; }
.danger-label { font-size: 14px; font-weight: 600; color: #991b1b; }
.danger-description { font-size: 12px; color: #7f1d1d; }
.danger-btn { padding: 8px 16px; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; transition: all 0.15s; border: none; }
.danger-btn--warning { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
.danger-btn--warning:hover { background: #fcd34d; }
.danger-btn--danger { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
.danger-btn--danger:hover { background: #fca5a5; }
@media (max-width: 1024px) {
  .section-header { flex-direction: column; align-items: flex-start; gap: 12px; }
  .danger-item { flex-direction: column; align-items: flex-start; gap: 12px; }
  .danger-btn { width: 100%; }
}
</style>
