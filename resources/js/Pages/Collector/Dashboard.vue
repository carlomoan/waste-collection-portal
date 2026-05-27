<template>
  <CollectorLayout :title="'My Dashboard'">
    <div class="p-4 space-y-4">

      <!-- Today's summary -->
      <div class="grid grid-cols-2 gap-3">
        <StatCard label="Collected Today" :value="formatTZS(stats.today_total)"
                  icon="receipt" color="green" />
        <StatCard label="Transactions" :value="stats.today_count"
                  icon="list" color="blue" />
      </div>

      <!-- Quick record payment -->
      <div class="bg-white rounded-xl border border-gray-100 p-4">
        <h2 class="font-semibold text-gray-700 mb-3">Record Payment</h2>
        <ClientSearch @selected="openPaymentForm" />
      </div>

      <!-- Recent transactions -->
      <div class="bg-white rounded-xl border border-gray-100 p-4">
        <h2 class="font-semibold text-gray-700 mb-3">Recent Transactions</h2>
        <TransactionList :payments="recentPayments" />
      </div>

    </div>
  </CollectorLayout>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  stats: Object,
  recentPayments: Array,
})

const formatTZS = (amount) =>
  new Intl.NumberFormat('sw-TZ', { style: 'currency', currency: 'TZS',
    minimumFractionDigits: 0 }).format(amount)
</script>