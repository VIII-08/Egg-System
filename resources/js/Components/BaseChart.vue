<script setup>
import { computed } from 'vue'
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    ArcElement,
    Filler,
    BarController,
    LineController,
    PieController,
    DoughnutController
} from 'chart.js'
import { Chart } from 'vue-chartjs'

// Custom plugin to draw data labels inside pie/doughnut slices
const pieDataLabelPlugin = {
    id: 'pieDataLabels',
    afterDatasetsDraw(chart, args, pluginOptions) {
        if (chart.config.type !== 'pie' && chart.config.type !== 'doughnut') return

        const { ctx } = chart
        const opts = {
            color: '#0f172a', // slate-900
            fontSize: 16,
            fontFamily: 'Inter, system-ui, -apple-system, sans-serif',
            fontWeight: '600',
            formatter: (value) => Number(value)?.toLocaleString() ?? value,
            backgroundColor: null, // remove badge background by default
            padding: 0,
            borderRadius: 8,
            minDisplayValue: 0, // hide nothing by default
            shadowColor: 'rgba(0,0,0,0.08)',
            shadowBlur: 8,
            shadowOffsetY: 2,
            ...pluginOptions,
        }

        ctx.save()
        ctx.textAlign = 'center'
        ctx.textBaseline = 'middle'
        ctx.font = `${opts.fontWeight} ${opts.fontSize}px ${opts.fontFamily}`
        ctx.fillStyle = opts.color
        ctx.shadowColor = opts.shadowColor
        ctx.shadowBlur = opts.shadowBlur
        ctx.shadowOffsetY = opts.shadowOffsetY

        chart.data.datasets.forEach((dataset, datasetIndex) => {
            const meta = chart.getDatasetMeta(datasetIndex)
            meta.data.forEach((arc, index) => {
                const value = dataset.data[index]
                if (value === null || value === undefined || Number(value) === 0) return
                if (Math.abs(Number(value)) < opts.minDisplayValue) return

                const { x, y } = arc.tooltipPosition()
                const label = opts.formatter ? opts.formatter(value, index, dataset) : value
                const text = String(label)
                if (opts.backgroundColor) {
                    const textMetrics = ctx.measureText(text)
                    const padding = opts.padding
                    const boxWidth = textMetrics.width + padding * 2
                    const boxHeight = opts.fontSize + padding * 2
                    const boxX = x - boxWidth / 2
                    const boxY = y - boxHeight / 2

                    // draw soft background to make labels readable on any slice color
                    ctx.fillStyle = opts.backgroundColor
                    const radius = opts.borderRadius
                    ctx.beginPath()
                    ctx.moveTo(boxX + radius, boxY)
                    ctx.lineTo(boxX + boxWidth - radius, boxY)
                    ctx.quadraticCurveTo(boxX + boxWidth, boxY, boxX + boxWidth, boxY + radius)
                    ctx.lineTo(boxX + boxWidth, boxY + boxHeight - radius)
                    ctx.quadraticCurveTo(boxX + boxWidth, boxY + boxHeight, boxX + boxWidth - radius, boxY + boxHeight)
                    ctx.lineTo(boxX + radius, boxY + boxHeight)
                    ctx.quadraticCurveTo(boxX, boxY + boxHeight, boxX, boxY + boxHeight - radius)
                    ctx.lineTo(boxX, boxY + radius)
                    ctx.quadraticCurveTo(boxX, boxY, boxX + radius, boxY)
                    ctx.closePath()
                    ctx.fill()
                }

                ctx.fillStyle = opts.color
                ctx.shadowColor = 'transparent' // keep text crisp
                ctx.shadowBlur = 0
                ctx.fillText(text, x, y)
            })
        })

        ctx.restore()
    },
}

// Custom plugin to draw labels on top of bar elements
const barDataLabelPlugin = {
    id: 'barDataLabels',
    afterDatasetsDraw(chart, args, pluginOptions) {
        if (chart.config.type !== 'bar') return

        const { ctx } = chart
        const opts = {
            enabled: true,
            color: '#111827',
            fontSize: 13,
            fontFamily: 'Inter, system-ui, -apple-system, sans-serif',
            fontWeight: '700',
            formatter: (value) => Number(value)?.toLocaleString() ?? value,
            minDisplayValue: 0,
            offset: 6,
            ...pluginOptions,
        }

        if (!opts.enabled) return

        ctx.save()
        ctx.textAlign = 'center'
        ctx.textBaseline = 'bottom'
        ctx.font = `${opts.fontWeight} ${opts.fontSize}px ${opts.fontFamily}`
        ctx.fillStyle = opts.color

        chart.data.datasets.forEach((dataset, datasetIndex) => {
            const meta = chart.getDatasetMeta(datasetIndex)
            meta.data.forEach((bar, index) => {
                const value = dataset.data[index]
                if (value === null || value === undefined) return
                if (Math.abs(Number(value)) < opts.minDisplayValue) return

                const { x, y } = bar.tooltipPosition()
                const label = opts.formatter ? opts.formatter(value, index, dataset) : value
                ctx.fillText(label, x, y - opts.offset)
            })
        })

        ctx.restore()
    },
}

// Register all Chart.js components explicitly to prevent tree-shaking issues in production builds
ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    ArcElement,
    Filler,
    BarController,
    LineController,
    PieController,
    DoughnutController,
    pieDataLabelPlugin,
    barDataLabelPlugin
)

const props = defineProps({
    chartData: { type: Object, required: true },
    chartOptions: { type: Object, default: () => ({ responsive: true, maintainAspectRatio: false }) },
    chartType: { type: String, required: true },
})

const resolvedOptions = computed(() => {
    const userOptions = props.chartOptions || {}
    const userPlugins = userOptions.plugins || {}
    const isBar = props.chartType === 'bar'

    return {
        responsive: userOptions.responsive ?? true,
        maintainAspectRatio: userOptions.maintainAspectRatio ?? false,
        ...userOptions,
        plugins: {
            ...userPlugins,
            pieDataLabels: {
                color: '#0f172a',
                fontSize: 16,
                fontWeight: '700',
                backgroundColor: null,
                padding: 0,
                borderRadius: 8,
                minDisplayValue: 0,
                shadowColor: 'rgba(0,0,0,0.08)',
                shadowBlur: 8,
                shadowOffsetY: 2,
                ...(userPlugins.pieDataLabels || {}),
            },
            barDataLabels: {
                enabled: isBar,
                color: '#111827',
                fontSize: 13,
                fontWeight: '700',
                minDisplayValue: 0,
                offset: 6,
                formatter: (value) => Number(value)?.toLocaleString() ?? value,
                ...(userPlugins.barDataLabels || {}),
            },
        },
    }
})

// Dynamically render the correct chart type component
</script>
<template>
  <component :is="Chart" :type="chartType" :data="chartData" :options="resolvedOptions" />
</template>