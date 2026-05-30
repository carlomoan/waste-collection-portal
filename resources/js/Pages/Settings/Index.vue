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
        <button class="nav-btn" :class="{ 'nav-btn--active': activeTab === 'security' }" @click="setActiveTab('security')">Security</button>
        <button class="nav-btn" :class="{ 'nav-btn--active': activeTab === 'integrations' }" @click="setActiveTab('integrations')">Integrations</button>
      </div>

      <!-- General Settings -->
      <div class="settings-section" v-show="activeTab === 'general'">
        <div class="section-header">
          <h3>General Settings</h3>
          <button class="action-btn action-btn--primary">Save Changes</button>
        </div>
        
        <div class="settings-form">
          <div class="form-group">
            <label>Company Name</label>
            <input type="text" class="form-input" v-model="form.general.company_name">
          </div>
          <div class="form-group">
            <label>Business Email</label>
            <input type="email" class="form-input" v-model="form.general.email">
          </div>
          <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" class="form-input" v-model="form.general.phone">
          </div>
          <div class="form-group">
            <label>Address</label>
            <textarea class="form-input" rows="3" v-model="form.general.address"></textarea>
          </div>
          <div class="form-group">
            <label>Timezone</label>
            <select class="form-input" v-model="form.general.timezone">
              <option value="Africa/Dar_es_Salaam">Africa/Dar es Salaam</option>
              <option value="Africa/Nairobi">Africa/Nairobi</option>
              <option value="UTC">UTC</option>
            </select>
          </div>
          <div class="form-group">
            <label>Currency</label>
            <select class="form-input" v-model="form.general.currency">
              <option value="TZS">Tanzanian Shilling (TZS)</option>
              <option value="USD">US Dollar (USD)</option>
              <option value="EUR">Euro (EUR)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Billing Settings -->
      <div class="settings-section" v-show="activeTab === 'billing'">
        <div class="section-header">
          <h3>Billing Settings</h3>
          <button class="action-btn action-btn--primary">Save Changes</button>
        </div>
        
        <div class="settings-form">
          <div class="form-group">
            <label>Default Monthly Fee</label>
            <input type="text" class="form-input" v-model="form.billing.default_monthly_fee">
          </div>
          <div class="form-group">
            <label>Payment Due Days</label>
            <input type="number" class="form-input" v-model="form.billing.payment_due_days">
          </div>
          <div class="form-group">
            <label>Late Fee Percentage</label>
            <input type="number" class="form-input" v-model="form.billing.late_fee_percentage" step="0.5">
          </div>
          <div class="form-group">
            <label>Tax Rate (%)</label>
            <input type="number" class="form-input" v-model="form.billing.tax_rate" step="0.1">
          </div>
          <div class="form-group">
            <label>Invoice Prefix</label>
            <input type="text" class="form-input" v-model="form.billing.invoice_prefix">
          </div>
        </div>
      </div>

      <!-- Notification Settings -->
      <div class="settings-section" v-show="activeTab === 'notifications'">
        <div class="section-header">
          <h3>Notification Settings</h3>
          <button class="action-btn action-btn--primary">Save Changes</button>
        </div>
        
        <div class="settings-form">
          <div class="toggle-group">
            <div class="toggle-info">
              <div class="toggle-label">Email Notifications</div>
              <div class="toggle-description">Receive email alerts for important events</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="form.notifications.email_enabled">
              <span class="toggle-slider"></span>
            </label>
          </div>
          <div class="toggle-group">
            <div class="toggle-info">
              <div class="toggle-label">SMS Notifications</div>
              <div class="toggle-description">Receive SMS alerts for critical events</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="form.notifications.sms_enabled">
              <span class="toggle-slider"></span>
            </label>
          </div>
          <div class="toggle-group">
            <div class="toggle-info">
              <div class="toggle-label">Payment Reminders</div>
              <div class="toggle-description">Automatically send payment reminders to clients</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="form.notifications.payment_reminders">
              <span class="toggle-slider"></span>
            </label>
          </div>
          <div class="toggle-group">
            <div class="toggle-info">
              <div class="toggle-label">Collection Alerts</div>
              <div class="toggle-description">Notify when collections are completed</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="form.notifications.collection_alerts">
              <span class="toggle-slider"></span>
            </label>
          </div>
          <div class="toggle-group">
            <div class="toggle-info">
              <div class="toggle-label">System Updates</div>
              <div class="toggle-description">Receive notifications about system updates</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="form.notifications.system_updates">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>
      </div>

      <!-- Security Settings -->
      <div class="settings-section" v-show="activeTab === 'security'">
        <div class="section-header">
          <h3>Security Settings</h3>
          <button class="action-btn action-btn--primary">Save Changes</button>
        </div>
        
        <div class="settings-form">
          <div class="form-group">
            <label>Session Timeout (minutes)</label>
            <input type="number" class="form-input" value="60">
          </div>
          <div class="toggle-group">
            <div class="toggle-info">
              <div class="toggle-label">Two-Factor Authentication</div>
              <div class="toggle-description">Require 2FA for all admin accounts</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="form.security.two_factor_auth">
              <span class="toggle-slider"></span>
            </label>
          </div>
          <div class="toggle-group">
            <div class="toggle-info">
              <div class="toggle-label">IP Whitelist</div>
              <div class="toggle-description">Restrict access to specific IP addresses</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="form.security.ip_whitelist">
              <span class="toggle-slider"></span>
            </label>
          </div>
          <div class="toggle-group">
            <div class="toggle-info">
              <div class="toggle-label">Audit Logging</div>
              <div class="toggle-description">Log all user activities for security review</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="form.security.audit_logging">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>
      </div>

      <!-- Danger Zone -->
      <div class="danger-section" v-show="activeTab === 'security'">
        <div class="section-header">
          <h3>Danger Zone</h3>
        </div>
        <div class="danger-content">
          <div class="danger-item">
            <div class="danger-info">
              <div class="danger-label">Export All Data</div>
              <div class="danger-description">Download all system data as JSON</div>
            </div>
            <button class="danger-btn danger-btn--warning">Export Data</button>
          </div>
          <div class="danger-item">
            <div class="danger-info">
              <div class="danger-label">Reset System</div>
              <div class="danger-description">Reset all settings to default values</div>
            </div>
            <button class="danger-btn danger-btn--danger">Reset Settings</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const activeTab = ref('general')
const setActiveTab = (tab) => { activeTab.value = tab }

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({})
  }
})

const form = useForm({
  general: {
    company_name: props.settings.general?.company_name || '',
    email: props.settings.general?.email || '',
    phone: props.settings.general?.phone || '',
    address: props.settings.general?.address || '',
    timezone: props.settings.general?.timezone || 'Africa/Dar_es_Salaam',
    currency: props.settings.general?.currency || 'TZS',
  },
  billing: {
    default_monthly_fee: props.settings.billing?.default_monthly_fee || '',
    payment_due_days: props.settings.billing?.payment_due_days || 30,
    late_fee_percentage: props.settings.billing?.late_fee_percentage || 10,
    tax_rate: props.settings.billing?.tax_rate || 18,
    invoice_prefix: props.settings.billing?.invoice_prefix || 'INV-',
  },
  notifications: {
    email_enabled: props.settings.notifications?.email_enabled ?? true,
    sms_enabled: props.settings.notifications?.sms_enabled ?? true,
    payment_reminders: props.settings.notifications?.payment_reminders ?? true,
    collection_alerts: props.settings.notifications?.collection_alerts ?? false,
    system_updates: props.settings.notifications?.system_updates ?? true,
  },
  security: {
    two_factor_auth: props.settings.security?.two_factor_auth ?? true,
    ip_whitelist: props.settings.security?.ip_whitelist ?? false,
    audit_logging: props.settings.security?.audit_logging ?? true,
  }
})

const saveSettings = (section) => {
  form.put(route('settings.update'), {
    onSuccess: () => {
      // Handle success
    }
  })
}
</script>

<style scoped>
.settings-container {
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

.settings-nav {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  border-bottom: 1px solid rgba(0,0,0,0.08);
  padding-bottom: 16px;
}

.nav-btn {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  background: white;
  color: #4a6357;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.15s;
}

.nav-btn:hover {
  background: #f9fafb;
  color: #1a2e24;
}

.nav-btn--active {
  background: #4caf76;
  color: white;
}

.nav-btn--active:hover {
  background: #2d7a50;
}

.settings-section {
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

.action-btn--primary {
  background: #4caf76;
  color: white;
  border-color: #4caf76;
}

.action-btn--primary:hover {
  background: #2d7a50;
  border-color: #2d7a50;
}

.settings-form {
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

.form-input::placeholder {
  color: #9ca3af;
}

.toggle-group {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid rgba(0,0,0,0.04);
}

.toggle-group:last-child {
  border-bottom: none;
}

.toggle-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.toggle-label {
  font-size: 14px;
  font-weight: 500;
  color: #1a2e24;
}

.toggle-description {
  font-size: 12px;
  color: #4a6357;
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 24px;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #d1d5db;
  transition: 0.3s;
  border-radius: 24px;
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
}

.toggle-switch input:checked + .toggle-slider {
  background-color: #4caf76;
}

.toggle-switch input:checked + .toggle-slider:before {
  transform: translateX(24px);
}

.danger-section {
  background: white;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  padding: 24px;
  border-color: #fee2e2;
}

.danger-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.danger-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
}

.danger-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.danger-label {
  font-size: 14px;
  font-weight: 600;
  color: #991b1b;
}

.danger-description {
  font-size: 12px;
  color: #7f1d1d;
}

.danger-btn {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s;
}

.danger-btn--warning {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fcd34d;
}

.danger-btn--warning:hover {
  background: #fcd34d;
}

.danger-btn--danger {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fca5a5;
}

.danger-btn--danger:hover {
  background: #fca5a5;
}

@media (max-width: 1024px) {
  .settings-nav {
    flex-wrap: wrap;
  }
  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  .danger-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
}
</style>
