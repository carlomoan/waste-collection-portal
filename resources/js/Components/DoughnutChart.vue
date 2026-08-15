<template>
  <div class="doughnut-chart">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, onUnmounted } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
  data: {
    type: Object,
    default: () => ({})
  },
  options: {
    type: Object,
    default: () => ({})
  }
})

const chartCanvas = ref(null)
let chart = null

onMounted(() => {
  renderChart()
})

watch(() => props.data, () => {
  if (chart) {
    chart.data = props.data
    chart.update()
  }
})

function renderChart() {
  if (!chartCanvas.value) return
  
  const ctx = chartCanvas.value.getContext('2d')
  
  chart = new Chart(ctx, {
    type: 'doughnut',
    data: props.data,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 20,
            usePointStyle: true,
            pointStyle: 'circle',
            font: { size: 12 }
          }
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          titleFont: { size: 14, weight: 'bold' },
          bodyFont: { size: 13 },
          padding: 12,
          cornerRadius: 8,
          callbacks: {
            label: function(context) {
              const label = context.label || ''
              const value = context.parsed
              const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0)
              const percentage = Math.round((value / total) * 100)
              return `${label}: ${percentage}% (${value} transactions)`
            }
          }
        },
        ...props.options?.plugins || {}
      },
      ...props.options || {}
    }
  })
}

function destroy() {
  if (chart) {
    chart.destroy()
    chart = null
  }
}

onUnmounted(() => {
  destroy()
})
</script>

<style scoped>
.doughnut-chart {
  position: relative;
  height: 100%;
  width: 100%;
}

canvas {
  position: absolute;
  top: 0;
  left: 0;
  width: 100% !important;
  height: 100% !important;
}
</style>