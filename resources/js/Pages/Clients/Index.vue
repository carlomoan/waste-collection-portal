<template>
  <AppLayout title="Client Registry">

    <div class="page-actions">
      <Link :href="route('clients.create')" class="btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke-width="2" stroke="currentColor" width="14" height="14">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Add Client
      </Link>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <input v-model="search" class="search-input" placeholder="Search name, phone, client number…" />
      <select v-model="filterZone" class="filter-select">
        <option value="">All Zones</option>
        <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }}</option>
      </select>
      <select v-model="filterType" class="filter-select">
        <option value="">All Types</option>
        <option value="residential">Residential</option>
        <option value="commercial">Commercial</option>
      </select>
      <select v-model="filterStatus" class="filter-select">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
        <option value="suspended">Suspended</option>
      </select>
    </div>

    <!-- Stats strip -->
    <div class="summary-strip">
      <div class="strip-item">
        <span class="strip-label">Total Clients</span>
        <span class="strip-val">{{ filteredClients.length }}</span>
      </div>
      <div class="strip-item">
        <span class="strip-label">Active</span>
        <span class="strip-val green">{{ activeCount }}</span>
      </div>
      <div class="strip-item">
        <span class="strip-label">With Debt</span>
        <span class="strip-val red">{{ debtCount }}</span>
      </div>
      <div class="strip-item">
        <span class="strip-label">Monthly Revenue</span>
        <span class="strip-val">{{ formatTZS(monthlyRevenue) }}</span>
      </div>
    </div>

    <!-- Table -->
    <div class="card">
      <table class="clients-table">
        <thead>
          <tr>
            <th>Client #</th>
            <th>Name</th>
            <th>Zone</th>
            <th>Type</th>
            <th>Monthly Fee</th>
            <th>Outstanding</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="client in paginatedClients" :key="client.id">
            <td class="td-num">{{ client.client_number }}</td>
            <td>
              <div class="client-cell">
                <div class="client-av">{{ initials(client.name) }}</div>
                <div>
                  <div class="client-name">{{ client.name }}</div>
                  <div class="client-phone">{{ client.phone ?? '—' }}</div>
                </div>
              </div>
            </td>
            <td>
              <span class="zone-badge" :style="{ background: client.zone_color + '22', color: client.zone_color }">
                {{ client.zone_name }}
              </span>
            </td>
            <td class="td-type">{{ client.client_type }}</td>
            <td class="td-fee">{{ formatTZS(client.monthly_fee) }}</td>
            <td>
              <span v-if="client.outstanding > 0" class="debt-amount">
                {{ formatTZS(client.outstanding) }}
              </span>
              <span v-else class="no-debt">—</span>
            </td>
            <td>
              <span class="status-badge" :class="`status--${client.status}`">
                {{ client.status }}
              </span>
            </td>
            <td class="td-actions">
              <Link :href="route('clients.show', client.id)" class="action-link">View</Link>
              <Link :href="route('clients.edit', client.id)" class="action-link">Edit</Link>
            </td>
          </tr>
          <tr v-if="filteredClients.length === 0">
            <td colspan="8" class="empty-row">No clients found.</td>
          </tr>
        </tbody>
      </table>

      <div class="pagination">
        <button class="page-btn" :disabled="currentPage === 1" @click="currentPage--">← Prev</button>
        <span class="page-info">Page {{ currentPage }} of {{ totalPages }}</span>
        <button class="page-btn" :disabled="currentPage === totalPages" @click="currentPage++">Next →</button>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  clients: { type: Array, default: () => [] },
  zones:   { type: Array, default: () => [] },
})

const search       = ref('')
const filterZone   = ref('')
const filterType   = ref('')
const filterStatus = ref('')
const currentPage  = ref(1)
const perPage      = 25

const filteredClients = computed(() => props.clients.filter(c => {
  const q = search.value.toLowerCase()
  return (!q || c.name.toLowerCase().includes(q) || c.client_number.includes(q) || (c.phone ?? '').includes(q))
    && (!filterZone.value   || c.zone_id == filterZone.value)
    && (!filterType.value   || c.category === filterType.value)
    && (!filterStatus.value || c.status === filterStatus.value)
}))

const totalPages       = computed(() => Math.max(1, Math.ceil(filteredClients.value.length / perPage)))
const paginatedClients = computed(() =>
  filteredClients.value.slice((currentPage.value - 1) * perPage, currentPage.value * perPage)
)
const activeCount    = computed(() => filteredClients.value.filter(c => c.status === 'active').length)
const debtCount      = computed(() => filteredClients.value.filter(c => c.outstanding > 0).length)
const monthlyRevenue = computed(() => filteredClients.value.filter(c => c.status === 'active').reduce((s, c) => s + c.monthly_fee, 0))

const formatTZS = v => new Intl.NumberFormat('sw-TZ', { minimumFractionDigits: 0 }).format(v)
const initials  = n => n.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
</script>

<style scoped>
.page-actions { display: flex; justify-content: flex-end; margin-bottom: 12px; }
.btn-primary {
  display: flex; align-items: center; gap: 6px;
  padding: 8px 14px; background: #2d7a50; color: #fff;
  border-radius: 8px; font-size: 12px; text-decoration: none;
  font-weight: 500; transition: background 0.15s;
}
.btn-primary:hover { background: #1a4d32; }

.filters-bar { display: flex; gap: 10px; margin-bottom: 12px; flex-wrap: wrap; }
.search-input {
  flex: 1; min-width: 200px; padding: 7px 12px;
  border: 1px solid rgba(0,0,0,0.12); border-radius: 7px;
  font-size: 12px; background: #fff;
}
.search-input:focus { outline: none; border-color: #4caf76; }
.filter-select {
  padding: 7px 10px; border: 1px solid rgba(0,0,0,0.12);
  border-radius: 7px; font-size: 12px; background: #fff;
}

.summary-strip {
  display: flex; background: #fff; border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px; overflow: hidden; margin-bottom: 14px;
}
.strip-item {
  flex: 1; padding: 10px 16px; text-align: center;
  border-right: 1px solid rgba(0,0,0,0.06);
}
.strip-item:last-child { border-right: none; }
.strip-label { display: block; font-size: 10px; color: #7a9489; text-transform: uppercase; letter-spacing: 0.6px; }
.strip-val   { display: block; font-size: 15px; font-weight: 600; color: #1a2e24; margin-top: 2px; }
.strip-val.green { color: #2d7a50; }
.strip-val.red   { color: #c0392b; }

.card { background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px; overflow: hidden; }
.clients-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.clients-table th {
  text-align: left; padding: 10px 14px; font-size: 10px;
  text-transform: uppercase; letter-spacing: 0.8px; color: #7a9489;
  background: #f8faf9; border-bottom: 1px solid rgba(0,0,0,0.08);
}
.clients-table td { padding: 10px 14px; border-bottom: 1px solid rgba(0,0,0,0.05); }
.clients-table tbody tr:hover { background: #f8faf9; }
.clients-table tbody tr:last-child td { border-bottom: none; }

.client-cell  { display: flex; align-items: center; gap: 8px; }
.client-av {
  width: 28px; height: 28px; border-radius: 50%; background: #d6f0df;
  color: #2d7a50; font-size: 9px; font-weight: 600;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.client-name  { font-size: 12px; font-weight: 500; color: #1a2e24; }
.client-phone { font-size: 10px; color: #7a9489; }
.zone-badge   { font-size: 10px; padding: 2px 8px; border-radius: 10px; font-weight: 500; }
.td-type      { font-size: 11px; color: #4a6357; text-transform: capitalize; }
.td-fee       { font-weight: 600; color: #2d7a50; }
.debt-amount  { font-size: 11px; color: #c0392b; font-weight: 600; }
.no-debt      { color: #7a9489; }
.td-num       { font-family: monospace; font-size: 11px; color: #4a6357; }
.td-actions   { display: flex; gap: 8px; }
.action-link  { font-size: 11px; color: #4caf76; text-decoration: none; }
.action-link:hover { text-decoration: underline; }

.status-badge { font-size: 9px; padding: 2px 7px; border-radius: 8px; font-weight: 600; text-transform: capitalize; }
.status--active    { background: #f0faf3; color: #2d7a50; }
.status--inactive  { background: #f5f5f5; color: #666; }
.status--suspended { background: #fef0f0; color: #c0392b; }

.empty-row { text-align: center; color: #7a9489; padding: 32px; }
.pagination { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 12px; border-top: 1px solid rgba(0,0,0,0.06); }
.page-btn { padding: 5px 12px; border: 1px solid rgba(0,0,0,0.12); border-radius: 6px; font-size: 11px; color: #4a6357; background: #fff; cursor: pointer; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-info { font-size: 11px; color: #7a9489; }
</style>
