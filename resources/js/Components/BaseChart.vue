<script setup>
import { h } from 'vue'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Filler } from 'chart.js'
import { Chart } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Filler)

const props = defineProps({
    chartData: { type: Object, required: true },
    chartOptions: { type: Object, default: () => ({ responsive: true, maintainAspectRatio: false }) },
    chartType: { type: String, required: true },
})

// Dynamically render the correct chart type component
</script>
<template>
  <component :is="Chart" :type="chartType" :data="chartData" :options="chartOptions" />
</template>