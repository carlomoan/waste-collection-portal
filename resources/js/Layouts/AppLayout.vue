<template>
  <div class="shell">
    <!-- Sidebar -->
    <aside class="sidebar" :class="{ 'sidebar--collapsed': sidebarCollapsed }">
      <div class="logo-area">
        <div class="logo-icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
               stroke-width="2" stroke="currentColor" width="20" height="20">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25
                 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
          </svg>
        </div>
        <template v-if="!sidebarCollapsed">
          <div class="logo-name">Waste Collection</div>
          <div class="logo-sub">Portal v1.0</div>
        </template>
      </div>

      <nav class="sidebar-nav">
        <template v-for="group in navGroups" :key="group.label">
          <div class="nav-section">
            <div class="nav-label" v-if="!sidebarCollapsed">{{ group.label }}</div>
            <Link
              v-for="item in group.items"
              :key="item.route"
              :href="route(item.route)"
              class="nav-item"
              :class="{ active: isActive(route(item.route)) }"
              :title="sidebarCollapsed ? item.label : ''"
            >
              <component :is="item.icon" class="nav-icon" />
              <span v-if="!sidebarCollapsed">{{ item.label }}</span>
              <span v-if="item.badge && !sidebarCollapsed"
                    class="badge" :class="item.badgeColor">
                {{ item.badge }}
              </span>
            </Link>
          </div>
        </template>
      </nav>

      <button class="collapse-btn" @click="sidebarCollapsed = !sidebarCollapsed"
              :title="sidebarCollapsed ? 'Expand' : 'Collapse'">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="2" stroke="currentColor" width="16" height="16">
          <path stroke-linecap="round" stroke-linejoin="round"
            :d="sidebarCollapsed
              ? 'M11.25 4.5l7.5 7.5-7.5 7.5m-6-15l7.5 7.5-7.5 7.5'
              : 'M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5'" />
        </svg>
      </button>
    </aside>

    <!-- Main area -->
    <div class="main">
      <!-- Top bar -->
      <header class="topbar">
        <h1 class="page-title">{{ title }}</h1>
        <div class="topbar-right">
          <div class="role-badge">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke-width="2" stroke="currentColor" width="12" height="12">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99
                   11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03
                   9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
            </svg>
            {{ $page.props.auth.user.role ?? 'Admin' }}
          </div>
          <div class="topbar-date">{{ currentDate }}</div>

          <!-- Logout Form -->
          <form method="POST" :action="route('logout')" class="logout-form">
            <button type="submit" class="logout-btn" title="Logout">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                   stroke-width="2" stroke="currentColor" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/>
              </svg>
            </button>
          </form>

          <div class="user-avatar">{{ userInitials }}</div>
        </div>
      </header>

      <!-- Page content -->
      <main class="content">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

defineProps({ title: { type: String, default: 'Dashboard' } })

const page = usePage()
const sidebarCollapsed = ref(false)

const userInitials = computed(() => {
  const name = page.props.auth?.user?.name ?? 'AG'
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
})

const currentDate = computed(() =>
  new Date().toLocaleDateString('en-TZ', { month: 'short', year: 'numeric' })
)

const isActive = (routeName) => route().current(routeName)

// --- Clean Inline SVG Icons (No spaces in variable names) ---
const IconDashboard = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg>` }
const IconChart = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>` }
const IconReceipt = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/></svg>` }
const IconUsers = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>` }
const IconAlert = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>` }
const IconCalendar = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>` }
const IconBank = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z"/></svg>` }
const IconWallet = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3"/></svg>` }
const IconCash = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg>` }
const IconFile = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>` }
const IconBadge = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>` }
const IconClock = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>` }
const IconTruck = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.256 2.256 0 0 0-1.586-.948l-1.372-.17A4.5 4.5 0 0 0 14.25 3H13.5a3 3 0 0 0-3 3v.572M8.25 18.75H6.375"/></svg>` }
const IconShield = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>` }
const IconAudit = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Zm3.75 11.625a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>` }
const IconSettings = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>` }
const IconUpload = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>` }
const IconFinance = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>` }
const IconMap = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>` }
const IconInvoice = { template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>` }

// --- Navigation Groups (100% Matched to web.php & Syntax Cleaned) ---
const navGroups = [
  {
    label: 'Overview',
    items: [
      { label: 'Dashboard', route: 'dashboard', icon: IconDashboard },
      { label: 'Analytics', route: 'analytics.index', icon: IconChart }, // Fixed: added .index
    ],
  },
  {
    label: 'Collections',
    items: [
      { label: 'Transactions', route: 'transactions.index', icon: IconReceipt },
      { label: 'Invoices', route: 'invoices.index', icon: IconInvoice },
      { label: 'Clients', route: 'clients.index', icon: IconUsers },
      { label: 'Debts', route: 'debts.index', icon: IconAlert },
      { label: 'Schedules', route: 'schedules.index', icon: IconCalendar }, // Fixed: plural schedules
      { label: 'Bulk Import', route: 'bulk-import.index', icon: IconUpload },
    ],
  },
  {
    label: 'Finance',
    items: [
      { label: 'Finance', route: 'finance.index', icon: IconFinance },
      { label: 'Banking', route: 'banking.index', icon: IconBank },
      // ⚠️ Commented out: 'expenses.index' does not exist in your web.php
      // { label: 'Expenses', route: 'expenses.index', icon: IconWallet },
      { label: 'Payroll', route: 'payroll.index', icon: IconCash },
      { label: 'Reports', route: 'reports.index', icon: IconFile },
    ],
  },
  {
    label: 'HR & Operations',
    items: [
      { label: 'Staff', route: 'staff.index', icon: IconBadge },
      { label: 'Attendance', route: 'attendance.index', icon: IconClock },
      { label: 'Vehicles', route: 'vehicles.index', icon: IconTruck },
      { label: 'Zones', route: 'zones.index', icon: IconMap },
    ],
  },
  {
    label: 'System',
    items: [
      { label: 'Users', route: 'users.index', icon: IconUsers },
      { label: 'Roles', route: 'roles.index', icon: IconShield },
      { label: 'Audit Log', route: 'audit.index', icon: IconAudit },
      { label: 'Settings', route: 'settings.index', icon: IconSettings },
    ],
  },
]
</script>

<style scoped>
:root {
  --g400: #4caf76; --g600: #2d7a50; --g800: #1a4d32; --g900: #0d2e1e;
  --sb: #1c2b24; --sl: #f8faf9; --card: #ffffff; --border: rgba(0,0,0,0.08);
  --txt: #1a2e24; --txt2: #4a6357; --txt3: #7a9489;
}
.shell { display: flex; height: 100vh; overflow: hidden; background: linear-gradient(135deg, #f0faf4 0%, #f8faf9 100%); }

/* Sidebar */
.sidebar {
  width: 240px; min-width: 240px; background: linear-gradient(180deg, #1c2b24 0%, #16261f 100%);
  display: flex; flex-direction: column; transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden; box-shadow: 4px 0 24px rgba(0, 0, 0, 0.1);
}
.sidebar--collapsed { width: 64px; min-width: 64px; }
.logo-area {
  padding: 24px 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.08);
  display: flex; flex-direction: column;
}
.logo-icon {
  width: 40px; height: 40px; background: linear-gradient(135deg, #4caf76 0%, #2d7a50 100%);
  border-radius: 10px; display: flex; align-items: center; justify-content: center;
  color: white; flex-shrink: 0; margin-bottom: 12px;
  box-shadow: 0 4px 12px rgba(76, 175, 118, 0.3);
}
.logo-name { font-size: 14px; font-weight: 700; color: #e8f5ee; letter-spacing: -0.3px; }
.logo-sub { font-size: 11px; color: #5a8a6a; margin-top: 2px; font-weight: 500; }
.sidebar-nav { flex: 1; overflow-y: auto; padding: 12px 8px; }
.sidebar-nav::-webkit-scrollbar { width: 4px; }
.sidebar-nav::-webkit-scrollbar-track { background: transparent; }
.sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }
.nav-section { padding: 8px 8px 4px; }
.nav-label {
  font-size: 10px; letter-spacing: 1.2px; text-transform: uppercase;
  color: #5a8a6a; padding: 0 8px; margin-bottom: 6px; font-weight: 600;
}
.nav-item {
  display: flex; align-items: center; gap: 10px; padding: 9px 10px;
  border-radius: 8px; cursor: pointer; color: #9abfaa; font-size: 13px;
  text-decoration: none; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  margin-bottom: 2px; white-space: nowrap; position: relative;
}
.nav-item::before {
  content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
  width: 3px; height: 0; background: #4caf76; border-radius: 0 2px 2px 0;
  transition: height 0.2s;
}
.nav-item:hover { background: rgba(76,175,118,0.12); color: #c8e8d4; transform: translateX(2px); }
.nav-item.active {
  background: rgba(76,175,118,0.18); color: #7de0a4;
  font-weight: 500;
}
.nav-item.active::before { height: 20px; }
.nav-icon { flex-shrink: 0; }
.badge {
  margin-left: auto; background: linear-gradient(135deg, #4caf76 0%, #2d7a50 100%);
  color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px;
  font-weight: 600; box-shadow: 0 2px 4px rgba(76, 175, 118, 0.2);
}
.badge--warn { background: linear-gradient(135deg, #f5c842 0%, #d4a520 100%); color: #4a3000; }
.collapse-btn {
  margin: 12px; padding: 10px; background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; color: #5a8a6a;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.collapse-btn:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.15); color: #7de0a4; }

/* Main */
.main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.topbar {
  background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(0,0,0,0.06);
  padding: 0 24px; height: 60px; display: flex; align-items: center;
  gap: 16px; position: sticky; top: 0; z-index: 10; flex-shrink: 0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}
.page-title { font-size: 18px; font-weight: 700; color: #1a2e24; letter-spacing: -0.3px; }
.topbar-right { margin-left: auto; display: flex; align-items: center; gap: 12px; }
.role-badge {
  display: flex; align-items: center; gap: 5px;
  background: linear-gradient(135deg, #f0faf3 0%, #e8f5e9 100%);
  border: 1px solid #a8ddb8; color: #2d7a50;
  font-size: 11px; padding: 4px 10px; border-radius: 14px; font-weight: 600;
  box-shadow: 0 1px 2px rgba(76, 175, 118, 0.1);
}
.topbar-date { font-size: 12px; color: #7a9489; font-weight: 500; }
.logout-form { display: contents; }
.logout-btn {
  padding: 8px; background: transparent; border: none;
  border-radius: 8px; color: #5a8a6a; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.logout-btn:hover { background: rgba(220, 38, 38, 0.08); color: #dc2626; transform: scale(1.05); }
.user-avatar {
  width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #4caf76 0%, #2d7a50 100%);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; color: white;
  box-shadow: 0 2px 8px rgba(76, 175, 118, 0.3); border: 2px solid white;
}
.content { flex: 1; overflow-y: auto; padding: 24px; }
.content::-webkit-scrollbar { width: 8px; }
.content::-webkit-scrollbar-track { background: transparent; }
.content::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
.content::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

@media (max-width: 768px) {
  .sidebar { position: fixed; left: 0; top: 0; height: 100%; z-index: 50; transform: translateX(-100%); }
  .sidebar--mobile-open { transform: translateX(0); }
  .content { padding: 16px; }
}
</style>
