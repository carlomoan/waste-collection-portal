<template>
  <div class="error-page">
    <div class="error-container">
      <div class="error-code">404</div>
      <h1 class="error-title">Page Not Found</h1>
      <p class="error-message">
        The page you're looking for doesn't exist or has been moved.
      </p>
      <Link :href="'/'" class="error-button">
        Go to Dashboard
      </Link>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<style scoped>
.error-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f0faf3 0%, #e6f4ea 100%);
}

.error-container {
  text-align: center;
  padding: 40px;
}

.error-code {
  font-size: 120px;
  font-weight: 700;
  color: #4caf76;
  line-height: 1;
  margin-bottom: 20px;
}

.error-title {
  font-size: 32px;
  font-weight: 600;
  color: #1a2e24;
  margin-bottom: 12px;
}

.error-message {
  font-size: 16px;
  color: #4a6357;
  margin-bottom: 32px;
  max-width: 400px;
}

.error-button {
  display: inline-block;
  padding: 12px 24px;
  background: #4caf76;
  color: white;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 500;
  transition: background 0.2s;
}

.error-button:hover {
  background: #2d7a50;
}
</style>
