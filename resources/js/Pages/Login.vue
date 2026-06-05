<template>
  <div class="login-container">
    <div class="login-card">
      <!-- Logo & Header -->
      <div class="login-header">
        <div class="logo-icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="32" height="32">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </div>
        <h1 class="login-title">Waste Collection Portal</h1>
        <p class="login-subtitle">Sign in to access your dashboard</p>
      </div>

      <!-- Error Alert -->
      <div v-if="error" class="error-alert">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
        </svg>
        <span>{{ error }}</span>
      </div>

      <!-- Login Form -->
      <form class="login-form" @submit.prevent="submitLogin">
        <div class="form-group">
          <label for="login">NIDA ID / Username / Email</label>
          <div class="input-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
            <input
              id="login"
              v-model="form.login"
              type="text"
              autocomplete="username"
              required
              placeholder="NIDA number, username, or email"
              :class="{ 'input-error': form.errors.login }"
            />
          </div>
          <span v-if="form.errors.login" class="error-text">{{ form.errors.login }}</span>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="current-password"
              required
              placeholder="••••••••"
              :class="{ 'input-error': form.errors.password }"
            />
            <button type="button" @click="showPassword = !showPassword" class="toggle-password">
              <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
              </svg>
            </button>
          </div>
          <span v-if="form.errors.password" class="error-text">{{ form.errors.password }}</span>
        </div>

        <button type="submit" class="submit-btn" :disabled="loading">
          <svg v-if="loading" class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ loading ? 'Signing in...' : 'Sign in' }}
        </button>
      </form>

      <!-- Footer -->
      <div class="login-footer">
        <p class="footer-text">Secure login powered by Waste Collection Portal</p>
      </div>
    </div>

    <!-- Background Decoration -->
    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>
    <div class="bg-decoration bg-decoration-3"></div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  login: '',
  password: '',
})

const loading = ref(false)
const error = ref('')
const showPassword = ref(false)

const submitLogin = () => {
  loading.value = true
  error.value = ''

  form.post('/login', {
    onSuccess: () => {
      loading.value = false
    },
    onError: (errors) => {
      loading.value = false
      error.value = errors.login || errors.password || 'Invalid credentials. Please try again.'
    },
  })
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f0faf4 0%, #e8f5e9 50%, #dcedc8 100%);
  padding: 20px;
  position: relative;
  overflow: hidden;
}

.login-card {
  width: 100%;
  max-width: 420px;
  background: white;
  border-radius: 20px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.05);
  padding: 48px;
  position: relative;
  z-index: 10;
}

.login-header {
  text-align: center;
  margin-bottom: 32px;
}

.logo-icon {
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, #4caf76 0%, #2d7a50 100%);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  color: white;
  box-shadow: 0 10px 25px -5px rgba(76, 175, 118, 0.4);
}

.login-title {
  font-size: 26px;
  font-weight: 700;
  color: #1a2e24;
  margin: 0 0 8px;
  letter-spacing: -0.5px;
}

.login-subtitle {
  font-size: 14px;
  color: #7a9489;
  margin: 0;
}

.error-alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 10px;
  color: #991b1b;
  font-size: 13px;
  margin-bottom: 24px;
}

.error-alert svg {
  flex-shrink: 0;
  color: #dc2626;
}

.login-form {
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
  font-weight: 600;
  color: #4a6357;
  letter-spacing: 0.3px;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-wrapper svg {
  position: absolute;
  left: 14px;
  color: #9ca3af;
  pointer-events: none;
  transition: color 0.2s;
}

.input-wrapper input {
  width: 100%;
  padding: 12px 14px 12px 44px;
  border: 1.5px solid #e5e7eb;
  border-radius: 10px;
  font-size: 14px;
  color: #1a2e24;
  background: #f9fafb;
  transition: all 0.2s;
}

.input-wrapper input::placeholder {
  color: #9ca3af;
}

.input-wrapper input:focus {
  outline: none;
  border-color: #4caf76;
  background: white;
  box-shadow: 0 0 0 3px rgba(76, 175, 118, 0.1);
}

.input-wrapper input:focus + svg {
  color: #4caf76;
}

.input-wrapper input.input-error {
  border-color: #dc2626;
  background: #fef2f2;
}

.input-wrapper input.input-error:focus {
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

.toggle-password {
  position: absolute;
  right: 12px;
  padding: 4px;
  background: none;
  border: none;
  cursor: pointer;
  color: #9ca3af;
  transition: color 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.toggle-password:hover {
  color: #4a6357;
}

.error-text {
  font-size: 12px;
  color: #dc2626;
}

.submit-btn {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, #4caf76 0%, #2d7a50 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: 0 4px 14px -2px rgba(76, 175, 118, 0.4);
  margin-top: 8px;
}

.submit-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px -2px rgba(76, 175, 118, 0.5);
}

.submit-btn:active:not(:disabled) {
  transform: translateY(0);
}

.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.login-footer {
  text-align: center;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
}

.footer-text {
  font-size: 12px;
  color: #9ca3af;
  margin: 0;
}

.bg-decoration {
  position: absolute;
  border-radius: 50%;
  opacity: 0.1;
  pointer-events: none;
}

.bg-decoration-1 {
  width: 400px;
  height: 400px;
  background: #4caf76;
  top: -200px;
  right: -100px;
}

.bg-decoration-2 {
  width: 300px;
  height: 300px;
  background: #2d7a50;
  bottom: -150px;
  left: -100px;
}

.bg-decoration-3 {
  width: 200px;
  height: 200px;
  background: #81c784;
  bottom: 100px;
  right: 50px;
}

@media (max-width: 480px) {
  .login-card {
    padding: 32px 24px;
  }
  
  .login-title {
    font-size: 22px;
  }
  
  .bg-decoration {
    display: none;
  }
}
</style>
