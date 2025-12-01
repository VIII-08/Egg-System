<script setup>
import { Head } from '@inertiajs/vue3';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import BaseChart from '@/Components/BaseChart.vue';

const props = defineProps({
    chartData: Object,
    summary: Object,
    topProducts: Array,
});

const cards = [
    { key: 'projectedTotal', label: 'Projected Volume (next 14 days)', suffix: 'pcs' },
    { key: 'avgDailyForecast', label: 'Average Daily Forecast', suffix: 'pcs/day' },
    { key: 'recentDailyAverage', label: 'Recent 7-Day Average', suffix: 'pcs/day' },
];
</script>

<template>
    <Head title="Marketing Forecasting" />

    <StaffLayout>
        <template #header>Marketing Forecasting</template>

        <div class="space-y-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <p class="text-gray-600">
                    Align selling strategies with the next two weeks of projected demand so you can reserve customers, plan deliveries, and launch promos ahead of peak days.
                </p>
                <p class="text-xs text-gray-400 mt-2">
                    Last forecast update: <strong>{{ summary?.generatedAt ?? 'N/A' }}</strong>
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div v-for="card in cards" :key="card.key" class="bg-white p-6 rounded-lg shadow">
                    <p class="text-sm uppercase text-gray-500">{{ card.label }}</p>
                    <p class="text-4xl font-bold text-gray-800 mt-2">
                        {{ (summary?.[card.key] ?? 0).toLocaleString() }} <span class="text-base font-medium">{{ card.suffix }}</span>
                    </p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                 <h3 class="text-lg font-semibold text-gray-800 mb-4">Projected Sales (Next 14 Days)</h3>
                 <div class="h-96">
                    <BaseChart
                        v-if="chartData"
                        chartType="line"
                        :chartData="{ labels: chartData.labels, datasets: [{
                            label: 'Projected Sales (pcs)',
                            borderColor: '#F97316',
                            backgroundColor: 'rgba(249, 115, 22, 0.15)',
                            data: chartData.forecast,
                            tension: 0.2,
                            fill: true,
                        }] }"
                    />
                    <div v-else class="flex h-full items-center justify-center text-gray-400">
                        Forecast data is not available. Run the forecasting script to refresh projections.
                    </div>
                 </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Products to Push</h3>
                <div v-if="topProducts && topProducts.length" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="product in topProducts" :key="product.name" class="border rounded-lg p-4">
                        <p class="text-xs uppercase text-gray-400 tracking-wide">{{ product.name }}</p>
                        <p class="text-3xl font-bold text-gray-800 mt-2">
                            {{ product.total.toLocaleString() }} pcs
                        </p>
                        <p class="text-sm text-gray-500">≈ {{ product.avgDaily }} pcs/day</p>
                        <div class="mt-3 text-xs text-gray-500 space-y-1">
                            <p>MAE: <strong>{{ product.mae ?? 'N/A' }}</strong></p>
                            <p>RMSE: <strong>{{ product.rmse ?? 'N/A' }}</strong></p>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-gray-400 py-12">
                    No product-level forecasts available yet.
                </div>
            </div>
        </div>
    </StaffLayout>
</template>



