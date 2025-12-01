<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import TreasurerLayout from '@/Layouts/TreasurerLayout.vue';
import BaseChart from '@/Components/BaseChart.vue';

const props = defineProps({
    chartData: Object,
    summary: Object,
    topProducts: Array,
});

const lineChartConfig = computed(() => {
    if (!props.chartData) return null;

    return {
        labels: props.chartData.labels,
        datasets: [
            {
                label: 'Actual Monthly Sales (pcs)',
                borderColor: '#1D4ED8',
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                data: props.chartData.actual,
                tension: 0.2,
                fill: true,
            },
            {
                label: 'Forecasted Sales (pcs)',
                borderColor: '#F97316',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                borderDash: [6, 4],
                data: props.chartData.forecast,
                tension: 0.2,
            },
        ],
    };
});
</script>

<template>
    <Head title="Sales Forecasting" />

    <TreasurerLayout>
        <template #header>Sales Forecasting Overview</template>

        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <p class="text-sm text-gray-500 uppercase">Projected Volume (30 days)</p>
                    <p class="text-4xl font-bold text-gray-800 mt-2">
                        {{ summary?.next30Days?.toLocaleString() ?? '0' }} pcs
                    </p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <p class="text-sm text-gray-500 uppercase">Average Daily Forecast</p>
                    <p class="text-4xl font-bold text-gray-800 mt-2">
                        {{ summary?.avgDailyForecast ?? 0 }} pcs/day
                    </p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <p class="text-sm text-gray-500 uppercase">Last Model Run</p>
                    <p class="text-xl font-semibold text-gray-800 mt-2 break-words">
                        {{ summary?.generatedAt ?? 'N/A' }}
                    </p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                 <h3 class="text-lg font-semibold text-gray-800 mb-4">Monthly Sales vs Forecast</h3>
                 <div class="h-96">
                    <BaseChart
                        v-if="lineChartConfig"
                        chartType="line"
                        :chartData="lineChartConfig"
                    />
                    <div v-else class="flex h-full items-center justify-center text-gray-400">
                        Not enough data to display forecast.
                    </div>
                 </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Forecasted Products (Next 30 Days)</h3>

                <div v-if="topProducts && topProducts.length" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="product in topProducts" :key="product.name" class="border rounded-lg p-4">
                        <p class="text-sm uppercase text-gray-500">{{ product.name }}</p>
                        <p class="text-3xl font-bold text-gray-800 mt-2">
                            {{ product.next30Days.toLocaleString() }} pcs
                        </p>
                        <p class="text-sm text-gray-500">≈ {{ product.avgDaily }} pcs/day</p>
                        <div class="mt-4 text-xs text-gray-500 space-y-1">
                            <p>MAE: <strong>{{ product.mae ?? 'N/A' }}</strong></p>
                            <p>RMSE: <strong>{{ product.rmse ?? 'N/A' }}</strong></p>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-gray-400 py-12">
                    No forecast data available. Please run the forecasting script.
                </div>
            </div>
        </div>
    </TreasurerLayout>
</template>



