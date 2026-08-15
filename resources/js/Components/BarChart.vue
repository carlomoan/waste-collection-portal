<template>
  <div class="bar-chart">
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
    type: 'bar',
    data: props.data,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      ...props.options,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          titleFont: { size: 14, weight: 'bold' },
          bodyFont: { size: 13 },
          padding: 12,
          cornerRadius: 8,
          callbacks: {
            label: function(context) {
              let label = context.label || ''
              let value = context.parsed.y
              if (label) {
                label += ': '
              }
              if (value !== null && value !== undefined) {
                label += 'TZS ' + value.toLocaleString()
              }
              return label
            }
          }
        },
        ...props.options?.plugins || {}
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { 
            color: '#6b7280',
            font: { size: 12 }
          }
        },
        y: {
          grid: { 
            color: '#e5e7eb',
            drawBorder: false
          },
          ticks: {
            color: '#6b7280',
            font: { size: 12 },
            callback: function(value) {
              return 'TZS ' + value.toLocaleString()
            }
          }
        },
        ...props.options?.scales || {}
      }
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
.bar-chart {
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